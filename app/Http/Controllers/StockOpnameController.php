<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\StockOpname;
use App\Models\Store;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    /**
     * Daftar Sesi Stock Opname.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $query = StockOpname::where('company_id', $company->id)
            ->with(['store', 'creator', 'approver'])
            ->latest('opname_date')
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $opnames = $query->paginate(15)->withQueryString();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'opnames' => $opnames,
            ]);
        }

        return view('warehouse.opnames.index', compact('opnames', 'stores', 'company'));
    }

    /**
     * Form Mulai Sesi Stock Opname Baru.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        return view('warehouse.opnames.create', compact('company', 'stores'));
    }

    /**
     * Inisiasi Sesi Stock Opname (Status: IN_PROGRESS).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|uuid',
            'opname_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = Store::findOrFail($request->store_id);

        $opname = $this->warehouseService->startOpname(
            company: $company,
            store: $store,
            opnameDate: $request->opname_date,
            notes: $request->notes,
            userId: Auth::id()
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Sesi Stock Opname {$opname->opname_number} berhasil dimulai.",
                'opname' => $opname,
            ]);
        }

        return redirect()->route('warehouse.opnames.show', $opname->id)
            ->with('success', "Sesi Stock Opname {$opname->opname_number} berhasil dimulai!");
    }

    /**
     * Detail Sesi Stock Opname & Form Blind Count Input.
     * Menerapkan aturan Blind Stock Opname: Staf gudang TIDAK boleh melihat system_qty (AGENTS.md Section 2).
     */
    public function show(string $id): View|JsonResponse
    {
        $opname = StockOpname::with(['store', 'creator', 'approver', 'items.product.category', 'items.product.barcodes', 'items.product.units', 'items.product.baseUnit'])->findOrFail($id);
        $user = Auth::user();

        // Cek wewenang: apakah user adalah Manager / Owner
        $isManager = in_array($user?->role, ['owner', 'manager', 'store_head'], true);

        // Jika opname adalah blind dan user bukan manager, sembunyikan system_qty
        if ($opname->is_blind && ! $isManager && in_array($opname->status, ['IN_PROGRESS', 'PENDING_APPROVAL'], true)) {
            $items = $opname->items->map(function ($it) {
                return [
                    'id' => $it->id,
                    'product_id' => $it->product_id,
                    'product_name' => $it->product->name,
                    'sku' => $it->product->sku,
                    'actual_quantity' => (float) $it->actual_quantity,
                    'notes' => $it->notes,
                    // system_quantity & discrepancy_quantity disembunyikan (MASKED)
                    'system_quantity' => null,
                    'discrepancy_quantity' => null,
                ];
            });
        } else {
            $items = $opname->items;
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_manager' => $isManager,
                'is_blind' => $opname->is_blind,
                'opname' => $opname,
                'items' => $items,
            ]);
        }

        return view('warehouse.opnames.show', compact('opname', 'items', 'isManager'));
    }

    /**
     * Staf Gudang Menginput Hasil Hitungan Fisik (Blind Count Input).
     */
    public function submitCount(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $counts = $request->input('counts') ?: $request->input('actual_counts', []);
        $notes = $request->input('notes') ?: $request->input('actual_notes', []);

        if (empty($counts) || ! is_array($counts)) {
            return back()->withErrors(['counts' => 'Kuantitas hitungan fisik harus diisi.']);
        }

        $opname = StockOpname::with('items')->findOrFail($id);

        try {
            $updatedOpname = $this->warehouseService->submitBlindCounts($opname, $counts, Auth::user(), $notes);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Hitungan fisik Stock Opname {$updatedOpname->opname_number} berhasil disimpan dan dikirim untuk persetujuan manajer.",
                    'opname' => $updatedOpname,
                ]);
            }

            return redirect()->route('warehouse.opnames.show', $updatedOpname->id)
                ->with('success', "Hitungan fisik berhasil disimpan!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Manager Menyetujui Rekonsiliasi & Penyesuaian Selisih Stok (Adjustment).
     */
    public function approve(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $opname = StockOpname::with('items.product')->findOrFail($id);
        $user = Auth::user();

        if (! in_array($user?->role, ['owner', 'manager', 'store_head'], true)) {
            abort(403, 'Hanya Manajer, Kepala Toko, atau Owner yang memiliki wewenang menyetujui rekonsiliasi stock opname.');
        }

        try {
            $completedOpname = $this->warehouseService->approveOpnameReconciliation(
                opname: $opname,
                manager: $user,
                approvalNotes: $request->input('approval_notes')
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Rekonsiliasi Stock Opname {$completedOpname->opname_number} berhasil disetujui. Penyesuaian stok dan jurnal selisih telah dibukukan.",
                    'opname' => $completedOpname,
                ]);
            }

            return redirect()->route('warehouse.opnames.show', $completedOpname->id)
                ->with('success', "Stock Opname {$completedOpname->opname_number} berhasil disetujui dan stok telah disesuaikan!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
