<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\CashTransaction;
use App\Models\Company;
use App\Models\CustomerPaymentItem;
use App\Models\JournalEntry;
use App\Models\PosReturn;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\StockMutation;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CleanupSigmaTrialTransactions extends Command
{
    protected $signature = 'sigma:cleanup-pt-sigma-trial-transactions
        {--execute : Apply the company-scoped cleanup. Omit for a dry run.}
        {--confirm= : Required confirmation phrase for execution}';

    protected $description = 'Dry-run or remove the three verified PT Sigma trial POS transactions with guarded stock and ledger handling.';

    private const COMPANY_NAME = 'PT SIGMA JAYA NUSANTARA';

    private const CONFIRMATION = 'HAPUS-3-TRIAL-PT-SIGMA';

    /** @var array<string, int> */
    private const INVOICES = [
        'POS-202611-0001' => 557500,
        'POS-202611-0002' => 182500,
        'POS-202611-0003' => 17500,
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        if ($execute && $this->option('confirm') !== self::CONFIRMATION) {
            $this->error('Execution refused: the exact confirmation phrase is required.');

            return self::FAILURE;
        }

        if (! $execute && $this->option('confirm')) {
            $this->error('Dry-run does not accept a confirmation phrase.');

            return self::FAILURE;
        }

        if (! app()->isProduction()) {
            $this->error('This one-time cleanup may run only against the configured production environment.');

            return self::FAILURE;
        }

        try {
            $summary = DB::transaction(function () use ($execute): array {
                DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');

                $company = Company::query()
                    ->where('name', self::COMPANY_NAME)
                    ->lockForUpdate()
                    ->firstOrFail();

                $transactions = PosTransaction::query()
                    ->where('company_id', $company->id)
                    ->orderBy('invoice_number')
                    ->lockForUpdate()
                    ->get();

                $this->assertExactTransactions($transactions);
                $transactionIds = $transactions->pluck('id')->all();
                $invoiceNumbers = array_keys(self::INVOICES);

                $this->assertNoDependentBusinessRecords($transactionIds, $company->id);

                $journals = JournalEntry::query()
                    ->where('company_id', $company->id)
                    ->where('reference_type', 'POS_SALE')
                    ->where(function ($query) use ($invoiceNumbers, $transactionIds): void {
                        $query->whereIn('reference_number', $invoiceNumbers)
                            ->orWhereIn('reference_id', $transactionIds);
                    })
                    ->lockForUpdate()
                    ->get();

                $journalIds = $journals->pluck('id')->all();
                $cashTransactions = $journalIds === []
                    ? collect()
                    : CashTransaction::query()
                        ->where('company_id', $company->id)
                        ->whereIn('journal_entry_id', $journalIds)
                        ->lockForUpdate()
                        ->get();

                $saleMutations = StockMutation::query()
                    ->where('company_id', $company->id)
                    ->whereIn('reference_number', $invoiceNumbers)
                    ->where('mutation_type', 'SALE')
                    ->lockForUpdate()
                    ->get();

                if ($saleMutations->isEmpty() || $saleMutations->contains(fn (StockMutation $mutation): bool => (float) $mutation->quantity >= 0)) {
                    throw new RuntimeException('Stock ledger does not contain only expected negative SALE mutations; no changes were made.');
                }

                $stockPlan = $this->buildAndValidateStockPlan($company->id, $transactions, $saleMutations);
                $footVouchers = $this->findFootVouchers($company->id, $transactions);

                $summary = [
                    'company' => $company->name,
                    'company_id' => $company->id,
                    'transactions' => $transactions->map(fn (PosTransaction $transaction): array => [
                        'invoice' => $transaction->invoice_number,
                        'amount' => (int) round((float) $transaction->grand_total),
                    ])->values()->all(),
                    'total' => (int) round((float) $transactions->sum('grand_total')),
                    'journal_entries' => $journals->count(),
                    'cash_transactions' => $cashTransactions->count(),
                    'sale_stock_mutations' => $saleMutations->count(),
                    'stock_restore_groups' => count($stockPlan),
                    'issued_foot_vouchers' => $footVouchers->count(),
                ];

                if (! $execute) {
                    return $summary;
                }

                $cleanupReference = 'CLEANUP-PTSIGMA-'.now()->format('Ymd-His');
                $stockMutationIds = $saleMutations->pluck('id')->all();

                foreach ($stockPlan as $group) {
                    /** @var ProductStock $stock */
                    $stock = $group['stock'];
                    /** @var Product $product */
                    $product = $group['product'];

                    $before = (float) $stock->physical_quantity;
                    $after = $before + $group['quantity'];
                    $stock->update(['physical_quantity' => $after]);

                    $product->update([
                        'total_quantity' => (float) $product->total_quantity + $group['quantity'],
                        'total_stock_value' => (float) $product->total_stock_value + $group['total_cost'],
                    ]);

                    StockMutation::create([
                        'company_id' => $company->id,
                        'store_id' => $group['store_id'],
                        'product_id' => $product->id,
                        'reference_number' => $cleanupReference,
                        'mutation_type' => 'DEMO_CLEANUP_RESTORE',
                        'quantity' => $group['quantity'],
                        'unit_cost' => $group['unit_cost'],
                        'total_cost' => $group['total_cost'],
                        'qty_before' => $before,
                        'qty_after' => $after,
                        'notes' => 'Pemulihan stok setelah penghapusan transaksi percobaan PT Sigma.',
                        'created_by' => null,
                    ]);
                }

                StockMutation::query()->whereIn('id', $stockMutationIds)->delete();

                if ($cashTransactions->isNotEmpty()) {
                    CashTransaction::query()->whereIn('id', $cashTransactions->pluck('id'))->delete();
                }

                if ($journals->isNotEmpty()) {
                    JournalEntry::query()->whereIn('id', $journalIds)->delete();
                }

                if ($footVouchers->isNotEmpty()) {
                    Voucher::query()->whereIn('id', $footVouchers->pluck('id'))->delete();
                }

                PosTransaction::query()->where('company_id', $company->id)->whereIn('id', $transactionIds)->delete();

                AuditLog::record(
                    module: 'POS',
                    action: 'TRIAL_TRANSACTIONS_CLEANED_UP',
                    description: 'Menghapus 3 transaksi POS percobaan PT Sigma dengan pemulihan stok, pembersihan jurnal, dan pembatasan company_id.',
                    oldValues: [
                        'invoice_numbers' => $invoiceNumbers,
                        'transaction_total' => $summary['total'],
                        'journal_entries' => $summary['journal_entries'],
                        'sale_stock_mutations' => $summary['sale_stock_mutations'],
                    ],
                    newValues: [
                        'deleted_transactions' => count($invoiceNumbers),
                        'stock_restore_groups' => $summary['stock_restore_groups'],
                        'cleanup_reference' => $cleanupReference,
                    ],
                    companyId: $company->id,
                );

                return $summary;
            }, 3);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Cleanup dihentikan tanpa perubahan yang ter-commit: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($execute ? 'CLEANUP COMPLETED' : 'DRY-RUN ONLY; no data was changed');
        $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function assertExactTransactions($transactions): void
    {
        if ($transactions->count() !== count(self::INVOICES)) {
            throw new RuntimeException('Company transaction count changed from the audited 3; no changes were made.');
        }

        $actualInvoices = $transactions->pluck('invoice_number')->sort()->values()->all();
        $expectedInvoices = collect(array_keys(self::INVOICES))->sort()->values()->all();
        if ($actualInvoices !== $expectedInvoices) {
            throw new RuntimeException('Invoice list differs from the audited trial set; no changes were made.');
        }

        foreach ($transactions as $transaction) {
            $expectedAmount = self::INVOICES[$transaction->invoice_number] ?? null;
            $observed = [
                'amount' => (int) round((float) $transaction->grand_total),
                'status' => $transaction->status,
                'payment_method' => $transaction->payment_method,
                'has_customer' => $transaction->customer_id !== null,
                'has_voucher' => $transaction->voucher_id !== null,
                'points_earned' => (int) $transaction->points_earned,
                'points_redeemed' => (int) $transaction->points_redeemed,
                'credit_amount' => (float) $transaction->credit_amount,
                'remaining_receivable' => (float) $transaction->remaining_receivable,
            ];
            $expected = [
                'amount' => $expectedAmount,
                'status' => 'COMPLETED',
                'payment_method' => 'TRANSFER',
                'has_customer' => false,
                'has_voucher' => false,
                'points_earned' => 0,
                'points_redeemed' => 0,
                'credit_amount' => 0.0,
                'remaining_receivable' => 0.0,
            ];

            if ($observed !== $expected) {
                throw new RuntimeException('Transaction '.$transaction->invoice_number.' differs from the audited trial profile; observed safe metadata: '.json_encode($observed, JSON_UNESCAPED_SLASHES).'. No changes were made.');
            }
        }

        if ((int) round((float) $transactions->sum('grand_total')) !== array_sum(self::INVOICES)) {
            throw new RuntimeException('Transaction total differs from the audited Rp 757.500; no changes were made.');
        }
    }

    /** @param array<int, string> $transactionIds */
    private function assertNoDependentBusinessRecords(array $transactionIds, string $companyId): void
    {
        $hasReturns = PosReturn::query()->where('company_id', $companyId)->whereIn('pos_transaction_id', $transactionIds)->exists();
        $hasPaymentAllocations = CustomerPaymentItem::query()->whereIn('pos_transaction_id', $transactionIds)->exists();
        $hasSalesOrders = SalesOrder::query()->where('company_id', $companyId)->whereIn('pos_transaction_id', $transactionIds)->exists();
        $hasLoyaltyMutations = DB::table('customer_point_mutations')->where('company_id', $companyId)->whereIn('pos_transaction_id', $transactionIds)->exists();

        if ($hasReturns || $hasPaymentAllocations || $hasSalesOrders || $hasLoyaltyMutations) {
            throw new RuntimeException('A return, customer payment, sales order, or loyalty mutation is linked; no changes were made.');
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PosTransaction>  $transactions
     * @param  \Illuminate\Support\Collection<int, StockMutation>  $mutations
     * @return array<string, array{stock: ProductStock, product: Product, store_id: string, quantity: float, total_cost: float, unit_cost: float}>
     */
    private function buildAndValidateStockPlan(string $companyId, $transactions, $mutations): array
    {
        $transactionIds = $transactions->pluck('id')->all();
        $mutationIds = $mutations->pluck('id')->all();
        $firstTransactionAt = $transactions->min('created_at');

        $productIds = $mutations->pluck('product_id')->unique()->values();
        $laterMutationsExist = StockMutation::query()
            ->where('company_id', $companyId)
            ->whereIn('product_id', $productIds)
            ->where('created_at', '>=', $firstTransactionAt)
            ->whereNotIn('id', $mutationIds)
            ->exists();

        if ($laterMutationsExist) {
            throw new RuntimeException('There are other stock movements for affected products during/after the trial period; inventory replay is required, so cleanup was stopped.');
        }

        $byProduct = $mutations->groupBy('product_id');
        foreach ($byProduct as $productId => $productMutations) {
            $product = Product::query()->where('company_id', $companyId)->whereKey($productId)->lockForUpdate()->firstOrFail();
            $costs = $productMutations->pluck('unit_cost')->map(fn ($cost): float => (float) $cost)->unique();
            if ($costs->count() !== 1 || abs((float) $product->cost_price - $costs->first()) > 0.0001) {
                throw new RuntimeException('An affected product cost changed after the trial sale; stock valuation replay is required, so cleanup was stopped.');
            }
        }

        $plan = [];
        foreach ($mutations->groupBy(fn (StockMutation $mutation): string => $mutation->store_id.'|'.$mutation->product_id) as $key => $group) {
            [$storeId, $productId] = explode('|', $key, 2);
            $stock = ProductStock::query()
                ->where('company_id', $companyId)
                ->where('store_id', $storeId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();
            $product = Product::query()->where('company_id', $companyId)->whereKey($productId)->lockForUpdate()->firstOrFail();
            $quantity = (float) $group->sum(fn (StockMutation $mutation): float => abs((float) $mutation->quantity));
            $unitCost = (float) $group->first()->unit_cost;
            $totalCost = (float) $group->sum('total_cost');

            if ($quantity <= 0 || (float) $product->total_quantity < 0) {
                throw new RuntimeException('Stock restoration plan is invalid; no changes were made.');
            }

            $plan[$key] = [
                'stock' => $stock,
                'product' => $product,
                'store_id' => $storeId,
                'quantity' => $quantity,
                'total_cost' => $totalCost,
                'unit_cost' => $unitCost,
            ];
        }

        return $plan;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PosTransaction>  $transactions
     * @return \Illuminate\Support\Collection<int, Voucher>
     */
    private function findFootVouchers(string $companyId, $transactions)
    {
        $codes = [];
        foreach ($transactions as $transaction) {
            if (preg_match_all('/\[FOOT_VOUCHER:([A-Z0-9-]+)\|/', (string) $transaction->notes, $matches)) {
                array_push($codes, ...$matches[1]);
            }
        }

        if ($codes === []) {
            return collect();
        }

        $vouchers = Voucher::query()->where('company_id', $companyId)->whereIn('code', array_unique($codes))->lockForUpdate()->get();
        if ($vouchers->count() !== count(array_unique($codes)) || $vouchers->contains(fn (Voucher $voucher): bool => $voucher->status !== 'ISSUED' || $voucher->redeemed_in_transaction_id !== null)) {
            throw new RuntimeException('A foot voucher is missing, redeemed, or changed status; no changes were made.');
        }

        return $vouchers;
    }
}
