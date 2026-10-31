<?php

namespace App\Services;

use App\Exceptions\UnbalancedJournalException;
use App\Models\AssetDepreciationRecord;
use App\Models\BankAccount;
use App\Models\CashierShift;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\CustomerPayment;
use App\Models\FixedAsset;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\ProductAssembly;
use App\Models\Purchase;
use App\Models\GoodsReceipt;
use App\Models\PurchaseReturn;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\SupplierPayment;
use App\Models\TaxConfiguration;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingService
{
    /**
     * Buat Entri Jurnal Umum Double-Entry Seimbang (AGENTS.md Section 5).
     *
     * Setiap transaksi WAJIB memiliki Total Debit == Total Kredit (Balanced).
     */
    public function createJournalEntry(
        Company $company,
        string $referenceType,
        string $referenceNumber,
        string $description,
        array $lines,
        ?Store $store = null,
        ?string $userId = null,
        ?string $entryDate = null
    ): JournalEntry {
        if (empty($lines)) {
            throw new \InvalidArgumentException('Baris jurnal tidak boleh kosong.');
        }

        // Kunci Periode Tutup Buku Tahunan (Period Locking Protection)
        if ($referenceType !== 'YEAR_END_CLOSING' && $entryDate) {
            $year = (int) date('Y', strtotime($entryDate));
            $isClosed = DB::table('fiscal_year_closings')
                ->where('company_id', $company->id)
                ->where('fiscal_year', $year)
                ->exists();

            if ($isClosed) {
                throw new \InvalidArgumentException("Periode transaksi tahun buku {$year} telah ditutup buku dan dikunci.");
            }
        }

        return DB::transaction(function () use (
            $company,
            $referenceType,
            $referenceNumber,
            $description,
            $lines,
            $store,
            $userId,
            $entryDate
        ) {
            $totalDebit = 0.0;
            $totalCredit = 0.0;
            $processedLines = [];

            foreach ($lines as $line) {
                $debit = round((float) ($line['debit'] ?? 0.0), 2);
                $credit = round((float) ($line['credit'] ?? 0.0), 2);

                if ($debit == 0.0 && $credit == 0.0) {
                    continue;
                }

                if ($debit < 0 || $credit < 0) {
                    throw new \InvalidArgumentException('Nilai debit dan kredit tidak boleh bernilai negatif.');
                }

                $account = $line['account'] ?? null;
                if (! $account) {
                    $account = ChartOfAccount::where('company_id', $company->id)
                        ->where('code', $line['account_code'])
                        ->firstOrFail();
                }

                $totalDebit += $debit;
                $totalCredit += $credit;

                $processedLines[] = [
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $line['memo'] ?? null,
                ];
            }

            // Validasi Keseimbangan Debit vs Kredit (AGENTS.md Section 5)
            $difference = round(abs($totalDebit - $totalCredit), 2);
            if ($difference > 0.01) {
                throw new UnbalancedJournalException(
                    totalDebit: $totalDebit,
                    totalCredit: $totalCredit,
                    difference: $difference
                );
            }

            $entryNumber = 'JRN-'.date('Ymd').'-'.strtoupper(Str::random(6));

            $entry = JournalEntry::create([
                'company_id' => $company->id,
                'store_id' => $store?->id,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate ?: now()->toDateString(),
                'reference_type' => $referenceType,
                'reference_number' => $referenceNumber,
                'description' => $description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => true,
                'created_by' => $userId,
            ]);

            foreach ($processedLines as $pLine) {
                $pLine['journal_entry_id'] = $entry->id;
                JournalEntryLine::create($pLine);
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * Otomasi Jurnal Penjualan Kasir POS (Sales Journal & HPP / COGS).
     */
    public function recordSalesJournal(PosTransaction $transaction): JournalEntry
    {
        $company = $transaction->company;
        $store = $transaction->store;
        $lines = [];

        // 1. Tentukan Akun Penerimaan Pembayaran (DEBIT)
        $paidAmount = (float) $transaction->paid_amount;
        $changeAmount = (float) $transaction->change_amount;
        $netPaymentAmount = max(0.0, $paidAmount - $changeAmount);
        $creditAmount = (float) ($transaction->credit_amount ?? 0.00);

        if ($transaction->payment_method === 'SPLIT') {
            if ((float) ($transaction->split_non_cash_amount ?? 0) > 0) {
                // Mode 1: Split Tunai + Non-Tunai (CASH + QRIS/TRANSFER)
                $splitCash = (float) $transaction->split_cash_amount;
                $splitNonCash = (float) $transaction->split_non_cash_amount;
                $nonCashMethod = strtoupper($transaction->split_non_cash_method ?: 'QRIS');

                if ($splitCash > 0) {
                    $lines[] = [
                        'account_code' => '1001-001', // Kas Toko
                        'debit' => $splitCash,
                        'credit' => 0.00,
                        'memo' => 'Penerimaan Kas Tunai (Split) POS '.$transaction->invoice_number,
                    ];
                }

                if ($splitNonCash > 0) {
                    if ($nonCashMethod === 'TRANSFER') {
                        $bankAccountCode = match (strtoupper($transaction->bank_name ?? '')) {
                            'MANDIRI' => '1002-002',
                            'BRI' => '1002-003',
                            'BNI' => '1002-004',
                            default => '1002-001',
                        };
                        $targetAccount = ChartOfAccount::where('company_id', $company->id)
                            ->where('code', $bankAccountCode)
                            ->first();
                        $finalAccountCode = $targetAccount ? $bankAccountCode : '1002-001';
                        $bankInfo = $transaction->bank_name ? " [{$transaction->bank_name} - {$transaction->bank_account_number}]" : '';
                        $refInfo = $transaction->payment_reference ? " (Ref: {$transaction->payment_reference})" : '';

                        $lines[] = [
                            'account_code' => $finalAccountCode,
                            'debit' => $splitNonCash,
                            'credit' => 0.00,
                            'memo' => "Penerimaan Transfer (Split){$bankInfo}{$refInfo} POS {$transaction->invoice_number}",
                        ];
                    } else {
                        // QRIS
                        $lines[] = [
                            'account_code' => '1003-001', // QRIS
                            'debit' => $splitNonCash,
                            'credit' => 0.00,
                            'memo' => 'Penerimaan QRIS (Split) POS '.$transaction->invoice_number,
                        ];
                    }
                }

                if ($creditAmount > 0) {
                    $lines[] = [
                        'account_code' => '1004-001', // Piutang Usaha (Tempo)
                        'debit' => $creditAmount,
                        'credit' => 0.00,
                        'memo' => 'Piutang Sisa Pembayaran Split POS '.$transaction->invoice_number,
                    ];
                }
            } else {
                // Mode 2: Split Bayar Sebagian + Sisa Tempo/Kredit
                $splitMethod = strtoupper($transaction->split_payment_method ?: 'CASH');

                // Bagian Tunai / Transfer / QRIS
                if ($netPaymentAmount > 0) {
                    if ($splitMethod === 'TRANSFER') {
                        $bankAccountCode = match (strtoupper($transaction->bank_name ?? '')) {
                            'MANDIRI' => '1002-002',
                            'BRI' => '1002-003',
                            'BNI' => '1002-004',
                            default => '1002-001',
                        };
                        $targetAccount = ChartOfAccount::where('company_id', $company->id)
                            ->where('code', $bankAccountCode)
                            ->first();
                        $finalAccountCode = $targetAccount ? $bankAccountCode : '1002-001';
                        $bankInfo = $transaction->bank_name ? " [{$transaction->bank_name} - {$transaction->bank_account_number}]" : '';
                        $refInfo = $transaction->payment_reference ? " (Ref: {$transaction->payment_reference})" : '';

                        $lines[] = [
                            'account_code' => $finalAccountCode,
                            'debit' => $netPaymentAmount,
                            'credit' => 0.00,
                            'memo' => "Penerimaan Transfer Sebagian{$bankInfo}{$refInfo} POS {$transaction->invoice_number}",
                        ];
                    } elseif ($splitMethod === 'QRIS') {
                        $lines[] = [
                            'account_code' => '1003-001',
                            'debit' => $netPaymentAmount,
                            'credit' => 0.00,
                            'memo' => 'Penerimaan QRIS Sebagian POS '.$transaction->invoice_number,
                        ];
                    } else {
                        $lines[] = [
                            'account_code' => '1001-001',
                            'debit' => $netPaymentAmount,
                            'credit' => 0.00,
                            'memo' => 'Penerimaan Kas Tunai Sebagian POS '.$transaction->invoice_number,
                        ];
                    }
                }

                // Bagian Sisa Piutang / Tempo
                if ($creditAmount > 0) {
                    $lines[] = [
                        'account_code' => '1004-001', // Piutang Usaha (Tempo)
                        'debit' => $creditAmount,
                        'credit' => 0.00,
                        'memo' => 'Piutang Sisa Pembayaran Split POS '.$transaction->invoice_number,
                    ];
                }
            }
        } else {
            switch ($transaction->payment_method) {
                case 'CASH':
                    $lines[] = [
                        'account_code' => '1001-001', // Kas Toko
                        'debit' => $netPaymentAmount,
                        'credit' => 0.00,
                        'memo' => 'Penerimaan Kas Penjualan POS '.$transaction->invoice_number,
                    ];
                    if ($creditAmount > 0) {
                        $lines[] = [
                            'account_code' => '1004-001', // Piutang Usaha
                            'debit' => $creditAmount,
                            'credit' => 0.00,
                            'memo' => 'Piutang Sisa Penjualan POS '.$transaction->invoice_number,
                        ];
                    }
                    break;

                case 'QRIS':
                    $lines[] = [
                        'account_code' => '1003-001', // QRIS / Digital Payment
                        'debit' => (float) $transaction->grand_total,
                        'credit' => 0.00,
                        'memo' => 'Penerimaan QRIS Penjualan POS '.$transaction->invoice_number,
                    ];
                    break;

                case 'TRANSFER':
                    $bankAccountCode = match (strtoupper($transaction->bank_name ?? '')) {
                        'MANDIRI' => '1002-002',
                        'BRI' => '1002-003',
                        'BNI' => '1002-004',
                        default => '1002-001', // Bank BCA / default
                    };

                    $targetAccount = ChartOfAccount::where('company_id', $company->id)
                        ->where('code', $bankAccountCode)
                        ->first();
                    $finalAccountCode = $targetAccount ? $bankAccountCode : '1002-001';

                    $bankInfo = $transaction->bank_name ? " [{$transaction->bank_name} - {$transaction->bank_account_number}]" : '';
                    $refInfo = $transaction->payment_reference ? " (Ref: {$transaction->payment_reference})" : '';

                    $lines[] = [
                        'account_code' => $finalAccountCode,
                        'debit' => (float) $transaction->grand_total,
                        'credit' => 0.00,
                        'memo' => "Penerimaan Transfer Bank{$bankInfo}{$refInfo} POS {$transaction->invoice_number}",
                    ];
                    break;

                case 'TEMPO':
                    $lines[] = [
                        'account_code' => '1004-001', // Piutang Usaha / Tempo
                        'debit' => (float) $transaction->grand_total,
                        'credit' => 0.00,
                        'memo' => 'Piutang Penjualan Tempo/Kredit POS '.$transaction->invoice_number,
                    ];
                    break;
            }
        }

        // 2. Penebusan Voucher Belanja (DEBIT Unearned Revenue -> Sales) (AGENTS.md Section 3 & 5)
        $voucherDiscount = (float) $transaction->voucher_discount;
        if ($voucherDiscount > 0) {
            $lines[] = [
                'account_code' => '2002-001', // Pendapatan Diterima Dimuka (Voucher)
                'debit' => $voucherDiscount,
                'credit' => 0.00,
                'memo' => 'Penebusan Voucher Diskon '.($transaction->voucher?->code ?? ''),
            ];
        }

        // 2b. Potongan Penukaran Poin Loyalitas Pelanggan (DEBIT Kontra Penjualan)
        $pointsDiscount = (float) ($transaction->points_discount_amount ?? 0.0);
        if ($pointsDiscount > 0) {
            $discountAccount = ChartOfAccount::where('company_id', $company->id)
                ->where('code', '4001-002')
                ->first();
            $accountCode = $discountAccount ? '4001-002' : '4001-001';

            $lines[] = [
                'account_code' => $accountCode,
                'debit' => $pointsDiscount,
                'credit' => 0.00,
                'memo' => "Diskon Penukaran Poin Member ({$transaction->points_redeemed} Pts) POS {$transaction->invoice_number}",
            ];
        }

        // 3. Pendapatan Penjualan & PPN Keluaran (KREDIT)
        $taxAmount = (float) $transaction->tax_amount;
        $subtotal = (float) $transaction->subtotal;

        if ($taxAmount > 0) {
            // Mode PKP dengan PPN (Bisa Exclude atau Include)
            $grandTotal = (float) $transaction->grand_total;
            $voucherDiscount = (float) $transaction->voucher_discount;
            $isTaxExcluded = $grandTotal > ($subtotal - $voucherDiscount);
            $netRevenue = $isTaxExcluded ? $subtotal : max(0.0, $subtotal - $taxAmount);

            $lines[] = [
                'account_code' => '4001-001', // Pendapatan Penjualan Toko
                'debit' => 0.00,
                'credit' => $netRevenue,
                'memo' => 'Pendapatan Bersih Penjualan '.$transaction->invoice_number,
            ];
            $lines[] = [
                'account_code' => '2003-001', // PPN Keluaran
                'debit' => 0.00,
                'credit' => $taxAmount,
                'memo' => 'PPN Keluaran Penjualan '.$transaction->invoice_number,
            ];
        } else {
            // Mode Non-PKP (Tanpa PPN)
            $lines[] = [
                'account_code' => '4001-001', // Pendapatan Penjualan Toko
                'debit' => 0.00,
                'credit' => $subtotal,
                'memo' => 'Pendapatan Penjualan Non-PKP '.$transaction->invoice_number,
            ];
        }

        // 3b. Selisih Pembulatan Nilai Transaksi (Rounding)
        $rounding = (float) ($transaction->rounding_amount ?? 0.00);
        if ($rounding > 0) {
            $lines[] = [
                'account_code' => '4002-001', // Pendapatan Selisih / Pembulatan
                'debit' => 0.00,
                'credit' => $rounding,
                'memo' => 'Pendapatan Pembulatan Transaksi POS '.$transaction->invoice_number,
            ];
        } elseif ($rounding < 0) {
            $lines[] = [
                'account_code' => '6199-001', // Beban Operasional Lain-lain
                'debit' => abs($rounding),
                'credit' => 0.00,
                'memo' => 'Beban Selisih Pembulatan POS '.$transaction->invoice_number,
            ];
        }

        // 4. Jurnal Beban Pokok Penjualan (HPP) & Persediaan Barang Dagang
        // Debit: HPP (5001-001) | Kredit: Persediaan (1005-001)
        $totalCostOfGoods = 0.0;
        foreach ($transaction->items as $item) {
            $totalCostOfGoods += ((float) $item->base_quantity * (float) $item->unit_cost);
        }

        if ($totalCostOfGoods > 0) {
            $lines[] = [
                'account_code' => '5001-001', // Beban Pokok Penjualan (HPP)
                'debit' => $totalCostOfGoods,
                'credit' => 0.00,
                'memo' => 'Beban HPP Penjualan '.$transaction->invoice_number,
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $totalCostOfGoods,
                'memo' => 'Pengurangan Persediaan Penjualan '.$transaction->invoice_number,
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'POS_SALE',
            referenceNumber: $transaction->invoice_number,
            description: "Jurnal Penjualan Kasir POS Faktur: {$transaction->invoice_number}",
            lines: $lines,
            store: $store,
            userId: $transaction->cashier_id
        );
    }

    /**
     * Otomasi Jurnal Penerimaan Barang dari Supplier (Goods Receipt / Purchasing).
     */
    public function recordGoodsReceiptJournal(
        Company $company,
        Store $store,
        string $referenceNumber,
        float $totalPurchasedValue,
        string $paymentType = 'CREDIT',
        ?string $bankAccountCode = null,
        float $taxAmount = 0.00
    ): JournalEntry {
        $taxAmount = round(max(0.0, $taxAmount), 2);
        $totalPurchasedValue = round(max(0.0, $totalPurchasedValue), 2);
        $netInventoryValue = round($totalPurchasedValue - $taxAmount, 2);

        $lines = [];

        if ($taxAmount > 0 && $netInventoryValue > 0) {
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang (Net)
                'debit' => $netInventoryValue,
                'credit' => 0.00,
                'memo' => 'Penerimaan Persediaan dari Supplier '.$referenceNumber,
            ];
            $lines[] = [
                'account_code' => '1006-001', // PPN Masukan (VAT In)
                'debit' => $taxAmount,
                'credit' => 0.00,
                'memo' => 'PPN Masukan Faktur Pembelian '.$referenceNumber,
            ];
        } else {
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang (Gross)
                'debit' => $totalPurchasedValue,
                'credit' => 0.00,
                'memo' => 'Penerimaan Persediaan dari Supplier '.$referenceNumber,
            ];
        }

        $upperType = strtoupper($paymentType);
        if ($upperType === 'CASH' || $upperType === 'TUNAI') {
            $lines[] = [
                'account_code' => '1001-001', // Kas Toko
                'debit' => 0.00,
                'credit' => $totalPurchasedValue,
                'memo' => 'Pembayaran Tunai Pembelian '.$referenceNumber,
            ];
        } elseif ($upperType === 'TRANSFER') {
            $lines[] = [
                'account_code' => $bankAccountCode ?: '1002-001', // Rekening Bank
                'debit' => 0.00,
                'credit' => $totalPurchasedValue,
                'memo' => 'Pembayaran Transfer Bank Pembelian '.$referenceNumber,
            ];
        } else {
            $lines[] = [
                'account_code' => '2001-001', // Hutang Usaha (Accounts Payable)
                'debit' => 0.00,
                'credit' => $totalPurchasedValue,
                'memo' => 'Hutang Usaha Pembelian '.$referenceNumber,
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'GOODS_RECEIPT',
            referenceNumber: $referenceNumber,
            description: "Jurnal Penerimaan Barang Dagang PO/GRN: {$referenceNumber}",
            lines: $lines,
            store: $store
        );
    }

    /**
     * Jurnal Penerimaan Fisik Barang Gudang (Goods Receipt Note / LPB - AGENTS.md Section 4C & 5).
     * [Dr] 1005-001 Persediaan Barang Dagang (termasuk alokasi landed cost ongkir)
     * [Cr] 2004-001 Hutang Belum Difakturkan (GRNI / Goods Received Not Invoiced)
     * [Cr] 1001-001 Kas Toko (jika landed cost ongkir dibayar tunai)
     */
    public function recordGoodsReceiptGrniJournal(GoodsReceipt $receipt): JournalEntry
    {
        $company = $receipt->company;
        $store = $receipt->store;
        $inventoryValue = round((float) $receipt->estimated_total_value + (float) $receipt->total_landed_cost, 2);
        $grniAmount = round((float) $receipt->estimated_total_value, 2);
        $landedCost = round((float) $receipt->total_landed_cost, 2);

        $lines = [
            [
                'account_code' => '1005-001',
                'debit' => $inventoryValue,
                'credit' => 0.00,
                'memo' => "Penerimaan Fisik Gudang LPB: {$receipt->receipt_number}",
            ],
            [
                'account_code' => '2004-001',
                'debit' => 0.00,
                'credit' => $grniAmount,
                'memo' => "Akrual Hutang Belum Ditagih LPB: {$receipt->receipt_number}",
            ],
        ];

        if ($landedCost > 0) {
            $lines[] = [
                'account_code' => '1001-001',
                'debit' => 0.00,
                'credit' => $landedCost,
                'memo' => "Biaya Angkut/Landed Cost LPB: {$receipt->receipt_number}",
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'GOODS_RECEIPT_LPB',
            referenceNumber: $receipt->receipt_number,
            description: "Jurnal Penerimaan Fisik LPB Gudang No: {$receipt->receipt_number} DO: {$receipt->delivery_order_number}",
            lines: $lines,
            store: $store
        );
    }

    /**
     * Jurnal Penyelesaian Three-Way Matching (Vendor Invoice Matching).
     * [Dr] 2004-001 Hutang Belum Difakturkan (GRNI) - Menutup Akrual LPB
     * [Dr] 1006-001 PPN Masukan (VAT In) - Jika ada pajak
     * [Dr/Cr] 1005-001 - Selisih harga jika ada variance
     * [Cr] 2001-001 Hutang Usaha (AP) - Mengakui kewajiban hutang faktur resmi
     */
    public function recordVendorInvoiceMatchingJournal(Purchase $purchase, GoodsReceipt $receipt): JournalEntry
    {
        $company = $purchase->company;
        $store = $purchase->store;
        
        $grniValue = round((float) $receipt->estimated_total_value, 2);
        $invoiceSubtotal = round((float) $purchase->subtotal, 2);
        $taxAmount = round((float) $purchase->tax_amount, 2);
        $discountAmount = round((float) $purchase->discount_amount, 2);
        $grandTotal = round((float) $purchase->grand_total, 2);

        $lines = [
            [
                'account_code' => '2004-001',
                'debit' => $grniValue,
                'credit' => 0.00,
                'memo' => "Eliminasi Akrual GRNI LPB {$receipt->receipt_number} via Faktur {$purchase->invoice_number}",
            ],
        ];

        $variance = round($invoiceSubtotal - $grniValue, 2);
        if ($variance > 0) {
            $lines[] = [
                'account_code' => '1005-001',
                'debit' => $variance,
                'credit' => 0.00,
                'memo' => "Selisih Kenaikan Harga Faktur {$purchase->invoice_number}",
            ];
        } elseif ($variance < 0) {
            $lines[] = [
                'account_code' => '1005-001',
                'debit' => 0.00,
                'credit' => abs($variance),
                'memo' => "Diskon/Penurunan Harga Faktur {$purchase->invoice_number}",
            ];
        }

        if ($taxAmount > 0) {
            $lines[] = [
                'account_code' => '1006-001',
                'debit' => $taxAmount,
                'credit' => 0.00,
                'memo' => "PPN Masukan Faktur {$purchase->invoice_number}",
            ];
        }

        if ($discountAmount > 0) {
            $lines[] = [
                'account_code' => '1005-001',
                'debit' => 0.00,
                'credit' => $discountAmount,
                'memo' => "Diskon Tambahan Faktur {$purchase->invoice_number}",
            ];
        }

        $upperType = strtoupper($purchase->payment_type ?? 'TEMPO');
        if ($upperType === 'CASH' || $upperType === 'TUNAI') {
            $lines[] = [
                'account_code' => '1001-001',
                'debit' => 0.00,
                'credit' => $grandTotal,
                'memo' => "Pelunasan Tunai Faktur Pembelian {$purchase->invoice_number}",
            ];
        } elseif ($upperType === 'TRANSFER') {
            $lines[] = [
                'account_code' => '1002-001',
                'debit' => 0.00,
                'credit' => $grandTotal,
                'memo' => "Pembayaran Transfer Faktur Pembelian {$purchase->invoice_number}",
            ];
        } else {
            $lines[] = [
                'account_code' => '2001-001',
                'debit' => 0.00,
                'credit' => $grandTotal,
                'memo' => "Hutang Usaha Resmi Faktur {$purchase->invoice_number}",
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'THREE_WAY_MATCH',
            referenceNumber: $purchase->reference_number,
            description: "Jurnal Three-Way Matching Faktur: {$purchase->invoice_number} vs LPB: {$receipt->receipt_number}",
            lines: $lines,
            store: $store
        );
    }

    /**
     * Otomasi Jurnal Penerbitan / Penjualan Voucher Belanja (Unearned Revenue).
     */
    public function recordVoucherIssuanceJournal(Voucher $voucher, float $cashReceived, ?Store $store = null): JournalEntry
    {
        $company = $voucher->company;

        $lines = [
            [
                'account_code' => '1001-001', // Kas Toko
                'debit' => $cashReceived,
                'credit' => 0.00,
                'memo' => 'Penerimaan Kas Penjualan Voucher '.$voucher->code,
            ],
            [
                'account_code' => '2002-001', // Pendapatan Diterima Dimuka (Unearned Revenue)
                'debit' => 0.00,
                'credit' => $cashReceived,
                'memo' => 'Kewajiban Voucher Belanja Belum Ditebus '.$voucher->code,
            ],
        ];

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'VOUCHER_ISSUANCE',
            referenceNumber: $voucher->code,
            description: "Jurnal Penerbitan Voucher Belanja: {$voucher->code}",
            lines: $lines,
            store: $store
        );
    }

    /**
     * Otomasi Jurnal Penyusutan Garis Lurus Bulanan (Straight-Line Depreciation - AGENTS.md Section 5).
     */
    public function recordStraightLineDepreciation(FixedAsset $asset, string $periodDate): JournalEntry
    {
        return DB::transaction(function () use ($asset, $periodDate) {
            /** @var FixedAsset $lockedAsset */
            $lockedAsset = FixedAsset::where('id', $asset->id)->lockForUpdate()->firstOrFail();

            if ($lockedAsset->status === 'FULLY_DEPRECIATED') {
                throw new \RuntimeException("Aset [{$lockedAsset->name}] sudah habis masa manfaatnya dan tersusut penuh.");
            }

            $currentBookValue = (float) $lockedAsset->book_value;
            $salvageValue = (float) $lockedAsset->salvage_value;
            $monthlyDepreciation = (float) $lockedAsset->monthly_depreciation_amount;

            // Maksimal nilai yang boleh disusutkan agar tidak tembus di bawah salvage value
            $maxAllowable = max(0, $currentBookValue - $salvageValue);
            $depreciationAmount = min($monthlyDepreciation, $maxAllowable);

            if ($depreciationAmount <= 0) {
                $lockedAsset->update(['status' => 'FULLY_DEPRECIATED']);
                throw new \RuntimeException("Nilai buku aset [{$lockedAsset->name}] sudah mencapai nilai residu.");
            }

            $newAccumulated = (float) $lockedAsset->accumulated_depreciation + $depreciationAmount;
            $newBookValue = (float) $lockedAsset->acquisition_cost - $newAccumulated;

            $status = $newBookValue <= $salvageValue ? 'FULLY_DEPRECIATED' : 'ACTIVE';

            $lockedAsset->update([
                'accumulated_depreciation' => $newAccumulated,
                'book_value' => $newBookValue,
                'last_depreciation_date' => $periodDate,
                'status' => $status,
            ]);

            $accumulatedAccount = match ($lockedAsset->category) {
                'VEHICLE' => '1502-002',
                'BUILDING' => '1502-003',
                'FURNITURE' => '1502-004',
                default => '1502-001',
            };

            // Jurnal Penyusutan:
            // Debit: Beban Penyusutan Aset Tetap (6001-001)
            // Kredit: Akumulasi Penyusutan Aset Tetap Sesuai Kategori
            $lines = [
                [
                    'account_code' => '6001-001',
                    'debit' => $depreciationAmount,
                    'credit' => 0.00,
                    'memo' => "Beban Penyusutan {$lockedAsset->name} Periode {$periodDate}",
                ],
                [
                    'account_code' => $accumulatedAccount,
                    'debit' => 0.00,
                    'credit' => $depreciationAmount,
                    'memo' => "Akumulasi Penyusutan {$lockedAsset->name} Periode {$periodDate}",
                ],
            ];

            $journal = $this->createJournalEntry(
                company: $lockedAsset->company,
                referenceType: 'ASSET_DEPRECIATION',
                referenceNumber: $lockedAsset->asset_code.'-'.$periodDate,
                description: "Jurnal Penyusutan Garis Lurus Aset [{$lockedAsset->name}] Periode {$periodDate}",
                lines: $lines,
                store: $lockedAsset->store,
                entryDate: $periodDate
            );

            // Simpan Riwayat Penyusutan Aset
            AssetDepreciationRecord::create([
                'fixed_asset_id' => $lockedAsset->id,
                'journal_entry_id' => $journal->id,
                'period_date' => $periodDate,
                'amount' => $depreciationAmount,
                'book_value_after' => $newBookValue,
            ]);

            return $journal;
        });
    }

    /**
     * Jurnal Perolehan Aset Tetap Baru (Cash / Bank / Modal Pemilik - AGENTS.md Section 5).
     */
    public function recordAssetAcquisition(
        FixedAsset $asset,
        string $fundingSource = 'CASH',
        ?BankAccount $bankAccount = null
    ): JournalEntry {
        return DB::transaction(function () use ($asset, $fundingSource, $bankAccount) {
            $cost = (float) $asset->acquisition_cost;

            $assetAccount = match ($asset->category) {
                'VEHICLE' => '1501-002',
                'BUILDING' => '1501-003',
                'FURNITURE' => '1501-004',
                default => '1501-001',
            };

            $creditAccount = '1001-001';
            if ($fundingSource === 'BANK') {
                $creditAccount = '1002-001';
                if ($bankAccount) {
                    $bankName = strtoupper($bankAccount->bank_name ?? '');
                    $candidate = match (true) {
                        str_contains($bankName, 'MANDIRI') => '1002-002',
                        str_contains($bankName, 'BRI') => '1002-003',
                        str_contains($bankName, 'BNI') => '1002-004',
                        default => '1002-001',
                    };
                    $creditAccount = ChartOfAccount::where('company_id', $asset->company_id)->where('code', $candidate)->exists() ? $candidate : '1002-001';
                }
            } elseif ($fundingSource === 'MODAL_AWAL') {
                $creditAccount = '3001-001';
            }

            $lines = [
                [
                    'account_code' => $assetAccount,
                    'debit' => $cost,
                    'credit' => 0.00,
                    'memo' => "Perolehan Aset Tetap {$asset->name} ({$asset->asset_code})",
                ],
                [
                    'account_code' => $creditAccount,
                    'debit' => 0.00,
                    'credit' => $cost,
                    'memo' => "Sumber Dana Perolehan Aset {$asset->name}",
                ],
            ];

            return $this->createJournalEntry(
                company: $asset->company,
                referenceType: 'ASSET_ACQUISITION',
                referenceNumber: 'ACQ-'.$asset->asset_code,
                description: "Jurnal Perolehan Aset Tetap [{$asset->name}] ({$asset->asset_code})",
                lines: $lines,
                store: $asset->store,
                entryDate: $asset->acquisition_date->toDateString()
            );
        });
    }

    /**
     * Jurnal Pelepasan / Penghentian Aset Tetap (Asset Disposal / Write-Off).
     */
    public function recordAssetDisposal(
        FixedAsset $asset,
        float $disposalProceeds = 0.0,
        ?string $disposalDate = null,
        ?BankAccount $bankAccount = null
    ): JournalEntry {
        return DB::transaction(function () use ($asset, $disposalProceeds, $disposalDate, $bankAccount) {
            $disposalDate = $disposalDate ?: now()->toDateString();
            $acquisitionCost = (float) $asset->acquisition_cost;
            $accumulatedDep = (float) $asset->accumulated_depreciation;
            $bookValue = (float) $asset->book_value;
            $gainOrLoss = round($disposalProceeds - $bookValue, 2);

            $assetAccount = match ($asset->category) {
                'VEHICLE' => '1501-002',
                'BUILDING' => '1501-003',
                'FURNITURE' => '1501-004',
                default => '1501-001',
            };

            $accumulatedAccount = match ($asset->category) {
                'VEHICLE' => '1502-002',
                'BUILDING' => '1502-003',
                'FURNITURE' => '1502-004',
                default => '1502-001',
            };

            $lines = [];

            // 1. Terima kas / bank jika aset dijual berbayar
            if ($disposalProceeds > 0) {
                $proceedsAccount = '1001-001';
                if ($bankAccount) {
                    $bankName = strtoupper($bankAccount->bank_name ?? '');
                    $candidate = match (true) {
                        str_contains($bankName, 'MANDIRI') => '1002-002',
                        str_contains($bankName, 'BRI') => '1002-003',
                        str_contains($bankName, 'BNI') => '1002-004',
                        default => '1002-001',
                    };
                    $proceedsAccount = ChartOfAccount::where('company_id', $asset->company_id)->where('code', $candidate)->exists() ? $candidate : '1002-001';
                }
                $lines[] = [
                    'account_code' => $proceedsAccount,
                    'debit' => $disposalProceeds,
                    'credit' => 0.00,
                    'memo' => "Hasil Penjualan Pelepasan Aset {$asset->name}",
                ];
            }

            // 2. Tutup Akumulasi Penyusutan (Debet)
            if ($accumulatedDep > 0) {
                $lines[] = [
                    'account_code' => $accumulatedAccount,
                    'debit' => $accumulatedDep,
                    'credit' => 0.00,
                    'memo' => "Tutup Akumulasi Penyusutan Aset {$asset->name}",
                ];
            }

            // 3. Catat Rugi atau Untung Pelepasan Aset
            if ($gainOrLoss < 0) {
                // Rugi Pelepasan Aset (Debet)
                $lines[] = [
                    'account_code' => '6001-001',
                    'debit' => abs($gainOrLoss),
                    'credit' => 0.00,
                    'memo' => "Rugi Pelepasan Aset {$asset->name}",
                ];
            } elseif ($gainOrLoss > 0) {
                // Untung Penjualan Aset (Kredit)
                $lines[] = [
                    'account_code' => '4002-001',
                    'debit' => 0.00,
                    'credit' => $gainOrLoss,
                    'memo' => "Keuntungan Pelepasan Aset {$asset->name}",
                ];
            }

            // 4. Tutup Harga Perolehan Aset (Kredit)
            $lines[] = [
                'account_code' => $assetAccount,
                'debit' => 0.00,
                'credit' => $acquisitionCost,
                'memo' => "Pelepasan & Penghapusan Aset Tetap {$asset->name}",
            ];

            $journal = $this->createJournalEntry(
                company: $asset->company,
                referenceType: 'ASSET_DISPOSAL',
                referenceNumber: 'DSP-'.$asset->asset_code,
                description: "Jurnal Pelepasan Aset Tetap [{$asset->name}] ({$asset->asset_code})",
                lines: $lines,
                store: $asset->store,
                entryDate: $disposalDate
            );

            $asset->update([
                'status' => 'DISPOSED',
                'book_value' => 0.00,
            ]);

            return $journal;
        });
    }

    /**
     * Otomasi Jurnal Selisih Kas Tutup Shift Kasir (Blind Closing Discrepancy).
     */
    public function recordCashDiscrepancyJournal(CashierShift $shift): ?JournalEntry
    {
        $discrepancy = (float) $shift->cash_discrepancy;

        if (abs($discrepancy) < 0.01) {
            return null; // Tidak ada selisih, kas pas seimbang
        }

        $lines = [];

        if ($discrepancy < 0) {
            // Kas Kurang / Tekor (Cash Shortage)
            // Debit: Beban Selisih Kas (6002-001) | Kredit: Kas Toko (1001-001)
            $amount = abs($discrepancy);
            $lines = [
                [
                    'account_code' => '6002-001',
                    'debit' => $amount,
                    'credit' => 0.00,
                    'memo' => "Selisih Kurang Kas Shift {$shift->shift_number}",
                ],
                [
                    'account_code' => '1001-001',
                    'debit' => 0.00,
                    'credit' => $amount,
                    'memo' => "Penyesuaian Fisik Kas Toko Shift {$shift->shift_number}",
                ],
            ];
        } else {
            // Kas Lebih (Cash Overage)
            // Debit: Kas Toko (1001-001) | Kredit: Pendapatan Selisih Kas (4002-001)
            $lines = [
                [
                    'account_code' => '1001-001',
                    'debit' => $discrepancy,
                    'credit' => 0.00,
                    'memo' => "Penyesuaian Fisik Kas Toko Shift {$shift->shift_number}",
                ],
                [
                    'account_code' => '4002-001',
                    'debit' => 0.00,
                    'credit' => $discrepancy,
                    'memo' => "Pendapatan Selisih Lebih Kas Shift {$shift->shift_number}",
                ],
            ];
        }

        return $this->createJournalEntry(
            company: $shift->company,
            referenceType: 'CASH_DISCREPANCY',
            referenceNumber: $shift->shift_number,
            description: "Jurnal Selisih Kas Blind Closing Shift: {$shift->shift_number}",
            lines: $lines,
            store: $shift->store
        );
    }

    /**
     * Cek apakah perpajakan PPN aktif pada tanggal tertentu (Taxation Flexible Toggle - AGENTS.md Section 5).
     */
    public function isTaxEnabled(Company $company, ?string $date = null): bool
    {
        $checkDate = $date ?: now()->toDateString();

        $taxConfig = TaxConfiguration::where('company_id', $company->id)
            ->where('is_tax_enabled', true)
            ->where('effective_from', '<=', $checkDate)
            ->where(function ($q) use ($checkDate) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $checkDate);
            })
            ->first();

        return (bool) $taxConfig;
    }

    /**
     * Otomasi Jurnal Pembalikan Penjualan (Sales Return / Retur POS).
     * Membalik pendapatan penjualan, pajak, kas/piutang, serta memulihkan HPP dan persediaan.
     */
    public function recordSalesReturnJournal(PosReturn $posReturn): JournalEntry
    {
        $company = $posReturn->company;
        $store = $posReturn->store;
        $lines = [];

        $subtotalReturned = (float) $posReturn->subtotal_returned;
        $taxReturned = (float) $posReturn->tax_returned;
        $totalRefund = (float) $posReturn->total_refund_amount;

        // 1. Pembalikan Pendapatan (DEBIT Pendapatan Penjualan)
        if ($subtotalReturned > 0) {
            $lines[] = [
                'account_code' => '4001-001', // Pendapatan Penjualan Toko
                'debit' => $subtotalReturned,
                'credit' => 0.00,
                'memo' => "Pembalikan Pendapatan Retur POS {$posReturn->return_number}",
            ];
        }

        // 2. Pembalikan PPN Keluaran (DEBIT PPN Keluaran)
        if ($taxReturned > 0) {
            $lines[] = [
                'account_code' => '2003-001', // PPN Keluaran
                'debit' => $taxReturned,
                'credit' => 0.00,
                'memo' => "Pembalikan PPN Keluaran Retur POS {$posReturn->return_number}",
            ];
        }

        // 3. Pengembalian Kas / Bank / Pengurangan Piutang (KREDIT)
        $refundMethod = strtoupper($posReturn->refund_method ?: 'CASH');
        if ($refundMethod === 'CREDIT_REDUCTION') {
            $lines[] = [
                'account_code' => '1004-001', // Piutang Usaha
                'debit' => 0.00,
                'credit' => $totalRefund,
                'memo' => "Pengurangan Piutang Retur POS {$posReturn->return_number}",
            ];
        } elseif ($refundMethod === 'TRANSFER') {
            $lines[] = [
                'account_code' => '1002-001', // Bank Rekening
                'debit' => 0.00,
                'credit' => $totalRefund,
                'memo' => "Pengembalian Dana Transfer Retur POS {$posReturn->return_number}",
            ];
        } else {
            $lines[] = [
                'account_code' => '1001-001', // Kas Toko
                'debit' => 0.00,
                'credit' => $totalRefund,
                'memo' => "Pengembalian Kas Tunai Retur POS {$posReturn->return_number}",
            ];
        }

        // 4. Pembalikan HPP & Pemulihan Persediaan (DEBIT Persediaan, KREDIT HPP)
        $totalCostOfReturnedGoods = 0.0;
        foreach ($posReturn->items as $item) {
            $totalCostOfReturnedGoods += ((float) $item->base_quantity * (float) $item->unit_cost);
        }

        if ($totalCostOfReturnedGoods > 0) {
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => $totalCostOfReturnedGoods,
                'credit' => 0.00,
                'memo' => "Pemulihan Persediaan Retur POS {$posReturn->return_number}",
            ];
            $lines[] = [
                'account_code' => '5001-001', // Beban Pokok Penjualan (HPP)
                'debit' => 0.00,
                'credit' => $totalCostOfReturnedGoods,
                'memo' => "Pembalikan HPP Retur POS {$posReturn->return_number}",
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'POS_RETURN',
            referenceNumber: $posReturn->return_number,
            description: "Jurnal Pembalikan Retur POS No: {$posReturn->return_number}",
            lines: $lines,
            store: $store,
            userId: $posReturn->processed_by
        );
    }

    /**
     * Otomasi Jurnal Retur Pembelian ke Supplier (Purchase Return Journal).
     * Mengurangi hutang usaha supplier atau mencatat pengembalian dana, serta mengurangi persediaan dan PPN Masukan.
     */
    public function recordPurchaseReturnJournal(PurchaseReturn $purchaseReturn): JournalEntry
    {
        $company = $purchaseReturn->company;
        $store = $purchaseReturn->store;
        $lines = [];

        $subtotal = (float) $purchaseReturn->subtotal;
        $taxAmount = (float) $purchaseReturn->tax_amount;
        $grandTotal = (float) $purchaseReturn->grand_total;
        $refundType = strtoupper($purchaseReturn->refund_type ?: 'DEBT_REDUCTION');

        // 1. Pengurangan Hutang Usaha atau Penerimaan Kas Fisik (DEBIT)
        // Sesuai Phase 4.3: Hanya DEBT_REDUCTION (2001-001) dan CASH (1001-001) yang aktif
        if ($refundType === 'CASH') {
            $lines[] = [
                'account_code' => '1001-001', // Kas Toko / Kasir
                'debit' => $grandTotal,
                'credit' => 0.00,
                'memo' => "Penerimaan Kas Refund Retur Pembelian {$purchaseReturn->return_number}",
            ];
        } else {
            // Default & DEBT_REDUCTION: Pengurangan Hutang Usaha
            $lines[] = [
                'account_code' => '2001-001', // Hutang Usaha (Accounts Payable)
                'debit' => $grandTotal,
                'credit' => 0.00,
                'memo' => "Pengurangan Hutang Supplier Retur Pembelian {$purchaseReturn->return_number}",
            ];
        }

        // 2. Pengurangan Persediaan Barang Dagang (KREDIT Persediaan)
        if ($subtotal > 0) {
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $subtotal,
                'memo' => "Pengurangan Persediaan Retur Pembelian {$purchaseReturn->return_number}",
            ];
        }

        // 3. Pembalikan PPN Masukan jika ada pajak (KREDIT PPN Masukan)
        if ($taxAmount > 0) {
            $lines[] = [
                'account_code' => '1006-001', // PPN Masukan
                'debit' => 0.00,
                'credit' => $taxAmount,
                'memo' => "Pembalikan PPN Masukan Retur Pembelian {$purchaseReturn->return_number}",
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'PURCHASE_RETURN',
            referenceNumber: $purchaseReturn->return_number,
            description: "Jurnal Retur Pembelian ke Supplier No: {$purchaseReturn->return_number}",
            lines: $lines,
            store: $store,
            userId: $purchaseReturn->created_by
        );
    }

    /**
     * Otomasi Jurnal Pelunasan Hutang Supplier (Accounts Payable Settlement).
     * Debit Hutang Usaha (2001-001) dan Kredit Kas (1001-001) atau Bank (1002-001).
     */
    public function recordSupplierPaymentJournal(SupplierPayment $payment): JournalEntry
    {
        $company = $payment->company;
        $store = $payment->store;
        $totalAmount = (float) $payment->total_amount;
        $lines = [];

        // 1. Pengurangan Hutang Usaha Supplier (DEBIT Hutang Usaha)
        $lines[] = [
            'account_code' => '2001-001', // Hutang Usaha (Accounts Payable)
            'debit' => $totalAmount,
            'credit' => 0.00,
            'memo' => "Pelunasan Hutang Supplier {$payment->supplier->name} ({$payment->payment_number})",
        ];

        // 2. Pengeluaran Kas Toko atau Transfer Bank (KREDIT Kas / Bank)
        if (strtoupper($payment->payment_method) === 'CASH') {
            $lines[] = [
                'account_code' => '1001-001', // Kas Toko
                'debit' => 0.00,
                'credit' => $totalAmount,
                'memo' => "Pengeluaran Kas Tunai Pembayaran Hutang {$payment->payment_number}",
            ];
        } else {
            $bankCode = '1002-001';
            if ($payment->bankAccount) {
                $candidate = match (strtoupper($payment->bankAccount->bank_code ?? '')) {
                    'MANDIRI' => '1002-002',
                    'BRI' => '1002-003',
                    'BNI' => '1002-004',
                    default => '1002-001',
                };
                $exists = ChartOfAccount::where('company_id', $company->id)->where('code', $candidate)->exists();
                $bankCode = $exists ? $candidate : '1002-001';
            }

            $lines[] = [
                'account_code' => $bankCode,
                'debit' => 0.00,
                'credit' => $totalAmount,
                'memo' => "Pengeluaran Bank Pembayaran Hutang Supplier {$payment->payment_number}",
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'SUPPLIER_PAYMENT',
            referenceNumber: $payment->payment_number,
            description: "Jurnal Pelunasan Hutang Supplier No: {$payment->payment_number}",
            lines: $lines,
            store: $store,
            userId: $payment->created_by
        );
    }

    /**
     * Otomasi Jurnal Penyesuaian Stock Opname (Stock Reconciliation Journal).
     * Jika surplus: Debit Persediaan (1005-001), Kredit Pendapatan Selisih Stok (4002-001).
     * Jika shortage: Debit Beban Selisih Stok (6002-001), Kredit Persediaan (1005-001).
     */
    public function recordOpnameAdjustmentJournal(StockOpname $opname): ?JournalEntry
    {
        $company = $opname->company;
        $store = $opname->store;
        $discrepancyValue = (float) $opname->total_discrepancy_value;

        if (abs($discrepancyValue) < 0.01) {
            return null; // Tidak ada selisih nilai
        }

        $lines = [];

        if ($discrepancyValue > 0) {
            // Surplus fisik (Persediaan bertambah, Pendapatan selisih)
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => $discrepancyValue,
                'credit' => 0.00,
                'memo' => "Penyesuaian Surplus Stock Opname {$opname->opname_number}",
            ];
            $lines[] = [
                'account_code' => '4002-001', // Pendapatan Selisih Kas/Stok
                'debit' => 0.00,
                'credit' => $discrepancyValue,
                'memo' => "Pengakuan Pendapatan Selisih Fisik Opname {$opname->opname_number}",
            ];
        } else {
            // Shortage fisik (Beban selisih, Persediaan berkurang)
            $absLoss = abs($discrepancyValue);
            $lines[] = [
                'account_code' => '6002-001', // Beban Selisih Kas/Stok
                'debit' => $absLoss,
                'credit' => 0.00,
                'memo' => "Beban Selisih Kurang Stock Opname {$opname->opname_number}",
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $absLoss,
                'memo' => "Pengurangan Persediaan Hilang/Rusak Opname {$opname->opname_number}",
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'STOCK_OPNAME',
            referenceNumber: $opname->opname_number,
            description: "Jurnal Penyesuaian Rekonsiliasi Stock Opname No: {$opname->opname_number}",
            lines: $lines,
            store: $store,
            userId: $opname->approved_by ?? $opname->created_by
        );
    }

    /**
     * Otomasi Jurnal Penerimaan Pembayaran / Pelunasan Piutang Pelanggan (Accounts Receivable Settlement).
     * Debit: Kas Toko (1001-001) / Bank Transfer (1002-001).
     * Kredit: Piutang Usaha / Tempo (1004-001).
     */
    public function recordCustomerPaymentJournal(CustomerPayment $payment): JournalEntry
    {
        $company = $payment->company;
        $store = $payment->store;
        $totalAmount = (float) $payment->total_amount;

        $lines = [];

        // Debit: Kas Toko (1001-001) atau Bank Transfer (1002-001 / variasi)
        if ($payment->payment_method === 'TRANSFER') {
            $bankAccountCode = match (strtoupper($payment->bank_name ?? '')) {
                'MANDIRI' => '1002-002',
                'BRI' => '1002-003',
                'BNI' => '1002-004',
                default => '1002-001',
            };
            $targetAccount = ChartOfAccount::where('company_id', $company->id)->where('code', $bankAccountCode)->first();
            $accountCode = $targetAccount ? $bankAccountCode : '1002-001';

            $bankInfo = $payment->bank_name ? " [{$payment->bank_name} - {$payment->bank_account_number}]" : '';
            $refInfo = $payment->reference_number ? " (Ref: {$payment->reference_number})" : '';

            $lines[] = [
                'account_code' => $accountCode,
                'debit' => $totalAmount,
                'credit' => 0.00,
                'memo' => "Penerimaan Bank Transfer Pelunasan Piutang{$bankInfo}{$refInfo} No: {$payment->payment_number}",
            ];
        } else {
            $lines[] = [
                'account_code' => '1001-001', // Kas Toko / Kasir
                'debit' => $totalAmount,
                'credit' => 0.00,
                'memo' => "Penerimaan Kas Tunai Pelunasan Piutang No: {$payment->payment_number}",
            ];
        }

        // Kredit: Piutang Usaha (1004-001)
        $lines[] = [
            'account_code' => '1004-001', // Piutang Usaha (Tempo / Kredit)
            'debit' => 0.00,
            'credit' => $totalAmount,
            'memo' => "Pelunasan Piutang Pelanggan {$payment->customer->name} No: {$payment->payment_number}",
        ];

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'CUSTOMER_PAYMENT',
            referenceNumber: $payment->payment_number,
            description: "Jurnal Pelunasan Piutang Pelanggan No: {$payment->payment_number} ({$payment->customer->name})",
            lines: $lines,
            store: $store,
            userId: $payment->received_by
        );
    }

    /**
     * Otomasi Jurnal Perakitan / Pembongkaran Barang (Assembly / Disassembly Journal).
     * Reklasifikasi persediaan komponen menjadi persediaan barang jadi (seimbang debit-kredit akun 1005-001).
     */
    public function recordAssemblyJournal(ProductAssembly $assembly): JournalEntry
    {
        $company = $assembly->company;
        $store = $assembly->store;
        $totalCost = (float) $assembly->total_cost;
        $parentName = $assembly->parentProduct->name ?? 'Paket';

        $lines = [];

        if ($assembly->type === 'DISASSEMBLY') {
            // Pembongkaran: Debit Persediaan Komponen, Kredit Persediaan Barang Jadi
            $lines = [
                [
                    'account_code' => '1005-001',
                    'debit' => $totalCost,
                    'credit' => 0.00,
                    'memo' => "Komponen Hasil Pembongkaran {$parentName} No: {$assembly->assembly_number}",
                ],
                [
                    'account_code' => '1005-001',
                    'debit' => 0.00,
                    'credit' => $totalCost,
                    'memo' => "Pengurangan Stok Paket Jadi {$parentName} No: {$assembly->assembly_number}",
                ],
            ];
        } else {
            // Perakitan: Debit Persediaan Barang Jadi, Kredit Persediaan Komponen Bahan Baku
            $lines = [
                [
                    'account_code' => '1005-001',
                    'debit' => $totalCost,
                    'credit' => 0.00,
                    'memo' => "Persediaan Barang Jadi Paket {$parentName} No: {$assembly->assembly_number}",
                ],
                [
                    'account_code' => '1005-001',
                    'debit' => 0.00,
                    'credit' => $totalCost,
                    'memo' => "Penggunaan Komponen Bahan Baku Rakit {$parentName} No: {$assembly->assembly_number}",
                ],
            ];
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: $assembly->type,
            referenceNumber: $assembly->assembly_number,
            description: "Jurnal {$assembly->type} Paket {$parentName} No: {$assembly->assembly_number}",
            lines: $lines,
            store: $store,
            userId: $assembly->assembled_by
        );
    }

    /**
     * Catat Jurnal Penyesuaian Stok (Stock Adjustment: Rusak/Waste, Pemakaian Sendiri, Koreksi Masuk).
     */
    public function recordStockAdjustmentJournal(\App\Models\StockAdjustment $adjustment): ?JournalEntry
    {
        $company = $adjustment->company;
        $store = $adjustment->store;
        $totalCost = (float) $adjustment->total_cost_value;

        if ($totalCost < 0.01) {
            return null;
        }

        $lines = [];

        if (in_array($adjustment->adjustment_type, ['DAMAGE_WASTE', 'STOCK_OUT_CORRECTION'])) {
            // Debit: Beban Kerugian Persediaan Rusak / Waste
            // Credit: Persediaan Barang Dagang
            $lines[] = [
                'account_code' => '6002-001', // Beban Selisih/Kerugian Stok
                'debit' => $totalCost,
                'credit' => 0.00,
                'memo' => "Beban Kerusakan Persediaan: {$adjustment->adjustment_number}",
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $totalCost,
                'memo' => "Pengurangan Persediaan Rusak: {$adjustment->adjustment_number}",
            ];
        } elseif ($adjustment->adjustment_type === 'INTERNAL_USE') {
            // Debit: Beban Operasional Toko
            // Credit: Persediaan Barang Dagang
            $lines[] = [
                'account_code' => '6001-001', // Beban Operasional Umum
                'debit' => $totalCost,
                'credit' => 0.00,
                'memo' => "Beban Pemakaian Sendiri Toko: {$adjustment->adjustment_number}",
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $totalCost,
                'memo' => "Pengurangan Persediaan Pemakaian Toko: {$adjustment->adjustment_number}",
            ];
        } elseif ($adjustment->adjustment_type === 'STOCK_IN_CORRECTION') {
            // Debit: Persediaan Barang Dagang
            // Credit: Pendapatan Lain-lain / Selisih Stok
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => $totalCost,
                'credit' => 0.00,
                'memo' => "Penambahan Persediaan Koreksi: {$adjustment->adjustment_number}",
            ];
            $lines[] = [
                'account_code' => '4002-001', // Pendapatan Selisih Kas/Stok
                'debit' => 0.00,
                'credit' => $totalCost,
                'memo' => "Pengakuan Pendapatan Koreksi Masuk: {$adjustment->adjustment_number}",
            ];
        }

        if (empty($lines)) {
            return null;
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'STOCK_ADJUSTMENT',
            referenceNumber: $adjustment->adjustment_number,
            description: "Jurnal Penyesuaian Stok: {$adjustment->adjustment_number} ({$adjustment->adjustment_type})",
            lines: $lines,
            store: $store,
            entryDate: $adjustment->adjustment_date ? (\is_string($adjustment->adjustment_date) ? $adjustment->adjustment_date : $adjustment->adjustment_date->format('Y-m-d')) : now()->toDateString()
        );
    }

    /**
     * Catat Jurnal Penyelesaian Karantina Retur ke Supplier (Potong Hutang / Uang Tunai / Write-off).
     */
    public function recordQuarantineResolutionJournal(
        string $resolutionType,
        Store $store,
        string $referenceNumber,
        float $amount,
        ?\App\Models\Supplier $supplier = null,
        ?\App\Models\BankAccount $bankAccount = null
    ): ?JournalEntry {
        if ($amount < 0.01) {
            return null;
        }

        $company = $store->company;
        $lines = [];

        if ($resolutionType === 'DEBT_DEDUCTION') {
            // Supplier potong hutang faktur
            // Debit: Hutang Dagang (2001-001)
            // Credit: Persediaan Barang Dagang (1005-001)
            $lines[] = [
                'account_code' => '2001-001', // Hutang Dagang (AP)
                'debit' => $amount,
                'credit' => 0.00,
                'memo' => "Pemotongan Hutang Supplier atas Retur Karantina {$referenceNumber}",
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $amount,
                'memo' => "Kredit Persediaan atas Retur Karantina {$referenceNumber}",
            ];
        } elseif ($resolutionType === 'CASH_REFUND') {
            // Supplier bayar tunai / transfer
            $accountCode = ($bankAccount && $bankAccount->account_code) ? $bankAccount->account_code : '1001-001';
            $lines[] = [
                'account_code' => $accountCode, // Kas / Bank
                'debit' => $amount,
                'credit' => 0.00,
                'memo' => "Penerimaan Kas Pengembalian Retur Karantina {$referenceNumber}",
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $amount,
                'memo' => "Kredit Persediaan atas Retur Karantina {$referenceNumber}",
            ];
        } elseif ($resolutionType === 'WRITE_OFF') {
            // Supplier tolak / pemusnahan
            $lines[] = [
                'account_code' => '6002-001', // Beban Kerusakan Persediaan
                'debit' => $amount,
                'credit' => 0.00,
                'memo' => "Pemusnahan / Write-off Karantina {$referenceNumber}",
            ];
            $lines[] = [
                'account_code' => '1005-001', // Persediaan Barang Dagang
                'debit' => 0.00,
                'credit' => $amount,
                'memo' => "Kredit Persediaan atas Pemusnahan Karantina {$referenceNumber}",
            ];
        }

        if (empty($lines)) {
            return null;
        }

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'QUARANTINE_RESOLUTION',
            referenceNumber: $referenceNumber,
            description: "Penyelesaian Retur Karantina: {$referenceNumber} ({$resolutionType})",
            lines: $lines,
            store: $store,
            entryDate: now()->toDateString()
        );
    }


    /**
     * Catat Jurnal Akuntansi Transaksi Item Masuk & Item Keluar (Stock In / Stock Out).
     */
    public function recordStockMovementJournal(\App\Models\StockMovement $movement): ?JournalEntry
    {
        $company = $movement->company;
        $store = $movement->store;
        $totalCost = (float) $movement->total_cost_value;

        if ($totalCost < 0.01) {
            return null;
        }

        $lines = [];

        if ($movement->movement_type === 'IN') {
            $lines[] = [
                'account_code' => '1005-001',
                'debit' => $totalCost,
                'credit' => 0.00,
                'memo' => "Penerimaan Item Masuk {$movement->movement_number} ({$movement->category_reason})",
            ];
            $lines[] = [
                'account_code' => '4002-001',
                'debit' => 0.00,
                'credit' => $totalCost,
                'memo' => "Kredit Pendapatan atas Item Masuk {$movement->movement_number}",
            ];
        } else {
            $expenseAccount = match ($movement->category_reason) {
                'SPOILAGE_WASTE' => '6002-001',
                default => '6001-001',
            };

            $lines[] = [
                'account_code' => $expenseAccount,
                'debit' => $totalCost,
                'credit' => 0.00,
                'memo' => "Beban Pengeluaran Item Keluar {$movement->movement_number} ({$movement->category_reason})",
            ];
            $lines[] = [
                'account_code' => '1005-001',
                'debit' => 0.00,
                'credit' => $totalCost,
                'memo' => "Kredit Persediaan atas Pengeluaran {$movement->movement_number}",
            ];
        }

        if (empty($lines)) {
            return null;
        }

        $entryDate = $movement->movement_date ? (\is_string($movement->movement_date) ? $movement->movement_date : $movement->movement_date->format('Y-m-d')) : now()->toDateString();

        return $this->createJournalEntry(
            company: $company,
            referenceType: 'STOCK_MOVEMENT',
            referenceNumber: $movement->movement_number,
            description: "Jurnal {$movement->movement_type}: {$movement->movement_number} ({$movement->category_reason})",
            lines: $lines,
            store: $store,
            entryDate: $entryDate
        );
    }
}
