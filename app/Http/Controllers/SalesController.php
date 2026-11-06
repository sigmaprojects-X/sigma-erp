<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\PosService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function __construct(
        protected PosService $posService
    ) {}
    /**
     * Sales Hub & Ringkasan Penjualan (KPI Overview).
     */
    public function index(): View
    {
        $today = Carbon::today();
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = Store::where('company_id', $company->id)->where('is_main_store', true)->first()
            ?? Store::where('company_id', $company->id)->first();

        // Query transaksi hari ini (Terfilter per Perusahaan Aktif)
        $user = Auth::user();
        $storeRestrictedRoles = ['cashier', 'warehouse', 'sales'];
        $restrictToStore = $user && in_array($user->role, $storeRestrictedRoles, true);

        $todayTransactions = PosTransaction::where('company_id', $company->id)
            ->whereDate('created_at', $today)
            ->where('status', '!=', 'VOID');

        if ($restrictToStore) {
            $user->store_id ? $todayTransactions->where('store_id', $user->store_id) : $todayTransactions->whereRaw('1 = 0');
        }

        $todayRevenue = (float) (clone $todayTransactions)->sum('grand_total');
        $todayCount = (int) (clone $todayTransactions)->count();
        $todayCredit = (float) (clone $todayTransactions)->sum('credit_amount');
        $todayCashCollected = (float) (clone $todayTransactions)->where('payment_method', 'CASH')->sum('paid_amount')
            + (float) (clone $todayTransactions)->where('payment_method', 'SPLIT')->where('split_payment_method', 'CASH')->sum('paid_amount');
        $todayTransferCollected = (float) (clone $todayTransactions)->where('payment_method', 'TRANSFER')->sum('paid_amount')
            + (float) (clone $todayTransactions)->where('payment_method', 'SPLIT')->where('split_payment_method', 'TRANSFER')->sum('paid_amount');

        // Retur hari ini
        $todayReturnsQuery = PosReturn::where('company_id', $company->id)->whereDate('created_at', $today);
        if ($restrictToStore) {
            $user->store_id ? $todayReturnsQuery->where('store_id', $user->store_id) : $todayReturnsQuery->whereRaw('1 = 0');
        }
        $todayReturnsCount = (clone $todayReturnsQuery)->count();
        $todayReturnsAmount = (float) (clone $todayReturnsQuery)->sum('total_refund_amount');

        $initialTopProducts = $this->getDashboardTopProducts('7_days');
        $initialCashierPerformance = $this->getDashboardCashierPerformance('7_days');

        // Transaksi terakhir - Filter per Perusahaan Aktif
        $recentTransactionsQuery = PosTransaction::where('company_id', $company->id)
            ->with(['customer', 'customerTier'])
            ->where('status', '!=', 'VOID');
        if ($restrictToStore) {
            $user->store_id ? $recentTransactionsQuery->where('store_id', $user->store_id) : $recentTransactionsQuery->whereRaw('1 = 0');
        }
        $recentTransactions = $recentTransactionsQuery
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $wallpaperUrl = $company ? $company->wallpaper_url : asset('images/banner.jpg');

        $initialTrendData = $this->getTrendAnalytics('7_days');
        $initialBranchData = $this->getBranchAnalytics('7_days');

        return view('sales.index', compact(
            'store',
            'company',
            'wallpaperUrl',
            'todayRevenue',
            'todayCount',
            'todayCredit',
            'todayCashCollected',
            'todayTransferCollected',
            'todayReturnsCount',
            'todayReturnsAmount',
            'initialTopProducts',
            'initialCashierPerformance',
            'recentTransactions',
            'initialTrendData',
            'initialBranchData'
        ));
    }

    /**
     * Endpoint API Analytics untuk Grafik Dashboard Penjualan.
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->input('period', $request->input('trend_period', '7_days'));
        if (! in_array($period, ['7_days', '30_days', 'this_month'], true)) {
            $period = '7_days';
        }

        return response()->json([
            'success' => true,
            'trend' => $this->getTrendAnalytics($period),
            'branch' => $this->getBranchAnalytics($period),
            'products' => $this->getDashboardTopProducts($period),
            'cashiers' => $this->getDashboardCashierPerformance($period),
        ]);
    }

    /**
     * Date range shared by all dashboard analytics.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dashboardPeriodBounds(string $period): array
    {
        $today = Carbon::today();

        return match ($period) {
            '30_days' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth()->startOfDay(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
        };
    }

    /**
     * Top products for the selected dashboard period, scoped to the active company.
     */
    private function getDashboardTopProducts(string $period): array
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        [$startDate, $endDate] = $this->dashboardPeriodBounds($period);
        $user = Auth::user();

        return PosTransactionItem::whereHas('transaction', function ($query) use ($company, $startDate, $endDate, $user) {
                $query->where('company_id', $company->id)
                    ->where('status', '!=', 'VOID')
                    ->whereBetween('created_at', [$startDate, $endDate]);

                if ($user && in_array($user->role, ['cashier', 'warehouse', 'sales'], true)) {
                    $user->store_id ? $query->where('store_id', $user->store_id) : $query->whereRaw('1 = 0');
                }
            })
            ->with('product')
            ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product->name ?? 'Produk',
                'sku' => $item->product->sku ?? '-',
                'total_qty' => (float) $item->total_qty,
                'formatted_qty' => number_format((float) $item->total_qty, 0, ',', '.'),
                'total_revenue' => (float) $item->total_revenue,
                'formatted_revenue' => 'Rp ' . number_format((float) $item->total_revenue, 0, ',', '.'),
            ])
            ->values()
            ->all();
    }

    /**
     * Cashier performance for the selected dashboard period, scoped to the active company.
     */
    private function getDashboardCashierPerformance(string $period): array
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        [$startDate, $endDate] = $this->dashboardPeriodBounds($period);
        $user = Auth::user();
        $query = PosTransaction::where('company_id', $company->id)
            ->where('status', '!=', 'VOID')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('cashier_id');

        if ($user && in_array($user->role, ['cashier', 'warehouse', 'sales'], true)) {
            $user->store_id ? $query->where('store_id', $user->store_id) : $query->whereRaw('1 = 0');
        }

        return $query->with('cashier')
            ->selectRaw('cashier_id, COUNT(*) as tx_count, SUM(grand_total) as total_revenue')
            ->groupBy('cashier_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'cashier_id' => $row->cashier_id,
                'name' => $row->cashier->name ?? 'Kasir #' . substr((string) $row->cashier_id, 0, 6),
                'initials' => strtoupper(substr($row->cashier->name ?? 'K', 0, 1)),
                'tx_count' => (int) $row->tx_count,
                'total_revenue' => (float) $row->total_revenue,
                'formatted_revenue' => 'Rp ' . number_format((float) $row->total_revenue, 0, ',', '.'),
            ])
            ->values()
            ->all();
    }

    /**
     * Agregasi Tren Total Penjualan Harian.
     */
    public function getTrendAnalytics(string $period): array
    {
        $user = Auth::user();
        $now = Carbon::today();

        switch ($period) {
            case '30_days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case '7_days':
            default:
                $period = '7_days';
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
        }

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $query = PosTransaction::where('company_id', $company->id)
            ->where('status', '!=', 'VOID')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($user && in_array($user->role, ['cashier', 'warehouse', 'sales'], true)) {
            $user->store_id ? $query->where('store_id', $user->store_id) : $query->whereRaw('1 = 0');
        }

        $dailySales = $query->selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $fullDates = [];
        $totals = [];
        $totalPeriodSales = 0.0;

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dateStr = $cursor->toDateString();
            $val = isset($dailySales[$dateStr]) ? (float) $dailySales[$dateStr] : 0.0;

            $labels[] = $cursor->translatedFormat('d M');
            $fullDates[] = $cursor->translatedFormat('d F Y');
            $totals[] = $val;
            $totalPeriodSales += $val;

            $cursor->addDay();
        }

        return [
            'period' => $period,
            'labels' => $labels,
            'full_dates' => $fullDates,
            'totals' => $totals,
            'total_period_sales' => $totalPeriodSales,
            'formatted_total' => 'Rp ' . number_format($totalPeriodSales, 0, ',', '.'),
            'count_points' => count($totals),
        ];
    }

    /**
     * Agregasi Perbandingan Penjualan Antar Cabang.
     */
    public function getBranchAnalytics(string $period): array
    {
        $user = Auth::user();
        $now = Carbon::today();

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case '7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case '30_days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'this_month':
            default:
                $period = 'this_month';
                $startDate = $now->copy()->startOfMonth()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
        }

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storesQuery = Store::where('is_active', true);
        if ($company) {
            $storesQuery->where('company_id', $company->id);
        }

        if ($user && in_array($user->role, ['cashier', 'warehouse', 'sales'], true)) {
            $user->store_id ? $storesQuery->where('id', $user->store_id) : $storesQuery->whereRaw('1 = 0');
        }

        $stores = $storesQuery->orderBy('code')->get();

        $storeSales = PosTransaction::where('status', '!=', 'VOID')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('store_id', $stores->pluck('id'))
            ->groupBy('store_id')
            ->selectRaw('store_id, SUM(grand_total) as total, COUNT(*) as count')
            ->get()
            ->keyBy('store_id');

        $labels = [];
        $shortLabels = [];
        $totals = [];
        $formattedTotals = [];
        $details = [];
        $totalPeriodSales = 0.0;

        foreach ($stores as $st) {
            $row = $storeSales->get($st->id);
            $total = $row ? (float) $row->total : 0.0;
            $count = $row ? (int) $row->count : 0;

            $labels[] = $st->name;
            $shortLabels[] = $st->code ?: substr($st->name, 0, 15);
            $totals[] = $total;
            $formattedTotals[] = 'Rp ' . number_format($total, 0, ',', '.');
            $totalPeriodSales += $total;

            $details[] = [
                'store_id' => $st->id,
                'store_code' => $st->code,
                'store_name' => $st->name,
                'total_sales' => $total,
                'formatted_sales' => 'Rp ' . number_format($total, 0, ',', '.'),
                'tx_count' => $count,
            ];
        }

        return [
            'period' => $period,
            'labels' => $labels,
            'short_labels' => $shortLabels,
            'totals' => $totals,
            'formatted_totals' => $formattedTotals,
            'total_period_sales' => $totalPeriodSales,
            'formatted_total' => 'Rp ' . number_format($totalPeriodSales, 0, ',', '.'),
            'details' => $details,
        ];
    }

    /**
     * Daftar Riwayat Faktur Penjualan (Enterprise DataGrid).
     */
    public function transactions(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $query = PosTransaction::where('company_id', $company->id)
            ->with(['customer', 'customerTier', 'salesPerson', 'items.product', 'items.productUnit']);

        // Filter Pencarian No Faktur / Pelanggan
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ILIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'ILIKE', "%{$search}%")
                            ->orWhere('code', 'ILIKE', "%{$search}%");
                    });
            });
        }

        // Filter Metode Pembayaran
        if ($method = $request->input('payment_method')) {
            $query->where('payment_method', $method);
        }

        // Filter Status Pembayaran & Tempo / Overdue
        if ($paymentStatus = $request->input('payment_status')) {
            if ($paymentStatus === 'UNPAID_TEMPO') {
                $query->where(function ($q) {
                    $q->whereIn('payment_method', ['CREDIT', 'TEMPO'])
                        ->orWhere('credit_amount', '>', 0);
                })->where('remaining_receivable', '>', 0);
            } elseif ($paymentStatus === 'OVERDUE') {
                $query->where('remaining_receivable', '>', 0)
                    ->whereDate('due_date', '<', Carbon::today());
            } else {
                $query->where('payment_status', $paymentStatus);
            }
        }

        // Filter Tanggal
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('sales.transactions', compact('transactions'));
    }

    /**
     * API Detail Transaksi Penjualan untuk Modal View.
     */
    public function show(string $id): JsonResponse
    {
        $transaction = PosTransaction::with([
            'customer',
            'customerTier',
            'salesPerson',
            'voucher',
            'items.product',
            'items.productUnit',
            'returns.items',
            'store',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        foreach ($transaction->items as $item) {
            $alreadyReturned = (float) PosReturnItem::where('pos_transaction_item_id', $item->id)
                ->whereHas('posReturn', fn ($q) => $q->where('status', '!=', 'CANCELLED'))
                ->sum('quantity');

            $item->already_returned_qty = $alreadyReturned;
            $item->max_returnable = max(0, (float) $item->quantity - $alreadyReturned);
        }

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
        ]);
    }

    /**
     * Endpoint API Autocomplete & Pencarian Produk untuk Faktur Penjualan Back-Office.
     */
    public function searchProduct(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $tierId = $request->get('tier_id');

        $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $likeTerm = '%'.$term.'%';

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('type', '!=', Product::TYPE_BIAYA)
            ->whereNotIn('sale_status', ['DISCONTINUED', 'BUY_ONLY'])
            ->where(function ($q) use ($likeTerm, $likeOp) {
                $q->where('name', $likeOp, $likeTerm)
                    ->orWhere('sku', $likeOp, $likeTerm)
                    ->orWhereHas('barcodes', function ($bq) use ($likeTerm, $likeOp) {
                        $bq->where('barcode', $likeOp, $likeTerm);
                    });
            })
            ->with([
                'units' => fn ($u) => $u->where('is_active', true)->orderBy('conversion_factor'),
                'barcodes',
                'prices.customerTier',
                'prices.productUnit',
            ])
            ->limit((int) $request->get('limit', 50))
            ->get();

        $result = $products->map(function ($p) use ($tierId) {
            $baseUnit = $p->units->firstWhere('is_base_unit', true) ?? $p->units->first();

            // Cari harga jual sesuai tier jika ada, fallback ke retail atau harga pertama
            $tierPrice = null;
            if ($tierId) {
                $tierPrice = $p->prices->firstWhere(fn ($pr) => $pr->customer_tier_id === $tierId && $pr->product_unit_id === $baseUnit?->id)?->price;
            }
            if ($tierPrice === null) {
                $tierPrice = $p->prices->firstWhere(fn ($pr) => $pr->customerTier?->code === 'RETAIL' && $pr->product_unit_id === $baseUnit?->id)?->price
                    ?? $p->prices->firstWhere('product_unit_id', $baseUnit?->id)?->price
                    ?? $p->cost_price;
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcodes->first()?->barcode ?? '',
                'brand' => $p->brand ?: '-',
                'rack_location' => $p->rack_location ?: '-',
                'selling_price' => (float) $tierPrice,
                'total_quantity' => (float) $p->total_quantity,
                'min_stock_alert' => (float) ($p->min_stock_alert ?? 0),
                'base_unit' => $baseUnit ? [
                    'id' => $baseUnit->id,
                    'unit_code' => $baseUnit->unit_code,
                    'unit_name' => $baseUnit->unit_name,
                    'conversion_factor' => 1.0,
                ] : null,
                'units' => $p->units->map(fn ($u) => [
                    'id' => $u->id,
                    'unit_code' => $u->unit_code,
                    'unit_name' => $u->unit_name,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'is_base_unit' => (bool) $u->is_base_unit,
                ]),
                'prices' => $p->prices->map(fn ($pr) => [
                    'customer_tier_id' => $pr->customer_tier_id,
                    'customer_tier_code' => $pr->customerTier?->code,
                    'product_unit_id' => $pr->product_unit_id,
                    'price' => (float) $pr->price,
                ]),
            ];
        });

        return response()->json($result);
    }

    /**
     * Form Input Faktur Penjualan Back-Office (Direct Invoice Entry).
     */
    public function createInvoice(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $customers = Customer::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $tiers = CustomerTier::where('company_id', $company->id)->where('is_active', true)->get();
        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('type', '!=', Product::TYPE_BIAYA)
            ->whereNotIn('sale_status', ['DISCONTINUED', 'BUY_ONLY'])
            ->with(['units', 'prices'])
            ->orderBy('name')
            ->get();

        return view('sales.create_invoice', compact('company', 'stores', 'customers', 'tiers', 'products'));
    }

    /**
     * Simpan Faktur Penjualan Back-Office ke Ledger & Inventori.
     */
    public function storeInvoice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'customer_tier_id' => 'nullable|uuid|exists:customer_tiers,id',
            'payment_method' => 'required|in:CASH,TRANSFER,CREDIT,TEMPO,SPLIT',
            'split_payment_method' => 'nullable|string',
            'paid_amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'voucher_code' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.product_unit_id' => 'required|uuid|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'action' => 'nullable|in:save,print_invoice,print_do',
        ]);

        try {
            $company = app(\App\Services\CompanyService::class)->getActiveCompany();
            $store = Store::where('company_id', $company->id)->findOrFail($validated['store_id']);
            $user = Auth::user();

            // Pastikan produk, satuan & pelanggan milik perusahaan aktif (anti lintas-perusahaan)
            foreach ($validated['items'] as $item) {
                $productOk = Product::where('company_id', $company->id)->whereKey($item['product_id'])->exists();
                $unitOk = \App\Models\ProductUnit::where('company_id', $company->id)
                    ->where('product_id', $item['product_id'])
                    ->whereKey($item['product_unit_id'])
                    ->exists();
                if (! $productOk || ! $unitOk) {
                    throw new \RuntimeException('Barang atau satuan tidak valid untuk perusahaan aktif.');
                }
            }
            if (! empty($validated['customer_id'])
                && ! Customer::where('company_id', $company->id)->whereKey($validated['customer_id'])->exists()) {
                throw new \RuntimeException('Pelanggan tidak valid untuk perusahaan aktif.');
            }

            // Cari atau buka shift kasir/operasional otomatis untuk pencatatan
            $shift = CashierShift::where('store_id', $store->id)
                ->where('status', 'OPEN')
                ->latest('opened_at')
                ->first();

            if (! $shift) {
                $shift = $this->posService->openShift(
                    company: $company,
                    store: $store,
                    cashierId: $user?->id ?? User::first()->id,
                    startingCash: 0.0
                );
            }

            // Tentukan tier harga pelanggan
            if (! empty($validated['customer_tier_id'])) {
                $tier = CustomerTier::where('company_id', $company->id)->findOrFail($validated['customer_tier_id']);
            } elseif (! empty($validated['customer_id'])) {
                $cust = Customer::with('tier')->where('company_id', $company->id)->find($validated['customer_id']);
                $tier = $cust?->tier ?? CustomerTier::where('company_id', $company->id)->where('is_default', true)->firstOrFail();
            } else {
                $tier = CustomerTier::where('company_id', $company->id)->where('is_default', true)->firstOrFail();
            }

            $cartItems = [];
            foreach ($validated['items'] as $item) {
                $cartItems[] = [
                    'product_id' => $item['product_id'],
                    'product_unit_id' => $item['product_unit_id'],
                    'quantity' => (float) $item['quantity'],
                    'discount_amount' => (float) ($item['discount_amount'] ?? 0),
                ];
            }

            $paymentMethod = $validated['payment_method'];
            if ($paymentMethod === 'CREDIT') {
                $paymentMethod = 'TEMPO';
            }

            $transaction = $this->posService->checkout(
                shift: $shift,
                tier: $tier,
                cartItems: $cartItems,
                paymentMethod: $paymentMethod,
                paidAmount: (float) $validated['paid_amount'],
                voucherCode: $validated['voucher_code'] ?? null,
                notes: $validated['notes'] ?? 'Faktur Penjualan Back-Office',
                customerId: $validated['customer_id'] ?? null,
                salesPersonId: $user?->id,
                splitPaymentMethod: $validated['split_payment_method'] ?? null
            );

            // Jika metode kredit dan ada tempo due_date, perbarui due_date
            if (in_array($paymentMethod, ['CREDIT', 'TEMPO']) && ! empty($validated['due_date'])) {
                $transaction->update(['due_date' => $validated['due_date']]);
            }

            $action = $request->input('action', 'save');
            if ($action === 'print_invoice') {
                return redirect()->route('sales.print-invoice', $transaction->id);
            } elseif ($action === 'print_do') {
                return redirect()->route('sales.print-delivery-order', $transaction->id);
            }

            return redirect()->route('sales.transactions')
                ->with('success', "Faktur Penjualan [{$transaction->invoice_number}] berhasil dibuat & dicatat ke buku besar!");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal memproses faktur penjualan: '.$e->getMessage()]);
        }
    }

    /**
     * Cetak Faktur Penjualan Standard A4 / Continuous Form Dot Matrix (Epson LX-310).
     */
    public function printInvoice(string $id): View
    {
        $transaction = PosTransaction::with([
            'customer',
            'customerTier',
            'salesPerson',
            'store.company',
            'items.product',
            'items.productUnit',
            'voucher',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        $terbilang = self::terbilang($transaction->grand_total).' Rupiah';

        return view('sales.print_invoice', compact('transaction', 'terbilang'));
    }

    /**
     * Cetak Surat Jalan (Delivery Order / DO) Tanpa Harga.
     */
    public function printDeliveryOrder(string $id): View
    {
        $transaction = PosTransaction::with([
            'customer',
            'salesPerson',
            'store.company',
            'items.product',
            'items.productUnit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        return view('sales.print_delivery_order', compact('transaction'));
    }

    /**
     * Cetak Surat Pengingat Tagihan Faktur (Invoice Reminder / Statement of Account).
     */
    public function printReminder(string $id): View
    {
        $transaction = PosTransaction::with([
            'customer',
            'customerTier',
            'store.company',
            'salesPerson',
            'items.product',
            'items.productUnit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        $bankAccounts = BankAccount::where('company_id', $transaction->company_id)
            ->where('is_active', true)
            ->get();

        $remaining = (float) ($transaction->remaining_receivable ?: $transaction->credit_amount);
        $terbilang = self::terbilang($remaining).' Rupiah';

        return view('sales.print_reminder', compact('transaction', 'bankAccounts', 'terbilang'));
    }

    /**
     * Daftar Riwayat Retur Penjualan (Sales Returns).
     */
    public function returnsIndex(Request $request): View
    {
        $query = PosReturn::with([
            'transaction.customer',
            'processor',
            'approver',
            'items.product',
            'items.unit',
            'store',
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'ILIKE', "%{$search}%")
                    ->orWhereHas('transaction', function ($tq) use ($search) {
                        $tq->where('invoice_number', 'ILIKE', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'ILIKE', "%{$search}%");
                            });
                    });
            });
        }

        if ($method = $request->input('refund_method')) {
            $query->where('refund_method', $method);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('return_date', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('return_date', '<=', $endDate);
        }

        $returns = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('sales.returns.index', compact('returns'));
    }

    /**
     * Form Buat Retur Penjualan Baru (Cari Faktur / Scan Barcode).
     */
    public function returnsCreate(Request $request): View
    {
        $selectedTransaction = null;
        if ($invoiceNumber = $request->input('invoice_number')) {
            $selectedTransaction = PosTransaction::with([
                'customer',
                'customerTier',
                'store',
                'items.product',
                'items.productUnit',
                'returns.items',
            ])->where('invoice_number', trim($invoiceNumber))->first();

            if ($selectedTransaction) {
                foreach ($selectedTransaction->items as $item) {
                    $alreadyReturned = (float) PosReturnItem::where('pos_transaction_item_id', $item->id)
                        ->whereHas('posReturn', fn ($q) => $q->where('status', '!=', 'CANCELLED'))
                        ->sum('quantity');

                    $item->already_returned_qty = $alreadyReturned;
                    $item->max_returnable = max(0, (float) $item->quantity - $alreadyReturned);
                }
            }
        }

        return view('sales.returns.create', compact('selectedTransaction'));
    }

    /**
     * Simpan Retur Penjualan (Potong Saldo, Pulihkan Stok Fisik/Karantina, Jurnal Akuntansi).
     */
    public function returnsStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pos_transaction_id' => 'required|uuid|exists:pos_transactions,id',
            'refund_method' => 'required|in:CASH,TRANSFER,CREDIT_REDUCTION',
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.pos_transaction_item_id' => 'required|uuid|exists:pos_transaction_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.condition' => 'nullable|string|in:GOOD,DAMAGED,RESTOCKABLE,DEFECTIVE,RUSAK',
            'manager_pin' => 'nullable|string',
        ]);

        $transaction = PosTransaction::with(['items.product', 'items.unit', 'store', 'company'])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($validated['pos_transaction_id']);
        $user = Auth::user();

        // Otorisasi: jika bukan manager/owner/store_head, periksa PIN Manager
        $approvedBy = null;
        if (! in_array($user?->role, ['owner', 'manager', 'store_head'], true)) {
            $pin = $request->input('manager_pin');
            if (empty($pin)) {
                return back()->withInput()->withErrors(['error' => 'Retur transaksi memerlukan otorisasi PIN Manajer.']);
            }

            $authorizedManagers = User::whereIn('role', ['owner', 'manager', 'store_head'])
                ->where('is_active', true)
                ->whereNotNull('manager_pin')
                ->get();

            $approver = null;
            foreach ($authorizedManagers as $mgr) {
                if ($mgr->verifyManagerPin($pin)) {
                    $approver = $mgr;
                    break;
                }
            }

            if (! $approver) {
                return back()->withInput()->withErrors(['error' => 'PIN Manajer yang dimasukkan tidak valid.']);
            }

            $approvedBy = $approver->id;
        } else {
            $approvedBy = $user?->id;
        }

        $currentShift = CashierShift::where('store_id', $transaction->store_id)
            ->where('status', 'OPEN')
            ->latest('opened_at')
            ->first();

        try {
            $posReturn = $this->posService->processReturn(
                transaction: $transaction,
                itemsToReturn: $validated['items'],
                refundMethod: $validated['refund_method'],
                reason: $validated['reason'],
                processedBy: $user?->id ?? $transaction->cashier_id,
                approvedBy: $approvedBy,
                currentShift: $currentShift
            );

            return redirect()->route('sales.returns.print', $posReturn->id)
                ->with('success', "Retur Penjualan [{$posReturn->return_number}] berhasil diproses!");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal memproses retur: '.$e->getMessage()]);
        }
    }

    /**
     * Cetak Nota Retur Penjualan (Credit Note / Bukti Pengembalian).
     */
    public function printReturnNote(string $id): View
    {
        $return = PosReturn::with([
            'transaction.customer',
            'transaction.customerTier',
            'store.company',
            'processor',
            'approver',
            'items.product',
            'items.unit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        $terbilang = self::terbilang($return->total_refund_amount).' Rupiah';

        return view('sales.returns.print', compact('return', 'terbilang'));
    }

    /**
     * Konversi angka nominal uang ke kalimat terbilang Rupiah.
     */
    public static function terbilang(float|int $angka): string
    {
        $angka = abs((float) $angka);
        $bilangan = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
        ];

        if ($angka < 12) {
            $result = $bilangan[(int) $angka];
        } elseif ($angka < 20) {
            $result = self::terbilang($angka - 10).' Belas';
        } elseif ($angka < 100) {
            $result = self::terbilang((int) ($angka / 10)).' Puluh '.self::terbilang($angka % 10);
        } elseif ($angka < 200) {
            $result = 'Seratus '.self::terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $result = self::terbilang((int) ($angka / 100)).' Ratus '.self::terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $result = 'Seribu '.self::terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $result = self::terbilang((int) ($angka / 1000)).' Ribu '.self::terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $result = self::terbilang((int) ($angka / 1000000)).' Juta '.self::terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $result = self::terbilang((int) ($angka / 1000000000)).' Miliar '.self::terbilang(fmod($angka, 1000000000));
        } else {
            $result = self::terbilang((int) ($angka / 1000000000000)).' Triliun '.self::terbilang(fmod($angka, 1000000000000));
        }

        return trim(preg_replace('/\s+/', ' ', $result));
    }
}
