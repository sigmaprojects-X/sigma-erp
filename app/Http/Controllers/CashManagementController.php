<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Store;
use App\Services\CashManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CashManagementController extends Controller
{
    public function __construct(
        protected CashManagementService $cashService
    ) {}

    /**
     * Dashboard Kas & Bank + Daftar Riwayat Mutasi Kas.
     */
    public function index(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $type = $request->input('type');
        $storeId = $request->input('store_id');
        $search = trim((string) $request->input('search'));

        // Saldo Kas & Rekening Bank Real-time
        $cashAccounts = $this->cashService->getCashAndBankAccounts($company);
        $totalCash = $cashAccounts->sum('current_balance');

        $query = CashTransaction::where('company_id', $company->id)
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->with(['sourceAccount', 'destinationAccount', 'categoryAccount', 'store', 'creator']);

        if ($type && in_array($type, ['CASH_IN', 'CASH_OUT', 'CASH_TRANSFER'])) {
            $query->where('type', $type);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhereRaw('LOWER(contact_person) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(notes) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        $transactions = $query->orderByDesc('transaction_date')->orderByDesc('created_at')->paginate(20);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        return view('accounting.cash.index', compact(
            'company',
            'cashAccounts',
            'totalCash',
            'transactions',
            'stores',
            'startDate',
            'endDate',
            'type',
            'storeId',
            'search'
        ));
    }

    /**
     * Form Input Kas Masuk (Penerimaan Uang Non-Penjualan).
     */
    public function createIn(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $cashAccounts = $this->cashService->getCashAndBankAccounts($company);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        // Kategori Pendapatan & Akun Lawan
        $categoryAccounts = ChartOfAccount::where('company_id', $company->id)
            ->whereIn('type', ['REVENUE', 'LIABILITY', 'EQUITY'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.cash.in', compact('company', 'cashAccounts', 'categoryAccounts', 'stores'));
    }

    /**
     * Simpan Kas Masuk.
     */
    public function storeIn(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'destination_account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'category_account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'contact_person' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        $store = ! empty($validated['store_id']) ? Store::find($validated['store_id']) : null;

        try {
            $tx = $this->cashService->recordCashIn($company, $store, $validated, Auth::user());
            return redirect()->route('accounting.cash.show', $tx->id)
                ->with('success', "Kas Masuk {$tx->transaction_number} sebesar Rp ".number_format($tx->amount, 0, ',', '.')." berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Form Input Kas Keluar (Pengeluaran Biaya Toko).
     */
    public function createOut(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $cashAccounts = $this->cashService->getCashAndBankAccounts($company);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        // Kategori Beban Operasional
        $categoryAccounts = ChartOfAccount::where('company_id', $company->id)
            ->where('type', 'EXPENSE')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.cash.out', compact('company', 'cashAccounts', 'categoryAccounts', 'stores'));
    }

    /**
     * Simpan Kas Keluar.
     */
    public function storeOut(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'source_account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'category_account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'contact_person' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        $store = ! empty($validated['store_id']) ? Store::find($validated['store_id']) : null;

        try {
            $tx = $this->cashService->recordCashOut($company, $store, $validated, Auth::user());
            return redirect()->route('accounting.cash.show', $tx->id)
                ->with('success', "Kas Keluar {$tx->transaction_number} sebesar Rp ".number_format($tx->amount, 0, ',', '.')." berhasil dibukukan.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Form Transfer Kas / Setor Bank.
     */
    public function createTransfer(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $cashAccounts = $this->cashService->getCashAndBankAccounts($company);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $adminFeeAccounts = ChartOfAccount::where('company_id', $company->id)
            ->where('type', 'EXPENSE')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.cash.transfer', compact('company', 'cashAccounts', 'adminFeeAccounts', 'stores'));
    }

    /**
     * Simpan Transfer Kas / Setor Bank.
     */
    public function storeTransfer(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'source_account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'destination_account_id' => 'required|uuid|exists:chart_of_accounts,id|different:source_account_id',
            'amount' => 'required|numeric|min:0.01',
            'admin_fee' => 'nullable|numeric|min:0',
            'admin_fee_account_id' => 'nullable|uuid|exists:chart_of_accounts,id',
            'contact_person' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        $store = ! empty($validated['store_id']) ? Store::find($validated['store_id']) : null;

        try {
            $tx = $this->cashService->recordCashTransfer($company, $store, $validated, Auth::user());
            return redirect()->route('accounting.cash.show', $tx->id)
                ->with('success', "Transfer Kas {$tx->transaction_number} sebesar Rp ".number_format($tx->amount, 0, ',', '.')." berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Detail Voucher Bukti Kas (Siap Cetak).
     */
    public function show(string $id): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $transaction = CashTransaction::where('company_id', $company->id)
            ->with(['sourceAccount', 'destinationAccount', 'categoryAccount', 'store', 'creator', 'journalEntry.lines.account'])
            ->findOrFail($id);

        return view('accounting.cash.show', compact('transaction', 'company'));
    }
}
