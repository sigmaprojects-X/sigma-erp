<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\NegativeInventoryException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMutation;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Hitung stok yang tersedia untuk dijual (Available Stock).
     * Rumus: physical_quantity - reserved_quantity - quarantined_quantity (AGENTS.md Section 2)
     */
    public function getAvailableStock(Product $product, Store $store): float
    {
        $stock = ProductStock::where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();

        if (! $stock) {
            return 0.0;
        }

        return (float) $stock->available_quantity;
    }

    /**
     * Penerimaan Barang / Goods Receipt dari Pemasok.
     * Mengkalkulasi ulang HPP Moving Average (Weighted Average Cost) secara otomatis (AGENTS.md Section 2).
     *
     * Rumus HPP Moving Average:
     * HPP Baru = (Total Nilai Stok Lama + Nilai Masuk) / (Total Kuantitas Lama + Kuantitas Masuk)
     */
    public function recordGoodsReceipt(
        Product $product,
        Store $store,
        float $quantity,
        float $purchaseCostPerUnit,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas penerimaan barang harus lebih besar dari 0.');
        }

        if ($purchaseCostPerUnit < 0) {
            throw new \InvalidArgumentException('Harga beli barang masuk tidak boleh negatif.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $purchaseCostPerUnit,
            $referenceNumber,
            $notes,
            $userId
        ) {
            // Lock row product untuk mencegah race condition kalkulasi Moving Average
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            $currentQty = (float) $lockedProduct->total_quantity;
            $currentStockValue = (float) $lockedProduct->total_stock_value;

            $incomingValue = $quantity * $purchaseCostPerUnit;
            $newTotalQty = $currentQty + $quantity;
            $newTotalStockValue = $currentStockValue + $incomingValue;

            // Hitung HPP Moving Average Baru
            $newCostPrice = $newTotalQty > 0
                ? round($newTotalStockValue / $newTotalQty, 4)
                : $purchaseCostPerUnit;

            // Update master produk
            $lockedProduct->update([
                'cost_price' => $newCostPrice,
                'last_purchase_price' => $purchaseCostPerUnit,
                'total_quantity' => $newTotalQty,
                'total_stock_value' => $newTotalStockValue,
            ]);

            // Lock row product_stocks cabang
            /** @var ProductStock $stock */
            $stock = ProductStock::firstOrCreate(
                [
                    'company_id' => $lockedProduct->company_id,
                    'store_id' => $store->id,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'physical_quantity' => 0.0000,
                    'reserved_quantity' => 0.0000,
                    'quarantined_quantity' => 0.0000,
                ]
            );

            $stock = ProductStock::where('id', $stock->id)->lockForUpdate()->firstOrFail();

            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = $qtyBefore + $quantity;

            $stock->update([
                'physical_quantity' => $qtyAfter,
            ]);

            // Catat Kartu Stok (Stock Ledger)
            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'GOODS_RECEIPT',
                'quantity' => $quantity,
                'unit_cost' => $newCostPrice,
                'total_cost' => $incomingValue,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: 'Penerimaan barang dari supplier / restock',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Pencatatan Saldo Awal Persediaan Item (Opening Stock).
     * Menginisialisasi stok fisik cabang & HPP Moving Average awal item.
     */
    public function recordOpeningStock(
        Product $product,
        Store $store,
        float $quantity,
        float $costPrice,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null,
        ?float $minStock = null,
        ?string $rackLocation = null
    ): StockMutation {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Kuantitas saldo awal tidak boleh negatif.');
        }

        if ($costPrice < 0) {
            throw new \InvalidArgumentException('Harga pokok (HPP) saldo awal tidak boleh negatif.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $costPrice,
            $referenceNumber,
            $notes,
            $userId,
            $minStock,
            $rackLocation
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            // Lock row product_stocks cabang
            $stock = ProductStock::firstOrCreate(
                [
                    'company_id' => $lockedProduct->company_id,
                    'store_id' => $store->id,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'physical_quantity' => 0.0000,
                    'reserved_quantity' => 0.0000,
                    'quarantined_quantity' => 0.0000,
                ]
            );

            $stock = ProductStock::where('id', $stock->id)->lockForUpdate()->firstOrFail();
            $qtyBefore = (float) $stock->physical_quantity;
            $qtyDiff = $quantity - $qtyBefore;

            // Update stok fisik cabang menjadi saldo awal yang diinput
            $stock->update([
                'physical_quantity' => $quantity,
            ]);

            // Hitung akumulasi total kuantitas dan nilai stok produk
            $allStocksQty = ProductStock::where('product_id', $lockedProduct->id)->sum('physical_quantity');
            $newTotalStockValue = $allStocksQty * $costPrice;

            $productUpdates = [
                'cost_price' => $costPrice,
                'last_purchase_price' => $costPrice,
                'total_quantity' => $allStocksQty,
                'total_stock_value' => $newTotalStockValue,
            ];

            if ($minStock !== null) {
                $productUpdates['min_stock_alert'] = $minStock;
            }
            if ($rackLocation !== null) {
                $productUpdates['rack_location'] = $rackLocation;
            }

            $lockedProduct->update($productUpdates);

            // Catat Kartu Stok (Stock Mutation)
            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'OPENING_BALANCE',
                'quantity' => $qtyDiff,
                'unit_cost' => $costPrice,
                'total_cost' => round($quantity * $costPrice, 4),
                'qty_before' => $qtyBefore,
                'qty_after' => $quantity,
                'notes' => $notes ?: 'Pencatatan Saldo Awal Persediaan Barang',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Pengurangan Stok Fisik (Penjualan POS, Transfer Keluar, dll.).
     * Menegakkan Aturan 'Zero Negative Inventory' secara mutlak di backend (AGENTS.md Section 2).
     */
    public function deductStock(
        Product $product,
        Store $store,
        float $quantity,
        string $mutationType = 'SALE',
        string $referenceNumber = '',
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas pengurangan stok harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $mutationType,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->first();

            $physicalQty = $stock ? (float) $stock->physical_quantity : 0.0;
            $reservedQty = $stock ? (float) $stock->reserved_quantity : 0.0;
            $quarantinedQty = $stock ? (float) $stock->quarantined_quantity : 0.0;
            $availableStock = max(0, $physicalQty - $reservedQty - $quarantinedQty);

            // 1. Validasi kecukupan stok tersedia
            if ($quantity > $availableStock) {
                throw new InsufficientStockException(
                    productId: $lockedProduct->id,
                    storeId: $store->id,
                    requestedQuantity: $quantity,
                    availableQuantity: $availableStock
                );
            }

            // 2. Validasi Zero Negative Inventory
            $qtyAfter = $physicalQty - $quantity;
            if ($qtyAfter < 0) {
                throw new NegativeInventoryException(
                    productId: $lockedProduct->id,
                    storeId: $store->id,
                    currentQuantity: $physicalQty,
                    attemptedDeduction: $quantity
                );
            }

            // Update stok fisik cabang
            $stock->update([
                'physical_quantity' => $qtyAfter,
            ]);

            // Update akumulasi produk global (nilai persediaan berkurang sesuai HPP berjalan saat ini)
            $currentHpp = (float) $lockedProduct->cost_price;
            $newTotalQty = max(0, (float) $lockedProduct->total_quantity - $quantity);
            $newTotalValue = max(0, $newTotalQty * $currentHpp);

            $lockedProduct->update([
                'total_quantity' => $newTotalQty,
                'total_stock_value' => $newTotalValue,
            ]);

            // Catat Kartu Stok (Stock Ledger)
            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => $mutationType,
                'quantity' => -$quantity, // Nilai minus untuk pengurangan stok
                'unit_cost' => $currentHpp,
                'total_cost' => $quantity * $currentHpp,
                'qty_before' => $physicalQty,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: 'Pengurangan stok ('.$mutationType.')',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Reservasi Stok Otomatis (Checkout PWA Orders dengan TTL 30 Menit - AGENTS.md Section 2 & 4D).
     */
    public function reserveStock(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas reservasi harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $physicalQty = $stock ? (float) $stock->physical_quantity : 0.0;
            $reservedQty = $stock ? (float) $stock->reserved_quantity : 0.0;
            $quarantinedQty = $stock ? (float) $stock->quarantined_quantity : 0.0;
            $availableStock = max(0, $physicalQty - $reservedQty - $quarantinedQty);

            if ($quantity > $availableStock) {
                throw new InsufficientStockException(
                    productId: $product->id,
                    storeId: $store->id,
                    requestedQuantity: $quantity,
                    availableQuantity: $availableStock,
                    message: "Gagal mereservasi stok produk [{$product->name}]. Stok tersedia: {$availableStock}, diminta: {$quantity}."
                );
            }

            $newReserved = $reservedQty + $quantity;
            $stock->update([
                'reserved_quantity' => $newReserved,
            ]);

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'RESERVATION',
                'quantity' => $quantity,
                'unit_cost' => (float) $product->cost_price,
                'total_cost' => $quantity * (float) $product->cost_price,
                'qty_before' => $availableStock,
                'qty_after' => $availableStock - $quantity,
                'notes' => $notes ?: 'Reservasi stok pesanan PWA (TTL 30 menit)',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Pembatalan / Pelepasan Reservasi Stok (Pesanan dibatalkan / TTL 30 menit kedaluwarsa).
     */
    public function releaseReservedStock(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas pelepasan reservasi harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentReserved = (float) $stock->reserved_quantity;
            $releasedQty = min($currentReserved, $quantity);
            $newReserved = max(0, $currentReserved - $releasedQty);

            $stock->update([
                'reserved_quantity' => $newReserved,
            ]);

            $availableNow = (float) $stock->available_quantity;

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'RESERVATION_RELEASE',
                'quantity' => $releasedQty,
                'unit_cost' => (float) $product->cost_price,
                'total_cost' => $releasedQty * (float) $product->cost_price,
                'qty_before' => $availableNow - $releasedQty,
                'qty_after' => $availableNow,
                'notes' => $notes ?: 'Pelepasan reservasi pesanan PWA',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Realisasi Pesanan Reservasi Menjadi Penjualan Final.
     * Mengurangi reserved_quantity dan physical_quantity secara bersamaan.
     */
    public function fulfillReservedStock(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->firstOrFail();

            $physicalBefore = (float) $stock->physical_quantity;
            $reservedBefore = (float) $stock->reserved_quantity;

            $newPhysical = max(0, $physicalBefore - $quantity);
            $newReserved = max(0, $reservedBefore - $quantity);

            $stock->update([
                'physical_quantity' => $newPhysical,
                'reserved_quantity' => $newReserved,
            ]);

            // Update master produk
            $currentHpp = (float) $lockedProduct->cost_price;
            $newTotalQty = max(0, (float) $lockedProduct->total_quantity - $quantity);
            $newTotalValue = max(0, $newTotalQty * $currentHpp);

            $lockedProduct->update([
                'total_quantity' => $newTotalQty,
                'total_stock_value' => $newTotalValue,
            ]);

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'SALE',
                'quantity' => -$quantity,
                'unit_cost' => $currentHpp,
                'total_cost' => $quantity * $currentHpp,
                'qty_before' => $physicalBefore,
                'qty_after' => $newPhysical,
                'notes' => $notes ?: 'Pemenuhan pesanan PWA / Penjualan',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Karantina Barang Rusak atau Barang Retur (AGENTS.md Section 2).
     */
    public function quarantineStock(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        string $reason,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas karantina harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $referenceNumber,
            $reason,
            $userId
        ) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $available = (float) $stock->available_quantity;
            if ($quantity > $available) {
                throw new InsufficientStockException(
                    productId: $product->id,
                    storeId: $store->id,
                    requestedQuantity: $quantity,
                    availableQuantity: $available,
                    message: "Gagal memasukkan stok ke karantina. Stok tersedia hanya {$available}, diminta {$quantity}."
                );
            }

            $newQuarantined = (float) $stock->quarantined_quantity + $quantity;
            $stock->update([
                'quarantined_quantity' => $newQuarantined,
            ]);

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'QUARANTINE',
                'quantity' => $quantity,
                'unit_cost' => (float) $product->cost_price,
                'total_cost' => $quantity * (float) $product->cost_price,
                'qty_before' => $available,
                'qty_after' => $available - $quantity,
                'notes' => 'Masuk karantina: '.$reason,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Pengembalian Barang dari Penjualan POS (Sales Return / Retur POS).
     * Mengembalikan kuantitas fisik ke stok toko dan memulihkan nilai persediaan.
     * Jika barang rusak (isDamaged = true), stok langsung masuk karantina (quarantined_quantity)
     * sehingga TIDAK menambah stok yang layak jual (available_quantity).
     */
    public function recordSalesReturn(
        Product $product,
        Store $store,
        float $quantity,
        float $unitCost,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null,
        bool $isDamaged = false
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas retur penjualan harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $unitCost,
            $referenceNumber,
            $notes,
            $userId,
            $isDamaged
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::firstOrCreate(
                [
                    'company_id' => $lockedProduct->company_id,
                    'store_id' => $store->id,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'physical_quantity' => 0.0000,
                    'reserved_quantity' => 0.0000,
                    'quarantined_quantity' => 0.0000,
                ]
            );

            $stock = ProductStock::where('id', $stock->id)->lockForUpdate()->firstOrFail();

            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = $qtyBefore + $quantity;

            $stockUpdateData = [
                'physical_quantity' => $qtyAfter,
            ];

            if ($isDamaged) {
                // Sesuai AGENTS.md Section 2:
                // Formula: Available Stock = physical_quantity - reserved_quantity - quarantined_quantity
                // Barang rusak masuk karantina agar TIDAK menambah stok yang layak jual (available).
                $stockUpdateData['quarantined_quantity'] = (float) $stock->quarantined_quantity + $quantity;
            }

            $stock->update($stockUpdateData);

            // Pulihkan kuantitas global dan nilai persediaan produk
            $newTotalQty = (float) $lockedProduct->total_quantity + $quantity;
            $newTotalStockValue = (float) $lockedProduct->total_stock_value + ($quantity * $unitCost);

            $lockedProduct->update([
                'total_quantity' => $newTotalQty,
                'total_stock_value' => $newTotalStockValue,
            ]);

            $conditionLabel = $isDamaged ? 'RUSAK / KARANTINA' : 'LAYAK JUAL';
            $defaultNotes = "Retur Penjualan POS ({$referenceNumber}) [Kondisi: {$conditionLabel}]";

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'SALE_RETURN',
                'quantity' => $quantity, // Positif karena barang masuk kembali
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ? "{$notes} [Kondisi: {$conditionLabel}]" : $defaultNotes,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Pengembalian Barang Cacat / Rusak ke Pemasok (Purchase Return / Retur Pembelian).
     * Mengurangi stok fisik toko dan nilai persediaan dengan perlindungan zero negative inventory.
     */
    public function recordPurchaseReturn(
        Product $product,
        Store $store,
        float $quantity,
        float $purchaseCostPerUnit,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null,
        bool $isDamaged = false
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas retur pembelian harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $purchaseCostPerUnit,
            $referenceNumber,
            $notes,
            $userId,
            $isDamaged
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->first();

            $physicalQty = $stock ? (float) $stock->physical_quantity : 0.0;
            $reservedQty = $stock ? (float) $stock->reserved_quantity : 0.0;
            $quarantinedQty = $stock ? (float) $stock->quarantined_quantity : 0.0;
            $availableStock = max(0, $physicalQty - $reservedQty - $quarantinedQty);

            if ($isDamaged) {
                // Barang rusak diambil dari stok karantina (quarantined_quantity)
                if ($quantity > $quarantinedQty) {
                    throw new InsufficientStockException(
                        productId: $lockedProduct->id,
                        storeId: $store->id,
                        requestedQuantity: $quantity,
                        availableQuantity: $quarantinedQty,
                        message: "Gagal memproses retur pembelian barang rusak. Stok karantina produk [{$lockedProduct->name}] hanya {$quarantinedQty}, diminta retur {$quantity}."
                    );
                }
            } else {
                // Barang layak jual diambil dari stok tersedia (available stock)
                if ($quantity > $availableStock) {
                    throw new InsufficientStockException(
                        productId: $lockedProduct->id,
                        storeId: $store->id,
                        requestedQuantity: $quantity,
                        availableQuantity: $availableStock,
                        message: "Gagal memproses retur pembelian. Stok produk [{$lockedProduct->name}] yang tersedia hanya {$availableStock}, diminta retur {$quantity}."
                    );
                }
            }

            $qtyAfter = $physicalQty - $quantity;
            if ($qtyAfter < 0) {
                throw new NegativeInventoryException(
                    productId: $lockedProduct->id,
                    storeId: $store->id,
                    currentQuantity: $physicalQty,
                    attemptedDeduction: $quantity
                );
            }

            $stockUpdateData = [
                'physical_quantity' => $qtyAfter,
            ];
            if ($isDamaged) {
                $stockUpdateData['quarantined_quantity'] = max(0.0, $quarantinedQty - $quantity);
            }

            $stock->update($stockUpdateData);

            // Nilai persediaan berkurang dan HPP diselaraskan
            $newTotalQty = max(0, (float) $lockedProduct->total_quantity - $quantity);
            $newTotalStockValue = max(0, (float) $lockedProduct->total_stock_value - ($quantity * $purchaseCostPerUnit));

            $productUpdateData = [
                'total_quantity' => $newTotalQty,
                'total_stock_value' => $newTotalStockValue,
            ];

            if ($newTotalQty > 0) {
                // Selaraskan HPP Moving Average agar total_stock_value selalu setara dengan total_quantity * cost_price
                $productUpdateData['cost_price'] = round($newTotalStockValue / $newTotalQty, 4);
            }

            $lockedProduct->update($productUpdateData);

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'PURCHASE_RETURN',
                'quantity' => -$quantity, // Negatif karena barang keluar ke supplier
                'unit_cost' => $purchaseCostPerUnit,
                'total_cost' => $quantity * $purchaseCostPerUnit,
                'qty_before' => $physicalQty,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: "Retur Pembelian ke Supplier ({$referenceNumber})",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Dispatch / Pengiriman Transfer Antar Cabang (In-Transit Dispatch - AGENTS.md Section 4C).
     * Mengurangi stok fisik toko asal dan mencatat mutasi TRANSFER_OUT.
     */
    public function recordTransferDispatch(
        Product $product,
        Store $sourceStore,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas transfer barang harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $sourceStore,
            $quantity,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $sourceStore->id)
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->first();

            $physicalQty = $stock ? (float) $stock->physical_quantity : 0.0;
            $reservedQty = $stock ? (float) $stock->reserved_quantity : 0.0;
            $quarantinedQty = $stock ? (float) $stock->quarantined_quantity : 0.0;
            $availableStock = max(0, $physicalQty - $reservedQty - $quarantinedQty);

            if ($quantity > $availableStock) {
                throw new InsufficientStockException(
                    productId: $lockedProduct->id,
                    storeId: $sourceStore->id,
                    requestedQuantity: $quantity,
                    availableQuantity: $availableStock,
                    message: "Gagal mengirim transfer cabang. Stok tersedia produk [{$lockedProduct->name}] hanya {$availableStock}, diminta transfer {$quantity}."
                );
            }

            $qtyAfter = $physicalQty - $quantity;
            if ($qtyAfter < 0) {
                throw new NegativeInventoryException(
                    productId: $lockedProduct->id,
                    storeId: $sourceStore->id,
                    currentQuantity: $physicalQty,
                    attemptedDeduction: $quantity
                );
            }

            $stock->update(['physical_quantity' => $qtyAfter]);
            $unitCost = (float) $lockedProduct->cost_price;

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $sourceStore->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'TRANSFER_OUT',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $physicalQty,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: "Pengiriman Transfer Antar Cabang ({$referenceNumber})",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Konfirmasi Penerimaan Transfer di Cabang Tujuan (Store Receipt Confirmation).
     * Menambah stok fisik toko tujuan dan mencatat mutasi TRANSFER_IN.
     */
    public function recordTransferReceipt(
        Product $product,
        Store $destinationStore,
        float $quantity,
        float $unitCost,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas penerimaan transfer harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $destinationStore,
            $quantity,
            $unitCost,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::firstOrCreate(
                [
                    'store_id' => $destinationStore->id,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'company_id' => $lockedProduct->company_id,
                    'physical_quantity' => 0.0,
                    'reserved_quantity' => 0.0,
                    'quarantined_quantity' => 0.0,
                    'shelf_location' => null,
                ]
            );

            $stock = ProductStock::where('id', $stock->id)->lockForUpdate()->first();
            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = $qtyBefore + $quantity;

            $stock->update(['physical_quantity' => $qtyAfter]);

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $destinationStore->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'TRANSFER_IN',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: "Penerimaan Transfer Antar Cabang ({$referenceNumber})",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Penyesuaian Hasil Stock Opname Fisik (Stock Opname Reconciliation).
     * Mengoreksi selisih fisik vs sistem dengan pencatatan mutasi OPNAME_ADJUSTMENT.
     */
    public function recordStockOpnameAdjustment(
        Product $product,
        Store $store,
        float $actualQuantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): ?StockMutation {
        return DB::transaction(function () use (
            $product,
            $store,
            $actualQuantity,
            $referenceNumber,
            $notes,
            $userId
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            /** @var ProductStock $stock */
            $stock = ProductStock::firstOrCreate(
                ['store_id' => $store->id, 'product_id' => $lockedProduct->id],
                ['company_id' => $lockedProduct->company_id, 'physical_quantity' => 0.0, 'reserved_quantity' => 0.0, 'quarantined_quantity' => 0.0]
            );
            $stock = ProductStock::where('id', $stock->id)->lockForUpdate()->first();

            $qtyBefore = (float) $stock->physical_quantity;
            $discrepancy = $actualQuantity - $qtyBefore;
            $unitCost = (float) $lockedProduct->cost_price;

            if (abs($discrepancy) < 0.0001) {
                return null; // Tidak ada selisih fisik
            }

            // Update stok toko sesuai hitungan fisik
            $stock->update(['physical_quantity' => $actualQuantity]);

            // Update total akumulasi global produk
            $diffGlobalQty = (float) $lockedProduct->total_quantity + $discrepancy;
            $diffGlobalValue = max(0, $diffGlobalQty * $unitCost);
            $lockedProduct->update([
                'total_quantity' => max(0, $diffGlobalQty),
                'total_stock_value' => $diffGlobalValue,
            ]);

            $mutationType = $discrepancy > 0 ? 'OPNAME_ADJUSTMENT_PLUS' : 'OPNAME_ADJUSTMENT_MINUS';

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => $mutationType,
                'quantity' => $discrepancy,
                'unit_cost' => $unitCost,
                'total_cost' => abs($discrepancy) * $unitCost,
                'qty_before' => $qtyBefore,
                'qty_after' => $actualQuantity,
                'notes' => $notes ?: "Penyesuaian Stock Opname ({$referenceNumber})",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Penambahan Stok dari Hasil Perakitan Barang Jadi (Assembly In) atau Pembongkaran Komponen (Disassembly In).
     * Mengkalkulasi ulang HPP Moving Average produk jika diperlukan.
     */
    public function recordAssemblyStock(
        Product $product,
        Store $store,
        float $quantity,
        float $unitCost,
        string $referenceNumber,
        string $mutationType = 'ASSEMBLY_IN',
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas perakitan masuk harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $unitCost,
            $referenceNumber,
            $mutationType,
            $notes,
            $userId
        ) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            $currentQty = (float) $lockedProduct->total_quantity;
            $currentStockValue = (float) $lockedProduct->total_stock_value;

            $incomingValue = $quantity * $unitCost;
            $newTotalQty = $currentQty + $quantity;
            $newTotalStockValue = $currentStockValue + $incomingValue;

            $newCostPrice = $newTotalQty > 0
                ? round($newTotalStockValue / $newTotalQty, 4)
                : $unitCost;

            $lockedProduct->update([
                'cost_price' => $newCostPrice,
                'total_quantity' => $newTotalQty,
                'total_stock_value' => $newTotalStockValue,
            ]);

            /** @var ProductStock $stock */
            $stock = ProductStock::firstOrCreate(
                [
                    'store_id' => $store->id,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'company_id' => $lockedProduct->company_id,
                    'physical_quantity' => 0.0,
                    'reserved_quantity' => 0.0,
                    'quarantined_quantity' => 0.0,
                ]
            );

            $stock = ProductStock::where('id', $stock->id)->lockForUpdate()->first();
            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = $qtyBefore + $quantity;

            $stock->update(['physical_quantity' => $qtyAfter]);

            return StockMutation::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => $mutationType,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $incomingValue,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: "Penambahan Stok Perakitan ({$referenceNumber})",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Selesaikan barang karantina dengan Potong Hutang Supplier (Credit Note).
     */
    public function resolveQuarantineDeductDebt(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas penyelesaian karantina harus > 0');
        }

        return DB::transaction(function () use ($product, $store, $quantity, $referenceNumber, $notes, $userId) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity > (float) $stock->quarantined_quantity) {
                throw new \InvalidArgumentException("Kuantitas karantina tidak mencukupi (Tersedia: {$stock->quarantined_quantity})");
            }

            $unitCost = (float) $product->cost_price;
            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = max(0, $qtyBefore - $quantity);

            $stock->update([
                'quarantined_quantity' => max(0, (float) $stock->quarantined_quantity - $quantity),
                'physical_quantity' => $qtyAfter,
            ]);

            $product->update([
                'total_quantity' => max(0, (float) $product->total_quantity - $quantity),
                'total_stock_value' => max(0, (float) $product->total_stock_value - ($quantity * $unitCost)),
            ]);

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'QUARANTINE_RESOLVE_DEBT',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: 'Penyelesaian Karantina: Potong Hutang Supplier',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Selesaikan barang karantina dengan Pengembalian Tunai / Transfer Bank.
     */
    public function resolveQuarantineCashRefund(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas penyelesaian karantina harus > 0');
        }

        return DB::transaction(function () use ($product, $store, $quantity, $referenceNumber, $notes, $userId) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity > (float) $stock->quarantined_quantity) {
                throw new \InvalidArgumentException("Kuantitas karantina tidak mencukupi (Tersedia: {$stock->quarantined_quantity})");
            }

            $unitCost = (float) $product->cost_price;
            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = max(0, $qtyBefore - $quantity);

            $stock->update([
                'quarantined_quantity' => max(0, (float) $stock->quarantined_quantity - $quantity),
                'physical_quantity' => $qtyAfter,
            ]);

            $product->update([
                'total_quantity' => max(0, (float) $product->total_quantity - $quantity),
                'total_stock_value' => max(0, (float) $product->total_stock_value - ($quantity * $unitCost)),
            ]);

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'QUARANTINE_RESOLVE_CASH',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: 'Penyelesaian Karantina: Terima Pengembalian Tunai',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Selesaikan barang karantina dengan Tukar Guling / Ganti Barang Baru (1-to-1 Replacement).
     * Stok karantina berkurang, stok layak jual bertambah, stok fisik total tetap.
     */
    public function resolveQuarantineReplacement(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas penyelesaian karantina harus > 0');
        }

        return DB::transaction(function () use ($product, $store, $quantity, $referenceNumber, $notes, $userId) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity > (float) $stock->quarantined_quantity) {
                throw new \InvalidArgumentException("Kuantitas karantina tidak mencukupi (Tersedia: {$stock->quarantined_quantity})");
            }

            $unitCost = (float) $product->cost_price;
            $availBefore = (float) $stock->available_quantity;

            $stock->update([
                'quarantined_quantity' => max(0, (float) $stock->quarantined_quantity - $quantity),
            ]);

            $availAfter = (float) $stock->available_quantity;

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'QUARANTINE_REPLACEMENT',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $availBefore,
                'qty_after' => $availAfter,
                'notes' => $notes ?: 'Penyelesaian Karantina: Tukar Guling Barang Baru (1-to-1)',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Selesaikan barang karantina dengan Pemusnahan / Write-Off (Beban Toko).
     */
    public function resolveQuarantineWriteOff(
        Product $product,
        Store $store,
        float $quantity,
        string $referenceNumber,
        ?string $notes = null,
        ?string $userId = null
    ): StockMutation {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas pemusnahan karantina harus > 0');
        }

        return DB::transaction(function () use ($product, $store, $quantity, $referenceNumber, $notes, $userId) {
            /** @var ProductStock $stock */
            $stock = ProductStock::where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($quantity > (float) $stock->quarantined_quantity) {
                throw new \InvalidArgumentException("Kuantitas karantina tidak mencukupi (Tersedia: {$stock->quarantined_quantity})");
            }

            $unitCost = (float) $product->cost_price;
            $qtyBefore = (float) $stock->physical_quantity;
            $qtyAfter = max(0, $qtyBefore - $quantity);

            $stock->update([
                'quarantined_quantity' => max(0, (float) $stock->quarantined_quantity - $quantity),
                'physical_quantity' => $qtyAfter,
            ]);

            $product->update([
                'total_quantity' => max(0, (float) $product->total_quantity - $quantity),
                'total_stock_value' => max(0, (float) $product->total_stock_value - ($quantity * $unitCost)),
            ]);

            return StockMutation::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => $referenceNumber,
                'mutation_type' => 'QUARANTINE_WRITE_OFF',
                'quantity' => -$quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'qty_before' => $qtyBefore,
                'qty_after' => $qtyAfter,
                'notes' => $notes ?: 'Penyelesaian Karantina: Pemusnahan / Write-Off (Beban Toko)',
                'created_by' => $userId,
            ]);
        });
    }

}
