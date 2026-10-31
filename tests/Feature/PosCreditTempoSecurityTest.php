<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosCreditTempoSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected CustomerTier $tier;

    protected Product $product;

    protected ProductUnit $unit;

    protected User $cashier;

    protected CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->seed(AccountingSeeder::class);

        $this->company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $this->store = Store::where('code', 'STR-001')->firstOrFail();
        $this->tier = CustomerTier::where('code', 'RETAIL')->firstOrFail();
        $this->product = Product::where('sku', 'PRD-001')->firstOrFail();
        $this->unit = ProductUnit::where('product_id', $this->product->id)->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->shift = CashierShift::where('cashier_id', $this->cashier->id)->firstOrFail();
    }

    public function test_tempo_and_credit_amount_without_customer_are_rejected(): void
    {
        // 1. TEMPO tanpa customer_id harus ditolak (422)
        $resTempo = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'payment_method' => 'TEMPO',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $resTempo->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame(0, PosTransaction::count());

        // 2. SPLIT mode 2 dengan credit_amount > 0 tanpa customer_id harus ditolak (422)
        $resSplit = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'payment_method' => 'SPLIT',
            'split_payment_method' => 'CASH',
            'paid_amount' => 5000,
            'credit_amount' => 10000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $resSplit->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame(0, PosTransaction::count());

        // 3. CASH dengan credit_amount > 0 tanpa customer_id harus ditolak (422)
        $resCashCredit = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'payment_method' => 'CASH',
            'paid_amount' => 5000,
            'credit_amount' => 10000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $resCashCredit->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertSame(0, PosTransaction::count());
    }

    public function test_credit_limit_check_uses_lock_for_update_on_customer_across_tempo_split_and_cash_credit(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_tier_id' => $this->tier->id,
            'code' => 'CUST-TEMPO-01',
            'name' => 'Pelanggan Tempo Terkunci',
            'phone' => '08111222333',
            'credit_limit' => 50000,
            'current_credit_balance' => 0,
            'credit_term_days' => 30,
            'is_active' => true,
        ]);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = strtolower($query->sql);
        });

        // 1. TEMPO (15.000)
        $resTempo = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_id' => $customer->id,
            'payment_method' => 'TEMPO',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $resTempo->assertStatus(200);

        $lockIdx = null;
        $updateCreditIdx = null;
        foreach ($queries as $idx => $sql) {
            if ($lockIdx === null && str_contains($sql, 'customers') && str_contains($sql, 'for update')) {
                $lockIdx = $idx;
            }
            if ($updateCreditIdx === null && str_contains($sql, 'customers') && str_contains($sql, 'current_credit_balance')) {
                $updateCreditIdx = $idx;
            }
        }

        $this->assertNotNull($lockIdx, 'Customer harus diambil dengan lockForUpdate.');
        $this->assertNotNull($updateCreditIdx, 'Saldo piutang customer harus di-increment.');
        $this->assertLessThan(
            $updateCreditIdx,
            $lockIdx,
            'SELECT ... FOR UPDATE pada customers wajib dieksekusi SEBELUM pengecekan plafon dan increment current_credit_balance.'
        );
        $this->assertEquals(15000.0, (float) $customer->fresh()->current_credit_balance);

        // 2. SPLIT mode 2 dengan credit_amount = 10.000 (total hutang menjadi 25.000)
        $queries = [];
        $resSplit = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_id' => $customer->id,
            'payment_method' => 'SPLIT',
            'split_payment_method' => 'CASH',
            'paid_amount' => 5000,
            'credit_amount' => 10000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $resSplit->assertStatus(200);
        $this->assertNotEmpty(
            array_filter($queries, fn (string $sql) => str_contains($sql, 'customers') && str_contains($sql, 'for update'))
        );
        $this->assertEquals(25000.0, (float) $customer->fresh()->current_credit_balance);

        // 3. CASH dengan credit_amount = 10.000 (total hutang menjadi 35.000)
        $queries = [];
        $resCash = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_id' => $customer->id,
            'payment_method' => 'CASH',
            'paid_amount' => 5000,
            'credit_amount' => 10000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);
        $resCash->assertStatus(200);
        $this->assertNotEmpty(
            array_filter($queries, fn (string $sql) => str_contains($sql, 'customers') && str_contains($sql, 'for update'))
        );
        $this->assertEquals(35000.0, (float) $customer->fresh()->current_credit_balance);

        // 4. Transaksi berikutnya sebesar 30.000 (melebihi sisa plafon 15.000) harus ditolak (422)
        $resOver = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_id' => $customer->id,
            'payment_method' => 'TEMPO',
            'paid_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 2,
                ],
            ],
        ]);
        $resOver->assertStatus(422);
        $this->assertEquals(35000.0, (float) $customer->fresh()->current_credit_balance);
    }
}
