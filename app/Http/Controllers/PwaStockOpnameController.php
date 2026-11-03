<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockOpname;
use App\Models\Store;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PwaStockOpnameController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    /**
     * Dashboard PWA Stock Opname Gudang.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $selectedStore = null;
        $targetStore = $request->query('cabang') ?: ($request->query('store') ?: $request->query('store_id'));
        if ($targetStore) {
            $selectedStore = Store::where('company_id', $company->id)
                ->where(function ($q) use ($targetStore) {
                    $q->where('id', $targetStore)
                      ->orWhereRaw('LOWER(code) = ?', [strtolower(trim($targetStore))])
                      ->orWhereRaw('LOWER(name) = ?', [strtolower(trim($targetStore))]);
                })->first();
        }

        $userStore = $selectedStore ?? ($user?->store ?? Store::where('company_id', $company->id)->first());
        $availableStores = Store::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        // Ambil sesi opname aktif di toko pengguna
        $activeSessions = StockOpname::where('company_id', $company->id)
            ->where('store_id', $userStore?->id)
            ->where('status', 'IN_PROGRESS')
            ->with(['store', 'creator', 'items'])
            ->withCount('items')
            ->latest()
            ->get();

        // Hitung progres riil per sesi (berapa barang sudah dihitung)
        foreach ($activeSessions as $opname) {
            $counted = $opname->items->where('actual_quantity', '>', 0)->count();
            $total = $opname->items_count ?: $opname->items->count();
            $opname->counted_items_count = $counted;
            $opname->progress_percent = $total > 0 ? round(($counted / $total) * 100) : 0;
        }

        // Ambil sesi baru-baru ini yang menunggu approval atau selesai
        $recentSessions = StockOpname::where('company_id', $company->id)
            ->where('store_id', $userStore?->id)
            ->whereIn('status', ['PENDING_APPROVAL', 'COMPLETED'])
            ->with(['store', 'creator', 'items'])
            ->withCount('items')
            ->latest()
            ->take(5)
            ->get();

        $availableRacks = Product::where('company_id', $company->id)
            ->whereNotNull('rack_location')
            ->where('rack_location', '!=', '')
            ->distinct()
            ->pluck('rack_location')
            ->sort()
            ->values();

        return view('warehouse.pwa.index', compact('activeSessions', 'recentSessions', 'userStore', 'user', 'availableRacks', 'availableStores'));
    }

    /**
     * Buat Sesi Opname Baru Langsung dari PWA HP / Tablet / PC (Mendukung Full Opname & Siklus Rak).
     */
    public function storeSession(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'nullable|uuid|exists:stores,id',
            'notes' => 'nullable|string|max:255',
            'opname_date' => 'nullable|date',
            'rack_location' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $userStore = null;
        if ($request->filled('store_id')) {
            $userStore = Store::where('company_id', $company->id)->where('id', $request->store_id)->first();
        }
        $userStore = $userStore ?? ($user?->store ?? Store::where('company_id', $company->id)->firstOrFail());

        try {
            $notes = $request->filled('notes') 
                ? trim($request->notes) 
                : 'Opname Fisik PWA (' . now()->translatedFormat('d M Y H:i') . ')';

            $opname = $this->warehouseService->startOpname(
                company: $company,
                store: $userStore,
                opnameDate: $request->opname_date ?: now()->toDateString(),
                notes: $notes,
                userId: $user?->id,
                rackLocation: $request->rack_location
            );

            return redirect()->route('warehouse.opname-pwa.session', $opname->id)
                ->with('success', "Sesi Opname [{$opname->opname_number}] berhasil dibuat! Silakan mulai memindai barcode.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat sesi opname: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan Sesi Opname yang Masih Berjalan (IN PROGRESS).
     */
    public function cancelSession(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status !== 'IN_PROGRESS') {
            return back()->with('error', 'Hanya sesi yang masih berstatus IN PROGRESS yang dapat dibatalkan.');
        }

        try {
            DB::transaction(function () use ($stockOpname) {
                $stockOpname->items()->delete();
                $stockOpname->delete();
            });

            return redirect()->route('warehouse.opname-pwa.index')
                ->with('success', "Sesi Opname [{$stockOpname->opname_number}] berhasil dibatalkan.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membatalkan sesi: ' . $e->getMessage());
        }
    }

    /**
     * Layar Hitung Fisik Interaktif Mobile (Blind Count Sheet).
     */
    public function session(StockOpname $stockOpname): View|RedirectResponse
    {
        $user = Auth::user();
        $isManager = in_array($user?->role, ['owner', 'manager', 'store_head'], true);

        $stockOpname->load(['store', 'creator', 'items.product.category', 'items.product.barcodes', 'items.product.units', 'items.product.baseUnit']);

        // Data barang dengan proteksi Blind Count (system_quantity disembunyikan sesuai AGENTS.md)
        $items = $stockOpname->items->map(function ($it) {
            $product = $it->product;
            $barcodes = $product?->barcodes ? $product->barcodes->pluck('barcode')->toArray() : [];
            if ($product && !in_array($product->sku, $barcodes)) {
                $barcodes[] = $product->sku;
            }

            // Ambil daftar satuan kemasan produk diurutkan dari konversi terbesar ke terkecil
            $rawUnits = $product?->units ? $product->units->where('is_active', true)->sortByDesc('conversion_factor')->values() : collect();
            $units = $rawUnits->map(function ($u) {
                return [
                    'id' => $u->id,
                    'unit_code' => $u->unit_code,
                    'unit_name' => $u->unit_name ?: $u->unit_code,
                    'conversion_factor' => (float) $u->conversion_factor,
                    'is_base_unit' => (bool) $u->is_base_unit,
                ];
            })->toArray();

            $baseUnitName = $product?->base_unit ?? 'PCS';
            if (empty($units)) {
                $units = [
                    [
                        'id' => null,
                        'unit_code' => $baseUnitName,
                        'unit_name' => $baseUnitName,
                        'conversion_factor' => 1.0,
                        'is_base_unit' => true,
                    ]
                ];
            }

            return [
                'id' => $it->id,
                'product_id' => $it->product_id,
                'name' => $product?->name ?? 'Produk Dihapus',
                'sku' => $product?->sku ?? '-',
                'barcode' => $barcodes[0] ?? '-',
                'barcodes' => $barcodes,
                'category_name' => $product?->category?->name ?? 'Umum',
                'category_id' => $product?->category_id,
                'rack_location' => $product?->rack_location ?: 'Tanpa Rak',
                'actual_quantity' => (float) $it->actual_quantity,
                'notes' => $it->notes,
                'units' => $units,
                'base_unit' => $baseUnitName,
                'is_counted' => (float) $it->actual_quantity > 0,
                // STRICT BLIND OPNAME: System quantity tidak pernah dikirim ke mobile PWA
                'system_quantity' => null,
            ];
        });

        // Ambil daftar rak dan kategori unik untuk filter
        $racks = $items->pluck('rack_location')->unique()->sort()->values();
        $categories = $items->pluck('category_name')->unique()->sort()->values();

        return view('warehouse.pwa.session', compact('stockOpname', 'items', 'racks', 'categories', 'isManager', 'user'));
    }

    /**
     * Update hitungan fisik satu produk secara atomik dari mobile PWA (Real-time auto save).
     */
    public function updateItem(Request $request, StockOpname $stockOpname): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|uuid',
            'actual_quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $item = $this->warehouseService->updateOpnameItemCount(
                opname: $stockOpname,
                productId: $request->product_id,
                actualQuantity: (float) $request->actual_quantity,
                notes: $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Hitungan fisik berhasil disimpan.',
                'item_id' => $item->id,
                'product_id' => $item->product_id,
                'actual_quantity' => (float) $item->actual_quantity,
                'notes' => $item->notes,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Submit seluruh hasil hitungan fisik sesi opname (Mengubah status jadi PENDING_APPROVAL).
     */
    public function submit(Request $request, StockOpname $stockOpname): JsonResponse|RedirectResponse
    {
        try {
            // Ambil hitungan aktual dari request jika dikirimkan, atau dari data tersimpan di DB
            $counts = $request->input('counts', []);
            if (empty($counts)) {
                $counts = $stockOpname->items->pluck('actual_quantity', 'product_id')->toArray();
            }
            $notes = $request->input('notes', []);
            if (empty($notes)) {
                $notes = $stockOpname->items->pluck('notes', 'product_id')->toArray();
            }

            $updated = $this->warehouseService->submitBlindCounts(
                opname: $stockOpname,
                actualCounts: $counts,
                counter: Auth::user(),
                actualNotes: $notes
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Hitungan fisik sesi {$updated->opname_number} berhasil diselesaikan. Menunggu persetujuan manajer di Back-Office.",
                    'redirect' => route('warehouse.opname-pwa.index'),
                ]);
            }

            return redirect()->route('warehouse.opname-pwa.index')
                ->with('success', "Hitungan fisik sesi {$updated->opname_number} berhasil diselesaikan dan dikirim ke Manajer!");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Web App Manifest untuk PWA installable.
     */
    public function manifest(): JsonResponse
    {
        return response()->json([
            'name' => 'SIGMA Warehouse PWA',
            'short_name' => 'SIGMA Opname',
            'description' => 'Aplikasi Mobile Stock Opname Fisik Gudang & Toko SIGMA ERP',
            'start_url' => route('warehouse.opname-pwa.index'),
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#0f172a',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => asset('favicon.ico'),
                    'sizes' => '64x64 32x32 24x24 16x16',
                    'type' => 'image/x-icon',
                ],
            ],
        ]);
    }
}
