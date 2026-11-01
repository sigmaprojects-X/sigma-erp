<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use App\Services\BatchExpiryService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BatchExpiryController extends Controller
{
    public function __construct(
        protected BatchExpiryService $batchExpiryService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Daftar Seluruh Batch & Status Stok Lot.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        // Jalankan auto-sinkronisasi status kedaluwarsa
        $this->batchExpiryService->syncExpiredStatus();

        $query = ProductBatch::where('company_id', $company->id)
            ->with(['store', 'product.category'])
            ->orderBy('expired_date', 'asc');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'ilike', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'ilike', "%{$search}%")
                            ->orWhere('sku', 'ilike', "%{$search}%");
                    });
            });
        }

        $batches = $query->paginate(20)->withQueryString();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'batches' => $batches,
            ]);
        }

        return view('warehouse.batches.index', compact('batches', 'stores', 'company'));
    }

    /**
     * Dashboard Peringatan Dini Produk Mendekati Kedaluwarsa (Near-Expired Alert).
     */
    public function nearExpired(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $this->batchExpiryService->syncExpiredStatus();

        $days = (int) $request->input('days', 30);
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;

        $nearExpiredBatches = $this->batchExpiryService->getNearExpiredBatches($store, $days);
        $alreadyExpiredBatches = $this->batchExpiryService->getExpiredBatches($store);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'days_threshold' => $days,
                'near_expired' => $nearExpiredBatches,
                'already_expired' => $alreadyExpiredBatches,
            ]);
        }

        return view('warehouse.batches.near_expired', compact(
            'nearExpiredBatches',
            'alreadyExpiredBatches',
            'stores',
            'days',
            'storeId'
        ));
    }

    /**
     * Form Pendaftaran Batch Baru Manual.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('warehouse.batches.create', compact('stores', 'products', 'company'));
    }

    /**
     * Simpan Batch Baru & Tambahkan Stok Fisik.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'product_id' => 'required|uuid|exists:products,id',
            'batch_number' => 'required|string|max:50',
            'expired_date' => 'required|date',
            'manufacture_date' => 'nullable|date',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $store = Store::findOrFail($validated['store_id']);
            $product = Product::findOrFail($validated['product_id']);
            $quantity = (float) $validated['quantity'];
            $unitCost = (float) ($validated['unit_cost'] ?? $product->cost_price ?? 0);

            // 1. Daftarkan batch
            $batch = $this->batchExpiryService->registerBatch(
                store: $store,
                product: $product,
                batchNumber: $validated['batch_number'],
                expiredDate: $validated['expired_date'],
                quantity: $quantity,
                unitCost: $unitCost,
                mfgDate: $validated['manufacture_date'] ?? null,
                notes: $validated['notes'] ?? null
            );

            // Pastikan produk memiliki flag has_batch_expiry = true
            if (! $product->has_batch_expiry) {
                $product->update(['has_batch_expiry' => true]);
            }

            // 2. Tambah stok fisik jika registrasi langsung melalui modul batch
            $user = Auth::user();
            $this->inventoryService->recordAssemblyStock(
                product: $product,
                store: $store,
                quantity: $quantity,
                unitCost: $unitCost,
                referenceNumber: 'LOT-'.strtoupper($batch->batch_number),
                mutationType: 'INITIAL',
                notes: "Registrasi Batch Lot: {$batch->batch_number} (EXP: {$batch->expired_date})",
                userId: $user?->id
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Batch produk berhasil didaftarkan.',
                    'batch' => $batch,
                ]);
            }

            return redirect()->route('warehouse.batches.index')
                ->with('success', "Batch [{$batch->batch_number}] untuk {$product->name} berhasil disimpan.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal mendaftarkan batch: '.$e->getMessage()]);
        }
    }

    /**
     * API JSON Rekomendasi Batch FEFO untuk POS Kasir.
     */
    public function fefoLookup(Request $request, string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $storeId = $request->input('store_id', Auth::user()?->store_id);

        $batches = ProductBatch::where('product_id', $product->id)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->fefoOrder()
            ->get();

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'has_batch_expiry' => $product->has_batch_expiry,
            ],
            'batches' => $batches,
        ]);
    }
}
