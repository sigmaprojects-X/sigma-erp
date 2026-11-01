<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PosService;
use App\Services\PricingEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function __construct(
        protected PricingEngineService $pricingEngine,
        protected PosService $posService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Daftar Pesanan Penjualan (Sales Orders).
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $query = SalesOrder::where('company_id', $company->id)
            ->with(['customer', 'store', 'creator', 'items.product'])
            ->latest('order_date')
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'ilike', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'ilike', "%{$s}%");
                    });
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'orders' => $orders]);
        }

        return view('sales.orders.index', compact('orders', 'company'));
    }

    /**
     * Form Buat Pesanan Penjualan Baru.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $customers = Customer::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('type', '!=', Product::TYPE_BIAYA)
            ->whereNotIn('sale_status', ['DISCONTINUED', 'BUY_ONLY'])
            ->with(['units', 'prices'])
            ->orderBy('name')
            ->get();

        return view('sales.orders.create', compact('stores', 'customers', 'products', 'company'));
    }

    /**
     * Simpan Pesanan Penjualan Baru.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'customer_id' => 'nullable|uuid|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.product_unit_id' => 'required|uuid|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $company = app(\App\Services\CompanyService::class)->getActiveCompany();
            $user = Auth::user();

            $order = DB::transaction(function () use ($validated, $company, $user) {
                $orderNumber = 'SO-' . date('Ymd') . '-' . strtoupper(Str::random(5));

                $subtotal = 0.0;
                $totalDiscount = 0.0;
                $preparedItems = [];

                foreach ($validated['items'] as $item) {
                    $unit = ProductUnit::findOrFail($item['product_unit_id']);
                    $qty = (float) $item['quantity'];
                    $price = (float) $item['unit_price'];
                    $discount = (float) ($item['discount_amount'] ?? 0.0);
                    $lineTotal = max(0, ($qty * $price) - $discount);

                    $subtotal += ($qty * $price);
                    $totalDiscount += $discount;

                    $preparedItems[] = [
                        'product_id' => $item['product_id'],
                        'product_unit_id' => $unit->id,
                        'unit_code' => $unit->unit_code,
                        'conversion_factor' => (float) $unit->conversion_factor,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'discount_amount' => $discount,
                        'subtotal' => $lineTotal,
                    ];
                }

                $grandTotal = max(0, $subtotal - $totalDiscount);

                $salesOrder = SalesOrder::create([
                    'company_id' => $company->id,
                    'store_id' => $validated['store_id'],
                    'customer_id' => $validated['customer_id'] ?? null,
                    'order_number' => $orderNumber,
                    'order_date' => $validated['order_date'],
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'status' => 'PENDING',
                    'subtotal' => $subtotal,
                    'discount_amount' => $totalDiscount,
                    'tax_amount' => 0.00,
                    'grand_total' => $grandTotal,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $user?->id,
                ]);

                foreach ($preparedItems as $pi) {
                    $salesOrder->items()->create($pi);
                }

                return $salesOrder;
            });

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'order' => $order->load('items')]);
            }

            return redirect()->route('sales.orders.show', $order->id)
                ->with('success', "Pesanan Penjualan [{$order->order_number}] berhasil dibuat!");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal membuat pesanan: '.$e->getMessage()]);
        }
    }

    /**
     * Tampilkan Detail Pesanan Penjualan.
     */
    public function show(string $id): View
    {
        $order = SalesOrder::with(['customer.tier', 'store', 'creator', 'approver', 'items.product', 'items.productUnit'])
            ->findOrFail($id);

        return view('sales.orders.show', compact('order'));
    }

    /**
     * Konfirmasi / Approve Pesanan Penjualan.
     */
    public function approve(string $id): RedirectResponse|JsonResponse
    {
        $order = SalesOrder::findOrFail($id);
        $user = Auth::user();

        if ($order->status !== 'PENDING') {
            return back()->withErrors(['error' => 'Hanya pesanan berstatus PENDING yang dapat disetujui.']);
        }

        $order->update([
            'status' => 'APPROVED',
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Pesanan penjualan telah disetujui.']);
        }

        return redirect()->route('sales.orders.show', $order->id)
            ->with('success', "Pesanan Penjualan [{$order->order_number}] telah disetujui (APPROVED).");
    }

    /**
     * Batalkan Pesanan Penjualan.
     */
    public function cancel(string $id): RedirectResponse|JsonResponse
    {
        $order = SalesOrder::with(['items.product', 'items.productUnit', 'store'])->findOrFail($id);

        if (in_array($order->status, ['PROCESSED', 'COMPLETED', 'SELESAI'], true) || $order->stock_deducted_at !== null) {
            return back()->withErrors(['error' => 'Pesanan yang telah berstatus Selesai / Terproses tidak dapat dibatalkan.']);
        }

        DB::transaction(function () use ($order) {
            // Lepas reservasi stok jika status masih PENDING atau APPROVED (AGENTS.md Section 2 & 4D)
            if (in_array($order->status, ['PENDING', 'APPROVED'], true)) {
                foreach ($order->items as $item) {
                    if (! $item->product || ! $item->product->tracksInventory()) {
                        continue;
                    }
                    $baseQty = (float) $item->quantity * (float) ($item->conversion_factor ?? 1);
                    $this->inventoryService->releaseReservedStock(
                        product: $item->product,
                        store: $order->store,
                        quantity: $baseQty,
                        referenceNumber: $order->order_number,
                        notes: "Pelepasan reservasi akibat pembatalan pesanan [{$order->order_number}]"
                    );
                }
            }

            $order->update(['status' => 'CANCELLED']);
        });

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Pesanan penjualan dibatalkan dan alokasi stok berhasil dilepaskan.']);
        }

        return redirect()->route('sales.orders.show', $order->id)
            ->with('success', "Pesanan Penjualan [{$order->order_number}] berhasil dibatalkan dan alokasi stok telah dilepas.");
    }

    /**
     * Selesaikan Pesanan Penjualan (Tandai Selesai / Completed).
     * Otomatis memotong stok inventaris (AGENTS.md Section 2 & 4D).
     */
    public function complete(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $order = SalesOrder::with(['items.product', 'items.productUnit', 'store'])->findOrFail($id);

        if ($order->status === 'CANCELLED') {
            return back()->withErrors(['error' => 'Pesanan yang telah dibatalkan tidak dapat diselesaikan.']);
        }

        if (in_array($order->status, ['COMPLETED', 'SELESAI', 'PROCESSED'], true)) {
            return back()->withErrors(['error' => 'Pesanan ini sudah berstatus Selesai / Terproses.']);
        }

        try {
            $user = Auth::user();

            DB::transaction(function () use ($order, $user) {
                // Update status menjadi COMPLETED (akan memicu event booted SalesOrder::updating untuk potong stok)
                $order->update([
                    'status' => 'COMPLETED',
                    'completed_at' => now(),
                    'approved_by' => $order->approved_by ?? $user?->id,
                    'approved_at' => $order->approved_at ?? now(),
                ]);
            });

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pesanan Penjualan [{$order->order_number}] berhasil diselesaikan dan stok inventaris otomatis dikurangi!",
                    'order' => $order->fresh(['items.product', 'store']),
                ]);
            }

            return redirect()->route('sales.orders.show', $order->id)
                ->with('success', "Pesanan Penjualan [{$order->order_number}] telah Selesai (COMPLETED). Stok barang otomatis dikurangi dari inventaris!");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => 'Gagal menyelesaikan pesanan: ' . $e->getMessage()]);
        }
    }

    /**
     * Proses Pesanan Penjualan menjadi Faktur Penjualan POS / Transaksi.
     */
    public function processToSale(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $order = SalesOrder::with(['items.product', 'items.productUnit', 'customer.tier', 'store.company'])->findOrFail($id);

        if ($order->status === 'CANCELLED') {
            return back()->withErrors(['error' => 'Pesanan yang telah dibatalkan tidak dapat diproses.']);
        }
        if (in_array($order->status, ['PROCESSED', 'COMPLETED', 'SELESAI'], true)) {
            return back()->withErrors(['error' => 'Pesanan ini sudah pernah diproses menjadi faktur penjualan / selesai.']);
        }

        try {
            $user = Auth::user();
            $shift = CashierShift::where('store_id', $order->store_id)
                ->where('status', 'OPEN')
                ->latest('opened_at')
                ->first();

            if (! $shift) {
                // Buat shift kasir otomatis jika belum ada shift aktif
                $shift = $this->posService->openShift(
                    company: $order->store->company,
                    store: $order->store,
                    cashierId: $user?->id ?? User::first()->id,
                    startingCash: 0.0
                );
            }

            $tier = $order->customer?->tier ?? CustomerTier::where('company_id', $order->company_id)->where('is_default', true)->firstOrFail();

            $cartItems = [];
            foreach ($order->items as $item) {
                $cartItems[] = [
                    'product_id' => $item->product_id,
                    'product_unit_id' => $item->product_unit_id,
                    'quantity' => (float) $item->quantity,
                    'discount_amount' => (float) $item->discount_amount,
                ];
            }

            $paymentMethod = $request->input('payment_method', 'CASH');
            $paidAmount = (float) $request->input('paid_amount', $order->grand_total);

            // Lepas reservasi stok PWA SEBELUM checkout agar available stock tidak terblokir saat checkout
            foreach ($order->items as $item) {
                if (! $item->product || ! $item->product->tracksInventory()) {
                    continue;
                }
                $baseQty = (float) $item->quantity * (float) ($item->conversion_factor ?? 1);
                $this->inventoryService->releaseReservedStock(
                    product: $item->product,
                    store: $order->store,
                    quantity: $baseQty,
                    referenceNumber: $order->order_number,
                    notes: "Pelepasan reservasi untuk realisasi pesanan [{$order->order_number}] ke Faktur Kasir POS"
                );
            }

            $transaction = $this->posService->checkout(
                shift: $shift,
                tier: $tier,
                cartItems: $cartItems,
                paymentMethod: $paymentMethod,
                paidAmount: $paidAmount,
                notes: "Realisasi Pesanan Penjualan: {$order->order_number}",
                customerId: $order->customer_id
            );

            $order->update([
                'status' => 'PROCESSED',
                'pos_transaction_id' => $transaction->id,
                'stock_deducted_at' => now(),
                'completed_at' => now(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pesanan berhasil dikonversi menjadi Faktur Penjualan!',
                    'transaction' => $transaction,
                ]);
            }

            return redirect()->route('pos.receipt', $transaction->id)
                ->with('success', "Pesanan Penjualan [{$order->order_number}] berhasil diproses menjadi Faktur [{$transaction->invoice_number}]!");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => 'Gagal memproses pesanan ke faktur: '.$e->getMessage()]);
        }
    }
}
