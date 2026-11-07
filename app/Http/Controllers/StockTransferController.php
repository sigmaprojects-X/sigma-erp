<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockTransferController extends Controller
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    /**
     * Daftar Transfer Antar Cabang.
     */
    public function index(Request $request): View|JsonResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $query = StockTransfer::where('company_id', $company->id)
            ->with(['sourceStore', 'destinationStore', 'creator', 'dispatcher', 'receiver', 'items.product'])
            ->latest('transfer_date')
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source_store_id')) {
            $query->where('source_store_id', $request->source_store_id);
        }

        if ($request->filled('destination_store_id')) {
            $query->where('destination_store_id', $request->destination_store_id);
        }

        $transfers = $query->paginate(15)->withQueryString();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'transfers' => $transfers,
            ]);
        }

        return view('warehouse.transfers.index', compact('transfers', 'stores', 'company'));
    }

    /**
     * Form Buat Transfer Antar Cabang Baru.
     */
    public function create(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $products = Product::where('company_id', $company->id)->where('is_active', true)->with('units')->get();

        return view('warehouse.transfers.create', compact('company', 'stores', 'products'));
    }

    /**
     * Simpan Draft Transfer Antar Cabang.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'source_store_id' => 'required|uuid|different:destination_store_id',
            'destination_store_id' => 'required|uuid',
            'transfer_date' => 'required|date',
            'driver_name' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $sourceStore = Store::where('company_id', $company->id)->findOrFail($request->source_store_id);
        $destinationStore = Store::where('company_id', $company->id)->findOrFail($request->destination_store_id);

        $transfer = $this->warehouseService->createTransfer(
            company: $company,
            sourceStore: $sourceStore,
            destinationStore: $destinationStore,
            items: $request->items,
            transferDate: $request->transfer_date,
            driverName: $request->driver_name,
            vehicleNumber: $request->vehicle_number,
            notes: $request->notes,
            userId: Auth::id()
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Transfer {$transfer->transfer_number} berhasil dibuat (Status: DRAFT).",
                'transfer' => $transfer,
            ]);
        }

        return redirect()->route('warehouse.transfers.show', $transfer->id)
            ->with('success', "Transfer {$transfer->transfer_number} berhasil dibuat!");
    }

    /**
     * Tampilan Detail Transfer Antar Cabang.
     */
    public function show(string $id): View
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $transfer = StockTransfer::with([
            'sourceStore',
            'destinationStore',
            'creator',
            'dispatcher',
            'receiver',
            'items.product',
            'items.productUnit',
        ])->where('company_id', $companyId)->findOrFail($id);

        return view('warehouse.transfers.show', compact('transfer'));
    }

    /**
     * Cetak Surat Jalan Pengiriman Transfer Stok (Delivery Note).
     */
    public function printDeliveryNote(string $id): View
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $transfer = StockTransfer::with([
            'company',
            'sourceStore',
            'destinationStore',
            'creator',
            'dispatcher',
            'receiver',
            'items.product',
            'items.productUnit',
        ])->where('company_id', $companyId)->findOrFail($id);

        $company = $transfer->company;

        return view('warehouse.transfers.print', compact('transfer', 'company'));
    }

    /**
     * Kirim Barang Transfer (Dispatch -> IN_TRANSIT).
     */
    public function dispatch(string $id): JsonResponse|RedirectResponse
    {
        $transfer = StockTransfer::where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        try {
            $dispatched = $this->warehouseService->dispatchTransfer($transfer, Auth::user());

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transfer {$dispatched->transfer_number} berhasil dikirim (Status: IN_TRANSIT). Stok cabang asal telah berkurang.",
                    'transfer' => $dispatched,
                ]);
            }

            return redirect()->route('warehouse.transfers.show', $dispatched->id)
                ->with('success', "Transfer {$dispatched->transfer_number} berhasil dikirim!");
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Konfirmasi Penerimaan Barang di Cabang Tujuan (RECEIVED).
     */
    public function receive(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $transfer = StockTransfer::with('items')->where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);
        $receivedQuantities = $request->input('received_quantities', []);

        try {
            $received = $this->warehouseService->receiveTransfer($transfer, $receivedQuantities, Auth::user());

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transfer {$received->transfer_number} berhasil diterima. Stok cabang tujuan telah bertambah.",
                    'transfer' => $received,
                ]);
            }

            return redirect()->route('warehouse.transfers.show', $received->id)
                ->with('success', "Transfer {$received->transfer_number} berhasil diterima di cabang tujuan!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pembatalan Transfer Antar Cabang (DRAFT) & Pelepasan Alokasi Stok.
     */
    public function cancel(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $transfer = StockTransfer::where('company_id', app(\App\Services\CompanyService::class)->getActiveCompanyId())->findOrFail($id);

        try {
            $reason = $request->input('reason', 'Dibatalkan oleh staf.');
            $cancelled = $this->warehouseService->cancelTransfer($transfer, Auth::user(), $reason);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Transfer {$cancelled->transfer_number} berhasil dibatalkan.",
                    'transfer' => $cancelled,
                ]);
            }

            return redirect()->route('warehouse.transfers.show', $cancelled->id)
                ->with('success', "Transfer {$cancelled->transfer_number} berhasil dibatalkan!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}

