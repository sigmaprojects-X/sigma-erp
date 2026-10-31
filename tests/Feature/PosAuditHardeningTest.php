<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Database\Seeders\UserGroupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosAuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected Store $branchStore;

    protected User $cashier;

    protected User $branchCashier;

    protected User $manager;

    protected CashierShift $activeShift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->seed(AccountingSeeder::class);
        $this->seed(UserGroupSeeder::class);

        $this->company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $this->store = Store::where('code', 'STR-001')->firstOrFail();

        $this->branchStore = Store::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'STR-002',
            'name' => 'Toko Cabang Surabaya',
        ]);

        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->cashier->update(['store_id' => $this->store->id]);

        $this->branchCashier = User::factory()->cashier()->create([
            'name' => 'Kasir Cabang',
            'email' => 'kasir_cabang@sigma.test',
            'store_id' => $this->branchStore->id,
            'user_group_id' => $this->cashier->user_group_id,
        ]);

        $this->manager = User::where('role', 'manager')->firstOrFail();
        $this->manager->update(['manager_pin' => '123456']);

        $this->activeShift = CashierShift::where('cashier_id', $this->cashier->id)->firstOrFail();
    }

    public function test_cashier_can_access_return_print_note(): void
    {
        $customer = Customer::first();
        $tier = CustomerTier::first();

        $tx = PosTransaction::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_shift_id' => $this->activeShift->id,
            'cashier_id' => $this->cashier->id,
            'customer_id' => $customer?->id,
            'customer_tier_id' => $tier->id,
            'invoice_number' => 'POS-TEST-0001',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 50000,
            'paid_amount' => 50000,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
        ]);

        $return = PosReturn::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'pos_transaction_id' => $tx->id,
            'cashier_shift_id' => $this->activeShift->id,
            'customer_id' => $customer?->id,
            'processed_by' => $this->cashier->id,
            'return_number' => 'RET/TEST/0001',
            'return_date' => now()->toDateString(),
            'total_refund_amount' => 15000,
            'refund_method' => 'CASH',
            'status' => 'COMPLETED',
            'reason' => 'Penggantian barang cacat fisik',
        ]);

        $response = $this->actingAs($this->cashier)->get(route('sales.returns.print', $return->id));

        $response->assertStatus(200);
    }

    public function test_open_shift_assigns_correct_store_for_branch_cashier(): void
    {
        $response = $this->actingAs($this->branchCashier)->postJson(route('pos.open-shift'), [
            'starting_cash' => 200000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $shift = CashierShift::findOrFail($response->json('shift.id'));
        $this->assertEquals($this->branchStore->id, $shift->store_id, 'Shift kasir cabang harus terasosiasi ke cabang toko kasir bersangkutan.');
        $this->assertEquals($this->branchCashier->id, $shift->cashier_id);
    }

    public function test_reprint_past_shift_with_manager_pin(): void
    {
        // Past shift yang sudah closed
        $pastShift = CashierShift::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
            'shift_number' => 'SHF-PAST-01',
            'status' => 'CLOSED',
        ]);

        $tier = CustomerTier::first();
        $pastTx = PosTransaction::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_shift_id' => $pastShift->id,
            'cashier_id' => $this->cashier->id,
            'customer_tier_id' => $tier->id,
            'invoice_number' => 'POS-PAST-0002',
            'subtotal' => 75000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 75000,
            'paid_amount' => 75000,
            'payment_method' => 'CASH',
            'status' => 'COMPLETED',
        ]);

        // 1. Tanpa PIN Manager -> 403
        $resNoPin = $this->actingAs($this->cashier)->postJson(route('pos.reprint', $pastTx->id), [
            'shift_id' => $this->activeShift->id,
        ]);
        $resNoPin->assertStatus(403);

        // 2. Dengan PIN Manager valid -> 200
        $resWithPin = $this->actingAs($this->cashier)->postJson(route('pos.reprint', $pastTx->id), [
            'shift_id' => $this->activeShift->id,
            'manager_pin' => '123456',
        ]);
        $resWithPin->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
