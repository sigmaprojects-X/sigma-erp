<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockRequirement;
use App\Models\StockRequirementAllocation;
use App\Models\StockTransfer;
use App\Models\Store;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAllocationService
{
    /**
     * Hitung Kebutuhan Stok (Formula Tahap 2H.1)
     *
     * S_avail = max(0, physical - reserved - quarantined)
     * D_gross = max(0, target_stock - S_avail)
     * D_uncovered = max(0, D_gross - Q_inbound - L_pending)
     * D_po_eligible = IsTransferActive ? 0 : D_uncovered
     */
    public function calculateDeficit(Store $store, Product $product, float $targetStock): array
    {
        /** @var ProductStock|null $stock */
        $stock = ProductStock::where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();

        $physicalQty = $stock ? (float) $stock->physical_quantity : 0.0;
        $reservedQty = $stock ? (float) $stock->reserved_quantity : 0.0;
        $quarantinedQty = $stock ? (float) $stock->quarantined_quantity : 0.0;

        // Pencegahan reduksi ganda: S_avail sudah mengeluarkan reserved dan quarantined
        $availableStock = max(0.0, $physicalQty - $reservedQty - $quarantinedQty);
        $grossDeficit = max(0.0, $targetStock - $availableStock);

        // Ambil kuantitas alokasi berjalan dari stock_requirements aktif
        $activeReq = StockRequirement::active()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();

        $committedInbound = 0.0;
        $pendingLocks = 0.0;

        if ($activeReq) {
            $committedInbound = (float) $activeReq->allocations()
                ->where('status', 'COMMITTED')
                ->sum('outstanding_base_qty');

            $pendingLocks = (float) $activeReq->allocations()
                ->where('status', 'ACTIVE')
                ->sum('outstanding_base_qty');
        }

        // Kebutuhan riil yang belum tercakup alokasi/pasokan
        $uncoveredRequirement = max(0.0, $grossDeficit - $committedInbound - $pendingLocks);

        // Aturan Transfer-First: Cek apakah ada transfer masuk aktif ke toko ini
        $isTransferActive = $this->isTransferActive($store, $product);

        // Kuota belanja supplier (D_po_eligible) dibekukan (0) jika transfer aktif
        $purchaseEligibleQty = $isTransferActive ? 0.0 : $uncoveredRequirement;

        return [
            'physical_stock' => $physicalQty,
            'reserved_stock' => $reservedQty,
            'quarantined_stock' => $quarantinedQty,
            'available_stock' => $availableStock,
            'target_stock' => $targetStock,
            'gross_deficit' => $grossDeficit,
            'committed_inbound' => $committedInbound,
            'pending_locks' => $pendingLocks,
            'uncovered_requirement' => $uncoveredRequirement,
            'is_transfer_active' => $isTransferActive,
            'purchase_eligible_qty' => $purchaseEligibleQty,
        ];
    }

    /**
     * Dapatkan atau Buat Header Kebutuhan Aktif Tunggal (Single Active Header).
     */
    public function getOrCreateActiveRequirement(
        Store $store,
        Product $product,
        float $targetStock,
        ?string $userId = null
    ): StockRequirement {
        return DB::transaction(function () use ($store, $product, $targetStock, $userId) {
            // 1. Kunci Baris Master Produk (Hierarki Tingkat 1)
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            // 2. Pastikan Baris Stok Ada Secara Atomik & Kunci (Hierarki Tingkat 2)
            $stock = ProductStock::firstOrCreate(
                [
                    'store_id' => $store->id,
                    'product_id' => $lockedProduct->id,
                ],
                [
                    'company_id' => $lockedProduct->company_id,
                    'physical_quantity' => 0.0000,
                    'reserved_quantity' => 0.0000,
                    'quarantined_quantity' => 0.0000,
                ]
            );

            ProductStock::where('id', $stock->id)->lockForUpdate()->first();

            // 3. Cari Header Kebutuhan Aktif yang Ada (Hierarki Tingkat 3)
            /** @var StockRequirement|null $req */
            $req = StockRequirement::active()
                ->where('company_id', $lockedProduct->company_id)
                ->where('store_id', $store->id)
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->first();

            $metrics = $this->calculateDeficit($store, $lockedProduct, $targetStock);

            if ($req) {
                // Perbarui data dinamis pada header aktif yang ada
                $req->update([
                    'target_stock' => $targetStock,
                    'available_stock_snapshot' => $metrics['available_stock'],
                    'gross_deficit' => $metrics['gross_deficit'],
                    'updated_by' => $userId,
                ]);

                return $req->fresh(['allocations']);
            }

            // Buat header baru jika belum ada
            return StockRequirement::create([
                'company_id' => $lockedProduct->company_id,
                'store_id' => $store->id,
                'product_id' => $lockedProduct->id,
                'target_stock' => $targetStock,
                'available_stock_snapshot' => $metrics['available_stock'],
                'gross_deficit' => $metrics['gross_deficit'],
                'total_allocated' => 0.0000,
                'status' => 'OPEN',
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Alokasikan Kebutuhan Stok (Draft PO atau Draft Transfer)
     */
    public function allocateStock(
        StockRequirement $requirement,
        string $allocationType,
        string $referenceId,
        ?string $referenceItemId,
        float $baseQuantity,
        ?string $referenceNumber = null,
        ?string $userId = null,
        ?string $idempotencyKey = null
    ): StockRequirementAllocation {
        if ($baseQuantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas alokasi harus lebih besar dari 0.');
        }

        // Pengecekan Idempotensi Terarah
        if ($idempotencyKey) {
            $cachedAllocationId = Cache::get("idempotency:stock_alloc:{$idempotencyKey}");
            if ($cachedAllocationId) {
                /** @var StockRequirementAllocation|null $cached */
                $cached = StockRequirementAllocation::find($cachedAllocationId);
                if ($cached) {
                    return $cached;
                }
            }
        }

        return DB::transaction(function () use (
            $requirement,
            $allocationType,
            $referenceId,
            $referenceItemId,
            $baseQuantity,
            $referenceNumber,
            $userId,
            $idempotencyKey
        ) {
            // 1. Kunci Baris Master Produk (Tingkat 1)
            $lockedProduct = Product::where('id', $requirement->product_id)->lockForUpdate()->firstOrFail();

            // 2. Kunci Baris Stok Cabang (Tingkat 2)
            ProductStock::where('store_id', $requirement->store_id)
                ->where('product_id', $lockedProduct->id)
                ->lockForUpdate()
                ->first();

            // 3. Kunci Header Kebutuhan (Tingkat 3)
            /** @var StockRequirement $lockedReq */
            $lockedReq = StockRequirement::where('id', $requirement->id)->lockForUpdate()->firstOrFail();

            // Pengecekan Idempotensi Atomik di Dalam Transaksi Terkunci
            if ($idempotencyKey) {
                $existing = StockRequirementAllocation::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    Cache::put("idempotency:stock_alloc:{$idempotencyKey}", $existing->id, now()->addMinutes(10));
                    return $existing;
                }
            }

            if (!in_array($lockedReq->status, ['OPEN', 'PARTIALLY_ALLOCATED', 'OVER_COMMITTED'])) {
                throw new DomainException("Header kebutuhan berstatus [{$lockedReq->status}] tidak dapat menerima alokasi baru.");
            }

            // Hitung ulang kebutuhan dinamis terkini
            $store = $lockedReq->store;
            $metrics = $this->calculateDeficit($store, $lockedProduct, (float) $lockedReq->target_stock);

            // Validasi Aturan Transfer-First untuk Pembelian Supplier (PO)
            if ($allocationType === 'PURCHASE_ORDER' && $metrics['is_transfer_active']) {
                throw ValidationException::withMessages([
                    'allocation' => 'Kebijakan Transfer-First: Pengadaan supplier untuk produk ini ditahan karena terdapat transfer antar-cabang yang masih aktif.',
                ]);
            }

            // Validasi Kecukupan Defisit Bersih Belum Tercakup
            $uncovered = (float) $metrics['uncovered_requirement'];
            if ($baseQuantity > ($uncovered + 0.0001)) { // Toleransi presisi floating point
                throw ValidationException::withMessages([
                    'quantity' => "Kuantitas alokasi ({$baseQuantity}) melampaui sisa kebutuhan yang belum tercakup ({$uncovered}).",
                ]);
            }

            // Simpan Baris Alokasi Anak (dengan perlindungan Unique Exception untuk Concurrency)
            try {
                $allocation = StockRequirementAllocation::create([
                    'stock_requirement_id' => $lockedReq->id,
                    'allocation_type' => $allocationType,
                    'reference_id' => $referenceId,
                    'reference_item_id' => $referenceItemId,
                    'reference_number' => $referenceNumber,
                    'idempotency_key' => $idempotencyKey,
                    'initial_allocated_base_qty' => $baseQuantity,
                    'received_base_qty' => 0.0000,
                    'released_base_qty' => 0.0000,
                    'outstanding_base_qty' => $baseQuantity,
                    'status' => 'ACTIVE',
                    'allocated_by' => $userId,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException | \Illuminate\Database\QueryException $e) {
                if ($idempotencyKey) {
                    $existing = StockRequirementAllocation::where('idempotency_key', $idempotencyKey)->first();
                    if ($existing) {
                        Cache::put("idempotency:stock_alloc:{$idempotencyKey}", $existing->id, now()->addMinutes(10));
                        return $existing;
                    }
                }
                throw $e;
            }

            // Perbarui Total Alokasi dan Status Header Kebutuhan
            $newTotalAllocated = (float) $lockedReq->total_allocated + $baseQuantity;
            $newStatus = ($newTotalAllocated >= (float) $lockedReq->gross_deficit)
                ? 'PARTIALLY_ALLOCATED' // Tetap partially jika belum committed
                : 'PARTIALLY_ALLOCATED';

            $lockedReq->update([
                'total_allocated' => $newTotalAllocated,
                'status' => $newStatus,
                'updated_by' => $userId,
            ]);

            if ($idempotencyKey) {
                Cache::put("idempotency:stock_alloc:{$idempotencyKey}", $allocation->id, now()->addMinutes(2));
            }

            return $allocation;
        });
    }

    /**
     * Komitmenkan Alokasi (PO menjadi ORDERED atau Transfer menjadi IN_TRANSIT).
     * Memindahkan kuantitas dari domain Lock (ACTIVE) ke Pasokan Masuk (COMMITTED).
     */
    public function commitAllocation(StockRequirementAllocation $allocation): void
    {
        DB::transaction(function () use ($allocation) {
            /** @var StockRequirementAllocation $lockedAlloc */
            $lockedAlloc = StockRequirementAllocation::where('id', $allocation->id)->lockForUpdate()->firstOrFail();

            if ($lockedAlloc->status !== 'ACTIVE') {
                return; // Sudah committed atau tidak lagi active
            }

            $lockedAlloc->update(['status' => 'COMMITTED']);

            // Periksa apakah seluruh alokasi kebutuhan sudah COMMITTED & menutup defisit
            /** @var StockRequirement $req */
            $req = StockRequirement::where('id', $lockedAlloc->stock_requirement_id)->lockForUpdate()->firstOrFail();

            $hasActiveDrafts = $req->allocations()->where('status', 'ACTIVE')->exists();
            $totalAllocated = (float) $req->total_allocated;
            $grossDeficit = (float) $req->gross_deficit;

            if (!$hasActiveDrafts && $totalAllocated >= $grossDeficit && $grossDeficit > 0) {
                $req->update(['status' => 'AWAITING_SUPPLY']);
            }
        });
    }

    /**
     * Catat Penerimaan Fisik Parsial / Penuh (GRN PO atau Penerimaan Transfer).
     */
    public function recordPartialFulfillment(StockRequirementAllocation $allocation, float $fulfilledBaseQty): void
    {
        if ($fulfilledBaseQty <= 0) {
            throw new \InvalidArgumentException('Kuantitas pemenuhan fisik harus lebih besar dari 0.');
        }

        DB::transaction(function () use ($allocation, $fulfilledBaseQty) {
            /** @var StockRequirementAllocation $lockedAlloc */
            $lockedAlloc = StockRequirementAllocation::where('id', $allocation->id)->lockForUpdate()->firstOrFail();

            $newReceived = (float) $lockedAlloc->received_base_qty + $fulfilledBaseQty;
            $initial = (float) $lockedAlloc->initial_allocated_base_qty;
            $released = (float) $lockedAlloc->released_base_qty;

            $newOutstanding = max(0.0, $initial - $newReceived - $released);
            $newStatus = ($newOutstanding <= 0.0001) ? 'FULFILLED' : $lockedAlloc->status;

            $lockedAlloc->update([
                'received_base_qty' => $newReceived,
                'outstanding_base_qty' => $newOutstanding,
                'status' => $newStatus,
            ]);

            // Re-evaluasi Status Header Kebutuhan
            /** @var StockRequirement $req */
            $req = StockRequirement::where('id', $lockedAlloc->stock_requirement_id)->lockForUpdate()->firstOrFail();

            $allAllocationsFulfilled = !$req->allocations()
                ->whereIn('status', ['ACTIVE', 'COMMITTED'])
                ->exists();

            $totalReceivedOnRequirement = (float) $req->allocations()->sum('received_base_qty');

            if ($allAllocationsFulfilled && $totalReceivedOnRequirement >= ((float) $req->gross_deficit - 0.0001)) {
                $req->update(['status' => 'FULFILLED']);
            }
        });
    }

    /**
     * Lepas Alokasi (Pembatalan Dokumen / Ditolak Approver).
     */
    public function releaseAllocation(
        StockRequirementAllocation $allocation,
        ?float $releaseBaseQty = null,
        ?string $reason = null
    ): void {
        DB::transaction(function () use ($allocation, $releaseBaseQty, $reason) {
            /** @var StockRequirementAllocation $lockedAlloc */
            $lockedAlloc = StockRequirementAllocation::where('id', $allocation->id)->lockForUpdate()->firstOrFail();

            $outstanding = (float) $lockedAlloc->outstanding_base_qty;
            $qtyToRelease = ($releaseBaseQty === null || $releaseBaseQty > $outstanding) ? $outstanding : $releaseBaseQty;

            if ($qtyToRelease <= 0) {
                return;
            }

            $newReleased = (float) $lockedAlloc->released_base_qty + $qtyToRelease;
            $initial = (float) $lockedAlloc->initial_allocated_base_qty;
            $received = (float) $lockedAlloc->received_base_qty;

            $newOutstanding = max(0.0, $initial - $received - $newReleased);
            $newStatus = ($newOutstanding <= 0.0001 && $received <= 0) ? 'RELEASED' : $lockedAlloc->status;

            $lockedAlloc->update([
                'released_base_qty' => $newReleased,
                'outstanding_base_qty' => $newOutstanding,
                'status' => $newStatus,
                'notes' => $reason ? ($lockedAlloc->notes . ' | Batal: ' . $reason) : $lockedAlloc->notes,
            ]);

            // Kurangi total alokasi pada header dan kembalikan status aktif
            /** @var StockRequirement $req */
            $req = StockRequirement::where('id', $lockedAlloc->stock_requirement_id)->lockForUpdate()->firstOrFail();
            $newTotalAllocated = max(0.0, (float) $req->total_allocated - $qtyToRelease);

            $reqStatus = ($newTotalAllocated <= 0.0001) ? 'OPEN' : 'PARTIALLY_ALLOCATED';

            $req->update([
                'total_allocated' => $newTotalAllocated,
                'status' => $reqStatus,
            ]);
        });
    }

    /**
     * Cek apakah terdapat transfer masuk aktif untuk kombinasi cabang tujuan dan produk.
     * Status aktif: DRAFT, IN_TRANSIT.
     */
    public function isTransferActive(Store $destinationStore, Product $product): bool
    {
        return StockTransfer::where('destination_store_id', $destinationStore->id)
            ->whereIn('status', ['DRAFT', 'IN_TRANSIT'])
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->exists();
    }
}
