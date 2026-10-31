<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Models\SalesOrder;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaStorefrontSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected CustomerTier $tier;

    protected Product $product;

    protected ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();

        $this->company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $this->store = Store::where('code', 'STR-001')->firstOrFail();
        $this->tier = CustomerTier::where('code', 'RETAIL')->firstOrFail();
        $this->product = Product::where('sku', 'PRD-001')->firstOrFail();
        $this->unit = ProductUnit::where('product_id', $this->product->id)->firstOrFail();
    }

    public function test_checkout_ignores_client_supplied_discount_and_tebus_murah_and_calculates_on_server(): void
    {
        // Harga resmi PRD-001 adalah 15000. Pengunjung anonim mengirim discount_amount=15000 dan is_tebus_murah=true
        $response = $this->postJson(route('pwa.checkout'), [
            'store_id' => $this->store->id,
            'customer_name' => 'Anonim Manipulator',
            'customer_phone' => '081234567899',
            'delivery_type' => 'PICKUP',
            'payment_method' => 'TRANSFER',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 2,
                    'discount_amount' => 30000,
                    'is_tebus_murah' => true,
                    'promotion_id' => '00000000-0000-0000-0000-000000000000',
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $order = SalesOrder::with('items')->latest()->firstOrFail();
        // 2 x 15000 = 30000 (tidak boleh 0!)
        $this->assertEquals(30000.0, (float) $order->grand_total);
        $this->assertEquals(0.0, (float) $order->items->first()->discount_amount);
        $this->assertEquals(30000.0, (float) $order->items->first()->subtotal);
    }

    public function test_checkout_applies_valid_active_tebus_murah_promotion_from_server(): void
    {
        $rewardProduct = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'PRD-RWD-01',
            'name' => 'Minyak Tebus Murah',
            'cost_price' => 8000,
            'total_quantity' => 50,
            'total_stock_value' => 400000,
        ]);

        $rewardUnit = ProductUnit::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $rewardProduct->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Pcs',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'product_id' => $rewardProduct->id,
            'product_unit_id' => $rewardUnit->id,
            'customer_tier_id' => $this->tier->id,
            'min_quantity' => 1,
            'price' => 20000,
        ]);

        ProductStock::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $rewardProduct->id,
            'physical_quantity' => 50,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ]);

        Promotion::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'code' => 'PRM-TM-10K',
            'name' => 'Tebus Murah Minyak 10rb',
            'type' => 'TEBUS_MURAH',
            'min_spend' => 30000,
            'reward_product_id' => $rewardProduct->id,
            'reward_quantity' => 1,
            'special_price' => 10000,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'is_active' => true,
        ]);

        // Beli 2x PRD-001 (2 x 15000 = 30000 >= min_spend 30000) + 1x PRD-RWD-01 (normal 20000, tebus murah 10000)
        $response = $this->postJson(route('pwa.checkout'), [
            'store_id' => $this->store->id,
            'customer_name' => 'Pelanggan Jujur',
            'customer_phone' => '081234567800',
            'delivery_type' => 'PICKUP',
            'payment_method' => 'TRANSFER',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $rewardProduct->id,
                    'product_unit_id' => $rewardUnit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $order = SalesOrder::with('items')->latest()->firstOrFail();
        // Total harus 30000 + 10000 = 40000
        $this->assertEquals(40000.0, (float) $order->grand_total);
    }

    public function test_product_detail_hides_cost_price_and_blocks_inactive_or_discontinued_products(): void
    {
        $response = $this->getJson(route('pwa.product', $this->product->id));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('product.id', $this->product->id)
            ->assertJsonPath('product.sku', 'PRD-001')
            ->assertJsonPath('product.name', 'Produk Uji Standar')
            ->assertJsonPath('available_stock', 100);

        $productJson = $response->json('product');
        $this->assertArrayNotHasKey('cost_price', $productJson);
        $this->assertArrayNotHasKey('last_purchase_price', $productJson);
        $this->assertArrayNotHasKey('total_stock_value', $productJson);
        $this->assertArrayHasKey('units', $productJson);
        $this->assertArrayHasKey('selling_price', $productJson);
        $this->assertArrayHasKey('available_stock', $productJson);

        // Produk Discontinue atau Nonaktif harus 404
        $discontinued = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'PRD-DISC-99',
            'name' => 'Produk Discontinue',
            'sale_status' => 'DISCONTINUED',
            'is_active' => true,
        ]);

        $this->getJson(route('pwa.product', $discontinued->id))
            ->assertStatus(404);

        $inactive = Product::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'sku' => 'PRD-INACT-99',
            'name' => 'Produk Nonaktif',
            'sale_status' => 'ACTIVE',
            'is_active' => false,
        ]);

        $this->getJson(route('pwa.product', $inactive->id))
            ->assertStatus(404);
    }

    public function test_checkout_route_has_rate_limiting_throttle(): void
    {
        $route = app('router')->getRoutes()->getByName('pwa.checkout');
        $this->assertNotNull($route);
        $middlewares = $route->gatherMiddleware();

        $hasThrottle = collect($middlewares)->contains(fn ($m) => str_starts_with((string) $m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Route pwa.checkout harus memiliki middleware throttle');
    }
}
