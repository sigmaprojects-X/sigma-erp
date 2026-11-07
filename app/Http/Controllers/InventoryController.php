<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Dashboard Ringkasan & Monitoring Valuasi Persediaan Toko (Rekomendasi 1).
     */
    public function index(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        $categories = ProductCategory::where('company_id', $company->id)->orderBy('name')->get();

        // Query Products & Stock di Toko Terpilih
        $query = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('type', [Product::TYPE_BARANG, Product::TYPE_RAKITAN_PROSES])
            ->with([
                'category',
                'baseUnit',
                'barcodes',
                'stocks' => fn ($s) => $s->where('store_id', $selectedStore->id),
            ]);

        // Filter Pencarian
        if ($q = trim($request->get('q', ''))) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $likeTerm = "%{$q}%";
            $query->where(function ($w) use ($likeTerm, $likeOp) {
                $w->where('name', $likeOp, $likeTerm)
                    ->orWhere('sku', $likeOp, $likeTerm)
                    ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', $likeOp, $likeTerm));
            });
        }

        // Filter Kategori
        if ($catId = $request->get('category_id')) {
            $query->where('category_id', $catId);
        }

        // Filter Status Stok
        $statusFilter = $request->get('status', 'ALL');

        $allProducts = $query->orderBy('name')->get();

        // Hitung 4 KPI Summary
        $totalValuation = 0.0;
        $totalItems = $allProducts->count();
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $quarantinedCount = 0;

        $filteredProducts = $allProducts->filter(function ($p) use (
            $statusFilter,
            &$totalValuation,
            &$lowStockCount,
            &$outOfStockCount,
            &$quarantinedCount
        ) {
            $stock = $p->stocks->first();
            $phys = (float) ($stock->physical_quantity ?? 0);
            $res = (float) ($stock->reserved_quantity ?? 0);
            $quar = (float) ($stock->quarantined_quantity ?? 0);
            $avail = max(0, $phys - $res - $quar);
            $minAlert = (float) ($p->min_stock_alert ?? 0);

            $costPrice = (float) $p->cost_price;
            $valuation = $phys * $costPrice;
            $totalValuation += $valuation;

            if ($avail <= 0) {
                $outOfStockCount++;
            } elseif ($avail <= $minAlert && $minAlert > 0) {
                $lowStockCount++;
            }

            if ($quar > 0) {
                $quarantinedCount++;
            }

            // Filter status
            if ($statusFilter === 'LOW_STOCK') {
                return $avail > 0 && $avail <= $minAlert && $minAlert > 0;
            } elseif ($statusFilter === 'OUT_OF_STOCK') {
                return $avail <= 0;
            } elseif ($statusFilter === 'QUARANTINED') {
                return $quar > 0;
            }

            return true;
        });

        // Pagination Manual sederhana
        $page = (int) $request->get('page', 1);
        $perPage = 25;
        $paginatedItems = $filteredProducts->forPage($page, $perPage);
        $totalPages = (int) ceil($filteredProducts->count() / $perPage);

        return view('warehouse.inventory.index', [
            'stores' => $stores,
            'selectedStore' => $selectedStore,
            'categories' => $categories,
            'products' => $paginatedItems,
            'totalItemsCount' => $filteredProducts->count(),
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalValuation' => $totalValuation,
            'totalItems' => $totalItems,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'quarantinedCount' => $quarantinedCount,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Export Laporan Persediaan & Valuasi Stok Toko ke CSV / Excel.
     */
    public function exportStockSummary(Request $request): StreamedResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storeId = $request->get('store_id', Auth::user()?->store_id);
        $store = Store::where('company_id', $company->id)->findOrFail($storeId);

        $query = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->whereIn('type', [Product::TYPE_BARANG, Product::TYPE_RAKITAN_PROSES])
            ->with([
                'category',
                'baseUnit',
                'barcodes',
                'stocks' => fn ($s) => $s->where('store_id', $store->id),
            ]);

        if ($q = trim($request->get('q', ''))) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($w) use ($q, $likeOp) {
                $w->where('name', $likeOp, "%{$q}%")
                    ->orWhere('sku', $likeOp, "%{$q}%")
                    ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', $likeOp, "%{$q}%"));
            });
        }

        if ($catId = $request->get('category_id')) {
            $query->where('category_id', $catId);
        }

        $products = $query->orderBy('name')->get();
        $filename = "laporan_persediaan_{$store->code}_" . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($products, $store) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'Kode SKU',
                'Barcode',
                'Nama Produk',
                'Kategori',
                'Rak / Lokasi',
                'Satuan Dasar',
                'Stok Fisik Total',
                'Reserved PWA',
                'Karantina (Rusak/BS)',
                'Stok Tersedia (Jual)',
                'Batas Min. Stok (ROP)',
                'HPP Moving Average (Rp)',
                'Total Nilai Aset Stok (Rp)',
                'Status Stok',
            ]);

            foreach ($products as $p) {
                $stock = $p->stocks->first();
                $phys = (float) ($stock->physical_quantity ?? 0);
                $res = (float) ($stock->reserved_quantity ?? 0);
                $quar = (float) ($stock->quarantined_quantity ?? 0);
                $avail = max(0, $phys - $res - $quar);
                $minAlert = (float) ($p->min_stock_alert ?? 0);
                $cost = (float) $p->cost_price;
                $val = $phys * $cost;

                $statusText = 'Aman';
                if ($avail <= 0) {
                    $statusText = 'Habis (0 Unit)';
                } elseif ($avail <= $minAlert && $minAlert > 0) {
                    $statusText = 'Menipis (< ROP)';
                }

                if ($quar > 0) {
                    $statusText .= ' | Ada Karantina (' . number_format($quar, 0) . ')';
                }

                fputcsv($handle, [
                    $p->sku,
                    $p->barcodes->first()?->barcode ?? '-',
                    $p->name,
                    $p->category?->name ?? 'Umum',
                    $p->rack_location ?: '-',
                    $p->baseUnit?->unit_code ?? 'PCS',
                    number_format($phys, 2, ',', '.'),
                    number_format($res, 2, ',', '.'),
                    number_format($quar, 2, ',', '.'),
                    number_format($avail, 2, ',', '.'),
                    number_format($minAlert, 0, ',', '.'),
                    number_format($cost, 2, ',', '.'),
                    number_format($val, 2, ',', '.'),
                    $statusText,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Daftar Riwayat Penyesuaian Stok & Mutasi Karantina (Rekomendasi 2).
     */
    public function adjustmentsIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);

        $query = StockAdjustment::where('company_id', $company->id)
            ->with(['store', 'creator', 'items.product', 'items.productUnit']);

        if ($selectedStoreId) {
            $query->where('store_id', $selectedStoreId);
        }

        if ($type = $request->get('type')) {
            $query->where('adjustment_type', $type);
        }

        $adjustments = $query->orderByDesc('created_at')->paginate(20);

        return view('warehouse.adjustments.index', compact('stores', 'selectedStoreId', 'adjustments'));
    }

    /**
     * Form Input Penyesuaian Stok / Barang Rusak / Pemakaian Toko.
     */
    public function adjustmentsCreate(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        return view('warehouse.adjustments.create', compact('stores', 'selectedStore'));
    }

    /**
     * Simpan Transaksi Penyesuaian Stok & Eksekusi Jurnal.
     */
    public function adjustmentsStore(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|uuid',
            'adjustment_date' => 'required|date',
            'adjustment_type' => 'required|in:DAMAGE_WASTE,INTERNAL_USE,STOCK_IN_CORRECTION,STOCK_OUT_CORRECTION',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.reason' => 'required|string|max:150',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = Store::where('company_id', $company->id)->findOrFail($request->store_id);
        $userId = Auth::id();

        $dateStr = date('Ymd');
        $count = StockAdjustment::where('company_id', $company->id)->whereDate('created_at', today())->count() + 1;
        $adjNumber = 'ADJ-' . $dateStr . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($request, $company, $store, $userId, $adjNumber) {
            $totalCost = 0.0;
            $totalItemsCount = 0;
            $itemsData = [];

            foreach ($request->items as $row) {
                $product = Product::where('company_id', $company->id)->findOrFail($row['product_id']);
                $unit = ProductUnit::where('company_id', $company->id)
                    ->where('product_id', $product->id)
                    ->findOrFail($row['product_unit_id']);

                $qty = (float) $row['quantity'];
                $conv = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round($qty * $conv, 4);

                $unitCost = (float) $product->cost_price;
                $lineCost = round($baseQty * $unitCost, 4);
                $totalCost += $lineCost;
                $totalItemsCount++;

                $action = match ($request->adjustment_type) {
                    'DAMAGE_WASTE' => 'MOVE_TO_QUARANTINE',
                    'INTERNAL_USE', 'STOCK_OUT_CORRECTION' => 'DEDUCT_DIRECT',
                    'STOCK_IN_CORRECTION' => 'ADD_STOCK',
                };

                $itemsData[] = [
                    'product' => $product,
                    'unit' => $unit,
                    'quantity' => $qty,
                    'conversion_factor' => $conv,
                    'base_quantity' => $baseQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'action' => $action,
                    'reason' => $row['reason'],
                    'notes' => $row['notes'] ?? null,
                ];
            }

            $adjustment = StockAdjustment::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'adjustment_number' => $adjNumber,
                'adjustment_date' => $request->adjustment_date,
                'adjustment_type' => $request->adjustment_type,
                'status' => 'COMPLETED',
                'total_items' => $totalItemsCount,
                'total_cost_value' => $totalCost,
                'notes' => $request->notes,
                'created_by' => $userId,
                'approved_by' => $userId,
            ]);

            // Eksekusi mutasi fisik ke inventori
            foreach ($itemsData as $it) {
                $p = $it['product'];

                $adjustment->items()->create([
                    'product_id' => $p->id,
                    'product_unit_id' => $it['unit']->id,
                    'unit_code' => $it['unit']->unit_code,
                    'conversion_factor' => $it['conversion_factor'],
                    'quantity' => $it['quantity'],
                    'base_quantity' => $it['base_quantity'],
                    'unit_cost' => $it['unit_cost'],
                    'total_cost' => $it['total_cost'],
                    'action' => $it['action'],
                    'reason' => $it['reason'],
                    'notes' => $it['notes'],
                ]);

                if ($it['action'] === 'MOVE_TO_QUARANTINE') {
                    // Masuk karantina (terkunci dari kasir POS)
                    $this->inventoryService->quarantineStock(
                        product: $p,
                        store: $store,
                        quantity: $it['base_quantity'],
                        referenceNumber: $adjNumber,
                        reason: "{$it['reason']} (ADJ: {$adjNumber})",
                        userId: $userId
                    );
                } elseif ($it['action'] === 'DEDUCT_DIRECT') {
                    // Potong langsung (pemakaian toko / keluar)
                    $this->inventoryService->deductStock(
                        product: $p,
                        store: $store,
                        quantity: $it['base_quantity'],
                        referenceNumber: $adjNumber,
                        mutationType: 'INTERNAL_USE',
                        notes: "{$it['reason']} (ADJ: {$adjNumber})",
                        userId: $userId
                    );
                } elseif ($it['action'] === 'ADD_STOCK') {
                    // Tambah stok koreksi masuk
                    $this->inventoryService->recordGoodsReceipt(
                        product: $p,
                        store: $store,
                        quantity: $it['base_quantity'],
                        purchaseCostPerUnit: $it['unit_cost'],
                        referenceNumber: $adjNumber,
                        notes: "Koreksi Stok Masuk: {$it['reason']}",
                        userId: $userId
                    );
                }
            }

            // Catat Jurnal Akuntansi Penyesuaian
            $this->accountingService->recordStockAdjustmentJournal($adjustment);
        });

        return redirect()->route('warehouse.adjustments.index', ['store_id' => $store->id])
            ->with('success', "Penyesuaian stok {$adjNumber} berhasil dieksekusi!");
    }

    /**
     * Dashboard Karantina Barang Rusak (Active Quarantined Goods).
     */
    public function quarantineIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        // Cari stok dengan quarantined_quantity > 0
        $quarantinedStocks = ProductStock::where('store_id', $selectedStore->id)
            ->where('quarantined_quantity', '>', 0)
            ->with(['product.baseUnit', 'product.category', 'product.barcodes'])
            ->get();

        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('company_id', $company->id)->where('is_active', true)->get();

        $totalQuarantinedValuation = 0.0;
        foreach ($quarantinedStocks as $qs) {
            $totalQuarantinedValuation += ((float) $qs->quarantined_quantity * (float) $qs->product->cost_price);
        }

        return view('warehouse.adjustments.quarantine', compact(
            'stores',
            'selectedStore',
            'quarantinedStocks',
            'suppliers',
            'bankAccounts',
            'totalQuarantinedValuation'
        ));
    }

    /**
     * Eksekusi Penyelesaian Karantina Barang Rusak (4 Opsi: Potong Hutang, Tunai, Tukar Guling, Write-off).
     */
    public function quarantineResolve(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|uuid',
            'product_id' => 'required|uuid',
            'quantity' => 'required|numeric|min:0.01',
            'resolution_type' => 'required|in:DEBT_DEDUCTION,CASH_REFUND,REPLACEMENT,WRITE_OFF',
            'supplier_id' => 'nullable|uuid',
            'bank_account_id' => 'nullable|uuid',
            'notes' => 'nullable|string|max:255',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = Store::where('company_id', $company->id)->findOrFail($request->store_id);
        $product = Product::where('company_id', $company->id)->findOrFail($request->product_id);
        $qty = (float) $request->quantity;
        $resolutionType = $request->resolution_type;
        $userId = Auth::id();

        $supplier = $request->supplier_id
            ? Supplier::where('company_id', $company->id)->findOrFail($request->supplier_id)
            : null;
        $bankAccount = $request->bank_account_id
            ? BankAccount::where('company_id', $company->id)->findOrFail($request->bank_account_id)
            : null;

        $dateStr = date('Ymd');
        $refNumber = 'QRES-' . $dateStr . '-' . strtoupper(substr(uniqid(), -4));
        $totalCost = round($qty * (float) $product->cost_price, 2);

        DB::transaction(function () use (
            $product,
            $store,
            $qty,
            $resolutionType,
            $refNumber,
            $totalCost,
            $supplier,
            $bankAccount,
            $userId,
            $request
        ) {
            $notes = $request->notes ?: "Penyelesaian Karantina ({$resolutionType})";

            if ($resolutionType === 'DEBT_DEDUCTION') {
                $this->inventoryService->resolveQuarantineDeductDebt(
                    product: $product,
                    store: $store,
                    quantity: $qty,
                    referenceNumber: $refNumber,
                    notes: $notes,
                    userId: $userId
                );

                // Jika ada supplier yang dipilih, cek apakah ada faktur tempo belum lunas untuk dipotong
                if ($supplier) {
                    $unpaidPurchase = Purchase::where('supplier_id', $supplier->id)
                        ->where('status', 'RECEIVED')
                        ->where('payment_type', 'TEMPO')
                        ->whereColumn('paid_amount', '<', 'grand_total')
                        ->orderBy('due_date')
                        ->first();

                    if ($unpaidPurchase) {
                        $newPaid = min((float) $unpaidPurchase->grand_total, (float) $unpaidPurchase->paid_amount + $totalCost);
                        $unpaidPurchase->update(['paid_amount' => $newPaid]);
                    }
                }

                $this->accountingService->recordQuarantineResolutionJournal(
                    resolutionType: 'DEBT_DEDUCTION',
                    store: $store,
                    referenceNumber: $refNumber,
                    amount: $totalCost,
                    supplier: $supplier
                );
            } elseif ($resolutionType === 'CASH_REFUND') {
                $this->inventoryService->resolveQuarantineCashRefund(
                    product: $product,
                    store: $store,
                    quantity: $qty,
                    referenceNumber: $refNumber,
                    notes: $notes,
                    userId: $userId
                );

                $this->accountingService->recordQuarantineResolutionJournal(
                    resolutionType: 'CASH_REFUND',
                    store: $store,
                    referenceNumber: $refNumber,
                    amount: $totalCost,
                    bankAccount: $bankAccount
                );
            } elseif ($resolutionType === 'REPLACEMENT') {
                // Tukar guling barang baru (1-to-1)
                $this->inventoryService->resolveQuarantineReplacement(
                    product: $product,
                    store: $store,
                    quantity: $qty,
                    referenceNumber: $refNumber,
                    notes: $notes,
                    userId: $userId
                );
                // Nilai persediaan tetap berada di inventori, tidak ada selisih kas/hutang
            } elseif ($resolutionType === 'WRITE_OFF') {
                $this->inventoryService->resolveQuarantineWriteOff(
                    product: $product,
                    store: $store,
                    quantity: $qty,
                    referenceNumber: $refNumber,
                    notes: $notes,
                    userId: $userId
                );

                $this->accountingService->recordQuarantineResolutionJournal(
                    resolutionType: 'WRITE_OFF',
                    store: $store,
                    referenceNumber: $refNumber,
                    amount: $totalCost
                );
            }
        });

        return redirect()->route('warehouse.adjustments.quarantine', ['store_id' => $store->id])
            ->with('success', "Penyelesaian barang karantina ({$product->name} x {$qty}) dengan metode {$resolutionType} berhasil dieksekusi!");
    }

    /**
     * Endpoint API Autocomplete & Pencarian Produk untuk Adjustment / Transfer Antar Cabang.
     */
    public function searchProduct(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storeId = $request->get('store_id', Auth::user()?->store_id);

        $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $likeTerm = '%' . $term . '%';

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) use ($likeTerm, $likeOp) {
                $q->where('name', $likeOp, $likeTerm)
                    ->orWhere('sku', $likeOp, $likeTerm)
                    ->orWhereHas('barcodes', fn ($bq) => $bq->where('barcode', $likeOp, $likeTerm));
            })
            ->with([
                'category',
                'units' => fn ($u) => $u->where('is_active', true)->orderBy('conversion_factor'),
                'barcodes',
                'stocks' => fn ($s) => $storeId ? $s->where('store_id', $storeId) : $s,
            ])
            ->limit((int) $request->get('limit', 50))
            ->get();

        $canViewHpp = $request->user()?->canViewHpp() ?? false;

        $result = $products->map(function ($p) use ($canViewHpp) {
            $stock = $p->stocks->first();
            $physical = (float) ($stock?->physical_quantity ?? 0);
            $reserved = (float) ($stock?->reserved_quantity ?? 0);
            $quarantined = (float) ($stock?->quarantined_quantity ?? 0);
            $available = max(0, $physical - $reserved - $quarantined);

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category_name' => $p->category?->name ?? 'Umum',
                'rack_location' => $p->rack_location ?: '-',
                'barcode' => $p->barcodes->first()?->barcode ?? $p->sku,
                'barcodes' => $p->barcodes->pluck('barcode')->toArray(),
                'cost_price' => $canViewHpp ? (float) $p->cost_price : 0,
                'physical_quantity' => $physical,
                'reserved_quantity' => $reserved,
                'quarantined_quantity' => $quarantined,
                'available_quantity' => $available,
                'units' => $p->units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_code' => $u->unit_code,
                    'name' => $u->name,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'is_base_unit' => (bool) $u->is_base_unit,
                ]),
            ];
        });

        return response()->json($result);
    }
}

