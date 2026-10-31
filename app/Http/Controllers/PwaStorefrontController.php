<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicProductResource;
use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Store;
use App\Services\InventoryService;
use App\Services\PricingEngineService;
use App\Services\PromoEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PwaStorefrontController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected PricingEngineService $pricingEngine,
        protected ?PromoEngineService $promoEngine = null
    ) {
        $this->promoEngine = $promoEngine ?? app(PromoEngineService::class);
    }

    /**
     * Tampilan Beranda Marketplace / Katalog PWA Pelanggan via HP.
     */
    public function index(Request $request): View
    {
        $store = null;
        if ($request->filled('store_id')) {
            $store = Store::find($request->store_id);
        }
        if (! $store) {
            $store = Store::where('is_active', true)->first() ?? Store::first();
        }

        $company = $store ? $store->company : (Company::first());

        $categories = ProductCategory::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $query = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('type', '!=', Product::TYPE_BIAYA)
            ->whereNotIn('sale_status', ['DISCONTINUED', 'BUY_ONLY'])
            ->with(['units', 'prices', 'category', 'stocks' => function ($q) use ($store) {
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            }]);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('sku', 'ILIKE', "%{$search}%")
                    ->orWhere('barcode', 'ILIKE', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(24)->withQueryString();

        // Siapkan informasi stok tersedia per produk
        foreach ($products as $prod) {
            $stock = $prod->stocks->first();
            $physical = $stock ? (float) $stock->physical_quantity : 0.0;
            $reserved = $stock ? (float) $stock->reserved_quantity : 0.0;
            $quarantined = $stock ? (float) $stock->quarantined_quantity : 0.0;
            $prod->available_stock = max(0, $physical - $reserved - $quarantined);

            // Ambil harga retail umum sesuai satuan default yang ditampilkan
            $defaultUnit = $prod->units->first();
            $retailPrice = (float) $prod->cost_price;
            if ($defaultUnit && $prod->prices->isNotEmpty()) {
                $priceObj = $prod->prices->firstWhere('product_unit_id', $defaultUnit->id) ?? $prod->prices->first();
                $retailPrice = (float) $priceObj->price;
            } elseif ($prod->prices->isNotEmpty()) {
                $retailPrice = (float) $prod->prices->first()->price;
            }
            $prod->display_price = $retailPrice;
        }

        // Promosi Aktif (Tebus Murah, Buy X Get Y, Diskon) untuk PWA Pelanggan
        $activePromotions = Promotion::where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->where(function ($q) use ($store) {
                $q->whereNull('store_id');
                if ($store) {
                    $q->orWhere('store_id', $store->id);
                }
            })
            ->with(['buyProduct.units', 'rewardProduct.units', 'rewardProduct.prices'])
            ->get();

        return view('pwa.index', compact('company', 'store', 'categories', 'products', 'activePromotions'));
    }

    /**
     * Web App Manifest untuk PWA Storefront Pelanggan (Installable ke Layar HP).
     */
    public function manifest(): JsonResponse
    {
        $company = Company::first();
        return response()->json([
            'name' => $company->name ?? 'SIGMA Supermarket Online',
            'short_name' => 'SIGMA Store',
            'description' => 'Marketplace & Katalog Belanja Online SIGMA Supermarket',
            'start_url' => route('pwa.index'),
            'display' => 'standalone',
            'background_color' => '#f8fafc',
            'theme_color' => '#2563eb',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => asset('favicon.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => asset('logo-icon.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ],
            ],
        ]);
    }

    /**
     * Detail Produk untuk PWA Popup / Modal.
     */
    public function productDetail(string $id): JsonResponse
    {
        $product = Product::with(['units', 'prices.customerTier', 'category'])
            ->where('is_active', true)
            ->where('type', '!=', Product::TYPE_BIAYA)
            ->whereNotIn('sale_status', ['DISCONTINUED', 'BUY_ONLY'])
            ->findOrFail($id);

        $store = Store::where('is_active', true)->first();

        $stock = ProductStock::where('product_id', $product->id)
            ->where('store_id', $store?->id)
            ->first();

        $physical = $stock ? (float) $stock->physical_quantity : 0.0;
        $reserved = $stock ? (float) $stock->reserved_quantity : 0.0;
        $quarantined = $stock ? (float) $stock->quarantined_quantity : 0.0;
        $available = max(0, $physical - $reserved - $quarantined);
        $product->available_stock = $available;

        return response()->json([
            'success' => true,
            'product' => (new PublicProductResource($product))->resolve(),
            'available_stock' => $available,
        ]);
    }

    /**
     * Proses Checkout Pesanan Pelanggan dari HP (Atomic Stock Reservation TTL 30 Menit).
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:30',
            'delivery_type' => 'required|in:PICKUP,DELIVERY',
            'delivery_address' => 'nullable|string|max:300',
            'payment_method' => 'required|in:TRANSFER,COD',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.product_unit_id' => 'required|uuid|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $order = DB::transaction(function () use ($validated) {
                $store = Store::with('company')->findOrFail($validated['store_id']);
                $company = $store->company;

                // 1. Cari atau buat akun pelanggan berdasarkan nomor HP
                $phoneClean = preg_replace('/[^0-9]/', '', $validated['customer_phone']);
                $defaultTier = CustomerTier::where('company_id', $company->id)->where('is_default', true)->first()
                    ?? CustomerTier::where('company_id', $company->id)->firstOrFail();

                $customer = Customer::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'phone' => $phoneClean,
                    ],
                    [
                        'code' => 'CUST-PWA-'.strtoupper(Str::random(4)),
                        'name' => $validated['customer_name'],
                        'customer_tier_id' => $defaultTier->id,
                        'address' => $validated['delivery_address'] ?? 'PWA Online Customer',
                        'is_active' => true,
                    ]
                );

                $tier = $customer->customerTier ?? $defaultTier;
                $orderNumber = 'ORD-PWA-'.date('Ymd').'-'.strtoupper(Str::random(4));
                $preparedItems = [];

                // 2. Hitung harga satuan resmi di server (Zero Manual Price Override)
                foreach ($validated['items'] as $itemData) {
                    $product = Product::where('id', $itemData['product_id'])
                        ->where('is_active', true)
                        ->where('type', '!=', Product::TYPE_BIAYA)
                        ->whereNotIn('sale_status', ['DISCONTINUED', 'BUY_ONLY'])
                        ->firstOrFail();

                    $unit = ProductUnit::where('id', $itemData['product_unit_id'])
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->firstOrFail();

                    $qty = (float) $itemData['quantity'];
                    $baseQty = $qty * (float) $unit->conversion_factor;

                    $unitPrice = $this->pricingEngine->calculatePrice(
                        product: $product,
                        unit: $unit,
                        tier: $tier,
                        quantity: $qty,
                        store: $store
                    );

                    $preparedItems[] = [
                        'product' => $product,
                        'product_id' => $product->id,
                        'product_unit_id' => $unit->id,
                        'unit_code' => $unit->unit_code,
                        'conversion_factor' => $unit->conversion_factor,
                        'quantity' => $qty,
                        'base_quantity' => $baseQty,
                        'unit_price' => $unitPrice,
                        'discount_amount' => 0.00,
                        'subtotal' => round($qty * $unitPrice, 2),
                    ];
                }

                // 3. Evaluasi promosi aktif secara server-side (Tebus Murah, Buy X Get Y, Diskon)
                $activePromos = $this->promoEngine->getActivePromotions($company, $store);
                $rawSubtotal = (float) collect($preparedItems)->sum('subtotal');

                // Terapkan promo Tebus Murah & Buy X Get Y pada baris item yang berhak
                foreach ($activePromos as $promo) {
                    if ($promo->type === 'TEBUS_MURAH' && $promo->reward_product_id) {
                        $nonRewardSubtotal = (float) collect($preparedItems)
                            ->where('product_id', '!=', $promo->reward_product_id)
                            ->sum('subtotal');

                        if ($nonRewardSubtotal >= (float) $promo->min_spend) {
                            $remainingRewardQuota = (float) ($promo->reward_quantity ?: 1.0);
                            foreach ($preparedItems as &$pItem) {
                                if ($pItem['product_id'] === $promo->reward_product_id && $remainingRewardQuota > 0) {
                                    $eligibleQty = min($pItem['quantity'], $remainingRewardQuota);
                                    $specialPrice = (float) $promo->special_price;
                                    if ($pItem['unit_price'] > $specialPrice) {
                                        $itemDisc = round($eligibleQty * ($pItem['unit_price'] - $specialPrice), 2);
                                        $pItem['discount_amount'] = round($pItem['discount_amount'] + $itemDisc, 2);
                                        $pItem['subtotal'] = max(0.0, round(($pItem['quantity'] * $pItem['unit_price']) - $pItem['discount_amount'], 2));
                                    }
                                    $remainingRewardQuota -= $eligibleQty;
                                }
                            }
                            unset($pItem);
                        }
                    } elseif ($promo->type === 'BUY_X_GET_Y' && $promo->buy_product_id && $promo->reward_product_id) {
                        if ($promo->min_spend > 0 && $rawSubtotal < (float) $promo->min_spend) {
                            continue;
                        }
                        $buyQty = (float) $promo->buy_quantity;
                        $ownedQty = (float) collect($preparedItems)->where('product_id', $promo->buy_product_id)->sum('quantity');
                        if ($buyQty > 0 && $ownedQty >= $buyQty) {
                            $freeQuota = floor($ownedQty / $buyQty) * (float) $promo->reward_quantity;
                            foreach ($preparedItems as &$pItem) {
                                if ($pItem['product_id'] === $promo->reward_product_id && $freeQuota > 0) {
                                    $eligibleQty = min($pItem['quantity'], $freeQuota);
                                    $itemDisc = round($eligibleQty * $pItem['unit_price'], 2);
                                    $pItem['discount_amount'] = round($pItem['discount_amount'] + $itemDisc, 2);
                                    $pItem['subtotal'] = max(0.0, round(($pItem['quantity'] * $pItem['unit_price']) - $pItem['discount_amount'], 2));
                                    $freeQuota -= $eligibleQty;
                                }
                            }
                            unset($pItem);
                        }
                    }
                }

                $subtotal = 0.0;
                foreach ($preparedItems as &$pItem) {
                    $subtotal += $pItem['subtotal'];

                    // Reservasi stok di database (khusus item yang melacak stok fisik)
                    /** @var Product $productModel */
                    $productModel = $pItem['product'];
                    if ($productModel->tracksInventory()) {
                        $this->inventoryService->reserveStock(
                            product: $productModel,
                            store: $store,
                            quantity: $pItem['base_quantity'],
                            referenceNumber: $orderNumber,
                            notes: "Reservasi Pesanan Online HP (PWA) [{$orderNumber}] - Pelanggan: {$validated['customer_name']}"
                        );
                    }
                    unset($pItem['product'], $pItem['base_quantity']);
                }
                unset($pItem);

                // Evaluasi diskon keranjang (DISCOUNT_PERCENT / DISCOUNT_NOMINAL) dari promosi aktif
                $orderDiscount = 0.0;
                foreach ($activePromos as $promo) {
                    if ($promo->min_spend > 0 && $subtotal < (float) $promo->min_spend) {
                        continue;
                    }
                    if ($promo->type === 'DISCOUNT_PERCENT' && $promo->discount_percent > 0) {
                        $orderDiscount += round(($subtotal * (float) $promo->discount_percent) / 100, 2);
                    } elseif ($promo->type === 'DISCOUNT_NOMINAL' && $promo->discount_nominal > 0) {
                        $orderDiscount += min($subtotal, (float) $promo->discount_nominal);
                    }
                }
                $orderDiscount = min($subtotal, round($orderDiscount, 2));
                $grandTotal = max(0.0, round($subtotal - $orderDiscount, 2));

                $deliveryAddress = $validated['delivery_address'] ?? null;
                $orderNotes = $validated['notes'] ?? null;

                // 4. Simpan Pesanan Penjualan (Sales Order)
                $salesOrder = SalesOrder::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'order_number' => $orderNumber,
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->toDateString(),
                    'status' => 'PENDING',
                    'subtotal' => $subtotal,
                    'discount_amount' => $orderDiscount,
                    'tax_amount' => 0.00,
                    'grand_total' => $grandTotal,
                    'notes' => "[PWA ONLINE] Tipe: {$validated['delivery_type']} | Bayar: {$validated['payment_method']} | Alamat: ".($deliveryAddress ?: '-').' | Catatan: '.($orderNotes ?: '-'),
                ]);

                // 5. Simpan baris item
                foreach ($preparedItems as $prep) {
                    $prep['sales_order_id'] = $salesOrder->id;
                    SalesOrderItem::create($prep);
                }

                return $salesOrder;
            });

            // 5. Buat link WhatsApp direct order toko
            $store = $order->store;
            $rawPhone = $store->phone ?: '081234567890';
            $waPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            if (str_starts_with($waPhone, '0')) {
                $waPhone = '62'.substr($waPhone, 1);
            }

            $waMessage = "Halo {$store->name},\nSaya baru saja membuat pesanan online lewat HP:\n\n".
                "📌 No. Pesanan: *{$order->order_number}*\n".
                "👤 Nama: {$validated['customer_name']}\n".
                "💰 Total: *Rp ".number_format($order->grand_total, 0, ',', '.')."*\n".
                "🚚 Metode: {$validated['delivery_type']} ({$validated['payment_method']})\n".
                "📍 Alamat: ".(($validated['delivery_address'] ?? null) ?: 'Ambil di Toko')."\n\n".
                "Mohon segera dikonfirmasi. Terima kasih!";

            $waUrl = 'https://wa.me/'.$waPhone.'?text='.urlencode($waMessage);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat! Stok telah direservasi selama 30 menit.',
                'order_number' => $order->order_number,
                'redirect_url' => route('pwa.order-status', $order->order_number),
                'whatsapp_url' => $waUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pesanan: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Halaman Live Pelacakan Status Pesanan Pelanggan.
     */
    public function orderStatus(string $orderNumber): View
    {
        $order = SalesOrder::with(['items.product', 'items.productUnit', 'customer', 'store.company'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $bankAccounts = BankAccount::where('company_id', $order->company_id)
            ->where('is_active', true)
            ->get();

        return view('pwa.order_status', compact('order', 'bankAccounts'));
    }
}
