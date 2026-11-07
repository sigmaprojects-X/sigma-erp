<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PosTransaction;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OpeningBalanceService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * ========================================================
     * 1. SALDO AWAL PERKIRAAN (COA OPENING BALANCE)
     * ========================================================
     */

    /**
     * Dapatkan Seluruh Akun COA Beserta Nilai Saldo Awal yang Tersimpan.
     */
    public function getCoaOpeningBalances(Company $company): Collection
    {
        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $existingJournal = JournalEntry::where('company_id', $company->id)
            ->where('reference_type', 'OPENING_BALANCE')
            ->where('reference_number', 'SALDO-AWAL-COA')
            ->with('lines')
            ->first();

        $openingLines = $existingJournal ? $existingJournal->lines->keyBy('account_id') : collect();

        return $accounts->map(function ($acc) use ($openingLines) {
            $line = $openingLines->get($acc->id);
            $acc->opening_debit = (float) ($line?->debit ?? 0.0);
            $acc->opening_credit = (float) ($line?->credit ?? 0.0);
            return $acc;
        });
    }

    /**
     * Simpan / Perbarui Saldo Awal Perkiraan (Double-Entry Seimbang).
     */
    public function saveCoaOpeningBalance(Company $company, string $cutoffDate, array $balances, ?User $user): JournalEntry
    {
        return DB::transaction(function () use ($company, $cutoffDate, $balances, $user) {
            // Hapus entri saldo awal COA sebelumnya jika ada
            $existing = JournalEntry::where('company_id', $company->id)
                ->where('reference_type', 'OPENING_BALANCE')
                ->where('reference_number', 'SALDO-AWAL-COA')
                ->first();

            if ($existing) {
                $existing->lines()->delete();
                $existing->delete();
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;
            $processedLines = [];

            foreach ($balances as $accountId => $val) {
                $debit = round((float) ($val['debit'] ?? 0), 2);
                $credit = round((float) ($val['credit'] ?? 0), 2);

                if ($debit == 0 && $credit == 0) {
                    continue;
                }

                $account = ChartOfAccount::where('company_id', $company->id)->findOrFail($accountId);
                $totalDebit += $debit;
                $totalCredit += $credit;

                $processedLines[] = [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => "Saldo Awal Migrasi [{$account->name}]",
                ];
            }

            if (empty($processedLines)) {
                throw new \InvalidArgumentException('Minimal harus ada satu akun perkiraan dengan saldo awal.');
            }

            $difference = round(abs($totalDebit - $totalCredit), 2);
            if ($difference > 0.01) {
                throw new \InvalidArgumentException("Total Debit (Rp ".number_format($totalDebit, 0, ',', '.').") belum seimbang dengan Total Kredit (Rp ".number_format($totalCredit, 0, ',', '.')."). Selisih: Rp ".number_format($difference, 0, ',', '.'));
            }

            return $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'OPENING_BALANCE',
                referenceNumber: 'SALDO-AWAL-COA',
                description: 'Saldo Awal Pembukuan Perkiraan Migrasi (Cut-off: '.$cutoffDate.')',
                lines: $processedLines,
                store: null,
                userId: $user?->id,
                entryDate: $cutoffDate
            );
        });
    }

    /**
     * ========================================================
     * 2. SALDO AWAL PIUTANG PELANGGAN (OPENING AR)
     * ========================================================
     */

    /**
     * Dapatkan Daftar Saldo Awal Piutang yang Sudah Dicatat.
     */
    public function getCustomerOpeningReceivables(Company $company, ?Store $store = null): Collection
    {
        $query = PosTransaction::where('company_id', $company->id)
            ->where('payment_method', 'TEMPO')
            ->where('invoice_number', 'like', 'SA-AR-%')
            ->with(['customer', 'store']);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        return $query->orderByDesc('transaction_date')->get();
    }

    /**
     * Catat Saldo Awal Piutang Pelanggan Lama.
     */
    public function recordCustomerOpeningReceivable(Company $company, array $data, ?User $user): PosTransaction
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal Saldo Awal Piutang harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $data, $user, $amount) {
            $customer = Customer::where('company_id', $company->id)->lockForUpdate()->findOrFail($data['customer_id']);
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : (Store::where('company_id', $company->id)->find($customer->store_id)
                    ?? Store::where('company_id', $company->id)->firstOrFail());
            $tier = $customer->tier ?? CustomerTier::where('company_id', $company->id)->firstOrFail();

            $txDate = $data['transaction_date'] ?? now()->toDateString();
            $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime($txDate.' +30 days'));

            // Pastikan format nomor invoice saldo awal: SA-AR-YYYYMMDD-XXXX atau nomor faktur manual lama
            $rawRef = trim($data['invoice_number'] ?? '');
            $invoiceNumber = $rawRef ? (str_starts_with($rawRef, 'SA-AR-') ? $rawRef : 'SA-AR-'.$rawRef) : 'SA-AR-'.date('Ymd').'-'.strtoupper(Str::random(4));

            if (PosTransaction::where('company_id', $company->id)->where('invoice_number', $invoiceNumber)->exists()) {
                throw new \InvalidArgumentException("Nomor Faktur [{$invoiceNumber}] sudah terdaftar di sistem.");
            }

            // Dapatkan atau buat shift sistem untuk menampung saldo awal
            $shift = CashierShift::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'shift_number' => 'SHF-SYSTEM-MIGRATION',
                ],
                [
                    'cashier_id' => $user?->id ?? User::firstOrFail()->id,
                    'starting_cash' => 0,
                    'status' => 'OPEN',
                    'opened_at' => now(),
                ]
            );

            $transaction = PosTransaction::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'cashier_id' => $user?->id ?? User::firstOrFail()->id,
                'cashier_shift_id' => $shift->id,
                'customer_id' => $customer->id,
                'customer_tier_id' => $tier->id,
                'invoice_number' => $invoiceNumber,
                'transaction_date' => $txDate,
                'due_date' => $dueDate,
                'subtotal' => $amount,
                'tax_amount' => 0.00,
                'discount_amount' => 0.00,
                'grand_total' => $amount,
                'paid_amount' => 0.00,
                'credit_amount' => $amount,
                'remaining_receivable' => $amount,
                'payment_method' => 'TEMPO',
                'payment_status' => 'UNPAID',
                'status' => 'COMPLETED',
                'notes' => $data['notes'] ?? 'Saldo Awal Piutang Migrasi Nota: '.$rawRef,
            ]);

            // Update saldo piutang berjalan pelanggan
            $customer->increment('current_credit_balance', $amount);

            // Otomasi Double-Entry Jurnal Saldo Awal Piutang:
            // DEBIT: Piutang Usaha (1004-001)
            // KREDIT: Modal Pemilik / Ekuitas Saldo Awal (3001-001)
            $arAccount = ChartOfAccount::where('company_id', $company->id)->where('code', '1004-001')->first()
                ?? ChartOfAccount::where('company_id', $company->id)->where('type', 'ASSET')->where(DB::raw('LOWER(name)'), 'like', '%piutang%')->first();
            $equityAccount = ChartOfAccount::where('company_id', $company->id)->where('code', '3001-001')->first()
                ?? ChartOfAccount::where('company_id', $company->id)->where('type', 'EQUITY')->first();

            if ($arAccount && $equityAccount) {
                $this->accountingService->createJournalEntry(
                    company: $company,
                    referenceType: 'OPENING_AR',
                    referenceNumber: $invoiceNumber,
                    description: "Saldo Awal Piutang Pelanggan [{$customer->name}] - Nota: {$invoiceNumber}",
                    lines: [
                        [
                            'account' => $arAccount,
                            'debit' => $amount,
                            'credit' => 0.00,
                            'memo' => "Saldo Awal Piutang {$customer->name}",
                        ],
                        [
                            'account' => $equityAccount,
                            'debit' => 0.00,
                            'credit' => $amount,
                            'memo' => "Penyesuaian Modal Saldo Awal Piutang {$customer->name}",
                        ],
                    ],
                    store: $store,
                    userId: $user?->id,
                    entryDate: $txDate
                );
            }

            return $transaction->load(['customer', 'store']);
        });
    }

    /**
     * Hapus Saldo Awal Piutang (Jika salah input dan belum pernah dibayar).
     */
    public function deleteCustomerOpeningReceivable(Company $company, string $id): void
    {
        DB::transaction(function () use ($company, $id) {
            $transaction = PosTransaction::where('company_id', $company->id)
                ->where('invoice_number', 'like', 'SA-AR-%')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($transaction->paid_amount > 0) {
                throw new \InvalidArgumentException('Saldo awal piutang ini sudah pernah memiliki riwayat pembayaran dan tidak boleh dihapus.');
            }

            $customer = Customer::where('id', $transaction->customer_id)->lockForUpdate()->first();
            if ($customer) {
                $customer->decrement('current_credit_balance', $transaction->remaining_receivable);
            }

            // Hapus Jurnal Saldo Awal Piutang Terkait
            $journal = JournalEntry::where('company_id', $company->id)
                ->where('reference_type', 'OPENING_AR')
                ->where('reference_number', $transaction->invoice_number)
                ->first();

            if ($journal) {
                $journal->lines()->delete();
                $journal->delete();
            }

            $transaction->delete();
        });
    }

    /**
     * ========================================================
     * 3. SALDO AWAL HUTANG SUPPLIER (OPENING AP)
     * ========================================================
     */

    /**
     * Dapatkan Daftar Saldo Awal Hutang Supplier yang Sudah Dicatat.
     */
    public function getSupplierOpeningPayables(Company $company, ?Store $store = null): Collection
    {
        $query = Purchase::where('company_id', $company->id)
            ->where('invoice_number', 'like', 'SA-AP-%')
            ->with(['supplier', 'store']);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        return $query->orderByDesc('purchase_date')->get();
    }

    /**
     * Catat Saldo Awal Hutang Supplier Lama.
     */
    public function recordSupplierOpeningPayable(Company $company, array $data, ?User $user): Purchase
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal Saldo Awal Hutang harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $data, $user, $amount) {
            $supplier = Supplier::where('company_id', $company->id)->findOrFail($data['supplier_id']);
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : Store::where('company_id', $company->id)->firstOrFail();

            $date = $data['purchase_date'] ?? now()->toDateString();
            $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime($date.' +30 days'));

            $rawRef = trim($data['invoice_number'] ?? '');
            $invoiceNumber = $rawRef ? (str_starts_with($rawRef, 'SA-AP-') ? $rawRef : 'SA-AP-'.$rawRef) : 'SA-AP-'.date('Ymd').'-'.strtoupper(Str::random(4));

            if (Purchase::where('company_id', $company->id)->where('invoice_number', $invoiceNumber)->exists()) {
                throw new \InvalidArgumentException("Nomor Faktur Pembelian [{$invoiceNumber}] sudah terdaftar di sistem.");
            }

            $purchase = Purchase::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'supplier_id' => $supplier->id,
                'invoice_number' => $invoiceNumber,
                'reference_number' => 'REF-'.$invoiceNumber,
                'purchase_date' => $date,
                'due_date' => $dueDate,
                'payment_type' => 'CREDIT',
                'status' => 'APPROVED',
                'subtotal' => $amount,
                'tax_amount' => 0.00,
                'discount_amount' => 0.00,
                'grand_total' => $amount,
                'paid_amount' => 0.00,
                'notes' => $data['notes'] ?? 'Saldo Awal Hutang Supplier Faktur: '.$rawRef,
                'created_by' => $user?->id,
            ]);

            // Otomasi Double-Entry Jurnal Saldo Awal Hutang:
            // DEBIT: Modal Pemilik / Ekuitas Saldo Awal (3001-001)
            // KREDIT: Hutang Usaha (2001-001)
            $apAccount = ChartOfAccount::where('company_id', $company->id)->where('code', '2001-001')->first()
                ?? ChartOfAccount::where('company_id', $company->id)->where('type', 'LIABILITY')->where(DB::raw('LOWER(name)'), 'like', '%hutang%')->first();
            $equityAccount = ChartOfAccount::where('company_id', $company->id)->where('code', '3001-001')->first()
                ?? ChartOfAccount::where('company_id', $company->id)->where('type', 'EQUITY')->first();

            if ($apAccount && $equityAccount) {
                $this->accountingService->createJournalEntry(
                    company: $company,
                    referenceType: 'OPENING_AP',
                    referenceNumber: $invoiceNumber,
                    description: "Saldo Awal Hutang Supplier [{$supplier->name}] - Faktur: {$invoiceNumber}",
                    lines: [
                        [
                            'account' => $equityAccount,
                            'debit' => $amount,
                            'credit' => 0.00,
                            'memo' => "Penyesuaian Modal Saldo Awal Hutang {$supplier->name}",
                        ],
                        [
                            'account' => $apAccount,
                            'debit' => 0.00,
                            'credit' => $amount,
                            'memo' => "Saldo Awal Hutang {$supplier->name}",
                        ],
                    ],
                    store: $store,
                    userId: $user?->id,
                    entryDate: $date
                );
            }

            return $purchase->load(['supplier', 'store']);
        });
    }

    /**
     * Hapus Saldo Awal Hutang Supplier.
     */
    public function deleteSupplierOpeningPayable(Company $company, string $id): void
    {
        DB::transaction(function () use ($company, $id) {
            $purchase = Purchase::where('company_id', $company->id)
                ->where('invoice_number', 'like', 'SA-AP-%')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($purchase->paid_amount > 0) {
                throw new \InvalidArgumentException('Saldo awal hutang ini sudah memiliki riwayat pembayaran dan tidak boleh dihapus.');
            }

            // Hapus Jurnal Saldo Awal Hutang Terkait
            $journal = JournalEntry::where('company_id', $company->id)
                ->where('reference_type', 'OPENING_AP')
                ->where('reference_number', $purchase->invoice_number)
                ->first();

            if ($journal) {
                $journal->lines()->delete();
                $journal->delete();
            }

            $purchase->delete();
        });
    }
}

