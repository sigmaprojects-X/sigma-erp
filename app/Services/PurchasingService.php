<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\StockRequirement;
use App\Models\StockRequirementAllocation;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchasingService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService,
        protected StockAllocationService $stockAllocationService
    ) {}

    /**
     * Rekomendasi Restock Cerdas (Smart Replenishment - AGENTS.md Section 4C).
     * Menganalisis stok produk yang telah menyentuh atau berada di bawah min_stock_alert,
     * serta memperhitungkan reserved_quantity dan lead time order.
     */
    public function getRestockRecommendations(Store $store, ?string $supplierId = null): Collection
    {
        $stocks = ProductStock::where('store_id', $store->id)
            ->with(['product.units' => fn ($q) => $q->where('is_active', true)->orderByDesc('conversion_factor')])
            ->get();

        $recommendations = collect();

        foreach ($stocks as $stock) {
            $product = $stock->product;
            if (! $product || ! $product->is_active) {
                continue;
            }

            $minAlert = (float) $product->min_stock_alert;
            $physicalQty = (float) $stock->physical_quantity;
            $reservedQty = (float) $stock->reserved_quantity;
            $quarantinedQty = (float) $stock->quarantined_quantity;
            $availableStock = max(0, $physicalQty - $reservedQty - $quarantinedQty);

            // Trigger restock jika stok fisik atau stok tersedia <= min_stock_alert
            if ($availableStock <= $minAlert || $physicalQty <= $minAlert) {
                // Target Restock: 2x Minimum Alert atau minimal 10 unit dasar
                $targetStock = max($minAlert * 2, 10.0);
                $deficitBaseQty = max(1.0, $targetStock - $availableStock);

                // Cari pemasok utama dari histori pembelian terakhir
                $lastPurchaseItem = PurchaseItem::where('product_id', $product->id)
                    ->whereHas('purchase', fn ($q) => $q->where('status', 'RECEIVED'))
                    ->latest()
                    ->with('purchase.supplier')
                    ->first();

                $primarySupplier = $lastPurchaseItem?->purchase?->supplier;

                if ($supplierId && (! $primarySupplier || $primarySupplier->id !== $supplierId)) {
                    continue;
                }

                // Cari kemasan pemesanan yang paling efisien (misal DUS / SAK jika kuantitas besar)
                $units = $product->units;
                $recommendedUnit = $units->first(function ($u) use ($deficitBaseQty) {
                    return (float) $u->conversion_factor > 1.0 && $deficitBaseQty >= (float) $u->conversion_factor;
                }) ?? $units->firstWhere('is_base_unit', true) ?? $units->first();

                $conversion = (float) ($recommendedUnit?->conversion_factor ?? 1.0);
                if ($conversion <= 0) {
                    $conversion = 1.0;
                }

                $recommendedOrderQty = ceil($deficitBaseQty / $conversion);
                $estimatedUnitCost = (float) $product->cost_price * $conversion;
                $estimatedTotalCost = $recommendedOrderQty * $estimatedUnitCost;

                $recommendations->push([
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'category' => $product->category,
                    'current_stock' => $physicalQty,
                    'reserved_stock' => $reservedQty,
                    'available_stock' => $availableStock,
                    'min_stock_alert' => $minAlert,
                    'deficit_base_qty' => $deficitBaseQty,
                    'recommended_quantity' => $recommendedOrderQty,
                    'recommended_unit_id' => $recommendedUnit?->id,
                    'recommended_unit_code' => $recommendedUnit?->unit_code ?? 'PCS',
                    'conversion_factor' => $conversion,
                    'estimated_unit_cost' => $estimatedUnitCost,
                    'estimated_total_cost' => $estimatedTotalCost,
                    'primary_supplier_id' => $primarySupplier?->id,
                    'primary_supplier_name' => $primarySupplier?->name ?? 'Belum Ditentukan',
                ]);
            }
        }

        return $recommendations->sortByDesc(fn ($r) => $r['min_stock_alert'] - $r['available_stock']);
    }

    /**
     * Buat Draft Purchase Order (PO Baru) dan Alokasikan Kebutuhan Stok (Status DRAFT).
     */
    public function createPurchaseOrder(
        Company $company,
        Store $store,
        Supplier $supplier,
        array $items,
        string $orderDate,
        ?string $expectedDeliveryDate = null,
        ?string $notes = null,
        ?string $userId = null
    ): PurchaseOrder {
        if (empty($items)) {
            throw new \InvalidArgumentException('Daftar barang pada Purchase Order tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $company,
            $store,
            $supplier,
            $items,
            $orderDate,
            $expectedDeliveryDate,
            $notes,
            $userId
        ) {
            $dateStr = date('Ymd', strtotime($orderDate));
            $count = PurchaseOrder::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $poNumber = 'PO-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            // 1. Validasi Transfer-First & Hitung Kebutuhan Stok per Produk
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

                // Aturan Transfer-First: Cek apakah ada transfer masuk aktif ke toko ini
                if ($this->stockAllocationService->isTransferActive($store, $product)) {
                    throw ValidationException::withMessages([
                        'allocation' => "Kebijakan Transfer-First: Pengadaan supplier untuk produk [{$product->name}] ditahan karena terdapat transfer antar-cabang yang masih aktif.",
                    ]);
                }

                // Dapatkan / Buat Header Kebutuhan Aktif yang Memadai
                $existingReq = StockRequirement::active()
                    ->where('company_id', $company->id)
                    ->where('store_id', $store->id)
                    ->where('product_id', $product->id)
                    ->first();

                $metrics = $this->stockAllocationService->calculateDeficit($store, $product, 0);
                $minTarget = (float) $metrics['available_stock'] + (float) $metrics['committed_inbound'] + (float) $metrics['pending_locks'] + $totalBaseQty;
                $currentTarget = $existingReq ? (float) $existingReq->target_stock : (float) $product->min_stock_alert * 2;
                $targetStock = max($currentTarget, $minTarget);

                $requirements[$productId] = $this->stockAllocationService->getOrCreateActiveRequirement(
                    $store,
                    $product,
                    $targetStock,
                    $userId
                );
            }

            $subtotal = 0.0;
            $preparedItems = [];

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $unit = ProductUnit::findOrFail($item['product_unit_id']);

                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineSubtotal = round($qty * $price, 2);

                $subtotal += $lineSubtotal;
                $conversion = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round($qty * $conversion, 4);

                $preparedItems[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'unit_code' => $unit->unit_code,
                    'conversion_factor' => $conversion,
                    'quantity_ordered' => $qty,
                    'quantity_received' => 0.00,
                    'unit_price' => $price,
                    'subtotal' => $lineSubtotal,
                    'base_quantity' => $baseQty,
                ];
            }

            $taxAmount = 0.0;
            $grandTotal = $subtotal + $taxAmount;

            $po = PurchaseOrder::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'po_number' => $poNumber,
                'order_date' => $orderDate,
                'expected_delivery_date' => $expectedDeliveryDate,
                'status' => 'DRAFT',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($preparedItems as $pItem) {
                $product = $pItem['product'];
                $baseQty = $pItem['base_quantity'];
                unset($pItem['product'], $pItem['base_quantity']);

                $pItem['purchase_order_id'] = $po->id;
                $poItem = PurchaseOrderItem::create($pItem);

                // Rekam baris alokasi kebutuhan stok (Status: ACTIVE)
                $req = $requirements[$product->id];
                $this->stockAllocationService->allocateStock(
                    requirement: $req,
                    allocationType: 'PURCHASE_ORDER',
                    referenceId: $po->id,
                    referenceItemId: $poItem->id,
                    baseQuantity: $baseQty,
                    referenceNumber: $po->po_number,
                    userId: $userId,
                    idempotencyKey: 'PO-ALLOC-'.$poItem->id
                );
            }

            return $po->load('items.product');
        });
    }

    /**
     * Otorisasi / Approval PO menjadi status ORDERED (Siap dikirim ke pemasok).
     * Memindahkan alokasi stok dari ACTIVE (Draft) menjadi COMMITTED (Pasokan Masuk).
     */
    public function approvePurchaseOrder(PurchaseOrder $po, User $approver): PurchaseOrder
    {
        if ($po->status !== 'DRAFT') {
            throw new \DomainException("Hanya Purchase Order berstatus DRAFT yang dapat disetujui. Status saat ini: {$po->status}");
        }

        return DB::transaction(function () use ($po, $approver) {
            $po->update([
                'status' => 'ORDERED',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            // Komitmenkan alokasi stok terkait PO ini (ACTIVE -> COMMITTED)
            $allocations = StockRequirementAllocation::where('reference_id', $po->id)
                ->where('allocation_type', 'PURCHASE_ORDER')
                ->where('status', 'ACTIVE')
                ->get();

            foreach ($allocations as $allocation) {
                $this->stockAllocationService->commitAllocation($allocation);
            }

            return $po->fresh();
        });
    }

    /**
     * Penerimaan Barang & Three-Way Matching (PO vs Faktur Supplier vs Fisik yang Diterima).
     * Merekam Goods Receipt di tabel purchases, menambah stok, moving average HPP, dan jurnal akuntansi.
     */
    public function receiveGoodsFromPo(
        PurchaseOrder $po,
        array $receivedItems,
        string $invoiceNumber,
        string $paymentType,
        ?string $dueDate = null,
        ?string $bankAccountId = null,
        ?string $userId = null
    ): Purchase {
        if (! in_array($po->status, ['ORDERED', 'PARTIALLY_RECEIVED'], true)) {
            throw new \DomainException("Penerimaan barang hanya dapat dilakukan untuk PO yang berstatus ORDERED atau PARTIALLY_RECEIVED. Status saat ini: {$po->status}");
        }

        return DB::transaction(function () use (
            $po,
            $receivedItems,
            $invoiceNumber,
            $paymentType,
            $dueDate,
            $bankAccountId,
            $userId
        ) {
            $company = $po->company;
            $store = $po->store;
            $supplier = $po->supplier;

            $dateStr = date('Ymd');
            $count = Purchase::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $refNumber = 'FB-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0.0;
            $itemsToProcess = [];

            foreach ($receivedItems as $recItem) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $po->items()->where('id', $recItem['purchase_order_item_id'])->firstOrFail();

                $qtyReceivedNow = (float) $recItem['quantity'];
                if ($qtyReceivedNow <= 0) {
                    continue;
                }

                $remainingOrdered = (float) $poItem->quantity_ordered - (float) $poItem->quantity_received;
                if ($qtyReceivedNow > $remainingOrdered) {
                    throw new \InvalidArgumentException("Kuantitas barang yang diterima ({$qtyReceivedNow}) melebihi sisa pesanan PO ({$remainingOrdered}) untuk item [{$poItem->product->name}].");
                }

                $price = isset($recItem['unit_price']) ? (float) $recItem['unit_price'] : (float) $poItem->unit_price;
                $lineSubtotal = round($qtyReceivedNow * $price, 2);
                $subtotal += $lineSubtotal;

                $conversion = (float) $poItem->conversion_factor > 0 ? (float) $poItem->conversion_factor : 1.0;
                $baseQty = round($qtyReceivedNow * $conversion, 4);

                $itemsToProcess[] = [
                    'po_item' => $poItem,
                    'qty' => $qtyReceivedNow,
                    'base_qty' => $baseQty,
                    'unit_price' => $price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            if (empty($itemsToProcess)) {
                throw new \InvalidArgumentException('Tidak ada kuantitas barang yang valid untuk diterima.');
            }

            $taxAmount = 0.0;
            $grandTotal = $subtotal + $taxAmount;

            // Buat Faktur Penerimaan Barang (Goods Receipt)
            $purchase = Purchase::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'purchase_order_id' => $po->id,
                'supplier_id' => $supplier->id,
                'invoice_number' => $invoiceNumber,
                'reference_number' => $refNumber,
                'purchase_date' => now()->toDateString(),
                'due_date' => $paymentType === 'TEMPO' ? ($dueDate ?: now()->addDays(30)->toDateString()) : null,
                'payment_type' => $paymentType,
                'bank_account_id' => $bankAccountId,
                'status' => 'RECEIVED',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paymentType === 'TEMPO' ? 0.00 : $grandTotal,
                'notes' => "Penerimaan Barang Three-Way Matching dari Purchase Order No: {$po->po_number}",
                'created_by' => $userId,
            ]);

            // Mutasi Stok & Hitung Moving Average HPP
            foreach ($itemsToProcess as $proc) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $proc['po_item'];
                $product = $poItem->product;
                $conversion = (float) $poItem->conversion_factor > 0 ? (float) $poItem->conversion_factor : 1.0;
                $baseUnitPrice = round($proc['unit_price'] / $conversion, 4);

                $stockMutation = $this->inventoryService->recordGoodsReceipt(
                    product: $product,
                    store: $store,
                    quantity: $proc['base_qty'],
                    purchaseCostPerUnit: $baseUnitPrice,
                    referenceNumber: $refNumber,
                    notes: "Penerimaan PO {$po->po_number} Faktur {$invoiceNumber}",
                    userId: $userId
                );

                $newMovingAvgCost = (float) $stockMutation->unit_cost;

                // Simpan PurchaseItem
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'product_unit_id' => $poItem->product_unit_id,
                    'unit_code' => $poItem->unit_code,
                    'conversion_factor' => $conversion,
                    'quantity' => $proc['qty'],
                    'base_quantity' => $proc['base_qty'],
                    'unit_price' => $proc['unit_price'],
                    'base_unit_price' => $baseUnitPrice,
                    'discount_percent' => 0.00,
                    'subtotal' => $proc['subtotal'],
                    'old_cost_price' => (float) $stockMutation->qty_before > 0 ? (float) $product->cost_price : $baseUnitPrice,
                    'new_cost_price' => $newMovingAvgCost,
                    'is_price_increased' => false,
                    'is_selling_price_updated' => false,
                ]);

                // Update progres quantity_received di PurchaseOrderItem
                $poItem->increment('quantity_received', $proc['qty']);

                // Realisasi parsial/penuh pada alokasi kebutuhan stok
                $allocation = StockRequirementAllocation::where('reference_id', $po->id)
                    ->where('reference_item_id', $poItem->id)
                    ->where('allocation_type', 'PURCHASE_ORDER')
                    ->whereIn('status', ['COMMITTED', 'ACTIVE'])
                    ->first();

                if ($allocation) {
                    $this->stockAllocationService->recordPartialFulfillment($allocation, $proc['base_qty']);
                }
            }

            // Evaluasi Three-Way Matching Status PO
            $po->refresh();
            $allCompleted = true;
            foreach ($po->items as $item) {
                if ((float) $item->quantity_received < (float) $item->quantity_ordered) {
                    $allCompleted = false;
                    break;
                }
            }

            $po->update([
                'status' => $allCompleted ? 'COMPLETED' : 'PARTIALLY_RECEIVED',
            ]);

            // Jurnal Akuntansi Double Entry Seimbang
            $bankAcc = $bankAccountId ? BankAccount::find($bankAccountId) : null;
            $this->accountingService->recordGoodsReceiptJournal(
                company: $company,
                store: $store,
                referenceNumber: $refNumber,
                totalPurchasedValue: $grandTotal,
                paymentType: $paymentType,
                bankAccountCode: $bankAcc ? '1002-001' : null,
                taxAmount: $taxAmount
            );

            return $purchase->load('items.product');
        });
    }

    /**
     * Batalkan Purchase Order dan lepaskan alokasi stok terkait.
     */
    public function cancelPurchaseOrder(PurchaseOrder $po, User $user, ?string $reason = null): PurchaseOrder
    {
        if (! in_array($po->status, ['DRAFT', 'ORDERED'], true)) {
            throw new \DomainException("Hanya Purchase Order berstatus DRAFT atau ORDERED yang dapat dibatalkan. Status saat ini: {$po->status}");
        }

        return DB::transaction(function () use ($po, $user, $reason) {
            $po->update([
                'status' => 'CANCELLED',
                'notes' => $reason ? ($po->notes . ' | Dibatalkan: ' . $reason) : $po->notes,
            ]);

            $allocations = StockRequirementAllocation::where('reference_id', $po->id)
                ->where('allocation_type', 'PURCHASE_ORDER')
                ->whereIn('status', ['ACTIVE', 'COMMITTED'])
                ->get();

            foreach ($allocations as $alloc) {
                $this->stockAllocationService->releaseAllocation($alloc, null, $reason ?? "Pembatalan PO {$po->po_number}");
            }

            return $po->fresh();
        });
    }

    /**
     * Pelunasan Hutang Usaha Supplier (Accounts Payable Settlement - AGENTS.md Section 5).
     */
    public function recordSupplierPayment(
        Company $company,
        Store $store,
        Supplier $supplier,
        array $invoicesToPay,
        string $paymentMethod = 'TRANSFER',
        ?string $bankAccountId = null,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?string $userId = null
    ): SupplierPayment {
        if (empty($invoicesToPay)) {
            throw new \InvalidArgumentException('Daftar faktur hutang yang akan dibayar tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $company,
            $store,
            $supplier,
            $invoicesToPay,
            $paymentMethod,
            $bankAccountId,
            $referenceNumber,
            $notes,
            $userId
        ) {
            $dateStr = date('Ymd');
            $count = SupplierPayment::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $paymentNumber = 'PAY-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0.0;
            $preparedItems = [];

            foreach ($invoicesToPay as $inv) {
                /** @var Purchase $purchase */
                $purchase = Purchase::where('id', $inv['purchase_id'])
                    ->where('supplier_id', $supplier->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $allocatedReturns = (float) $purchase->debtAllocations()
                    ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'COMPLETED'))
                    ->sum('allocated_amount');

                $directUnallocatedReturns = (float) $purchase->returns()
                    ->where('status', 'COMPLETED')
                    ->where('refund_type', 'DEBT_REDUCTION')
                    ->whereDoesntHave('debtAllocations')
                    ->sum('grand_total');

                $alreadyReturnedDebt = $allocatedReturns + $directUnallocatedReturns;

                $unpaidDebt = max(0.0, (float) $purchase->grand_total - (float) $purchase->paid_amount - $alreadyReturnedDebt);
                $amountToPay = (float) $inv['amount'];

                if ($amountToPay <= 0 || $amountToPay > $unpaidDebt) {
                    throw new \InvalidArgumentException("Nominal pelunasan (Rp {$amountToPay}) melebihi sisa hutang faktur [{$purchase->invoice_number}] (Sisa: Rp {$unpaidDebt}).");
                }

                $totalAmount += $amountToPay;
                $preparedItems[] = [
                    'purchase' => $purchase,
                    'amount_paid' => $amountToPay,
                ];
            }

            $payment = SupplierPayment::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'payment_number' => $paymentNumber,
                'payment_date' => now()->toDateString(),
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'total_amount' => $totalAmount,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            foreach ($preparedItems as $pItem) {
                /** @var Purchase $p */
                $p = $pItem['purchase'];
                SupplierPaymentItem::create([
                    'supplier_payment_id' => $payment->id,
                    'purchase_id' => $p->id,
                    'amount_paid' => $pItem['amount_paid'],
                ]);

                // Update status paid_amount pada faktur pembelian
                $p->increment('paid_amount', $pItem['amount_paid']);
            }

            // Catat Jurnal Pelunasan Hutang Seimbang
            $this->accountingService->recordSupplierPaymentJournal($payment);

            return $payment->load('items.purchase');
        });
    }

    /**
     * Buku Pembantu Hutang Supplier (Accounts Payable Aging Ledger).
     */
    public function getAccountsPayableLedger(Store $store, ?string $supplierId = null): Collection
    {
        $query = Purchase::where('store_id', $store->id)
            ->where('payment_type', 'TEMPO')
            ->where('status', 'RECEIVED')
            ->whereRaw('paid_amount < grand_total')
            ->with(['supplier', 'store', 'returns.debtAllocations', 'debtAllocations.purchaseReturn'])
            ->orderBy('due_date');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->get()->map(function ($purchase) {
            $allocatedReturns = (float) $purchase->debtAllocations
                ->where('purchaseReturn.status', 'COMPLETED')
                ->sum('allocated_amount');

            // Fallback untuk retur lama yang belum memiliki tabel alokasi
            $directUnallocatedReturns = (float) $purchase->returns
                ->where('status', 'COMPLETED')
                ->where('refund_type', 'DEBT_REDUCTION')
                ->filter(fn ($r) => $r->debtAllocations->isEmpty())
                ->sum('grand_total');

            $returnedDebt = $allocatedReturns + $directUnallocatedReturns;

            $remaining = max(0.0, (float) $purchase->grand_total - (float) $purchase->paid_amount - $returnedDebt);
            $today = today();
            $dueDate = $purchase->due_date ? \Carbon\Carbon::parse($purchase->due_date) : $today;
            $daysOverdue = $today->gt($dueDate) ? $today->diffInDays($dueDate) : 0;

            return [
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'invoice_number' => $purchase->invoice_number,
                'reference_number' => $purchase->reference_number,
                'supplier_name' => $purchase->supplier->name,
                'supplier_phone' => $purchase->supplier->phone,
                'purchase_date' => $purchase->purchase_date->format('Y-m-d'),
                'due_date' => $purchase->due_date ? $purchase->due_date->format('Y-m-d') : '-',
                'grand_total' => (float) $purchase->grand_total,
                'paid_amount' => (float) $purchase->paid_amount,
                'returned_amount' => $returnedDebt,
                'remaining_debt' => $remaining,
                'is_overdue' => $daysOverdue > 0,
                'days_overdue' => $daysOverdue,
            ];
        })->filter(function ($item) {
            return $item['remaining_debt'] > 0;
        })->values();
    }

    /**
     * Penerimaan Fisik Barang di Gudang (Goods Receipt Note / LPB - AGENTS.md Section 4C).
     * Menerima barang fisik dari PO berdasarkan Surat Jalan Supplier (DO).
     * Mengalokasikan Landed Cost (Ongkir & Bongkar Muat) ke HPP Satuan Barang.
     * Mengupdate stok fisik, menghitung Moving Average HPP, dan mencatat akrual GRNI.
     */
    public function createGoodsReceiptFromPo(
        PurchaseOrder $po,
        array $receivedItems,
        string $deliveryOrderNumber,
        ?string $receiptDate = null,
        float $shippingCost = 0.0,
        float $handlingCost = 0.0,
        ?string $driverName = null,
        ?string $vehicleNumber = null,
        ?string $notes = null,
        ?string $userId = null
    ): GoodsReceipt {
        if (! in_array($po->status, ['ORDERED', 'PARTIALLY_RECEIVED'], true)) {
            throw new \DomainException("Penerimaan barang hanya dapat dilakukan untuk PO yang berstatus ORDERED atau PARTIALLY_RECEIVED. Status saat ini: {$po->status}");
        }

        return DB::transaction(function () use (
            $po,
            $receivedItems,
            $deliveryOrderNumber,
            $receiptDate,
            $shippingCost,
            $handlingCost,
            $driverName,
            $vehicleNumber,
            $notes,
            $userId
        ) {
            $company = $po->company;
            $store = $po->store;
            $supplier = $po->supplier;

            $dateStr = date('Ymd');
            $count = GoodsReceipt::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $receiptNumber = 'LPB-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $shippingCost = max(0.0, round($shippingCost, 2));
            $handlingCost = max(0.0, round($handlingCost, 2));
            $totalLandedCost = $shippingCost + $handlingCost;

            $totalEstimatedValue = 0.0;
            $itemsToProcess = [];

            // Tahap 1: Validasi kuantitas item vs sisa order PO
            foreach ($receivedItems as $recItem) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $po->items()->where('id', $recItem['purchase_order_item_id'])->firstOrFail();

                $qtyReceivedNow = (float) $recItem['quantity'];
                if ($qtyReceivedNow <= 0) {
                    continue;
                }

                $remainingOrdered = (float) $poItem->quantity_ordered - (float) $poItem->quantity_received;
                if ($qtyReceivedNow > $remainingOrdered) {
                    throw new \InvalidArgumentException("Kuantitas barang yang diterima ({$qtyReceivedNow}) melebihi sisa pesanan PO ({$remainingOrdered}) untuk item [{$poItem->product->name}].");
                }

                $conversion = (float) $poItem->conversion_factor > 0 ? (float) $poItem->conversion_factor : 1.0;
                $baseQty = round($qtyReceivedNow * $conversion, 4);
                $estUnitPrice = (float) ($recItem['unit_price'] ?? $poItem->unit_price);
                $lineSubtotal = round($qtyReceivedNow * $estUnitPrice, 2);

                $totalEstimatedValue += $lineSubtotal;

                $itemsToProcess[] = [
                    'po_item' => $poItem,
                    'qty' => $qtyReceivedNow,
                    'base_qty' => $baseQty,
                    'conversion' => $conversion,
                    'unit_price' => $estUnitPrice,
                    'subtotal' => $lineSubtotal,
                    'batch_number' => $recItem['batch_number'] ?? null,
                    'expiry_date' => $recItem['expiry_date'] ?? null,
                    'physical_condition' => $recItem['physical_condition'] ?? 'BAIK',
                    'notes' => $recItem['notes'] ?? null,
                ];
            }

            if (empty($itemsToProcess)) {
                throw new \InvalidArgumentException('Tidak ada kuantitas barang yang valid untuk diterima.');
            }

            // Tahap 2: Buat Dokumen LPB (GoodsReceipt)
            $goodsReceipt = GoodsReceipt::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'purchase_order_id' => $po->id,
                'supplier_id' => $supplier->id,
                'receipt_number' => $receiptNumber,
                'delivery_order_number' => $deliveryOrderNumber,
                'receipt_date' => $receiptDate ?: now()->toDateString(),
                'status' => 'RECEIVED',
                'total_items_received' => count($itemsToProcess),
                'estimated_total_value' => $totalEstimatedValue,
                'shipping_cost' => $shippingCost,
                'handling_cost' => $handlingCost,
                'total_landed_cost' => $totalLandedCost,
                'driver_name' => $driverName,
                'vehicle_number' => $vehicleNumber,
                'received_by' => $userId,
                'notes' => $notes,
            ]);

            // Tahap 3: Alokasi Landed Cost & Tambah Stok Fisik
            foreach ($itemsToProcess as $proc) {
                /** @var PurchaseOrderItem $poItem */
                $poItem = $proc['po_item'];
                $product = $poItem->product;
                $baseQty = $proc['base_qty'];

                $allocatedCost = 0.0;
                if ($totalEstimatedValue > 0 && $totalLandedCost > 0) {
                    $itemWeight = $proc['subtotal'] / $totalEstimatedValue;
                    $allocatedCost = round(($totalLandedCost * $itemWeight) / max(1.0, $proc['qty']), 2);
                }

                $effectiveUnitPrice = $proc['unit_price'] + $allocatedCost;
                $effectiveBaseCost = round($effectiveUnitPrice / $proc['conversion'], 4);

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $goodsReceipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'product_id' => $product->id,
                    'product_unit_id' => $poItem->product_unit_id,
                    'unit_code' => $poItem->unit_code,
                    'conversion_factor' => $proc['conversion'],
                    'quantity_received' => $proc['qty'],
                    'base_quantity' => $baseQty,
                    'estimated_unit_price' => $proc['unit_price'],
                    'allocated_landed_cost' => $allocatedCost,
                    'effective_cost_price' => $effectiveUnitPrice,
                    'subtotal' => $proc['subtotal'],
                    'batch_number' => $proc['batch_number'],
                    'expiry_date' => $proc['expiry_date'],
                    'physical_condition' => $proc['physical_condition'],
                    'notes' => $proc['notes'],
                ]);

                // Eksekusi Moving Average & Tambah Stok Fisik Gudang
                $this->inventoryService->recordGoodsReceipt(
                    product: $product,
                    store: $store,
                    quantity: $baseQty,
                    purchaseCostPerUnit: $effectiveBaseCost,
                    referenceNumber: $receiptNumber,
                    notes: "Penerimaan Fisik LPB {$receiptNumber} Surat Jalan {$deliveryOrderNumber} dari PO {$po->po_number}",
                    userId: $userId
                );

                // Update kuantitas diterima di PurchaseOrderItem
                $poItem->quantity_received = (float) $poItem->quantity_received + $proc['qty'];
                $poItem->save();
            }

            // Tahap 4: Update Status PO (RECEIVED atau PARTIALLY_RECEIVED)
            $allReceived = true;
            foreach ($po->items()->get() as $item) {
                if ((float) $item->quantity_received < (float) $item->quantity_ordered) {
                    $allReceived = false;
                    break;
                }
            }
            $po->status = $allReceived ? 'RECEIVED' : 'PARTIALLY_RECEIVED';
            $po->save();

            // Tahap 5: Jurnal Double-Entry Akrual GRNI (Persediaan vs Hutang Belum Ditagih)
            $this->accountingService->recordGoodsReceiptGrniJournal($goodsReceipt);

            return $goodsReceipt;
        });
    }

    /**
     * Verifikasi & Penyelesaian Three-Way Matching (PO vs LPB vs Vendor Invoice - AGENTS.md Section 4C & 5).
     * Mencocokkan penerimaan fisik gudang (LPB) dengan faktur tagihan resmi supplier.
     * Mengakui Hutang Usaha (AP) definitif, mencatat PPN Masukan, dan menutup akrual GRNI.
     */
    public function performThreeWayMatching(
        GoodsReceipt $receipt,
        string $invoiceNumber,
        string $purchaseDate,
        string $paymentType,
        ?string $dueDate = null,
        ?string $bankAccountId = null,
        float $taxAmount = 0.0,
        float $discountAmount = 0.0,
        array $invoiceItemPrices = [],
        ?string $notes = null,
        ?string $userId = null
    ): Purchase {
        if ($receipt->status === 'MATCHED') {
            throw new \DomainException("Dokumen LPB {$receipt->receipt_number} sudah pernah di-matching dengan Faktur Pembelian.");
        }

        return DB::transaction(function () use (
            $receipt,
            $invoiceNumber,
            $purchaseDate,
            $paymentType,
            $dueDate,
            $bankAccountId,
            $taxAmount,
            $discountAmount,
            $invoiceItemPrices,
            $notes,
            $userId
        ) {
            $company = $receipt->company;
            $store = $receipt->store;
            $supplier = $receipt->supplier;

            $dateStr = date('Ymd');
            $count = Purchase::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $refNumber = 'FB-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $subtotal = 0.0;
            $hasDiscrepancy = false;

            $receiptItems = $receipt->items()->with('product')->get();
            $purchaseItemsData = [];

            foreach ($receiptItems as $grItem) {
                $finalUnitPrice = isset($invoiceItemPrices[$grItem->id]) 
                    ? (float) $invoiceItemPrices[$grItem->id] 
                    : (float) $grItem->estimated_unit_price;

                if (abs($finalUnitPrice - (float) $grItem->estimated_unit_price) > 0.01) {
                    $hasDiscrepancy = true;
                }

                $itemSubtotal = round((float) $grItem->quantity_received * $finalUnitPrice, 2);
                $subtotal += $itemSubtotal;

                $conversion = (float) $grItem->conversion_factor > 0 ? (float) $grItem->conversion_factor : 1.0;
                $baseUnitPrice = round($finalUnitPrice / $conversion, 4);

                $purchaseItemsData[] = [
                    'product_id' => $grItem->product_id,
                    'product_unit_id' => $grItem->product_unit_id,
                    'unit_code' => $grItem->unit_code,
                    'conversion_factor' => $conversion,
                    'quantity' => $grItem->quantity_received,
                    'base_quantity' => $grItem->base_quantity,
                    'unit_price' => $finalUnitPrice,
                    'base_unit_price' => $baseUnitPrice,
                    'discount_percent' => 0,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $discountAmount = max(0.0, round($discountAmount, 2));
            $taxAmount = max(0.0, round($taxAmount, 2));
            $grandTotal = max(0.0, $subtotal - $discountAmount + $taxAmount);
            $paidAmount = $paymentType === 'TEMPO' ? 0.00 : $grandTotal;

            $matchStatus = $hasDiscrepancy ? 'DISCREPANCY_APPROVED' : 'MATCHED';

            $purchase = Purchase::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'purchase_order_id' => $receipt->purchase_order_id,
                'goods_receipt_id' => $receipt->id,
                'supplier_id' => $supplier->id,
                'invoice_number' => $invoiceNumber,
                'reference_number' => $refNumber,
                'purchase_date' => $purchaseDate,
                'due_date' => $paymentType === 'TEMPO' ? ($dueDate ?: now()->addDays(30)->toDateString()) : null,
                'payment_type' => $paymentType,
                'bank_account_id' => $bankAccountId,
                'status' => 'RECEIVED',
                'three_way_match_status' => $matchStatus,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'notes' => $notes ?: "Three-Way Matching dari LPB No: {$receipt->receipt_number} Surat Jalan: {$receipt->delivery_order_number}",
                'created_by' => $userId,
            ]);

            foreach ($purchaseItemsData as $itemData) {
                PurchaseItem::create(array_merge($itemData, [
                    'purchase_id' => $purchase->id,
                ]));
            }

            $receipt->status = 'MATCHED';
            $receipt->save();

            $this->accountingService->recordVendorInvoiceMatchingJournal(
                purchase: $purchase,
                receipt: $receipt
            );

            return $purchase;
        });
    }
}
