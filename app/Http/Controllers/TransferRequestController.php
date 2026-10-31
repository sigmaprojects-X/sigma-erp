<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use App\Models\TransferRequest;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TransferRequestController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    /**
     * Daftar Permintaan Transfer Cabang.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $query = TransferRequest::where('company_id', $company->id)
            ->with(['requestingStore', 'destinationStore', 'creator', 'approver', 'stockTransfer'])
            ->latest('request_date')
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('requesting_store_id')) {
            $query->where('requesting_store_id', $request->requesting_store_id);
        }

        if ($request->filled('destination_store_id')) {
            $query->where('destination_store_id', $request->destination_store_id);
        }

        $requests = $query->paginate(15)->withQueryString();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'requests' => $requests,
            ]);
        }

        return view('warehouse.requests.index', compact('requests', 'stores', 'company'));
    }

    /**
     * Form Buat Permintaan Transfer Cabang Baru.
     */
    public function create(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $userStore = Auth::user()?->store ?? $stores->first();

        // Default destination store: Toko bertipe HO / Pusat, atau store pertama yang beda
        $destinationStore = Store::where('company_id', $company->id)
            ->where('id', '!=', $userStore?->id)
            ->where(function ($q) {
                $q->where('code', 'LIKE', '%HO%')
                  ->orWhere('name', 'LIKE', '%Pusat%')
                  ->orWhere('name', 'LIKE', '%Gudang%');
            })
            ->first() ?? $stores->where('id', '!=', $userStore?->id)->first();

        return view('warehouse.requests.create', compact('company', 'stores', 'userStore', 'destinationStore'));
    }

    /**
     * Simpan Permintaan Transfer Cabang.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'requesting_store_id' => 'required|uuid|different:destination_store_id',
            'destination_store_id' => 'required|uuid',
            'request_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $requestingStore = Store::findOrFail($request->requesting_store_id);
        $destinationStore = Store::findOrFail($request->destination_store_id);

        try {
            $transferRequest = $this->warehouseService->createTransferRequest(
                company: $company,
                requestingStore: $requestingStore,
                destinationStore: $destinationStore,
                items: $request->items,
                requestDate: $request->request_date,
                notes: $request->notes,
                user: Auth::user()
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Permintaan transfer cabang berhasil dibuat.',
                    'request' => $transferRequest,
                ]);
            }

            return redirect()->route('warehouse.requests.show', $transferRequest->id)
                ->with('success', "Permintaan transfer {$transferRequest->request_number} berhasil diajukan.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Detail Permintaan Transfer Cabang.
     */
    public function show(TransferRequest $transferRequest): View
    {
        $transferRequest->load([
            'requestingStore',
            'destinationStore',
            'creator',
            'approver',
            'stockTransfer',
            'items.product.category',
            'items.product.stocks',
            'items.unit',
        ]);

        return view('warehouse.requests.show', compact('transferRequest'));
    }

    /**
     * Setujui Permintaan Transfer Cabang (Full / Partial) dan Auto-Generate Surat Jalan Transfer.
     */
    public function approve(Request $request, TransferRequest $transferRequest): JsonResponse|RedirectResponse
    {
        $request->validate([
            'approved_items' => 'required|array|min:1',
            'approved_items.*.item_id' => 'required|uuid',
            'approved_items.*.approved_quantity' => 'required|numeric|min:0',
            'generate_stock_transfer' => 'nullable|boolean',
        ]);

        try {
            $generateTransfer = $request->boolean('generate_stock_transfer', true);

            $updated = $this->warehouseService->approveTransferRequest(
                transferRequest: $transferRequest,
                approvedItems: $request->approved_items,
                approverUser: Auth::user(),
                generateStockTransfer: $generateTransfer
            );

            $msg = "Permintaan transfer {$updated->request_number} berhasil disetujui (Status: {$updated->status}).";
            if ($updated->stockTransfer) {
                $msg .= " Surat jalan pengiriman {$updated->stockTransfer->transfer_number} telah dibuat otomatis.";
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'request' => $updated,
                ]);
            }

            return redirect()->route('warehouse.requests.show', $transferRequest->id)->with('success', $msg);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak Permintaan Transfer Cabang.
     */
    public function reject(Request $request, TransferRequest $transferRequest): JsonResponse|RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $updated = $this->warehouseService->rejectTransferRequest(
                transferRequest: $transferRequest,
                reason: $request->rejection_reason,
                approverUser: Auth::user()
            );

            $msg = "Permintaan transfer {$updated->request_number} telah ditolak.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'request' => $updated,
                ]);
            }

            return redirect()->route('warehouse.requests.show', $transferRequest->id)->with('warning', $msg);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * API Smart Restock Rekomendasi Cabang.
     */
    public function smartRestock(Request $request): JsonResponse
    {
        $requestingStoreId = $request->query('requesting_store_id', Auth::user()?->store_id);
        if (!$requestingStoreId) {
            return response()->json(['success' => false, 'message' => 'Store ID wajib diisi.'], 400);
        }

        $requestingStore = Store::findOrFail($requestingStoreId);
        $sourceStore = $request->filled('source_store_id') ? Store::find($request->source_store_id) : null;
        $days = (int) $request->query('days', 30);

        $recommendations = $this->warehouseService->getSmartRestockBranchRecommendations(
            requestingStore: $requestingStore,
            sourceStore: $sourceStore,
            analysisDays: $days
        );

        return response()->json([
            'success' => true,
            'count' => $recommendations->count(),
            'critical_count' => $recommendations->where('status', 'CRITICAL')->count(),
            'reorder_count' => $recommendations->where('status', 'REORDER')->count(),
            'overstock_count' => $recommendations->where('status', 'OVERSTOCK')->count(),
            'optimal_count' => $recommendations->where('status', 'OPTIMAL')->count(),
            'recommendations' => $recommendations,
        ]);
    }
}
