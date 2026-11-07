<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerPaymentItem;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomerReceivableService
{
    public function __construct(
        protected AccountingService $accountingService
    ) {}

    /**
     * Catat Penerimaan Pembayaran / Pelunasan Piutang Pelanggan (Accounts Receivable Settlement).
     *
     * 1. Alokasi pembayaran terhadap faktur-faktur tempo (manual per invoice atau otomatis FIFO).
     * 2. Pengurangan sisa piutang faktur & update payment_status (PAID / PARTIALLY_PAID).
     * 3. Pengurangan saldo hutang berjalan pelanggan (current_credit_balance).
     * 4. Pembuatan Jurnal Finansial Double-Entry Otomatis Seimbang (Debit Kas/Bank, Kredit Piutang Usaha 1004-001).
     */
    public function recordPayment(Customer $customer, array $data, User $receiver): CustomerPayment
    {
        $totalAmount = (float) ($data['total_amount'] ?? 0.00);

        if ($totalAmount <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran piutang harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($customer, $data, $receiver, $totalAmount) {
            /** @var Customer $lockedCustomer */
            $company = $customer->company;
            if (! $company) {
                abort(403, 'Pelanggan tidak memiliki perusahaan pemilik yang valid.');
            }

            $lockedCustomer = Customer::where('company_id', $company->id)
                ->whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();
            $store = ! empty($data['store_id'])
                ? Store::where('company_id', $company->id)->findOrFail($data['store_id'])
                : (Store::where('company_id', $company->id)->find($lockedCustomer->store_id)
                    ?? Store::where('company_id', $company->id)->firstOrFail());

            if ($receiver->role !== 'owner' && $receiver->store?->company_id !== $company->id) {
                abort(403, 'Penerima pembayaran tidak memiliki akses ke perusahaan pelanggan ini.');
            }

            $date = $data['payment_date'] ?? now()->toDateString();
            $dateStr = date('Ymd', strtotime($date));

            $count = CustomerPayment::where('company_id', $company->id)
                ->whereDate('created_at', today())
                ->count() + 1;
            $paymentNumber = 'RCP-'.$dateStr.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

            $payment = CustomerPayment::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'customer_id' => $lockedCustomer->id,
                'payment_number' => $paymentNumber,
                'payment_date' => $date,
                'payment_method' => strtoupper($data['payment_method'] ?? 'CASH'),
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
                'received_by' => $receiver->id,
            ]);

            // Alokasi Pembayaran ke Faktur Piutang
            $allocations = $data['allocations'] ?? [];
            $remainingToAllocate = $totalAmount;

            if (! empty($allocations)) {
                // Alokasi spesifik berdasarkan input user
                foreach ($allocations as $posId => $allocatedAmt) {
                    $allocAmt = (float) $allocatedAmt;
                    if ($allocAmt <= 0) {
                        continue;
                    }

                    /** @var PosTransaction $tx */
                    $tx = PosTransaction::where('id', $posId)
                        ->where('company_id', $company->id)
                        ->where('customer_id', $lockedCustomer->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $appliedAmt = min($allocAmt, (float) $tx->remaining_receivable);
                    $newRemaining = max(0.0, (float) $tx->remaining_receivable - $appliedAmt);
                    $newStatus = ($newRemaining <= 0.01) ? 'PAID' : 'PARTIALLY_PAID';

                    $tx->update([
                        'remaining_receivable' => $newRemaining,
                        'payment_status' => $newStatus,
                    ]);

                    CustomerPaymentItem::create([
                        'customer_payment_id' => $payment->id,
                        'pos_transaction_id' => $tx->id,
                        'amount' => $appliedAmt,
                    ]);

                    $remainingToAllocate -= $appliedAmt;
                }
            } else {
                // Alokasi Otomatis FIFO ke faktur tertua yang belum lunas
                $unpaidTxs = PosTransaction::where('customer_id', $lockedCustomer->id)
                    ->whereIn('payment_status', ['UNPAID', 'PARTIALLY_PAID'])
                    ->where('remaining_receivable', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($unpaidTxs as $tx) {
                    if ($remainingToAllocate <= 0) {
                        break;
                    }

                    $invoiceRemaining = (float) $tx->remaining_receivable;
                    $allocatedAmt = min($remainingToAllocate, $invoiceRemaining);
                    $newRemaining = max(0.0, $invoiceRemaining - $allocatedAmt);
                    $newStatus = ($newRemaining <= 0.01) ? 'PAID' : 'PARTIALLY_PAID';

                    $tx->update([
                        'remaining_receivable' => $newRemaining,
                        'payment_status' => $newStatus,
                    ]);

                    CustomerPaymentItem::create([
                        'customer_payment_id' => $payment->id,
                        'pos_transaction_id' => $tx->id,
                        'amount' => $allocatedAmt,
                    ]);

                    $remainingToAllocate -= $allocatedAmt;
                }
            }

            // Kurangi saldo piutang berjalan pelanggan
            $currentDebt = (float) $lockedCustomer->current_credit_balance;
            $newDebt = max(0.0, $currentDebt - $totalAmount);
            $lockedCustomer->update([
                'current_credit_balance' => $newDebt,
            ]);

            // Otomasi Jurnal Keuangan Double-Entry
            $this->accountingService->recordCustomerPaymentJournal($payment);

            return $payment->fresh(['items.transaction', 'customer', 'receiver', 'store']);
        });
    }

    /**
     * Dapatkan Seluruh Faktur Piutang yang Belum Lunas Milik Pelanggan.
     */
    public function getOutstandingInvoices(Customer $customer): Collection
    {
        return PosTransaction::where('customer_id', $customer->id)
            ->whereIn('payment_status', ['UNPAID', 'PARTIALLY_PAID'])
            ->where('remaining_receivable', '>', 0)
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Buku Pembantu Piutang / Kartu Piutang Pelanggan (Customer Subsidiary Ledger).
     * Menggabungkan transaksi debit (penjualan tempo) dan transaksi kredit (pembayaran),
     * lengkap dengan kalkulasi saldo piutang berjalan.
     */
    public function getCustomerLedger(Customer $customer): array
    {
        $transactions = PosTransaction::where('customer_id', $customer->id)
            ->where(function ($q) {
                $q->where('credit_amount', '>', 0)
                    ->orWhere('payment_method', 'TEMPO');
            })
            ->get();

        $payments = CustomerPayment::where('customer_id', $customer->id)
            ->with('items.transaction')
            ->get();

        $returns = PosReturn::whereHas('transaction', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->where('status', 'COMPLETED')
            ->where(function ($q) {
                $q->where('refund_method', 'CREDIT_REDUCTION')
                    ->orWhereHas('transaction', fn ($sub) => $sub->where('payment_method', 'TEMPO'));
            })
            ->with('transaction')
            ->get();

        $ledgerEntries = [];

        foreach ($transactions as $tx) {
            $debit = (float) ($tx->credit_amount > 0 ? $tx->credit_amount : $tx->grand_total);
            $ledgerEntries[] = [
                'date' => $tx->created_at ? $tx->created_at->toDateTimeString() : now()->toDateTimeString(),
                'reference_number' => $tx->invoice_number,
                'type' => 'INVOICE',
                'description' => "Penjualan POS Tempo Faktur: {$tx->invoice_number}",
                'due_date' => $tx->due_date,
                'debit' => $debit,
                'credit' => 0.00,
                'model' => $tx,
            ];
        }

        foreach ($payments as $pay) {
            $credit = (float) $pay->total_amount;
            $ledgerEntries[] = [
                'date' => $pay->payment_date ? $pay->payment_date->toDateTimeString() : $pay->created_at->toDateTimeString(),
                'reference_number' => $pay->payment_number,
                'type' => 'PAYMENT',
                'description' => "Pelunasan Piutang Kwitansi: {$pay->payment_number} ({$pay->payment_method})",
                'due_date' => null,
                'debit' => 0.00,
                'credit' => $credit,
                'model' => $pay,
            ];
        }

        foreach ($returns as $ret) {
            $credit = (float) $ret->total_refund_amount;
            $ledgerEntries[] = [
                'date' => $ret->created_at ? $ret->created_at->toDateTimeString() : $ret->return_date->toDateTimeString(),
                'reference_number' => $ret->return_number,
                'type' => 'RETURN',
                'description' => "Retur Penjualan (Kredit): {$ret->return_number} (Faktur: {$ret->transaction?->invoice_number})",
                'due_date' => null,
                'debit' => 0.00,
                'credit' => $credit,
                'model' => $ret,
            ];
        }

        // Urutkan berdasarkan tanggal ascending
        usort($ledgerEntries, fn ($a, $b) => strcmp($a['date'], $b['date']));

        // Hitung saldo berjalan (Running Balance)
        $runningBalance = 0.0;
        foreach ($ledgerEntries as &$entry) {
            $runningBalance += ($entry['debit'] - $entry['credit']);
            $entry['balance'] = max(0.0, $runningBalance);
        }

        return $ledgerEntries;
    }
}

