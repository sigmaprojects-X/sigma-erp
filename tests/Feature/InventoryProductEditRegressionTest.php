<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryProductEditRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->owner = User::where('role', 'owner')->firstOrFail();
    }

    public function test_opening_stock_zero_update_returns_success_with_base_unit_code(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->owner->store->company_id,
            'store_id' => $this->owner->store_id,
            'sku' => 'OPEN-ZERO-001',
            'name' => 'Produk Saldo Awal Nol',
            'type' => Product::TYPE_BARANG,
            'cost_price' => 12000,
            'total_quantity' => 0,
        ]);

        ProductUnit::factory()->create([
            'company_id' => $product->company_id,
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Pcs',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        $this->actingAs($this->owner)
            ->post(route('warehouse.opening-stock.store'), [
                'store_id' => $this->owner->store_id,
                'product_id' => $product->id,
                'quantity' => 0,
                'cost_price' => 12000,
                'notes' => 'Test koreksi saldo awal nol',
            ])
            ->assertRedirect(route('warehouse.opening-stock.index', ['store_id' => $this->owner->store_id]))
            ->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'PCS'))
            ->assertSessionHasNoErrors();
    }

    public function test_status_only_update_bypasses_duplicate_legacy_unit_payload_without_rewriting_units(): void
    {
        $product = Product::factory()->create([
            'company_id' => $this->owner->store->company_id,
            'store_id' => $this->owner->store_id,
            'sku' => 'STATUS-ONLY-001',
            'name' => 'Produk Status Saja',
            'type' => Product::TYPE_BARANG,
            'cost_price' => 12000,
            'total_quantity' => 0,
            'sale_status' => 'ACTIVE',
            'is_point_of_sale' => true,
        ]);

        $baseUnit = ProductUnit::factory()->create([
            'company_id' => $product->company_id,
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Pcs',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        $this->actingAs($this->owner)
            ->put(route('master.products.update', $product->id), [
                'status_only' => 1,
                'sale_status' => 'DISCONTINUED',
                'units' => [
                    ['id' => $baseUnit->id, 'unit_code' => 'BOX'],
                    ['id' => $baseUnit->id, 'unit_code' => 'BOX'],
                ],
            ])
            ->assertRedirect(route('master.products'))
            ->assertSessionHas('success');

        $product->refresh();
        $baseUnit->refresh();

        $this->assertSame('DISCONTINUED', $product->sale_status);
        $this->assertFalse($product->is_point_of_sale);
        $this->assertSame('PCS', $baseUnit->unit_code);
    }
}
