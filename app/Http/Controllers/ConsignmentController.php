<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ConsignmentReceipt;
use App\Models\ConsignmentReceiptItem;
use App\Models\ConsignmentSettlement;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\ConsignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    public function __construct(
        protected ConsignmentService $consignmentService
    ) {}

    /**
     * Daftar Penerimaan Barang Titipan Konsinyasi (Consignment In).
     */
    public function receipts(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $query = ConsignmentReceipt::where('company_id', $company->id)
            ->with(['supplier', 'store', 'receiver', 'items.product'])
            ->latest('receipt_date')
            ->latest('created_at');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $receipts = $query->paginate(15)->withQueryString();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'receipts' => $receipts,
            ]);
        }

        return view('warehouse.consignments.receipts.index', compact('receipts', 'stores', 'suppliers', 'company'));
    }

    /**
     * Form Penerimaan Barang Titipan Konsinyasi Baru.
     */
    public function createReceipt(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->with(['units'])
            ->orderBy('name')
            ->get();

        return view('warehouse.consignments.receipts.create', compact('stores', 'suppliers', 'products', 'company'));
    }

    /**
     * Simpan Penerimaan Konsinyasi Baru.
     */
    public function storeReceipt(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid|exists:products,id',
            'items.*.product_unit_id' => 'required|uuid|exists:product_units,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        try {
            $store = Store::findOrFail($validated['store_id']);
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $user = Auth::user();

            $receipt = $this->consignmentService->receiveConsignment(
                store: $store,
                supplier: $supplier,
                items: $validated['items'],
                user: $user,
                notes: $validated['notes'] ?? null
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Penerimaan barang titipan konsinyasi berhasil disimpan.',
                    'receipt' => $receipt,
                ]);
            }

            return redirect()->route('warehouse.consignments.receipts.index')
                ->with('success', "Penerimaan konsinyasi {$receipt->receipt_number} dari {$supplier->name} berhasil disimpan.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal menyimpan penerimaan: '.$e->getMessage()]);
        }
    }

    /**
     * Daftar Tagihan & Rekonsiliasi Konsinyasi (Settlements).
     */
    public function settlements(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $query = ConsignmentSettlement::where('company_id', $company->id)
            ->with(['supplier', 'store', 'creator', 'items.product'])
            ->latest('settlement_date')
            ->latest('created_at');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', strtoupper($request->payment_status));
        }

        $settlements = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'settlements' => $settlements,
            ]);
        }

        return view('warehouse.consignments.settlements.index', compact('settlements', 'suppliers', 'stores', 'company'));
    }

    /**
     * Form Buat Tagihan Rekonsiliasi Konsinyasi.
     */
    public function createSettlement(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('warehouse.consignments.settlements.create', compact('stores', 'suppliers', 'company'));
    }

    /**
     * API JSON Barang Konsinyasi yang Terjual & Belum Diselesaikan (Unsettled Items).
     */
    public function getUnsettledItems(Request $request, string $supplierId): JsonResponse
    {
        $storeId = $request->input('store_id');

        $query = ConsignmentReceiptItem::whereHas('receipt', function ($q) use ($supplierId, $storeId) {
            $q->where('supplier_id', $supplierId);
            if ($storeId) {
                $q->where('store_id', $storeId);
            }
        })
            ->with(['product', 'unit', 'receipt'])
            ->whereRaw('sold_quantity > settled_quantity');

        $items = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'receipt_number' => $item->receipt->receipt_number,
                'receipt_date' => $item->receipt->receipt_date->format('d/m/Y'),
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'sku' => $item->product->sku,
                'unit_name' => $item->unit->unit_name,
                'total_received' => (float) $item->quantity,
                'sold_quantity' => (float) $item->sold_quantity,
                'settled_quantity' => (float) $item->settled_quantity,
                'unsettled_quantity' => (float) $item->unsettled_sold_quantity,
                'unit_cost' => (float) $item->unit_cost,
                'total_unsettled_cost' => round((float) $item->unsettled_sold_quantity * (float) $item->unit_cost, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }

    /**
     * Simpan Tagihan Settlement Konsinyasi & Jurnal Akuntansi.
     */
    public function storeSettlement(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'selections' => 'required|array|min:1',
            'selections.*.consignment_receipt_item_id' => 'required|uuid|exists:consignment_receipt_items,id',
            'selections.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $store = Store::findOrFail($validated['store_id']);
            $supplier = Supplier::findOrFail($validated['supplier_id']);
            $user = Auth::user();

            $settlement = $this->consignmentService->createSettlement(
                store: $store,
                supplier: $supplier,
                settlementSelections: $validated['selections'],
                user: $user,
                notes: $validated['notes'] ?? null,
                startDate: $validated['period_start'] ?? null,
                endDate: $validated['period_end'] ?? null
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tagihan rekonsiliasi konsinyasi berhasil dibuat.',
                    'settlement' => $settlement,
                ]);
            }

            return redirect()->route('warehouse.consignments.settlements.index')
                ->with('success', "Tagihan konsinyasi {$settlement->settlement_number} sebesar Rp ".number_format($settlement->total_settlement_amount, 0, ',', '.').' berhasil diakui.');
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->withErrors(['error' => 'Gagal membuat tagihan: '.$e->getMessage()]);
        }
    }

    /**
     * Pelunasan Pembayaran Tagihan Konsinyasi ke Supplier.
     */
    public function paySettlement(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:CASH,TRANSFER',
            'bank_name' => 'nullable|string|max:50',
        ]);

        try {
            $settlement = ConsignmentSettlement::findOrFail($id);
            $user = Auth::user();

            $this->consignmentService->paySettlement(
                settlement: $settlement,
                paymentMethod: $validated['payment_method'],
                user: $user,
                bankName: $validated['bank_name'] ?? null
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pembayaran tagihan konsinyasi berhasil diselesaikan.',
                ]);
            }

            return redirect()->route('warehouse.consignments.settlements.index')
                ->with('success', "Tagihan konsinyasi {$settlement->settlement_number} telah lunas dibayar.");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal memproses pembayaran: '.$e->getMessage()]);
        }
    }
}
