<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\StockMutation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProductTypeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected CustomerTier $tier;

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
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->shift = CashierShift::where('cashier_id', $this->cashier->id)->firstOrFail();
    }

    public function test_jasa_product_can_be_checked_out_with_zero_stock_without_stock_mutation(): void
    {
        $jasaProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'JASA-SRV-01',
            'name' => 'Jasa Pasang Instalasi',
            'type' => Product::TYPE_JASA,
            'cost_price' => 0,
            'total_quantity' => 0,
            'total_stock_value' => 0,
        ]);

        $jasaUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $jasaProduct->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Jasa',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $jasaProduct->id,
            'product_unit_id' => $jasaUnit->id,
            'customer_tier_id' => $this->tier->id,
            'min_quantity' => 1,
            'price' => 50000,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $jasaProduct->id,
            'physical_quantity' => 0,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ]);

        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->tier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 100000,
            'items' => [
                [
                    'product_id' => $jasaProduct->id,
                    'product_unit_id' => $jasaUnit->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('grand_total', 100000);

        $this->assertSame(0, StockMutation::where('product_id', $jasaProduct->id)->count());
        $this->assertEquals(0.0, (float) ProductStock::where('product_id', $jasaProduct->id)->value('physical_quantity'));
    }

    public function test_paket_bundling_rakitan_non_proses_deducts_component_stocks_on_checkout(): void
    {
        $componentProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $componentUnit = ProductUnit::where('product_id', $componentProduct->id)->firstOrFail();

        $bundleProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'PKT-BND-01',
            'name' => 'Paket Hemat Isi 2',
            'type' => Product::TYPE_RAKITAN_NON_PROSES,
            'cost_price' => 20000,
            'total_quantity' => 0,
            'total_stock_value' => 0,
        ]);

        $bundleUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $bundleProduct->id,
            'unit_code' => 'PKT',
            'unit_name' => 'Paket',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $bundleProduct->id,
            'product_unit_id' => $bundleUnit->id,
            'customer_tier_id' => $this->tier->id,
            'min_quantity' => 1,
            'price' => 28000,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $bundleProduct->id,
            'physical_quantity' => 0,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ]);

        ProductBom::create([
            'company_id' => $this->company->id,
            'parent_product_id' => $bundleProduct->id,
            'component_product_id' => $componentProduct->id,
            'component_product_unit_id' => $componentUnit->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->tier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 100000,
            'items' => [
                [
                    'product_id' => $bundleProduct->id,
                    'product_unit_id' => $bundleUnit->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('grand_total', 84000);

        $this->assertEquals(94.0, (float) ProductStock::where('product_id', $componentProduct->id)->value('physical_quantity'));
        $this->assertSame(1, StockMutation::where('product_id', $componentProduct->id)->where('mutation_type', 'SALE')->count());
        $this->assertSame(0, StockMutation::where('product_id', $bundleProduct->id)->count());
    }

    public function test_biaya_product_is_rejected_at_pos_checkout(): void
    {
        $biayaProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'BYA-OPR-01',
            'name' => 'Biaya Kebersihan Toko',
            'type' => Product::TYPE_BIAYA,
            'sale_status' => 'BUY_ONLY',
            'is_point_of_sale' => false,
            'cost_price' => 50000,
            'total_quantity' => 10,
            'total_stock_value' => 500000,
        ]);

        $biayaUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $biayaProduct->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Kali',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $biayaProduct->id,
            'product_unit_id' => $biayaUnit->id,
            'customer_tier_id' => $this->tier->id,
            'min_quantity' => 1,
            'price' => 50000,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $biayaProduct->id,
            'physical_quantity' => 10,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ]);

        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->tier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 50000,
            'items' => [
                [
                    'product_id' => $biayaProduct->id,
                    'product_unit_id' => $biayaUnit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
        $this->assertStringContainsString('tidak dapat dijual di Kasir POS', (string) $response->json('message'));
        $this->assertSame(0, PosTransaction::count());
    }

    public function test_sales_return_for_jasa_and_paket_bundling_handles_stock_correctly(): void
    {
        $componentProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $componentUnit = ProductUnit::where('product_id', $componentProduct->id)->firstOrFail();

        $jasaProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'JASA-RET-01',
            'name' => 'Jasa Servis Berkala',
            'type' => Product::TYPE_JASA,
            'cost_price' => 0,
        ]);
        $jasaUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $jasaProduct->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Jasa',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);
        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $jasaProduct->id,
            'product_unit_id' => $jasaUnit->id,
            'customer_tier_id' => $this->tier->id,
            'min_quantity' => 1,
            'price' => 30000,
        ]);
        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $jasaProduct->id,
            'physical_quantity' => 0,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ]);

        $bundleProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'PKT-RET-01',
            'name' => 'Paket Bundling Retur',
            'type' => Product::TYPE_RAKITAN_NON_PROSES,
            'cost_price' => 20000,
        ]);
        $bundleUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $bundleProduct->id,
            'unit_code' => 'PKT',
            'unit_name' => 'Paket',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);
        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $bundleProduct->id,
            'product_unit_id' => $bundleUnit->id,
            'customer_tier_id' => $this->tier->id,
            'min_quantity' => 1,
            'price' => 30000,
        ]);
        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $bundleProduct->id,
            'physical_quantity' => 0,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ]);
        ProductBom::create([
            'company_id' => $this->company->id,
            'parent_product_id' => $bundleProduct->id,
            'component_product_id' => $componentProduct->id,
            'component_product_unit_id' => $componentUnit->id,
            'quantity' => 2,
        ]);

        // Checkout 1 Jasa + 2 Paket Bundling (total potong stok komponen = 4)
        $checkoutRes = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->tier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 100000,
            'items' => [
                [
                    'product_id' => $jasaProduct->id,
                    'product_unit_id' => $jasaUnit->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $bundleProduct->id,
                    'product_unit_id' => $bundleUnit->id,
                    'quantity' => 2,
                ],
            ],
        ]);
        $checkoutRes->assertStatus(200);
        $this->assertEquals(96.0, (float) ProductStock::where('product_id', $componentProduct->id)->value('physical_quantity'));

        $tx = PosTransaction::with('items')->firstOrFail();
        $jasaTxItem = $tx->items->firstWhere('product_id', $jasaProduct->id);
        $bundleTxItem = $tx->items->firstWhere('product_id', $bundleProduct->id);

        // Retur 1 Jasa + 1 Paket Bundling
        $posService = app(\App\Services\PosService::class);
        $posService->processReturn(
            transaction: $tx,
            itemsToReturn: [
                [
                    'pos_transaction_item_id' => $jasaTxItem->id,
                    'quantity' => 1,
                    'condition' => 'GOOD',
                ],
                [
                    'pos_transaction_item_id' => $bundleTxItem->id,
                    'quantity' => 1,
                    'condition' => 'GOOD',
                ],
            ],
            refundMethod: 'CASH',
            reason: 'Uji retur jasa & paket bundling',
            processedBy: $this->cashier->id,
            approvedBy: $this->cashier->id,
            currentShift: $this->shift
        );

        // Jasa tidak boleh menambah stok fisik, Paket Bundling tidak boleh menambah stok parent tetapi memulihkan 2 unit komponen (96 + 2 = 98)
        $this->assertEquals(0.0, (float) ProductStock::where('product_id', $jasaProduct->id)->value('physical_quantity'));
        $this->assertEquals(0.0, (float) ProductStock::where('product_id', $bundleProduct->id)->value('physical_quantity'));
        $this->assertEquals(98.0, (float) ProductStock::where('product_id', $componentProduct->id)->value('physical_quantity'));
    }

    public function test_sales_order_completion_handles_jasa_and_paket_bundling_stocks_correctly(): void
    {
        $componentProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $componentUnit = ProductUnit::where('product_id', $componentProduct->id)->firstOrFail();

        $jasaProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'JASA-SO-01',
            'name' => 'Jasa Pengiriman Ekspres',
            'type' => Product::TYPE_JASA,
            'cost_price' => 0,
        ]);
        $jasaUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $jasaProduct->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Kali',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        $bundleProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'PKT-SO-01',
            'name' => 'Paket Bundling SO',
            'type' => Product::TYPE_RAKITAN_NON_PROSES,
            'cost_price' => 20000,
        ]);
        $bundleUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $bundleProduct->id,
            'unit_code' => 'PKT',
            'unit_name' => 'Paket',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        ProductBom::create([
            'company_id' => $this->company->id,
            'parent_product_id' => $bundleProduct->id,
            'component_product_id' => $componentProduct->id,
            'component_product_unit_id' => $componentUnit->id,
            'quantity' => 2,
        ]);

        $owner = User::where('role', 'owner')->firstOrFail();

        $order = \App\Models\SalesOrder::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'order_number' => 'SO-TEST-001',
            'order_date' => now()->toDateString(),
            'status' => 'PENDING',
            'subtotal' => 75000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 75000,
            'created_by' => $owner->id,
        ]);

        $order->items()->create([
            'product_id' => $jasaProduct->id,
            'product_unit_id' => $jasaUnit->id,
            'unit_code' => 'PCS',
            'conversion_factor' => 1,
            'quantity' => 1,
            'unit_price' => 15000,
            'discount_amount' => 0,
            'subtotal' => 15000,
        ]);

        $order->items()->create([
            'product_id' => $bundleProduct->id,
            'product_unit_id' => $bundleUnit->id,
            'unit_code' => 'PKT',
            'conversion_factor' => 1,
            'quantity' => 2,
            'unit_price' => 30000,
            'discount_amount' => 0,
            'subtotal' => 60000,
        ]);

        $res = $this->actingAs($owner)->postJson(route('sales.orders.complete', $order->id));
        $res->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(96.0, (float) ProductStock::where('product_id', $componentProduct->id)->value('physical_quantity'));
    }
}
