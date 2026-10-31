<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use App\Services\PosService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanupAndFinalHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_junk_files_and_empty_scratch_scripts_folders_are_removed(): void
    {
        $forbiddenPaths = [
            base_path('bootstrap()'),
            base_path('getPdo()'),
            base_path('test_users_page.php'),
            base_path('database/database.sqlite'),
            base_path('scratch'),
            base_path('scripts'),
        ];

        foreach ($forbiddenPaths as $path) {
            $this->assertFileDoesNotExist($path, "File/folder sampah masih ada: {$path}");
        }
    }

    public function test_open_shift_rejects_negative_starting_cash_unconditionally_and_enforces_unique_open_shift_per_cashier(): void
    {
        $this->seedMinimalData();

        $company = Company::firstOrFail();
        $store = Store::firstOrFail();
        $cashier = User::where('role', 'cashier')->firstOrFail();
        $posService = app(PosService::class);

        $company->update(['cashier_require_starting_cash' => false]);

        try {
            $posService->openShift($company->fresh(), $store, $cashier->id, -10000);
            $this->fail('openShift harus menolak startingCash < 0 tanpa syarat meskipun cashier_require_starting_cash = false.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('negatif', strtolower($e->getMessage()));
        }

        $firstShift = $posService->openShift($company->fresh(), $store, $cashier->id, 100000);
        $this->assertSame('OPEN', $firstShift->status);

        // Unique partial index di level database mencegah 2 shift OPEN untuk kasir yang sama
        $this->expectException(QueryException::class);

        CashierShift::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'cashier_id' => $cashier->id,
            'shift_number' => 'SHF-DUP-OPEN-01',
            'starting_cash' => 50000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);
    }

    public function test_login_checks_is_active_before_auth_attempt_so_inactive_account_does_not_leak_password_truth(): void
    {
        $this->seedMinimalData();

        $cashier = User::where('role', 'cashier')->firstOrFail();
        $cashier->update([
            'is_active' => false,
            'password' => bcrypt('SecretPass123!'),
        ]);

        // 1. Login dengan password SALAH pada akun nonaktif
        $respWrongPass = $this->from(route('login'))->post(route('login.post'), [
            'email' => $cashier->email,
            'password' => 'WrongPass999!',
        ]);
        $respWrongPass->assertRedirect(route('login'));
        $respWrongPass->assertSessionHasErrors([
            'email' => 'Akun Anda telah dinonaktifkan atau masa kontrak telah berakhir. Hubungi Administrator.',
        ]);
        $this->assertFalse(auth()->check());

        // 2. Login dengan password BENAR pada akun nonaktif
        $respCorrectPass = $this->from(route('login'))->post(route('login.post'), [
            'email' => $cashier->email,
            'password' => 'SecretPass123!',
        ]);
        $respCorrectPass->assertRedirect(route('login'));
        $respCorrectPass->assertSessionHasErrors([
            'email' => 'Akun Anda telah dinonaktifkan atau masa kontrak telah berakhir. Hubungi Administrator.',
        ]);
        $this->assertFalse(auth()->check());
    }

    public function test_pos_checkout_hides_raw_message_for_non_business_exception_and_logs_details(): void
    {
        $this->seedMinimalData();

        $company = Company::firstOrFail();
        $store = Store::firstOrFail();
        $cashier = User::where('role', 'cashier')->firstOrFail();
        $product = \App\Models\Product::firstOrFail();
        $unit = \App\Models\ProductUnit::where('product_id', $product->id)->firstOrFail();

        $shift = app(PosService::class)->openShift($company, $store, $cashier->id, 100000);

        $mockPosService = \Mockery::mock(PosService::class);
        $mockPosService->shouldReceive('checkout')
            ->once()
            ->andThrow(new \ErrorException('SQLSTATE[42P01]: Undefined table: secret_internal_table at /var/www/sigma'));
        $this->app->instance(PosService::class, $mockPosService);

        \Illuminate\Support\Facades\Log::spy();

        $response = $this->actingAs($cashier)->postJson(route('pos.checkout'), [
            'shift_id' => $shift->id,
            'payment_method' => 'CASH',
            'paid_amount' => 50000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(500);
        $response->assertJson(['success' => false]);
        $this->assertStringNotContainsString('SQLSTATE', (string) $response->json('message'));
        $this->assertStringNotContainsString('secret_internal_table', (string) $response->json('message'));

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->once();
    }

    public function test_destroy_product_blocks_loyalty_redemptions_and_promotions_and_deletes_images_only_after_commit(): void
    {
        $this->seedMinimalData();

        $company = Company::firstOrFail();
        $store = Store::firstOrFail();
        $owner = User::where('role', 'owner')->firstOrFail();
        $tier = \App\Models\CustomerTier::firstOrFail();
        $customer = \App\Models\Customer::first() ?? \App\Models\Customer::create([
            'company_id' => $company->id,
            'customer_tier_id' => $tier->id,
            'code' => 'CUST-RDM-01',
            'name' => 'Pelanggan Uji Reward',
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\Storage::fake('public');

        // 1. Produk dengan riwayat penukaran reward loyalty (loyalty_reward_redemptions) harus ditolak dihapus
        $productWithRedemption = \App\Models\Product::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'sku' => 'SKU-REWARD-01',
            'name' => 'Merchandise Payung SIGMA',
            'slug' => 'merchandise-payung-sigma',
            'category' => 'Merchandise',
            'cost_price' => 25000,
            'last_purchase_price' => 25000,
            'total_stock_value' => 0,
            'total_quantity' => 0,
            'is_active' => true,
        ]);

        $reward = \App\Models\LoyaltyReward::create([
            'company_id' => $company->id,
            'code' => 'RWD-PAYUNG-01',
            'name' => 'Hadiah Payung SIGMA',
            'product_id' => $productWithRedemption->id,
            'points_required' => 100,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        \App\Models\LoyaltyRewardRedemption::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'loyalty_reward_id' => $reward->id,
            'redemption_number' => 'RDM-TEST-0001',
            'points_used' => 100,
            'quantity' => 1,
            'redemption_date' => now()->toDateString(),
            'created_by' => $owner->id,
        ]);

        $respBlocked = $this->actingAs($owner)
            ->from(route('master.products'))
            ->delete(route('master.products.destroy', $productWithRedemption->id));

        $respBlocked->assertRedirect(route('master.products'));
        $respBlocked->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $productWithRedemption->id]);

        // 2. Pastikan penghapusan file gambar hanya dieksekusi SETELAH transaksi DB commit
        \Illuminate\Support\Facades\Storage::disk('public')->put('products/main-test.jpg', 'dummy-main');
        \Illuminate\Support\Facades\Storage::disk('public')->put('products/gallery/gal-1.jpg', 'dummy-gal');

        $productRollback = \App\Models\Product::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'sku' => 'SKU-ROLLBACK-IMG',
            'name' => 'Barang Uji Rollback Gambar',
            'slug' => 'barang-uji-rollback-gambar',
            'category' => 'Umum',
            'cost_price' => 10000,
            'last_purchase_price' => 10000,
            'total_stock_value' => 0,
            'total_quantity' => 0,
            'image_path' => 'products/main-test.jpg',
            'additional_images' => ['products/gallery/gal-1.jpg'],
            'is_active' => true,
        ]);

        // Pasang listener yang menggagalkan transaksi saat Product::deleting dipicu di dalam DB::transaction
        $shouldFailDelete = true;
        \App\Models\Product::deleting(function ($model) use ($productRollback, &$shouldFailDelete) {
            if ($shouldFailDelete && $model->id === $productRollback->id) {
                throw new \RuntimeException('Simulated DB failure inside transaction before commit');
            }
        });

        try {
            $this->actingAs($owner)->delete(route('master.products.destroy', $productRollback->id));
        } catch (\Throwable $e) {
            // Expected rollback
        }

        // Karena transaksi gagal/rollback, file gambar TIDAK BOLEH sudah terhapus!
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('products/main-test.jpg');
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('products/gallery/gal-1.jpg');
        $this->assertDatabaseHas('products', ['id' => $productRollback->id]);

        // Sekarang izinkan transaksi commit dan pastikan gambar terhapus setelah commit
        $shouldFailDelete = false;
        $respSuccess = $this->actingAs($owner)->delete(route('master.products.destroy', $productRollback->id));
        $respSuccess->assertRedirect(route('master.products'));
        $this->assertDatabaseMissing('products', ['id' => $productRollback->id]);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('products/main-test.jpg');
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('products/gallery/gal-1.jpg');
    }

    public function test_readme_contains_sigma_installation_system_requirements_and_backup_restore_guide(): void
    {
        $readmePath = base_path('README.md');
        $this->assertFileExists($readmePath);

        $content = file_get_contents($readmePath);

        $this->assertStringNotContainsString('## About Laravel', $content);
        $this->assertStringContainsString('PHP 8.3', $content);
        $this->assertStringContainsString('PostgreSQL 15+', $content);
        $this->assertStringContainsString('BACKUP-DATABASE.bat', $content);
        $this->assertStringContainsString('RESTORE-DATABASE.bat', $content);
    }
}
