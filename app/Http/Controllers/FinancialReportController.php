<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    /**
     * Laporan Laba Rugi (Income Statement / P&L).
     */
    public function incomeStatement(Request $request): View|JsonResponse|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->reportService->getIncomeStatement($company, $startDate, $endDate, $store);

        if ($request->input('export') === 'excel' || $request->input('export') === 'xlsx') {
            return $this->exportIncomeStatementExcel($company, $report, $store);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        }

        return view('reports.income_statement', compact('report', 'stores', 'company', 'startDate', 'endDate', 'storeId'));
    }

    /**
     * Laporan Neraca (Balance Sheet).
     */
    public function balanceSheet(Request $request): View|JsonResponse|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $asOfDate = $request->input('as_of_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->reportService->getBalanceSheet($company, $asOfDate, $store);

        if ($request->input('export') === 'excel' || $request->input('export') === 'xlsx') {
            return $this->exportBalanceSheetExcel($company, $report, $store, $asOfDate);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        }

        return view('reports.balance_sheet', compact('report', 'stores', 'company', 'asOfDate', 'storeId'));
    }

    /**
     * Neraca Saldo (Trial Balance).
     */
    public function trialBalance(Request $request): View|JsonResponse|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->reportService->getTrialBalance($company, $startDate, $endDate, $store);

        if ($request->input('export') === 'excel' || $request->input('export') === 'xlsx') {
            return $this->exportTrialBalanceExcel($company, $report, $store, $startDate, $endDate);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        }

        return view('reports.trial_balance', compact('report', 'stores', 'company', 'startDate', 'endDate', 'storeId'));
    }

    /**
     * Buku Besar Akun (General Ledger Explorer).
     */
    public function generalLedger(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $accounts = ChartOfAccount::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $accountId = $request->input('account_id', $accounts->first()?->id);
        $selectedAccount = $accountId ? ChartOfAccount::findOrFail($accountId) : $accounts->first();

        $report = $selectedAccount
            ? $this->reportService->getGeneralLedger($selectedAccount, $startDate, $endDate, $store)
            : null;

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report' => $report,
                'accounts' => $accounts,
            ]);
        }

        return view('reports.general_ledger', compact('report', 'accounts', 'selectedAccount', 'stores', 'company', 'startDate', 'endDate', 'storeId'));
    }

    /**
     * Laporan Arus Kas (Cash Flow Statement).
     */
    public function cashFlow(Request $request): View|JsonResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->reportService->getCashFlow($company, $startDate, $endDate, $store);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        }

        return view('reports.cash_flow', compact('report', 'stores', 'company', 'startDate', 'endDate', 'storeId'));
    }

    /**
     * Daftar Penutupan Shift Kasir (Audit & Z-Report List) - Khusus Keuangan / Manager.
     */
    public function shifts(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $query = CashierShift::with(['cashier', 'store'])
            ->where('company_id', $company->id)
            ->where('status', 'CLOSED');

        if ($storeId = $request->input('store_id')) {
            $query->where('store_id', $storeId);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('closed_at', $date);
        }

        $shifts = $query->orderByDesc('closed_at')->paginate(20)->withQueryString();
        $stores = Store::where('company_id', $company->id)->get();

        return view('reports.shifts', compact('shifts', 'stores', 'company'));
    }

    /**
     * Cetak Laporan Penutupan Shift Kasir (Z-Report Resmi) - Khusus Keuangan / Manager.
     */
    public function shiftZReport(string $id): View
    {
        $shift = CashierShift::with([
            'cashier',
            'store.company',
            'cashDrops',
            'transactions.items.product',
            'transactions.customer',
        ])->findOrFail($id);

        $txs = $shift->transactions;

        $cashTxs = $txs->where('payment_method', 'CASH');
        $transferTxs = $txs->where('payment_method', 'TRANSFER');
        $qrisTxs = $txs->where('payment_method', 'QRIS');
        $splitTxs = $txs->where('payment_method', 'SPLIT');
        $creditTxs = $txs->filter(fn ($t) => in_array($t->payment_method, ['CREDIT', 'TEMPO']));

        $totalRevenue = (float) $txs->sum('grand_total');
        $totalCash = (float) $cashTxs->sum('paid_amount') + (float) $splitTxs->where('split_payment_method', 'CASH')->sum('paid_amount');
        $totalTransfer = (float) $transferTxs->sum('paid_amount') + (float) $splitTxs->where('split_payment_method', 'TRANSFER')->sum('paid_amount');
        $totalQris = (float) $qrisTxs->sum('paid_amount');
        $totalCredit = (float) $creditTxs->sum('credit_amount');
        $totalVoucher = (float) $txs->sum('voucher_discount');
        $totalPointsDiscount = (float) $txs->sum('points_discount_amount');
        $totalPointsRedeemed = (int) $txs->sum('points_redeemed');
        $totalDrops = (float) $shift->cashDrops->sum('amount');

        return view('reports.shift_z_report', compact(
            'shift',
            'totalRevenue',
            'totalCash',
            'totalTransfer',
            'totalQris',
            'totalCredit',
            'totalVoucher',
            'totalPointsDiscount',
            'totalPointsRedeemed',
            'totalDrops',
            'cashTxs',
            'transferTxs',
            'qrisTxs',
            'splitTxs',
            'creditTxs'
        ));
    }

    /**
     * Laporan Analisa Penjualan Ritel & Grosir Multi-Dimensi.
     */
    public function sales(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $cashierId = $request->input('cashier_id');
        $paymentMethod = $request->input('payment_method');

        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $cashiers = User::where('is_active', true)->orderBy('name')->get();

        $report = $this->reportService->getSalesReport(
            company: $company,
            startDate: $startDate,
            endDate: $endDate,
            store: $store,
            cashierId: $cashierId,
            paymentMethod: $paymentMethod
        );

        if ($request->input('export') === 'xlsx') {
            return $this->exportSalesReportExcel($company, $report, $store, $period);
        }

        if ($request->input('export') === 'csv') {
            $filename = "Laporan_Penjualan_{$startDate}_sampai_{$endDate}.csv";
            return response()->streamDownload(function () use ($report) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['LAPORAN PENJUALAN RITEL & GROSIR SIGMA']);
                fputcsv($handle, ['Periode', $report['start_date'].' s/d '.$report['end_date']]);
                fputcsv($handle, ['Total Transaksi', $report['total_transactions']]);
                fputcsv($handle, ['Total Omzet Kotor', $report['total_gross_sales']]);
                fputcsv($handle, ['Total Diskon', $report['total_discount']]);
                fputcsv($handle, ['Total Pajak (PPN)', $report['total_tax']]);
                fputcsv($handle, ['Total Omzet Bersih', $report['total_net_sales']]);
                fputcsv($handle, ['Total HPP (COGS)', $report['total_cogs']]);
                fputcsv($handle, ['Laba Kotor', $report['total_gross_profit']]);
                fputcsv($handle, ['Margin Laba (%)', $report['profit_margin'].'%']);
                fputcsv($handle, []);

                // Rincian Per Item
                fputcsv($handle, ['RINCIAN PENJUALAN PER ITEM PRODUK']);
                fputcsv($handle, ['SKU', 'Nama Produk', 'Kategori', 'Supplier', 'Qty Terjual', 'Satuan', 'Total Omzet', 'Total HPP', 'Laba Kotor', 'Margin %']);
                foreach ($report['by_item'] as $prod) {
                    fputcsv($handle, [
                        $prod['sku'],
                        $prod['product_name'],
                        $prod['category'],
                        $prod['supplier_name'],
                        $prod['qty_sold'],
                        $prod['base_unit'],
                        $prod['total_sales'],
                        $prod['total_cogs'],
                        $prod['gross_profit'],
                        $prod['profit_margin'].'%',
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('reports.sales', compact('report', 'stores', 'cashiers', 'company', 'startDate', 'endDate', 'storeId', 'cashierId', 'paymentMethod', 'period'));
    }

    /**
     * Laporan Analisa Pembelian Barang ke Supplier Multi-Dimensi.
     */
    public function purchases(Request $request): View|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $supplierId = $request->input('supplier_id');

        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        $report = $this->reportService->getPurchasesReport(
            company: $company,
            startDate: $startDate,
            endDate: $endDate,
            store: $store,
            supplierId: $supplierId
        );

        if ($request->input('export') === 'xlsx') {
            return $this->exportPurchasesReportExcel($company, $report, $store, $period);
        }

        if ($request->input('export') === 'csv') {
            $filename = "Laporan_Pembelian_{$startDate}_sampai_{$endDate}.csv";
            return response()->streamDownload(function () use ($report) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['LAPORAN PEMBELIAN SUPPLIER SIGMA']);
                fputcsv($handle, ['Periode', $report['start_date'].' s/d '.$report['end_date']]);
                fputcsv($handle, ['Total Faktur PO', $report['total_purchases']]);
                fputcsv($handle, ['Total Nilai Pembelian', $report['total_amount']]);
                fputcsv($handle, ['Total Sudah Dibayar', $report['total_paid']]);
                fputcsv($handle, ['Total Hutang Belum Lunas', $report['total_unpaid']]);
                fputcsv($handle, []);

                fputcsv($handle, ['REKAP PEMBELIAN PER SUPPLIER']);
                fputcsv($handle, ['Supplier', 'No Telp', 'Jml PO', 'Total Belanja', 'Terbayar', 'Sisa Hutang']);
                foreach ($report['by_supplier'] as $sup) {
                    fputcsv($handle, [
                        $sup['supplier_name'],
                        $sup['supplier_phone'],
                        $sup['invoice_count'],
                        $sup['total_amount'],
                        $sup['total_paid'],
                        $sup['total_unpaid'],
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('reports.purchases', compact('report', 'stores', 'suppliers', 'company', 'startDate', 'endDate', 'storeId', 'supplierId', 'period'));
    }

    /**
     * Laporan Analisa Umur Piutang Pelanggan (Aging AR).
     */
    public function agingReceivables(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->reportService->getAgingReceivables($company, $store);

        return view('reports.aging_receivables', compact('report', 'stores', 'company', 'storeId'));
    }

    /**
     * Laporan Analisa Umur Hutang Dagang Supplier (Aging AP).
     */
    public function agingPayables(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->reportService->getAgingPayables($company, $store);

        return view('reports.aging_payables', compact('report', 'stores', 'company', 'storeId'));
    }

    /**
     * Ekspor Laporan Laba Rugi ke File Excel (.xlsx) Berformat Akuntansi Resmi.
     */
    protected function exportIncomeStatementExcel(Company $company, array $report, ?Store $store): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laba Rugi');

        // Header Title
        $sheet->setCellValue('A1', $company->name);
        $sheet->setCellValue('A2', 'LAPORAN LABA RUGI KOMPREHENSIF');
        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko";
        $sheet->setCellValue('A3', "Periode: {$report['start_date']} s/d {$report['end_date']} | {$storeText}");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF64748B'));

        $row = 5;
        // Table Header
        $sheet->setCellValue("A{$row}", 'KODE AKUN');
        $sheet->setCellValue("B{$row}", 'KETERANGAN AKUN BUKU BESAR');
        $sheet->setCellValue("C{$row}", 'JUMLAH (RP)');
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');

        $row++;
        // 1. PENDAPATAN
        $sheet->setCellValue("B{$row}", '1. PENDAPATAN USAHA');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($report['revenues'] as $rev) {
            $sheet->setCellValue("A{$row}", $rev['code']);
            $sheet->setCellValue("B{$row}", "   {$rev['name']}");
            $sheet->setCellValue("C{$row}", $rev['amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'TOTAL PENDAPATAN USAHA');
        $sheet->setCellValue("C{$row}", $report['total_revenue']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row += 2;

        // 2. BEBAN POKOK PENJUALAN
        $sheet->setCellValue("B{$row}", '2. BEBAN POKOK PENJUALAN (HPP)');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue("A{$row}", '5001-001');
        $sheet->setCellValue("B{$row}", '   Beban Pokok Penjualan (HPP Moving Average)');
        $sheet->setCellValue("C{$row}", $report['cogs']);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue("B{$row}", 'TOTAL BEBAN POKOK PENJUALAN');
        $sheet->setCellValue("C{$row}", $report['cogs']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row += 2;

        // 3. LABA KOTOR
        $sheet->setCellValue("B{$row}", 'LABA KOTOR (GROSS PROFIT)');
        $sheet->setCellValue("C{$row}", $report['gross_profit']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        $row += 2;

        // 4. BEBAN OPERASIONAL
        $sheet->setCellValue("B{$row}", '3. BEBAN OPERASIONAL (SG&A)');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($report['operating_expenses'] as $exp) {
            $sheet->setCellValue("A{$row}", $exp['code']);
            $sheet->setCellValue("B{$row}", "   {$exp['name']}");
            $sheet->setCellValue("C{$row}", $exp['amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'TOTAL BEBAN OPERASIONAL');
        $sheet->setCellValue("C{$row}", $report['total_operating_expenses']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row += 2;

        // 5. LABA / RUGI BERSIH
        $sheet->setCellValue("B{$row}", 'LABA / (RUGI) BERSIH TAHUN BERJALAN');
        $sheet->setCellValue("C{$row}", $report['net_profit']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCEAFE');

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Laporan_Laba_Rugi_{$report['start_date']}_sampai_{$report['end_date']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Ekspor Laporan Neraca ke File Excel (.xlsx) Berformat Akuntansi Resmi.
     */
    protected function exportBalanceSheetExcel(Company $company, array $report, ?Store $store, string $asOfDate): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Neraca Keuangan');

        // Header Title
        $sheet->setCellValue('A1', $company->name);
        $sheet->setCellValue('A2', 'LAPORAN POSISI KEUANGAN (NERACA)');
        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko";
        $sheet->setCellValue('A3', "Posisi Per: {$asOfDate} | {$storeText}");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF64748B'));

        $row = 5;
        // Table Header
        $sheet->setCellValue("A{$row}", 'KODE AKUN');
        $sheet->setCellValue("B{$row}", 'KOMPONEN NERACA');
        $sheet->setCellValue("C{$row}", 'NILAI BUKU (RP)');
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');

        $row++;
        // 1. ASET LANCAR
        $sheet->setCellValue("B{$row}", 'ASET LANCAR (CURRENT ASSETS)');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($report['current_assets'] as $ast) {
            $sheet->setCellValue("A{$row}", $ast['code']);
            $sheet->setCellValue("B{$row}", "   {$ast['name']}");
            $sheet->setCellValue("C{$row}", $ast['amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // 2. ASET TETAP
        $sheet->setCellValue("B{$row}", 'ASET TETAP (FIXED ASSETS)');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($report['fixed_assets'] as $fa) {
            $sheet->setCellValue("A{$row}", $fa['code']);
            $sheet->setCellValue("B{$row}", "   {$fa['name']}");
            $sheet->setCellValue("C{$row}", $fa['amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'TOTAL AKTIVA (ASET)');
        $sheet->setCellValue("C{$row}", $report['total_assets']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDBEAFE');
        $row += 2;

        // 3. KEWAJIBAN (LIABILITAS)
        $sheet->setCellValue("B{$row}", 'KEWAJIBAN / HUTANG (LIABILITIES)');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($report['liabilities'] as $liab) {
            $sheet->setCellValue("A{$row}", $liab['code']);
            $sheet->setCellValue("B{$row}", "   {$liab['name']}");
            $sheet->setCellValue("C{$row}", $liab['amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'TOTAL KEWAJIBAN');
        $sheet->setCellValue("C{$row}", $report['total_liabilities']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row += 2;

        // 4. EKUITAS (MODAL)
        $sheet->setCellValue("B{$row}", 'EKUITAS (MODAL & LABA DITAHAN)');
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        $row++;

        foreach ($report['equity'] as $eq) {
            $sheet->setCellValue("A{$row}", $eq['code']);
            $sheet->setCellValue("B{$row}", "   {$eq['name']}");
            $sheet->setCellValue("C{$row}", $eq['amount']);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        $sheet->setCellValue("B{$row}", '   Laba Bersih Berjalan');
        $sheet->setCellValue("C{$row}", $report['current_earnings']);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue("B{$row}", 'TOTAL EKUITAS');
        $sheet->setCellValue("C{$row}", $report['total_equity']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row += 2;

        // 5. TOTAL PASIVA
        $sheet->setCellValue("B{$row}", 'TOTAL PASIVA (KEWAJIBAN + EKUITAS)');
        $sheet->setCellValue("C{$row}", $report['total_liabilities_and_equity']);
        $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:C{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');

        foreach (['A', 'B', 'C'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Laporan_Neraca_{$asOfDate}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Ekspor Neraca Saldo ke File Excel (.xlsx) Berformat Akuntansi Resmi.
     */
    protected function exportTrialBalanceExcel(Company $company, array $report, ?Store $store, string $startDate, string $endDate): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Neraca Saldo');

        // Header Title
        $sheet->setCellValue('A1', $company->name);
        $sheet->setCellValue('A2', 'NERACA SALDO (TRIAL BALANCE)');
        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko";
        $sheet->setCellValue('A3', "Periode: {$startDate} s/d {$endDate} | {$storeText}");

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF64748B'));

        $row = 5;
        // Table Header
        $sheet->setCellValue("A{$row}", 'KODE AKUN');
        $sheet->setCellValue("B{$row}", 'NAMA AKUN PERKIRAAN');
        $sheet->setCellValue("C{$row}", 'TIPE');
        $sheet->setCellValue("D{$row}", 'DEBIT (RP)');
        $sheet->setCellValue("E{$row}", 'KREDIT (RP)');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
        $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');

        $row++;
        foreach ($report['rows'] as $acc) {
            $sheet->setCellValue("A{$row}", $acc['code']);
            $sheet->setCellValue("B{$row}", $acc['name']);
            $sheet->setCellValue("C{$row}", $acc['type']);
            $sheet->setCellValue("D{$row}", $acc['debit']);
            $sheet->setCellValue("E{$row}", $acc['credit']);
            $sheet->getStyle("D{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        // Totals
        $sheet->setCellValue("B{$row}", 'TOTAL SALDO AKHIR');
        $sheet->setCellValue("D{$row}", $report['total_debit']);
        $sheet->setCellValue("E{$row}", $report['total_credit']);
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $sheet->getStyle("D{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');

        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Neraca_Saldo_{$startDate}_sampai_{$endDate}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Ekspor Laporan Analisa Penjualan Multi-Dimensi ke Excel (.xlsx).
     */
    protected function exportSalesReportExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // 1. SHEET 1: RINGKASAN
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan Penjualan');

        $sheet1->setCellValue('A1', strtoupper($company->name));
        $sheet1->setCellValue('A2', 'LAPORAN ANALISA PENJUALAN RITEL & GROSIR');
        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko (Konsolidasi)";
        $sheet1->setCellValue('A3', "Periode: {$period['label']} | {$storeText}");

        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet1->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF64748B'));

        $sheet1->setCellValue('A5', 'METRIK KINERJA');
        $sheet1->setCellValue('B5', 'NILAI / JUMLAH');
        $sheet1->getStyle('A5:B5')->applyFromArray($headerStyle);

        $kpis = [
            ['Total Transaksi Kasir', $report['total_transactions'], '#,##0'],
            ['Total Omzet Kotor (Subtotal)', $report['total_gross_sales'], '#,##0.00'],
            ['Total Potongan Diskon & Voucher', $report['total_discount'], '#,##0.00'],
            ['Total PPN Terpungut', $report['total_tax'], '#,##0.00'],
            ['Total Omzet Bersih (Grand Total)', $report['total_net_sales'], '#,##0.00'],
            ['Total HPP Barang Terjual (COGS)', $report['total_cogs'], '#,##0.00'],
            ['Total Laba Kotor Penjualan (Gross Profit)', $report['total_gross_profit'], '#,##0.00'],
            ['Margin Laba Kotor (%)', ($report['profit_margin'] ?? 0) . '%', '@'],
            ['Rata-rata Nilai Belanja / Basket Size', $report['avg_transaction'], '#,##0.00'],
        ];

        $r = 6;
        foreach ($kpis as $kpi) {
            $sheet1->setCellValue("A{$r}", $kpi[0]);
            $sheet1->setCellValue("B{$r}", $kpi[1]);
            if ($kpi[2] !== '@') {
                $sheet1->getStyle("B{$r}")->getNumberFormat()->setFormatCode($kpi[2]);
            }
            $r++;
        }
        $sheet1->getStyle("A5:B" . ($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet1->getColumnDimension('A')->setAutoSize(true);
        $sheet1->getColumnDimension('B')->setAutoSize(true);

        // 2. SHEET 2: PER ITEM
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Per Item Barang');
        $sheet2->setCellValue('A1', 'RINCIAN PENJUALAN PER ITEM BARANG');
        $sheet2->setCellValue('A2', "Periode: {$period['label']} | {$storeText}");
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersItem = ['No', 'SKU', 'Nama Produk', 'Kategori', 'Supplier Utama', 'Qty Terjual', 'Satuan', 'Total Omzet (Rp)', 'Total HPP (Rp)', 'Laba Kotor (Rp)', 'Margin (%)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        foreach ($headersItem as $i => $h) {
            $sheet2->setCellValue("{$cols[$i]}4", $h);
        }
        $sheet2->getStyle('A4:K4')->applyFromArray($headerStyle);

        $rowItem = 5;
        $no = 1;
        foreach ($report['by_item'] as $item) {
            $sheet2->setCellValue("A{$rowItem}", $no++);
            $sheet2->setCellValue("B{$rowItem}", $item['sku']);
            $sheet2->setCellValue("C{$rowItem}", $item['product_name']);
            $sheet2->setCellValue("D{$rowItem}", $item['category']);
            $sheet2->setCellValue("E{$rowItem}", $item['supplier_name']);
            $sheet2->setCellValue("F{$rowItem}", $item['qty_sold']);
            $sheet2->setCellValue("G{$rowItem}", $item['base_unit']);
            $sheet2->setCellValue("H{$rowItem}", $item['total_sales']);
            $sheet2->setCellValue("I{$rowItem}", $item['total_cogs']);
            $sheet2->setCellValue("J{$rowItem}", $item['gross_profit']);
            $sheet2->setCellValue("K{$rowItem}", $item['profit_margin'] . '%');

            $sheet2->getStyle("F{$rowItem}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet2->getStyle("H{$rowItem}:J{$rowItem}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowItem++;
        }
        $sheet2->getStyle("A4:K" . ($rowItem - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet2->getColumnDimension($c)->setAutoSize(true);
        }

        // 3. SHEET 3: PER KATEGORI
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Per Kategori');
        $sheet3->setCellValue('A1', 'PENJUALAN PER JENIS / KATEGORI BARANG');
        $sheet3->setCellValue('A2', "Periode: {$period['label']} | {$storeText}");
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersCat = ['No', 'Kategori Barang', 'Jml Item', 'Qty Terjual', 'Total Omzet (Rp)', 'Total HPP (Rp)', 'Laba Kotor (Rp)', 'Margin (%)', 'Porsi Omzet (%)'];
        $colsCat = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headersCat as $i => $h) {
            $sheet3->setCellValue("{$colsCat[$i]}4", $h);
        }
        $sheet3->getStyle('A4:I4')->applyFromArray($headerStyle);

        $rowCat = 5;
        $no = 1;
        foreach ($report['by_category'] as $cat) {
            $sheet3->setCellValue("A{$rowCat}", $no++);
            $sheet3->setCellValue("B{$rowCat}", $cat['category']);
            $sheet3->setCellValue("C{$rowCat}", $cat['item_count']);
            $sheet3->setCellValue("D{$rowCat}", $cat['qty_sold']);
            $sheet3->setCellValue("E{$rowCat}", $cat['total_sales']);
            $sheet3->setCellValue("F{$rowCat}", $cat['total_cogs']);
            $sheet3->setCellValue("G{$rowCat}", $cat['gross_profit']);
            $sheet3->setCellValue("H{$rowCat}", $cat['profit_margin'] . '%');
            $sheet3->setCellValue("I{$rowCat}", $cat['sales_share_percent'] . '%');

            $sheet3->getStyle("D{$rowCat}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet3->getStyle("E{$rowCat}:G{$rowCat}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowCat++;
        }
        $sheet3->getStyle("A4:I" . ($rowCat - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsCat as $c) {
            $sheet3->getColumnDimension($c)->setAutoSize(true);
        }

        // 4. SHEET 4: PER KASIR
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Per Kasir');
        $sheet4->setCellValue('A1', 'PENJUALAN PER KASIR');
        $sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersCashier = ['No', 'Nama Kasir', 'Jml Struk / Transaksi', 'Total Omzet (Rp)', 'Rata-rata per Struk (Rp)'];
        $colsCashier = ['A', 'B', 'C', 'D', 'E'];
        foreach ($headersCashier as $i => $h) {
            $sheet4->setCellValue("{$colsCashier[$i]}4", $h);
        }
        $sheet4->getStyle('A4:E4')->applyFromArray($headerStyle);

        $rowCashier = 5;
        $no = 1;
        foreach ($report['by_cashier'] as $csh) {
            $sheet4->setCellValue("A{$rowCashier}", $no++);
            $sheet4->setCellValue("B{$rowCashier}", $csh['cashier_name']);
            $sheet4->setCellValue("C{$rowCashier}", $csh['transaction_count']);
            $sheet4->setCellValue("D{$rowCashier}", $csh['total_sales']);
            $sheet4->setCellValue("E{$rowCashier}", $csh['avg_per_tx']);
            $sheet4->getStyle("D{$rowCashier}:E{$rowCashier}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowCashier++;
        }
        $sheet4->getStyle("A4:E" . ($rowCashier - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsCashier as $c) {
            $sheet4->getColumnDimension($c)->setAutoSize(true);
        }

        // 5. SHEET 5: PER PELANGGAN
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Per Pelanggan');
        $sheet5->setCellValue('A1', 'PENJUALAN PER PELANGGAN');
        $sheet5->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersCust = ['No', 'Kode', 'Nama Pelanggan', 'Tier Pelanggan', 'Jml Transaksi', 'Total Belanja (Rp)', 'Sisa Piutang Berjalan (Rp)'];
        $colsCust = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($headersCust as $i => $h) {
            $sheet5->setCellValue("{$colsCust[$i]}4", $h);
        }
        $sheet5->getStyle('A4:G4')->applyFromArray($headerStyle);

        $rowCust = 5;
        $no = 1;
        foreach ($report['by_customer'] as $cust) {
            $sheet5->setCellValue("A{$rowCust}", $no++);
            $sheet5->setCellValue("B{$rowCust}", $cust['customer_code']);
            $sheet5->setCellValue("C{$rowCust}", $cust['customer_name']);
            $sheet5->setCellValue("D{$rowCust}", $cust['tier_name']);
            $sheet5->setCellValue("E{$rowCust}", $cust['transaction_count']);
            $sheet5->setCellValue("F{$rowCust}", $cust['total_sales']);
            $sheet5->setCellValue("G{$rowCust}", $cust['current_credit_balance']);
            $sheet5->getStyle("F{$rowCust}:G{$rowCust}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowCust++;
        }
        $sheet5->getStyle("A4:G" . ($rowCust - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsCust as $c) {
            $sheet5->getColumnDimension($c)->setAutoSize(true);
        }

        // 6. SHEET 6: PER SUPPLIER
        $sheet6 = $spreadsheet->createSheet();
        $sheet6->setTitle('Per Supplier');
        $sheet6->setCellValue('A1', 'PENJUALAN PER SUPPLIER (PEMASOK BARANG)');
        $sheet6->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersSup = ['No', 'Nama Supplier Pemasok', 'Qty Terjual', 'Total Omzet (Rp)', 'Total HPP (Rp)', 'Laba Kotor Toko (Rp)', 'Margin (%)'];
        $colsSup = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($headersSup as $i => $h) {
            $sheet6->setCellValue("{$colsSup[$i]}4", $h);
        }
        $sheet6->getStyle('A4:G4')->applyFromArray($headerStyle);

        $rowSup = 5;
        $no = 1;
        foreach ($report['by_supplier'] as $sup) {
            $sheet6->setCellValue("A{$rowSup}", $no++);
            $sheet6->setCellValue("B{$rowSup}", $sup['supplier_name']);
            $sheet6->setCellValue("C{$rowSup}", $sup['qty_sold']);
            $sheet6->setCellValue("D{$rowSup}", $sup['total_sales']);
            $sheet6->setCellValue("E{$rowSup}", $sup['total_cogs']);
            $sheet6->setCellValue("F{$rowSup}", $sup['gross_profit']);
            $sheet6->setCellValue("G{$rowSup}", $sup['profit_margin'] . '%');

            $sheet6->getStyle("C{$rowSup}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet6->getStyle("D{$rowSup}:F{$rowSup}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowSup++;
        }
        $sheet6->getStyle("A4:G" . ($rowSup - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsSup as $c) {
            $sheet6->getColumnDimension($c)->setAutoSize(true);
        }

        // 7. SHEET 7: PER CABANG
        $sheet7 = $spreadsheet->createSheet();
        $sheet7->setTitle('Per Cabang');
        $sheet7->setCellValue('A1', 'PERBANDINGAN PENJUALAN ANTAR CABANG');
        $sheet7->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersStore = ['No', 'Kode Cabang', 'Nama Cabang Toko', 'Jml Transaksi', 'Total Omzet (Rp)', 'Rata-rata per Struk (Rp)'];
        $colsStore = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headersStore as $i => $h) {
            $sheet7->setCellValue("{$colsStore[$i]}4", $h);
        }
        $sheet7->getStyle('A4:F4')->applyFromArray($headerStyle);

        $rowStore = 5;
        $no = 1;
        foreach ($report['by_store'] as $st) {
            $sheet7->setCellValue("A{$rowStore}", $no++);
            $sheet7->setCellValue("B{$rowStore}", $st['store_code']);
            $sheet7->setCellValue("C{$rowStore}", $st['store_name']);
            $sheet7->setCellValue("D{$rowStore}", $st['transaction_count']);
            $sheet7->setCellValue("E{$rowStore}", $st['total_sales']);
            $sheet7->setCellValue("F{$rowStore}", $st['avg_per_tx']);
            $sheet7->getStyle("E{$rowStore}:F{$rowStore}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowStore++;
        }
        $sheet7->getStyle("A4:F" . ($rowStore - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsStore as $c) {
            $sheet7->getColumnDimension($c)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = "Laporan_Penjualan_{$period['start_date']}_sampai_{$period['end_date']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Ekspor Laporan Analisa Pembelian Multi-Dimensi ke Excel (.xlsx).
     */
    protected function exportPurchasesReportExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // 1. SHEET 1: RINGKASAN
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Ringkasan Pembelian');

        $sheet1->setCellValue('A1', strtoupper($company->name));
        $sheet1->setCellValue('A2', 'LAPORAN ANALISA PEMBELIAN & PENGADAAN BARANG');
        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko (Konsolidasi)";
        $sheet1->setCellValue('A3', "Periode: {$period['label']} | {$storeText}");

        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet1->getStyle('A3')->getFont()->setItalic(true)->setSize(10)->setColor(new Color('FF64748B'));

        $sheet1->setCellValue('A5', 'METRIK PEMBELIAN');
        $sheet1->setCellValue('B5', 'NILAI / JUMLAH');
        $sheet1->getStyle('A5:B5')->applyFromArray($headerStyle);

        $kpis = [
            ['Total Faktur Pembelian (PO)', $report['total_purchases'], '#,##0'],
            ['Subtotal Pembelian', $report['total_subtotal'] ?? 0, '#,##0.00'],
            ['Potongan Diskon Pembelian', $report['total_discount'] ?? 0, '#,##0.00'],
            ['PPN Masukan', $report['total_tax'] ?? 0, '#,##0.00'],
            ['Biaya Pengiriman (Ongkir)', $report['total_shipping'] ?? 0, '#,##0.00'],
            ['Grand Total Nilai Pembelian', $report['total_amount'], '#,##0.00'],
            ['Total Sudah Dibayar', $report['total_paid'], '#,##0.00'],
            ['Sisa Hutang Belum Lunas (AP)', $report['total_unpaid'], '#,##0.00'],
        ];

        $r = 6;
        foreach ($kpis as $kpi) {
            $sheet1->setCellValue("A{$r}", $kpi[0]);
            $sheet1->setCellValue("B{$r}", $kpi[1]);
            $sheet1->getStyle("B{$r}")->getNumberFormat()->setFormatCode($kpi[2]);
            $r++;
        }
        $sheet1->getStyle("A5:B" . ($r - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet1->getColumnDimension('A')->setAutoSize(true);
        $sheet1->getColumnDimension('B')->setAutoSize(true);

        // 2. SHEET 2: PER ITEM
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Per Item Barang');
        $sheet2->setCellValue('A1', 'RINCIAN PEMBELIAN PER ITEM BARANG');
        $sheet2->setCellValue('A2', "Periode: {$period['label']} | {$storeText}");
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersItem = ['No', 'SKU', 'Nama Produk', 'Kategori', 'Qty Dibeli', 'Satuan', 'Total Nilai (Rp)', 'Rata-rata Harga Beli (Rp)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($headersItem as $i => $h) {
            $sheet2->setCellValue("{$cols[$i]}4", $h);
        }
        $sheet2->getStyle('A4:H4')->applyFromArray($headerStyle);

        $rowItem = 5;
        $no = 1;
        foreach ($report['by_item'] as $item) {
            $sheet2->setCellValue("A{$rowItem}", $no++);
            $sheet2->setCellValue("B{$rowItem}", $item['sku']);
            $sheet2->setCellValue("C{$rowItem}", $item['product_name']);
            $sheet2->setCellValue("D{$rowItem}", $item['category']);
            $sheet2->setCellValue("E{$rowItem}", $item['qty_purchased']);
            $sheet2->setCellValue("F{$rowItem}", $item['base_unit']);
            $sheet2->setCellValue("G{$rowItem}", $item['total_amount']);
            $sheet2->setCellValue("H{$rowItem}", $item['avg_unit_cost']);

            $sheet2->getStyle("E{$rowItem}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet2->getStyle("G{$rowItem}:H{$rowItem}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowItem++;
        }
        $sheet2->getStyle("A4:H" . ($rowItem - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet2->getColumnDimension($c)->setAutoSize(true);
        }

        // 3. SHEET 3: PER KATEGORI
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Per Kategori');
        $sheet3->setCellValue('A1', 'PEMBELIAN PER KATEGORI BARANG');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersCat = ['No', 'Kategori Barang', 'Jml Item', 'Qty Dibeli', 'Total Modal Belanja (Rp)', 'Porsi Modal (%)'];
        $colsCat = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headersCat as $i => $h) {
            $sheet3->setCellValue("{$colsCat[$i]}4", $h);
        }
        $sheet3->getStyle('A4:F4')->applyFromArray($headerStyle);

        $rowCat = 5;
        $no = 1;
        foreach ($report['by_category'] as $cat) {
            $sheet3->setCellValue("A{$rowCat}", $no++);
            $sheet3->setCellValue("B{$rowCat}", $cat['category']);
            $sheet3->setCellValue("C{$rowCat}", $cat['item_count']);
            $sheet3->setCellValue("D{$rowCat}", $cat['qty_purchased']);
            $sheet3->setCellValue("E{$rowCat}", $cat['total_amount']);
            $sheet3->setCellValue("F{$rowCat}", $cat['share_percent'] . '%');

            $sheet3->getStyle("D{$rowCat}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet3->getStyle("E{$rowCat}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowCat++;
        }
        $sheet3->getStyle("A4:F" . ($rowCat - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsCat as $c) {
            $sheet3->getColumnDimension($c)->setAutoSize(true);
        }

        // 4. SHEET 4: PER SUPPLIER
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Per Supplier');
        $sheet4->setCellValue('A1', 'REKAP PEMBELIAN PER SUPPLIER');
        $sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersSup = ['No', 'Kode', 'Nama Supplier', 'NPWP', 'PKP', 'Jml Faktur', 'Total Belanja (Rp)', 'Sudah Dibayar (Rp)', 'Sisa Hutang (Rp)'];
        $colsSup = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headersSup as $i => $h) {
            $sheet4->setCellValue("{$colsSup[$i]}4", $h);
        }
        $sheet4->getStyle('A4:I4')->applyFromArray($headerStyle);

        $rowSup = 5;
        $no = 1;
        foreach ($report['by_supplier'] as $sup) {
            $sheet4->setCellValue("A{$rowSup}", $no++);
            $sheet4->setCellValue("B{$rowSup}", $sup['supplier_code']);
            $sheet4->setCellValue("C{$rowSup}", $sup['supplier_name']);
            $sheet4->setCellValue("D{$rowSup}", $sup['supplier_npwp']);
            $sheet4->setCellValue("E{$rowSup}", $sup['is_pkp'] ? 'YA' : 'TIDAK');
            $sheet4->setCellValue("F{$rowSup}", $sup['invoice_count']);
            $sheet4->setCellValue("G{$rowSup}", $sup['total_amount']);
            $sheet4->setCellValue("H{$rowSup}", $sup['total_paid']);
            $sheet4->setCellValue("I{$rowSup}", $sup['total_unpaid']);

            $sheet4->getStyle("G{$rowSup}:I{$rowSup}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowSup++;
        }
        $sheet4->getStyle("A4:I" . ($rowSup - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsSup as $c) {
            $sheet4->getColumnDimension($c)->setAutoSize(true);
        }

        // 5. SHEET 5: PER CABANG
        $sheet5 = $spreadsheet->createSheet();
        $sheet5->setTitle('Per Cabang');
        $sheet5->setCellValue('A1', 'PEMBELIAN PER CABANG TOKO');
        $sheet5->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersStore = ['No', 'Kode Cabang', 'Nama Cabang Toko', 'Jml Faktur', 'Total Pembelian (Rp)', 'Sudah Dibayar (Rp)', 'Sisa Hutang (Rp)'];
        $colsStore = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($headersStore as $i => $h) {
            $sheet5->setCellValue("{$colsStore[$i]}4", $h);
        }
        $sheet5->getStyle('A4:G4')->applyFromArray($headerStyle);

        $rowStore = 5;
        $no = 1;
        foreach ($report['by_store'] as $st) {
            $sheet5->setCellValue("A{$rowStore}", $no++);
            $sheet5->setCellValue("B{$rowStore}", $st['store_code']);
            $sheet5->setCellValue("C{$rowStore}", $st['store_name']);
            $sheet5->setCellValue("D{$rowStore}", $st['invoice_count']);
            $sheet5->setCellValue("E{$rowStore}", $st['total_amount']);
            $sheet5->setCellValue("F{$rowStore}", $st['total_paid']);
            $sheet5->setCellValue("G{$rowStore}", $st['total_unpaid']);

            $sheet5->getStyle("E{$rowStore}:G{$rowStore}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowStore++;
        }
        $sheet5->getStyle("A4:G" . ($rowStore - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsStore as $c) {
            $sheet5->getColumnDimension($c)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = "Laporan_Pembelian_{$period['start_date']}_sampai_{$period['end_date']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
