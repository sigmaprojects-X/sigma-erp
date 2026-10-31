<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\StockMutation;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    /**
     * 1. Laporan Nilai Persediaan Aset Barang (Stock Valuation Report).
     * Rumus: Qty Fisik x HPP Moving Average = Nilai Aset Persediaan.
     */
    public function getStockValuationReport(
        Company $company,
        ?Store $store = null,
        ?string $categoryId = null
    ): array {
        $stocksQuery = ProductStock::query()
            ->where('company_id', $company->id)
            ->with([
                'product.category',
                'product.supplier',
                'product.baseUnit',
                'store',
            ]);

        if ($store) {
            $stocksQuery->where('store_id', $store->id);
        }

        if ($categoryId) {
            $stocksQuery->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $stocks = $stocksQuery->get();

        $totalPhysicalQty = 0.0;
        $totalReservedQty = 0.0;
        $totalValuationValue = 0.0;
        $itemRows = [];
        $categorySummary = [];
        $storeSummary = [];

        foreach ($stocks as $stock) {
            $product = $stock->product;
            if (!$product) {
                continue;
            }

            $physicalQty = (float) $stock->physical_quantity;
            $reservedQty = (float) $stock->reserved_quantity;
            $costPrice = (float) $product->cost_price;
            $totalValue = round($physicalQty * $costPrice, 2);

            $totalPhysicalQty += $physicalQty;
            $totalReservedQty += $reservedQty;
            $totalValuationValue += $totalValue;

            $catName = is_object($product->category) ? $product->category->name : ($product->category ?: 'Umum');
            $storeName = $stock->store?->name ?? 'Cabang Default';
            $storeId = $stock->store_id;

            // Rincian per baris item
            $itemRows[] = [
                'stock_id' => $stock->id,
                'product_id' => $product->id,
                'sku' => $product->sku ?? '-',
                'barcode' => $product->barcode ?? '-',
                'product_name' => $product->name,
                'category' => $catName,
                'supplier_name' => $product->supplier?->name ?? 'Supplier Umum',
                'store_code' => $stock->store?->code ?? '-',
                'store_name' => $storeName,
                'base_unit' => $product->baseUnit?->unit_name ?? 'Pcs',
                'physical_qty' => $physicalQty,
                'reserved_qty' => $reservedQty,
                'available_qty' => max(0, $physicalQty - $reservedQty),
                'cost_price' => $costPrice,
                'total_value' => $totalValue,
            ];

            // Akumulasi per Kategori
            if (!isset($categorySummary[$catName])) {
                $categorySummary[$catName] = [
                    'category' => $catName,
                    'sku_count' => 0,
                    'total_qty' => 0.0,
                    'total_value' => 0.0,
                ];
            }
            $categorySummary[$catName]['sku_count']++;
            $categorySummary[$catName]['total_qty'] += $physicalQty;
            $categorySummary[$catName]['total_value'] += $totalValue;

            // Akumulasi per Cabang Toko
            if (!isset($storeSummary[$storeId])) {
                $storeSummary[$storeId] = [
                    'store_id' => $storeId,
                    'store_code' => $stock->store?->code ?? '-',
                    'store_name' => $storeName,
                    'sku_count' => 0,
                    'total_qty' => 0.0,
                    'total_value' => 0.0,
                ];
            }
            $storeSummary[$storeId]['sku_count']++;
            $storeSummary[$storeId]['total_qty'] += $physicalQty;
            $storeSummary[$storeId]['total_value'] += $totalValue;
        }

        // Hitung share percent per kategori
        $byCategory = collect($categorySummary)->map(function ($cat) use ($totalValuationValue) {
            $cat['value_share_percent'] = $totalValuationValue > 0
                ? round(($cat['total_value'] / $totalValuationValue) * 100, 2)
                : 0.0;
            return $cat;
        })->sortByDesc('total_value')->values();

        $byStore = collect($storeSummary)->sortByDesc('total_value')->values();
        $items = collect($itemRows)->sortByDesc('total_value')->values();

        return [
            'store' => $store,
            'category_id' => $categoryId,
            'total_sku_count' => $items->count(),
            'total_physical_qty' => $totalPhysicalQty,
            'total_reserved_qty' => $totalReservedQty,
            'total_valuation_value' => round($totalValuationValue, 2),
            'items' => $items,
            'by_category' => $byCategory,
            'by_store' => $byStore,
        ];
    }

    /**
     * 2. Kartu Stok Lengkap (Stock Card / Mutation History).
     */
    public function getStockCardReport(
        Company $company,
        string $productId,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        $product = Product::with(['category', 'baseUnit', 'supplier'])
            ->where('company_id', $company->id)
            ->findOrFail($productId);

        // 1. Hitung Saldo Awal (Sebelum startDate)
        $openingBalanceQuery = StockMutation::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->where('created_at', '<', $startDate . ' 00:00:00');

        if ($store) {
            $openingBalanceQuery->where('store_id', $store->id);
        }

        $openingBalance = (float) $openingBalanceQuery->sum('quantity');

        // 2. Ambil Mutasi pada Rentang Tanggal
        $mutationsQuery = StockMutation::query()
            ->where('company_id', $company->id)
            ->where('product_id', $product->id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['store'])
            ->orderBy('created_at', 'asc');

        if ($store) {
            $mutationsQuery->where('store_id', $store->id);
        }

        $mutations = $mutationsQuery->get();

        $runningBalance = $openingBalance;
        $totalIn = 0.0;
        $totalOut = 0.0;
        $rows = [];

        foreach ($mutations as $m) {
            $qty = (float) $m->quantity;
            $qtyIn = $qty > 0 ? $qty : 0.0;
            $qtyOut = $qty < 0 ? abs($qty) : 0.0;

            $totalIn += $qtyIn;
            $totalOut += $qtyOut;
            $runningBalance += $qty;

            $rows[] = [
                'id' => $m->id,
                'datetime' => Carbon::parse($m->created_at)->format('d/m/Y H:i'),
                'store_name' => $m->store?->name ?? 'Cabang',
                'reference_number' => $m->reference_number,
                'mutation_type' => $m->mutation_type,
                'type_label' => $this->formatMutationType($m->mutation_type),
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'balance' => $runningBalance,
                'unit_cost' => (float) $m->unit_cost,
                'total_cost' => (float) $m->total_cost,
                'notes' => $m->notes,
            ];
        }

        return [
            'product' => $product,
            'store' => $store,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'closing_balance' => $runningBalance,
            'rows' => collect($rows),
        ];
    }

    /**
     * 3. Laporan Peringatan Stok Minimum & Estimasi Habis (Low Stock Alert).
     */
    public function getLowStockAlertReport(Company $company, ?Store $store = null): array
    {
        $stocksQuery = ProductStock::query()
            ->where('company_id', $company->id)
            ->with([
                'product.category',
                'product.supplier',
                'product.baseUnit',
                'store',
            ]);

        if ($store) {
            $stocksQuery->where('store_id', $store->id);
        }

        $stocks = $stocksQuery->get();

        $criticalItems = [];
        $warningItems = [];

        foreach ($stocks as $stock) {
            $product = $stock->product;
            if (!$product || !$product->is_active) {
                continue;
            }

            $minStock = (float) ($product->min_stock_alert ?? 0);
            if ($minStock <= 0) {
                // Jika belum diatur min stock, lewati
                continue;
            }

            $physicalQty = (float) $stock->physical_quantity;

            if ($physicalQty <= 0) {
                // Stok Habis / Kosong (Kritis Level 1)
                $criticalItems[] = $this->buildLowStockItem($stock, $product, $physicalQty, $minStock, 'OUT_OF_STOCK');
            } elseif ($physicalQty <= $minStock) {
                // Di bawah atau sama dengan stok minimum (Kritis Level 2)
                $warningItems[] = $this->buildLowStockItem($stock, $product, $physicalQty, $minStock, 'LOW_STOCK');
            }
        }

        $critical = collect($criticalItems)->sortBy('physical_qty')->values();
        $warning = collect($warningItems)->sortBy('physical_qty')->values();

        return [
            'store' => $store,
            'critical_count' => $critical->count(),
            'warning_count' => $warning->count(),
            'total_alert_count' => $critical->count() + $warning->count(),
            'critical_items' => $critical,
            'warning_items' => $warning,
            'all_alerts' => $critical->merge($warning),
        ];
    }

    /**
     * 4. Laporan Analisa Perputaran Barang (Fast Moving, Slow Moving, Dead Stock).
     */
    public function getInventoryTurnoverReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        // 1. Ambil data penjualan per item pada rentang waktu
        $salesQuery = PosTransactionItem::query()
            ->whereHas('transaction', function ($q) use ($company, $startDate, $endDate, $store) {
                $q->where('company_id', $company->id)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->where('status', '!=', 'VOID');
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            })
            ->selectRaw('product_id, SUM(quantity) as total_qty_sold, SUM(subtotal) as total_revenue, COUNT(*) as tx_frequency')
            ->groupBy('product_id');

        $salesData = $salesQuery->get()->keyBy('product_id');

        // 2. Ambil seluruh stok produk
        $stocksQuery = ProductStock::query()
            ->where('company_id', $company->id)
            ->with(['product.category', 'product.baseUnit', 'store']);

        if ($store) {
            $stocksQuery->where('store_id', $store->id);
        }

        $stocks = $stocksQuery->get();

        $fastMoving = [];
        $slowMoving = [];
        $deadStock = [];

        foreach ($stocks as $stock) {
            $product = $stock->product;
            if (!$product || !$product->is_active) {
                continue;
            }

            $pid = $product->id;
            $physicalQty = (float) $stock->physical_quantity;
            $costPrice = (float) $product->cost_price;
            $currentStockValue = round($physicalQty * $costPrice, 2);

            $sale = $salesData->get($pid);
            $qtySold = $sale ? (float) $sale->total_qty_sold : 0.0;
            $revenue = $sale ? (float) $sale->total_revenue : 0.0;
            $frequency = $sale ? (int) $sale->tx_frequency : 0;

            $item = [
                'product_id' => $pid,
                'sku' => $product->sku,
                'product_name' => $product->name,
                'category' => is_object($product->category) ? $product->category->name : ($product->category ?: 'Umum'),
                'store_name' => $stock->store?->name ?? 'Cabang',
                'base_unit' => $product->baseUnit?->unit_name ?? 'Pcs',
                'physical_qty' => $physicalQty,
                'current_stock_value' => $currentStockValue,
                'qty_sold' => $qtySold,
                'revenue' => $revenue,
                'frequency' => $frequency,
            ];

            if ($qtySold <= 0 && $physicalQty > 0) {
                // Dead Stock: Ada stok mengendap tapi NOL penjualan
                $item['status'] = 'DEAD_STOCK';
                $deadStock[] = $item;
            } elseif ($qtySold >= 15 || $frequency >= 5) {
                // Fast Moving
                $item['status'] = 'FAST_MOVING';
                $fastMoving[] = $item;
            } else {
                // Slow Moving
                $item['status'] = 'SLOW_MOVING';
                $slowMoving[] = $item;
            }
        }

        $fast = collect($fastMoving)->sortByDesc('qty_sold')->values();
        $slow = collect($slowMoving)->sortByDesc('qty_sold')->values();
        $dead = collect($deadStock)->sortByDesc('current_stock_value')->values();

        $deadStockValue = (float) $dead->sum('current_stock_value');

        return [
            'store' => $store,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fast_count' => $fast->count(),
            'slow_count' => $slow->count(),
            'dead_count' => $dead->count(),
            'dead_stock_value' => $deadStockValue,
            'fast_moving' => $fast,
            'slow_moving' => $slow,
            'dead_stock' => $dead,
        ];
    }

    /**
     * 5. Laporan Kedaluwarsa Barang (Batch & Expiry Date Report).
     */
    public function getExpiryDateReport(
        Company $company,
        ?Store $store = null,
        int $withinDays = 90
    ): array {
        $batchesQuery = ProductBatch::query()
            ->where('company_id', $company->id)
            ->where('current_quantity', '>', 0)
            ->with(['product.category', 'product.baseUnit', 'store'])
            ->orderBy('expired_date', 'asc');

        if ($store) {
            $batchesQuery->where('store_id', $store->id);
        }

        $batches = $batchesQuery->get();
        $today = Carbon::today();

        $expiredList = [];
        $criticalList = []; // <= 30 hari
        $warningList = [];  // 31 - 60 hari
        $cautionList = [];  // 61 - 90 hari

        foreach ($batches as $batch) {
            $product = $batch->product;
            if (!$product) {
                continue;
            }

            $expiredDate = Carbon::parse($batch->expired_date);
            $daysLeft = $today->diffInDays($expiredDate, false);
            $qty = (float) $batch->current_quantity;
            $unitCost = (float) ($batch->unit_cost ?: $product->cost_price);
            $potentialLoss = round($qty * $unitCost, 2);

            $item = [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_id' => $product->id,
                'sku' => $product->sku,
                'product_name' => $product->name,
                'category' => is_object($product->category) ? $product->category->name : ($product->category ?: 'Umum'),
                'store_name' => $batch->store?->name ?? 'Cabang',
                'base_unit' => $product->baseUnit?->unit_name ?? 'Pcs',
                'expired_date' => $expiredDate->format('d/m/Y'),
                'days_left' => $daysLeft,
                'current_qty' => $qty,
                'unit_cost' => $unitCost,
                'potential_loss' => $potentialLoss,
            ];

            if ($daysLeft < 0) {
                $item['status'] = 'EXPIRED';
                $expiredList[] = $item;
            } elseif ($daysLeft <= 30) {
                $item['status'] = 'CRITICAL';
                $criticalList[] = $item;
            } elseif ($daysLeft <= 60) {
                $item['status'] = 'WARNING';
                $warningList[] = $item;
            } elseif ($daysLeft <= $withinDays) {
                $item['status'] = 'CAUTION';
                $cautionList[] = $item;
            }
        }

        $expired = collect($expiredList)->values();
        $critical = collect($criticalList)->values();
        $warning = collect($warningList)->values();
        $caution = collect($cautionList)->values();

        return [
            'store' => $store,
            'within_days' => $withinDays,
            'expired_count' => $expired->count(),
            'critical_count' => $critical->count(),
            'warning_count' => $warning->count(),
            'caution_count' => $caution->count(),
            'total_risk_count' => $expired->count() + $critical->count() + $warning->count() + $caution->count(),
            'total_potential_loss' => (float) ($expired->sum('potential_loss') + $critical->sum('potential_loss')),
            'expired' => $expired,
            'critical' => $critical,
            'warning' => $warning,
            'caution' => $caution,
        ];
    }

    /**
     * 6. Laporan Selisih Stock Opname (Discrepancy / Shrinkage Report).
     */
    public function getOpnameDiscrepancyReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        $opnamesQuery = StockOpname::query()
            ->where('company_id', $company->id)
            ->whereDate('opname_date', '>=', $startDate)
            ->whereDate('opname_date', '<=', $endDate)
            ->with(['store', 'items.product.category'])
            ->orderBy('opname_date', 'desc');

        if ($store) {
            $opnamesQuery->where('store_id', $store->id);
        }

        $opnames = $opnamesQuery->get();

        $totalOpnameCount = $opnames->count();
        $totalDeficitValue = 0.0; // Kerugian (Hilang)
        $totalSurplusValue = 0.0; // Kelebihan
        $discrepancyRows = [];

        foreach ($opnames as $op) {
            foreach ($op->items as $item) {
                $discQty = (float) $item->discrepancy_quantity;
                if ($discQty == 0) {
                    continue; // Tidak ada selisih, lewati
                }

                $discVal = (float) $item->discrepancy_value;
                if ($discVal < 0) {
                    $totalDeficitValue += abs($discVal);
                } else {
                    $totalSurplusValue += $discVal;
                }

                $product = $item->product;
                $discrepancyRows[] = [
                    'opname_id' => $op->id,
                    'opname_number' => $op->opname_number,
                    'opname_date' => Carbon::parse($op->opname_date)->format('d/m/Y'),
                    'store_name' => $op->store?->name ?? 'Cabang',
                    'status' => $op->status,
                    'sku' => $product?->sku ?? '-',
                    'product_name' => $product?->name ?? 'Barang',
                    'system_qty' => (float) $item->system_quantity,
                    'actual_qty' => (float) $item->actual_quantity,
                    'discrepancy_qty' => $discQty,
                    'unit_cost' => (float) $item->unit_cost,
                    'discrepancy_value' => $discVal,
                    'type' => $discQty < 0 ? 'HILANG / DEFISIT' : 'LEBIH / SURPLUS',
                    'notes' => $item->notes,
                ];
            }
        }

        $rows = collect($discrepancyRows)->sortBy('discrepancy_value')->values();

        return [
            'store' => $store,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_opnames' => $totalOpnameCount,
            'total_opname_count' => $totalOpnameCount,
            'total_discrepancy_items' => $rows->count(),
            'total_deficit_value' => $totalDeficitValue,
            'total_surplus_value' => $totalSurplusValue,
            'net_discrepancy_value' => $totalSurplusValue - $totalDeficitValue,
            'items' => $rows,
            'rows' => $rows,
        ];
    }

    /**
     * Helper pemformatan tipe mutasi agar mudah dibaca kasir/auditor.
     */
    protected function formatMutationType(string $type): string
    {
        return match (strtoupper($type)) {
            'GOODS_RECEIPT', 'PURCHASE' => 'Penerimaan Barang (Pembelian)',
            'SALE', 'POS_SALE' => 'Penjualan Kasir POS',
            'SALE_RETURN' => 'Retur Penjualan',
            'PURCHASE_RETURN' => 'Retur Pembelian Supplier',
            'TRANSFER_IN' => 'Transfer Masuk (Dari Cabang Lain)',
            'TRANSFER_OUT' => 'Transfer Keluar (Ke Cabang Lain)',
            'STOCK_OPNAME', 'OPNAME_ADJUSTMENT' => 'Penyesuaian Stock Opname',
            'STOCK_ADJUSTMENT' => 'Koreksi Stok Manual',
            'ASSEMBLY_BUILD' => 'Produksi / Perakitan Paket',
            default => str_replace('_', ' ', $type),
        };
    }

    /**
     * Helper pembangun item low stock.
     */
    protected function buildLowStockItem($stock, $product, float $physicalQty, float $minStock, string $alertType): array
    {
        $recommendation = max(0, ($minStock * 2) - $physicalQty);

        return [
            'stock_id' => $stock->id,
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'category' => is_object($product->category) ? $product->category->name : ($product->category ?: 'Umum'),
            'supplier_name' => $product->supplier?->name ?? 'Supplier Utama',
            'store_name' => $stock->store?->name ?? 'Cabang',
            'base_unit' => $product->baseUnit?->unit_name ?? 'Pcs',
            'physical_qty' => $physicalQty,
            'min_stock' => $minStock,
            'min_stock_alert' => $minStock,
            'alert_type' => $alertType,
            'status' => $alertType,
            'recommended_restock_qty' => ceil($recommendation),
            'cost_price' => (float) $product->cost_price,
            'estimated_restock_cost' => ceil($recommendation) * (float) $product->cost_price,
        ];
    }
}
