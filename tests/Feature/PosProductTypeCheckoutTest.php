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
}
