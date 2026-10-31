<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\JournalEntryLine;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Helper Universal Penyelesai Rentang Tanggal Laporan.
     * Mendukung: harian, bulanan, kuartal (Q1-Q4), tahunan, dan rentang tanggal kustom.
     */
    public static function resolvePeriodDates(Request $request): array
    {
        $periodType = $request->input('period_type', 'monthly');
        $now = Carbon::now();

        switch ($periodType) {
            case 'daily':
                $dailyDate = $request->input('daily_date', $now->toDateString());
                $startDate = $dailyDate;
                $endDate = $dailyDate;
                $label = 'Harian (' . Carbon::parse($dailyDate)->format('d/m/Y') . ')';
                break;

            case 'monthly':
                $year = (int) $request->input('monthly_year', $now->year);
                $month = (int) $request->input('monthly_month', $now->month);
                $dt = Carbon::createFromDate($year, $month, 1);
                $startDate = $dt->copy()->startOfMonth()->toDateString();
                $endDate = $dt->copy()->endOfMonth()->toDateString();
                $label = 'Bulan ' . $dt->format('F Y');
                break;

            case 'quarterly':
                $qYear = (int) $request->input('quarter_year', $now->year);
                $quarter = $request->input('quarter_val', 'Q' . $now->quarter);
                $qMonths = match ($quarter) {
                    'Q1' => [1, 3],
                    'Q2' => [4, 6],
                    'Q3' => [7, 9],
                    'Q4' => [10, 12],
                    default => [1, 3],
                };
                $startDate = Carbon::createFromDate($qYear, $qMonths[0], 1)->startOfMonth()->toDateString();
                $endDate = Carbon::createFromDate($qYear, $qMonths[1], 1)->endOfMonth()->toDateString();
                $label = "Kuartal {$quarter} {$qYear} (" . Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y') . ')';
                break;

            case 'yearly':
                $yYear = (int) $request->input('yearly_year', $now->year);
                $startDate = Carbon::createFromDate($yYear, 1, 1)->startOfYear()->toDateString();
                $endDate = Carbon::createFromDate($yYear, 12, 31)->endOfYear()->toDateString();
                $label = "Tahun {$yYear}";
                break;

            case 'custom':
            default:
                $startDate = $request->input('start_date', $now->copy()->startOfMonth()->toDateString());
                $endDate = $request->input('end_date', $now->toDateString());
                $label = Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . Carbon::parse($endDate)->format('d/m/Y');
                break;
        }

        return [
            'period_type' => $periodType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label,
            'daily_date' => $request->input('daily_date', $now->toDateString()),
            'monthly_month' => (int) $request->input('monthly_month', $now->month),
            'monthly_year' => (int) $request->input('monthly_year', $now->year),
            'quarter_val' => $request->input('quarter_val', 'Q' . $now->quarter),
            'quarter_year' => (int) $request->input('quarter_year', $now->year),
            'yearly_year' => (int) $request->input('yearly_year', $now->year),
        ];
    }
    /**
     * 1. Laporan Laba Rugi Komprehensif (Income Statement / P&L).
     *
     * Pendapatan (Revenues)
     * - Beban Pokok Penjualan (HPP Moving Average)
     * = Laba Kotor (Gross Profit)
     * - Beban Operasional (Operating Expenses)
     * = Laba Bersih (Net Profit / Loss)
     */
    public function getIncomeStatement(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        // Query baris jurnal untuk akun REVENUE dan EXPENSE
        $linesQuery = JournalEntryLine::whereHas('entry', function ($q) use ($company, $startDate, $endDate, $store) {
            $q->where('company_id', $company->id)
                ->whereDate('entry_date', '>=', $startDate)
                ->whereDate('entry_date', '<=', $endDate);
            if ($store) {
                $q->where('store_id', $store->id);
            }
        })->with('account');

        $lines = $linesQuery->get();

        $revenues = [];
        $totalRevenue = 0.0;

        $cogs = 0.0;

        $operatingExpenses = [];
        $totalOperatingExpenses = 0.0;

        // Ambil semua akun Revenue dan Expense untuk memastikan semua akun tampil meskipun bernilai 0
        $coaList = ChartOfAccount::where('company_id', $company->id)
            ->whereIn('type', ['REVENUE', 'EXPENSE'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        foreach ($coaList as $acc) {
            $accLines = $lines->where('account_id', $acc->id);
            $totalDebit = (float) $accLines->sum('debit');
            $totalCredit = (float) $accLines->sum('credit');

            if ($acc->type === 'REVENUE') {
                $amount = $totalCredit - $totalDebit;
                $revenues[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'amount' => $amount,
                ];
                $totalRevenue += $amount;
            } elseif ($acc->type === 'EXPENSE') {
                $amount = $totalDebit - $totalCredit;
                if ($acc->code === '5001-001') {
                    $cogs += $amount;
                } else {
                    $operatingExpenses[] = [
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'amount' => $amount,
                    ];
                    $totalOperatingExpenses += $amount;
                }
            }
        }

        $grossProfit = $totalRevenue - $cogs;
        $netProfit = $grossProfit - $totalOperatingExpenses;
        $grossProfitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0.0;
        $netProfitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0.0;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store' => $store,
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $grossProfitMargin,
            'operating_expenses' => $operatingExpenses,
            'total_operating_expenses' => $totalOperatingExpenses,
            'net_profit' => $netProfit,
            'net_profit_margin' => $netProfitMargin,
        ];
    }

    /**
     * 2. Laporan Neraca Keuangan (Balance Sheet).
     *
     * Persamaan Akuntansi: Total Aset = Total Liabilitas + Total Ekuitas
     */
    public function getBalanceSheet(
        Company $company,
        string $asOfDate,
        ?Store $store = null
    ): array {
        // Ambil saldo seluruh akun per tanggal asOfDate
        $lines = JournalEntryLine::whereHas('entry', function ($q) use ($company, $asOfDate, $store) {
            $q->where('company_id', $company->id)
                ->whereDate('entry_date', '<=', $asOfDate);
            if ($store) {
                $q->where('store_id', $store->id);
            }
        })->with('account')->get();

        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $currentAssets = [];
        $fixedAssets = [];
        $totalAssets = 0.0;

        $liabilities = [];
        $totalLiabilities = 0.0;

        $equity = [];
        $totalEquityWithoutNetIncome = 0.0;

        $totalRevenueToDate = 0.0;
        $totalExpenseToDate = 0.0;

        foreach ($accounts as $acc) {
            $accLines = $lines->where('account_id', $acc->id);
            $totalDebit = (float) $accLines->sum('debit');
            $totalCredit = (float) $accLines->sum('credit');

            if ($acc->type === 'ASSET') {
                if ($acc->code === '1502-001') {
                    // Akumulasi penyusutan (kontra aset, normal kredit)
                    $balance = -($totalCredit - $totalDebit);
                    $fixedAssets[] = [
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'amount' => $balance,
                    ];
                    $totalAssets += $balance;
                } elseif (str_starts_with($acc->code, '15')) {
                    // Aset Tetap
                    $balance = $totalDebit - $totalCredit;
                    $fixedAssets[] = [
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'amount' => $balance,
                    ];
                    $totalAssets += $balance;
                } else {
                    // Aset Lancar (Kas, Bank, Piutang, Persediaan, PPN Masukan)
                    $balance = $totalDebit - $totalCredit;
                    $currentAssets[] = [
                        'code' => $acc->code,
                        'name' => $acc->name,
                        'amount' => $balance,
                    ];
                    $totalAssets += $balance;
                }
            } elseif ($acc->type === 'LIABILITY') {
                $balance = $totalCredit - $totalDebit;
                $liabilities[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'amount' => $balance,
                ];
                $totalLiabilities += $balance;
            } elseif ($acc->type === 'EQUITY') {
                $balance = $totalCredit - $totalDebit;
                $equity[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'amount' => $balance,
                ];
                $totalEquityWithoutNetIncome += $balance;
            } elseif ($acc->type === 'REVENUE') {
                $totalRevenueToDate += ($totalCredit - $totalDebit);
            } elseif ($acc->type === 'EXPENSE') {
                $totalExpenseToDate += ($totalDebit - $totalCredit);
            }
        }

        // Laba Bersih Berjalan sampai tanggal asOfDate
        $currentEarnings = $totalRevenueToDate - $totalExpenseToDate;
        $totalEquity = $totalEquityWithoutNetIncome + $currentEarnings;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.05;

        return [
            'as_of_date' => $asOfDate,
            'store' => $store,
            'current_assets' => $currentAssets,
            'fixed_assets' => $fixedAssets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'current_earnings' => $currentEarnings,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * 3. Neraca Saldo (Trial Balance).
     * Membuktikan keseimbangan seluruh mutasi debit dan kredit akun.
     */
    public function getTrialBalance(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $rows = [];
        $grandDebit = 0.0;
        $grandCredit = 0.0;

        foreach ($accounts as $acc) {
            // Mutasi dalam periode
            $periodLines = JournalEntryLine::where('account_id', $acc->id)
                ->whereHas('entry', function ($q) use ($company, $startDate, $endDate, $store) {
                    $q->where('company_id', $company->id)
                        ->whereDate('entry_date', '>=', $startDate)
                        ->whereDate('entry_date', '<=', $endDate);
                    if ($store) {
                        $q->where('store_id', $store->id);
                    }
                })->get();

            $debit = (float) $periodLines->sum('debit');
            $credit = (float) $periodLines->sum('credit');

            $grandDebit += $debit;
            $grandCredit += $credit;

            $rows[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'type' => $acc->type,
                'normal_balance' => $acc->normal_balance,
                'debit' => $debit,
                'credit' => $credit,
                'net_balance' => ($acc->normal_balance === 'DEBIT') ? ($debit - $credit) : ($credit - $debit),
            ];
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store' => $store,
            'rows' => $rows,
            'total_debit' => $grandDebit,
            'total_credit' => $grandCredit,
            'is_balanced' => abs($grandDebit - $grandCredit) < 0.05,
        ];
    }

    /**
     * 4. Buku Besar Akun (General Ledger Explorer).
     * Rincian kronologis mutasi akun dengan saldo berjalan.
     */
    public function getGeneralLedger(
        ChartOfAccount $account,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        // Saldo Awal (sebelum startDate)
        $priorLines = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('entry', function ($q) use ($account, $startDate, $store) {
                $q->where('company_id', $account->company_id)
                    ->whereDate('entry_date', '<', $startDate);
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            })->get();

        $priorDebit = (float) $priorLines->sum('debit');
        $priorCredit = (float) $priorLines->sum('credit');

        $startingBalance = ($account->normal_balance === 'DEBIT')
            ? ($priorDebit - $priorCredit)
            : ($priorCredit - $priorDebit);

        // Mutasi Periode
        $lines = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('entry', function ($q) use ($account, $startDate, $endDate, $store) {
                $q->where('company_id', $account->company_id)
                    ->whereDate('entry_date', '>=', $startDate)
                    ->whereDate('entry_date', '<=', $endDate);
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            })
            ->with('entry')
            ->get()
            ->sortBy(fn ($l) => $l->entry->entry_date->toDateString().' '.$l->entry->created_at);

        $runningBalance = $startingBalance;
        $ledgerEntries = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($account->normal_balance === 'DEBIT') {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $ledgerEntries[] = [
                'date' => $line->entry->entry_date,
                'entry_number' => $line->entry->entry_number,
                'reference_type' => $line->entry->reference_type,
                'reference_number' => $line->entry->reference_number,
                'memo' => $line->memo ?: $line->entry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        }

        return [
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store' => $store,
            'starting_balance' => $startingBalance,
            'entries' => $ledgerEntries,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'ending_balance' => $runningBalance,
        ];
    }

    /**
     * 5. Laporan Arus Kas (Cash Flow Statement).
     * Melacak arus kas masuk dan keluar dari seluruh rekening kas dan bank toko.
     */
    public function getCashFlow(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null
    ): array {
        // Akun Kas dan Bank (1001-xxx, 1002-xxx, 1003-xxx)
        $cashAccounts = ChartOfAccount::where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('code', 'like', '1001%')
                    ->orWhere('code', 'like', '1002%')
                    ->orWhere('code', 'like', '1003%');
            })
            ->where('is_active', true)
            ->pluck('id');

        // Saldo Awal Kas & Bank
        $priorLines = JournalEntryLine::whereIn('account_id', $cashAccounts)
            ->whereHas('entry', function ($q) use ($company, $startDate, $store) {
                $q->where('company_id', $company->id)
                    ->whereDate('entry_date', '<', $startDate);
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            })->get();

        $startingCash = (float) $priorLines->sum('debit') - (float) $priorLines->sum('credit');

        // Mutasi Kas Periode Berjalan
        $periodLines = JournalEntryLine::whereIn('account_id', $cashAccounts)
            ->whereHas('entry', function ($q) use ($company, $startDate, $endDate, $store) {
                $q->where('company_id', $company->id)
                    ->whereDate('entry_date', '>=', $startDate)
                    ->whereDate('entry_date', '<=', $endDate);
                if ($store) {
                    $q->where('store_id', $store->id);
                }
            })
            ->with('entry')
            ->get();

        $inflows = [];
        $outflows = [];
        $totalInflow = 0.0;
        $totalOutflow = 0.0;

        foreach ($periodLines as $line) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $refType = $line->entry->reference_type ?? 'OTHER';

            if ($debit > 0) {
                $totalInflow += $debit;
                $inflows[] = [
                    'date' => $line->entry->entry_date,
                    'reference' => $line->entry->reference_number,
                    'type' => $refType,
                    'description' => $line->memo ?: $line->entry->description,
                    'amount' => $debit,
                ];
            }

            if ($credit > 0) {
                $totalOutflow += $credit;
                $outflows[] = [
                    'date' => $line->entry->entry_date,
                    'reference' => $line->entry->reference_number,
                    'type' => $refType,
                    'description' => $line->memo ?: $line->entry->description,
                    'amount' => $credit,
                ];
            }
        }

        $netCashFlow = $totalInflow - $totalOutflow;
        $endingCash = $startingCash + $netCashFlow;

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store' => $store,
            'starting_cash' => $startingCash,
            'inflows' => $inflows,
            'total_inflow' => $totalInflow,
            'outflows' => $outflows,
            'total_outflow' => $totalOutflow,
            'net_cash_flow' => $netCashFlow,
            'ending_cash' => $endingCash,
        ];
    }

    /**
     * 6. Laporan Analisa Penjualan Ritel & Grosir Terperinci Multi-Dimensi.
     */
    public function getSalesReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null,
        ?string $cashierId = null,
        ?string $paymentMethod = null
    ): array {
        $query = \App\Models\PosTransaction::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->where('status', '!=', 'VOID');

        if ($store) {
            $query->where('store_id', $store->id);
        }

        if ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        $transactions = $query->with([
            'cashier',
            'customer.customerTier',
            'store',
            'items.product.category',
            'items.product.supplier',
            'items.product.baseUnit',
        ])->get();

        $totalTransactions = $transactions->count();
        $totalGrossSales = (float) $transactions->sum('subtotal');
        $totalDiscount = (float) $transactions->sum('discount_amount') + (float) $transactions->sum('voucher_discount');
        $totalTax = (float) $transactions->sum('tax_amount');
        $totalNetSales = (float) $transactions->sum('grand_total');
        $avgTransaction = $totalTransactions > 0 ? round($totalNetSales / $totalTransactions, 2) : 0.0;

        $totalCogs = 0.0;
        $itemStats = [];
        $supplierStats = [];

        foreach ($transactions as $tx) {
            foreach ($tx->items as $item) {
                $pid = $item->product_id;
                $product = $item->product;
                $qty = (float) $item->quantity;
                $subtotal = (float) $item->subtotal;
                $unitCost = (float) ($item->cost_price ?? ($product?->cost_price ?? 0.0));
                $cogs = round($unitCost * $qty, 2);
                $totalCogs += $cogs;

                // 1. By Item
                if (!isset($itemStats[$pid])) {
                    $itemStats[$pid] = [
                        'product_id' => $pid,
                        'product_name' => $product?->name ?? 'Produk',
                        'sku' => $product?->sku ?? '-',
                        'category' => $product?->getRelationValue('category')?->name ?? (is_string($product?->category) ? $product->category : 'Umum'),
                        'supplier_name' => $product?->supplier?->name ?? 'Supplier Umum',
                        'base_unit' => $product?->baseUnit?->unit_name ?? 'Pcs',
                        'qty_sold' => 0.0,
                        'total_sales' => 0.0,
                        'total_cogs' => 0.0,
                    ];
                }
                $itemStats[$pid]['qty_sold'] += $qty;
                $itemStats[$pid]['total_sales'] += $subtotal;
                $itemStats[$pid]['total_cogs'] += $cogs;

                // 2. By Supplier (Pemasok asal barang)
                $supId = $product?->supplier_id ?? 'unknown';
                $supName = $product?->supplier?->name ?? 'Supplier Umum / Tanpa Pemasok';
                if (!isset($supplierStats[$supId])) {
                    $supplierStats[$supId] = [
                        'supplier_id' => $supId,
                        'supplier_name' => $supName,
                        'item_count' => 0,
                        'qty_sold' => 0.0,
                        'total_sales' => 0.0,
                        'total_cogs' => 0.0,
                    ];
                }
                $supplierStats[$supId]['qty_sold'] += $qty;
                $supplierStats[$supId]['total_sales'] += $subtotal;
                $supplierStats[$supId]['total_cogs'] += $cogs;
            }
        }

        $totalGrossProfit = round($totalNetSales - $totalCogs, 2);
        $profitMargin = $totalNetSales > 0 ? round(($totalGrossProfit / $totalNetSales) * 100, 2) : 0.0;

        // Finalize by_item
        $byItem = collect($itemStats)->map(function ($item) {
            $profit = round($item['total_sales'] - $item['total_cogs'], 2);
            $margin = $item['total_sales'] > 0 ? round(($profit / $item['total_sales']) * 100, 2) : 0.0;
            $item['gross_profit'] = $profit;
            $item['profit_margin'] = $margin;
            return $item;
        })->sortByDesc('total_sales')->values();

        // Finalize by_category
        $byCategory = collect($itemStats)->groupBy('category')->map(function ($items, $category) use ($totalGrossSales) {
            $qty = (float) $items->sum('qty_sold');
            $sales = (float) $items->sum('total_sales');
            $cogs = (float) $items->sum('total_cogs');
            $profit = round($sales - $cogs, 2);
            $margin = $sales > 0 ? round(($profit / $sales) * 100, 2) : 0.0;
            $share = $totalGrossSales > 0 ? round(($sales / $totalGrossSales) * 100, 2) : 0.0;
            return [
                'category' => $category ?: 'Umum',
                'item_count' => $items->count(),
                'qty_sold' => $qty,
                'total_sales' => $sales,
                'total_cogs' => $cogs,
                'gross_profit' => $profit,
                'profit_margin' => $margin,
                'sales_share_percent' => $share,
            ];
        })->sortByDesc('total_sales')->values();

        // Finalize by_supplier
        $bySupplier = collect($supplierStats)->map(function ($sup) {
            $profit = round($sup['total_sales'] - $sup['total_cogs'], 2);
            $sup['gross_profit'] = $profit;
            $sup['profit_margin'] = $sup['total_sales'] > 0 ? round(($profit / $sup['total_sales']) * 100, 2) : 0.0;
            return $sup;
        })->sortByDesc('total_sales')->values();

        // Finalize by_cashier
        $byCashier = $transactions->groupBy('cashier_id')->map(function ($group) {
            $cashier = $group->first()->cashier;
            $sales = (float) $group->sum('grand_total');
            $count = $group->count();
            return [
                'cashier_id' => $cashier?->id,
                'cashier_name' => $cashier?->name ?? 'Kasir Default',
                'transaction_count' => $count,
                'total_sales' => $sales,
                'avg_per_tx' => $count > 0 ? round($sales / $count, 2) : 0.0,
            ];
        })->sortByDesc('total_sales')->values();

        // Finalize by_customer
        $byCustomer = $transactions->groupBy('customer_id')->map(function ($group) {
            $cust = $group->first()->customer;
            $sales = (float) $group->sum('grand_total');
            return [
                'customer_id' => $cust?->id,
                'customer_code' => $cust?->code ?? '-',
                'customer_name' => $cust?->name ?? 'Pelanggan Umum / Non-Member',
                'tier_name' => $cust?->customerTier?->name ?? 'Retail',
                'transaction_count' => $group->count(),
                'total_sales' => $sales,
                'current_credit_balance' => (float) ($cust?->current_credit_balance ?? 0.0),
            ];
        })->sortByDesc('total_sales')->values();

        // Finalize by_store (Benchmark Antar Cabang)
        $byStore = $transactions->groupBy('store_id')->map(function ($group) {
            $storeObj = $group->first()->store;
            $sales = (float) $group->sum('grand_total');
            $count = $group->count();
            return [
                'store_id' => $storeObj?->id,
                'store_code' => $storeObj?->code ?? '-',
                'store_name' => $storeObj?->name ?? 'Cabang Utama',
                'transaction_count' => $count,
                'total_sales' => $sales,
                'avg_per_tx' => $count > 0 ? round($sales / $count, 2) : 0.0,
            ];
        })->sortByDesc('total_sales')->values();

        // Breakdown per Metode Pembayaran
        $byPaymentMethod = $transactions->groupBy('payment_method')->map(function ($group, $method) {
            return [
                'method' => $method ?: 'CASH',
                'count' => $group->count(),
                'total_amount' => (float) $group->sum('grand_total'),
            ];
        })->sortByDesc('total_amount')->values();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store' => $store,
            'total_transactions' => $totalTransactions,
            'total_gross_sales' => $totalGrossSales,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_net_sales' => $totalNetSales,
            'total_cogs' => $totalCogs,
            'total_gross_profit' => $totalGrossProfit,
            'profit_margin' => $profitMargin,
            'avg_transaction' => $avgTransaction,
            'by_item' => $byItem,
            'by_category' => $byCategory,
            'by_cashier' => $byCashier,
            'by_customer' => $byCustomer,
            'by_supplier' => $bySupplier,
            'by_store' => $byStore,
            'by_payment_method' => $byPaymentMethod,
            'top_products' => $byItem->take(20),
        ];
    }

    /**
     * 7. Laporan Analisa Pembelian & Hutang Dagang Supplier Multi-Dimensi.
     */
    public function getPurchasesReport(
        Company $company,
        string $startDate,
        string $endDate,
        ?Store $store = null,
        ?string $supplierId = null
    ): array {
        $query = \App\Models\Purchase::where('company_id', $company->id)
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->where('status', '!=', 'CANCELLED');

        if ($store) {
            $query->where('store_id', $store->id);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $purchases = $query->with([
            'supplier',
            'store',
            'items.product.category',
            'items.product.baseUnit',
        ])->get();

        $totalPurchases = $purchases->count();
        $totalSubtotal = (float) $purchases->sum('subtotal');
        $totalDiscount = (float) $purchases->sum('discount_amount');
        $totalTax = (float) $purchases->sum('tax_amount');
        $totalShipping = (float) $purchases->sum('shipping_cost');
        $totalAmount = (float) $purchases->sum('grand_total');
        $totalPaid = (float) $purchases->sum('paid_amount');
        $totalUnpaid = max(0.0, $totalAmount - $totalPaid);

        // Breakdown per Supplier
        $bySupplier = $purchases->groupBy('supplier_id')->map(function ($group) {
            $supplier = $group->first()->supplier;
            $grand = (float) $group->sum('grand_total');
            $paid = (float) $group->sum('paid_amount');
            return [
                'supplier_id' => $supplier?->id,
                'supplier_code' => $supplier?->code ?? '-',
                'supplier_name' => $supplier?->name ?? 'Supplier Umum',
                'supplier_phone' => $supplier?->phone ?? '-',
                'supplier_npwp' => $supplier?->npwp ?? '-',
                'is_pkp' => (bool) ($supplier?->is_pkp ?? false),
                'invoice_count' => $group->count(),
                'total_amount' => $grand,
                'total_paid' => $paid,
                'total_unpaid' => max(0.0, $grand - $paid),
            ];
        })->sortByDesc('total_amount')->values();

        // Breakdown per Produk yang Dibeli
        $productStats = [];
        foreach ($purchases as $po) {
            foreach ($po->items as $item) {
                $pid = $item->product_id;
                $product = $item->product;
                if (!isset($productStats[$pid])) {
                    $productStats[$pid] = [
                        'product_id' => $pid,
                        'product_name' => $product?->name ?? 'Produk',
                        'sku' => $product?->sku ?? '-',
                        'category' => $product?->getRelationValue('category')?->name ?? (is_string($product?->category) ? $product->category : 'Umum'),
                        'base_unit' => $product?->baseUnit?->unit_name ?? 'Pcs',
                        'qty_purchased' => 0.0,
                        'total_amount' => 0.0,
                    ];
                }
                $productStats[$pid]['qty_purchased'] += (float) $item->quantity;
                $productStats[$pid]['total_amount'] += (float) $item->subtotal;
            }
        }

        $byItem = collect($productStats)->map(function ($p) {
            $qty = $p['qty_purchased'];
            $p['avg_unit_cost'] = $qty > 0 ? round($p['total_amount'] / $qty, 2) : 0.0;
            return $p;
        })->sortByDesc('total_amount')->values();

        // Breakdown per Jenis / Kategori
        $byCategory = collect($productStats)->groupBy('category')->map(function ($items, $category) use ($totalSubtotal) {
            $amount = (float) $items->sum('total_amount');
            $qty = (float) $items->sum('qty_purchased');
            $share = $totalSubtotal > 0 ? round(($amount / $totalSubtotal) * 100, 2) : 0.0;
            return [
                'category' => $category ?: 'Umum',
                'item_count' => $items->count(),
                'qty_purchased' => $qty,
                'total_amount' => $amount,
                'share_percent' => $share,
            ];
        })->sortByDesc('total_amount')->values();

        // Breakdown per Cabang / Toko
        $byStore = $purchases->groupBy('store_id')->map(function ($group) {
            $storeObj = $group->first()->store;
            $grand = (float) $group->sum('grand_total');
            $paid = (float) $group->sum('paid_amount');
            return [
                'store_id' => $storeObj?->id,
                'store_code' => $storeObj?->code ?? '-',
                'store_name' => $storeObj?->name ?? 'Cabang Utama',
                'invoice_count' => $group->count(),
                'total_amount' => $grand,
                'total_paid' => $paid,
                'total_unpaid' => max(0.0, $grand - $paid),
            ];
        })->sortByDesc('total_amount')->values();

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'store' => $store,
            'total_purchases' => $totalPurchases,
            'total_subtotal' => $totalSubtotal,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_shipping' => $totalShipping,
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'by_item' => $byItem,
            'by_category' => $byCategory,
            'by_supplier' => $bySupplier,
            'by_store' => $byStore,
            'top_purchased_products' => $byItem->take(20),
        ];
    }

    /**
     * 8. Laporan Analisa Umur Piutang Pelanggan (Aging Accounts Receivable).
     */
    public function getAgingReceivables(Company $company, ?Store $store = null): array
    {
        $query = \App\Models\PosTransaction::where('company_id', $company->id)
            ->whereIn('payment_status', ['UNPAID', 'PARTIALLY_PAID'])
            ->where('remaining_receivable', '>', 0)
            ->where('status', '!=', 'VOID');

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $invoices = $query->with('customer')->get();
        $now = now()->startOfDay();

        $agingBuckets = [
            'current' => 0.0, // Belum jatuh tempo
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_over_90' => 0.0,
        ];

        $customerGroups = [];

        foreach ($invoices as $inv) {
            $cid = $inv->customer_id;
            $customerName = $inv->customer?->name ?? 'Pelanggan Umum';
            $customerPhone = $inv->customer?->phone ?? '-';
            $creditLimit = (float) ($inv->customer?->credit_limit ?? 0);
            $remaining = (float) $inv->remaining_receivable;

            if (! isset($customerGroups[$cid])) {
                $customerGroups[$cid] = [
                    'customer_id' => $cid,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'credit_limit' => $creditLimit,
                    'total_receivable' => 0.0,
                    'current' => 0.0,
                    'days_1_30' => 0.0,
                    'days_31_60' => 0.0,
                    'days_61_90' => 0.0,
                    'days_over_90' => 0.0,
                    'invoices' => [],
                ];
            }

            $customerGroups[$cid]['total_receivable'] += $remaining;

            // Hitung hari keterlambatan dari due_date
            $dueDate = $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->startOfDay() : null;
            $daysOverdue = $dueDate ? $dueDate->diffInDays($now, false) : 0;

            if ($daysOverdue <= 0) {
                $customerGroups[$cid]['current'] += $remaining;
                $agingBuckets['current'] += $remaining;
                $bucketLabel = 'Current';
            } elseif ($daysOverdue <= 30) {
                $customerGroups[$cid]['days_1_30'] += $remaining;
                $agingBuckets['days_1_30'] += $remaining;
                $bucketLabel = '1-30 Hari';
            } elseif ($daysOverdue <= 60) {
                $customerGroups[$cid]['days_31_60'] += $remaining;
                $agingBuckets['days_31_60'] += $remaining;
                $bucketLabel = '31-60 Hari';
            } elseif ($daysOverdue <= 90) {
                $customerGroups[$cid]['days_61_90'] += $remaining;
                $agingBuckets['days_61_90'] += $remaining;
                $bucketLabel = '61-90 Hari';
            } else {
                $customerGroups[$cid]['days_over_90'] += $remaining;
                $agingBuckets['days_over_90'] += $remaining;
                $bucketLabel = '> 90 Hari';
            }

            $customerGroups[$cid]['invoices'][] = [
                'invoice_number' => $inv->invoice_number,
                'transaction_date' => $inv->transaction_date,
                'due_date' => $inv->due_date,
                'days_overdue' => max(0, $daysOverdue),
                'remaining' => $remaining,
                'bucket' => $bucketLabel,
            ];
        }

        $customersList = collect($customerGroups)->sortByDesc('total_receivable')->values();
        $grandTotal = $customersList->sum('total_receivable');

        return [
            'store' => $store,
            'as_of_date' => $now->toDateString(),
            'grand_total' => $grandTotal,
            'aging_buckets' => $agingBuckets,
            'customers' => $customersList,
        ];
    }

    /**
     * 9. Laporan Analisa Umur Hutang Dagang (Aging Accounts Payable).
     */
    public function getAgingPayables(Company $company, ?Store $store = null): array
    {
        $query = \App\Models\Purchase::where('company_id', $company->id)
            ->whereIn('status', ['ORDERED', 'RECEIVED', 'APPROVED'])
            ->whereRaw('grand_total > paid_amount');

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $purchases = $query->with('supplier')->get();
        $now = now()->startOfDay();

        $agingBuckets = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_over_90' => 0.0,
        ];

        $supplierGroups = [];

        foreach ($purchases as $po) {
            $sid = $po->supplier_id;
            $supplierName = $po->supplier?->name ?? 'Supplier Umum';
            $supplierPhone = $po->supplier?->phone ?? '-';
            $remaining = max(0.0, (float) $po->grand_total - (float) $po->paid_amount);

            if (! isset($supplierGroups[$sid])) {
                $supplierGroups[$sid] = [
                    'supplier_id' => $sid,
                    'supplier_name' => $supplierName,
                    'supplier_phone' => $supplierPhone,
                    'total_payable' => 0.0,
                    'current' => 0.0,
                    'days_1_30' => 0.0,
                    'days_31_60' => 0.0,
                    'days_61_90' => 0.0,
                    'days_over_90' => 0.0,
                    'invoices' => [],
                ];
            }

            $supplierGroups[$sid]['total_payable'] += $remaining;

            $dueDate = $po->due_date ? \Carbon\Carbon::parse($po->due_date)->startOfDay() : null;
            $daysOverdue = $dueDate ? $dueDate->diffInDays($now, false) : 0;

            if ($daysOverdue <= 0) {
                $supplierGroups[$sid]['current'] += $remaining;
                $agingBuckets['current'] += $remaining;
                $bucketLabel = 'Current';
            } elseif ($daysOverdue <= 30) {
                $supplierGroups[$sid]['days_1_30'] += $remaining;
                $agingBuckets['days_1_30'] += $remaining;
                $bucketLabel = '1-30 Hari';
            } elseif ($daysOverdue <= 60) {
                $supplierGroups[$sid]['days_31_60'] += $remaining;
                $agingBuckets['days_31_60'] += $remaining;
                $bucketLabel = '31-60 Hari';
            } elseif ($daysOverdue <= 90) {
                $supplierGroups[$sid]['days_61_90'] += $remaining;
                $agingBuckets['days_61_90'] += $remaining;
                $bucketLabel = '61-90 Hari';
            } else {
                $supplierGroups[$sid]['days_over_90'] += $remaining;
                $agingBuckets['days_over_90'] += $remaining;
                $bucketLabel = '> 90 Hari';
            }

            $supplierGroups[$sid]['invoices'][] = [
                'purchase_number' => $po->invoice_number ?: $po->reference_number ?: 'PO-'.$po->id,
                'purchase_date' => $po->purchase_date,
                'due_date' => $po->due_date,
                'days_overdue' => max(0, $daysOverdue),
                'remaining' => $remaining,
                'bucket' => $bucketLabel,
            ];
        }

        $suppliersList = collect($supplierGroups)->sortByDesc('total_payable')->values();
        $grandTotal = $suppliersList->sum('total_payable');

        return [
            'store' => $store,
            'as_of_date' => $now->toDateString(),
            'grand_total' => $grandTotal,
            'aging_buckets' => $agingBuckets,
            'suppliers' => $suppliersList,
        ];
    }
}
