<?php

namespace App\Services;

use App\Models\CashDrop;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\PosReturn;
use App\Models\PosReturnItem;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductBom;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Models\Store;
use App\Models\User;
use App\Models\Voucher;
use App\Services\BatchExpiryService;
use App\Services\ConsignmentService;
use App\Services\LoyaltyPointService;
use App\Services\PromoEngineService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosService
{
    public function __construct(
        protected PricingEngineService $pricingEngine,
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService,
        protected ?BatchExpiryService $batchExpiryService = null,
        protected ?LoyaltyPointService $loyaltyPointService = null,
        protected ?ConsignmentService $consignmentService = null,
        protected ?PromoEngineService $promoEngine = null,
        protected ?AutoNumberService $autoNumberService = null
    ) {
        $this->batchExpiryService = $batchExpiryService ?? app(BatchExpiryService::class);
        $this->loyaltyPointService = $loyaltyPointService ?? app(LoyaltyPointService::class);
        $this->consignmentService = $consignmentService ?? app(ConsignmentService::class);
        $this->promoEngine = $promoEngine ?? app(PromoEngineService::class);
        $this->autoNumberService = $autoNumberService ?? app(AutoNumberService::class);
    }

    /**
     * Buka Shift Kasir Baru (AGENTS.md Section 4A).
     */
    public function openShift(Company $company, Store $store, string $cashierId, float $startingCash): CashierShift
    {
        $existingShift = CashierShift::where('store_id', $store->id)
            ->where('cashier_id', $cashierId)
            ->where('status', 'OPEN')
            ->first();

        if ($existingShift) {
            return $existingShift;
        }

        if ($company && $company->cashier_require_starting_cash && $startingCash < 0) {
            throw new \InvalidArgumentException('Modal awal kasir tidak boleh bernilai negatif.');
        }

        if ($company && $company->cashier_max_starting_cash > 0 && $startingCash > (float) $company->cashier_max_starting_cash) {
            throw new \InvalidArgumentException(
                'Modal awal kasir (Rp '.number_format($startingCash, 0, ',', '.').') melebihi batas maksimal yang diizinkan (Rp '.number_format($company->cashier_max_starting_cash, 0, ',', '.').').'
            );
        }

        $shiftNumber = 'SHF-'.date('Ymd').'-'.strtoupper(Str::random(5));

        return CashierShift::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'cashier_id' => $cashierId,
            'shift_number' => $shiftNumber,
            'starting_cash' => $startingCash,
            'status' => 'OPEN',
            'opened_at' => now(),
        ]);
    }

    /**
     * Kasir Setor Uang Tunai ke Brankas di Tengah Shift (Cash Drop).
     */
    public function recordCashDrop(CashierShift $shift, float $amount, ?string $reason = null): CashDrop
    {
        if ($shift->status !== 'OPEN') {
            throw new \RuntimeException('Shift kasir sudah ditutup.');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Nominal Cash Drop harus lebih besar dari 0.');
        }

        return CashDrop::create([
            'cashier_shift_id' => $shift->id,
            'amount' => $amount,
            'reason' => $reason ?: 'Cash Drop rutin ke brankas',
            'created_at' => now(),
        ]);
    }

    /**
     * Tutup Shift Kasir (Blind Closing Rule - AGENTS.md Section 4A).
     *
     * Kasir menginput jumlah uang fisik aktual hasil hitungan fisik tanpa melihat ekspektasi sistem.
     * Backend menghitung ekspektasi kas dan mencatat selisih (Over / Short).
     */
    public function blindCloseShift(CashierShift $shift, float $actualCashCounted, ?string $notes = null): CashierShift
    {
        if ($shift->status !== 'OPEN') {
            throw new \RuntimeException('Shift ini sudah berstatus CLOSED.');
        }

        return DB::transaction(function () use ($shift, $actualCashCounted, $notes) {
            /** @var CashierShift $lockedShift */
            $lockedShift = CashierShift::where('id', $shift->id)->lockForUpdate()->firstOrFail();

            // Hitung total penerimaan kas bersih dari transaksi penjualan CASH dan porsi CASH transaksi SPLIT
            $cashTransactions = PosTransaction::where('cashier_shift_id', $lockedShift->id)
                ->whereIn('status', ['COMPLETED', 'VOID'])
                ->where(function ($q) {
                    $q->where('payment_method', 'CASH')
                      ->orWhere(function ($sq) {
                          $sq->where('payment_method', 'SPLIT')
                             ->where('split_cash_amount', '>', 0);
                      });
                })
                ->get();

            $totalCashSales = 0.0;
            foreach ($cashTransactions as $tx) {
                if ($tx->status === 'COMPLETED') {
                    if ($tx->payment_method === 'SPLIT' && (float) $tx->split_cash_amount > 0) {
                        $totalCashSales += (float) $tx->split_cash_amount;
                    } else {
                        $totalCashSales += ((float) $tx->paid_amount - (float) $tx->change_amount);
                    }
                }
            }

            // Hitung total pengeluaran uang kas untuk refund retur penjualan pada shift ini
            $totalCashRefunds = (float) PosReturn::where('cashier_shift_id', $lockedShift->id)
                ->where('refund_method', 'CASH')
                ->where('status', 'COMPLETED')
                ->sum('total_refund_amount');

            // Hitung total Cash Drop yang sudah disetor
            $totalCashDrops = (float) CashDrop::where('cashier_shift_id', $lockedShift->id)->sum('amount');

            // Ekspektasi Kas Sistem = Modal Awal + Penjualan Tunai - Refund Kas Retur - Cash Drop
            $startingCash = (float) $lockedShift->starting_cash;
            $expectedCash = $startingCash + $totalCashSales - $totalCashRefunds - $totalCashDrops;

            // Selisih Kas = Fisik Aktual - Ekspektasi Sistem (+ Over, - Short)
            $discrepancy = $actualCashCounted - $expectedCash;

            $company = $lockedShift->company ?? Company::find($lockedShift->company_id);
            $discrepancyTolerance = (float) ($company?->cashier_max_discrepancy_tolerance ?? 0.00);

            $lockedShift->update([
                'status' => 'CLOSED',
                'closed_at' => now(),
                'actual_cash_counted' => $actualCashCounted,
                'expected_cash' => $expectedCash,
                'cash_discrepancy' => $discrepancy,
                'discrepancy_resolved' => abs($discrepancy) < ($discrepancyTolerance > 0 ? $discrepancyTolerance : 0.01),
                'resolution_notes' => $notes,
            ]);

            // Catat Jurnal Selisih Kas jika ada ketidaksesuaian uang fisik vs sistem
            $this->accountingService->recordCashDiscrepancyJournal($lockedShift);

            return $lockedShift;
        });
    }

    /**
     * Eksekusi Transaksi POS & Checkout (AGENTS.md Section 3 & 4).
     *
     * 1. 3D Pricing Engine (Zero Manual Price Override).
     * 2. Single-use Voucher validation (ISSUED -> REDEEMED).
     * 3. Inventory Deduction via InventoryService (Zero Negative Inventory lock).
     * 4. Multi-payment support (CASH, QRIS, TRANSFER, VOUCHER, TEMPO/AR).
     */
    public function checkout(
        CashierShift $shift,
        CustomerTier $tier,
        array $cartItems,
        string $paymentMethod,
        float $paidAmount,
        ?string $voucherCode = null,
        ?string $notes = null,
        ?string $customerId = null,
        ?float $taxAmount = null,
        ?string $taxMode = null,
        ?string $salesPersonId = null,
        ?string $bankName = null,
        ?string $bankAccountNumber = null,
        ?string $paymentReference = null,
        ?float $creditAmount = null,
        ?string $splitPaymentMethod = null,
        ?int $redeemPoints = 0,
        ?float $roundingAmount = null,
        ?float $splitCashAmount = null,
        ?float $splitNonCashAmount = null,
        ?string $splitNonCashMethod = null
    ): PosTransaction {
        if ($shift->status !== 'OPEN') {
            throw new \RuntimeException('Tidak dapat memproses checkout: Shift kasir belum dibuka atau sudah ditutup.');
        }

        if (empty($cartItems)) {
            throw new \InvalidArgumentException('Keranjang belanja tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $shift,
            $tier,
            $cartItems,
            $paymentMethod,
            $paidAmount,
            $voucherCode,
            $notes,
            $customerId,
            $taxAmount,
            $taxMode,
            $salesPersonId,
            $bankName,
            $bankAccountNumber,
            $paymentReference,
            $creditAmount,
            $splitPaymentMethod,
            $redeemPoints,
            $roundingAmount,
            $splitCashAmount,
            $splitNonCashAmount,
            $splitNonCashMethod
        ) {
            $invoiceNumber = $this->autoNumberService->generateNextNumber(
                documentType: 'POS',
                storeCode: $shift->store?->code,
                company: $shift->company ?? Company::find($shift->company_id)
            );

            $totalItemsCount = 0;
            $subtotal = 0.0;
            $preparedItems = [];

            // 1. Proses Setiap Baris Item Keranjang Belanja
            foreach ($cartItems as $item) {
                /** @var Product $product */
                $product = Product::findOrFail($item['product_id']);

                if (
                    ! $product->is_active
                    || ! $product->is_point_of_sale
                    || in_array($product->sale_status, ['DISCONTINUED', 'BUY_ONLY'], true)
                    || $product->type === Product::TYPE_BIAYA
                ) {
                    throw new \InvalidArgumentException("Produk [{$product->name}] tidak dapat dijual di Kasir POS.");
                }

                /** @var ProductUnit $unit */
                $unit = ProductUnit::where('id', $item['product_unit_id'])
                    ->where('product_id', $product->id)
                    ->firstOrFail();

                $quantity = (float) $item['quantity'];
                if ($quantity <= 0) {
                    throw new \InvalidArgumentException("Kuantitas produk [{$product->name}] harus lebih dari 0.");
                }

                // Kalkulasi Harga Otomatis 3 Dimensi (Kasir tidak boleh override harga)
                $unitPrice = $this->pricingEngine->calculatePrice(
                    product: $product,
                    unit: $unit,
                    tier: $tier,
                    quantity: $quantity,
                    store: $shift->store
                );

                $itemSubtotal = $quantity * $unitPrice;
                $itemDiscount = (float) ($item['discount_amount'] ?? 0.00);

                // Validasi Promo Tebus Murah dari Mesin Promo (AGENTS.md Section 3)
                if (! empty($item['is_tebus_murah']) || ! empty($item['promotion_id'])) {
                    $promoId = $item['promotion_id'] ?? null;
                    $promo = $promoId ? Promotion::find($promoId) : null;
                    if ($promo && $promo->type === 'TEBUS_MURAH' && $promo->is_active) {
                        $targetSpecialTotal = (float) $promo->special_price * $quantity;
                        $itemDiscount = max(0.0, $itemSubtotal - $targetSpecialTotal);
                    }
                }

                $itemTotal = max(0, $itemSubtotal - $itemDiscount);

                // Kuantitas dasar untuk pengurangan stok fisik
                $conversionFactor = (float) $unit->conversion_factor;
                $baseQuantity = $quantity * $conversionFactor;

                // HPP berjalan & Pemotongan Stok sesuai Klasifikasi Tipe Produk
                if ($product->tracksInventory()) {
                    $currentHpp = (float) $product->cost_price;

                    // Potong Stok melalui InventoryService (terkunci dengan Zero Negative Inventory)
                    $this->inventoryService->deductStock(
                        product: $product,
                        store: $shift->store,
                        quantity: $baseQuantity,
                        mutationType: 'SALE',
                        referenceNumber: $invoiceNumber,
                        notes: "Penjualan POS Faktur: {$invoiceNumber}"
                    );
                } elseif ($product->type === Product::TYPE_RAKITAN_NON_PROSES) {
                    $boms = ProductBom::where('parent_product_id', $product->id)
                        ->with(['componentProduct', 'componentUnit'])
                        ->get();

                    if ($boms->isEmpty()) {
                        throw new \InvalidArgumentException("Paket bundling [{$product->name}] belum memiliki komposisi komponen (BOM).");
                    }

                    $bundleCostPerBaseUnit = 0.0;
                    foreach ($boms as $bom) {
                        $compProduct = $bom->componentProduct;
                        if (! $compProduct) {
                            continue;
                        }
                        $compFactor = (float) ($bom->componentUnit?->conversion_factor ?: 1.0);
                        $compBaseQtyPerBundle = (float) $bom->quantity * $compFactor;
                        $totalCompBaseQty = $baseQuantity * $compBaseQtyPerBundle;

                        if ($compProduct->tracksInventory()) {
                            $this->inventoryService->deductStock(
                                product: $compProduct,
                                store: $shift->store,
                                quantity: $totalCompBaseQty,
                                mutationType: 'SALE',
                                referenceNumber: $invoiceNumber,
                                notes: "Komponen Paket Bundling [{$product->name}] Faktur: {$invoiceNumber}"
                            );
                            $bundleCostPerBaseUnit += ($compBaseQtyPerBundle * (float) $compProduct->cost_price);
                        }
                    }

                    $currentHpp = $bundleCostPerBaseUnit > 0 ? $bundleCostPerBaseUnit : (float) $product->cost_price;
                } else {
                    // Item JASA atau NON_INVENTORY: tidak memotong stok fisik dan tidak mengurangi akun persediaan
                    $currentHpp = 0.0;
                }

                // FEFO Batch Allocation & Deduction jika produk memiliki batch expiry
                $itemBatchId = $item['batch_id'] ?? null;
                $itemBatchNumber = null;
                $itemExpiredDate = null;

                if ($product->has_batch_expiry) {
                    if ($itemBatchId) {
                        /** @var ProductBatch $specificBatch */
                        $specificBatch = ProductBatch::where('id', $itemBatchId)
                            ->where('store_id', $shift->store_id)
                            ->firstOrFail();

                        $this->batchExpiryService->deductBatchStock(
                            batch: $specificBatch,
                            quantity: $baseQuantity,
                            referenceNumber: $invoiceNumber,
                            reason: 'SALE'
                        );

                        $itemBatchNumber = $specificBatch->batch_number;
                        $itemExpiredDate = $specificBatch->expired_date;
                    } else {
                        // Otomatis Alokasi Berdasarkan Rekomendasi FEFO
                        $allocations = $this->batchExpiryService->allocateFefoBatches(
                            store: $shift->store,
                            product: $product,
                            requiredQuantity: $baseQuantity
                        );

                        foreach ($allocations as $alloc) {
                            $this->batchExpiryService->deductBatchStock(
                                batch: $alloc['batch'],
                                quantity: $alloc['quantity'],
                                referenceNumber: $invoiceNumber,
                                reason: 'SALE'
                            );
                        }

                        if (! empty($allocations)) {
                            $firstBatch = $allocations[0]['batch'];
                            $itemBatchId = $firstBatch->id;
                            $itemBatchNumber = $firstBatch->batch_number;
                            $itemExpiredDate = $firstBatch->expired_date;
                        }
                    }
                }

                // Jika produk adalah barang titipan konsinyasi, catat kuantitas laku terjual
                if ($product->is_consignment) {
                    $this->consignmentService->recordConsignmentSale(
                        product: $product,
                        store: $shift->store,
                        quantity: $baseQuantity
                    );
                }

                $subtotal += $itemTotal;
                $totalItemsCount += (int) ceil($quantity);

                $preparedItems[] = [
                    'product_id' => $product->id,
                    'product_unit_id' => $unit->id,
                    'batch_id' => $itemBatchId,
                    'batch_number' => $itemBatchNumber,
                    'expired_date' => $itemExpiredDate,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'unit_cost' => $currentHpp,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'discount_amount' => $itemDiscount,
                    'total_price' => $itemTotal,
                ];
            }

            // 2. Validasi Voucher / Gift Card (AGENTS.md Section 3)
            $voucher = null;
            $voucherDiscount = 0.0;

            if ($voucherCode) {
                /** @var Voucher $voucher */
                $voucher = Voucher::where('company_id', $shift->company_id)
                    ->where(function ($q) use ($voucherCode) {
                        $q->where('code', trim($voucherCode))
                            ->orWhere('barcode', trim($voucherCode));
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $voucher || ! $voucher->isValidForUse($subtotal)) {
                    throw new \InvalidArgumentException("Voucher [{$voucherCode}] tidak valid, sudah digunakan, atau belum memenuhi syarat belanja.");
                }

                if ($voucher->type === 'VALUE') {
                    $voucherDiscount = min($subtotal, (float) $voucher->amount);
                } elseif ($voucher->type === 'DISCOUNT_PERCENT') {
                    $voucherDiscount = round($subtotal * ((float) $voucher->amount / 100), 2);
                }
            }

            $customer = null;
            if ($customerId) {
                $customer = Customer::find($customerId);
            }

            // Validasi & Hitung Potongan Penukaran Poin Pelanggan (Loyalty Points)
            $pointsDiscount = 0.0;
            $redeemedPointsCount = 0;
            if ($customer && $redeemPoints > 0) {
                $pointsDiscount = $this->loyaltyPointService->calculateRedemptionDiscount(
                    customer: $customer,
                    pointsToRedeem: (int) $redeemPoints,
                    maxAllowedAmount: max(0.0, $subtotal - $voucherDiscount)
                );
                $redeemedPointsCount = (int) $redeemPoints;
            }

            $totalDiscounts = $voucherDiscount + $pointsDiscount;

            $tax = max(0.0, (float) ($taxAmount ?? 0.00));
            if ($taxMode === 'PPN_EXCLUDE' && $tax > 0) {
                $rawGrandTotal = max(0.0, ($subtotal - $totalDiscounts) + $tax);
            } else {
                $rawGrandTotal = max(0.0, $subtotal - $totalDiscounts);
            }

            // Hitung Pembulatan Transaksi (Rounding Engine)
            $company = $shift->company ?? Company::find($shift->company_id);
            if ($roundingAmount !== null) {
                $finalRoundingAmount = (float) $roundingAmount;
            } elseif ($company && ($company->rounding_enabled ?? false)) {
                $finalRoundingAmount = $this->calculateRounding($rawGrandTotal, $company, $paymentMethod, $splitPaymentMethod, $splitNonCashMethod);
            } else {
                $finalRoundingAmount = 0.0;
            }

            $grandTotal = max(0.0, $rawGrandTotal + $finalRoundingAmount);

            // 3. Validasi Pembayaran
            $credit = max(0.0, (float) ($creditAmount ?? 0.00));
            $changeAmount = 0.0;
            $finalSplitCash = null;
            $finalSplitNonCash = null;
            $finalSplitNonCashMethod = null;

            if ($paymentMethod === 'SPLIT') {
                $isTrueSplitCashNonCash = ($splitNonCashAmount !== null && $splitNonCashAmount > 0) ||
                                          ($splitCashAmount !== null && $splitCashAmount > 0 && !empty($splitNonCashMethod) && $credit <= 0);

                if ($isTrueSplitCashNonCash) {
                    // MODE 1: True Split Bill LUNAS (Tunai + Non-Tunai: QRIS / Bank Transfer)
                    // Tidak membutuhkan fasilitas kredit/tempo pelanggan karena transaksi lunas seketika
                    $finalSplitNonCashMethod = strtoupper($splitNonCashMethod ?: ($splitPaymentMethod ?: 'QRIS'));
                    if (! in_array($finalSplitNonCashMethod, ['QRIS', 'TRANSFER'])) {
                        $finalSplitNonCashMethod = 'QRIS';
                    }

                    $finalSplitCash = max(0.0, (float) ($splitCashAmount ?? 0.00));
                    $finalSplitNonCash = max(0.0, (float) ($splitNonCashAmount ?? max(0.0, $grandTotal - $finalSplitCash)));

                    if (($finalSplitCash + $finalSplitNonCash) < ($grandTotal - 0.01)) {
                        throw new \InvalidArgumentException(
                            'Kombinasi Split Pembayaran (Tunai: Rp '.number_format($finalSplitCash).' + Non-Tunai: Rp '.number_format($finalSplitNonCash).') kurang dari Total Belanja (Rp '.number_format($grandTotal).').'
                        );
                    }

                    // Uang tunai yang diserahkan pelanggan vs nominal split tunai yang harus dibayar
                    if ($paidAmount >= $finalSplitCash) {
                        $changeAmount = $paidAmount - $finalSplitCash;
                    } else {
                        throw new \InvalidArgumentException(
                            'Uang tunai diterima (Rp '.number_format($paidAmount).') kurang dari porsi tunai split bill (Rp '.number_format($finalSplitCash).').'
                        );
                    }

                    $credit = 0.0;
                    $paidAmount = $paidAmount + $finalSplitNonCash;
                    $splitPaymentMethod = $finalSplitNonCashMethod;
                } else {
                    // MODE 2: Split Bayar Sebagian + Sisa Tempo / Piutang
                    $splitPaymentMethod = strtoupper($splitPaymentMethod ?: 'CASH');
                    if (! in_array($splitPaymentMethod, ['CASH', 'TRANSFER', 'QRIS'])) {
                        $splitPaymentMethod = 'CASH';
                    }

                    if ($splitPaymentMethod === 'CASH') {
                        $finalSplitCash = $paidAmount;
                    } else {
                        $finalSplitNonCash = $paidAmount;
                        $finalSplitNonCashMethod = $splitPaymentMethod;
                    }

                    if (($paidAmount + $credit) < $grandTotal) {
                        throw new \InvalidArgumentException(
                            'Total pembayaran kombinasi (Bayar: Rp '.number_format($paidAmount).' + Piutang: Rp '.number_format($credit).') kurang dari total belanja (Rp '.number_format($grandTotal).').'
                        );
                    }

                    if ($credit > 0) {
                        if (! $customer) {
                            throw new \InvalidArgumentException('Pelanggan harus dipilih untuk alokasi pembayaran sebagian ke Piutang / Tempo.');
                        }
                        if ((float) $customer->credit_limit <= 0) {
                            throw new \InvalidArgumentException("Pelanggan [{$customer->name}] tidak memiliki fasilitas plafon kredit untuk Tempo.");
                        }
                        $availableCredit = (float) $customer->credit_limit - (float) $customer->current_credit_balance;
                        if ($credit > $availableCredit) {
                            throw new \InvalidArgumentException(
                                'Alokasi piutang (Rp '.number_format($credit).') melebihi sisa plafon kredit pelanggan ['.$customer->name.'] (Rp '.number_format($availableCredit).').'
                            );
                        }
                        $customer->increment('current_credit_balance', $credit);
                        $changeAmount = 0.0;
                    } else {
                        $changeAmount = max(0.0, $paidAmount - $grandTotal);
                    }
                }
            } elseif ($paymentMethod === 'TEMPO') {
                $credit = $grandTotal;
                $paidAmount = 0.0;
                $changeAmount = 0.0;
                if ($customer) {
                    if ((float) $customer->credit_limit <= 0) {
                        throw new \InvalidArgumentException("Pelanggan [{$customer->name}] tidak memiliki fasilitas plafon kredit untuk pembayaran Tempo.");
                    }

                    $availableCredit = (float) $customer->credit_limit - (float) $customer->current_credit_balance;
                    if ($grandTotal > $availableCredit) {
                        throw new \InvalidArgumentException('Total transaksi tempo (Rp '.number_format($grandTotal).') melebihi sisa plafon kredit pelanggan ['.$customer->name.'] (Rp '.number_format($availableCredit).').');
                    }

                    $customer->increment('current_credit_balance', $grandTotal);
                }
            } elseif ($paymentMethod === 'CASH') {
                if ($credit > 0) {
                    $paymentMethod = 'SPLIT';
                    $splitPaymentMethod = 'CASH';
                    $finalSplitCash = $paidAmount;
                    if (! $customer) {
                        throw new \InvalidArgumentException('Pelanggan harus dipilih untuk alokasi sisa pembayaran ke Piutang / Tempo.');
                    }
                    if ((float) $customer->credit_limit <= 0) {
                        throw new \InvalidArgumentException("Pelanggan [{$customer->name}] tidak memiliki fasilitas plafon kredit untuk Tempo.");
                    }
                    $availableCredit = (float) $customer->credit_limit - (float) $customer->current_credit_balance;
                    if ($credit > $availableCredit) {
                        throw new \InvalidArgumentException(
                            'Alokasi piutang (Rp '.number_format($credit).') melebihi sisa plafon kredit pelanggan ['.$customer->name.'] (Rp '.number_format($availableCredit).').'
                        );
                    }
                    $customer->increment('current_credit_balance', $credit);
                    $changeAmount = 0.0;
                } else {
                    if ($paidAmount < $grandTotal) {
                        throw new \InvalidArgumentException('Uang pembayaran tunai (Rp '.number_format($paidAmount).') kurang dari total belanja (Rp '.number_format($grandTotal).').');
                    }
                    $changeAmount = $paidAmount - $grandTotal;
                }
            } else {
                // QRIS, TRANSFER, VOUCHER
                $paidAmount = $grandTotal;
                $changeAmount = 0.0;
            }

            // 4. Hitung Status Piutang & Jatuh Tempo (Accounts Receivable)
            $hasReceivable = ($credit > 0);
            $paymentStatus = $hasReceivable ? 'UNPAID' : 'PAID';
            $remainingReceivable = $hasReceivable ? $credit : 0.00;
            $dueDate = null;
            if ($hasReceivable) {
                $termDays = ($customer && $customer->credit_term_days > 0) ? (int) $customer->credit_term_days : 30;
                $dueDate = now()->addDays($termDays)->toDateString();
            }

            // 4. Buat Record Transaksi POS
            $transaction = PosTransaction::create([
                'company_id' => $shift->company_id,
                'store_id' => $shift->store_id,
                'cashier_shift_id' => $shift->id,
                'customer_tier_id' => $tier->id,
                'customer_id' => $customer?->id,
                'cashier_id' => $shift->cashier_id,
                'sales_person_id' => $salesPersonId ?: $shift->cashier_id,
                'invoice_number' => $invoiceNumber,
                'total_item_count' => $totalItemsCount,
                'subtotal' => $subtotal,
                'discount_amount' => 0.00,
                'points_earned' => 0,
                'points_redeemed' => $redeemedPointsCount,
                'points_discount_amount' => $pointsDiscount,
                'voucher_id' => $voucher?->id,
                'voucher_discount' => $voucherDiscount,
                'tax_amount' => $tax,
                'tax_mode' => $taxMode ?: 'NON',
                'grand_total' => $grandTotal,
                'rounding_amount' => $finalRoundingAmount ?? 0.00,
                'payment_method' => $paymentMethod,
                'split_payment_method' => $paymentMethod === 'SPLIT' ? $splitPaymentMethod : null,
                'split_cash_amount' => $finalSplitCash !== null ? $finalSplitCash : 0.00,
                'split_non_cash_amount' => $finalSplitNonCash !== null ? $finalSplitNonCash : 0.00,
                'split_non_cash_method' => $finalSplitNonCashMethod,
                'bank_name' => ($paymentMethod === 'TRANSFER' || ($paymentMethod === 'SPLIT' && in_array($splitPaymentMethod, ['TRANSFER', $finalSplitNonCashMethod]))) ? $bankName : null,
                'bank_account_number' => ($paymentMethod === 'TRANSFER' || ($paymentMethod === 'SPLIT' && in_array($splitPaymentMethod, ['TRANSFER', $finalSplitNonCashMethod]))) ? $bankAccountNumber : null,
                'payment_reference' => $paymentReference,
                'paid_amount' => $paidAmount,
                'credit_amount' => $credit,
                'remaining_receivable' => $remainingReceivable,
                'due_date' => $dueDate,
                'payment_status' => $paymentStatus,
                'change_amount' => $changeAmount,
                'status' => 'COMPLETED',
                'notes' => $notes,
            ]);

            // 5. Simpan Item Detail Transaksi
            foreach ($preparedItems as $itemData) {
                $itemData['pos_transaction_id'] = $transaction->id;
                PosTransactionItem::create($itemData);
            }

            // 6. Tandai Voucher sebagai REDEEMED
            if ($voucher) {
                $voucher->update([
                    'status' => 'REDEEMED',
                    'redeemed_at' => now(),
                    'redeemed_in_transaction_id' => $transaction->id,
                ]);
            }

            // 6b. Eksekusi Mutasi Poin Loyalitas Pelanggan (Redeem & Earn)
            if ($customer) {
                if ($redeemedPointsCount > 0) {
                    $this->loyaltyPointService->redeemPoints($customer, $redeemedPointsCount, $transaction, $shift->cashier_id);
                }

                $earnedPoints = $this->loyaltyPointService->calculatePointsEarned($customer, $grandTotal, $cartItems);
                if ($earnedPoints > 0) {
                    $this->loyaltyPointService->awardPoints($customer, $earnedPoints, $transaction, $shift->cashier_id);
                    $transaction->update(['points_earned' => $earnedPoints]);
                }
            }

            // 6c. Otomasi Generator Voucher Belanja di Ekor Struk (Foot Voucher)
            $footVoucherTemplate = Voucher::where('company_id', $shift->company_id)
                ->where('is_printed_on_receipt', true)
                ->where('status', 'TEMPLATE')
                ->where('trigger_min_spend', '<=', $subtotal)
                ->orderByDesc('trigger_min_spend')
                ->first();

            if ($footVoucherTemplate) {
                $footCode = 'VCH-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
                $footVoucher = Voucher::create([
                    'company_id' => $shift->company_id,
                    'store_id' => $shift->store_id,
                    'code' => $footCode,
                    'title' => $footVoucherTemplate->title ?: 'Voucher Belanja Ekor Struk',
                    'type' => $footVoucherTemplate->type,
                    'amount' => $footVoucherTemplate->amount,
                    'min_spend' => $footVoucherTemplate->min_spend,
                    'trigger_min_spend' => $footVoucherTemplate->trigger_min_spend,
                    'is_printed_on_receipt' => true,
                    'status' => 'ISSUED',
                    'valid_from' => now(),
                    'valid_until' => now()->addDays(14),
                ]);

                $footInfo = "[FOOT_VOUCHER:{$footCode}|" . number_format($footVoucher->amount, 0, ',', '.') . "|{$footVoucher->valid_until->format('d/m/Y')}]";
                $transaction->update([
                    'notes' => trim(($transaction->notes ? $transaction->notes . "\n" : '') . $footInfo),
                ]);
            }

            // 7. Otomasi Double-Entry Bookkeeping Jurnal Penjualan (AGENTS.md Section 5)
            $this->accountingService->recordSalesJournal($transaction);

            return $transaction->load(['items.product', 'items.unit', 'voucher', 'customerTier', 'customer', 'salesPerson']);
        });
    }

    /**
     * Cetak Ulang / Reprint Struk POS dengan Watermark Audit (AGENTS.md Section 4B).
     */
    public function reprintReceipt(PosTransaction $transaction, CashierShift $activeShift, ?string $managerPin = null): array
    {
        // Otorisasi Cetak Ulang (AGENTS.md Section 4B):
        // Shift saat ini: Kasir dapat langsung cetak ulang.
        // Shift lampau: Memerlukan PIN Otorisasi Manajer / Owner / Store Head.
        if ($transaction->cashier_shift_id !== $activeShift->id) {
            if (empty($managerPin)) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Cetak ulang transaksi dari shift lampau memerlukan persetujuan dan PIN Manager / PIN Otorisasi Manajer.');
            }

            $valid = false;
            $managers = User::whereIn('role', ['owner', 'manager', 'store_head'])
                ->where('is_active', true)
                ->whereNotNull('manager_pin')
                ->get();

            foreach ($managers as $mgr) {
                if ($mgr->verifyManagerPin($managerPin)) {
                    $valid = true;
                    break;
                }
            }

            if (! $valid) {
                throw new \Illuminate\Auth\Access\AuthorizationException('PIN Otorisasi Manajer tidak valid untuk mencetak ulang transaksi shift lampau.');
            }
        }

        $now = now();

        $transaction->increment('reprint_count');
        $transaction->update([
            'last_reprinted_at' => $now,
        ]);

        return [
            'invoice_number' => $transaction->invoice_number,
            'is_reprint' => true,
            'watermark' => '** CETAK ULANG / REPRINT **',
            'reprint_timestamp' => $now->format('Y-m-d H:i:s'),
            'reprint_count' => $transaction->reprint_count,
            'transaction' => $transaction->fresh(['items.product', 'items.unit', 'customerTier']),
        ];
    }

    /**
     * Proses Retur Penjualan POS (Sales Return / Void / Partial Return).
     *
     * 1. Pulihkan kuantitas stok fisik melalui InventoryService (SALE_RETURN).
     * 2. Kurangi saldo piutang pelanggan jika transaksi asal menggunakan pembayaran TEMPO.
     * 3. Otomasi pencatatan jurnal pembalikan pendapatan & pemulihan HPP/persediaan.
     */
    public function processReturn(
        PosTransaction $transaction,
        array $itemsToReturn,
        string $refundMethod,
        string $reason,
        string $processedBy,
        ?string $approvedBy = null,
        ?CashierShift $currentShift = null
    ): PosReturn {
        if ($transaction->status === 'VOID') {
            throw new \RuntimeException('Transaksi ini sudah berstatus VOID / Dibatalkan.');
        }

        if (empty($itemsToReturn)) {
            throw new \InvalidArgumentException('Daftar barang yang diretur tidak boleh kosong.');
        }

        return DB::transaction(function () use (
            $transaction,
            $itemsToReturn,
            $refundMethod,
            $reason,
            $processedBy,
            $approvedBy,
            $currentShift
        ) {
            // 1. Lock transaksi asal secara atomik untuk mencegah race condition / concurrent over-return
            /** @var PosTransaction $lockedTx */
            $lockedTx = PosTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();
            if ($lockedTx->status === 'VOID') {
                throw new \RuntimeException('Transaksi ini sudah berstatus VOID / Dibatalkan.');
            }

            $returnNumber = 'RET-'.date('Ymd').'-'.strtoupper(Str::random(5));
            $subtotalReturned = 0.0;
            $preparedReturnItems = [];

            foreach ($itemsToReturn as $itemInput) {
                /** @var PosTransactionItem $txItem */
                $txItem = PosTransactionItem::where('id', $itemInput['pos_transaction_item_id'])
                    ->where('pos_transaction_id', $lockedTx->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $qty = (float) $itemInput['quantity'];
                if ($qty <= 0) {
                    throw new \InvalidArgumentException("Kuantitas retur untuk produk [{$txItem->product->name}] harus lebih besar dari 0.");
                }

                // Cek kuantitas kumulatif yang sudah pernah diretur sebelumnya untuk item ini
                $alreadyReturned = (float) PosReturnItem::where('pos_transaction_item_id', $txItem->id)
                    ->whereHas('posReturn', fn ($q) => $q->where('status', '!=', 'CANCELLED'))
                    ->sum('quantity');

                $remainingReturnable = (float) $txItem->quantity - $alreadyReturned;
                if ($qty > $remainingReturnable) {
                    throw new \InvalidArgumentException(
                        "Kuantitas retur untuk produk [{$txItem->product->name}] melebihi sisa yang dapat diretur. Sisa: {$remainingReturnable}, diminta: {$qty}."
                    );
                }

                $condition = strtoupper($itemInput['condition'] ?? ($itemInput['is_damaged'] ?? false ? 'DAMAGED' : 'GOOD'));
                $isDamaged = in_array($condition, ['DAMAGED', 'DEFECTIVE', 'RUSAK'], true);

                $conversionFactor = (float) ($txItem->unit?->conversion_factor ?? 1.0);
                $baseQty = $qty * $conversionFactor;

                $unitPrice = (float) $txItem->unit_price;
                $lineSubtotal = round($qty * $unitPrice, 2);
                $unitCost = (float) $txItem->unit_cost;

                $subtotalReturned += $lineSubtotal;

                // 1. Pulihkan Stok ke Toko melalui InventoryService sesuai klasifikasi tipe produk
                $returnedProduct = $txItem->product;
                if ($returnedProduct->type === Product::TYPE_RAKITAN_NON_PROSES) {
                    $bomRows = ProductBom::where('parent_product_id', $returnedProduct->id)
                        ->with(['componentProduct', 'componentUnit'])
                        ->get();

                    foreach ($bomRows as $bom) {
                        $compProduct = $bom->componentProduct;
                        $compUnit = $bom->componentUnit;
                        if (! $compProduct || ! $compUnit || ! $compProduct->tracksInventory()) {
                            continue;
                        }

                        $compBaseQty = round($baseQty * (float) $bom->quantity * (float) $compUnit->conversion_factor, 4);
                        if ($compBaseQty > 0) {
                            $this->inventoryService->recordSalesReturn(
                                product: $compProduct,
                                store: $lockedTx->store,
                                quantity: $compBaseQty,
                                unitCost: (float) $compProduct->cost_price,
                                referenceNumber: $returnNumber,
                                notes: "Retur Komponen Paket [{$returnedProduct->name}] dari Faktur POS {$lockedTx->invoice_number} ({$reason})",
                                userId: $processedBy,
                                isDamaged: $isDamaged
                            );
                        }
                    }
                } elseif ($returnedProduct->tracksInventory()) {
                    $this->inventoryService->recordSalesReturn(
                        product: $returnedProduct,
                        store: $lockedTx->store,
                        quantity: $baseQty,
                        unitCost: $unitCost,
                        referenceNumber: $returnNumber,
                        notes: "Retur dari Faktur POS {$lockedTx->invoice_number} ({$reason})",
                        userId: $processedBy,
                        isDamaged: $isDamaged
                    );
                }

                $preparedReturnItems[] = [
                    'pos_transaction_item_id' => $txItem->id,
                    'product_id' => $txItem->product_id,
                    'product_unit_id' => $txItem->product_unit_id,
                    'quantity' => $qty,
                    'base_quantity' => $baseQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                    'unit_cost' => $unitCost,
                ];
            }

            // Proporsi PPN jika transaksi asal dikenakan PPN
            $taxReturned = 0.0;
            if ((float) $lockedTx->subtotal > 0 && (float) $lockedTx->tax_amount > 0) {
                $taxReturned = round(($subtotalReturned / (float) $lockedTx->subtotal) * (float) $lockedTx->tax_amount, 2);
            }

            $totalRefundAmount = round($subtotalReturned + $taxReturned, 2);

            // 2. Sinkronkan Piutang Pelanggan jika pembayaran asal TEMPO atau refund_method CREDIT_REDUCTION
            if ($lockedTx->customer_id && ($refundMethod === 'CREDIT_REDUCTION' || $lockedTx->payment_method === 'TEMPO')) {
                // Potong sisa piutang pada transaksi asal tanpa mengubah grand_total historis
                $currentRemaining = (float) $lockedTx->remaining_receivable;
                $deductReceivable = min($currentRemaining, $totalRefundAmount);
                $newRemaining = max(0.0, $currentRemaining - $deductReceivable);
                $newPaymentStatus = ($newRemaining <= 0.01) ? 'PAID' : ($deductReceivable > 0 ? 'PARTIALLY_PAID' : $lockedTx->payment_status);

                $lockedTx->update([
                    'remaining_receivable' => $newRemaining,
                    'payment_status' => $newPaymentStatus,
                ]);

                // Kurangi saldo piutang berjalan pelanggan secara atomik
                $customer = Customer::where('id', $lockedTx->customer_id)->lockForUpdate()->first();
                if ($customer && $deductReceivable > 0) {
                    $currentBalance = (float) $customer->current_credit_balance;
                    $customerDeduction = min($currentBalance, $deductReceivable);
                    if ($customerDeduction > 0) {
                        $customer->decrement('current_credit_balance', $customerDeduction);
                    }
                }
            }

            // 3. Simpan Header PosReturn
            $posReturn = PosReturn::create([
                'company_id' => $lockedTx->company_id,
                'store_id' => $lockedTx->store_id,
                'pos_transaction_id' => $lockedTx->id,
                'cashier_shift_id' => $currentShift?->id ?? $lockedTx->cashier_shift_id,
                'processed_by' => $processedBy,
                'approved_by' => $approvedBy,
                'return_number' => $returnNumber,
                'return_date' => now()->toDateString(),
                'refund_method' => $refundMethod,
                'subtotal_returned' => $subtotalReturned,
                'tax_returned' => $taxReturned,
                'total_refund_amount' => $totalRefundAmount,
                'reason' => $reason,
                'status' => 'COMPLETED',
            ]);

            // 4. Simpan Detail Item
            foreach ($preparedReturnItems as $pItem) {
                $pItem['pos_return_id'] = $posReturn->id;
                PosReturnItem::create($pItem);
            }

            // 5. Update Status Transaksi Asal (VOID jika total kumulatif retur menutup seluruh nilai belanja)
            $totalCumulativeReturned = (float) PosReturn::where('pos_transaction_id', $lockedTx->id)
                ->where('status', 'COMPLETED')
                ->sum('subtotal_returned');

            if ($totalCumulativeReturned >= (float) $lockedTx->subtotal) {
                $lockedTx->update(['status' => 'VOID']);
            }

            // 6. Catat Jurnal Pembalikan Akuntansi
            $this->accountingService->recordSalesReturnJournal($posReturn);

            return $posReturn->load(['items.product', 'items.unit']);
        });
    }

    /**
     * Resolusi Selisih Kas Shift Kasir oleh Manajer (AGENTS.md Section 4A).
     */
    public function resolveShiftDiscrepancy(CashierShift $shift, User $manager, string $resolutionNotes): CashierShift
    {
        if (! in_array($manager->role, ['owner', 'manager', 'store_head'], true)) {
            throw new AuthorizationException('Hanya Manajer atau Pemilik yang memiliki wewenang menyelesaikan selisih kas shift.');
        }

        $shift->update([
            'discrepancy_resolved' => true,
            'resolved_by' => $manager->id,
            'resolution_notes' => $resolutionNotes,
        ]);

        return $shift->fresh();
    }

    /**
     * Hitung Nominal Selisih Pembulatan Transaksi (Rounding Engine).
     *
     * Mendukung:
     * - Kelipatan: 50, 100, 500, 1000
     * - Mode: ROUND_NEAREST (Terdekat), ROUND_UP (Ke Atas), ROUND_DOWN (Ke Bawah)
     * - Cakupan: CASH_ONLY (Hanya Tunai & Split Tunai), ALL (Semua Metode Pembayaran)
     */
    public function calculateRounding(
        float $amount,
        Company $company,
        string $paymentMethod,
        ?string $splitPaymentMethod = null,
        ?string $splitNonCashMethod = null
    ): float {
        if (! ($company->rounding_enabled ?? false)) {
            return 0.0;
        }

        $step = (int) ($company->rounding_step ?? 100);
        if ($step <= 0) {
            return 0.0;
        }

        $appliesTo = $company->rounding_applies_to ?? 'CASH_ONLY';
        $isCashTransaction = ($paymentMethod === 'CASH') ||
            ($paymentMethod === 'SPLIT' && ($splitPaymentMethod === 'CASH' || ! empty($splitNonCashMethod)));

        if ($appliesTo === 'CASH_ONLY' && ! $isCashTransaction) {
            return 0.0;
        }

        $mode = $company->rounding_mode ?? 'ROUND_NEAREST';

        $remainder = fmod($amount, $step);
        if (abs($remainder) < 0.001) {
            return 0.0;
        }

        $rounded = match ($mode) {
            'ROUND_UP' => ceil($amount / $step) * $step,
            'ROUND_DOWN' => floor($amount / $step) * $step,
            default => round($amount / $step) * $step,
        };

        return round($rounded - $amount, 2);
    }
}
