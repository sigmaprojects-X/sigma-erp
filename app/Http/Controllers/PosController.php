<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\CashDrop;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\TaxConfiguration;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Promotion;
use App\Services\LoyaltyPointService;
use App\Services\PosService;
use App\Services\PricingEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected PosService $posService,
        protected PricingEngineService $pricingEngine,
        protected LoyaltyPointService $loyaltyPointService
    ) {}

    /**
     * Tampilan Utama POS Kasir (High-Speed Desktop POS Viewport).
     */
    public function index(Request $request): View
    {
        [$company, $store] = $this->resolveCompanyAndStore($request);

        // Ambil kasir aktif dari request / user yang sedang login / session
        // ISOLASI CABANG: Hanya ambil kasir di toko/cabang yang sama
        $allCashiers = User::where('is_active', true)
            ->where('store_id', $store->id)
            ->orderBy('name')
            ->get();
        $authUser = Auth::user();

        // Jika user yang login adalah kasir, KUNCI mati ke kasir tersebut (tidak boleh oper akun tanpa login)
        if ($authUser && $authUser->role === 'cashier') {
            $cashier = $authUser;
            session(['pos_active_cashier_id' => $cashier->id]);
        } elseif ($request->filled('cashier_id')) {
            $cashier = User::where('store_id', $store->id)->find($request->input('cashier_id')) ?? $authUser;
            session(['pos_active_cashier_id' => $cashier?->id]);
        } else {
            $cashier = $authUser ?: (session('pos_active_cashier_id') ? User::where('store_id', $store->id)->find(session('pos_active_cashier_id')) : null);
            if (! $cashier) {
                $cashier = $authUser ?? User::where('store_id', $store->id)->first() ?? User::first();
            }
            session(['pos_active_cashier_id' => $cashier?->id]);
        }

        $activeShift = CashierShift::where('store_id', $store->id)
            ->where('cashier_id', $cashier->id)
            ->where('status', 'OPEN')
            ->with('cashier')
            ->first();

        $customerTiers = CustomerTier::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get();

        $customers = Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->with('tier')
            ->orderByRaw("code = 'CUST-UMUM' DESC")
            ->orderBy('name')
            ->get();

        $salesList = User::orderBy('name')->get();
        $taxConfigurations = TaxConfiguration::where('company_id', $company->id)->get();

        // Konfigurasi Pajak Terpusat dari Pengaturan Pajak ERP Back-Office (AGENTS.md Section 5)
        $activeTaxConfig = TaxConfiguration::where('company_id', $company->id)
            ->where('is_tax_enabled', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if ($activeTaxConfig && $activeTaxConfig->is_tax_enabled) {
            $erpTaxMode = $activeTaxConfig->is_tax_inclusive ? 'PPN_INCLUDE' : 'PPN_EXCLUDE';
            $erpTaxRatePercent = (float) $activeTaxConfig->tax_rate_percent;
            $erpTaxRate = $erpTaxRatePercent / 100;
            $erpTaxName = $activeTaxConfig->tax_name ?: 'PPN';
            $erpTaxLabel = $activeTaxConfig->is_tax_inclusive
                ? "PKP ({$erpTaxName} {$erpTaxRatePercent}% Include)"
                : "PKP ({$erpTaxName} {$erpTaxRatePercent}% Exclude)";
        } elseif (($company->is_tax_registered ?? false) && !in_array($company->tax_mode, ['NON', 'NON_TAX'])) {
            $erpTaxMode = in_array($company->tax_mode, ['INCLUSIVE', 'PPN_INCLUDE']) ? 'PPN_INCLUDE' : 'PPN_EXCLUDE';
            $erpTaxRatePercent = (float) ($company->default_tax_rate ?: 11.00);
            $erpTaxRate = $erpTaxRatePercent / 100;
            $erpTaxName = 'PPN';
            $erpTaxLabel = $erpTaxMode === 'PPN_INCLUDE'
                ? "PKP ({$erpTaxName} {$erpTaxRatePercent}% Include)"
                : "PKP ({$erpTaxName} {$erpTaxRatePercent}% Exclude)";
        } else {
            $erpTaxMode = 'NON';
            $erpTaxRate = 0.0;
            $erpTaxRatePercent = 0.0;
            $erpTaxName = 'Non-PKP';
            $erpTaxLabel = 'Non-PKP (Bebas PPN 0%)';
        }

        $dbBankAccounts = BankAccount::where('company_id', $company->id)->where('is_active', true)->get();
        if ($dbBankAccounts->isNotEmpty()) {
            $bankAccounts = $dbBankAccounts->map(function ($b) {
                return [
                    'code' => $b->bank_code,
                    'bank_name' => $b->bank_name,
                    'account_number' => $b->account_number,
                    'account_holder' => $b->account_holder,
                ];
            })->toArray();
        } else {
            $bankAccounts = [
                ['code' => '1002-001', 'bank_name' => 'BCA', 'account_number' => '123-456-7890', 'account_holder' => 'PT SIGMA RETAIL INDONESIA'],
                ['code' => '1002-002', 'bank_name' => 'Mandiri', 'account_number' => '098-765-4321', 'account_holder' => 'PT SIGMA RETAIL INDONESIA'],
                ['code' => '1002-003', 'bank_name' => 'BRI', 'account_number' => '567-890-1234', 'account_holder' => 'PT SIGMA RETAIL INDONESIA'],
                ['code' => '1002-004', 'bank_name' => 'BNI', 'account_number' => '321-654-0987', 'account_holder' => 'PT SIGMA RETAIL INDONESIA'],
            ];
        }

        $recentTransactions = $activeShift
            ? PosTransaction::where('cashier_shift_id', $activeShift->id)
                ->with(['items.product', 'items.unit'])
                ->latest()
                ->take(10)
                ->get()
            : collect();

        // Hitung estimasi saldo kas di laci kasir saat ini & peringatan Kas Drop (Security Rule)
        $cashDropThreshold = (float) ($company->cashier_cash_drop_threshold ?? 2000000.00);
        $currentDrawerCash = 0.0;
        $cashDropWarning = false;
        if ($activeShift) {
            $startingCash = (float) $activeShift->starting_cash;
            $cashSales = PosTransaction::where('cashier_shift_id', $activeShift->id)
                ->where('status', 'COMPLETED')
                ->where(function ($q) {
                    $q->where('payment_method', 'CASH')
                      ->orWhere(function ($sq) {
                          $sq->where('payment_method', 'SPLIT')->where('split_cash_amount', '>', 0);
                      });
                })
                ->get()
                ->sum(function ($tx) {
                    return $tx->payment_method === 'SPLIT'
                        ? (float) $tx->split_cash_amount
                        : ((float) $tx->paid_amount - (float) $tx->change_amount);
                });

            $cashRefunds = (float) PosReturn::where('cashier_shift_id', $activeShift->id)
                ->where('refund_method', 'CASH')
                ->where('status', 'COMPLETED')
                ->sum('total_refund_amount');

            $cashDrops = (float) CashDrop::where('cashier_shift_id', $activeShift->id)->sum('amount');
            $currentDrawerCash = max(0.0, $startingCash + $cashSales - $cashRefunds - $cashDrops);

            if (($company->cashier_cash_drop_warning_enabled ?? true) && $currentDrawerCash >= $cashDropThreshold) {
                $cashDropWarning = true;
            }
        }

        $loyaltyProgram = $this->loyaltyPointService->getActiveProgram($company);

        // Promosi Aktif untuk Deteksi Tebus Murah & Buy X Get Y di Kasir
        $activePromotions = Promotion::where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) use ($store) {
                $q->whereNull('store_id')->orWhere('store_id', $store->id);
            })
            ->with(['buyProduct.units', 'rewardProduct.units', 'rewardProduct.prices'])
            ->get();

        return view('pos.index', compact(
            'company',
            'store',
            'cashier',
            'allCashiers',
            'activeShift',
            'customerTiers',
            'customers',
            'salesList',
            'taxConfigurations',
            'bankAccounts',
            'recentTransactions',
            'currentDrawerCash',
            'cashDropWarning',
            'cashDropThreshold',
            'loyaltyProgram',
            'activePromotions',
            'erpTaxMode',
            'erpTaxRate',
            'erpTaxRatePercent',
            'erpTaxName',
            'erpTaxLabel'
        ));
    }

    /**
     * API Pencarian Cepat Kasir (Barcode Instant Match & PostgreSQL Trigram).
     * Menampilkan Stok Tersedia dan Harga Jual Otomatis (AGENTS.md Section 2 & 3).
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->input('q', '');
        [$company, $store] = $this->resolveCompanyAndStore($request);

        $results = $this->pricingEngine->search($company, $query, $store, 15);

        // Ambil stok produk sekaligus untuk performa tinggi
        $productIds = $results->map(fn ($item) => $item['product']->id)->unique()->values();
        $stocks = \App\Models\ProductStock::where('store_id', $store->id)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');

        // Tentukan customer tier yang aktif (default: retail / umum)
        $tierId = $request->input('customer_tier_id');
        $tier = $tierId ? CustomerTier::where('company_id', $company->id)->find($tierId) : null;
        if (! $tier) {
            $tier = CustomerTier::where('company_id', $company->id)->where('is_default', true)->first()
                ?? CustomerTier::where('company_id', $company->id)->first();
        }

        $formatted = $results->map(function ($item) use ($stocks, $tier, $store) {
            $product = $item['product'];
            $matchedUnit = $item['matched_unit'];

            // Hitung Stok Tersedia: Physical - Reserved - Quarantined (AGENTS.md Section 2)
            $stockRecord = $stocks->get($product->id);
            $availableStockBase = $stockRecord ? (float) $stockRecord->available_quantity : 0.0;

            $targetUnit = $matchedUnit ?? $product->units->firstWhere('is_base_unit', true) ?? $product->units->first();
            $factor = ($targetUnit && (float) $targetUnit->conversion_factor > 0) ? (float) $targetUnit->conversion_factor : 1.0;
            $stockInTargetUnit = $factor > 0 ? ($availableStockBase / $factor) : $availableStockBase;

            // Hitung Harga Jual berdasarkan Satuan & Customer Tier (AGENTS.md Section 3)
            $sellingPrice = 0.0;
            if ($targetUnit && $tier) {
                try {
                    $sellingPrice = $this->pricingEngine->calculatePrice(
                        product: $product,
                        unit: $targetUnit,
                        tier: $tier,
                        quantity: 1.0,
                        store: $store
                    );
                } catch (\Throwable $e) {
                    $sellingPrice = 0.0;
                }
            }

            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'type' => $product->type ?: Product::TYPE_BARANG,
                'tracks_inventory' => $product->tracksInventory(),
                'category' => $product->category,
                'matched_unit_id' => $matchedUnit?->id,
                'matched_unit_code' => $matchedUnit?->unit_code,
                'barcode' => $item['barcode'],
                'match_type' => $item['match_type'],
                'scale_weight' => $item['scale_weight'] ?? null,
                'stock' => round($stockInTargetUnit, 2),
                'stock_base' => round($availableStockBase, 2),
                'stock_formatted' => $product->tracksInventory()
                    ? (floor($stockInTargetUnit) == $stockInTargetUnit ? (int) $stockInTargetUnit : number_format($stockInTargetUnit, 2, ',', '.')) . ' ' . ($targetUnit?->unit_code ?? '')
                    : 'Tanpa Stok',
                'selling_price' => (float) $sellingPrice,
                'selling_price_formatted' => 'Rp ' . number_format($sellingPrice, 0, ',', '.'),
                'units' => $product->units->where('is_active', true)->values()->map(fn ($u) => [
                    'id' => $u->id,
                    'code' => $u->unit_code,
                    'name' => $u->unit_name,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'is_base' => (bool) $u->is_base_unit,
                ]),
            ];
        });

        return response()->json($formatted);
    }

    /**
     * API Cek Harga & Ketersediaan Stok Real-Time untuk Kasir (F9 Price Checker).
     * Memungkinkan kasir memeriksa harga semua tier/satuan dan sisa stok toko
     * tanpa harus memasukkan barang ke keranjang transaksi aktif.
     */
    public function lookupItem(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        if (empty($query)) {
            return response()->json(['success' => false, 'message' => 'Masukkan kode barcode atau nama barang.'], 400);
        }

        [$company, $store] = $this->resolveCompanyAndStore($request);

        // Cari via Barcode, SKU, atau Nama Produk
        $searchResult = $this->pricingEngine->search($company, $query, $store, 1)->first();
        $product = $searchResult ? $searchResult['product'] : null;

        if (! $product) {
            $driver = DB::connection()->getDriverName();
            $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
            $product = Product::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('is_point_of_sale', true)
                ->where('sale_status', '!=', 'DISCONTINUED')
                ->where(function ($q) use ($query, $likeOp) {
                    $q->where('sku', $query)
                      ->orWhere('name', $likeOp, "%{$query}%")
                      ->orWhereHas('barcodes', function ($bq) use ($query) {
                          $bq->where('barcode', $query);
                      });
                })
                ->with(['units', 'barcodes', 'prices'])
                ->first();
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Produk dengan kode / barcode "' . $query . '" tidak ditemukan.',
            ], 404);
        }

        // Ambil data stok di toko saat ini
        $stock = ProductStock::where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->first();

        $physicalQty = (float) ($stock?->physical_quantity ?? 0);
        $reservedQty = (float) ($stock?->reserved_quantity ?? 0);
        $quarantinedQty = (float) ($stock?->quarantined_quantity ?? 0);
        $availableQty = max(0, $physicalQty - $reservedQty - $quarantinedQty);

        // Ambil semua customer tier yang ada
        $customerTiers = CustomerTier::where('company_id', $company->id)->orderBy('name')->get();

        // Data kemasan / satuan lengkap dengan harga di setiap tier pelanggan
        $unitsData = $product->units->where('is_active', true)->values()->map(function ($unit) use ($product, $customerTiers, $store, $availableQty) {
            $factor = (float) $unit->conversion_factor > 0 ? (float) $unit->conversion_factor : 1.0;
            $stockInUnit = $factor > 0 ? ($availableQty / $factor) : $availableQty;

            $tierPrices = $customerTiers->map(function ($tier) use ($product, $unit, $store) {
                try {
                    $price = $this->pricingEngine->calculatePrice(
                        product: $product,
                        unit: $unit,
                        tier: $tier,
                        quantity: 1.0,
                        store: $store
                    );
                } catch (\Throwable $e) {
                    $price = 0.0;
                }
                return [
                    'tier_id' => $tier->id,
                    'tier_name' => $tier->name,
                    'is_default' => (bool) $tier->is_default,
                    'price' => (float) $price,
                    'price_formatted' => 'Rp ' . number_format($price, 0, ',', '.'),
                ];
            });

            return [
                'id' => $unit->id,
                'code' => $unit->unit_code,
                'name' => $unit->unit_name,
                'conversion_factor' => $factor,
                'is_base' => (bool) $unit->is_base_unit,
                'stock' => round($stockInUnit, 2),
                'stock_formatted' => (floor($stockInUnit) == $stockInUnit ? (int) $stockInUnit : number_format($stockInUnit, 2, ',', '.')) . ' ' . $unit->unit_code,
                'tier_prices' => $tierPrices,
            ];
        });

        $primaryBarcode = $product->barcodes->firstWhere('is_primary', true)?->barcode
            ?? $product->barcodes->first()?->barcode
            ?? $product->sku;

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category ?? 'Umum',
                'brand' => $product->brand ?? '-',
                'barcode' => $primaryBarcode,
                'is_active' => (bool) $product->is_active,
                'stock_physical' => $physicalQty,
                'stock_reserved' => $reservedQty,
                'stock_quarantined' => $quarantinedQty,
                'stock_available' => $availableQty,
                'stock_available_formatted' => number_format($availableQty, 0, ',', '.'),
                'units' => $unitsData,
            ]
        ]);
    }

    /**
     * API Kalkulasi Harga 3 Dimensi Otomatis (Locked / Zero Manual Price Override).
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|uuid',
            'product_unit_id' => 'required|uuid',
            'customer_tier_id' => 'required|uuid',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $product = Product::where('company_id', $company->id)->findOrFail($request->product_id);
        $unit = ProductUnit::where('company_id', $company->id)->where('product_id', $product->id)->findOrFail($request->product_unit_id);
        $tier = CustomerTier::where('company_id', $company->id)->findOrFail($request->customer_tier_id);

        try {
            $unitPrice = $this->pricingEngine->calculatePrice(
                product: $product,
                unit: $unit,
                tier: $tier,
                quantity: (float) $request->quantity,
                store: $store
            );

            $quantity = (float) $request->quantity;
            $subtotal = $unitPrice * $quantity;

            return response()->json([
                'success' => true,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API Sinkronisasi Otomatis 3D Pricing untuk Seluruh Item di Keranjang Saat Level Pelanggan Berubah.
     */
    public function recalculateCart(Request $request): JsonResponse
    {
        $request->validate([
            'customer_tier_id' => 'required|uuid',
            'items' => 'required|array',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $tier = CustomerTier::where('company_id', $company->id)->findOrFail($request->customer_tier_id);

        try {
            $recalculated = [];
            foreach ($request->items as $index => $item) {
                $product = Product::where('company_id', $company->id)->findOrFail($item['product_id']);
                if ($product->sale_status === 'DISCONTINUED' || ! $product->is_active || ! $product->is_point_of_sale) {
                    throw new \RuntimeException("Produk [{$product->name}] berstatus Discontinue / Non-aktif dan tidak dapat diperjualbelikan.");
                }
                $unit = ProductUnit::where('company_id', $company->id)->where('product_id', $product->id)->findOrFail($item['product_unit_id']);
                $qty = (float) $item['quantity'];

                $unitPrice = $this->pricingEngine->calculatePrice(
                    product: $product,
                    unit: $unit,
                    tier: $tier,
                    quantity: $qty,
                    store: $store
                );

                $discountAmount = (float) ($item['discount_amount'] ?? 0.00);
                $subtotal = $unitPrice * $qty;
                $total = max(0.0, $subtotal - $discountAmount);

                $recalculated[] = [
                    'index' => $index,
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                ];
            }

            return response()->json([
                'success' => true,
                'tier' => [
                    'id' => $tier->id,
                    'code' => $tier->code,
                    'name' => $tier->name,
                ],
                'items' => $recalculated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API Validasi Voucher & Gift Card Nyata dari Database (Single-Use, Expiry, Min Spend).
     */
    public function validateVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required_without:voucher_code|string',
            'voucher_code' => 'required_without:code|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $code = strtoupper(trim($request->code ?? $request->voucher_code));
        $subtotal = (float) $request->subtotal;

        $voucher = Voucher::where('company_id', $company->id)
            ->where('code', $code)
            ->first();

        if (! $voucher) {
            return response()->json([
                'success' => false,
                'message' => "Kode voucher [{$code}] tidak ditemukan dalam database.",
            ], 404);
        }

        if ($voucher->status !== 'ISSUED') {
            return response()->json([
                'success' => false,
                'message' => "Voucher [{$code}] sudah pernah digunakan atau tidak aktif (Status: {$voucher->status}).",
            ], 422);
        }

        if (now()->lt($voucher->valid_from) || now()->gt($voucher->valid_until)) {
            return response()->json([
                'success' => false,
                'message' => "Voucher [{$code}] telah melewati masa berlaku (Expired).",
            ], 422);
        }

        if ($subtotal < (float) $voucher->min_spend) {
            return response()->json([
                'success' => false,
                'message' => 'Belanja belum mencapai syarat minimal Rp '.number_format((float) $voucher->min_spend)." untuk voucher [{$code}].",
            ], 422);
        }

        $discount = 0.0;
        if ($voucher->type === 'VALUE') {
            $discount = min($subtotal, (float) $voucher->amount);
        } elseif ($voucher->type === 'DISCOUNT_PERCENT') {
            $discount = round($subtotal * ((float) $voucher->amount / 100), 2);
        }

        return response()->json([
            'success' => true,
            'message' => "Voucher [{$code}] valid! Diskon sebesar Rp ".number_format($discount).' berhasil diterapkan.',
            'voucher' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'type' => $voucher->type,
                'amount' => (float) $voucher->amount,
                'discount' => $discount,
                'discount_amount' => $discount,
            ],
        ]);
    }

    /**
     * Buka Shift Kasir (Input Modal Awal Kasir).
     */
    public function openShift(Request $request): JsonResponse
    {
        $request->validate([
            'starting_cash' => 'required|numeric|min:0',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $user = $request->user() ?? Auth::user();

        // Tentukan kasir yang membuka shift
        $cashierId = $request->input('cashier_id') ?: session('pos_active_cashier_id');
        if ($cashierId && $user && in_array($user->role, ['owner', 'manager', 'store_head'], true)) {
            $cashier = User::where('store_id', $store->id)->where('is_active', true)->findOrFail($cashierId);
        } elseif ($user && $user->store_id === $store->id) {
            $cashier = $user;
        } else {
            $cashier = User::where('store_id', $store->id)
                ->where('role', 'cashier')
                ->where('is_active', true)
                ->orderBy('name')
                ->firstOrFail();
        }

        $shift = $this->posService->openShift(
            company: $company,
            store: $store,
            cashierId: $cashier->id,
            startingCash: (float) $request->starting_cash
        );

        session(['pos_active_cashier_id' => $cashier->id]);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dibuka.',
            'shift' => $shift,
        ]);
    }

    /**
     * Dapatkan instance Company dan Store aktif berdasarkan user yang login / request.
     * Mendukung multi-cabang (multi-store) tanpa hardcoding STR-001 / SIGMA-HO.
     *
     * @return array{0: Company, 1: Store}
     */
    protected function resolveCompanyAndStore(Request $request): array
    {
        $companyService = app(\App\Services\CompanyService::class);
        $user = $request->user() ?? Auth::user();

        if ($user && $user->store && $user->store->company && $user->role !== 'owner') {
            $company = $user->store->company;
            $store = $user->store;
        } else {
            $company = $companyService->getActiveCompany();
            if ($user && $user->store && $user->store->company_id === $company->id) {
                $store = $user->store;
            } elseif ($request->filled('store_id')) {
                $store = Store::where('company_id', $company->id)->find($request->store_id)
                    ?? Store::where('company_id', $company->id)->where('is_main_store', true)->first()
                    ?? Store::where('company_id', $company->id)->first()
                    ?? Store::where('company_id', $company->id)->firstOrFail();
            } else {
                $store = Store::where('company_id', $company->id)->where('is_main_store', true)->first()
                    ?? Store::where('company_id', $company->id)->first()
                    ?? Store::where('company_id', $company->id)->firstOrFail();
            }
        }

        return [$company, $store];
    }

    /**
     * Validasi kepemilikan & otorisasi akses terhadap shift kasir.
     *
     * - Role cashier: wajib shift.cashier_id == auth()->id(), shift.status == OPEN, dan shift.store_id == user.store_id.
     * - Role manager/store_head/owner: diizinkan atas shift di toko yang mereka kelola.
     * - Role lainnya: ditolak dengan 403.
     */
    protected function authorizeShiftAccess(Request $request, string $shiftId): CashierShift
    {
        [$company, $store] = $this->resolveCompanyAndStore($request);
        $shift = CashierShift::where('company_id', $company->id)->findOrFail($shiftId);
        $user = $request->user() ?? Auth::user();

        if (! $user) {
            abort(403, 'Akses ditolak: Pengguna belum terautentikasi.');
        }

        if ($user->role === 'cashier') {
            if (
                $shift->cashier_id !== $user->id
                || $shift->status !== 'OPEN'
                || empty($user->store_id)
                || $shift->store_id !== $user->store_id
            ) {
                abort(403, 'Akses ditolak: Kasir hanya dapat mengakses shift miliknya sendiri yang berstatus OPEN di tokonya.');
            }

            return $shift;
        }

        if (in_array($user->role, ['manager', 'store_head', 'owner'], true)) {
            $managesStore = (! empty($user->store_id) && $shift->store_id === $user->store_id)
                || ($user->role === 'owner' && empty($user->store_id));

            if (! $managesStore) {
                abort(403, 'Akses ditolak: Shift berada di luar toko yang Anda kelola.');
            }

            return $shift;
        }

        abort(403, 'Akses ditolak: Role Anda tidak memiliki izin untuk mengakses shift kasir.');
    }

    /**
     * Setor Kas ke Brankas di Tengah Shift (Cash Drop).
     */
    public function cashDrop(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|uuid',
            'amount' => 'required|numeric|min:1000',
            'reason' => 'nullable|string|max:255',
        ]);

        $shift = $this->authorizeShiftAccess($request, $request->shift_id);

        $drop = $this->posService->recordCashDrop(
            shift: $shift,
            amount: (float) $request->amount,
            reason: $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => 'Cash drop berhasil dicatat.',
            'cash_drop' => $drop,
        ]);
    }

    /**
     * Tutup Shift Kasir (Blind Closing Rule - Nilai ekspektasi kas dilarang tampil di form).
     */
    public function blindClose(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|uuid',
            'actual_cash_counted' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = $this->authorizeShiftAccess($request, $request->shift_id);

        $closedShift = $this->posService->blindCloseShift(
            shift: $shift,
            actualCashCounted: (float) $request->actual_cash_counted,
            notes: $request->notes
        );

        $discrepancy = (float) $closedShift->cash_discrepancy;
        $statusMsg = abs($discrepancy) < 0.01
            ? 'Kas seimbang sempurna (Balance).'
            : ($discrepancy > 0
                ? 'Kas Lebih (Overage) sebesar Rp '.number_format($discrepancy).'.'
                : 'Kas Kurang (Shortage) sebesar Rp '.number_format(abs($discrepancy)).'.');

        return response()->json([
            'success' => true,
            'message' => "Shift berhasil ditutup. {$statusMsg}",
            'discrepancy' => $discrepancy,
            'shift' => $closedShift,
        ]);
    }

    /**
     * Eksekusi Checkout Transaksi Kasir POS.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|uuid',
            'idempotency_key' => 'nullable|uuid',
            'customer_tier_id' => 'nullable|uuid',
            'customer_id' => 'nullable|uuid',
            'sales_person_id' => 'nullable|uuid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.custom_unit_price' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'manager_pin' => 'nullable|string',
            'payment_method' => 'required|string|in:CASH,QRIS,TRANSFER,VOUCHER,TEMPO,SPLIT',
            'split_payment_method' => 'nullable|string|in:CASH,TRANSFER,QRIS',
            'split_cash_amount' => 'nullable|numeric|min:0',
            'split_non_cash_amount' => 'nullable|numeric|min:0',
            'split_non_cash_method' => 'nullable|string|in:QRIS,TRANSFER',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'payment_reference' => 'nullable|string|max:100',
            'paid_amount' => 'required|numeric|min:0',
            'credit_amount' => 'nullable|numeric|min:0',
            'voucher_code' => 'nullable|string',
            'redeem_points' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $shift = $this->authorizeShiftAccess($request, $request->shift_id);
        $company = $shift->company ?? Company::findOrFail($shift->company_id);

        $defaultTier = CustomerTier::where('company_id', $company->id)
            ->where('is_default', true)
            ->first()
            ?? CustomerTier::where('company_id', $company->id)->firstOrFail();

        $customer = $request->filled('customer_id')
            ? Customer::where('company_id', $company->id)->find($request->customer_id)
            : null;

        $tier = ($customer && $customer->customerTier) ? $customer->customerTier : $defaultTier;

        // Aturan Pajak Ditetapkan Terpusat oleh Pengaturan Pajak ERP (AGENTS.md Section 5 - Zero Kasir Override)
        $taxConfig = TaxConfiguration::where('company_id', $company->id)
            ->where('is_tax_enabled', true)
            ->where(function ($q) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', now()->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if ($taxConfig && $taxConfig->is_tax_enabled) {
            $enforcedTaxMode = $taxConfig->is_tax_inclusive ? 'PPN_INCLUDE' : 'PPN_EXCLUDE';
        } elseif (($company->is_tax_registered ?? false) && ! in_array($company->tax_mode, ['NON', 'NON_TAX'], true)) {
            $enforcedTaxMode = in_array($company->tax_mode, ['INCLUSIVE', 'PPN_INCLUDE'], true) ? 'PPN_INCLUDE' : 'PPN_EXCLUDE';
        } else {
            $enforcedTaxMode = 'NON';
        }

        try {
            $transaction = $this->posService->checkout(
                shift: $shift,
                tier: $tier,
                cartItems: $request->items,
                paymentMethod: $request->payment_method,
                paidAmount: (float) $request->paid_amount,
                voucherCode: $request->voucher_code,
                notes: $request->notes,
                customerId: $customer?->id,
                taxAmount: null,
                taxMode: $enforcedTaxMode,
                salesPersonId: $request->sales_person_id,
                bankName: $request->bank_name,
                bankAccountNumber: $request->bank_account_number,
                paymentReference: $request->payment_reference,
                creditAmount: $request->credit_amount !== null ? (float) $request->credit_amount : null,
                splitPaymentMethod: $request->split_payment_method,
                redeemPoints: (int) ($request->redeem_points ?? 0),
                roundingAmount: null,
                splitCashAmount: $request->split_cash_amount !== null ? (float) $request->split_cash_amount : null,
                splitNonCashAmount: $request->split_non_cash_amount !== null ? (float) $request->split_non_cash_amount : null,
                splitNonCashMethod: $request->split_non_cash_method,
                managerPin: $request->input('manager_pin'),
                idempotencyKey: $request->input('idempotency_key')
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil diproses.',
                'invoice_number' => $transaction->invoice_number,
                'transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
                'grand_total' => (float) $transaction->grand_total,
                'rounding_amount' => (float) ($transaction->rounding_amount ?? 0),
                'paid_amount' => (float) $transaction->paid_amount,
                'credit_amount' => (float) ($transaction->credit_amount ?? 0),
                'change_amount' => (float) $transaction->change_amount,
                'split_cash_amount' => (float) ($transaction->split_cash_amount ?? 0),
                'split_non_cash_amount' => (float) ($transaction->split_non_cash_amount ?? 0),
                'split_non_cash_method' => $transaction->split_non_cash_method,
                'points_redeemed' => (int) ($transaction->points_redeemed ?? 0),
                'points_discount' => (float) ($transaction->points_discount_amount ?? 0),
                'points_earned' => (int) ($transaction->points_earned ?? 0),
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (
            \InvalidArgumentException
            | \DomainException
            | \App\Exceptions\InsufficientStockException
            | \App\Exceptions\NegativeInventoryException
            | \App\Exceptions\UnbalancedJournalException $e
        ) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            if (! ($e instanceof \Illuminate\Database\QueryException) && ! ($e instanceof \PDOException)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            Log::error('POS Checkout Database Error: '.$e->getMessage(), [
                'exception' => $e,
                'shift_id' => $shift->id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses transaksi. Silakan coba lagi atau hubungi administrator.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('POS Checkout Unexpected Error: '.$e->getMessage(), [
                'exception' => $e,
                'shift_id' => $shift->id,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses transaksi. Silakan coba lagi atau hubungi administrator.',
            ], 500);
        }
    }

    /**
     * Hitung Potongan Nilai Poin Member (Real-Time Calculation).
     */
    public function calculatePointsDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|uuid',
            'points' => 'required|integer|min:1',
            'subtotal' => 'required|numeric|min:0',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $customer = Customer::where('company_id', $company->id)->findOrFail($request->customer_id);

        try {
            $discount = $this->loyaltyPointService->calculateRedemptionDiscount(
                customer: $customer,
                pointsToRedeem: (int) $request->points,
                maxAllowedAmount: (float) $request->subtotal
            );

            return response()->json([
                'success' => true,
                'discount' => $discount,
                'points' => (int) $request->points,
                'customer_points' => (int) $customer->loyalty_points,
                'remaining_points' => max(0, (int) $customer->loyalty_points - (int) $request->points),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cetak Struk Uji Coba / Sample Thermal Receipt (58mm / 80mm).
     */
    public function sampleReceipt(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $transaction = PosTransaction::with([
            'items.product',
            'items.unit',
            'customerTier',
            'customer',
            'store',
            'company',
            'cashier',
            'shift.cashier',
        ])->where('company_id', $company->id)->latest()->first();

        if (! $transaction) {
            abort(404, 'Belum ada transaksi contoh yang dapat dicetak.');
        }

        $company = $transaction->company;
        $paperSize = $request->query('paper', $company->receipt_paper_size ?? '58');

        return view('pos.receipt', [
            'transaction' => $transaction,
            'isReprint' => false,
            'paperSize' => $paperSize,
            'company' => $company,
        ]);
    }

    /**
     * Cetak Struk Thermal Kasir (58mm / 80mm).
     */
    public function printReceipt(Request $request, string $id): View
    {
        [$company, $store] = $this->resolveCompanyAndStore($request);

        $transaction = PosTransaction::with([
            'items.product',
            'items.unit',
            'customerTier',
            'customer',
            'store',
            'company',
            'cashier',
            'shift.cashier',
        ])->where('company_id', $company->id)->findOrFail($id);

        $paperSize = $request->query('paper', $company->receipt_paper_size ?? '58');

        return view('pos.receipt', [
            'transaction' => $transaction,
            'isReprint' => (bool) $request->query('reprint', false),
            'paperSize' => $paperSize,
            'company' => $company,
        ]);
    }

    /**
     * Cetak Ulang Struk Kasir (Watermark REPRINT & PIN Manager untuk shift lampau).
     */
    public function reprintReceipt(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'shift_id' => 'required|uuid',
            'manager_pin' => 'nullable|string',
            'paper' => 'nullable|string',
        ]);

        $shift = $this->authorizeShiftAccess($request, $request->shift_id);
        $transaction = PosTransaction::where('company_id', $shift->company_id)->findOrFail($id);

        try {
            $result = $this->posService->reprintReceipt(
                transaction: $transaction,
                activeShift: $shift,
                managerPin: $request->manager_pin
            );

            return response()->json([
                'success' => true,
                'reprint_data' => $result,
                'print_url' => route('pos.print', [
                    'id' => $transaction->id, 
                    'reprint' => 1,
                    'paper' => $request->input('paper', '58'),
                ]),
            ]);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'PIN')) {
                AuditLog::record(
                    module: 'AUTH',
                    action: 'MANAGER_PIN_FAILED',
                    description: "Percobaan otorisasi PIN Manajer gagal saat mencetak ulang struk nota {$transaction->invoice_number} (Alasan: {$e->getMessage()}).",
                    user: $request->user()
                );
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Ambil Transaksi Terkini untuk Pencarian Cetak Ulang.
     */
    public function getRecentTransactions(Request $request): JsonResponse
    {
        [$company, $store] = $this->resolveCompanyAndStore($request);
        $shiftId = $request->input('shift_id');

        $query = PosTransaction::with(['items.product', 'items.unit'])
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->latest();

        if ($shiftId) {
            $shift = $this->authorizeShiftAccess($request, (string) $shiftId);
            $query->where('cashier_shift_id', $shift->id);
        }

        $transactions = $query->take(20)->get();

        return response()->json($transactions);
    }

    /**
     * API Proses Retur Penjualan POS (Void / Pengembalian Barang).
     */
    public function returnTransaction(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'refund_method' => 'required|in:CASH,TRANSFER,CREDIT_REDUCTION',
            'reason' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.pos_transaction_item_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.condition' => 'nullable|string|in:GOOD,DAMAGED,RESTOCKABLE,DEFECTIVE,RUSAK',
            'shift_id' => 'nullable|uuid',
            'manager_pin' => 'nullable|string',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $transaction = PosTransaction::with(['items.product', 'items.unit', 'store', 'company'])
            ->where('company_id', $company->id)
            ->findOrFail($id);
        $user = $request->user();

        // Otorisasi: jika bukan manager/owner/store_head, periksa PIN Manager
        $approvedBy = null;
        if (! in_array($user?->role, ['owner', 'manager', 'store_head'], true)) {
            $pin = $request->input('manager_pin');
            if (empty($pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Retur transaksi memerlukan otorisasi PIN Manajer.',
                ], 403);
            }

            $authorizedManagers = User::whereIn('role', ['owner', 'manager', 'store_head'])
                ->where('is_active', true)
                ->whereNotNull('manager_pin')
                ->where(function ($q) use ($company) {
                    $q->where('role', 'owner')
                        ->orWhereHas('store', fn ($sq) => $sq->where('company_id', $company->id));
                })
                ->get();

            $approver = null;
            foreach ($authorizedManagers as $mgr) {
                if ($mgr->verifyManagerPin($pin)) {
                    $approver = $mgr;
                    break;
                }
            }

            if (! $approver) {
                AuditLog::record(
                    module: 'AUTH',
                    action: 'MANAGER_PIN_FAILED',
                    description: "Percobaan otorisasi PIN Manajer gagal oleh pengguna {$user?->name} ({$user?->role}) untuk retur transaksi faktur {$transaction->invoice_number}.",
                    user: $user
                );

                return response()->json([
                    'success' => false,
                    'message' => 'PIN Manajer yang dimasukkan tidak valid.',
                ], 403);
            }

            $approvedBy = $approver->id;
        } else {
            $approvedBy = $user?->id;
        }

        $currentShift = $request->filled('shift_id')
            ? $this->authorizeShiftAccess($request, (string) $request->shift_id)
            : null;

        try {
            $posReturn = $this->posService->processReturn(
                transaction: $transaction,
                itemsToReturn: $request->items,
                refundMethod: $request->refund_method,
                reason: $request->reason,
                processedBy: $user?->id ?? $transaction->cashier_id,
                approvedBy: $approvedBy,
                currentShift: $currentShift
            );

            AuditLog::record(
                module: 'POS',
                action: 'POS_RETURN',
                description: "Retur transaksi faktur {$transaction->invoice_number} senilai Rp " . number_format($posReturn->total_refund_amount, 0, ',', '.') . " (No Retur: {$posReturn->return_number}, Alasan: {$request->reason})",
                user: $user
            );

            return response()->json([
                'success' => true,
                'message' => "Retur faktur {$transaction->invoice_number} berhasil diproses dengan nomor retur {$posReturn->return_number}.",
                'return' => $posReturn,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolusi Selisih Kas Shift oleh Manajer (AGENTS.md Section 4A).
     */
    public function resolveDiscrepancy(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $shift = CashierShift::where('company_id', $company->id)->findOrFail($id);
        $user = $request->user();

        if (! in_array($user?->role, ['owner', 'manager', 'store_head'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Manajer atau Pemilik yang memiliki hak menyelesaikan selisih kas shift.',
            ], 403);
        }

        $resolvedShift = $this->posService->resolveShiftDiscrepancy(
            shift: $shift,
            manager: $user,
            resolutionNotes: $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => "Selisih kas shift {$resolvedShift->shift_number} berhasil diselesaikan.",
            'shift' => $resolvedShift,
        ]);
    }

    /**
     * Cari Faktur untuk Penukaran Barang / Retur Cepat Kasir POS [F8].
     */
    public function lookupInvoice(Request $request): JsonResponse
    {
        $invoiceNumber = trim((string) $request->input('invoice_number'));
        if (! $invoiceNumber) {
            return response()->json(['success' => false, 'message' => 'Nomor faktur wajib diisi.'], 422);
        }

        [$company, $store] = $this->resolveCompanyAndStore($request);

        $transaction = PosTransaction::with([
            'customer',
            'customerTier',
            'store',
            'items.product',
            'items.productUnit',
        ])
        ->where('company_id', $company->id)
        ->where('invoice_number', $invoiceNumber)
        ->first();

        if (! $transaction) {
            return response()->json(['success' => false, 'message' => 'Faktur dengan nomor tersebut tidak ditemukan.'], 404);
        }

        $items = [];
        foreach ($transaction->items as $item) {
            $alreadyReturned = (float) PosReturnItem::where('pos_transaction_item_id', $item->id)
                ->whereHas('posReturn', fn ($q) => $q->where('status', '!=', 'CANCELLED'))
                ->sum('quantity');

            $maxReturnable = max(0, (float) $item->quantity - $alreadyReturned);

            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Produk',
                'sku' => $item->product?->sku ?? '-',
                'barcode' => $item->product?->barcode ?? '',
                'unit_name' => $item->productUnit?->unit_code ?? $item->unit_code ?? 'Pcs',
                'product_unit_id' => $item->product_unit_id,
                'unit_price' => (float) $item->unit_price,
                'quantity' => (float) $item->quantity,
                'already_returned_qty' => $alreadyReturned,
                'max_returnable' => $maxReturnable,
                'subtotal' => (float) $item->subtotal,
            ];
        }

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $transaction->id,
                'invoice_number' => $transaction->invoice_number,
                'transaction_date' => $transaction->transaction_date,
                'customer_name' => $transaction->customer?->name ?? 'Pelanggan Umum',
                'customer_id' => $transaction->customer_id,
                'payment_method' => $transaction->payment_method,
                'grand_total' => (float) $transaction->grand_total,
                'status' => $transaction->status,
                'items' => $items,
            ],
        ]);
    }

    /**
     * Simpan Pengaturan Pembulatan Transaksi dari POS Modal.
     */
    public function saveRoundingSettings(Request $request): JsonResponse
    {
        $request->validate([
            'rounding_enabled' => 'required|boolean',
            'rounding_step' => 'required|integer|in:50,100,500,1000',
            'rounding_mode' => 'required|string|in:ROUND_NEAREST,ROUND_UP,ROUND_DOWN',
            'rounding_applies_to' => 'required|string|in:CASH_ONLY,ALL',
        ]);

        [$company, $store] = $this->resolveCompanyAndStore($request);
        $company->update([
            'rounding_enabled' => (bool) $request->rounding_enabled,
            'rounding_step' => (int) $request->rounding_step,
            'rounding_mode' => $request->rounding_mode,
            'rounding_applies_to' => $request->rounding_applies_to,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan pembulatan transaksi kasir berhasil diperbarui.',
            'settings' => [
                'rounding_enabled' => (bool) $company->rounding_enabled,
                'rounding_step' => (int) $company->rounding_step,
                'rounding_mode' => $company->rounding_mode,
                'rounding_applies_to' => $company->rounding_applies_to,
            ],
        ]);
    }

    /**
     * API Pencarian Cepat Pelanggan POS berdasarkan Nama, No. HP, atau Kode (F3 Fast Lookup).
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        [$company, $store] = $this->resolveCompanyAndStore($request);
        $q = trim((string) $request->input('q', ''));
        $driver = DB::connection()->getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        $customers = Customer::where('company_id', $company->id)
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q, $likeOp) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $q);
                $query->where(function ($sub) use ($q, $cleanPhone, $likeOp) {
                    $sub->where('name', $likeOp, "%{$q}%")
                        ->orWhere('code', $likeOp, "%{$q}%")
                        ->orWhere('phone', $likeOp, "%{$q}%");
                    if (!empty($cleanPhone)) {
                        $sub->orWhere('phone', $likeOp, "%{$cleanPhone}%");
                    }
                });
            })
            ->with('tier')
            ->orderByRaw("code = 'CUST-UMUM' DESC")
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }

    /**
     * Pendaftaran Cepat Pelanggan Baru dari Layar Kasir POS (Quick Add Member).
     */
    public function quickStoreCustomer(Request $request): JsonResponse
    {
        [$company, $store] = $this->resolveCompanyAndStore($request);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        // Ambil customer tier default (Retail / Eceran)
        $defaultTier = CustomerTier::where('company_id', $company->id)->where('is_default', true)->first()
            ?? CustomerTier::where('company_id', $company->id)->first();

        if (! $defaultTier) {
            return response()->json([
                'success' => false,
                'message' => 'Tingkat/Tier Pelanggan default belum dikonfigurasi di sistem.',
            ], 422);
        }

        // Generate kode unik pelanggan CUST-XXXXX
        $count = Customer::where('company_id', $company->id)->count() + 1;
        $code = 'CUST-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        while (Customer::where('company_id', $company->id)->where('code', $code)->exists()) {
            $count++;
            $code = 'CUST-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
        }

        $customer = DB::transaction(function () use ($company, $store, $defaultTier, $code, $validated) {
            return Customer::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'customer_tier_id' => $defaultTier->id,
                'code' => $code,
                'name' => trim($validated['name']),
                'phone' => trim($validated['phone'] ?? '') ?: null,
                'address' => trim($validated['address'] ?? '') ?: null,
                'credit_limit' => 0.00,
                'current_credit_balance' => 0.00,
                'credit_term_days' => 0,
                'loyalty_points' => 0,
                'lifetime_points' => 0,
                'is_active' => true,
            ]);
        });

        $customer->load('tier');

        return response()->json([
            'success' => true,
            'message' => "Pelanggan baru [{$customer->name}] berhasil didaftarkan.",
            'customer' => $customer,
        ]);
    }
}

