<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BatchExpiryService
{
    /**
     * Daftarkan Batch / Lot Baru ke Database dan Tambahkan Stoknya.
     */
    public function registerBatch(
        Store $store,
        Product $product,
        string $batchNumber,
        string $expiredDate,
        float $quantity,
        float $unitCost = 0.0,
        ?string $mfgDate = null,
        ?string $notes = null
    ): ProductBatch {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas batch masuk harus lebih besar dari 0.');
        }

        $expDateObj = Carbon::parse($expiredDate)->toDateString();
        $mfgDateObj = $mfgDate ? Carbon::parse($mfgDate)->toDateString() : null;

        return DB::transaction(function () use (
            $store,
            $product,
            $batchNumber,
            $expDateObj,
            $mfgDateObj,
            $quantity,
            $unitCost,
            $notes
        ) {
            $company = $product->company;
            if (! $company || $store->company_id !== $company->id) {
                abort(403, 'Produk dan cabang batch harus berasal dari perusahaan yang sama.');
            }

            /** @var ProductBatch|null $batch */
            $batch = ProductBatch::where('company_id', $company->id)
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->where('batch_number', trim($batchNumber))
                ->whereDate('expired_date', $expDateObj)
                ->lockForUpdate()
                ->first();

            if ($batch) {
                // Tambah stok ke batch eksisting yang sama persis
                $newInitQty = (float) $batch->initial_quantity + $quantity;
                $newCurrQty = (float) $batch->current_quantity + $quantity;

                // Hitung Moving Average HPP batch bersangkutan
                $currentTotalVal = (float) $batch->current_quantity * (float) $batch->unit_cost;
                $incomingVal = $quantity * $unitCost;
                $newUnitCost = $newCurrQty > 0 ? ($currentTotalVal + $incomingVal) / $newCurrQty : $unitCost;

                $batch->update([
                    'initial_quantity' => $newInitQty,
                    'current_quantity' => $newCurrQty,
                    'unit_cost' => round($newUnitCost, 4),
                    'status' => Carbon::parse($expDateObj)->isPast() ? 'EXPIRED' : 'ACTIVE',
                    'notes' => $notes ?: $batch->notes,
                ]);
            } else {
                $status = Carbon::parse($expDateObj)->isPast() ? 'EXPIRED' : 'ACTIVE';

                $batch = ProductBatch::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'batch_number' => trim($batchNumber),
                    'expired_date' => $expDateObj,
                    'manufacture_date' => $mfgDateObj,
                    'initial_quantity' => $quantity,
                    'current_quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'status' => $status,
                    'notes' => $notes,
                ]);
            }

            return $batch;
        });
    }

    /**
     * Alokasi Stok Berdasarkan FEFO (First Expired First Out).
     *
     * Mengambil batch yang paling awal mendekati kedaluwarsa secara berurutan.
     * Mengembalikan rincian kuantitas yang dipotong dari masing-masing batch.
     *
     * @return array<int, array{batch: ProductBatch, quantity: float}>
     *
     * @throws InsufficientStockException
     */
    public function allocateFefoBatches(Store $store, Product $product, float $requiredQuantity): array
    {
        if ($requiredQuantity <= 0) {
            return [];
        }

        // Jika produk tidak diatur memiliki batch & expiry, lewati alokasi batch
        if (! $product->has_batch_expiry) {
            return [];
        }

        /** @var Collection<int, ProductBatch> $batches */
        $batches = ProductBatch::where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->fefoOrder()
            ->lockForUpdate()
            ->get();

        $totalAvailable = (float) $batches->sum('current_quantity');
        if ($totalAvailable < $requiredQuantity) {
            throw new InsufficientStockException(
                productId: $product->id,
                storeId: $store->id,
                requestedQuantity: $requiredQuantity,
                availableQuantity: $totalAvailable,
                message: "Stok batch aktif untuk [{$product->name}] di toko [{$store->name}] tidak mencukupi FEFO. Tersedia: {$totalAvailable}, Diminta: {$requiredQuantity}."
            );
        }

        $allocated = [];
        $remaining = $requiredQuantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0.00001) {
                break;
            }

            $batchQty = (float) $batch->current_quantity;
            $qtyToTake = min($batchQty, $remaining);

            $allocated[] = [
                'batch' => $batch,
                'quantity' => $qtyToTake,
            ];

            $remaining -= $qtyToTake;
        }

        return $allocated;
    }

    /**
     * Potong Stok Batch Tertentu (Zero Negative Safety).
     */
    public function deductBatchStock(
        ProductBatch $batch,
        float $quantity,
        string $referenceNumber,
        string $reason = 'SALE'
    ): void {
        if ($quantity <= 0) {
            return;
        }

        /** @var ProductBatch $lockedBatch */
        $lockedBatch = ProductBatch::where('id', $batch->id)->lockForUpdate()->firstOrFail();

        $current = (float) $lockedBatch->current_quantity;
        if ($current < $quantity) {
            throw new InsufficientStockException(
                productId: $lockedBatch->product_id,
                storeId: $lockedBatch->store_id,
                requestedQuantity: $quantity,
                availableQuantity: $current,
                message: "Stok batch [{$lockedBatch->batch_number}] tidak mencukupi. Tersedia: {$current}, Diminta: {$quantity}."
            );
        }

        $newQty = round($current - $quantity, 4);
        $status = $newQty <= 0.0001 ? 'DEPLETED' : $lockedBatch->status;

        $lockedBatch->update([
            'current_quantity' => max(0, $newQty),
            'status' => $status,
        ]);
    }

    /**
     * Ambil Daftar Batch yang Mendekati Kedaluwarsa (Near-Expired Alert).
     */
    public function getNearExpiredBatches(?Store $store = null, int $daysThreshold = 30): Collection
    {
        $query = ProductBatch::with(['product.category', 'store'])
            ->nearExpired($daysThreshold);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        return $query->get();
    }

    /**
     * Ambil Daftar Batch yang Sudah Kedaluwarsa.
     */
    public function getExpiredBatches(?Store $store = null): Collection
    {
        $query = ProductBatch::with(['product.category', 'store'])
            ->expired();

        if ($store) {
            $query->where('store_id', $store->id);
        }

        return $query->get();
    }

    /**
     * Sinkronisasi Otomatis Status Batch yang Kedaluwarsa.
     */
    public function syncExpiredStatus(): int
    {
        return ProductBatch::where('status', 'ACTIVE')
            ->whereDate('expired_date', '<', today())
            ->update(['status' => 'EXPIRED']);
    }
}

