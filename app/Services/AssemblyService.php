<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductAssembly;
use App\Models\ProductAssemblyItem;
use App\Models\ProductBom;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AssemblyService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Simpan / Perbarui Template Resep BOM (Bill of Materials) untuk Produk Paket.
     */
    public function saveBom(Product $parent, array $components): void
    {
        DB::transaction(function () use ($parent, $components) {
            ProductBom::where('parent_product_id', $parent->id)->delete();

            foreach ($components as $c) {
                $qty = (float) ($c['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                ProductBom::create([
                    'company_id' => $parent->company_id,
                    'parent_product_id' => $parent->id,
                    'component_product_id' => $c['component_product_id'],
                    'component_product_unit_id' => $c['component_product_unit_id'],
                    'quantity' => $qty,
                ]);
            }
        });
    }

    /**
     * Ambil Template Resep BOM Produk Paket.
     */
    public function getBom(Product $parent): Collection
    {
        return ProductBom::where('parent_product_id', $parent->id)
            ->with(['componentProduct', 'componentUnit'])
            ->get();
    }

    /**
     * Eksekusi Perakitan Barang Jadi / Paket Bundling (Assembly).
     *
     * 1. Pengurangan stok bahan baku / komponen (Zero Negative Inventory check).
     * 2. Kalkulasi HPP gabungan seluruh komponen.
     * 3. Penambahan stok barang jadi / paket di toko terkait.
     * 4. Pembuatan Jurnal Finansial Seimbang Otomatis.
     */
    public function assemble(
        Store $store,
        Product $parent,
        ProductUnit $parentUnit,
        float $quantity,
        array $components,
        User $user,
        ?string $notes = null
    ): ProductAssembly {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Jumlah paket yang dirakit harus lebih besar dari 0.');
        }

        if (empty($components)) {
            throw new \InvalidArgumentException('Daftar komponen bahan baku perakitan tidak boleh kosong.');
        }

        return DB::transaction(function () use ($store, $parent, $parentUnit, $quantity, $components, $user, $notes) {
            $company = $parent->company ?? Company::first();

            $dateStr = date('Ymd');
            $count = ProductAssembly::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $assemblyNumber = 'ASM-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $totalAssemblyCost = 0.0;
            $preparedItems = [];

            // 1. Validasi & Kurangi Stok Seluruh Komponen Bahan Baku
            foreach ($components as $compData) {
                /** @var Product $componentProduct */
                $componentProduct = Product::findOrFail($compData['component_product_id']);

                /** @var ProductUnit $compUnit */
                $compUnit = ProductUnit::where('id', $compData['component_product_unit_id'])
                    ->where('product_id', $componentProduct->id)
                    ->firstOrFail();

                // Kuantitas komponen yang dibutuhkan untuk seluruh paket
                $qtyPerUnit = (float) $compData['quantity'];
                $totalCompQty = round($qtyPerUnit * $quantity, 4);
                $conversion = (float) $compUnit->conversion_factor > 0 ? (float) $compUnit->conversion_factor : 1.0;
                $baseCompQty = round($totalCompQty * $conversion, 4);

                $unitCost = (float) $componentProduct->cost_price;
                $itemTotalCost = round($baseCompQty * $unitCost, 2);
                $totalAssemblyCost += $itemTotalCost;

                // Potong stok komponen dari toko (Zero Negative Check)
                $this->inventoryService->deductStock(
                    product: $componentProduct,
                    store: $store,
                    quantity: $baseCompQty,
                    mutationType: 'ASSEMBLY_OUT',
                    referenceNumber: $assemblyNumber,
                    notes: "Bahan Baku Perakitan {$parent->name} ({$assemblyNumber})",
                    userId: $user->id
                );

                $preparedItems[] = [
                    'component_product_id' => $componentProduct->id,
                    'component_product_unit_id' => $compUnit->id,
                    'quantity' => $totalCompQty,
                    'base_quantity' => $baseCompQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $itemTotalCost,
                ];
            }

            // 2. Tambah Stok Barang Jadi / Paket di Toko Terkait
            $parentConversion = (float) $parentUnit->conversion_factor > 0 ? (float) $parentUnit->conversion_factor : 1.0;
            $parentBaseQty = round($quantity * $parentConversion, 4);
            $parentUnitCost = $parentBaseQty > 0 ? round($totalAssemblyCost / $parentBaseQty, 4) : 0.0;

            $this->inventoryService->recordAssemblyStock(
                product: $parent,
                store: $store,
                quantity: $parentBaseQty,
                unitCost: $parentUnitCost,
                referenceNumber: $assemblyNumber,
                mutationType: 'ASSEMBLY_IN',
                notes: "Barang Jadi Hasil Perakitan ({$assemblyNumber})",
                userId: $user->id
            );

            // 3. Simpan Header Perakitan
            $assembly = ProductAssembly::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'assembly_number' => $assemblyNumber,
                'assembly_date' => now()->toDateString(),
                'type' => 'ASSEMBLY',
                'parent_product_id' => $parent->id,
                'parent_product_unit_id' => $parentUnit->id,
                'quantity' => $quantity,
                'unit_cost' => $parentUnitCost,
                'total_cost' => $totalAssemblyCost,
                'notes' => $notes,
                'assembled_by' => $user->id,
            ]);

            // 4. Simpan Rincian Komponen Perakitan
            foreach ($preparedItems as $item) {
                $item['product_assembly_id'] = $assembly->id;
                ProductAssemblyItem::create($item);
            }

            // 5. Jurnal Finansial Double-Entry Otomatis
            $this->accountingService->recordAssemblyJournal($assembly);

            return $assembly->fresh(['items.componentProduct', 'items.componentUnit', 'parentProduct', 'parentUnit', 'store', 'assembler']);
        });
    }

    /**
     * Pembongkaran Barang Jadi Kembali ke Komponen Bahan Baku (Disassembly).
     *
     * 1. Pengurangan stok barang jadi.
     * 2. Pengembalian stok masing-masing komponen pembentuk.
     * 3. Jurnal finansial pembalikan persediaan.
     */
    public function disassemble(
        ProductAssembly $originalAssembly,
        User $user,
        ?string $notes = null
    ): ProductAssembly {
        if ($originalAssembly->type !== 'ASSEMBLY') {
            throw new \DomainException('Hanya transaksi tipe ASSEMBLY yang dapat dibongkar kembali.');
        }

        return DB::transaction(function () use ($originalAssembly, $user, $notes) {
            $company = $originalAssembly->company;
            $store = $originalAssembly->store;
            $parent = $originalAssembly->parentProduct;
            $parentUnit = $originalAssembly->parentUnit;
            $quantity = (float) $originalAssembly->quantity;

            $dateStr = date('Ymd');
            $count = ProductAssembly::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $disassemblyNumber = 'DIS-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            // 1. Potong Stok Barang Jadi yang Dibongkar (Zero Negative Check)
            $parentConversion = (float) $parentUnit->conversion_factor > 0 ? (float) $parentUnit->conversion_factor : 1.0;
            $parentBaseQty = round($quantity * $parentConversion, 4);

            $this->inventoryService->deductStock(
                product: $parent,
                store: $store,
                quantity: $parentBaseQty,
                mutationType: 'DISASSEMBLY_OUT',
                referenceNumber: $disassemblyNumber,
                notes: "Pembongkaran Paket Jadi ({$disassemblyNumber})",
                userId: $user->id
            );

            // 2. Tambah Kembali Stok Masing-Masing Komponen
            $preparedItems = [];
            foreach ($originalAssembly->items as $origItem) {
                $compProduct = $origItem->componentProduct;
                $baseQty = (float) $origItem->base_quantity;
                $unitCost = (float) $origItem->unit_cost;

                $this->inventoryService->recordAssemblyStock(
                    product: $compProduct,
                    store: $store,
                    quantity: $baseQty,
                    unitCost: $unitCost,
                    referenceNumber: $disassemblyNumber,
                    mutationType: 'DISASSEMBLY_IN',
                    notes: "Pengembalian Komponen Hasil Pembongkaran ({$disassemblyNumber})",
                    userId: $user->id
                );

                $preparedItems[] = [
                    'component_product_id' => $compProduct->id,
                    'component_product_unit_id' => $origItem->component_product_unit_id,
                    'quantity' => $origItem->quantity,
                    'base_quantity' => $baseQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $origItem->total_cost,
                ];
            }

            // 3. Simpan Header Pembongkaran
            $disassembly = ProductAssembly::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'assembly_number' => $disassemblyNumber,
                'assembly_date' => now()->toDateString(),
                'type' => 'DISASSEMBLY',
                'parent_product_id' => $parent->id,
                'parent_product_unit_id' => $parentUnit->id,
                'quantity' => $quantity,
                'unit_cost' => (float) $originalAssembly->unit_cost,
                'total_cost' => (float) $originalAssembly->total_cost,
                'notes' => $notes ?: "Pembongkaran dari {$originalAssembly->assembly_number}",
                'assembled_by' => $user->id,
            ]);

            // 4. Simpan Rincian Komponen
            foreach ($preparedItems as $item) {
                $item['product_assembly_id'] = $disassembly->id;
                ProductAssemblyItem::create($item);
            }

            // 5. Jurnal Finansial
            $this->accountingService->recordAssemblyJournal($disassembly);

            return $disassembly->fresh(['items.componentProduct', 'items.componentUnit', 'parentProduct', 'parentUnit', 'store', 'assembler']);
        });
    }
}
