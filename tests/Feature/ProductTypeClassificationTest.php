<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductUnit;
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

    public function test_creating_rakitan_product_saves_bom_components_from_form(): void
    {
        $componentProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $componentUnit = ProductUnit::where('product_id', $componentProduct->id)->firstOrFail();

        $response = $this->actingAs($this->owner)->post(route('master.products.store'), [
            'sku' => 'PKT-HMP-001',
            'name' => 'Paket Hampers Lebaran',
            'type' => Product::TYPE_RAKITAN_NON_PROSES,
            'cost_price' => 30000,
            'sale_status' => 'ACTIVE',
            'units' => [
                [
                    'unit_code' => 'PKT',
                    'unit_name' => 'Paket',
                    'conversion_factor' => 1,
                    'is_base_unit' => 1,
                    'price_retail' => 45000,
                ],
            ],
            'bom_components' => [
                [
                    'component_product_id' => $componentProduct->id,
                    'component_product_unit_id' => $componentUnit->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response->assertRedirect(route('master.products'));

        $bundle = Product::where('sku', 'PKT-HMP-001')->firstOrFail();
        $this->assertSame(Product::TYPE_RAKITAN_NON_PROSES, $bundle->type);
        $this->assertSame(1, ProductBom::where('parent_product_id', $bundle->id)->count());

        $bom = ProductBom::where('parent_product_id', $bundle->id)->firstOrFail();
        $this->assertSame($componentProduct->id, $bom->component_product_id);
        $this->assertSame($componentUnit->id, $bom->component_product_unit_id);
        $this->assertEquals(3.0, (float) $bom->quantity);
    }

    public function test_updating_rakitan_product_syncs_and_clears_bom_when_type_changed_to_barang(): void
    {
        $componentProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $componentUnit = ProductUnit::where('product_id', $componentProduct->id)->firstOrFail();

        $rakitan = Product::factory()->create([
            'company_id' => $this->owner->store->company_id,
            'store_id' => $this->owner->store_id,
            'sku' => 'RKT-001',
            'name' => 'Parcel Buah Rakitan',
            'type' => Product::TYPE_RAKITAN_PROSES,
            'cost_price' => 20000,
        ]);

        $rakitanUnit = ProductUnit::factory()->create([
            'company_id' => $this->owner->store->company_id,
            'store_id' => $this->owner->store_id,
            'product_id' => $rakitan->id,
            'unit_code' => 'PCS',
            'unit_name' => 'Parcel',
            'conversion_factor' => 1,
            'is_base_unit' => true,
        ]);

        // 1. Update dengan komponen BOM
        $this->actingAs($this->owner)->put(route('master.products.update', $rakitan->id), [
            'sku' => 'RKT-001',
            'name' => 'Parcel Buah Rakitan',
            'type' => Product::TYPE_RAKITAN_PROSES,
            'cost_price' => 20000,
            'sale_status' => 'ACTIVE',
            'units' => [
                [
                    'id' => $rakitanUnit->id,
                    'unit_code' => 'PCS',
                    'unit_name' => 'Parcel',
                    'conversion_factor' => 1,
                    'is_base_unit' => 1,
                ],
            ],
            'bom_components' => [
                [
                    'component_product_id' => $componentProduct->id,
                    'component_product_unit_id' => $componentUnit->id,
                    'quantity' => 2,
                ],
            ],
        ])->assertRedirect(route('master.products'));

        $this->assertSame(1, ProductBom::where('parent_product_id', $rakitan->id)->count());

        // 2. Ubah tipe kembali menjadi BARANG fisik biasa -> BOM harus dibersihkan
        $this->actingAs($this->owner)->put(route('master.products.update', $rakitan->id), [
            'sku' => 'RKT-001',
            'name' => 'Parcel Buah Rakitan',
            'type' => Product::TYPE_BARANG,
            'cost_price' => 20000,
            'sale_status' => 'ACTIVE',
            'units' => [
                [
                    'id' => $rakitanUnit->id,
                    'unit_code' => 'PCS',
                    'unit_name' => 'Parcel',
                    'conversion_factor' => 1,
                    'is_base_unit' => 1,
                ],
            ],
        ])->assertRedirect(route('master.products'));

        $this->assertSame(0, ProductBom::where('parent_product_id', $rakitan->id)->count());
    }

    public function test_non_inventory_types_are_excluded_from_stock_opname_restock_and_inventory_valuation(): void
    {
        $company = \App\Models\Company::where('code', 'SIGMA-HO')->firstOrFail();
        $store = \App\Models\Store::where('code', 'STR-001')->firstOrFail();

        // Buat item JASA, RAKITAN_NON_PROSES, NON_INVENTORY, dan BIAYA
        foreach ([
            Product::TYPE_JASA => 'JSA-OPN-01',
            Product::TYPE_RAKITAN_NON_PROSES => 'PKT-OPN-01',
            Product::TYPE_NON_INVENTORY => 'NIN-OPN-01',
            Product::TYPE_BIAYA => 'BYA-OPN-01',
        ] as $type => $sku) {
            $p = Product::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'sku' => $sku,
                'name' => "Item {$type}",
                'type' => $type,
                'cost_price' => 10000,
                'min_stock_alert' => 0,
            ]);
            ProductUnit::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $p->id,
                'unit_code' => 'PCS',
                'unit_name' => 'Pcs',
                'conversion_factor' => 1,
                'is_base_unit' => true,
            ]);
            \App\Models\ProductStock::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $p->id,
                'physical_quantity' => 0,
                'reserved_quantity' => 0,
                'quarantined_quantity' => 0,
            ]);
        }

        // Set PRD-001 (BARANG fisik) menjadi stok 2 dengan min_stock_alert 5 agar muncul di rekomendasi restock
        $physicalProduct = Product::where('sku', 'PRD-001')->firstOrFail();
        $physicalProduct->update(['min_stock_alert' => 5]);
        \App\Models\ProductStock::where('product_id', $physicalProduct->id)->update(['physical_quantity' => 2]);

        // 1. Start Stock Opname -> Hanya boleh memuat PRD-001 (1 item fisik), bukan JASA/BUNDLING/NON_INVENTORY/BIAYA
        $warehouseService = app(\App\Services\WarehouseService::class);
        $opname = $warehouseService->startOpname(
            company: $company,
            store: $store,
            opnameDate: now()->toDateString(),
            notes: 'Uji opname hanya barang fisik',
            userId: $this->owner->id
        );
        $this->assertSame(1, $opname->items()->count());
        $this->assertSame($physicalProduct->id, $opname->items->first()->product_id);

        // 2. Rekomendasi Restock -> Hanya boleh memuat PRD-001
        $purchasingService = app(\App\Services\PurchasingService::class);
        $recommendations = $purchasingService->getRestockRecommendations($store);
        $this->assertSame(1, $recommendations->count());
        $this->assertSame('PRD-001', $recommendations->first()['sku']);

        // 3. Laporan Nilai Persediaan (Valuation) -> Hanya boleh memuat PRD-001
        $invReportService = app(\App\Services\InventoryReportService::class);
        $valuation = $invReportService->getStockValuationReport($company, $store);
        $this->assertSame(1, $valuation['total_sku_count']);

        // 4. Dashboard Monitoring Gudang (/warehouse/inventory) -> Tidak boleh menampilkan JASA-OPN-01
        $invPage = $this->actingAs($this->owner)->get(route('warehouse.inventory.index'));
        $invPage->assertStatus(200);
        $invPage->assertSee('PRD-001');
        $invPage->assertDontSee('JSA-OPN-01');
        $invPage->assertDontSee('BYA-OPN-01');
    }
}
