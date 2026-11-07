<?php

namespace App\Services;

use App\Models\AccountingSetting;
use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FiscalYearClosing;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FiscalYearClosingService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Dapatkan Ringkasan Saldo Pendapatan & Beban untuk Ditutup pada Tahun Buku Tertentu.
     */
    public function getClosingSummary(Company $company, int $fiscalYear): array
    {
        $startDate = "{$fiscalYear}-01-01";
        $endDate = "{$fiscalYear}-12-31";

        $existingClosing = FiscalYearClosing::where('company_id', $company->id)
            ->where('fiscal_year', $fiscalYear)
            ->whereRaw("UPPER(COALESCE(status, 'CLOSED')) = 'CLOSED'")
            ->with(['closer', 'closingJournalEntry', 'retainedEarningsAccount'])
            ->first();

        // Cari akun Laba Ditahan (Retained Earnings)
        $settingAccId = AccountingSetting::where('company_id', $company->id)
            ->where('setting_key', 'retained_earnings_account_id')
            ->value('account_id');

        $retainedEarningsAccount = $settingAccId
            ? ChartOfAccount::where('company_id', $company->id)->find($settingAccId)
            : (ChartOfAccount::where('company_id', $company->id)->where('code', '3002-001')->first()
                ?? ChartOfAccount::where('company_id', $company->id)->where('type', 'EQUITY')->where(DB::raw('LOWER(name)'), 'like', '%laba ditahan%')->first());

        if (!$retainedEarningsAccount) {
            $retainedEarningsAccount = ChartOfAccount::firstOrCreate(
                ['company_id' => $company->id, 'code' => '3002-001'],
                ['name' => 'Laba Ditahan (Retained Earnings)', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT', 'is_active' => true]
            );
        }

        // Ambil mutasi baris jurnal (kecuali jurnal penutup tahunan itu sendiri jika sudah pernah ada)
        $linesQuery = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jel.account_id', '=', 'coa.id')
            ->where('je.company_id', $company->id)
            ->whereDate('je.entry_date', '>=', $startDate)
            ->whereDate('je.entry_date', '<=', $endDate)
            ->where('je.reference_type', '!=', 'YEAR_END_CLOSING')
            ->whereIn('coa.type', ['REVENUE', 'EXPENSE'])
            ->select(
                'coa.id as account_id',
                'coa.code',
                'coa.name',
                'coa.type',
                DB::raw('SUM(jel.debit) as total_debit'),
                DB::raw('SUM(jel.credit) as total_credit')
            )
            ->groupBy('coa.id', 'coa.code', 'coa.name', 'coa.type')
            ->orderBy('coa.code')
            ->get();

        $revenueAccounts = [];
        $expenseAccounts = [];
        $totalRevenue = 0.0;
        $totalExpense = 0.0;

        foreach ($linesQuery as $item) {
            $debit = (float) $item->total_debit;
            $credit = (float) $item->total_credit;

            if ($item->type === 'REVENUE') {
                // Pendapatan normalnya di kredit
                $balance = $credit - $debit;
                if (abs($balance) > 0.001) {
                    $revenueAccounts[] = [
                        'account_id' => $item->account_id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'balance' => $balance,
                    ];
                    $totalRevenue += $balance;
                }
            } elseif ($item->type === 'EXPENSE') {
                // Beban normalnya di debit
                $balance = $debit - $credit;
                if (abs($balance) > 0.001) {
                    $expenseAccounts[] = [
                        'account_id' => $item->account_id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'balance' => $balance,
                    ];
                    $totalExpense += $balance;
                }
            }
        }

        $netProfit = $totalRevenue - $totalExpense;

        return [
            'fiscal_year' => $fiscalYear,
            'is_already_closed' => (bool) $existingClosing,
            'closing_record' => $existingClosing,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit' => $netProfit,
            'revenue_accounts' => $revenueAccounts,
            'expense_accounts' => $expenseAccounts,
            'retained_earnings_account' => $retainedEarningsAccount,
        ];
    }

    /**
     * Eksekusi Tutup Buku Tahunan & Terbitkan Jurnal Penutup Seimbang (Double-Entry).
     */
    public function closeFiscalYear(Company $company, int $fiscalYear, User $user, ?string $notes = null): FiscalYearClosing
    {
        return DB::transaction(function () use ($company, $fiscalYear, $user, $notes) {
            $existing = FiscalYearClosing::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->lockForUpdate()
                ->first();

            if ($existing && strtoupper((string) ($existing->status ?? 'CLOSED')) === 'CLOSED') {
                throw new \InvalidArgumentException("Tahun buku {$fiscalYear} sudah pernah ditutup sebelumnya.");
            }

            if ($existing) {
                $existing->delete();
            }

            $summary = $this->getClosingSummary($company, $fiscalYear);
            $retainedEarningsAccount = $summary['retained_earnings_account'];

            $journalLines = [];

            // 1. Tutup Akun Pendapatan (Debit Pendapatan agar bersaldo 0)
            foreach ($summary['revenue_accounts'] as $rev) {
                $account = ChartOfAccount::where('company_id', $company->id)->findOrFail($rev['account_id']);
                $bal = (float) $rev['balance'];
                if ($bal > 0) {
                    $journalLines[] = [
                        'account' => $account,
                        'debit' => $bal,
                        'credit' => 0.00,
                        'memo' => "Tutup Buku {$fiscalYear}: [{$account->code}] {$account->name}",
                    ];
                } elseif ($bal < 0) {
                    $journalLines[] = [
                        'account' => $account,
                        'debit' => 0.00,
                        'credit' => abs($bal),
                        'memo' => "Tutup Buku {$fiscalYear}: [{$account->code}] {$account->name}",
                    ];
                }
            }

            // 2. Tutup Akun Beban (Kredit Beban agar bersaldo 0)
            foreach ($summary['expense_accounts'] as $exp) {
                $account = ChartOfAccount::where('company_id', $company->id)->findOrFail($exp['account_id']);
                $bal = (float) $exp['balance'];
                if ($bal > 0) {
                    $journalLines[] = [
                        'account' => $account,
                        'debit' => 0.00,
                        'credit' => $bal,
                        'memo' => "Tutup Buku {$fiscalYear}: [{$account->code}] {$account->name}",
                    ];
                } elseif ($bal < 0) {
                    $journalLines[] = [
                        'account' => $account,
                        'debit' => abs($bal),
                        'credit' => 0.00,
                        'memo' => "Tutup Buku {$fiscalYear}: [{$account->code}] {$account->name}",
                    ];
                }
            }

            // 3. Pindahkan Laba Bersih / Rugi Bersih ke Laba Ditahan
            $netProfit = (float) $summary['net_profit'];
            if ($netProfit > 0) {
                // Laba bersih diposting di Kredit Laba Ditahan
                $journalLines[] = [
                    'account' => $retainedEarningsAccount,
                    'debit' => 0.00,
                    'credit' => $netProfit,
                    'memo' => "Tutup Buku {$fiscalYear}: Pemindahan Laba Bersih ke Laba Ditahan",
                ];
            } elseif ($netProfit < 0) {
                // Rugi bersih diposting di Debit Laba Ditahan
                $journalLines[] = [
                    'account' => $retainedEarningsAccount,
                    'debit' => abs($netProfit),
                    'credit' => 0.00,
                    'memo' => "Tutup Buku {$fiscalYear}: Pemindahan Rugi Bersih ke Laba Ditahan",
                ];
            }

            $journal = null;
            if (!empty($journalLines)) {
                $journal = $this->accountingService->createJournalEntry(
                    company: $company,
                    referenceType: 'YEAR_END_CLOSING',
                    referenceNumber: 'CLS-'.$fiscalYear,
                    description: "Jurnal Penutup Akhir Tahun Buku {$fiscalYear} ke Laba Ditahan",
                    lines: $journalLines,
                    store: null,
                    userId: $user->id,
                    entryDate: "{$fiscalYear}-12-31"
                );
            }

            $closing = FiscalYearClosing::create([
                'company_id' => $company->id,
                'fiscal_year' => $fiscalYear,
                'closing_date' => "{$fiscalYear}-12-31",
                'total_revenue' => $summary['total_revenue'],
                'total_expense' => $summary['total_expense'],
                'net_profit' => $summary['net_profit'],
                'retained_earnings_account_id' => $retainedEarningsAccount->id,
                'closing_journal_entry_id' => $journal?->id,
                'closed_by' => $user->id,
                'status' => 'CLOSED',
                'notes' => $notes ?: "Tutup buku tahunan {$fiscalYear} dilakukan oleh {$user->name}",
            ]);

            AuditLog::record(
                module: 'ACCOUNTING',
                action: 'CLOSE_FISCAL_YEAR',
                description: "Tutup buku tahunan {$fiscalYear} dilakukan oleh {$user->name}.",
                oldValues: null,
                newValues: [
                    'fiscal_year' => $fiscalYear,
                    'status' => 'CLOSED',
                    'net_profit' => (float) $closing->net_profit,
                ],
                user: $user,
                companyId: $company->id
            );

            return $closing->load(['closingJournalEntry', 'retainedEarningsAccount', 'closer']);
        });
    }

    /**
     * Buka Kembali Tahun Buku yang Ditutup (Hanya untuk Owner/Manager jika terdapat koreksi audit).
     */
    public function reopenFiscalYear(Company $company, int $fiscalYear, User $user): void
    {
        if (! in_array($user->role, ['owner', 'manager'], true)) {
            throw new \InvalidArgumentException('Hanya Owner atau Manajer yang berwenang membuka kembali tahun buku yang telah ditutup.');
        }

        DB::transaction(function () use ($company, $fiscalYear, $user) {
            $closing = FiscalYearClosing::where('company_id', $company->id)
                ->where('fiscal_year', $fiscalYear)
                ->lockForUpdate()
                ->firstOrFail();

            if ($closing->closing_journal_entry_id) {
                $journal = JournalEntry::find($closing->closing_journal_entry_id);
                if ($journal) {
                    $journal->lines()->delete();
                    $journal->delete();
                }
            }

            $closing->delete();

            AuditLog::record(
                module: 'ACCOUNTING',
                action: 'REOPEN_FISCAL_YEAR',
                description: "Pembukaan kembali (reopen) tahun buku {$fiscalYear} oleh {$user->name}.",
                oldValues: [
                    'fiscal_year' => $fiscalYear,
                    'status' => 'CLOSED',
                ],
                newValues: [
                    'fiscal_year' => $fiscalYear,
                    'status' => 'REOPENED',
                ],
                user: $user,
                companyId: $company->id
            );
        });
    }
}

