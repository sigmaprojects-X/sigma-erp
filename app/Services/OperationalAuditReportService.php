<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Store;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OperationalAuditReportService
{
    /**
     * 1. Laporan Analisa Jam Sibuk & Produktivitas Kasir (Hourly Peak Hours Heatmap).
     */
    public function getHourlyPeakHoursReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        $query = PosTransaction::query()
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', '!=', 'VOID');

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $transactions = $query->with('cashier')->get();

        $totalTxCount = $transactions->count();
        $totalTurnover = (float) $transactions->sum('grand_total');

        // Inisialisasi 24 jam (00:00 s.d. 23:00)
        $hourlyBuckets = [];
        for ($h = 0; $h < 24; $h++) {
            $hourStr = sprintf('%02d:00', $h);
            $hourlyBuckets[$h] = [
                'hour_num' => $h,
                'hour_label' => sprintf('%02d:00 - %02d:59', $h, $h),
                'tx_count' => 0,
                'turnover' => 0.0,
                'avg_basket_size' => 0.0,
                'share_percent' => 0.0,
            ];
        }

        // Inisialisasi Hari dalam Seminggu
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $dayBuckets = [];
        foreach ($dayNames as $dayNum => $name) {
            $dayBuckets[$dayNum] = [
                'day_num' => $dayNum,
                'day_name' => $name,
                'tx_count' => 0,
                'turnover' => 0.0,
                'avg_basket_size' => 0.0,
            ];
        }

        foreach ($transactions as $tx) {
            $createdAt = Carbon::parse($tx->created_at);
            $h = (int) $createdAt->format('H');
            $dayOfWeek = (int) $createdAt->dayOfWeekIso; // 1 (Senin) - 7 (Minggu)
            $amount = (float) $tx->grand_total;

            if (isset($hourlyBuckets[$h])) {
                $hourlyBuckets[$h]['tx_count']++;
                $hourlyBuckets[$h]['turnover'] += $amount;
            }

            if (isset($dayBuckets[$dayOfWeek])) {
                $dayBuckets[$dayOfWeek]['tx_count']++;
                $dayBuckets[$dayOfWeek]['turnover'] += $amount;
            }
        }

        // Kalkulasi Rata-rata dan Share
        $peakHour = null;
        $maxTxInHour = -1;

        foreach ($hourlyBuckets as $h => &$bucket) {
            if ($bucket['tx_count'] > 0) {
                $bucket['avg_basket_size'] = round($bucket['turnover'] / $bucket['tx_count'], 2);
            }
            if ($totalTxCount > 0) {
                $bucket['share_percent'] = round(($bucket['tx_count'] / $totalTxCount) * 100, 2);
            }

            if ($bucket['tx_count'] > $maxTxInHour) {
                $maxTxInHour = $bucket['tx_count'];
                $peakHour = $bucket;
            }
        }
        unset($bucket);

        foreach ($dayBuckets as &$db) {
            if ($db['tx_count'] > 0) {
                $db['avg_basket_size'] = round($db['turnover'] / $db['tx_count'], 2);
            }
        }
        unset($db);

        $hourly = collect($hourlyBuckets)->values();
        $daily = collect($dayBuckets)->values();

        // Cari jam sibuk (top 3)
        $topHours = $hourly->sortByDesc('tx_count')->take(3)->values();

        return [
            'store' => $store,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_tx_count' => $totalTxCount,
            'total_turnover' => $totalTurnover,
            'avg_basket_size' => $totalTxCount > 0 ? round($totalTurnover / $totalTxCount, 2) : 0.0,
            'peak_hour' => $peakHour,
            'top_hours' => $topHours,
            'hourly' => $hourly,
            'daily' => $daily,
        ];
    }

    /**
     * 2. Laporan Audit Pembatalan Kasir & Retur (Void & Return Audit).
     */
    public function getVoidReturnAuditReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        // 1. Ambil Transaksi VOID
        $voidQuery = PosTransaction::query()
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', 'VOID')
            ->with(['store', 'cashier', 'items.product']);

        if ($store) {
            $voidQuery->where('store_id', $store->id);
        }

        $voidTransactions = $voidQuery->get();

        // 2. Ambil Transaksi RETUR
        $returnQuery = PosReturn::query()
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['store', 'transaction.cashier', 'items.product', 'approvedBy', 'processedBy']);

        if ($store) {
            $returnQuery->where('store_id', $store->id);
        }

        $posReturns = $returnQuery->get();

        $auditRows = [];
        $cashierStats = [];

        $totalVoidAmount = 0.0;
        $totalReturnAmount = 0.0;

        // Proses Void
        foreach ($voidTransactions as $vt) {
            $amount = (float) $vt->grand_total;
            $totalVoidAmount += $amount;

            $cashierName = $vt->cashier?->name ?? 'Kasir';
            $cashierId = $vt->cashier_id ?: 'unknown';

            $itemNames = $vt->items->map(function ($it) {
                return ($it->product?->name ?? 'Item') . ' (' . (float)$it->quantity . 'x)';
            })->implode(', ');

            $auditRows[] = [
                'type' => 'VOID',
                'datetime' => Carbon::parse($vt->created_at)->format('d/m/Y H:i'),
                'store_name' => $vt->store?->name ?? 'Cabang',
                'reference_number' => $vt->invoice_number,
                'cashier_name' => $cashierName,
                'authorizer_name' => $vt->cashier?->role === 'manager' ? $cashierName : 'Manager On Duty',
                'amount' => $amount,
                'item_summary' => $itemNames ?: 'Transaksi Dibatalkan',
                'reason' => 'Pembatalan transaksi struk POS (VOID)',
            ];

            if (!isset($cashierStats[$cashierId])) {
                $cashierStats[$cashierId] = [
                    'cashier_id' => $cashierId,
                    'cashier_name' => $cashierName,
                    'void_count' => 0,
                    'void_amount' => 0.0,
                    'return_count' => 0,
                    'return_amount' => 0.0,
                    'total_incidents' => 0,
                ];
            }
            $cashierStats[$cashierId]['void_count']++;
            $cashierStats[$cashierId]['void_amount'] += $amount;
            $cashierStats[$cashierId]['total_incidents']++;
        }

        // Proses Retur
        foreach ($posReturns as $pr) {
            $amount = (float) $pr->total_refund_amount;
            $totalReturnAmount += $amount;

            $cashierName = $pr->processedBy?->name ?? ($pr->transaction?->cashier?->name ?? 'Kasir');
            $cashierId = $pr->processed_by ?: ($pr->transaction?->cashier_id ?: 'unknown');
            $approverName = $pr->approvedBy?->name ?? 'Manager';

            $itemNames = $pr->items->map(function ($it) {
                return ($it->product?->name ?? 'Item') . ' (' . (float)($it->quantity ?? $it->quantity_returned) . 'x)';
            })->implode(', ');

            $auditRows[] = [
                'type' => 'RETUR',
                'datetime' => Carbon::parse($pr->created_at)->format('d/m/Y H:i'),
                'store_name' => $pr->store?->name ?? 'Cabang',
                'reference_number' => $pr->return_number,
                'cashier_name' => $cashierName,
                'authorizer_name' => $approverName,
                'amount' => $amount,
                'item_summary' => $itemNames ?: 'Pengembalian Produk',
                'reason' => $pr->reason ?: 'Retur barang oleh pembeli',
            ];

            if (!isset($cashierStats[$cashierId])) {
                $cashierStats[$cashierId] = [
                    'cashier_id' => $cashierId,
                    'cashier_name' => $cashierName,
                    'void_count' => 0,
                    'void_amount' => 0.0,
                    'return_count' => 0,
                    'return_amount' => 0.0,
                    'total_incidents' => 0,
                ];
            }
            $cashierStats[$cashierId]['return_count']++;
            $cashierStats[$cashierId]['return_amount'] += $amount;
            $cashierStats[$cashierId]['total_incidents']++;
        }

        $rows = collect($auditRows)->sortByDesc('datetime')->values();
        $cashiers = collect($cashierStats)->sortByDesc('total_incidents')->values();

        return [
            'store' => $store,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'void_count' => $voidTransactions->count(),
            'void_amount' => $totalVoidAmount,
            'return_count' => $posReturns->count(),
            'return_amount' => $totalReturnAmount,
            'total_incidents' => $voidTransactions->count() + $posReturns->count(),
            'total_lost_turnover' => $totalVoidAmount + $totalReturnAmount,
            'rows' => $rows,
            'cashier_summary' => $cashiers,
        ];
    }

    /**
     * 3. Laporan Penjualan Barang Konsinyasi UMKM & Bagi Hasil Komisi Toko.
     */
    public function getConsignmentSalesReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null,
        ?string $supplierId = null
    ): array {
        $itemsQuery = PosTransactionItem::query()
            ->whereHas('transaction', function ($q) use ($company, $startDate, $endDate, $store) {
                $q->where('company_id', $company->id)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->where('status', '!=', 'VOID');
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            })
            ->whereHas('product', function ($q) use ($supplierId) {
                $q->where('is_consignment', true);
                if ($supplierId) {
                    $q->where(function ($sub) use ($supplierId) {
                        $sub->where('consignment_supplier_id', $supplierId)
                            ->orWhere('supplier_id', $supplierId);
                    });
                }
            })
            ->with([
                'product.category',
                'product.consignmentSupplier',
                'product.supplier',
                'product.baseUnit',
                'transaction.store',
            ]);

        $soldItems = $itemsQuery->get();

        $totalGrossRevenue = 0.0;
        $totalSupplierPayable = 0.0; // Hak UMKM / Supplier
        $totalStoreCommission = 0.0; // Bagi Hasil Toko

        $itemRows = [];
        $supplierSummary = [];

        foreach ($soldItems as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $qty = (float) $item->quantity;
            $unitPrice = (float) $item->unit_price;
            $grossRev = round($qty * $unitPrice, 2);

            // Biaya konsinyasi per unit (jika consignment_cost 0, gunakan cost_price)
            $consignmentCost = (float) ($product->consignment_cost ?: $product->cost_price);
            $supplierPayable = round($qty * $consignmentCost, 2);
            $storeCommission = max(0, $grossRev - $supplierPayable);

            $totalGrossRevenue += $grossRev;
            $totalSupplierPayable += $supplierPayable;
            $totalStoreCommission += $storeCommission;

            $supplier = $product->consignmentSupplier ?? $product->supplier;
            $supplierName = $supplier?->name ?? 'UMKM / Supplier Titip';
            $supplierIdKey = $supplier?->id ?? 'unknown';

            $itemRows[] = [
                'sku' => $product->sku ?? '-',
                'product_name' => $product->name,
                'supplier_name' => $supplierName,
                'category' => is_object($product->category) ? $product->category->name : ($product->category ?: 'Umum'),
                'store_name' => $item->transaction?->store?->name ?? 'Cabang',
                'qty_sold' => $qty,
                'base_unit' => $product->baseUnit?->unit_name ?? 'Pcs',
                'selling_price' => $unitPrice,
                'consignment_cost' => $consignmentCost,
                'gross_revenue' => $grossRev,
                'supplier_payable' => $supplierPayable,
                'store_commission' => $storeCommission,
                'commission_percent' => $grossRev > 0 ? round(($storeCommission / $grossRev) * 100, 2) : 0.0,
            ];

            if (!isset($supplierSummary[$supplierIdKey])) {
                $supplierSummary[$supplierIdKey] = [
                    'supplier_id' => $supplierIdKey,
                    'supplier_name' => $supplierName,
                    'phone' => $supplier?->phone ?? '-',
                    'total_qty' => 0.0,
                    'gross_revenue' => 0.0,
                    'supplier_payable' => 0.0,
                    'store_commission' => 0.0,
                ];
            }
            $supplierSummary[$supplierIdKey]['total_qty'] += $qty;
            $supplierSummary[$supplierIdKey]['gross_revenue'] += $grossRev;
            $supplierSummary[$supplierIdKey]['supplier_payable'] += $supplierPayable;
            $supplierSummary[$supplierIdKey]['store_commission'] += $storeCommission;
        }

        $items = collect($itemRows)->sortByDesc('gross_revenue')->values();
        $suppliers = collect($supplierSummary)->map(function ($s) {
            $s['commission_percent'] = $s['gross_revenue'] > 0
                ? round(($s['store_commission'] / $s['gross_revenue']) * 100, 2)
                : 0.0;
            return $s;
        })->sortByDesc('gross_revenue')->values();

        return [
            'store' => $store,
            'supplier_id' => $supplierId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_items_sold' => $items->sum('qty_sold'),
            'total_gross_revenue' => round($totalGrossRevenue, 2),
            'total_supplier_payable' => round($totalSupplierPayable, 2),
            'total_store_commission' => round($totalStoreCommission, 2),
            'avg_commission_percent' => $totalGrossRevenue > 0
                ? round(($totalStoreCommission / $totalGrossRevenue) * 100, 2)
                : 0.0,
            'items' => $items,
            'suppliers' => $suppliers,
        ];
    }
}
