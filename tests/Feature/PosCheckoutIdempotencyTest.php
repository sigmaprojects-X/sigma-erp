<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\StockMutation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosCheckoutIdempotencyTest extends TestCase
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

    public function test_duplicate_checkout_with_same_idempotency_key_returns_same_transaction_and_deducts_stock_once(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'shift_id' => $this->shift->id,
            'idempotency_key' => $idempotencyKey,
            'payment_method' => 'CASH',
            'paid_amount' => 50000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $firstResponse = $this->actingAs($this->cashier)->postJson('/pos/checkout', $payload);
        $firstResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $secondResponse = $this->actingAs($this->cashier)->postJson('/pos/checkout', $payload);
        $secondResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Harus mengembalikan transaksi dan nomor faktur yang sama persis
        $this->assertSame($firstResponse->json('transaction_id'), $secondResponse->json('transaction_id'));
        $this->assertSame($firstResponse->json('invoice_number'), $secondResponse->json('invoice_number'));

        // Hanya boleh ada 1 record PosTransaction dengan idempotency_key tersebut
        $this->assertSame(1, PosTransaction::count());
        $tx = PosTransaction::firstOrFail();
        $this->assertSame($idempotencyKey, $tx->idempotency_key);

        // Stok hanya boleh terpotong satu kali (100 - 2 = 98, bukan 96)
        $stock = ProductStock::where('store_id', $this->store->id)
            ->where('product_id', $this->product->id)
            ->firstOrFail();
        $this->assertEquals(98.0, (float) $stock->physical_quantity);
        $this->assertSame(1, StockMutation::where('product_id', $this->product->id)->where('mutation_type', 'SALE')->count());
    }

    public function test_pos_frontend_generates_and_sends_idempotency_key_per_cart(): void
    {
        $response = $this->actingAs($this->cashier)->get('/pos');

        $response->assertStatus(200);
        $response->assertSee('idempotency_key', false);
    }
}
