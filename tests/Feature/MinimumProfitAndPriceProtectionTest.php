<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PricingEngineService;
use Database\Seeders\MinimalTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinimumProfitAndPriceProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $owner;
    protected CustomerTier $tierRetail;
    protected CustomerTier $tierGrosir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MinimalTestSeeder::class);

        $this->company = Company::firstOrFail();
        $this->company->update(['min_profit_percentage' => 5.00]); // 5% minimum profit

        $this->owner = User::where('role', 'owner')->firstOrFail();

        $this->tierRetail = CustomerTier::where('company_id', $this->company->id)
            ->where('code', 'RETAIL')
            ->firstOrFail();

        $this->tierGrosir = CustomerTier::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'GROSIR'],
            ['name' => 'Grosir / Toko', 'is_default' => false, 'is_active' => true]
        );
    }

    public function test_supplier_umum_is_automatically_assigned_when_supplier_is_empty(): void
    {
        $this->actingAs($this->owner);

        $response = $this->post(route('master.products.store'), [
            'name' => 'Item Tanpa Supplier Test',
            'sku' => 'SKU-TEST-NOSUPP-01',
            'cost_price' => 10000,
            'unit_code' => 'PCS',
            'unit_name' => 'Pieces',
            'price_retail' => 12000,
            'supplier_id' => null, // sengaja dikosongkan
        ]);

        $response->assertSessionHasNoErrors();

        $product = Product::where('sku', 'SKU-TEST-NOSUPP-01')->firstOrFail();
        $generalSupplier = Supplier::generalFor($this->company->id);

        $this->assertNotNull($product->supplier_id);
        $this->assertEquals($generalSupplier->id, $product->supplier_id);
        $this->assertEquals(Supplier::GENERAL_CODE, $product->supplier->code);
    }

    public function test_inputting_price_below_minimum_profit_is_rejected_without_leaking_cost_price(): void
    {
        $this->actingAs($this->owner);

        // HPP 10.000, Min Profit 5% -> Batas bawah Rp 10.500
        // Input harga retail Rp 10.200 (di bawah batas 10.500)
        $response = $this->post(route('master.products.store'), [
            'name' => 'Item Rugi Test',
            'sku' => 'SKU-TEST-RUGI-01',
            'cost_price' => 10000,
            'unit_code' => 'PCS',
            'unit_name' => 'Pieces',
            'price_retail' => 10200,
        ]);

        $response->assertSessionHas('error');
        $errorMessage = session('error');

        // Pastikan ditolak karena batas minimum keuntungan
        $this->assertStringContainsString('batas minimum keuntungan', $errorMessage);

        // Pastikan angka modal / HPP 10.000 TIDAK bocor dalam pesan error
        $this->assertStringNotContainsString('10.000', $errorMessage);
        $this->assertStringNotContainsString('HPP', $errorMessage);
        $this->assertStringNotContainsString('modal', $errorMessage);
    }

    public function test_inactive_or_empty_tier_price_falls_back_to_retail_price(): void
    {
        $product = Product::create([
            'company_id' => $this->company->id,
            'sku' => 'SKU-FALLBACK-01',
            'name' => 'Item Fallback Test',
            'cost_price' => 5000,
            'is_point_of_sale' => true,
            'is_active' => true,
        ]);

        $unit = ProductUnit::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Pieces',
            'conversion_factor' => 1.0,
            'is_base_unit' => true,
            'is_active' => true,
        ]);

        // Retail price = 7000
        ProductPrice::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'customer_tier_id' => $this->tierRetail->id,
            'min_quantity' => 1.0,
            'price' => 7000,
            'is_active' => true,
        ]);

        // Grosir price = 2 (warisan salah input yang dinonaktifkan)
        ProductPrice::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'customer_tier_id' => $this->tierGrosir->id,
            'min_quantity' => 1.0,
            'price' => 2,
            'is_active' => false, // dinonaktifkan oleh migration cleanup
        ]);

        $engine = app(PricingEngineService::class);

        // Saat pelanggan Grosir belanja, karena harga Grosir aktif tidak ada, harus fallback ke Retail 7000
        $finalPrice = $engine->calculatePrice($product, $unit, $this->tierGrosir, 1.0);

        $this->assertEquals(7000.0, $finalPrice);
    }

    public function test_disabling_grosir_tier_keeps_retail_price_active(): void
    {
        $product = Product::where('sku', 'PRD-001')->firstOrFail();
        $unit = ProductUnit::where('product_id', $product->id)->where('unit_code', 'PCS')->firstOrFail();

        ProductPrice::create([
            'company_id' => $this->company->id,
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'customer_tier_id' => $this->tierGrosir->id,
            'min_quantity' => 1.00,
            'price' => 12000,
            'margin_percentage' => 20.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->get(route('master.products.edit', $product->id))
            ->assertOk()
            ->assertSee('Aktif');

        $response = $this->actingAs($this->owner)->put(route('master.products.update', $product->id), [
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => Product::TYPE_BARANG,
            'sale_status' => 'ACTIVE',
            'cost_price' => 10000,
            'is_active' => 1,
            'is_point_of_sale' => 1,
            'units' => [[
                'id' => $unit->id,
                'unit_code' => 'PCS',
                'unit_name' => 'Pieces',
                'conversion_factor' => 1,
                'is_base_unit' => 1,
                'is_active' => 1,
                'tier_prices' => [
                    $this->tierRetail->id => ['price' => 15000, 'margin' => 50],
                    $this->tierGrosir->id => ['is_active' => 0],
                ],
            ]],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'customer_tier_id' => $this->tierGrosir->id,
            'min_quantity' => 1.00,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'customer_tier_id' => $this->tierRetail->id,
            'min_quantity' => 1.00,
            'price' => 15000,
            'is_active' => true,
        ]);
    }
}
