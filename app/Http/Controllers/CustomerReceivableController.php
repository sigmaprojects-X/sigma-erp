<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Store;
use App\Services\CustomerReceivableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerReceivableController extends Controller
{
    public function __construct(
        protected CustomerReceivableService $receivableService
    ) {}

    /**
     * Daftar Piutang Usaha Pelanggan (Accounts Receivable Ledger Overview).
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Customer::with(['customerTier', 'store'])
            ->where('current_credit_balance', '>', 0);

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%")
                    ->orWhere('phone', 'ilike', "%{$q}%");
            });
        }

        if ($request->filled('tier_id')) {
            $query->where('customer_tier_id', $request->tier_id);
        }

        $customers = $query->orderBy('current_credit_balance', 'desc')->paginate(15)->withQueryString();

        // Ringkasan Finansial Piutang
        $totalReceivable = (float) Customer::sum('current_credit_balance');
        $totalCustomerInDebt = Customer::where('current_credit_balance', '>', 0)->count();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'customers' => $customers,
                'total_receivable' => $totalReceivable,
                'total_customer_in_debt' => $totalCustomerInDebt,
            ]);
        }

        return view('customers.receivables.index', compact('customers', 'totalReceivable', 'totalCustomerInDebt'));
    }

    /**
     * Kartu Piutang / Buku Pembantu Piutang per Pelanggan.
     */
    public function ledger(string $customerId): View|JsonResponse
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $customer = Customer::with(['customerTier', 'store'])->where('company_id', $companyId)->findOrFail($customerId);
        $ledgerEntries = $this->receivableService->getCustomerLedger($customer);
        $outstandingInvoices = $this->receivableService->getOutstandingInvoices($customer);

        return view('customers.receivables.ledger', compact('customer', 'ledgerEntries', 'outstandingInvoices'));
    }

    /**
     * Form Catat Pembayaran / Pelunasan Piutang Pelanggan.
     */
    public function createPayment(string $customerId): View
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $customer = Customer::with(['customerTier', 'store'])->where('company_id', $companyId)->findOrFail($customerId);
        $outstandingInvoices = $this->receivableService->getOutstandingInvoices($customer);
        $bankAccounts = BankAccount::where('company_id', $customer->company_id)->where('is_active', true)->get();
        $stores = Store::where('company_id', $customer->company_id)->where('is_active', true)->get();

        return view('customers.receivables.create_payment', compact('customer', 'outstandingInvoices', 'bankAccounts', 'stores'));
    }

    /**
     * Simpan Pelunasan Piutang Pelanggan & Posting Jurnal Double-Entry.
     */
    public function storePayment(Request $request, string $customerId): JsonResponse|RedirectResponse
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $customer = Customer::where('company_id', $companyId)->findOrFail($customerId);

        $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:CASH,TRANSFER',
            'total_amount' => 'required|numeric|min:1',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'allocations' => 'nullable|array',
            'allocations.*' => 'nullable|numeric|min:0',
        ]);

        try {
            $payment = $this->receivableService->recordPayment(
                customer: $customer,
                data: $request->all(),
                receiver: Auth::user()
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pembayaran piutang berhasil dicatat dengan No. Kwitansi: {$payment->payment_number}",
                    'payment' => $payment,
                ]);
            }

            return redirect()->route('customers.receivables.receipt', $payment->id)
                ->with('success', "Pembayaran piutang Rp ".number_format($payment->total_amount, 0, ',', '.')." berhasil dicatat! No Kwitansi: {$payment->payment_number}");
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Tampilkan Kwitansi Resmi Pembayaran Piutang (Print & Audit Ready).
     */
    public function showReceipt(string $paymentId): View
    {
        $companyId = app(\App\Services\CompanyService::class)->getActiveCompanyId();
        $payment = CustomerPayment::with(['customer', 'store', 'receiver', 'items.transaction'])->where('company_id', $companyId)->findOrFail($paymentId);

        return view('customers.receivables.receipt', compact('payment'));
    }
}
