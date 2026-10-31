<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTypeClassificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->owner = User::where('role', 'owner')->firstOrFail();
    }

    public function test_creating_jasa_product_disables_inventory_tracking_attributes(): void
    {
        $response = $this->actingAs($this->owner)->post(route('master.products.store'), [
            'sku' => 'JASA-001',
            'name' => 'Jasa Servis Berkala',
            'type' => 'JASA',
            'cost_price' => 0,
            'min_stock_alert' => 15,
            'max_stock' => 100,
            'has_serial_number' => 1,
            'has_batch_expiry' => 1,
            'sale_status' => 'ACTIVE',
            'units' => [
                [
                    'unit_code' => 'PCS',
                    'unit_name' => 'Jasa',
                    'conversion_factor' => 1,
                    'is_base_unit' => 1,
                    'price_retail' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect(route('master.products'));

        $product = Product::where('sku', 'JASA-001')->firstOrFail();
        $this->assertSame('JASA', $product->type);
        $this->assertFalse($product->tracksInventory());
        $this->assertEquals(0.0, (float) $product->min_stock_alert);
        $this->assertNull($product->max_stock);
        $this->assertFalse($product->has_serial_number);
        $this->assertFalse($product->has_batch_expiry);
        $this->assertTrue($product->is_point_of_sale);
    }

    public function test_creating_biaya_product_forces_buy_only_and_not_point_of_sale(): void
    {
        $response = $this->actingAs($this->owner)->post(route('master.products.store'), [
            'sku' => 'BIAYA-001',
            'name' => 'Biaya Listrik Bulanan',
            'type' => 'BIAYA',
            'cost_price' => 500000,
            'sale_status' => 'ACTIVE',
            'units' => [
                [
                    'unit_code' => 'PCS',
                    'unit_name' => 'Bulan',
                    'conversion_factor' => 1,
                    'is_base_unit' => 1,
                    'price_retail' => 500000,
                ],
            ],
        ]);

        $response->assertRedirect(route('master.products'));

        $product = Product::where('sku', 'BIAYA-001')->firstOrFail();
        $this->assertSame('BIAYA', $product->type);
        $this->assertFalse($product->tracksInventory());
        $this->assertSame('BUY_ONLY', $product->sale_status);
        $this->assertFalse($product->is_point_of_sale);
    }

    public function test_unsupported_product_type_is_rejected_by_validation(): void
    {
        $response = $this->actingAs($this->owner)->post(route('master.products.store'), [
            'sku' => 'VAR-001',
            'name' => 'Produk Varian Tidak Valid',
            'type' => 'VARIAN',
            'cost_price' => 10000,
            'units' => [
                [
                    'unit_code' => 'PCS',
                    'unit_name' => 'Pieces',
                    'conversion_factor' => 1,
                    'is_base_unit' => 1,
                    'price_retail' => 15000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('type');
        $this->assertDatabaseMissing('products', ['sku' => 'VAR-001']);
    }

    public function test_products_index_can_filter_by_type(): void
    {
        Product::factory()->create([
            'company_id' => $this->owner->store->company_id,
            'store_id' => $this->owner->store_id,
            'sku' => 'JASA-002',
            'name' => 'Jasa Pengiriman Toko',
            'type' => 'JASA',
        ]);

        $response = $this->actingAs($this->owner)->get(route('master.products', ['type' => 'JASA']));

        $response->assertStatus(200);
        $response->assertSee('Jasa Pengiriman Toko');
        $response->assertDontSee('Produk Uji Standar');
    }
}
