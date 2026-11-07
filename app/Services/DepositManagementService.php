<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\JournalEntry;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierDeposit;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepositManagementService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Dapatkan Akun Hutang Deposito Pelanggan (2005-001).
     */
    public function getCustomerDepositLiabilityAccount(Company $company): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => '2005-001',
            ],
            [
                'name' => 'Hutang Titipan / Deposito Pelanggan',
                'type' => 'LIABILITY',
                'normal_balance' => 'CREDIT',
                'is_active' => true,
            ]
        );
    }

    /**
     * Dapatkan Akun Uang Muka / Deposito Supplier (1105-001).
     */
    public function getSupplierDepositAssetAccount(Company $company): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => '1105-001',
            ],
            [
                'name' => 'Uang Muka Pembelian / Deposito Supplier',
                'type' => 'ASSET',
                'normal_balance' => 'DEBIT',
                'is_active' => true,
            ]
        );
    }

    /**
     * Dapatkan Akun Kas Toko / Kasir Default (1001-001).
     */
    public function getDefaultCashAccount(Company $company): ChartOfAccount
    {
        return ChartOfAccount::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => '1001-001',
            ],
            [
                'name' => 'Kas Toko / Kasir',
                'type' => 'ASSET',
                'normal_balance' => 'DEBIT',
                'is_active' => true,
            ]
        );
    }

    /**
     * ========================================================
     * 1. DEPOSITO PELANGGAN (FITUR NO. 5 & 7 IPOS 5)
     * ========================================================
     */

    /**
     * Terima / Top-Up Deposito Pelanggan.
     * Jurnal:
     *   Debit: Kas / Bank (Asset bertambah)
     *   Kredit: Hutang Titipan Pelanggan (Liability bertambah)
     */
    public function topUpCustomerDeposit(Company $company, array $data, ?User $user): CustomerDeposit
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal top-up deposito harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $data, $user, $amount) {
            $customer = Customer::where('company_id', $company->id)->lockForUpdate()->findOrFail($data['customer_id']);
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : (Store::where('company_id', $company->id)->find($customer->store_id)
                    ?? Store::where('company_id', $company->id)->firstOrFail());

            $balanceBefore = (float) $customer->deposit_balance;
            $balanceAfter = round($balanceBefore + $amount, 2);

            $depositNumber = 'DEP-CUST-'.date('Ymd').'-'.strtoupper(Str::random(4));
            $txDate = $data['transaction_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'CASH';

            // Tentukan akun kas/bank tujuan
            $account = null;
            if (! empty($data['account_id'])) {
                $account = ChartOfAccount::where('company_id', $company->id)->find($data['account_id']);
            }
            if (! $account) {
                $account = $this->getDefaultCashAccount($company);
            }

            $liabilityAccount = $this->getCustomerDepositLiabilityAccount($company);

            // Buat Jurnal Finansial Seimbang
            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CUSTOMER_DEPOSIT',
                referenceNumber: $depositNumber,
                description: "Penerimaan Deposito Pelanggan [{$customer->name}] - {$depositNumber}",
                lines: [
                    [
                        'account' => $account,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'memo' => "Kas/Bank Penerimaan Deposito {$customer->name}",
                    ],
                    [
                        'account' => $liabilityAccount,
                        'debit' => 0.00,
                        'credit' => $amount,
                        'memo' => "Hutang Titipan Deposito {$customer->name}",
                    ],
                ],
                store: $store,
                userId: $user?->id,
                entryDate: $txDate
            );

            // Update saldo deposit customer
            $customer->update(['deposit_balance' => $balanceAfter]);

            return CustomerDeposit::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'customer_id' => $customer->id,
                'deposit_number' => $depositNumber,
                'type' => 'TOP_UP',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'account_id' => $account->id,
                'journal_entry_id' => $journal->id,
                'transaction_date' => $txDate,
                'notes' => $data['notes'] ?? 'Penerimaan Deposit Pelanggan',
                'created_by' => $user?->id,
            ]);
        });
    }

    /**
     * Tarik / Refund Deposito Pelanggan.
     * Jurnal:
     *   Debit: Hutang Titipan Pelanggan (Liability berkurang)
     *   Kredit: Kas / Bank (Asset berkurang)
     */
    public function withdrawCustomerDeposit(Company $company, array $data, ?User $user): CustomerDeposit
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal penarikan deposito harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $data, $user, $amount) {
            $customer = Customer::where('company_id', $company->id)->lockForUpdate()->findOrFail($data['customer_id']);
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : (Store::where('company_id', $company->id)->find($customer->store_id)
                    ?? Store::where('company_id', $company->id)->firstOrFail());

            $balanceBefore = (float) $customer->deposit_balance;
            if ($amount > $balanceBefore) {
                throw new \InvalidArgumentException("Saldo deposito tidak mencukupi (Tersedia: Rp ".number_format($balanceBefore, 0, ',', '.').", Penarikan: Rp ".number_format($amount, 0, ',', '.').").");
            }

            $balanceAfter = round($balanceBefore - $amount, 2);

            $depositNumber = 'WDR-CUST-'.date('Ymd').'-'.strtoupper(Str::random(4));
            $txDate = $data['transaction_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'CASH';

            $account = null;
            if (! empty($data['account_id'])) {
                $account = ChartOfAccount::where('company_id', $company->id)->find($data['account_id']);
            }
            if (! $account) {
                $account = $this->getDefaultCashAccount($company);
            }

            $liabilityAccount = $this->getCustomerDepositLiabilityAccount($company);

            // Buat Jurnal Finansial Seimbang
            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'CUSTOMER_DEPOSIT',
                referenceNumber: $depositNumber,
                description: "Penarikan/Pengembalian Deposito Pelanggan [{$customer->name}] - {$depositNumber}",
                lines: [
                    [
                        'account' => $liabilityAccount,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'memo' => "Pengurangan Hutang Titipan Deposito {$customer->name}",
                    ],
                    [
                        'account' => $account,
                        'debit' => 0.00,
                        'credit' => $amount,
                        'memo' => "Kas/Bank Penyerahan Penarikan {$customer->name}",
                    ],
                ],
                store: $store,
                userId: $user?->id,
                entryDate: $txDate
            );

            // Update saldo deposit customer
            $customer->update(['deposit_balance' => $balanceAfter]);

            return CustomerDeposit::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'customer_id' => $customer->id,
                'deposit_number' => $depositNumber,
                'type' => 'WITHDRAWAL',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'account_id' => $account->id,
                'journal_entry_id' => $journal->id,
                'transaction_date' => $txDate,
                'notes' => $data['notes'] ?? 'Penarikan/Refund Saldo Deposito',
                'created_by' => $user?->id,
            ]);
        });
    }

    /**
     * Dapatkan Riwayat Mutasi Deposito Pelanggan.
     */
    public function getCustomerDepositLedger(Company $company, string $customerId): Collection
    {
        return CustomerDeposit::where('company_id', $company->id)
            ->where('customer_id', $customerId)
            ->with(['account', 'creator', 'store'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Dapatkan Ringkasan Saldo Deposito Seluruh Pelanggan (Fitur No. 7).
     */
    public function getCustomerDepositsSummary(Company $company, ?string $search = null): LengthAwarePaginator
    {
        $query = Customer::where('company_id', $company->id);

        if ($search) {
            $term = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(code) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(phone) LIKE ?', [$term]);
            });
        }

        return $query->orderByDesc('deposit_balance')->orderBy('name')->paginate(20);
    }

    /**
     * ========================================================
     * 2. DEPOSITO SUPPLIER (FITUR NO. 6 & 7 IPOS 5)
     * ========================================================
     */

    /**
     * Bayar Titipan / Uang Muka ke Supplier (Uang Muka Pembelian).
     * Jurnal:
     *   Debit: Uang Muka Pembelian / Deposito Supplier (Asset bertambah)
     *   Kredit: Kas / Bank (Asset berkurang)
     */
    public function topUpSupplierDeposit(Company $company, array $data, ?User $user): SupplierDeposit
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal uang muka supplier harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $data, $user, $amount) {
            $supplier = Supplier::where('company_id', $company->id)->lockForUpdate()->findOrFail($data['supplier_id']);
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : Store::where('company_id', $company->id)->firstOrFail();

            $balanceBefore = (float) $supplier->deposit_balance;
            $balanceAfter = round($balanceBefore + $amount, 2);

            $depositNumber = 'DEP-SUP-'.date('Ymd').'-'.strtoupper(Str::random(4));
            $txDate = $data['transaction_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'BANK_TRANSFER';

            $account = null;
            if (! empty($data['account_id'])) {
                $account = ChartOfAccount::where('company_id', $company->id)->find($data['account_id']);
            }
            if (! $account) {
                $account = $this->getDefaultCashAccount($company);
            }

            $assetDepositAccount = $this->getSupplierDepositAssetAccount($company);

            // Buat Jurnal Finansial Seimbang
            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'SUPPLIER_DEPOSIT',
                referenceNumber: $depositNumber,
                description: "Uang Muka / Titipan Pembelian ke Supplier [{$supplier->name}] - {$depositNumber}",
                lines: [
                    [
                        'account' => $assetDepositAccount,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'memo' => "Uang Muka Pembelian {$supplier->name}",
                    ],
                    [
                        'account' => $account,
                        'debit' => 0.00,
                        'credit' => $amount,
                        'memo' => "Kas/Bank Pembayaran Uang Muka {$supplier->name}",
                    ],
                ],
                store: $store,
                userId: $user?->id,
                entryDate: $txDate
            );

            // Update saldo deposit supplier
            $supplier->update(['deposit_balance' => $balanceAfter]);

            return SupplierDeposit::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'supplier_id' => $supplier->id,
                'deposit_number' => $depositNumber,
                'type' => 'TOP_UP',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'account_id' => $account->id,
                'journal_entry_id' => $journal->id,
                'transaction_date' => $txDate,
                'notes' => $data['notes'] ?? 'Uang Muka / Titipan Supplier',
                'created_by' => $user?->id,
            ]);
        });
    }

    /**
     * Terima Kembali Refund Uang Muka dari Supplier.
     * Jurnal:
     *   Debit: Kas / Bank (Asset bertambah)
     *   Kredit: Uang Muka Pembelian (Asset berkurang)
     */
    public function refundSupplierDeposit(Company $company, array $data, ?User $user): SupplierDeposit
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal refund uang muka supplier harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($company, $data, $user, $amount) {
            $supplier = Supplier::where('company_id', $company->id)->lockForUpdate()->findOrFail($data['supplier_id']);
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : Store::where('company_id', $company->id)->firstOrFail();

            $balanceBefore = (float) $supplier->deposit_balance;
            if ($amount > $balanceBefore) {
                throw new \InvalidArgumentException("Saldo uang muka supplier tidak mencukupi (Tersedia: Rp ".number_format($balanceBefore, 0, ',', '.').", Penarikan: Rp ".number_format($amount, 0, ',', '.').").");
            }

            $balanceAfter = round($balanceBefore - $amount, 2);

            $depositNumber = 'REF-SUP-'.date('Ymd').'-'.strtoupper(Str::random(4));
            $txDate = $data['transaction_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'BANK_TRANSFER';

            $account = null;
            if (! empty($data['account_id'])) {
                $account = ChartOfAccount::where('company_id', $company->id)->find($data['account_id']);
            }
            if (! $account) {
                $account = $this->getDefaultCashAccount($company);
            }

            $assetDepositAccount = $this->getSupplierDepositAssetAccount($company);

            // Buat Jurnal Finansial Seimbang
            $journal = $this->accountingService->createJournalEntry(
                company: $company,
                referenceType: 'SUPPLIER_DEPOSIT',
                referenceNumber: $depositNumber,
                description: "Pengembalian / Refund Uang Muka dari Supplier [{$supplier->name}] - {$depositNumber}",
                lines: [
                    [
                        'account' => $account,
                        'debit' => $amount,
                        'credit' => 0.00,
                        'memo' => "Kas/Bank Terima Refund Uang Muka {$supplier->name}",
                    ],
                    [
                        'account' => $assetDepositAccount,
                        'debit' => 0.00,
                        'credit' => $amount,
                        'memo' => "Pengurangan Uang Muka Pembelian {$supplier->name}",
                    ],
                ],
                store: $store,
                userId: $user?->id,
                entryDate: $txDate
            );

            // Update saldo deposit supplier
            $supplier->update(['deposit_balance' => $balanceAfter]);

            return SupplierDeposit::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'supplier_id' => $supplier->id,
                'deposit_number' => $depositNumber,
                'type' => 'REFUND',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'account_id' => $account->id,
                'journal_entry_id' => $journal->id,
                'transaction_date' => $txDate,
                'notes' => $data['notes'] ?? 'Pengembalian / Refund Saldo Uang Muka',
                'created_by' => $user?->id,
            ]);
        });
    }

    /**
     * Dapatkan Riwayat Mutasi Uang Muka / Deposito Supplier.
     */
    public function getSupplierDepositLedger(Company $company, string $supplierId): Collection
    {
        return SupplierDeposit::where('company_id', $company->id)
            ->where('supplier_id', $supplierId)
            ->with(['account', 'creator', 'store'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Dapatkan Ringkasan Saldo Deposito Seluruh Supplier (Fitur No. 7).
     */
    public function getSupplierDepositsSummary(Company $company, ?string $search = null): LengthAwarePaginator
    {
        $query = Supplier::where('company_id', $company->id);

        if ($search) {
            $term = '%'.strtolower($search).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(code) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(contact_person) LIKE ?', [$term]);
            });
        }

        return $query->orderByDesc('deposit_balance')->orderBy('name')->paginate(20);
    }
}

