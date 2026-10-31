<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PurchasingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function __construct(
        protected PurchasingService $purchasingService
    ) {}

    /**
     * Daftar Laporan Penerimaan Barang (LPB) / Goods Receipts Gudang.
     */
    public function index(Request $request): View|JsonResponse
    {
        $status = $request->query('status');
        $supplierId = $request->query('supplier_id');
        $search = $request->query('search');

        $query = GoodsReceipt::with([
            'supplier',
            'purchaseOrder',
            'receiver',
            'items.product',
            'purchase',
        ])->latest('receipt_date')->latest('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'ilike', "%{$search}%")
                  ->orWhere('delivery_order_number', 'ilike', "%{$search}%")
                  ->orWhereHas('supplier', fn ($s) => $s->where('name', 'ilike', "%{$search}%"))
                  ->orWhereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'ilike', "%{$search}%"));
            });
        }

        $receipts = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $receipts,
            ]);
        }

        return view('purchases.receipts.index', compact('receipts', 'suppliers', 'status', 'supplierId', 'search'));
    }

    /**
     * Form Penerimaan Fisik Barang Gudang dari Purchase Order (PO).
     */
    public function create(string $poId): View
    {
        $purchaseOrder = PurchaseOrder::with([
            'supplier',
            'store',
            'company',
            'items.product',
            'items.productUnit',
        ])->findOrFail($poId);

        if (! in_array($purchaseOrder->status, ['ORDERED', 'PARTIALLY_RECEIVED'], true)) {
            return redirect()->route('purchases.orders.show', $purchaseOrder->id)
                ->with('error', "PO {$purchaseOrder->po_number} belum disetujui atau sudah selesai diterima (Status: {$purchaseOrder->status}).");
        }

        return view('purchases.receipts.create', compact('purchaseOrder'));
    }

    /**
     * Simpan Penerimaan Fisik Barang Gudang (LPB), Landed Cost, dan Update Stok.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'purchase_order_id' => 'required|uuid',
            'delivery_order_number' => 'required|string|max:100',
            'received_date' => 'required|date',
            'shipping_fee' => 'nullable|numeric|min:0',
            'handling_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|uuid',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.physical_condition' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        $po = PurchaseOrder::findOrFail($request->purchase_order_id);

        try {
            $itemsForService = [];
            foreach ($request->items as $item) {
                $itemsForService[] = [
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'quantity' => (float) ($item['received_quantity'] ?? $item['quantity'] ?? 0),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'physical_condition' => $item['physical_condition'] ?? 'BAIK',
                    'notes' => $item['notes'] ?? null,
                ];
            }

            $goodsReceipt = $this->purchasingService->createGoodsReceiptFromPo(
                po: $po,
                receivedItems: $itemsForService,
                deliveryOrderNumber: $request->delivery_order_number,
                receiptDate: $request->received_date,
                shippingCost: (float) ($request->shipping_fee ?? 0.0),
                handlingCost: (float) ($request->handling_fee ?? 0.0),
                notes: $request->notes,
                userId: Auth::id()
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Laporan Penerimaan Barang (LPB) {$goodsReceipt->receipt_number} berhasil dicatat.",
                    'goods_receipt' => $goodsReceipt,
                ]);
            }

            return redirect()->route('purchases.receipts.show', $goodsReceipt->id)
                ->with('success', "Laporan Penerimaan Barang (LPB) {$goodsReceipt->receipt_number} berhasil diterbitkan dan stok fisik bertambah!");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Tampilan Rincian Laporan Penerimaan Barang (LPB) & Alokasi Landed Cost.
     */
    public function show(string $id): View
    {
        $receipt = GoodsReceipt::with([
            'company',
            'store',
            'supplier',
            'purchaseOrder.creator',
            'receiver',
            'items.product',
            'items.unit',
            'items.purchaseOrderItem',
            'purchase.items',
        ])->findOrFail($id);

        return view('purchases.receipts.show', compact('receipt'));
    }

    /**
     * Cetak Surat LPB Gudang dengan Tanda Tangan 3 Pihak.
     */
    public function print(string $id): View
    {
        $receipt = GoodsReceipt::with([
            'company',
            'store',
            'supplier',
            'purchaseOrder',
            'receiver',
            'items.product',
            'items.unit',
        ])->findOrFail($id);

        return view('purchases.receipts.print', compact('receipt'));
    }

    /**
     * Form Three-Way Matching (Verifikasi Finance: PO vs LPB vs Vendor Invoice).
     */
    public function matchingForm(string $id): View|RedirectResponse
    {
        $receipt = GoodsReceipt::with([
            'company',
            'store',
            'supplier',
            'purchaseOrder.items.product',
            'receiver',
            'items.product',
            'items.purchaseOrderItem',
        ])->findOrFail($id);

        if ($receipt->status === 'MATCHED') {
            return redirect()->route('purchases.show', $receipt->purchase_id)
                ->with('info', "LPB {$receipt->receipt_number} sudah selesai dicocokkan (Faktur: {$receipt->purchase?->invoice_number}).");
        }

        $bankAccounts = BankAccount::where('company_id', $receipt->company_id)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('purchases.receipts.match', compact('receipt', 'bankAccounts'));
    }

    /**
     * Eksekusi Three-Way Matching, Pembuatan Faktur Pembelian (AP), & Kliring GRNI.
     */
    public function processMatching(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $receipt = GoodsReceipt::findOrFail($id);

        $request->validate([
            'invoice_number' => 'required|string|max:100',
            'purchase_date' => 'required|date',
            'payment_type' => 'required|in:CASH,TRANSFER,TEMPO',
            'due_date' => 'nullable|date',
            'bank_account_id' => 'nullable|uuid',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'item_prices' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $purchase = $this->purchasingService->performThreeWayMatching(
                receipt: $receipt,
                invoiceNumber: $request->invoice_number,
                purchaseDate: $request->purchase_date,
                paymentType: $request->payment_type,
                dueDate: $request->due_date,
                bankAccountId: $request->bank_account_id,
                taxAmount: (float) ($request->tax_amount ?? 0.0),
                discountAmount: (float) ($request->discount_amount ?? 0.0),
                invoiceItemPrices: $request->item_prices ?? [],
                notes: $request->notes,
                userId: Auth::id()
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Three-Way Matching berhasil! Faktur Pembelian {$purchase->invoice_number} telah diterbitkan dan hutang diakui.",
                    'purchase' => $purchase,
                ]);
            }

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Three-Way Matching Berhasil! Faktur Pembelian {$purchase->invoice_number} (Ref: {$purchase->reference_number}) telah diterbitkan dan jurnal GRNI selesai dikliring ke Hutang Usaha.");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
