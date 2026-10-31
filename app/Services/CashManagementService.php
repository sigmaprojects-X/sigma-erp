<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashManagementService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Dapatkan Seluruh Akun Kas & Bank Beserta Saldo Berjalannya.
     */
    public function getCashAndBankAccounts(Company $company): Collection
    {
        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('type', 'ASSET')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('code', 'like', '1001%') // Kas
                    ->orWhere('code', 'like', '1002%') // Bank
                    ->orWhere('code', 'like', '1003%') // QRIS / E-Wallet
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%kas%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%bank%']);
            })
            ->orderBy('code')
            ->get();

        $balances = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->where('journal_entries.company_id', $company->id)
            ->whereIn('journal_entry_lines.account_id', $accounts->pluck('id'))
            ->select('journal_entry_lines.account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->groupBy('journal_entry_lines.account_id')
            ->get()
            ->keyBy('account_id');

        return $accounts->map(function ($acc) use ($balances) {
            $bal = $balances->get($acc->id);
            $totalDebit = (float) ($bal?->total_debit ?? 0.0);
            $totalCredit = (float) ($bal?->total_credit ?? 0.0);
            $acc->current_balance = $totalDebit - $totalCredit;
            return $acc;
        });
    }

    /**
     * 1. Catat Kas Masuk (Cash In Non-Penjualan).
     *
     * Jurnal Otomatis:
     * Debit: Kas/Bank Penerima
     * Kredit: Kategori Pendapatan / Akun Lawan
     */
    public function recordCashIn(Company $company, ?Store $store, array $data, ?User $user): CashTransaction
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal Kas Masuk harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $store, $data, $user, $amount) {
            $date = $data['transaction_date'] ?? now()->toDateString();
            $dateStr = date('Ymd', strtotime($date));
            $txNumber = 'KM-'.$dateStr.'-'.strtoupper(Str::random(4));

            $destAccount = ChartOfAccount::where('company_id', $company->id)->findOrFail($data['destination_account_id']);
            $categoryAccount = ChartOfAccount::where('company_id', $company->id)->findOrFail($data['category_account_id']);

            $tx = CashTransaction::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'transaction_number' => $txNumber,
                'type' => 'CASH_IN',
                'transaction_date' => $date,
                'destination_account_id' => $destAccount->id,
                'category_account_id' => $categoryAccount->id,
                'amount' => $amount,
                'contact_person' => $data['contact_person'] ?? null,
                'notes' => $data['notes'] ?? 'Penerimaan Kas Masuk',
                'created_by' => $user?->id,
            ]);

            // Jurnal Otomatis Double-Entry
            $memo = "Kas Masuk: {$tx->transaction_number} - ".($tx->notes ?: $categoryAccount->name);
            $journalLines = [
                [
                    'account' => $destAccount,
                    'debit' => $amount,
                    'credit' => 0.00,
                    'memo' => "Penerimaan uang ke [{$destAccount->name}]",
                ],
                [
                    'account' => $categoryAccount,
                    'debit' => 0.00,
                    'credit' => $amount,
                    'memo' => "Kategori pendapatan [{$categoryAccount->name}]",
                ],
            ];

            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CASH_IN',
                referenceNumber: $tx->transaction_number,
                description: $memo,
                lines: $journalLines,
                store: $store,
                userId: $user?->id,
                entryDate: $date
            );

            $tx->update(['journal_entry_id' => $journal->id]);

            return $tx->load(['destinationAccount', 'categoryAccount', 'store']);
        });
    }

    /**
     * 2. Catat Kas Keluar (Cash Out / Biaya Operasional Toko).
     *
     * Jurnal Otomatis:
     * Debit: Kategori Beban Operasional
     * Kredit: Kas/Bank Sumber Pembayaran
     */
    public function recordCashOut(Company $company, ?Store $store, array $data, ?User $user): CashTransaction
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal Kas Keluar harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $store, $data, $user, $amount) {
            $date = $data['transaction_date'] ?? now()->toDateString();
            $dateStr = date('Ymd', strtotime($date));
            $txNumber = 'KK-'.$dateStr.'-'.strtoupper(Str::random(4));

            $sourceAccount = ChartOfAccount::where('company_id', $company->id)->findOrFail($data['source_account_id']);
            $categoryAccount = ChartOfAccount::where('company_id', $company->id)->findOrFail($data['category_account_id']);

            $tx = CashTransaction::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'transaction_number' => $txNumber,
                'type' => 'CASH_OUT',
                'transaction_date' => $date,
                'source_account_id' => $sourceAccount->id,
                'category_account_id' => $categoryAccount->id,
                'amount' => $amount,
                'contact_person' => $data['contact_person'] ?? null,
                'notes' => $data['notes'] ?? 'Pengeluaran Kas Toko',
                'created_by' => $user?->id,
            ]);

            // Jurnal Otomatis Double-Entry
            $memo = "Kas Keluar: {$tx->transaction_number} - ".($tx->notes ?: $categoryAccount->name);
            $journalLines = [
                [
                    'account' => $categoryAccount,
                    'debit' => $amount,
                    'credit' => 0.00,
                    'memo' => "Beban operasional [{$categoryAccount->name}]",
                ],
                [
                    'account' => $sourceAccount,
                    'debit' => 0.00,
                    'credit' => $amount,
                    'memo' => "Pengeluaran uang dari [{$sourceAccount->name}]",
                ],
            ];

            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CASH_OUT',
                referenceNumber: $tx->transaction_number,
                description: $memo,
                lines: $journalLines,
                store: $store,
                userId: $user?->id,
                entryDate: $date
            );

            $tx->update(['journal_entry_id' => $journal->id]);

            return $tx->load(['sourceAccount', 'categoryAccount', 'store']);
        });
    }

    /**
     * 3. Catat Transfer Kas / Mutasi Antar Rekening Kas & Bank.
     *
     * Jurnal Otomatis:
     * Debit: Kas/Bank Tujuan (amount)
     * Debit: Beban Administrasi Bank (admin_fee, jika ada)
     * Kredit: Kas/Bank Sumber (amount + admin_fee)
     */
    public function recordCashTransfer(Company $company, ?Store $store, array $data, ?User $user): CashTransaction
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $adminFee = round((float) ($data['admin_fee'] ?? 0), 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal Transfer Kas harus lebih besar dari 0.');
        }

        if ($data['source_account_id'] === $data['destination_account_id']) {
            throw new \InvalidArgumentException('Rekening Sumber dan Rekening Tujuan tidak boleh sama.');
        }

        return DB::transaction(function () use ($company, $store, $data, $user, $amount, $adminFee) {
            $date = $data['transaction_date'] ?? now()->toDateString();
            $dateStr = date('Ymd', strtotime($date));
            $txNumber = 'KT-'.$dateStr.'-'.strtoupper(Str::random(4));

            $sourceAccount = ChartOfAccount::where('company_id', $company->id)->findOrFail($data['source_account_id']);
            $destAccount = ChartOfAccount::where('company_id', $company->id)->findOrFail($data['destination_account_id']);

            $adminFeeAccount = null;
            if ($adminFee > 0) {
                $adminFeeAccountId = $data['admin_fee_account_id'] ?? null;
                if ($adminFeeAccountId) {
                    $adminFeeAccount = ChartOfAccount::where('company_id', $company->id)->find($adminFeeAccountId);
                }
                if (! $adminFeeAccount) {
                    // Gunakan akun beban operasional / beban umum 6001 / create default
                    $adminFeeAccount = ChartOfAccount::where('company_id', $company->id)
                        ->where('type', 'EXPENSE')
                        ->orderBy('code')
                        ->first();
                }
            }

            $tx = CashTransaction::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'transaction_number' => $txNumber,
                'type' => 'CASH_TRANSFER',
                'transaction_date' => $date,
                'source_account_id' => $sourceAccount->id,
                'destination_account_id' => $destAccount->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'admin_fee_account_id' => $adminFeeAccount?->id,
                'contact_person' => $data['contact_person'] ?? null,
                'notes' => $data['notes'] ?? "Transfer {$sourceAccount->name} -> {$destAccount->name}",
                'created_by' => $user?->id,
            ]);

            // Jurnal Otomatis Double-Entry
            $memo = "Mutasi Kas: {$tx->transaction_number} dari {$sourceAccount->name} ke {$destAccount->name}";
            $totalDeduction = $amount + $adminFee;

            $journalLines = [
                [
                    'account' => $destAccount,
                    'debit' => $amount,
                    'credit' => 0.00,
                    'memo' => "Terima transfer dari [{$sourceAccount->name}]",
                ],
            ];

            if ($adminFee > 0 && $adminFeeAccount) {
                $journalLines[] = [
                    'account' => $adminFeeAccount,
                    'debit' => $adminFee,
                    'credit' => 0.00,
                    'memo' => "Biaya admin transfer bank",
                ];
            }

            $journalLines[] = [
                'account' => $sourceAccount,
                'debit' => 0.00,
                'credit' => $totalDeduction,
                'memo' => "Transfer keluar ke [{$destAccount->name}]".($adminFee > 0 ? " (+ Biaya Admin)" : ""),
            ];

            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CASH_TRANSFER',
                referenceNumber: $tx->transaction_number,
                description: $memo,
                lines: $journalLines,
                store: $store,
                userId: $user?->id,
                entryDate: $date
            );

            $tx->update(['journal_entry_id' => $journal->id]);

            return $tx->load(['sourceAccount', 'destinationAccount', 'store']);
        });
    }
}
