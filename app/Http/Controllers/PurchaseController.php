<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnDebtAllocation;
use App\Models\PurchaseReturnItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\TaxConfiguration;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * Daftar Faktur Pembelian & Penerimaan Barang.
     */
    public function index(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $query = Purchase::where('company_id', $company->id)
            ->with(['supplier', 'store', 'items.product'])
            ->orderByDesc('purchase_date')
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = trim($request->q);
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($w) use ($q, $likeOp) {
                $w->where('reference_number', $likeOp, "%{$q}%")
                    ->orWhere('invoice_number', $likeOp, "%{$q}%")
                    ->orWhereHas('supplier', function ($sq) use ($q, $likeOp) {
                        $sq->where('name', $likeOp, "%{$q}%");
                    });
            });
        }

        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->filled('payment_status')) {
            $ps = $request->payment_status;
            if ($ps === 'PAID') {
                $query->where(function ($w) {
                    $w->where('payment_type', '!=', 'TEMPO')
                        ->orWhereRaw('paid_amount >= grand_total');
                });
            } elseif ($ps === 'UNPAID_TEMPO') {
                $query->where('payment_type', 'TEMPO')
                    ->whereRaw('paid_amount < grand_total');
            } elseif ($ps === 'OVERDUE') {
                $query->where('payment_type', 'TEMPO')
                    ->whereRaw('paid_amount < grand_total')
                    ->whereDate('due_date', '<', now()->toDateString());
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->paginate(15)->withQueryString();

        // KPI Ringkasan Modul Pembelian & Hutang Supplier
        $startOfMonth = now()->startOfMonth();
        $monthlyPurchasesTotal = (float) Purchase::where('company_id', $company->id)
            ->where('purchase_date', '>=', $startOfMonth)
            ->sum('grand_total');

        $totalAccountsPayable = (float) Purchase::where('company_id', $company->id)
            ->where('payment_type', 'TEMPO')
            ->where('status', 'RECEIVED')
            ->whereRaw('paid_amount < grand_total')
            ->selectRaw('SUM(grand_total - paid_amount) as remaining')
            ->value('remaining');

        $overdueCount = Purchase::where('company_id', $company->id)
            ->where('payment_type', 'TEMPO')
            ->where('status', 'RECEIVED')
            ->whereRaw('paid_amount < grand_total')
            ->whereDate('due_date', '<', now()->toDateString())
            ->count();

        $activePoCount = PurchaseOrder::where('company_id', $company->id)
            ->whereIn('status', ['DRAFT', 'ORDERED', 'PARTIALLY_RECEIVED'])
            ->count();

        return view('purchases.index', compact(
            'purchases',
            'company',
            'monthlyPurchasesTotal',
            'totalAccountsPayable',
            'overdueCount',
            'activePoCount'
        ));
    }

    /**
     * Form Input Faktur Pembelian / Penerimaan Barang Baru.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = Store::where('company_id', $company->id)->first();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('company_id', $company->id)->where('is_active', true)->orderBy('bank_name')->get();
        $customerTiers = CustomerTier::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByRaw("CASE 
                WHEN code = 'RETAIL' THEN 1 
                WHEN code = 'GROSIR' THEN 2 
                WHEN code = 'AGEN' THEN 3 
                WHEN code = 'DISTRIBUTOR' THEN 4 
                WHEN code = 'MEMBER' THEN 5 
                WHEN code = 'KHUSUS' THEN 6 
                ELSE 7 END")
            ->get();

        // Generate auto reference number (FB-YYYYMMDD-XXXX)
        $dateStr = date('Ymd');
        $todayCount = Purchase::where('company_id', $company->id)
            ->whereDate('created_at', today())
            ->count() + 1;
        $autoRefNumber = 'FB-'.$dateStr.'-'.str_pad($todayCount, 4, '0', STR_PAD_LEFT);

        return view('purchases.create', compact(
            'company',
            'store',
            'suppliers',
            'bankAccounts',
            'customerTiers',
            'autoRefNumber'
        ));
    }

    /**
     * Endpoint API Autocomplete & Pencarian Produk untuk Faktur Pembelian.
     */
    public function searchProduct(Request $request): JsonResponse
    {
        $term = trim($request->get('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
        $likeTerm = '%'.$term.'%';

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
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

        $productIds = $products->pluck('id');
        $recentPurchasesByProduct = PurchaseItem::whereIn('product_id', $productIds)
            ->whereHas('purchase', fn ($q) => $q->where('status', 'RECEIVED'))
            ->with(['purchase.supplier'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('product_id');

        $canViewHpp = $request->user()?->canViewHpp() ?? false;

        $result = $products->map(function ($p) use ($canViewHpp, $recentPurchasesByProduct) {
            $baseUnit = $p->units->firstWhere('is_base_unit', true) ?? $p->units->first();

            $retailPrice = $p->prices->firstWhere(fn ($pr) => $pr->customerTier?->code === 'RETAIL' && $pr->product_unit_id === $baseUnit?->id)?->price
                ?? $p->prices->firstWhere('product_unit_id', $baseUnit?->id)?->price
                ?? $p->cost_price;

            $minStock = (float) ($p->min_stock_alert ?? 0);
            $currentStock = (float) $p->total_quantity;
            $recommendedRestock = max(0.0, ($minStock * 2) - $currentStock);
            if ($recommendedRestock == 0 && $currentStock <= $minStock && $minStock > 0) {
                $recommendedRestock = max(1.0, $minStock - $currentStock + 5);
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcodes->first()?->barcode ?? '',
                'brand' => $p->brand ?: '-',
                'rack_location' => $p->rack_location ?: '-',
                'cost_price' => $canViewHpp ? (float) $p->cost_price : null,
                'last_purchase_price' => $canViewHpp ? (float) $p->last_purchase_price : null,
                'retail_selling_price' => (float) $retailPrice,
                'total_quantity' => $currentStock,
                'min_stock_alert' => $minStock,
                'recommended_restock' => round($recommendedRestock, 2),
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
                'recent_purchases' => ($recentPurchasesByProduct->get($p->id) ?? collect())
                    ->take(3)
                    ->map(function ($pi) use ($canViewHpp) {
                        return [
                            'date' => $pi->purchase?->purchase_date ? date('d/m/Y', strtotime($pi->purchase->purchase_date)) : '-',
                            'supplier_name' => $pi->purchase?->supplier?->name ?? 'Supplier Umum',
                            'unit_code' => $pi->unit_code,
                            'unit_price' => $canViewHpp ? (float) $pi->unit_price : null,
                            'base_unit_price' => $canViewHpp ? (float) $pi->base_unit_price : null,
                        ];
                    })
                    ->values(),
                'prices' => $p->prices->map(fn ($pr) => [
                    'id' => $pr->id,
                    'product_unit_id' => $pr->product_unit_id,
                    'customer_tier_id' => $pr->customer_tier_id,
                    'tier_name' => $pr->customerTier?->name ?? 'Retail',
                    'min_quantity' => (float) $pr->min_quantity,
                    'price' => (float) $pr->price,
                    'margin_percentage' => (float) ($pr->margin_percentage ?? 0),
                ]),
            ];
        });

        return response()->json($result);
    }

    /**
     * Simpan Faktur Pembelian / Penerimaan Barang & Eksekusi Opsi B.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|uuid',
            'store_id' => 'required|uuid',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'payment_type' => 'required|in:CASH,TRANSFER,TEMPO',
            'shipping_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = Store::where('company_id', $company->id)->findOrFail($request->store_id);
        $supplier = Supplier::where('company_id', $company->id)->findOrFail($request->supplier_id);

        $refNumber = trim($request->reference_number ?: '');
        if ($refNumber === '') {
            $dateStr = date('Ymd');
            $count = Purchase::where('company_id', $company->id)->whereDate('created_at', today())->count() + 1;
            $refNumber = 'FB-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        $invoiceNumber = trim($request->invoice_number ?: '');
        if ($invoiceNumber === '') {
            $invoiceNumber = $refNumber;
        }

        $userId = Auth::id();
        $priceUpdates = $request->input('price_updates', []);
        $shouldPrintShelfTags = (bool) $request->input('print_shelf_tags', false);

        $purchase = DB::transaction(function () use (
            $request,
            $company,
            $store,
            $supplier,
            $refNumber,
            $invoiceNumber,
            $userId,
            $priceUpdates
        ) {
            $subtotal = 0.0;
            $itemsData = [];

            foreach ($request->items as $itemInput) {
                $product = Product::where('company_id', $company->id)->findOrFail($itemInput['product_id']);
                $unit = ProductUnit::where('company_id', $company->id)
                    ->where('product_id', $product->id)
                    ->findOrFail($itemInput['product_unit_id']);

                $qty = (float) $itemInput['quantity'];
                $unitPrice = (float) $itemInput['unit_price'];
                $discPct = (float) ($itemInput['discount_percent'] ?? 0);

                $itemSubtotal = ($qty * $unitPrice) * (1 - ($discPct / 100));
                $subtotal += $itemSubtotal;

                $conversion = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
                $baseQty = round($qty * $conversion, 4);
                $baseUnitPrice = round(($unitPrice * (1 - ($discPct / 100))) / $conversion, 4);

                $oldCostPrice = (float) $product->cost_price;
                $isPriceIncreased = ($baseUnitPrice > $oldCostPrice) && ($oldCostPrice > 0);

                $itemsData[] = [
                    'product' => $product,
                    'unit' => $unit,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'disc_pct' => $discPct,
                    'item_subtotal' => $itemSubtotal,
                    'conversion' => $conversion,
                    'base_qty' => $baseQty,
                    'base_unit_price' => $baseUnitPrice,
                    'old_cost_price' => $oldCostPrice,
                    'is_price_increased' => $isPriceIncreased,
                ];
            }

            $discountAmount = (float) ($request->discount_amount ?? 0);
            $taxAmount = (float) ($request->tax_amount ?? 0);
            $shippingCost = (float) ($request->shipping_cost ?? 0);
            $grandTotal = max(0, $subtotal - $discountAmount + $taxAmount + $shippingCost);
            $paidAmount = $request->payment_type === 'TEMPO'
                ? (float) ($request->paid_amount ?? 0)
                : $grandTotal;

            $purchaseRecord = Purchase::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'invoice_number' => $invoiceNumber,
                'reference_number' => $refNumber,
                'purchase_date' => $request->purchase_date,
                'due_date' => $request->payment_type === 'TEMPO' ? $request->due_date : null,
                'payment_type' => $request->payment_type,
                'bank_account_id' => $request->payment_type === 'TRANSFER' ? $request->bank_account_id : null,
                'status' => 'RECEIVED',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'notes' => $request->notes,
                'created_by' => $userId,
            ]);

            // Catat Penerimaan Barang, Moving Average HPP, dan Eksekusi Opsi B
            foreach ($itemsData as $it) {
                /** @var Product $p */
                $p = $it['product'];
                /** @var ProductUnit $u */
                $u = $it['unit'];

                // 1. Alokasi Landed Cost (Ongkir Masuk) dan Eksekusi Moving Average HPP (AGENTS.md Section 2)
                $itemShare = $subtotal > 0 ? ($it['item_subtotal'] / $subtotal) : (1 / max(1, count($itemsData)));
                $allocatedShipping = round($shippingCost * $itemShare, 2);
                $landedBaseUnitPrice = $it['base_qty'] > 0
                    ? round((($it['item_subtotal'] + $allocatedShipping) / $it['base_qty']), 4)
                    : $it['base_unit_price'];

                $this->inventoryService->recordGoodsReceipt(
                    product: $p,
                    store: $store,
                    quantity: $it['base_qty'],
                    purchaseCostPerUnit: $landedBaseUnitPrice,
                    referenceNumber: $refNumber,
                    notes: "Penerimaan Barang Faktur: {$purchaseRecord->invoice_number} ({$supplier->name})",
                    userId: $userId
                );

                $p->refresh();
                $newMovingAvgCost = (float) $p->cost_price;

                // 2. Cek apakah ada persetujuan Opsi B untuk update harga jual (AGENTS.md Section 3)
                $productId = $p->id;
                $isSellingPriceUpdated = false;
                $oldSellingPrice = null;
                $newSellingPrice = null;

                // Cek harga retail dasar saat ini untuk histori
                $baseUnit = $p->units->firstWhere('is_base_unit', true) ?? $u;
                $currentRetailPrice = ProductPrice::where('product_id', $p->id)
                    ->where('product_unit_id', $baseUnit->id)
                    ->where('min_quantity', 1.0)
                    ->first();
                $oldSellingPrice = $currentRetailPrice ? (float) $currentRetailPrice->price : null;

                if (isset($priceUpdates[$productId]) && ! empty($priceUpdates[$productId]['approve_update'])) {
                    $isSellingPriceUpdated = true;

                    // A. Jika user mengirim harga per tier spesifik dari Approval Modal
                    if (! empty($priceUpdates[$productId]['tiers']) && is_array($priceUpdates[$productId]['tiers'])) {
                        foreach ($priceUpdates[$productId]['tiers'] as $tierId => $priceVal) {
                            $parsedPrice = (float) $priceVal;
                            if ($parsedPrice > 0) {
                                ProductPrice::updateOrCreate(
                                    [
                                        'company_id' => $company->id,
                                        'product_id' => $p->id,
                                        'product_unit_id' => $baseUnit->id,
                                        'customer_tier_id' => $tierId,
                                        'min_quantity' => 1.00,
                                        'store_id' => null,
                                    ],
                                    [
                                        'price' => $parsedPrice,
                                        'is_active' => true,
                                    ]
                                );
                            }
                        }
                    }

                    // B. Otomatisasi harga kemasan bertingkat berdasarkan Moving Average HPP Baru
                    $allPrices = ProductPrice::where('product_id', $p->id)->get();
                    foreach ($allPrices as $pr) {
                        $pUnit = ProductUnit::find($pr->product_unit_id);
                        $conv = $pUnit ? (float) $pUnit->conversion_factor : 1.0;
                        $effCost = $newMovingAvgCost * $conv;

                        // Jika ada target margin yang telah ditetapkan sebelumnya
                        if ((float) $pr->margin_percentage > 0 && empty($priceUpdates[$productId]['tiers'][$pr->customer_tier_id])) {
                            $calcPrice = round($effCost * (1 + ((float) $pr->margin_percentage / 100)));
                            $pr->update(['price' => $calcPrice]);
                        }
                    }

                    // Ambil harga retail baru untuk audit
                    $updatedRetail = ProductPrice::where('product_id', $p->id)
                        ->where('product_unit_id', $baseUnit->id)
                        ->where('min_quantity', 1.0)
                        ->first();
                    $newSellingPrice = $updatedRetail ? (float) $updatedRetail->price : null;
                }

                // 3. Simpan baris PurchaseItem
                PurchaseItem::create([
                    'purchase_id' => $purchaseRecord->id,
                    'product_id' => $p->id,
                    'product_unit_id' => $u->id,
                    'unit_code' => $u->unit_code,
                    'conversion_factor' => $it['conversion'],
                    'quantity' => $it['qty'],
                    'base_quantity' => $it['base_qty'],
                    'unit_price' => $it['unit_price'],
                    'base_unit_price' => $it['base_unit_price'],
                    'discount_percent' => $it['disc_pct'],
                    'subtotal' => $it['item_subtotal'],
                    'allocated_shipping_cost' => $allocatedShipping,
                    'old_cost_price' => $it['old_cost_price'],
                    'new_cost_price' => $newMovingAvgCost,
                    'is_price_increased' => $it['is_price_increased'],
                    'is_selling_price_updated' => $isSellingPriceUpdated,
                    'old_selling_price' => $oldSellingPrice,
                    'new_selling_price' => $newSellingPrice,
                ]);
            }

            // 4. Jurnal Akuntansi Double-Entry Seimbang (AGENTS.md Section 5)
            $bankAcc = $request->bank_account_id ? BankAccount::where('company_id', $company->id)->find($request->bank_account_id) : null;
            $this->accountingService->recordGoodsReceiptJournal(
                company: $company,
                store: $store,
                referenceNumber: $refNumber,
                totalPurchasedValue: $grandTotal,
                paymentType: $request->payment_type,
                bankAccountCode: $bankAcc ? '1002-001' : null,
                taxAmount: $taxAmount
            );

            return $purchaseRecord;
        });

        \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

        if ($shouldPrintShelfTags) {
            return redirect()->route('purchases.shelf-tags', $purchase->id)
                ->with('success', "Faktur {$purchase->reference_number} berhasil disimpan dan stok otomatis bertambah!");
        }

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', "Faktur {$purchase->reference_number} berhasil disimpan!");
    }

    /**
     * Tampilan Detail Faktur Pembelian.
     */
    public function show(string $id): View
    {
        $purchase = Purchase::with([
            'company',
            'store',
            'supplier',
            'bankAccount',
            'creator',
            'items.product',
            'items.productUnit',
            'returns.creator',
            'returns.items.product',
            'returns.items.unit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        // Hitung kuantitas yang sudah diretur dan sisa yang dapat diretur untuk setiap item
        foreach ($purchase->items as $item) {
            $alreadyReturned = (float) PurchaseReturnItem::where('purchase_item_id', $item->id)
                ->whereHas('purchaseReturn', fn ($q) => $q->where('status', '!=', 'CANCELLED'))
                ->sum('quantity');

            $item->already_returned_qty = $alreadyReturned;
            $item->max_returnable = max(0, (float) $item->quantity - $alreadyReturned);
        }

        // Hitung sisa hutang faktur untuk opsi DEBT_REDUCTION
        $alreadyReturnedDebt = (float) PurchaseReturn::where('purchase_id', $purchase->id)
            ->where('status', 'COMPLETED')
            ->where('refund_type', 'DEBT_REDUCTION')
            ->sum('grand_total');

        $remainingDebt = max(0.0, (float) $purchase->grand_total - (float) $purchase->paid_amount - $alreadyReturnedDebt);

        return view('purchases.show', compact('purchase', 'remainingDebt'));
    }

    /**
     * Cetak Bukti Penerimaan Faktur Pembelian (A4 Purchase Invoice Voucher).
     */
    public function print(string $id): View
    {
        $purchase = Purchase::with([
            'company',
            'store',
            'supplier',
            'bankAccount',
            'creator',
            'items.product',
            'items.productUnit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        $alreadyReturnedDebt = (float) PurchaseReturn::where('purchase_id', $purchase->id)
            ->where('status', 'COMPLETED')
            ->where('refund_type', 'DEBT_REDUCTION')
            ->sum('grand_total');

        $remainingDebt = max(0.0, (float) $purchase->grand_total - (float) $purchase->paid_amount - $alreadyReturnedDebt);
        $terbilang = SalesController::terbilang((float) $purchase->grand_total) . ' Rupiah';

        return view('purchases.print', compact('purchase', 'remainingDebt', 'terbilang'));
    }

    /**
     * Tampilan Daftar Retur Pembelian ke Pemasok.
     */
    public function returnsIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $query = PurchaseReturn::where('company_id', $company->id)
            ->with([
                'purchase',
                'supplier',
                'store',
                'creator',
                'approver',
                'items.product',
                'items.unit',
            ])->latest('return_date')->latest('created_at');

        if ($search = $request->input('search')) {
            $likeOp = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $likeOp) {
                $q->where('return_number', $likeOp, "%{$search}%")
                    ->orWhere('reason', $likeOp, "%{$search}%")
                    ->orWhereHas('purchase', fn ($sub) => $sub->where('reference_number', $likeOp, "%{$search}%")->orWhere('invoice_number', $likeOp, "%{$search}%"))
                    ->orWhereHas('supplier', fn ($sub) => $sub->where('name', $likeOp, "%{$search}%"));
            });
        }

        if ($returnType = $request->input('return_type')) {
            if ($returnType !== 'ALL') {
                $query->where('return_type', $returnType);
            }
        }

        if ($status = $request->input('status')) {
            if ($status !== 'ALL') {
                $query->where('status', $status);
            }
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($refundType = $request->input('refund_type')) {
            if ($refundType !== 'ALL') {
                $query->where('refund_type', $refundType);
            }
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('return_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('return_date', '<=', $endDate);
        }

        $returns = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('purchases.returns.index', compact('returns', 'suppliers', 'company'));
    }

    /**
     * API Detail Retur Pembelian.
     */
    public function returnsShow(string $id): JsonResponse
    {
        $return = PurchaseReturn::with([
            'purchase',
            'supplier',
            'store',
            'creator',
            'approver',
            'items.product',
            'items.unit',
            'debtAllocations.purchase',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        return response()->json([
            'success' => true,
            'return' => $return,
        ]);
    }

    /**
     * Cetak Dokumen Nota Retur Pembelian Resmi / Surat Jalan Retur ke Supplier.
     */
    public function printReturn(string $id): View
    {
        $return = PurchaseReturn::with([
            'purchase',
            'supplier',
            'store',
            'company',
            'creator',
            'approver',
            'items.product',
            'items.unit',
            'debtAllocations.purchase',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        return view('purchases.returns.print', compact('return'));
    }

    /**
     * Tampilan Cetak Label Rak (Shelf Tag) untuk Barang yang Naik Harganya.
     */
    public function printShelfTags(string $id): View
    {
        $purchase = Purchase::with([
            'supplier',
            'items.product.barcodes',
            'items.product.units',
            'items.product.prices.customerTier',
            'items.productUnit',
        ])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        return view('purchases.shelf_tags', compact('purchase'));
    }

    /**
     * Memproses Retur Pembelian ke Supplier.
     * Mengurangi stok barang fisik dan membalik jurnal hutang usaha/persediaan secara seimbang.
     */
    public function storeReturn(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $purchase = Purchase::with(['supplier', 'items.product', 'items.productUnit'])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
            'refund_type' => 'nullable|string|in:DEBT_REDUCTION,CASH,TRANSFER',
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $purchaseReturn = DB::transaction(function () use ($request, $purchase) {
                /** @var Purchase $lockedPurchase */
                $lockedPurchase = Purchase::where('id', $purchase->id)->lockForUpdate()->firstOrFail();

                $company = app(\App\Services\CompanyService::class)->getActiveCompany();
                $store = $lockedPurchase->store ?? Store::where('company_id', $company->id)->first();
                $userId = Auth::id();

                $dateStr = date('Ymd');
                $todayCount = PurchaseReturn::where('company_id', $company->id)
                    ->whereDate('created_at', today())
                    ->count() + 1;
                $returnNumber = 'PR-'.$dateStr.'-'.str_pad($todayCount, 4, '0', STR_PAD_LEFT);

                $refundType = $request->input('refund_type', 'DEBT_REDUCTION');
                $taxRate = (float) $lockedPurchase->tax_rate;

                $totalSubtotal = 0.0;
                $itemsToProcess = [];

                foreach ($request->items as $itemInput) {
                    /** @var PurchaseItem $purchaseItem */
                    $purchaseItem = $lockedPurchase->items->firstWhere('id', $itemInput['purchase_item_id']);
                    if (! $purchaseItem) {
                        throw new \InvalidArgumentException("Item faktur pembelian tidak valid: {$itemInput['purchase_item_id']}");
                    }

                    $qty = (float) $itemInput['quantity'];
                    if ($qty <= 0) {
                        continue;
                    }

                    // Cek kuantitas yang sudah pernah diretur untuk item ini
                    $alreadyReturned = (float) PurchaseReturnItem::where('purchase_item_id', $purchaseItem->id)
                        ->whereHas('purchaseReturn', fn ($q) => $q->where('status', '!=', 'CANCELLED'))
                        ->sum('quantity');

                    $maxReturnable = (float) $purchaseItem->quantity - $alreadyReturned;
                    if ($qty > $maxReturnable) {
                        throw new \InvalidArgumentException("Jumlah retur ({$qty}) melebihi sisa barang yang dapat diretur ({$maxReturnable}) untuk {$purchaseItem->product->name}.");
                    }

                    $conversion = (float) ($purchaseItem->productUnit?->conversion_factor ?? 1.0);
                    if ($conversion <= 0) {
                        $conversion = 1.0;
                    }
                    $baseQty = $qty * $conversion;
                    $unitPrice = (float) $purchaseItem->unit_price;
                    $itemSubtotal = $qty * $unitPrice;

                    $totalSubtotal += $itemSubtotal;
                    $itemsToProcess[] = [
                        'purchase_item' => $purchaseItem,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $itemSubtotal,
                    ];
                }

                if (empty($itemsToProcess)) {
                    throw new \InvalidArgumentException('Tidak ada item yang valid untuk diretur.');
                }

                $taxAmount = 0.0;
                if ($taxRate > 0) {
                    $taxAmount = round($totalSubtotal * ($taxRate / 100), 2);
                }
                $grandTotal = $totalSubtotal + $taxAmount;

                // Validasi Integritas Hutang Supplier (AP) jika faktur asal bertipe TEMPO
                if ($refundType === 'DEBT_REDUCTION' && $lockedPurchase->payment_type === 'TEMPO') {
                    $alreadyReturnedDebt = (float) $lockedPurchase->debtAllocations()
                        ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'COMPLETED'))
                        ->sum('allocated_amount');

                    $unallocatedDirectReturns = (float) $lockedPurchase->returns()
                        ->where('status', 'COMPLETED')
                        ->where('refund_type', 'DEBT_REDUCTION')
                        ->whereDoesntHave('debtAllocations')
                        ->sum('grand_total');

                    $currentRemainingDebt = max(0.0, (float) $lockedPurchase->grand_total - (float) $lockedPurchase->paid_amount - $alreadyReturnedDebt - $unallocatedDirectReturns);

                    if ($grandTotal > $currentRemainingDebt) {
                        throw new \InvalidArgumentException(
                            "Nilai retur pengurangan hutang (Rp ".number_format($grandTotal, 0, ',', '.').") melebihi sisa hutang faktur pembelian (Sisa: Rp ".number_format($currentRemainingDebt, 0, ',', '.').")."
                        );
                    }
                }

                $returnRecord = PurchaseReturn::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'return_type' => 'INVOICE_BASED',
                    'purchase_id' => $lockedPurchase->id,
                    'supplier_id' => $lockedPurchase->supplier_id,
                    'return_number' => $returnNumber,
                    'return_date' => now()->toDateString(),
                    'refund_type' => $refundType,
                    'subtotal' => $totalSubtotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'reason' => $request->input('reason'),
                    'status' => 'COMPLETED',
                    'created_by' => $userId,
                    'approved_by' => $userId,
                    'approved_at' => now(),
                ]);

                foreach ($itemsToProcess as $proc) {
                    /** @var PurchaseItem $pi */
                    $pi = $proc['purchase_item'];
                    $conversion = (float) ($pi->productUnit?->conversion_factor ?? 1.0);
                    if ($conversion <= 0) {
                        $conversion = 1.0;
                    }
                    $unitCost = (float) $pi->unit_price / $conversion;

                    // Kurangi stok barang menggunakan InventoryService
                    $this->inventoryService->recordPurchaseReturn(
                        product: $pi->product,
                        store: $store,
                        quantity: $proc['base_quantity'],
                        purchaseCostPerUnit: $unitCost,
                        referenceNumber: $returnNumber,
                        notes: "Retur Pembelian Faktur {$purchase->reference_number} ({$returnRecord->reason})",
                        userId: $userId
                    );

                    PurchaseReturnItem::create([
                        'purchase_return_id' => $returnRecord->id,
                        'purchase_item_id' => $pi->id,
                        'product_id' => $pi->product_id,
                        'product_unit_id' => $pi->product_unit_id,
                        'quantity' => $proc['quantity'],
                        'base_quantity' => $proc['base_quantity'],
                        'unit_price' => $proc['unit_price'],
                        'subtotal' => $proc['subtotal'],
                        'condition' => 'GOOD',
                    ]);
                }

                // Catat Alokasi Pengurangan Hutang ke Faktur Pembelian (Single Source of Truth)
                if ($refundType === 'DEBT_REDUCTION' && $lockedPurchase->payment_type === 'TEMPO') {
                    PurchaseReturnDebtAllocation::create([
                        'company_id' => $company->id,
                        'store_id' => $store->id,
                        'purchase_return_id' => $returnRecord->id,
                        'purchase_id' => $lockedPurchase->id,
                        'allocated_amount' => $grandTotal,
                        'notes' => 'Alokasi otomatis retur faktur ' . ($lockedPurchase->invoice_number ?: $lockedPurchase->reference_number),
                    ]);
                }

                // Catat Jurnal Double Entry Seimbang
                $this->accountingService->recordPurchaseReturnJournal($returnRecord);

                return $returnRecord;
            });
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Retur pembelian {$purchaseReturn->return_number} berhasil diproses.",
                'return' => $purchaseReturn->load('items'),
            ]);
        }

        return redirect()->route('purchases.show', $purchase->id)
            ->with('success', "Retur pembelian {$purchaseReturn->return_number} berhasil disimpan!");
    }

    /**
     * Tampilan Formulir Retur Pembelian Fleksibel ke Supplier (Jalur 2).
     */
    public function createFlexibleReturn(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        $dateStr = date('Ymd');
        $todayCount = PurchaseReturn::where('company_id', $company->id)
            ->whereDate('created_at', today())
            ->count() + 1;
        $autoReturnNumber = 'PRF-'.$dateStr.'-'.str_pad($todayCount, 4, '0', STR_PAD_LEFT);

        return view('purchases.returns.create_flexible', compact('company', 'stores', 'suppliers', 'autoReturnNumber'));
    }

    /**
     * Memproses Penyimpanan Dokumen Retur Pembelian Fleksibel (Jalur 2).
     * Secara default tersimpan sebagai DRAFT tanpa mutasi stok hingga disetujui Manajer/Owner.
     */
    public function storeFlexibleReturn(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'return_date' => 'required|date',
            'refund_type' => 'required|in:DEBT_REDUCTION,CASH',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_taxable' => 'nullable|boolean',
            'is_cash_received' => 'nullable|boolean',
            'allocations' => 'nullable|array',
            'allocations.*.purchase_id' => 'required_with:allocations|uuid|exists:purchases,id',
            'allocations.*.amount' => 'required_with:allocations|numeric|min:0.01',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.product_unit_id' => 'required|uuid|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.condition' => 'required|in:GOOD,DAMAGED',
            'items.*.notes' => 'nullable|string|max:255',
            'approve_now' => 'nullable|boolean',
        ]);

        try {
            $company = app(\App\Services\CompanyService::class)->getActiveCompany();
            $store = $request->store_id ? Store::where('company_id', $company->id)->findOrFail($request->store_id) : Store::where('company_id', $company->id)->firstOrFail();
            $supplier = Supplier::where('company_id', $company->id)->findOrFail($request->supplier_id);

            if (! $supplier->is_active) {
                throw new \InvalidArgumentException("Pemasok [{$supplier->name}] tidak aktif. Retur tidak dapat diproses.");
            }

            $purchaseReturn = DB::transaction(function () use ($request, $company, $store, $supplier) {
                $user = Auth::user();
                $userId = $user->id;

                $dateStr = date('Ymd', strtotime($request->return_date));
                $count = PurchaseReturn::where('company_id', $company->id)
                    ->whereDate('created_at', today())
                    ->count() + 1;
                $returnNumber = 'PRF-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

                $totalSubtotal = 0.0;
                $itemsToProcess = [];

                foreach ($request->items as $it) {
                    /** @var Product $product */
                    $product = Product::where('id', $it['product_id'])->lockForUpdate()->firstOrFail();
                    if (! $product->is_active) {
                        throw new \InvalidArgumentException("Produk [{$product->name}] tidak aktif.");
                    }

                    /** @var ProductUnit $unit */
                    $unit = ProductUnit::where('id', $it['product_unit_id'])->where('product_id', $product->id)->firstOrFail();
                    $qty = (float) $it['quantity'];
                    if ($qty <= 0) {
                        continue;
                    }

                    $conversion = (float) ($unit->conversion_factor > 0 ? $unit->conversion_factor : 1.0);
                    $baseQty = $qty * $conversion;

                    // Validasi Ketersediaan Stok Fisik (AGENTS.md Section 2)
                    $stock = ProductStock::where('store_id', $store->id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    $physicalQty = $stock ? (float) $stock->physical_quantity : 0.0;
                    $reservedQty = $stock ? (float) $stock->reserved_quantity : 0.0;
                    $quarantinedQty = $stock ? (float) $stock->quarantined_quantity : 0.0;
                    $availableStock = max(0.0, $physicalQty - $reservedQty - $quarantinedQty);

                    $condition = strtoupper($it['condition'] ?? 'GOOD');
                    $isDamaged = ($condition === 'DAMAGED');

                    if ($isDamaged) {
                        if ($baseQty > $quarantinedQty) {
                            throw new \App\Exceptions\InsufficientStockException(
                                productId: $product->id,
                                storeId: $store->id,
                                requestedQuantity: $baseQty,
                                availableQuantity: $quarantinedQty,
                                message: "Stok karantina produk [{$product->name}] tidak mencukupi. Tersedia di karantina: {$quarantinedQty}, diminta retur: {$baseQty}."
                            );
                        }
                    } else {
                        if ($baseQty > $availableStock) {
                            throw new \App\Exceptions\InsufficientStockException(
                                productId: $product->id,
                                storeId: $store->id,
                                requestedQuantity: $baseQty,
                                availableQuantity: $availableStock,
                                message: "Stok tersedia produk [{$product->name}] tidak mencukupi. Tersedia: {$availableStock}, diminta retur: {$baseQty}."
                            );
                        }
                    }

                    // Penentuan Nilai/Harga Satuan Retur yang Sah:
                    if (isset($it['unit_price']) && (float) $it['unit_price'] > 0) {
                        $unitPrice = (float) $it['unit_price'];
                    } else {
                        $unitPrice = round((float) $product->cost_price * $conversion, 2);
                    }

                    $lineSubtotal = round($qty * $unitPrice, 2);
                    $totalSubtotal += $lineSubtotal;

                    $itemsToProcess[] = [
                        'product' => $product,
                        'product_id' => $product->id,
                        'product_unit_id' => $unit->id,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                        'condition' => $condition,
                        'notes' => $it['notes'] ?? null,
                    ];
                }

                if (empty($itemsToProcess)) {
                    throw new \InvalidArgumentException('Daftar barang yang diretur tidak boleh kosong.');
                }

                $taxAmount = 0.00;
                if ($request->boolean('is_taxable')) {
                    $taxConfig = TaxConfiguration::where('company_id', $company->id)
                        ->where('is_tax_enabled', true)
                        ->where('effective_from', '<=', $request->return_date ?: now()->toDateString())
                        ->latest('effective_from')
                        ->first();
                    if ($taxConfig && (float) $taxConfig->tax_rate_percent > 0) {
                        $taxAmount = round($totalSubtotal * ((float) $taxConfig->tax_rate_percent / 100), 2);
                    }
                }
                $grandTotal = $totalSubtotal + $taxAmount;

                // Validasi & Persiapan Alokasi Hutang Supplier jika refund_type DEBT_REDUCTION
                $allocationsToCreate = [];
                if ($request->refund_type === 'DEBT_REDUCTION') {
                    $unpaidPurchases = Purchase::where('supplier_id', $supplier->id)
                        ->where('store_id', $store->id)
                        ->where('payment_type', 'TEMPO')
                        ->where('status', 'RECEIVED')
                        ->whereRaw('paid_amount < grand_total')
                        ->orderBy('due_date')
                        ->lockForUpdate()
                        ->get();

                    $totalRemainingDebt = $unpaidPurchases->sum(function ($p) {
                        $alreadyPaid = (float) $p->paid_amount;
                        $allocatedReturns = (float) $p->debtAllocations()
                            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'COMPLETED'))
                            ->sum('allocated_amount');
                        $unallocatedDirect = (float) $p->returns()
                            ->where('status', 'COMPLETED')
                            ->where('refund_type', 'DEBT_REDUCTION')
                            ->whereDoesntHave('debtAllocations')
                            ->sum('grand_total');

                        return max(0.0, (float) $p->grand_total - $alreadyPaid - $allocatedReturns - $unallocatedDirect);
                    });

                    if ($totalRemainingDebt <= 0 || $grandTotal > $totalRemainingDebt) {
                        throw new \InvalidArgumentException(
                            "Nilai retur pengurangan hutang (Rp ".number_format($grandTotal, 0, ',', '.').") melebihi total saldo hutang aktif supplier [{$supplier->name}] (Sisa Hutang: Rp ".number_format($totalRemainingDebt, 0, ',', '.')."). Gunakan metode CASH."
                        );
                    }

                    if (! empty($request->input('allocations'))) {
                        $sumAllocated = 0.0;
                        foreach ($request->input('allocations') as $al) {
                            $p = $unpaidPurchases->firstWhere('id', $al['purchase_id']);
                            if (! $p) {
                                throw new \InvalidArgumentException("Faktur pembelian [{$al['purchase_id']}] tidak valid untuk supplier [{$supplier->name}] atau bukan faktur tempo.");
                            }
                            $alreadyPaid = (float) $p->paid_amount;
                            $allocatedReturns = (float) $p->debtAllocations()
                                ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'COMPLETED'))
                                ->sum('allocated_amount');
                            $unallocatedDirect = (float) $p->returns()
                                ->where('status', 'COMPLETED')
                                ->where('refund_type', 'DEBT_REDUCTION')
                                ->whereDoesntHave('debtAllocations')
                                ->sum('grand_total');
                            $rem = max(0.0, (float) $p->grand_total - $alreadyPaid - $allocatedReturns - $unallocatedDirect);
                            $amt = (float) ($al['amount'] ?? 0);
                            if ($amt <= 0 || $amt > $rem) {
                                throw new \InvalidArgumentException("Alokasi retur (Rp ".number_format($amt, 0, ',', '.').") melebihi sisa hutang faktur [{$p->invoice_number}] (Sisa: Rp ".number_format($rem, 0, ',', '.').").");
                            }
                            $sumAllocated += $amt;
                            $allocationsToCreate[] = [
                                'purchase_id' => $p->id,
                                'amount' => $amt,
                                'notes' => $al['notes'] ?? null,
                            ];
                        }

                        if (abs($sumAllocated - $grandTotal) > 0.01) {
                            throw new \InvalidArgumentException("Total alokasi hutang (Rp ".number_format($sumAllocated, 0, ',', '.').") harus sama persis dengan total nilai retur (Rp ".number_format($grandTotal, 0, ',', '.').").");
                        }
                    } else {
                        // Alokasi FIFO otomatis jika alokasi tidak dikirim secara manual
                        $needed = $grandTotal;
                        foreach ($unpaidPurchases as $p) {
                            if ($needed <= 0) {
                                break;
                            }
                            $alreadyPaid = (float) $p->paid_amount;
                            $allocatedReturns = (float) $p->debtAllocations()
                                ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'COMPLETED'))
                                ->sum('allocated_amount');
                            $unallocatedDirect = (float) $p->returns()
                                ->where('status', 'COMPLETED')
                                ->where('refund_type', 'DEBT_REDUCTION')
                                ->whereDoesntHave('debtAllocations')
                                ->sum('grand_total');
                            $rem = max(0.0, (float) $p->grand_total - $alreadyPaid - $allocatedReturns - $unallocatedDirect);
                            if ($rem <= 0) {
                                continue;
                            }
                            $allocAmount = min($needed, $rem);
                            $allocationsToCreate[] = [
                                'purchase_id' => $p->id,
                                'amount' => $allocAmount,
                                'notes' => 'Alokasi FIFO otomatis',
                            ];
                            $needed -= $allocAmount;
                        }
                    }
                }

                // Cek apakah langsung disetujui (Hanya diperbolehkan jika user adalah OWNER dan menyetujui langsung)
                $canAutoApprove = ($user->role === 'owner' && $request->boolean('approve_now'));
                $initialStatus = $canAutoApprove ? 'COMPLETED' : 'DRAFT';

                $returnRecord = PurchaseReturn::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'return_type' => 'FLEXIBLE',
                    'purchase_id' => null,
                    'supplier_id' => $supplier->id,
                    'return_number' => $returnNumber,
                    'return_date' => $request->return_date,
                    'refund_type' => $request->refund_type,
                    'subtotal' => $totalSubtotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $grandTotal,
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                    'status' => $initialStatus,
                    'created_by' => $userId,
                    'approved_by' => $canAutoApprove ? $userId : null,
                    'approved_at' => $canAutoApprove ? now() : null,
                ]);

                // Simpan Alokasi Pengurangan Hutang (purchase_return_debt_allocations)
                if ($request->refund_type === 'DEBT_REDUCTION') {
                    foreach ($allocationsToCreate as $al) {
                        PurchaseReturnDebtAllocation::create([
                            'company_id' => $company->id,
                            'store_id' => $store->id,
                            'purchase_return_id' => $returnRecord->id,
                            'purchase_id' => $al['purchase_id'],
                            'allocated_amount' => $al['amount'],
                            'notes' => $al['notes'] ?? null,
                        ]);
                    }
                }

                foreach ($itemsToProcess as $proc) {
                    PurchaseReturnItem::create([
                        'purchase_return_id' => $returnRecord->id,
                        'purchase_item_id' => null,
                        'product_id' => $proc['product_id'],
                        'product_unit_id' => $proc['product_unit_id'],
                        'quantity' => $proc['quantity'],
                        'base_quantity' => $proc['base_quantity'],
                        'unit_price' => $proc['unit_price'],
                        'subtotal' => $proc['subtotal'],
                        'condition' => $proc['condition'],
                        'notes' => $proc['notes'],
                    ]);

                    // Jika langsung disetujui, langsung kurangi stok
                    if ($canAutoApprove) {
                        $conv = (float) $proc['base_quantity'] / (float) $proc['quantity'];
                        $unitCost = (float) $proc['unit_price'] / ($conv > 0 ? $conv : 1.0);
                        $this->inventoryService->recordPurchaseReturn(
                            product: $proc['product'],
                            store: $store,
                            quantity: $proc['base_quantity'],
                            purchaseCostPerUnit: $unitCost,
                            referenceNumber: $returnNumber,
                            notes: "Retur Fleksibel ke {$supplier->name} ({$returnRecord->reason})",
                            userId: $userId,
                            isDamaged: ($proc['condition'] === 'DAMAGED')
                        );
                    }
                }

                // Jika langsung disetujui, langsung catat jurnal
                if ($canAutoApprove) {
                    $this->accountingService->recordPurchaseReturnJournal($returnRecord);
                }

                return $returnRecord;
            });
        } catch (\InvalidArgumentException|\App\Exceptions\InsufficientStockException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $purchaseReturn->status === 'COMPLETED'
                    ? "Retur fleksibel {$purchaseReturn->return_number} berhasil diproses dan disetujui."
                    : "Dokumen retur fleksibel {$purchaseReturn->return_number} berhasil dibuat (Status: DRAFT). Menunggu persetujuan Manajer.",
                'return' => $purchaseReturn->load('items.product'),
            ]);
        }

        $msg = $purchaseReturn->status === 'COMPLETED'
            ? "Retur fleksibel {$purchaseReturn->return_number} berhasil diproses dan disetujui!"
            : "Dokumen retur fleksibel {$purchaseReturn->return_number} berhasil disimpan sebagai DRAFT. Menunggu persetujuan Manajer.";

        return redirect()->route('purchases.returns.index')->with('success', $msg);
    }

    /**
     * Persetujuan Dokumen Retur Pembelian oleh Manajer / Owner.
     * Mengurangi stok fisik dan memposting jurnal akuntansi secara atomik.
     */
    public function approveReturn(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! in_array($user?->role, ['owner', 'manager'], true)) {
            abort(403, 'Hanya Owner atau Manajer yang berwenang menyetujui dokumen retur pembelian.');
        }

        $return = PurchaseReturn::with(['items.product', 'items.unit', 'store', 'company', 'supplier'])->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        if ($return->status !== 'DRAFT') {
            $msg = "Dokumen retur {$return->return_number} tidak dapat disetujui karena statusnya sudah {$return->status}.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        // Pemisahan Tugas (Segregation of Duties): Pembuat retur tidak boleh menyetujui retur buatannya sendiri (kecuali Owner)
        if ($user->role !== 'owner' && $return->created_by === $user->id) {
            $msg = 'Pelanggaran Pemisahan Tugas: Pembuat dokumen retur tidak boleh menyetujui retur buatannya sendiri.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }

            return back()->with('error', $msg);
        }

        try {
            DB::transaction(function () use ($return, $user) {
                /** @var PurchaseReturn $lockedReturn */
                $lockedReturn = PurchaseReturn::where('id', $return->id)->lockForUpdate()->firstOrFail();
                if ($lockedReturn->status !== 'DRAFT') {
                    throw new \InvalidArgumentException("Dokumen retur {$lockedReturn->return_number} sudah diproses sebelumnya.");
                }

                $store = $lockedReturn->store;
                $supplier = $lockedReturn->supplier;

                // 1. Validasi ulang & Kurangi Stok Fisik
                foreach ($lockedReturn->items as $it) {
                    $conv = (float) ($it->unit?->conversion_factor ?? 1.0);
                    if ($conv <= 0) {
                        $conv = 1.0;
                    }
                    $unitCost = (float) $it->unit_price / $conv;
                    $isDamaged = ($it->condition === 'DAMAGED');

                    $this->inventoryService->recordPurchaseReturn(
                        product: $it->product,
                        store: $store,
                        quantity: (float) $it->base_quantity,
                        purchaseCostPerUnit: $unitCost,
                        referenceNumber: $lockedReturn->return_number,
                        notes: "Retur ke {$supplier->name} ({$lockedReturn->reason})",
                        userId: $user->id,
                        isDamaged: $isDamaged
                    );
                }

                // 2. Validasi & Re-locking Faktur Alokasi jika DEBT_REDUCTION
                if ($lockedReturn->refund_type === 'DEBT_REDUCTION') {
                    $allocations = $lockedReturn->debtAllocations;
                    if ($allocations->isEmpty()) {
                        throw new \InvalidArgumentException("Dokumen retur pengurangan hutang tidak memiliki rincian alokasi faktur.");
                    }

                    $allocPurchaseIds = $allocations->pluck('purchase_id')->all();
                    $lockedPurchases = Purchase::whereIn('id', $allocPurchaseIds)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($allocations as $alloc) {
                        $p = $lockedPurchases->get($alloc->purchase_id);
                        if (! $p) {
                            throw new \InvalidArgumentException("Faktur pembelian teralokasi tidak ditemukan.");
                        }
                        $alreadyPaid = (float) $p->paid_amount;
                        $otherReturns = (float) $p->debtAllocations()
                            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', 'COMPLETED')->where('id', '!=', $lockedReturn->id))
                            ->sum('allocated_amount');
                        $unallocatedDirect = (float) $p->returns()
                            ->where('status', 'COMPLETED')
                            ->where('refund_type', 'DEBT_REDUCTION')
                            ->whereDoesntHave('debtAllocations')
                            ->sum('grand_total');

                        $currentRemaining = max(0.0, (float) $p->grand_total - $alreadyPaid - $otherReturns - $unallocatedDirect);

                        if ((float) $alloc->allocated_amount > $currentRemaining) {
                            throw new \InvalidArgumentException(
                                "Sisa hutang faktur [{$p->invoice_number}] tidak lagi mencukupi (Tersisa: Rp ".number_format($currentRemaining, 0, ',', '.').", Alokasi Retur: Rp ".number_format((float) $alloc->allocated_amount, 0, ',', '.')."). Transaksi dibatalkan."
                            );
                        }
                    }
                } elseif ($lockedReturn->refund_type === 'CASH') {
                    $lockedReturn->update([
                        'notes' => trim(($lockedReturn->notes ? $lockedReturn->notes.' | ' : '')."Fisik kas pengembalian diverifikasi oleh {$user->name}."),
                    ]);
                }

                // 3. Catat Jurnal Double Entry Seimbang
                $this->accountingService->recordPurchaseReturnJournal($lockedReturn);

                // 4. Update Status Dokumen
                $lockedReturn->update([
                    'status' => 'COMPLETED',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);
            });
        } catch (\InvalidArgumentException|\App\Exceptions\InsufficientStockException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Dokumen retur {$return->return_number} berhasil disetujui dan stok telah dipotong.",
                'return' => $return->fresh(['creator', 'approver', 'items']),
            ]);
        }

        return back()->with('success', "Dokumen retur {$return->return_number} berhasil disetujui!");
    }

    /**
     * Pembatalan Dokumen Retur Pembelian Berstatus DRAFT.
     */
    public function cancelReturn(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if (! in_array($user?->role, ['owner', 'manager', 'purchasing'], true)) {
            abort(403, 'Anda tidak memiliki hak untuk membatalkan dokumen retur.');
        }

        $return = PurchaseReturn::where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        if ($return->status !== 'DRAFT') {
            $msg = "Dokumen retur {$return->return_number} berstatus {$return->status} tidak dapat dibatalkan.";
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        // Hapus alokasi draft jika ada
        $return->debtAllocations()->delete();

        $return->update([
            'status' => 'CANCELLED',
            'notes' => trim(($return->notes ? $return->notes.' | ' : '')."Dibatalkan oleh {$user->name} pada ".now()->toDateTimeString()),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Dokumen retur {$return->return_number} berhasil dibatalkan.",
                'return' => $return,
            ]);
        }

        return back()->with('success', "Dokumen retur {$return->return_number} berhasil dibatalkan.");
    }

    /**
     * Ambil daftar faktur pembelian tempo belum lunas milik supplier (untuk alokasi retur).
     */
    public function supplierUnpaidInvoices(Request $request, string $supplierId): JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storeId = $request->query('store_id');
        $store = $storeId ? Store::find($storeId) : Store::where('company_id', $company->id)->first();

        $purchases = Purchase::where('company_id', $company->id)
            ->when($store, fn ($q) => $q->where('store_id', $store->id))
            ->where('supplier_id', $supplierId)
            ->where('payment_type', 'TEMPO')
            ->where('status', 'RECEIVED')
            ->whereRaw('paid_amount < grand_total')
            ->with(['debtAllocations.purchaseReturn', 'returns'])
            ->orderBy('due_date')
            ->get()
            ->map(function ($p) {
                $alreadyPaid = (float) $p->paid_amount;
                $allocatedReturns = (float) $p->debtAllocations
                    ->where('purchaseReturn.status', 'COMPLETED')
                    ->sum('allocated_amount');
                $unallocatedDirect = (float) $p->returns
                    ->where('status', 'COMPLETED')
                    ->where('refund_type', 'DEBT_REDUCTION')
                    ->filter(fn ($r) => $r->debtAllocations->isEmpty())
                    ->sum('grand_total');

                $remaining = max(0.0, (float) $p->grand_total - $alreadyPaid - $allocatedReturns - $unallocatedDirect);

                return [
                    'id' => $p->id,
                    'invoice_number' => $p->invoice_number ?: $p->reference_number,
                    'purchase_date' => $p->purchase_date->format('Y-m-d'),
                    'due_date' => $p->due_date ? $p->due_date->format('Y-m-d') : '-',
                    'grand_total' => (float) $p->grand_total,
                    'paid_amount' => $alreadyPaid,
                    'returned_amount' => $allocatedReturns + $unallocatedDirect,
                    'remaining_debt' => $remaining,
                ];
            })
            ->filter(fn ($item) => $item['remaining_debt'] > 0)
            ->values();

        return response()->json([
            'success' => true,
            'invoices' => $purchases,
        ]);
    }

    /**
     * Export Daftar Faktur Pembelian ke CSV / Excel spreadsheet.
     */
    public function exportPurchases(Request $request): StreamedResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $query = Purchase::with(['supplier', 'store'])
            ->where('company_id', $company->id);

        if ($q = trim($request->get('q', ''))) {
            $likeOp = DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($w) use ($q, $likeOp) {
                $w->where('reference_number', $likeOp, "%{$q}%")
                    ->orWhere('invoice_number', $likeOp, "%{$q}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', $likeOp, "%{$q}%"));
            });
        }

        if ($supplierId = $request->get('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($startDate = $request->get('start_date')) {
            $query->whereDate('purchase_date', '>=', $startDate);
        }
        if ($endDate = $request->get('end_date')) {
            $query->whereDate('purchase_date', '<=', $endDate);
        }

        if ($status = $request->get('status')) {
            if ($status === 'TEMPO') {
                $query->where('payment_type', 'TEMPO');
            } elseif ($status === 'PAID') {
                $query->whereColumn('paid_amount', '>=', 'grand_total');
            } elseif ($status === 'UNPAID') {
                $query->whereColumn('paid_amount', '<', 'grand_total');
            }
        }

        $purchases = $query->orderByDesc('purchase_date')->orderByDesc('created_at')->get();

        $filename = 'daftar_faktur_pembelian_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($purchases) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'No. Bukti (Internal)',
                'No. Faktur Supplier',
                'Tanggal Pembelian',
                'Jatuh Tempo',
                'Nama Supplier',
                'Status Pajak / NPWP',
                'Metode Pembayaran',
                'Status Faktur',
                'Subtotal (Rp)',
                'Diskon Global (Rp)',
                'Ongkos Kirim (Landed Cost Rp)',
                'PPN Masukan (Rp)',
                'Grand Total (Rp)',
                'Terbayar (Rp)',
                'Sisa Hutang (Rp)',
            ]);

            foreach ($purchases as $p) {
                $remaining = max(0, (float) $p->grand_total - (float) $p->paid_amount);
                fputcsv($handle, [
                    $p->reference_number,
                    $p->invoice_number,
                    $p->purchase_date ? date('d/m/Y', strtotime($p->purchase_date)) : '-',
                    $p->due_date ? date('d/m/Y', strtotime($p->due_date)) : '-',
                    $p->supplier?->name ?? 'Supplier Umum',
                    $p->supplier?->is_pkp ? 'PKP (' . ($p->supplier?->tax_identification_number ?: '-') . ')' : 'Non-PKP',
                    $p->payment_type,
                    $p->status,
                    number_format((float) $p->subtotal, 2, ',', '.'),
                    number_format((float) $p->discount_amount, 2, ',', '.'),
                    number_format((float) ($p->shipping_cost ?? 0), 2, ',', '.'),
                    number_format((float) $p->tax_amount, 2, ',', '.'),
                    number_format((float) $p->grand_total, 2, ',', '.'),
                    number_format((float) $p->paid_amount, 2, ',', '.'),
                    number_format($remaining, 2, ',', '.'),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

}
