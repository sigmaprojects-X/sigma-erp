<?php

namespace App\Http\Controllers;

use App\Exceptions\UnbalancedJournalException;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * ========================================================
     * 1. BAGAN AKUN / CHART OF ACCOUNTS (COA)
     * ========================================================
     */

    /**
     * Tampilkan Daftar Bagan Akun (COA) dengan Saldo Berjalan.
     */
    public function coaIndex(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $typeFilter = $request->input('type');
        $search = trim((string) $request->input('search'));

        $query = ChartOfAccount::where('company_id', $company->id);

        if ($typeFilter && in_array($typeFilter, ['ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE'])) {
            $query->where('type', $typeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        $accounts = $query->orderBy('code')->get();

        // Hitung Saldo Berjalan (Current Balance) tiap akun dari baris jurnal
        $balances = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $company->id)
            ->select('journal_entry_lines.account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        $accountData = $accounts->map(function ($acc) use ($balances) {
            $bal = $balances->get($acc->id);
            $totalDebit = (float) ($bal?->total_debit ?? 0.0);
            $totalCredit = (float) ($bal?->total_credit ?? 0.0);

            $currentBalance = $acc->normal_balance === 'DEBIT'
                ? ($totalDebit - $totalCredit)
                : ($totalCredit - $totalDebit);

            return [
                'id' => $acc->id,
                'code' => $acc->code,
                'name' => $acc->name,
                'type' => $acc->type,
                'normal_balance' => $acc->normal_balance,
                'description' => $acc->description,
                'is_active' => $acc->is_active,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'current_balance' => $currentBalance,
            ];
        });

        // Summary totals per account type
        $typeSummaries = [
            'ASSET' => $accountData->where('type', 'ASSET')->sum('current_balance'),
            'LIABILITY' => $accountData->where('type', 'LIABILITY')->sum('current_balance'),
            'EQUITY' => $accountData->where('type', 'EQUITY')->sum('current_balance'),
            'REVENUE' => $accountData->where('type', 'REVENUE')->sum('current_balance'),
            'EXPENSE' => $accountData->where('type', 'EXPENSE')->sum('current_balance'),
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'accounts' => $accountData,
                'summaries' => $typeSummaries,
            ]);
        }

        return view('accounting.coa.index', [
            'company' => $company,
            'accounts' => $accountData,
            'typeSummaries' => $typeSummaries,
            'currentType' => $typeFilter,
            'search' => $search,
        ]);
    }

    /**
     * Tambah Akun Perkiraan Baru.
     */
    public function coaStore(Request $request): RedirectResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:chart_of_accounts,code,NULL,id,company_id,'.$company->id,
            'name' => 'required|string|max:150',
            'type' => 'required|in:ASSET,LIABILITY,EQUITY,REVENUE,EXPENSE',
            'normal_balance' => 'required|in:DEBIT,CREDIT',
            'description' => 'nullable|string|max:500',
        ]);

        ChartOfAccount::create([
            'company_id' => $company->id,
            'code' => trim($validated['code']),
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'normal_balance' => $validated['normal_balance'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('accounting.coa.index')
            ->with('success', "Akun perkiraan [{$validated['code']}] {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Ubah / Perbarui Akun Perkiraan.
     */
    public function coaUpdate(Request $request, string $id): RedirectResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $account = ChartOfAccount::where('company_id', $company->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $account->update([
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('accounting.coa.index')
            ->with('success', "Akun perkiraan [{$account->code}] {$account->name} berhasil diperbarui.");
    }

    /**
     * ========================================================
     * 2. JURNAL UMUM & PENCATATAN BIAYA MANUAL
     * ========================================================
     */

    /**
     * Daftar Seluruh Entri Jurnal Umum.
     */
    public function journalIndex(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $refType = $request->input('reference_type');
        $storeId = $request->input('store_id');
        $search = trim((string) $request->input('search'));

        $query = JournalEntry::where('company_id', $company->id)
            ->whereDate('entry_date', '>=', $startDate)
            ->whereDate('entry_date', '<=', $endDate)
            ->with(['store', 'lines.account']);

        if ($refType) {
            $query->where('reference_type', $refType);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        $journals = $query->orderByDesc('entry_date')->orderByDesc('created_at')->paginate(25);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $refTypes = [
            'MANUAL' => 'Jurnal Manual / Biaya',
            'POS_SALE' => 'Penjualan POS',
            'GOODS_RECEIPT' => 'Penerimaan Barang (GRN)',
            'GOODS_RECEIPT_MATCHING' => 'Faktur Pembelian (AP Match)',
            'SALES_RETURN' => 'Retur Penjualan',
            'PURCHASE_RETURN' => 'Retur Pembelian',
            'ASSET_DEPRECIATION' => 'Penyusutan Aset Tetap',
            'STOCK_OPNAME_ADJUSTMENT' => 'Penyesuaian Opname',
            'STOCK_MOVEMENT' => 'Item Masuk / Keluar',
            'CASH_DISCREPANCY' => 'Selisih Kas Shift',
            'CUSTOMER_PAYMENT' => 'Pembayaran Piutang AR',
            'SUPPLIER_PAYMENT' => 'Pembayaran Hutang AP',
            'VOUCHER_ISSUANCE' => 'Penerbitan Voucher',
        ];

        return view('accounting.journals.index', [
            'company' => $company,
            'journals' => $journals,
            'stores' => $stores,
            'refTypes' => $refTypes,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'selectedRefType' => $refType,
            'selectedStoreId' => $storeId,
            'search' => $search,
        ]);
    }

    /**
     * Form Input Jurnal Umum Manual / Biaya Operasional.
     */
    public function journalCreate(): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $todayDate = now()->toDateString();
        $suggestedRef = 'MNL-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(2)));

        return view('accounting.journals.create', [
            'company' => $company,
            'stores' => $stores,
            'accounts' => $accounts,
            'todayDate' => $todayDate,
            'suggestedRef' => $suggestedRef,
        ]);
    }

    /**
     * Simpan Jurnal Umum Manual (Double-Entry Seimbang).
     */
    public function journalStore(Request $request): RedirectResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'reference_number' => 'required|string|max:100',
            'description' => 'required|string|max:500',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|uuid|exists:chart_of_accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.memo' => 'nullable|string|max:255',
        ]);

        $store = ! empty($validated['store_id']) ? Store::find($validated['store_id']) : null;
        $userId = Auth::id();

        // Siapkan baris jurnal
        $processedLines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($validated['lines'] as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit == 0 && $credit == 0) {
                continue;
            }

            $account = ChartOfAccount::findOrFail($line['account_id']);
            $totalDebit += $debit;
            $totalCredit += $credit;

            $processedLines[] = [
                'account' => $account,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? $validated['description'],
            ];
        }

        if (empty($processedLines)) {
            return back()->withInput()->withErrors(['lines' => 'Minimal harus ada 2 baris akun dengan nilai debit dan kredit.']);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()->withErrors([
                'lines' => "Jurnal tidak seimbang! Total Debit (Rp ".number_format($totalDebit, 0, ',', '.').") != Total Kredit (Rp ".number_format($totalCredit, 0, ',', '.')."). Selisih: Rp ".number_format(abs($totalDebit - $totalCredit), 0, ',', '.'),
            ]);
        }

        try {
            $entry = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'MANUAL',
                referenceNumber: $validated['reference_number'],
                description: $validated['description'],
                lines: $processedLines,
                store: $store,
                userId: $userId,
                entryDate: $validated['entry_date']
            );

            return redirect()->route('accounting.journals.show', $entry->id)
                ->with('success', "Jurnal Umum {$entry->entry_number} berhasil dibukukan secara seimbang.");
        } catch (UnbalancedJournalException $e) {
            return back()->withInput()->withErrors(['lines' => 'Validasi gagal: Total Debit dan Total Kredit harus persis sama.']);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal membukukan jurnal: '.$e->getMessage()]);
        }
    }

    /**
     * Tampilkan Detail Entri Jurnal Umum (Voucher Jurnal Siap Cetak).
     */
    public function journalShow(string $id): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $journal = JournalEntry::where('company_id', $company->id)
            ->with(['store', 'lines.account'])
            ->findOrFail($id);

        return view('accounting.journals.show', compact('journal', 'company'));
    }
}
