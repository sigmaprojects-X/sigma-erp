<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ConsignmentReceipt;
use App\Models\ConsignmentReceiptItem;
use App\Models\ConsignmentSettlement;
use App\Models\ConsignmentSettlementItem;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConsignmentService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Terima Barang Titipan Konsinyasi dari Supplier (Consignment In).
     *
     * Stok fisik bertambah di toko/gudang, namun belum diakui sebagai Hutang Dagang (AP)
     * sampai barang tersebut laku terjual di kasir POS.
     */
    public function receiveConsignment(
        Store $store,
        Supplier $supplier,
        array $items,
        User $user,
        ?string $notes = null
    ): ConsignmentReceipt {
        if (empty($items)) {
            throw new \InvalidArgumentException('Daftar barang penerimaan konsinyasi tidak boleh kosong.');
        }

        return DB::transaction(function () use ($store, $supplier, $items, $user, $notes) {
            $company = $supplier->company;
            if (! $company || $store->company_id !== $company->id) {
                abort(403, 'Supplier dan cabang konsinyasi harus berasal dari perusahaan yang sama.');
            }

            $dateStr = date('Ymd');
            $count = ConsignmentReceipt::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $receiptNumber = 'CSR-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $totalEstimatedAmount = 0.0;
            $totalItemsCount = 0;
            $preparedItems = [];

            foreach ($items as $itemData) {
                /** @var Product $product */
                $product = Product::where('company_id', $company->id)->findOrFail($itemData['product_id']);

                /** @var ProductUnit $unit */
                $unit = ProductUnit::where('company_id', $company->id)
                    ->where('id', $itemData['product_unit_id'])
                    ->where('product_id', $product->id)
                    ->firstOrFail();

                $quantity = (float) $itemData['quantity'];
                if ($quantity <= 0) {
                    continue;
                }

                $unitCost = (float) ($itemData['unit_cost'] ?? $product->consignment_cost ?: $product->cost_price);
                $conversion = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQuantity = round($quantity * $conversion, 4);
                $itemTotalCost = round($baseQuantity * $unitCost, 2);

                $totalEstimatedAmount += $itemTotalCost;
                $totalItemsCount += (int) ceil($quantity);

                // Tandai produk sebagai barang konsinyasi jika belum
                if (! $product->is_consignment) {
                    $product->update([
                        'is_consignment' => true,
                        'consignment_supplier_id' => $supplier->id,
                        'consignment_cost' => $unitCost,
                    ]);
                }

                // Tambah stok fisik di toko tanpa membuat jurnal hutang
                $this->inventoryService->recordAssemblyStock(
                    product: $product,
                    store: $store,
                    quantity: $baseQuantity,
                    unitCost: $unitCost,
                    referenceNumber: $receiptNumber,
                    mutationType: 'CONSIGNMENT_IN',
                    notes: "Penerimaan Titipan Konsinyasi dari {$supplier->name} ({$receiptNumber})",
                    userId: $user->id
                );

                $preparedItems[] = [
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'sold_quantity' => 0.0,
                    'returned_quantity' => 0.0,
                    'settled_quantity' => 0.0,
                    'total_cost' => $itemTotalCost,
                ];
            }

            $receipt = ConsignmentReceipt::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'receipt_number' => $receiptNumber,
                'receipt_date' => today()->toDateString(),
                'total_items' => $totalItemsCount,
                'total_estimated_amount' => $totalEstimatedAmount,
                'status' => 'RECEIVED',
                'notes' => $notes,
                'received_by' => $user->id,
            ]);

            foreach ($preparedItems as $pItem) {
                $pItem['consignment_receipt_id'] = $receipt->id;
                ConsignmentReceiptItem::create($pItem);
            }

            return $receipt->load(['items.product', 'items.unit', 'supplier', 'store']);
        });
    }

    /**
     * Catat Penjualan Barang Konsinyasi dari Kasir POS.
     *
     * Dipanggil secara otomatis ketika produk bertipe is_consignment terjual di POS.
     */
    public function recordConsignmentSale(Product $product, Store $store, float $quantity): void
    {
        if (! $product->is_consignment || $quantity <= 0) {
            return;
        }

        // Ambil penerimaan konsinyasi terbuka dari toko terkait secara FIFO
        $openItems = ConsignmentReceiptItem::whereHas('receipt', function ($q) use ($store) {
            $q->where('store_id', $store->id);
        })
            ->where('product_id', $product->id)
            ->whereRaw('sold_quantity < quantity')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $remaining = $quantity;

        foreach ($openItems as $item) {
            if ($remaining <= 0.0001) {
                break;
            }

            $availableToSell = (float) $item->quantity - (float) $item->sold_quantity;
            $qtyToTake = min($availableToSell, $remaining);

            $item->increment('sold_quantity', $qtyToTake);
            $remaining -= $qtyToTake;
        }
    }

    /**
     * Buat Rekonsiliasi & Tagihan Konsinyasi untuk Barang yang Laku Terjual.
     *
     * Mengakui Hutang Usaha (AP) definitif dan mencatat Jurnal Akuntansi Seimbang.
     */
    public function createSettlement(
        Store $store,
        Supplier $supplier,
        array $settlementSelections,
        User $user,
        ?string $notes = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): ConsignmentSettlement {
        if (empty($settlementSelections)) {
            throw new \InvalidArgumentException('Pilih minimal satu barang konsinyasi yang laku terjual untuk ditagihkan.');
        }

        return DB::transaction(function () use ($store, $supplier, $settlementSelections, $user, $notes, $startDate, $endDate) {
            $company = $supplier->company;
            if (! $company || $store->company_id !== $company->id) {
                abort(403, 'Supplier dan cabang settlement harus berasal dari perusahaan yang sama.');
            }

            $dateStr = date('Ymd');
            $count = ConsignmentSettlement::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $settlementNumber = 'CSS-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $totalSoldQty = 0.0;
            $totalAmount = 0.0;
            $preparedSettlementItems = [];

            foreach ($settlementSelections as $sel) {
                /** @var ConsignmentReceiptItem $receiptItem */
                $receiptItem = ConsignmentReceiptItem::where('id', $sel['consignment_receipt_item_id'])
                    ->whereHas('receipt', fn ($query) => $query
                        ->where('company_id', $company->id)
                        ->where('store_id', $store->id)
                        ->where('supplier_id', $supplier->id))
                    ->lockForUpdate()
                    ->firstOrFail();

                $unsettled = (float) $receiptItem->unsettled_sold_quantity;
                $qtyToSettle = (float) ($sel['quantity'] ?? $unsettled);

                if ($qtyToSettle <= 0 || $qtyToSettle > $unsettled) {
                    throw new \InvalidArgumentException("Kuantitas tagihan konsinyasi untuk {$receiptItem->product->name} tidak valid. Maksimal yang belum diselesaikan: {$unsettled}.");
                }

                $unitCost = (float) $receiptItem->unit_cost;
                $lineTotal = round($qtyToSettle * $unitCost, 2);

                $totalSoldQty += $qtyToSettle;
                $totalAmount += $lineTotal;

                // Update settled quantity
                $receiptItem->increment('settled_quantity', $qtyToSettle);

                $preparedSettlementItems[] = [
                    'consignment_receipt_item_id' => $receiptItem->id,
                    'product_id' => $receiptItem->product_id,
                    'product_unit_id' => $receiptItem->product_unit_id,
                    'settled_quantity' => $qtyToSettle,
                    'unit_cost' => $unitCost,
                    'total_amount' => $lineTotal,
                ];
            }

            $settlement = ConsignmentSettlement::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'settlement_number' => $settlementNumber,
                'settlement_date' => today()->toDateString(),
                'period_start' => $startDate ?: today()->startOfMonth()->toDateString(),
                'period_end' => $endDate ?: today()->toDateString(),
                'total_sold_quantity' => $totalSoldQty,
                'total_settlement_amount' => $totalAmount,
                'payment_status' => 'UNPAID',
                'notes' => $notes,
                'created_by' => $user->id,
            ]);

            foreach ($preparedSettlementItems as $psItem) {
                $psItem['consignment_settlement_id'] = $settlement->id;
                ConsignmentSettlementItem::create($psItem);
            }

            // Jurnal Akuntansi Double-Entry:
            // Debit: Beban Pokok Penjualan / HPP Konsinyasi (5001-001)
            // Kredit: Hutang Usaha Supplier Konsinyasi (2001-001)
            $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CONSIGNMENT_SETTLEMENT',
                referenceNumber: $settlementNumber,
                description: "Pengakuan Hutang Tagihan Konsinyasi {$supplier->name} No: {$settlementNumber}",
                lines: [
                    [
                        'account_code' => '5001-001', // HPP / COGS
                        'debit' => $totalAmount,
                        'credit' => 0.00,
                        'memo' => "Beban Pokok Penjualan Konsinyasi {$supplier->name} ({$settlementNumber})",
                    ],
                    [
                        'account_code' => '2001-001', // Hutang Usaha (AP)
                        'debit' => 0.00,
                        'credit' => $totalAmount,
                        'memo' => "Hutang Tagihan Konsinyasi Terjual {$supplier->name} ({$settlementNumber})",
                    ],
                ],
                store: $store,
                userId: $user->id
            );

            return $settlement->load(['items.product', 'items.unit', 'supplier', 'store']);
        });
    }

    /**
     * Bayar Tagihan Konsinyasi ke Supplier.
     */
    public function paySettlement(
        ConsignmentSettlement $settlement,
        string $paymentMethod,
        User $user,
        ?string $bankName = null
    ): void {
        if ($settlement->payment_status === 'PAID') {
            throw new \DomainException('Tagihan konsinyasi ini sudah lunas.');
        }

        DB::transaction(function () use ($settlement, $paymentMethod, $user, $bankName) {
            $settlement->update([
                'payment_status' => 'PAID',
                'payment_method' => strtoupper($paymentMethod),
            ]);

            $company = $settlement->company;
            $store = $settlement->store;
            $amount = (float) $settlement->total_settlement_amount;

            $creditAccountCode = (strtoupper($paymentMethod) === 'TRANSFER') ? '1002-001' : '1001-001';

            // Jurnal Pembayaran Tagihan Konsinyasi:
            // Debit: Hutang Usaha (2001-001)
            // Kredit: Kas / Bank (1001-001 / 1002-001)
            $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CONSIGNMENT_PAYMENT',
                referenceNumber: 'PAY-'.$settlement->settlement_number,
                description: "Pelunasan Tagihan Konsinyasi {$settlement->supplier->name} No: {$settlement->settlement_number}",
                lines: [
                    [
                        'account_code' => '2001-001', // Hutang Usaha
                        'debit' => $amount,
                        'credit' => 0.00,
                        'memo' => "Pelunasan Hutang Konsinyasi {$settlement->settlement_number}",
                    ],
                    [
                        'account_code' => $creditAccountCode, // Kas atau Bank
                        'debit' => 0.00,
                        'credit' => $amount,
                        'memo' => "Pengeluaran {$paymentMethod} Bayar Konsinyasi {$settlement->settlement_number}",
                    ],
                ],
                store: $store,
                userId: $user->id
            );
        });
    }
}

