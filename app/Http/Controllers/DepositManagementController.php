<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\DepositManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DepositManagementController extends Controller
{
    public function __construct(
        protected DepositManagementService $depositService
    ) {}

    /**
     * ========================================================
     * 1. DEPOSITO PELANGGAN (FITUR NO. 5 & 7 IPOS 5)
     * ========================================================
     */

    public function customerIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $search = $request->input('search');

        $customers = $this->depositService->getCustomerDepositsSummary($company, $search);
        $totalDepositBalance = Customer::where('company_id', $company->id)->sum('deposit_balance');

        $cashAccounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('type', 'ASSET')
            ->where(function ($q) {
                $q->where('code', 'like', '1001%')
                  ->orWhere('code', 'like', '1002%')
                  ->orWhere('code', 'like', '1003%');
            })
            ->orderBy('code')
            ->get();

        $allCustomers = Customer::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.deposits.customer_index', compact(
            'company',
            'customers',
            'totalDepositBalance',
            'cashAccounts',
            'allCustomers',
            'search'
        ));
    }

    public function customerTopUp(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'payment_method' => 'required|string|in:CASH,BANK_TRANSFER,QRIS',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $deposit = $this->depositService->topUpCustomerDeposit($company, $validated, Auth::user());

            return redirect()->route('accounting.deposits.customer.index')
                ->with('success', "Top Up Deposito pelanggan {$deposit->customer?->name} sebesar Rp ".number_format($deposit->amount, 0, ',', '.')." berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function customerWithdraw(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'payment_method' => 'required|string|in:CASH,BANK_TRANSFER',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $deposit = $this->depositService->withdrawCustomerDeposit($company, $validated, Auth::user());

            return redirect()->route('accounting.deposits.customer.index')
                ->with('success', "Penarikan Deposito pelanggan {$deposit->customer?->name} sebesar Rp ".number_format($deposit->amount, 0, ',', '.')." berhasil diproses.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function customerLedger(string $customerId): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $customer = Customer::where('company_id', $company->id)->findOrFail($customerId);

        $ledger = $this->depositService->getCustomerDepositLedger($company, $customer->id);

        return view('accounting.deposits.customer_ledger', compact(
            'company',
            'customer',
            'ledger'
        ));
    }

    /**
     * ========================================================
     * 2. DEPOSITO SUPPLIER (FITUR NO. 6 & 7 IPOS 5)
     * ========================================================
     */

    public function supplierIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $search = $request->input('search');

        $suppliers = $this->depositService->getSupplierDepositsSummary($company, $search);
        $totalDepositBalance = Supplier::where('company_id', $company->id)->sum('deposit_balance');

        $cashAccounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('type', 'ASSET')
            ->where(function ($q) {
                $q->where('code', 'like', '1001%')
                  ->orWhere('code', 'like', '1002%')
                  ->orWhere('code', 'like', '1003%');
            })
            ->orderBy('code')
            ->get();

        $allSuppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.deposits.supplier_index', compact(
            'company',
            'suppliers',
            'totalDepositBalance',
            'cashAccounts',
            'allSuppliers',
            'search'
        ));
    }

    public function supplierTopUp(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'payment_method' => 'required|string|in:CASH,BANK_TRANSFER',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $deposit = $this->depositService->topUpSupplierDeposit($company, $validated, Auth::user());

            return redirect()->route('accounting.deposits.supplier.index')
                ->with('success', "Uang Muka ke supplier {$deposit->supplier?->name} sebesar Rp ".number_format($deposit->amount, 0, ',', '.')." berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function supplierRefund(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'payment_method' => 'required|string|in:CASH,BANK_TRANSFER',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $deposit = $this->depositService->refundSupplierDeposit($company, $validated, Auth::user());

            return redirect()->route('accounting.deposits.supplier.index')
                ->with('success', "Refund uang muka dari supplier {$deposit->supplier?->name} sebesar Rp ".number_format($deposit->amount, 0, ',', '.')." berhasil diproses.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function supplierLedger(string $supplierId): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $supplier = Supplier::where('company_id', $company->id)->findOrFail($supplierId);

        $ledger = $this->depositService->getSupplierDepositLedger($company, $supplier->id);

        return view('accounting.deposits.supplier_ledger', compact(
            'company',
            'supplier',
            'ledger'
        ));
    }
}
