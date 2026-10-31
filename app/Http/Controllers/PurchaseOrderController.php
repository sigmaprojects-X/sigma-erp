<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\PurchasingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchasingService $purchasingService
    ) {}

    /**
     * Dashboard Rekomendasi Restock Cerdas (Smart Replenishment).
     */
    public function recommendations(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::where('company_id', $company->id)->firstOrFail();
        $supplierId = $request->query('supplier_id');

        $recommendations = $this->purchasingService->getRestockRecommendations($store, $supplierId);
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count' => $recommendations->count(),
                'recommendations' => $recommendations->values(),
            ]);
        }

        return view('purchases.recommendations', compact('recommendations', 'suppliers', 'store', 'company'));
    }

    /**
     * Daftar Purchase Order (PO).
     */
    public function index(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::where('company_id', $company->id)->firstOrFail();

        $query = PurchaseOrder::where('company_id', $company->id)
            ->with(['supplier', 'store', 'creator', 'approver', 'items.product'])
            ->withCount('items')
            ->latest('order_date')
            ->latest('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $likeOp = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
            $query->where(function ($w) use ($q, $likeOp) {
                $w->where('po_number', $likeOp, "%{$q}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', $likeOp, "%{$q}%"));
            });
        }

        $purchaseOrders = $query->paginate(15)->withQueryString();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('purchases.orders.index', compact('purchaseOrders', 'suppliers', 'store', 'company'));
    }

    /**
     * Form Buat Purchase Order Baru (atau dari Rekomendasi Restock).
     */
    public function create(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::where('company_id', $company->id)->firstOrFail();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $selectedSupplierId = $request->query('supplier_id');
        $selectedProductIds = array_filter(explode(',', (string) $request->query('product_ids', '')));

        return view('purchases.orders.create', compact('company', 'store', 'suppliers', 'selectedSupplierId', 'selectedProductIds'));
    }

    /**
     * Simpan Purchase Order Baru (Status DRAFT).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'required|uuid',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.product_unit_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::where('company_id', $company->id)->firstOrFail();
        $supplier = Supplier::findOrFail($request->supplier_id);

        $po = $this->purchasingService->createPurchaseOrder(
            company: $company,
            store: $store,
            supplier: $supplier,
            items: $request->items,
            orderDate: $request->order_date,
            expectedDeliveryDate: $request->expected_delivery_date,
            notes: $request->notes,
            userId: Auth::id()
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$po->po_number} berhasil dibuat (Status DRAFT).",
                'purchase_order' => $po,
            ]);
        }

        return redirect()->route('purchases.orders.show', $po->id)
            ->with('success', "Purchase Order {$po->po_number} berhasil dibuat!");
    }

    /**
     * Tampilan Detail Purchase Order & Riwayat Penerimaan Barang.
     */
    public function show(string $id): View
    {
        $purchaseOrder = PurchaseOrder::with([
            'company',
            'store',
            'supplier',
            'creator',
            'approver',
            'items.product',
            'items.productUnit',
            'receipts.items',
            'goodsReceipts.items',
            'goodsReceipts.purchase',
        ])->findOrFail($id);

        return view('purchases.orders.show', compact('purchaseOrder'));
    }

    /**
     * Otorisasi / Approval PO menjadi status ORDERED.
     */
    public function approve(string $id): JsonResponse|RedirectResponse
    {
        $po = PurchaseOrder::findOrFail($id);
        $user = Auth::user();

        if (! in_array($user?->role, ['owner', 'manager'], true)) {
            abort(403, 'Hanya Owner atau Manager yang memiliki wewenang menyetujui Purchase Order.');
        }

        $approvedPo = $this->purchasingService->approvePurchaseOrder($po, $user);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase Order {$approvedPo->po_number} telah disetujui (Status: ORDERED).",
                'purchase_order' => $approvedPo,
            ]);
        }

        return redirect()->route('purchases.orders.show', $approvedPo->id)
            ->with('success', "Purchase Order {$approvedPo->po_number} berhasil disetujui!");
    }

    /**
     * Eksekusi Three-Way Matching Penerimaan Barang dari PO.
     */
    public function receive(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $request->validate([
            'invoice_number' => 'required|string|max:100',
            'payment_type' => 'required|in:CASH,TRANSFER,TEMPO',
            'due_date' => 'nullable|date',
            'bank_account_id' => 'nullable|uuid',
            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);

        $po = PurchaseOrder::with(['items.product', 'company', 'store', 'supplier'])->findOrFail($id);

        try {
            $purchase = $this->purchasingService->receiveGoodsFromPo(
                po: $po,
                receivedItems: $request->items,
                invoiceNumber: $request->invoice_number,
                paymentType: $request->payment_type,
                dueDate: $request->due_date,
                bankAccountId: $request->bank_account_id,
                userId: Auth::id()
            );

            \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Penerimaan barang dari PO {$po->po_number} berhasil diproses. Faktur No: {$purchase->reference_number}.",
                    'purchase' => $purchase,
                    'po_status' => $po->fresh()->status,
                ]);
            }

            return redirect()->route('purchases.show', $purchase->id)
                ->with('success', "Penerimaan barang dari PO {$po->po_number} berhasil diproses!");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

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
     * Pembatalan Purchase Order & Pelepasan Alokasi Stok.
     */
    public function cancel(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $po = PurchaseOrder::findOrFail($id);
        $user = Auth::user();

        if (! in_array($user?->role, ['owner', 'manager', 'purchasing'], true)) {
            abort(403, 'Anda tidak memiliki wewenang membatalkan Purchase Order.');
        }

        try {
            $reason = $request->input('reason', 'Dibatalkan oleh staf pengguna.');
            $cancelledPo = $this->purchasingService->cancelPurchaseOrder($po, $user, $reason);

            \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Purchase Order {$cancelledPo->po_number} berhasil dibatalkan.",
                    'purchase_order' => $cancelledPo,
                ]);
            }

            return redirect()->route('purchases.orders.show', $cancelledPo->id)
                ->with('success', "Purchase Order {$cancelledPo->po_number} berhasil dibatalkan!");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Buku Pembantu Hutang Supplier (Accounts Payable Aging Ledger).
     */
    public function debtLedger(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::where('company_id', $company->id)->firstOrFail();
        $supplierId = $request->query('supplier_id');

        $debts = $this->purchasingService->getAccountsPayableLedger($store, $supplierId);
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('company_id', $company->id)->where('is_active', true)->get();
        $payments = SupplierPayment::where('company_id', $company->id)
            ->with(['supplier', 'bankAccount', 'creator'])
            ->latest('payment_date')
            ->latest('created_at')
            ->take(30)
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'total_remaining_debt' => $debts->sum('remaining_debt'),
                'debts' => $debts,
                'payments' => $payments,
            ]);
        }

        return view('purchases.debt_ledger', compact('debts', 'suppliers', 'bankAccounts', 'payments', 'store', 'company'));
    }

    /**
     * Cetak Surat Pesanan Pembelian Resmi (Official Purchase Order - A4).
     */
    public function print(string $id): View
    {
        $purchaseOrder = PurchaseOrder::with([
            'company',
            'store',
            'supplier',
            'creator',
            'approver',
            'items.product',
            'items.productUnit',
        ])->findOrFail($id);

        $terbilang = SalesController::terbilang((float) $purchaseOrder->grand_total) . ' Rupiah';

        return view('purchases.orders.print', compact('purchaseOrder', 'terbilang'));
    }

    /**
     * Cetak Bukti Pembayaran Hutang Supplier (Payment Voucher - A4).
     */
    public function printPayment(string $id): View
    {
        $payment = SupplierPayment::with([
            'company',
            'store',
            'supplier',
            'bankAccount',
            'creator',
            'items.purchase',
        ])->findOrFail($id);

        $terbilang = SalesController::terbilang((float) $payment->total_amount) . ' Rupiah';

        return view('purchases.payments.print', compact('payment', 'terbilang'));
    }

    /**
     * Proses Pelunasan Hutang Supplier (AP Payment).
     */
    public function paySupplier(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'supplier_id' => 'nullable|uuid',
            'payment_method' => 'required|in:CASH,TRANSFER',
            'bank_account_id' => 'nullable|uuid',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'invoices' => 'required|array|min:1',
            'invoices.*.purchase_id' => 'required|uuid',
            'invoices.*.amount' => 'required|numeric|min:0.01',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $store = Store::where('company_id', $company->id)->firstOrFail();
        $supplierId = $request->supplier_id;
        if (! $supplierId && ! empty($request->invoices[0]['purchase_id'])) {
            $supplierId = Purchase::findOrFail($request->invoices[0]['purchase_id'])->supplier_id;
        }
        $supplier = Supplier::findOrFail($supplierId);

        try {
            $payment = $this->purchasingService->recordSupplierPayment(
                company: $company,
                store: $store,
                supplier: $supplier,
                invoicesToPay: $request->invoices,
                paymentMethod: $request->payment_method,
                bankAccountId: $request->bank_account_id,
                referenceNumber: $request->reference_number,
                notes: $request->notes,
                userId: Auth::id()
            );

            \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pelunasan hutang supplier {$supplier->name} berhasil dicatat (No: {$payment->payment_number}).",
                    'payment' => $payment,
                    'print_url' => route('purchases.payments.print', $payment->id),
                ]);
            }

            return redirect()->route('purchases.debt-ledger')
                ->with('success', "Pelunasan hutang No {$payment->payment_number} berhasil diproses!")
                ->with('payment_id', $payment->id);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::forget('sidebar_overdue_debt_count');

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
     * Export Rekapitulasi Buku Pembantu Hutang Supplier (Accounts Payable) ke CSV.
     */
    public function exportDebtLedger(Request $request): StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $unpaidPurchases = Purchase::with('supplier')
            ->where('company_id', $company->id)
            ->where('status', 'RECEIVED')
            ->where('payment_type', 'TEMPO')
            ->whereColumn('paid_amount', '<', 'grand_total')
            ->orderBy('due_date')
            ->get();

        $filename = 'rekap_buku_pembantu_hutang_' . date('Ymd_His') . '.csv';

        return response()->stream(function () use ($unpaidPurchases) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, [
                'No. Bukti Faktur',
                'No. Faktur Supplier',
                'Tanggal Pembelian',
                'Tanggal Jatuh Tempo',
                'Nama Supplier',
                'Telepon Supplier',
                'Total Tagihan (Rp)',
                'Sudah Dibayar (Rp)',
                'Sisa Hutang (Rp)',
                'Hari Keterlambatan',
                'Status Aging Hutang',
            ]);

            $today = today();
            foreach ($unpaidPurchases as $p) {
                $remaining = max(0, (float) $p->grand_total - (float) $p->paid_amount);
                $days = $p->due_date ? (int) Carbon::parse($p->due_date)->diffInDays($today, false) : 0;

                $agingStatus = 'Belum Jatuh Tempo';
                if ($days > 0) {
                    if ($days <= 30) {
                        $agingStatus = 'Overdue 1-30 Hari';
                    } elseif ($days <= 60) {
                        $agingStatus = 'Overdue 31-60 Hari';
                    } else {
                        $agingStatus = 'Overdue >60 Hari';
                    }
                }

                fputcsv($handle, [
                    $p->reference_number,
                    $p->invoice_number,
                    $p->purchase_date ? date('d/m/Y', strtotime($p->purchase_date)) : '-',
                    $p->due_date ? date('d/m/Y', strtotime($p->due_date)) : '-',
                    $p->supplier?->name ?? 'Supplier Umum',
                    $p->supplier?->phone ?? '-',
                    number_format((float) $p->grand_total, 2, ',', '.'),
                    number_format((float) $p->paid_amount, 2, ',', '.'),
                    number_format($remaining, 2, ',', '.'),
                    $days > 0 ? $days : 0,
                    $agingStatus,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

}
