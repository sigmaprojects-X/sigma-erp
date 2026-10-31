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
}
