<?php

namespace Tests\Feature;

use App\Http\Controllers\MaintenanceController;
use App\Models\CashierShift;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaintenanceResetDemoTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $cashier;

    protected Company $company;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();

        $this->company = Company::firstOrFail();
        $this->store = Store::firstOrFail();
        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
    }

    protected function tearDown(): void
    {
        putenv('SIGMA_ALLOW_DEMO_RESET');
        unset($_ENV['SIGMA_ALLOW_DEMO_RESET'], $_SERVER['SIGMA_ALLOW_DEMO_RESET']);

        parent::tearDown();
    }

    public function test_manager_and_cashier_are_forbidden_from_running_reset_demo(): void
    {
        $managerResponse = $this->actingAs($this->manager)->post(route('settings.maintenance.reset-demo'), [
            'confirm_text' => 'RESET DEMO',
        ]);
        $managerResponse->assertStatus(403);

        $cashierResponse = $this->actingAs($this->cashier)->post(route('settings.maintenance.reset-demo'), [
            'confirm_text' => 'RESET DEMO',
        ]);
        $cashierResponse->assertStatus(403);

        $this->assertDatabaseHas('cashier_shifts', ['shift_number' => 'SHF-0001']);
    }

    public function test_reset_demo_is_disabled_in_production_unless_explicit_flag_is_enabled(): void
    {
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
        putenv('SIGMA_ALLOW_DEMO_RESET=false');
        $_ENV['SIGMA_ALLOW_DEMO_RESET'] = 'false';

        $blockedResponse = $this->actingAs($this->owner)->post(route('settings.maintenance.reset-demo'), [
            'confirm_text' => 'RESET DEMO',
        ]);

        $blockedResponse->assertRedirect(route('settings.maintenance.index'));
        $blockedResponse->assertSessionHas('error');
        $this->assertDatabaseHas('cashier_shifts', ['shift_number' => 'SHF-0001']);

        // Sekarang aktifkan flag eksplisit SIGMA_ALLOW_DEMO_RESET=true di production
        putenv('SIGMA_ALLOW_DEMO_RESET=true');
        $_ENV['SIGMA_ALLOW_DEMO_RESET'] = 'true';
        $_SERVER['SIGMA_ALLOW_DEMO_RESET'] = 'true';

        $allowedResponse = $this->actingAs($this->owner)->post(route('settings.maintenance.reset-demo'), [
            'confirm_text' => 'RESET DEMO',
        ]);

        $allowedResponse->assertRedirect(route('settings.maintenance.index'));
        $allowedResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('cashier_shifts', ['shift_number' => 'SHF-0001']);
    }

    public function test_reset_demo_clears_all_transactional_tables_and_resets_derived_balances(): void
    {
        $tier = CustomerTier::firstOrFail();

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_tier_id' => $tier->id,
            'code' => 'CUST-TEST-01',
            'name' => 'Pelanggan Uji Reset',
            'credit_limit' => 5000000,
            'current_credit_balance' => 750000,
            'deposit_balance' => 150000,
            'loyalty_points' => 250,
            'lifetime_points' => 500,
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'company_id' => $this->company->id,
            'code' => 'SUP-TEST-01',
            'name' => 'Supplier Uji Reset',
            'deposit_balance' => 300000,
            'is_active' => true,
        ]);

        $coa = ChartOfAccount::create([
            'company_id' => $this->company->id,
            'code' => '1001-999',
            'name' => 'Kas Uji',
            'type' => 'ASSET',
            'normal_balance' => 'DEBIT',
            'is_active' => true,
        ]);

        // Isi tabel-tabel transaksi yang sebelumnya terlewat
        DB::table('cash_transactions')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'transaction_number' => 'CSH-TEST-001',
            'type' => 'CASH_IN',
            'transaction_date' => now()->toDateString(),
            'destination_account_id' => $coa->id,
            'category_account_id' => $coa->id,
            'amount' => 50000,
            'created_by' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_deposits')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'deposit_number' => 'DEP-C-001',
            'type' => 'TOP_UP',
            'amount' => 150000,
            'balance_before' => 0,
            'balance_after' => 150000,
            'payment_method' => 'CASH',
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('supplier_deposits')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'supplier_id' => $supplier->id,
            'deposit_number' => 'DEP-S-001',
            'type' => 'TOP_UP',
            'amount' => 300000,
            'balance_before' => 0,
            'balance_after' => 300000,
            'payment_method' => 'CASH',
            'transaction_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_point_mutations')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'type' => 'EARN',
            'points' => 250,
            'balance_before' => 0,
            'balance_after' => 250,
            'description' => 'Poin belanja uji',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_adjustments')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'adjustment_number' => 'ADJ-TEST-001',
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'DAMAGE_WASTE',
            'status' => 'COMPLETED',
            'created_by' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('stock_movements')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'movement_number' => 'MOV-TEST-001',
            'movement_type' => 'OUT',
            'movement_date' => now()->toDateString(),
            'category_reason' => 'INTERNAL_USE',
            'status' => 'COMPLETED',
            'created_by' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vouchers')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'code' => 'VCH-CUSTOM-999',
            'type' => 'VALUE',
            'amount' => 25000,
            'min_spend' => 50000,
            'status' => 'ISSUED',
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDays(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fixed_assets')->insert([
            'id' => (string) Str::uuid(),
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'asset_code' => 'AST-TEST-001',
            'name' => 'Mesin Kasir Uji',
            'category' => 'EQUIPMENT',
            'acquisition_date' => now()->toDateString(),
            'acquisition_cost' => 5000000,
            'salvage_value' => 500000,
            'useful_life_months' => 48,
            'monthly_depreciation_amount' => 93750,
            'accumulated_depreciation' => 0,
            'book_value' => 5000000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $paymentId = (string) Str::uuid();
        $shift = CashierShift::firstOrFail();
        $posTxId = (string) Str::uuid();

        DB::table('pos_transactions')->insert([
            'id' => $posTxId,
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_shift_id' => $shift->id,
            'cashier_id' => $this->cashier->id,
            'customer_tier_id' => $tier->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 100000,
            'payment_method' => 'TEMPO',
            'paid_amount' => 0,
            'change_amount' => 0,
            'credit_amount' => 100000,
            'status' => 'COMPLETED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_payments')->insert([
            'id' => $paymentId,
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'received_by' => $this->owner->id,
            'payment_number' => 'PAY-TEST-001',
            'payment_date' => now()->toDateString(),
            'total_amount' => 50000,
            'payment_method' => 'CASH',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customer_payment_items')->insert([
            'id' => (string) Str::uuid(),
            'customer_payment_id' => $paymentId,
            'pos_transaction_id' => $posTxId,
            'amount' => 50000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $this->assertEquals(100, (float) $customProduct->total_quantity);

        $response = $this->actingAs($this->owner)->post(route('settings.maintenance.reset-demo'), [
            'confirm_text' => 'RESET DEMO',
        ]);

        $response->assertRedirect(route('settings.maintenance.index'));
        $response->assertSessionHas('success');

        $this->assertSame(0, DB::table('cash_transactions')->count());
        $this->assertSame(0, DB::table('customer_deposits')->count());
        $this->assertSame(0, DB::table('supplier_deposits')->count());
        $this->assertSame(0, DB::table('customer_point_mutations')->count());
        $this->assertSame(0, DB::table('stock_adjustments')->count());
        $this->assertSame(0, DB::table('stock_movements')->count());
        $this->assertDatabaseMissing('vouchers', ['code' => 'VCH-CUSTOM-999']);
        $this->assertSame(0, DB::table('fixed_assets')->count());
        $this->assertSame(0, DB::table('customer_payment_items')->count());
        $this->assertSame(0, DB::table('customer_payments')->count());

        $customer->refresh();
        $this->assertEquals(0.0, (float) $customer->current_credit_balance);
        $this->assertEquals(0, (int) $customer->loyalty_points);
        $this->assertEquals(0.0, (float) $customer->deposit_balance);

        $customProduct->refresh();
        $this->assertEquals(0.0, (float) $customProduct->total_quantity);
    }

    public function test_reset_demo_rolls_back_deletions_when_seeding_fails(): void
    {
        $this->app->bind(MaintenanceController::class, function () {
            return new class extends MaintenanceController
            {
                protected function runDemoSeeders(): void
                {
                    throw new \RuntimeException('Simulated seeding failure inside transaction');
                }
            };
        });

        $response = $this->actingAs($this->owner)->post(route('settings.maintenance.reset-demo'), [
            'confirm_text' => 'RESET DEMO',
        ]);

        $response->assertRedirect(route('settings.maintenance.index'));
        $response->assertSessionHas('error');

        // Karena hapus dan seed dalam satu transaksi, shift SHF-0001 dan stok PRD-001 harus tetap utuh
        $this->assertDatabaseHas('cashier_shifts', ['shift_number' => 'SHF-0001']);
        $this->assertEquals(100.0, (float) Product::where('sku', 'PRD-001')->value('total_quantity'));
    }
}
