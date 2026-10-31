<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockRequirement;
use App\Models\StockRequirementAllocation;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Models\TransferRequest;
use App\Models\TransferRequestItem;
use App\Models\User;
use App\Services\AutoNumberService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService,
        protected StockAllocationService $stockAllocationService
    ) {}

    // =========================================================================
    // 1. ALUR TRANSFER ANTAR CABANG (IN-TRANSIT DISPATCH & STORE RECEIPT)
    // =========================================================================

    /**
     * Buat Draft Transfer Antar Cabang.
     */
    public function createTransfer(
        Company $company,
        Store $sourceStore,
        Store $destinationStore,
        array $items,
        string $transferDate,
        ?string $driverName = null,
        ?string $vehicleNumber = null,
        ?string $notes = null,
        ?string $userId = null
    ): StockTransfer {
        if ($sourceStore->id === $destinationStore->id) {
            throw new \InvalidArgumentException('Toko asal dan toko tujuan transfer tidak boleh sama.');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('Daftar item barang transfer tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $company,
            $sourceStore,
            $destinationStore,
            $items,
            $transferDate,
            $driverName,
            $vehicleNumber,
            $notes,
            $userId
        ) {
            // Hasilkan Nomor Dokumen Transfer Otomatis via AutoNumberService
            $transferNumber = app(AutoNumberService::class)->generateNextNumber('TRF', $sourceStore->code, $company);

            // 1. Hitung total kuantitas per produk dan inisialisasi alokasi kebutuhan di toko tujuan
            $productTotals = [];
            foreach ($items as $item) {
                $pId = $item['product_id'];
                $unit = ProductUnit::findOrFail($item['product_unit_id']);
                $conv = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $bQty = round((float) $item['quantity'] * $conv, 4);
                $productTotals[$pId] = ($productTotals[$pId] ?? 0.0) + $bQty;
            }

            $requirements = [];
            foreach ($productTotals as $productId => $totalBaseQty) {
                $product = Product::findOrFail($productId);

                $existingReq = StockRequirement::active()
                    ->where('company_id', $company->id)
                    ->where('store_id', $destinationStore->id)
                    ->where('product_id', $product->id)
                    ->first();

                $metrics = $this->stockAllocationService->calculateDeficit($destinationStore, $product, 0);
                $minTarget = (float) $metrics['available_stock'] + (float) $metrics['committed_inbound'] + (float) $metrics['pending_locks'] + $totalBaseQty;
                $currentTarget = $existingReq ? (float) $existingReq->target_stock : (float) $product->min_stock_alert * 2;
                $targetStock = max($currentTarget, $minTarget);

                $requirements[$productId] = $this->stockAllocationService->getOrCreateActiveRequirement(
                    $destinationStore,
                    $product,
                    $targetStock,
                    $userId
                );
            }

            $totalItems = count($items);
            $totalSentBaseQty = 0.0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unit = ProductUnit::findOrFail($item['product_unit_id']);
                $qty = (float) $item['quantity'];

                $conversion = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round($qty * $conversion, 4);
                $totalSentBaseQty += $baseQty;

                $preparedItems[] = [
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'unit_code' => $unit->unit_code,
                    'conversion_factor' => $conversion,
                    'quantity_sent' => $qty,
                    'base_quantity_sent' => $baseQty,
                    'quantity_received' => 0.00,
                    'base_quantity_received' => 0.0000,
                    'unit_cost' => (float) $product->cost_price,
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $transfer = StockTransfer::create([
                'company_id' => $company->id,
                'source_store_id' => $sourceStore->id,
                'destination_store_id' => $destinationStore->id,
                'transfer_number' => $transferNumber,
                'transfer_date' => $transferDate,
                'status' => 'DRAFT',
                'driver_name' => $driverName,
                'vehicle_number' => $vehicleNumber,
                'total_items' => $totalItems,
                'total_sent_quantity' => $totalSentBaseQty,
                'total_received_quantity' => 0.0000,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($preparedItems as $pItem) {
                $pItem['stock_transfer_id'] = $transfer->id;
                $transferItem = StockTransferItem::create($pItem);

                // Rekam alokasi kebutuhan stok cabang tujuan (Status: ACTIVE)
                $req = $requirements[$pItem['product_id']];
                $this->stockAllocationService->allocateStock(
                    requirement: $req,
                    allocationType: 'STOCK_TRANSFER',
                    referenceId: $transfer->id,
                    referenceItemId: $transferItem->id,
                    baseQuantity: $pItem['base_quantity_sent'],
                    referenceNumber: $transfer->transfer_number,
                    userId: $userId,
                    idempotencyKey: 'TRF-ALLOC-'.$transferItem->id
                );
            }

            return $transfer->load('items.product');
        });
    }

    /**
     * Dispatch / Kirim Barang Transfer (Stok Toko Asal Berkurang -> Status IN_TRANSIT).
     * Memindahkan alokasi stok di toko tujuan dari ACTIVE menjadi COMMITTED (Pasokan Masuk).
     */
    public function dispatchTransfer(StockTransfer $transfer, User $dispatcher): StockTransfer
    {
        if ($transfer->status !== 'DRAFT') {
            throw new \DomainException("Hanya transfer berstatus DRAFT yang dapat dikirim. Status saat ini: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $dispatcher) {
            $sourceStore = $transfer->sourceStore;

            foreach ($transfer->items as $item) {
                // Kurangi stok toko asal dengan penjaga Zero Negative Inventory
                $this->inventoryService->recordTransferDispatch(
                    product: $item->product,
                    sourceStore: $sourceStore,
                    quantity: (float) $item->base_quantity_sent,
                    referenceNumber: $transfer->transfer_number,
                    notes: "Pengiriman Transfer ke {$transfer->destinationStore->name}",
                    userId: $dispatcher->id
                );
            }

            $transfer->update([
                'status' => 'IN_TRANSIT',
                'dispatched_at' => now(),
                'dispatched_by' => $dispatcher->id,
            ]);

            // Komitmenkan alokasi stok transfer (ACTIVE -> COMMITTED)
            $allocations = StockRequirementAllocation::where('reference_id', $transfer->id)
                ->where('allocation_type', 'STOCK_TRANSFER')
                ->where('status', 'ACTIVE')
                ->get();

            foreach ($allocations as $allocation) {
                $this->stockAllocationService->commitAllocation($allocation);
            }

            return $transfer->fresh(['items.product', 'sourceStore', 'destinationStore']);
        });
    }

    /**
     * Konfirmasi Penerimaan Barang di Toko Tujuan (Status RECEIVED / COMPLETED).
     * Merealisasikan alokasi kebutuhan stok menjadi FULFILLED.
     */
    public function receiveTransfer(StockTransfer $transfer, array $receivedQuantities, User $receiver): StockTransfer
    {
        if ($transfer->status !== 'IN_TRANSIT') {
            throw new \DomainException("Penerimaan transfer hanya dapat dilakukan untuk barang yang berstatus IN_TRANSIT. Status saat ini: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $receivedQuantities, $receiver) {
            $destinationStore = $transfer->destinationStore;
            $totalReceivedBaseQty = 0.0;

            foreach ($transfer->items as $item) {
                $qtyReceived = isset($receivedQuantities[$item->id])
                    ? (float) $receivedQuantities[$item->id]
                    : (float) $item->quantity_sent;

                $conversion = (float) $item->conversion_factor > 0 ? (float) $item->conversion_factor : 1.0;
                $baseQtyReceived = round($qtyReceived * $conversion, 4);
                $totalReceivedBaseQty += $baseQtyReceived;

                // Tambah stok fisik di toko tujuan
                $this->inventoryService->recordTransferReceipt(
                    product: $item->product,
                    destinationStore: $destinationStore,
                    quantity: $baseQtyReceived,
                    unitCost: (float) $item->unit_cost,
                    referenceNumber: $transfer->transfer_number,
                    notes: "Penerimaan Transfer dari {$transfer->sourceStore->name}",
                    userId: $receiver->id
                );

                $item->update([
                    'quantity_received' => $qtyReceived,
                    'base_quantity_received' => $baseQtyReceived,
                ]);

                // Realisasi parsial/penuh pada alokasi kebutuhan stok cabang tujuan
                $allocation = StockRequirementAllocation::where('reference_id', $transfer->id)
                    ->where('reference_item_id', $item->id)
                    ->where('allocation_type', 'STOCK_TRANSFER')
                    ->whereIn('status', ['COMMITTED', 'ACTIVE'])
                    ->first();

                if ($allocation) {
                    $this->stockAllocationService->recordPartialFulfillment($allocation, $baseQtyReceived);
                }
            }

            $transfer->update([
                'status' => 'RECEIVED',
                'total_received_quantity' => $totalReceivedBaseQty,
                'received_at' => now(),
                'received_by' => $receiver->id,
            ]);

            // Realisasi status pada Permintaan Cabang jika transfer ini berasal dari TransferRequest
            $linkedRequest = TransferRequest::where('stock_transfer_id', $transfer->id)->first();
            if ($linkedRequest) {
                $linkedRequest->update([
                    'status' => 'FULFILLED',
                    'total_fulfilled_quantity' => $totalReceivedBaseQty,
                ]);
            }

            return $transfer->fresh(['items.product', 'sourceStore', 'destinationStore']);
        });
    }

    /**
     * Batalkan Transfer Antar Cabang (Hanya DRAFT) dan lepaskan alokasi stok terkait.
     */
    public function cancelTransfer(StockTransfer $transfer, User $user, ?string $reason = null): StockTransfer
    {
        if ($transfer->status !== 'DRAFT') {
            throw new \DomainException("Hanya transfer berstatus DRAFT yang dapat dibatalkan. Status saat ini: {$transfer->status}");
        }

        return DB::transaction(function () use ($transfer, $user, $reason) {
            $transfer->update([
                'status' => 'CANCELLED',
                'notes' => $reason ? ($transfer->notes . ' | Dibatalkan: ' . $reason) : $transfer->notes,
            ]);

            $allocations = StockRequirementAllocation::where('reference_id', $transfer->id)
                ->where('allocation_type', 'STOCK_TRANSFER')
                ->whereIn('status', ['ACTIVE', 'COMMITTED'])
                ->get();

            foreach ($allocations as $alloc) {
                $this->stockAllocationService->releaseAllocation($alloc, null, $reason ?? "Pembatalan Transfer {$transfer->transfer_number}");
            }

            return $transfer->fresh();
        });
    }

    // =========================================================================
    // 2. ALUR BLIND STOCK OPNAME & REKONSILIASI SELISIH FISIK (M04)
    // =========================================================================

    /**
     * Inisiasi Sesi Stock Opname Baru (Membekukan angka sistem).
     */
    public function startOpname(
        Company $company,
        Store $store,
        string $opnameDate,
        ?string $notes = null,
        ?string $userId = null,
        ?string $rackLocation = null
    ): StockOpname {
        return DB::transaction(function () use ($company, $store, $opnameDate, $notes, $userId, $rackLocation) {
            // Hasilkan Nomor Dokumen Stock Opname Otomatis via AutoNumberService
            $opnameNumber = app(AutoNumberService::class)->generateNextNumber('OPN', $store->code, $company);

            $finalNotes = $notes;
            if ($rackLocation && $rackLocation !== 'ALL') {
                $finalNotes = trim(($notes ? $notes . ' ' : '') . "[Siklus Rak: {$rackLocation}]");
            }

            $opname = StockOpname::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'opname_number' => $opnameNumber,
                'opname_date' => $opnameDate,
                'status' => 'IN_PROGRESS',
                'is_blind' => true,
                'notes' => $finalNotes,
                'created_by' => $userId,
            ]);

            // Bekukan stok sistem produk di toko (seluruh produk atau difokuskan pada rak tertentu)
            $stocksQuery = ProductStock::where('store_id', $store->id)->with('product');
            if ($rackLocation && $rackLocation !== 'ALL') {
                $stocksQuery->whereHas('product', function ($pq) use ($rackLocation) {
                    $pq->where('rack_location', $rackLocation);
                });
            }
            $stocks = $stocksQuery->get();
            $totalSystemQty = 0.0;

            foreach ($stocks as $st) {
                if (! $st->product || ! $st->product->is_active || ! $st->product->tracksInventory()) {
                    continue;
                }

                $sysQty = (float) $st->physical_quantity;
                $totalSystemQty += $sysQty;

                StockOpnameItem::create([
                    'stock_opname_id' => $opname->id,
                    'product_id' => $st->product_id,
                    'system_quantity' => $sysQty,
                    'actual_quantity' => 0.0000,
                    'discrepancy_quantity' => -$sysQty, // Default sebelum hitung fisik
                    'unit_cost' => (float) $st->product->cost_price,
                    'discrepancy_value' => -$sysQty * (float) $st->product->cost_price,
                ]);
            }

            $opname->update(['total_system_quantity' => $totalSystemQty]);

            return $opname->load('items.product');
        });
    }

    /**
     * Petugas Toko Menginput Hitungan Fisik Buta (Blind Count Submission).
     * Angka ekspektasi sistem dirahasiakan / tidak boleh dilihat oleh staf toko.
     */
    public function submitBlindCounts(StockOpname $opname, array $actualCounts, User $counter): StockOpname
    {
        if ($opname->status !== 'IN_PROGRESS') {
            throw new \DomainException("Penginputan fisik hanya dapat dilakukan saat opname berstatus IN_PROGRESS. Status saat ini: {$opname->status}");
        }

        return DB::transaction(function () use ($opname, $actualCounts) {
            $totalActualQty = 0.0;
            $totalDiscrepancyQty = 0.0;
            $totalDiscrepancyVal = 0.0;

            foreach ($opname->items as $item) {
                $productId = $item->product_id;
                $actualQty = isset($actualCounts[$productId]) ? (float) $actualCounts[$productId] : (float) $item->actual_quantity;

                $sysQty = (float) $item->system_quantity;
                $discrepancyQty = $actualQty - $sysQty;
                $unitCost = (float) $item->unit_cost;
                $discrepancyVal = round($discrepancyQty * $unitCost, 2);

                $totalActualQty += $actualQty;
                $totalDiscrepancyQty += $discrepancyQty;
                $totalDiscrepancyVal += $discrepancyVal;

                $item->update([
                    'actual_quantity' => $actualQty,
                    'discrepancy_quantity' => $discrepancyQty,
                    'discrepancy_value' => $discrepancyVal,
                ]);
            }

            $opname->update([
                'status' => 'PENDING_APPROVAL',
                'total_actual_quantity' => $totalActualQty,
                'total_discrepancy_quantity' => $totalDiscrepancyQty,
                'total_discrepancy_value' => $totalDiscrepancyVal,
            ]);

            return $opname->fresh('items.product');
        });
    }

    /**
     * Update hasil hitung fisik satu produk secara atomik (untuk mobile PWA).
     */
    public function updateOpnameItemCount(StockOpname $opname, string $productId, float $actualQuantity, ?string $notes = null): StockOpnameItem
    {
        if ($opname->status !== 'IN_PROGRESS') {
            throw new \DomainException("Penginputan fisik hanya dapat dilakukan saat opname berstatus IN_PROGRESS. Status saat ini: {$opname->status}");
        }

        $item = StockOpnameItem::where('stock_opname_id', $opname->id)
            ->where('product_id', $productId)
            ->firstOrFail();

        $item->update([
            'actual_quantity' => max(0.0, $actualQuantity),
            'notes' => $notes ?? $item->notes,
        ]);

        return $item;
    }

    /**
     * Approval & Rekonsiliasi Hasil Opname oleh Manager / Owner.
     * Mengkoreksi stok sistem dan memposting jurnal selisih stok seimbang.
     */
    public function approveOpnameReconciliation(
        StockOpname $opname,
        User $manager,
        ?string $approvalNotes = null
    ): StockOpname {
        if (! in_array($manager->role, ['owner', 'manager', 'store_head'], true)) {
            throw new AuthorizationException('Hanya Manajer, Kepala Toko, atau Owner yang memiliki wewenang menyetujui rekonsiliasi stock opname.');
        }

        if ($opname->status !== 'PENDING_APPROVAL') {
            throw new \DomainException("Hanya opname berstatus PENDING_APPROVAL yang dapat disetujui. Status saat ini: {$opname->status}");
        }

        return DB::transaction(function () use ($opname, $manager, $approvalNotes) {
            $store = $opname->store;

            foreach ($opname->items as $item) {
                // Koreksi stok fisik toko
                $this->inventoryService->recordStockOpnameAdjustment(
                    product: $item->product,
                    store: $store,
                    actualQuantity: (float) $item->actual_quantity,
                    referenceNumber: $opname->opname_number,
                    notes: "Rekonsiliasi Stock Opname {$opname->opname_number}",
                    userId: $manager->id
                );
            }

            // Catat Jurnal Akuntansi Double Entry Seimbang
            $this->accountingService->recordOpnameAdjustmentJournal($opname);

            $opname->update([
                'status' => 'COMPLETED',
                'approved_by' => $manager->id,
                'approved_at' => now(),
                'approval_notes' => $approvalNotes,
            ]);

            AuditLog::record(
                module: 'INVENTORY',
                action: 'STOCK_OPNAME_APPROVED',
                description: "Persetujuan rekonsiliasi stock opname {$opname->opname_number} oleh {$manager->name} di toko {$store->name} (Catatan: " . ($approvalNotes ?: '-') . ")",
                user: $manager
            );

            return $opname->fresh(['items.product', 'approver']);
        });
    }

    // =========================================================================
    // 3. ALUR PERMINTAAN TRANSFER CABANG & SMART RESTOCK (TRANSFER REQUEST)
    // =========================================================================

    /**
     * Rekomendasi Smart Restock Cabang (Mencegah Kekurangan & Mencegah Overstock).
     * Menganalisis ADS (Average Daily Sales), stok tersedia, stok in-transit, dan kapasitas max.
     */
    public function getSmartRestockBranchRecommendations(Store $requestingStore, ?Store $sourceStore = null, int $analysisDays = 30): Collection
    {
        $companyId = $requestingStore->company_id;

        // 1. Ambil seluruh produk aktif dan stoknya di toko cabang peminta & gudang pengirim
        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereIn('type', [Product::TYPE_BARANG, Product::TYPE_RAKITAN_PROSES])
            ->with([
                'category',
                'units' => fn ($u) => $u->where('is_active', true)->orderByDesc('conversion_factor'),
                'stocks' => fn ($s) => $s->whereIn('store_id', array_filter([$requestingStore->id, $sourceStore?->id])),
            ])
            ->get();

        // 2. Ambil data penjualan cabang dalam N hari terakhir (POS + Sales Order)
        $sinceDate = now()->subDays($analysisDays)->startOfDay();

        $posSales = DB::table('pos_transaction_items')
            ->join('pos_transactions', 'pos_transaction_items.pos_transaction_id', '=', 'pos_transactions.id')
            ->where('pos_transactions.store_id', $requestingStore->id)
            ->where('pos_transactions.created_at', '>=', $sinceDate)
            ->where('pos_transactions.status', 'COMPLETED')
            ->select('pos_transaction_items.product_id', DB::raw('SUM(pos_transaction_items.quantity) as total_sold'))
            ->groupBy('pos_transaction_items.product_id')
            ->pluck('total_sold', 'product_id');

        $soSales = DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->where('sales_orders.store_id', $requestingStore->id)
            ->where('sales_orders.created_at', '>=', $sinceDate)
            ->whereIn('sales_orders.status', ['CONFIRMED', 'DELIVERED', 'COMPLETED'])
            ->select('sales_order_items.product_id', DB::raw('SUM(sales_order_items.quantity) as total_sold'))
            ->groupBy('sales_order_items.product_id')
            ->pluck('total_sold', 'product_id');

        // 3. Ambil data barang yang sedang dalam pengiriman ke cabang ini (IN_TRANSIT)
        $inTransitStock = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfer_items.stock_transfer_id', '=', 'stock_transfers.id')
            ->where('stock_transfers.destination_store_id', $requestingStore->id)
            ->where('stock_transfers.status', 'IN_TRANSIT')
            ->select('stock_transfer_items.product_id', DB::raw('SUM(stock_transfer_items.base_quantity_sent) as in_transit_qty'))
            ->groupBy('stock_transfer_items.product_id')
            ->pluck('in_transit_qty', 'product_id');

        $recommendations = collect();

        foreach ($products as $p) {
            $branchStock = $p->stocks->firstWhere('store_id', $requestingStore->id);
            $sourceStock = $sourceStore ? $p->stocks->firstWhere('store_id', $sourceStore->id) : null;

            $phys = (float) ($branchStock?->physical_quantity ?? 0);
            $res = (float) ($branchStock?->reserved_quantity ?? 0);
            $quar = (float) ($branchStock?->quarantined_quantity ?? 0);
            $avail = max(0.0, $phys - $res - $quar);

            $sourceAvail = 0.0;
            if ($sourceStock) {
                $sPhys = (float) $sourceStock->physical_quantity;
                $sRes = (float) $sourceStock->reserved_quantity;
                $sQuar = (float) $sourceStock->quarantined_quantity;
                $sourceAvail = max(0.0, $sPhys - $sRes - $sQuar);
            }

            // Total terjual & ADS
            $soldQty = (float) (($posSales->get($p->id) ?? 0) + ($soSales->get($p->id) ?? 0));
            $ads = round($soldQty / max(1, $analysisDays), 2);

            // In-transit
            $inTransit = (float) ($inTransitStock->get($p->id) ?? 0);

            // Ambang Batas Min & Max
            $minAlert = (float) $p->min_stock_alert > 0 ? (float) $p->min_stock_alert : 5.0;
            $leadTimeDays = 2; // lead time rata-rata pengiriman dari pusat (2 hari)
            $safetyDays = 3;   // buffer cadangan keamanan 3 hari
            $dynamicRop = max($minAlert, ceil($ads * ($leadTimeDays + $safetyDays)));

            $maxStock = (float) $p->max_stock > 0
                ? (float) $p->max_stock
                : max($dynamicRop * 2.5, ceil($ads * 14), 20.0);

            // Matriks Kesehatan Stok (CRITICAL, REORDER, OVERSTOCK, OPTIMAL)
            $status = 'OPTIMAL';
            $recommendedBaseQty = 0.0;

            if ($avail <= 0) {
                $status = 'CRITICAL';
                $recommendedBaseQty = max(1.0, ceil($maxStock - $inTransit));
            } elseif (($avail + $inTransit) <= $dynamicRop) {
                $status = 'REORDER';
                $recommendedBaseQty = max(1.0, ceil($maxStock - ($avail + $inTransit)));
            } elseif ($avail > $maxStock && $maxStock > 0) {
                $status = 'OVERSTOCK';
                $recommendedBaseQty = 0.0;
            } else {
                $status = 'OPTIMAL';
                $recommendedBaseQty = 0.0;
            }

            // Cari satuan kemasan yang paling efisien
            $units = $p->units;
            $bestUnit = $units->first(function ($u) use ($recommendedBaseQty) {
                return (float) $u->conversion_factor > 1.0 && $recommendedBaseQty >= (float) $u->conversion_factor;
            }) ?? $units->firstWhere('is_base_unit', true) ?? $units->first();

            $conv = (float) ($bestUnit?->conversion_factor ?? 1.0);
            if ($conv <= 0) $conv = 1.0;

            $recommendedOrderQty = ceil($recommendedBaseQty / $conv);

            $recommendations->push([
                'product_id' => $p->id,
                'product_name' => $p->name,
                'sku' => $p->sku,
                'category_name' => $p->category?->name ?? 'Umum',
                'rack_location' => $p->rack_location ?: '-',
                'barcode' => $p->barcodes?->first()?->barcode ?? $p->sku,
                'current_stock' => $phys,
                'available_stock' => $avail,
                'in_transit_stock' => $inTransit,
                'ads' => $ads,
                'sold_last_days' => $soldQty,
                'min_stock_alert' => $minAlert,
                'rop' => $dynamicRop,
                'max_stock' => $maxStock,
                'status' => $status,
                'recommended_base_qty' => $recommendedBaseQty,
                'recommended_quantity' => $recommendedOrderQty,
                'unit_id' => $bestUnit?->id,
                'unit_code' => $bestUnit?->unit_code ?? 'PCS',
                'conversion_factor' => $conv,
                'source_available_stock' => $sourceAvail,
                'source_supply_status' => $sourceStore ? ($sourceAvail >= $recommendedBaseQty ? 'READY' : ($sourceAvail > 0 ? 'PARTIAL' : 'EMPTY')) : 'UNKNOWN',
                'units' => $units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_code' => $u->unit_code,
                    'conversion_factor' => (float) $u->conversion_factor,
                ]),
            ]);
        }

        // Urutkan: CRITICAL -> REORDER -> OVERSTOCK -> OPTIMAL
        $statusOrder = ['CRITICAL' => 1, 'REORDER' => 2, 'OVERSTOCK' => 3, 'OPTIMAL' => 4];
        return $recommendations->sortBy(fn ($r) => $statusOrder[$r['status']] ?? 99)->values();
    }

    /**
     * Buat Draft Permintaan Transfer Cabang Baru (TR-YYYYMMDD-XXXX).
     */
    public function createTransferRequest(
        Company $company,
        Store $requestingStore,
        Store $destinationStore,
        array $items,
        string $requestDate,
        ?string $notes = null,
        ?User $user = null
    ): TransferRequest {
        if ($requestingStore->id === $destinationStore->id) {
            throw new \InvalidArgumentException('Toko cabang peminta dan toko tujuan tidak boleh sama.');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('Daftar item barang permintaan transfer tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $company,
            $requestingStore,
            $destinationStore,
            $items,
            $requestDate,
            $notes,
            $user
        ) {
            $dateStr = date('Ymd', strtotime($requestDate));
            $count = TransferRequest::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $requestNumber = 'TR-' . $dateStr . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $transferRequest = TransferRequest::create([
                'company_id' => $company->id,
                'request_number' => $requestNumber,
                'requesting_store_id' => $requestingStore->id,
                'destination_store_id' => $destinationStore->id,
                'request_date' => $requestDate,
                'status' => 'REQUESTED',
                'notes' => $notes,
                'created_by' => $user?->id ?? Auth::id(),
                'total_items' => count($items),
                'total_requested_quantity' => 0,
            ]);

            $totalRequestedBaseQty = 0;

            foreach ($items as $item) {
                $unit = ProductUnit::findOrFail($item['product_unit_id']);
                $conv = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round((float) $item['requested_quantity'] * $conv, 4);
                $totalRequestedBaseQty += $baseQty;

                TransferRequestItem::create([
                    'transfer_request_id' => $transferRequest->id,
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $unit->id,
                    'requested_quantity' => (float) $item['requested_quantity'],
                    'approved_quantity' => 0,
                    'fulfilled_quantity' => 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $transferRequest->update([
                'total_requested_quantity' => $totalRequestedBaseQty,
            ]);

            return $transferRequest->fresh(['items.product', 'requestingStore', 'destinationStore']);
        });
    }

    /**
     * Setujui Permintaan Transfer Cabang dan (opsional) buat Surat Jalan Transfer (StockTransfer).
     */
    public function approveTransferRequest(
        TransferRequest $transferRequest,
        array $approvedItems,
        User $approverUser,
        bool $generateStockTransfer = true
    ): TransferRequest {
        if (!in_array($transferRequest->status, ['REQUESTED', 'PARTIAL'])) {
            throw new \DomainException("Hanya permintaan dengan status REQUESTED atau PARTIAL yang dapat disetujui. Status saat ini: {$transferRequest->status}");
        }

        return DB::transaction(function () use (
            $transferRequest,
            $approvedItems,
            $approverUser,
            $generateStockTransfer
        ) {
            $totalApprovedBaseQty = 0;
            $hasPartial = false;
            $stockTransferItems = [];

            foreach ($approvedItems as $app) {
                $reqItem = TransferRequestItem::where('transfer_request_id', $transferRequest->id)
                    ->where('id', $app['item_id'])
                    ->firstOrFail();

                $approvedQty = max(0.0, (float) ($app['approved_quantity'] ?? 0));
                $unit = ProductUnit::findOrFail($reqItem->product_unit_id);
                $conv = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round($approvedQty * $conv, 4);

                $totalApprovedBaseQty += $baseQty;

                if ($approvedQty < (float) $reqItem->requested_quantity) {
                    $hasPartial = true;
                }

                $reqItem->update([
                    'approved_quantity' => $approvedQty,
                ]);

                if ($approvedQty > 0) {
                    $stockTransferItems[] = [
                        'product_id' => $reqItem->product_id,
                        'product_unit_id' => $reqItem->product_unit_id,
                        'quantity' => $approvedQty,
                    ];
                }
            }

            $newStatus = $hasPartial ? 'PARTIAL' : 'APPROVED';

            $stockTransfer = null;
            if ($generateStockTransfer && !empty($stockTransferItems)) {
                $stockTransfer = $this->createTransfer(
                    company: $transferRequest->company,
                    sourceStore: $transferRequest->destinationStore,
                    destinationStore: $transferRequest->requestingStore,
                    items: $stockTransferItems,
                    transferDate: date('Y-m-d'),
                    notes: "Realisasi otomatis dari Permintaan {$transferRequest->request_number}",
                    userId: $approverUser->id
                );
            }

            $transferRequest->update([
                'status' => $newStatus,
                'approved_by' => $approverUser->id,
                'approved_at' => now(),
                'total_approved_quantity' => $totalApprovedBaseQty,
                'stock_transfer_id' => $stockTransfer?->id,
            ]);

            return $transferRequest->fresh(['items.product', 'stockTransfer']);
        });
    }

    /**
     * Tolak Permintaan Transfer Cabang.
     */
    public function rejectTransferRequest(
        TransferRequest $transferRequest,
        string $reason,
        User $approverUser
    ): TransferRequest {
        if ($transferRequest->status !== 'REQUESTED') {
            throw new \DomainException("Hanya permintaan dengan status REQUESTED yang dapat ditolak. Status saat ini: {$transferRequest->status}");
        }

        return DB::transaction(function () use ($transferRequest, $reason, $approverUser) {
            $transferRequest->update([
                'status' => 'REJECTED',
                'rejection_reason' => $reason,
                'approved_by' => $approverUser->id,
                'approved_at' => now(),
            ]);

            return $transferRequest->fresh();
        });
    }
}
