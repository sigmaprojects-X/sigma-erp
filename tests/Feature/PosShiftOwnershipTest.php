<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosShiftOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected Store $otherStore;

    protected CustomerTier $tier;

    protected Product $product;

    protected ProductUnit $unit;

    protected User $cashier;

    protected User $otherCashier;

    protected User $manager;

    protected User $otherStoreManager;

    protected User $salesUser;

    protected CashierShift $shift;

    protected CashierShift $otherCashierShift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->seed(AccountingSeeder::class);

        $this->company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $this->store = Store::where('code', 'STR-001')->firstOrFail();
        $this->otherStore = Store::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'STR-002',
            'name' => 'Toko Cabang Kedua',
        ]);

        $this->tier = CustomerTier::where('code', 'RETAIL')->firstOrFail();
        $this->product = Product::where('sku', 'PRD-001')->firstOrFail();
        $this->unit = ProductUnit::where('product_id', $this->product->id)->firstOrFail();

        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();

        $this->otherCashier = User::factory()->cashier()->create([
            'name' => 'Kasir Kedua',
            'email' => 'cashier2@sigma.test',
            'store_id' => $this->store->id,
        ]);

        $this->otherStoreManager = User::factory()->manager()->create([
            'name' => 'Manager Cabang 2',
            'email' => 'manager2@sigma.test',
            'store_id' => $this->otherStore->id,
        ]);

        $this->salesUser = User::factory()->create([
            'name' => 'Sales User',
            'email' => 'sales@sigma.test',
            'role' => 'sales',
            'store_id' => $this->store->id,
        ]);

        $this->shift = CashierShift::where('cashier_id', $this->cashier->id)->firstOrFail();
        $this->otherCashierShift = CashierShift::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_id' => $this->otherCashier->id,
            'shift_number' => 'SHF-0002',
            'starting_cash' => 150000,
            'status' => 'OPEN',
        ]);
    }

    public function test_cashier_cannot_act_on_another_cashiers_shift(): void
    {
        // 1. Checkout memakai shift kasir lain -> 403
        $checkoutRes = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->otherCashierShift->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $checkoutRes->assertStatus(403);

        // 2. Cash drop memakai shift kasir lain -> 403
        $dropRes = $this->actingAs($this->cashier)->postJson('/pos/cash-drop', [
            'shift_id' => $this->otherCashierShift->id,
            'amount' => 10000,
            'reason' => 'Setor kas',
        ]);
        $dropRes->assertStatus(403);

        // 3. Blind close memakai shift kasir lain -> 403
        $closeRes = $this->actingAs($this->cashier)->postJson('/pos/blind-close', [
            'shift_id' => $this->otherCashierShift->id,
            'actual_cash_counted' => 150000,
        ]);
        $closeRes->assertStatus(403);

        // 4. Recent transactions memakai shift kasir lain -> 403
        $recentRes = $this->actingAs($this->cashier)->getJson('/pos/recent-transactions?shift_id=' . $this->otherCashierShift->id);
        $recentRes->assertStatus(403);
    }

    public function test_cashier_cannot_act_on_closed_shift_or_shift_from_different_store(): void
    {
        $closedShift = CashierShift::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
            'shift_number' => 'SHF-CLOSED',
            'starting_cash' => 100000,
            'status' => 'CLOSED',
        ]);

        $otherStoreShift = CashierShift::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->otherStore->id,
            'cashier_id' => $this->cashier->id,
            'shift_number' => 'SHF-STR2',
            'starting_cash' => 100000,
            'status' => 'OPEN',
        ]);

        // Shift CLOSED milik sendiri -> 403
        $this->actingAs($this->cashier)->postJson('/pos/cash-drop', [
            'shift_id' => $closedShift->id,
            'amount' => 10000,
        ])->assertStatus(403);

        $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $closedShift->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ])->assertStatus(403);

        // Shift OPEN milik sendiri tetapi beda store_id -> 403
        $this->actingAs($this->cashier)->postJson('/pos/cash-drop', [
            'shift_id' => $otherStoreShift->id,
            'amount' => 10000,
        ])->assertStatus(403);

        $this->actingAs($this->cashier)->postJson('/pos/blind-close', [
            'shift_id' => $otherStoreShift->id,
            'actual_cash_counted' => 100000,
        ])->assertStatus(403);
    }

    public function test_manager_can_act_on_shift_in_managed_store_while_other_store_manager_and_sales_get_403(): void
    {
        // Manager dari toko lain (STR-002) ditolak (403) saat mengakses shift di STR-001
        $this->actingAs($this->otherStoreManager)->postJson('/pos/cash-drop', [
            'shift_id' => $this->shift->id,
            'amount' => 10000,
        ])->assertStatus(403);

        $this->actingAs($this->otherStoreManager)->postJson('/pos/blind-close', [
            'shift_id' => $this->shift->id,
            'actual_cash_counted' => 100000,
        ])->assertStatus(403);

        // Role selain cashier/manager/store_head/owner (misal sales) ditolak (403)
        $this->actingAs($this->salesUser)->postJson('/pos/cash-drop', [
            'shift_id' => $this->shift->id,
            'amount' => 10000,
        ])->assertStatus(403);

        // Manager dari toko yang sama (STR-001) diizinkan bertindak atas shift di tokonya (200)
        $this->actingAs($this->manager)->postJson('/pos/cash-drop', [
            'shift_id' => $this->shift->id,
            'amount' => 10000,
            'reason' => 'Setor kas oleh manajer toko',
        ])->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->actingAs($this->manager)->postJson('/pos/blind-close', [
            'shift_id' => $this->shift->id,
            'actual_cash_counted' => 90000,
            'notes' => 'Tutup shift oleh manajer toko',
        ])->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
