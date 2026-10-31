<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PwaStorefrontController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected PricingEngineService $pricingEngine
    ) {}

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
        $product = Product::with(['units', 'prices.customerTier', 'category'])->findOrFail($id);
        $store = Store::where('is_active', true)->first();

        $stock = ProductStock::where('product_id', $product->id)
            ->where('store_id', $store?->id)
            ->first();

        $physical = $stock ? (float) $stock->physical_quantity : 0.0;
        $reserved = $stock ? (float) $stock->reserved_quantity : 0.0;
        $quarantined = $stock ? (float) $stock->quarantined_quantity : 0.0;
        $available = max(0, $physical - $reserved - $quarantined);

        return response()->json([
            'success' => true,
            'product' => $product,
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
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.is_tebus_murah' => 'nullable|boolean',
            'items.*.promotion_id' => 'nullable|uuid',
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $request) {
                $store = Store::with('company')->findOrFail($validated['store_id']);
                $company = $store->company;

                // 1. Cari atau buat akun pelanggan berdasarkan nomor HP
                $phoneClean = preg_replace('/[^0-9]/', '', $validated['customer_phone']);
                $defaultTier = CustomerTier::where('company_id', $company->id)->where('is_default', true)->first()
                    ?? CustomerTier::where('company_id', $company->id)->first();

                $customer = Customer::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'phone' => $phoneClean,
                    ],
                    [
                        'code' => 'CUST-PWA-'.strtoupper(Str::random(4)),
                        'name' => $validated['customer_name'],
                        'customer_tier_id' => $defaultTier?->id,
                        'address' => $validated['delivery_address'] ?? 'PWA Online Customer',
                        'is_active' => true,
                    ]
                );

                $orderNumber = 'ORD-PWA-'.date('Ymd').'-'.strtoupper(Str::random(4));
                $subtotal = 0.0;
                $preparedItems = [];

                // 2. Validasi stok dan lakukan Atomic Reservation dengan TTL 30 Menit (AGENTS.md Section 2 & 4D)
                foreach ($validated['items'] as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    $unit = ProductUnit::where('id', $itemData['product_unit_id'])
                        ->where('product_id', $product->id)
                        ->firstOrFail();

                    $qty = (float) $itemData['quantity'];
                    $baseQty = $qty * (float) $unit->conversion_factor;

                    // Ambil harga
                    $unitPrice = (float) ($product->prices->firstWhere('product_unit_id', $unit->id)?->price ?? $product->cost_price);
                    $discountAmount = (float) ($itemData['discount_amount'] ?? 0.00);

                    // Validasi Promo Tebus Murah dari Mesin Promo (AGENTS.md Section 3)
                    if (! empty($itemData['is_tebus_murah']) || ! empty($itemData['promotion_id'])) {
                        $promoId = $itemData['promotion_id'] ?? null;
                        $promo = $promoId ? Promotion::find($promoId) : null;
                        if ($promo && $promo->type === 'TEBUS_MURAH' && $promo->is_active) {
                            $targetSpecialTotal = (float) $promo->special_price * $qty;
                            $discountAmount = max(0.0, ($qty * $unitPrice) - $targetSpecialTotal);
                        }
                    }

                    $lineTotal = max(0.0, ($qty * $unitPrice) - $discountAmount);
                    $subtotal += $lineTotal;

                    // Reservasi stok di database
                    $this->inventoryService->reserveStock(
                        product: $product,
                        store: $store,
                        quantity: $baseQty,
                        referenceNumber: $orderNumber,
                        notes: "Reservasi Pesanan Online HP (PWA) [{$orderNumber}] - Pelanggan: {$validated['customer_name']}"
                    );

                    $preparedItems[] = [
                        'product_id' => $product->id,
                        'product_unit_id' => $unit->id,
                        'unit_code' => $unit->unit_code,
                        'conversion_factor' => $unit->conversion_factor,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $lineTotal,
                    ];
                }

                $deliveryAddress = $validated['delivery_address'] ?? null;
                $orderNotes = $validated['notes'] ?? null;

                // 3. Simpan Pesanan Penjualan (Sales Order)
                $salesOrder = SalesOrder::create([
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'customer_id' => $customer->id,
                    'order_number' => $orderNumber,
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->toDateString(),
                    'status' => 'PENDING',
                    'subtotal' => $subtotal,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'grand_total' => $subtotal,
                    'notes' => "[PWA ONLINE] Tipe: {$validated['delivery_type']} | Bayar: {$validated['payment_method']} | Alamat: ".($deliveryAddress ?: '-').' | Catatan: '.($orderNotes ?: '-'),
                ]);

                // 4. Simpan baris item
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
