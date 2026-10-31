<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Services\FinancialReportService;
use App\Services\InventoryReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportController extends Controller
{
    public function __construct(
        protected InventoryReportService $inventoryService
    ) {}

    /**
     * 1. Laporan Nilai Persediaan Aset Barang (Stock Valuation).
     */
    public function valuation(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $categoryId = $request->input('category_id');

        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $categories = ProductCategory::where('company_id', $company->id)->orderBy('name')->get();

        $report = $this->inventoryService->getStockValuationReport(
            company: $company,
            store: $store,
            categoryId: $categoryId
        );

        if ($request->input('export') === 'xlsx') {
            return $this->exportValuationExcel($company, $report, $store);
        }

        return view('reports.inventory.valuation', compact('report', 'stores', 'categories', 'company', 'storeId', 'categoryId'));
    }

    /**
     * 2. Kartu Mutasi Stok Lengkap per Item Barang (Stock Card).
     */
    public function stockCard(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;

        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $products = Product::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();

        $productId = $request->input('product_id') ?: ($products->first()?->id ?? null);
        $report = null;

        if ($productId) {
            $report = $this->inventoryService->getStockCardReport(
                company: $company,
                productId: $productId,
                startDate: $startDate,
                endDate: $endDate,
                store: $store
            );
        }

        if ($request->input('export') === 'xlsx' && $report) {
            return $this->exportStockCardExcel($company, $report, $store, $period);
        }

        return view('reports.inventory.stock_card', compact('report', 'stores', 'products', 'company', 'productId', 'storeId', 'startDate', 'endDate', 'period'));
    }

    /**
     * 3. Laporan Peringatan Stok Minimum & Estimasi Habis (Low Stock Alert).
     */
    public function lowStock(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->inventoryService->getLowStockAlertReport($company, $store);

        if ($request->input('export') === 'xlsx') {
            return $this->exportLowStockExcel($company, $report, $store);
        }

        return view('reports.inventory.low_stock', compact('report', 'stores', 'company', 'storeId'));
    }

    /**
     * 4. Laporan Analisa Perputaran Barang (Fast/Slow Moving & Dead Stock).
     */
    public function turnover(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->inventoryService->getInventoryTurnoverReport($company, $startDate, $endDate, $store);

        if ($request->input('export') === 'xlsx') {
            return $this->exportTurnoverExcel($company, $report, $store, $period);
        }

        return view('reports.inventory.turnover', compact('report', 'stores', 'company', 'storeId', 'period'));
    }

    /**
     * 5. Laporan Kedaluwarsa Barang (Expiry Date Report).
     */
    public function expiry(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $withinDays = (int) $request->input('within_days', 90);
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->inventoryService->getExpiryDateReport($company, $store, $withinDays);

        if ($request->input('export') === 'xlsx') {
            return $this->exportExpiryExcel($company, $report, $store, $withinDays);
        }

        return view('reports.inventory.expiry', compact('report', 'stores', 'company', 'storeId', 'withinDays'));
    }

    /**
     * 6. Laporan Selisih Stock Opname (Discrepancy / Shrinkage).
     */
    public function opnameDiscrepancy(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->inventoryService->getOpnameDiscrepancyReport($company, $startDate, $endDate, $store);

        if ($request->input('export') === 'xlsx') {
            return $this->exportOpnameDiscrepancyExcel($company, $report, $store, $period);
        }

        return view('reports.inventory.opname_discrepancy', compact('report', 'stores', 'company', 'storeId', 'period'));
    }

    /**
     * Export Excel Nilai Persediaan.
     */
    protected function exportValuationExcel(Company $company, array $report, ?Store $store): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // Sheet 1: Rincian Barang
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Stok per Item');

        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko (Konsolidasi)";
        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', 'LAPORAN NILAI PERSEDIAAN ASET BARANG (STOCK VALUATION)');
        $sheet->setCellValue('A3', "Posisi Per: " . now()->format('d/m/Y H:i') . " | {$storeText}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'SKU', 'Barcode', 'Nama Barang', 'Kategori', 'Supplier Utama', 'Cabang', 'Qty Fisik', 'Qty Dipesan', 'Qty Tersedia', 'Satuan', 'HPP Moving Avg (Rp)', 'Total Nilai Aset (Rp)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}5", $h);
        }
        $sheet->getStyle('A5:M5')->applyFromArray($headerStyle);

        $row = 6;
        $no = 1;
        foreach ($report['items'] as $item) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $item['sku']);
            $sheet->setCellValue("C{$row}", $item['barcode']);
            $sheet->setCellValue("D{$row}", $item['product_name']);
            $sheet->setCellValue("E{$row}", $item['category']);
            $sheet->setCellValue("F{$row}", $item['supplier_name']);
            $sheet->setCellValue("G{$row}", $item['store_name']);
            $sheet->setCellValue("H{$row}", $item['physical_qty']);
            $sheet->setCellValue("I{$row}", $item['reserved_qty']);
            $sheet->setCellValue("J{$row}", $item['available_qty']);
            $sheet->setCellValue("K{$row}", $item['base_unit']);
            $sheet->setCellValue("L{$row}", $item['cost_price']);
            $sheet->setCellValue("M{$row}", $item['total_value']);

            $sheet->getStyle("H{$row}:J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("L{$row}:M{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Total
        $sheet->setCellValue("G{$row}", 'TOTAL NILAI ASET STOK:');
        $sheet->setCellValue("H{$row}", $report['total_physical_qty']);
        $sheet->setCellValue("M{$row}", $report['total_valuation_value']);
        $sheet->getStyle("A{$row}:M{$row}")->getFont()->setBold(true);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("M{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$row}:M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');

        $sheet->getStyle("A5:M{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Sheet 2: Per Kategori
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rekap per Kategori');
        $sheet2->setCellValue('A1', 'NILAI PERSEDIAAN PER KATEGORI BARANG');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $catHeaders = ['No', 'Kategori Barang', 'Variasi SKU', 'Total Qty Fisik', 'Total Nilai Aset Stok (Rp)', 'Porsi Nilai (%)'];
        $cols2 = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($catHeaders as $i => $h) {
            $sheet2->setCellValue("{$cols2[$i]}4", $h);
        }
        $sheet2->getStyle('A4:F4')->applyFromArray($headerStyle);

        $r2 = 5;
        $no2 = 1;
        foreach ($report['by_category'] as $cat) {
            $sheet2->setCellValue("A{$r2}", $no2++);
            $sheet2->setCellValue("B{$r2}", $cat['category']);
            $sheet2->setCellValue("C{$r2}", $cat['sku_count']);
            $sheet2->setCellValue("D{$r2}", $cat['total_qty']);
            $sheet2->setCellValue("E{$r2}", $cat['total_value']);
            $sheet2->setCellValue("F{$r2}", $cat['value_share_percent'] . '%');
            $sheet2->getStyle("D{$r2}:E{$r2}")->getNumberFormat()->setFormatCode('#,##0.00');
            $r2++;
        }
        $sheet2->getStyle("A4:F" . ($r2 - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols2 as $c) {
            $sheet2->getColumnDimension($c)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = "Laporan_Nilai_Persediaan_" . now()->format('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export Excel Kartu Stok.
     */
    protected function exportStockCardExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kartu Stok');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $product = $report['product'];
        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko";

        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', "KARTU STOK: {$product->name} (SKU: {$product->sku})");
        $sheet->setCellValue('A3', "Periode: {$period['label']} | {$storeText} | Satuan: " . ($product->baseUnit?->unit_name ?? 'Pcs'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Saldo Awal Box
        $sheet->setCellValue('A5', 'SALDO AWAL SEBELUM PERIODE:');
        $sheet->setCellValue('B5', $report['opening_balance']);
        $sheet->getStyle('A5:B5')->getFont()->setBold(true);
        $sheet->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.00');

        $headers = ['No', 'Tanggal & Waktu', 'Cabang', 'No. Referensi Dokumen', 'Tipe Mutasi', 'Masuk (+)', 'Keluar (-)', 'Saldo Akhir', 'HPP (Rp)', 'Total Nilai (Rp)', 'Keterangan'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}7", $h);
        }
        $sheet->getStyle('A7:K7')->applyFromArray($headerStyle);

        $row = 8;
        $no = 1;
        foreach ($report['rows'] as $m) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $m['datetime']);
            $sheet->setCellValue("C{$row}", $m['store_name']);
            $sheet->setCellValue("D{$row}", $m['reference_number']);
            $sheet->setCellValue("E{$row}", $m['type_label']);
            $sheet->setCellValue("F{$row}", $m['qty_in']);
            $sheet->setCellValue("G{$row}", $m['qty_out']);
            $sheet->setCellValue("H{$row}", $m['balance']);
            $sheet->setCellValue("I{$row}", $m['unit_cost']);
            $sheet->setCellValue("J{$row}", $m['total_cost']);
            $sheet->setCellValue("K{$row}", $m['notes']);

            $sheet->getStyle("F{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("I{$row}:J{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Summary Totals
        $sheet->setCellValue("E{$row}", 'TOTAL MUTASI & SALDO AKHIR:');
        $sheet->setCellValue("F{$row}", $report['total_in']);
        $sheet->setCellValue("G{$row}", $report['total_out']);
        $sheet->setCellValue("H{$row}", $report['closing_balance']);
        $sheet->getStyle("A{$row}:K{$row}")->getFont()->setBold(true);
        $sheet->getStyle("F{$row}:H{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("A{$row}:K{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');

        $sheet->getStyle("A7:K{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = "Kartu_Stok_{$product->sku}_{$report['start_date']}_sampai_{$report['end_date']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export Excel Low Stock Alert.
     */
    protected function exportLowStockExcel(Company $company, array $report, ?Store $store): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Peringatan Stok Minimum');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDC2626']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko";
        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', 'LAPORAN PERINGATAN STOK MINIMUM & ESTIMASI HABIS');
        $sheet->setCellValue('A3', "Posisi Per: " . now()->format('d/m/Y H:i') . " | {$storeText}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'SKU', 'Nama Barang', 'Kategori', 'Supplier Rekomendasi', 'Cabang', 'Status Stok', 'Stok Fisik Saat Ini', 'Batas Minimum', 'Satuan', 'Rekomendasi Order (PO)', 'Estimasi Modal Restock (Rp)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}5", $h);
        }
        $sheet->getStyle('A5:L5')->applyFromArray($headerStyle);

        $row = 6;
        $no = 1;
        foreach ($report['all_alerts'] as $item) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $item['sku']);
            $sheet->setCellValue("C{$row}", $item['product_name']);
            $sheet->setCellValue("D{$row}", $item['category']);
            $sheet->setCellValue("E{$row}", $item['supplier_name']);
            $sheet->setCellValue("F{$row}", $item['store_name']);
            $sheet->setCellValue("G{$row}", $item['alert_type'] === 'OUT_OF_STOCK' ? 'HABIS / KOSONG' : 'DI BAWAH MINIMUM');
            $sheet->setCellValue("H{$row}", $item['physical_qty']);
            $sheet->setCellValue("I{$row}", $item['min_stock']);
            $sheet->setCellValue("J{$row}", $item['base_unit']);
            $sheet->setCellValue("K{$row}", $item['recommended_restock_qty']);
            $sheet->setCellValue("L{$row}", $item['estimated_restock_cost']);

            $sheet->getStyle("H{$row}:I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K{$row}:L{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        $sheet->getStyle("A5:L" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = "Peringatan_Stok_Minimum_" . now()->format('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export Excel Analisa Perputaran Barang (Turnover).
     */
    protected function exportTurnoverExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // 1. Sheet 1: Fast Moving
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Fast Moving');
        $sheet1->setCellValue('A1', 'PRODUK FAST MOVING (PERPUTARAN CEPAT)');
        $sheet1->setCellValue('A2', "Periode: {$period['label']}");
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'SKU', 'Nama Produk', 'Kategori', 'Cabang', 'Qty Terjual', 'Satuan', 'Omzet Penjualan (Rp)', 'Sisa Stok Fisik'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headers as $i => $h) {
            $sheet1->setCellValue("{$cols[$i]}4", $h);
        }
        $sheet1->getStyle('A4:I4')->applyFromArray($headerStyle);

        $row = 5;
        $no = 1;
        foreach ($report['fast_moving'] as $item) {
            $sheet1->setCellValue("A{$row}", $no++);
            $sheet1->setCellValue("B{$row}", $item['sku']);
            $sheet1->setCellValue("C{$row}", $item['product_name']);
            $sheet1->setCellValue("D{$row}", $item['category']);
            $sheet1->setCellValue("E{$row}", $item['store_name']);
            $sheet1->setCellValue("F{$row}", $item['qty_sold']);
            $sheet1->setCellValue("G{$row}", $item['base_unit']);
            $sheet1->setCellValue("H{$row}", $item['revenue']);
            $sheet1->setCellValue("I{$row}", $item['physical_qty']);
            $sheet1->getStyle("F{$row}:I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        $sheet1->getStyle("A4:I" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet1->getColumnDimension($c)->setAutoSize(true);
        }

        // 2. Sheet 2: Dead Stock
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Dead Stock');
        $sheet2->setCellValue('A1', 'DEAD STOCK (BARANG TIDAK LAKU / MODAL MENGENDAP)');
        $sheet2->setCellValue('A2', "Total Nilai Modal Mengendap: Rp " . number_format($report['dead_stock_value'], 0, ',', '.'));
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet2->getStyle('A2')->getFont()->setBold(true)->setColor(new Color('FFDC2626'));

        $deadHeaders = ['No', 'SKU', 'Nama Produk', 'Kategori', 'Cabang', 'Stok Fisik Mengendap', 'Satuan', 'Total Nilai Modal Mengendap (Rp)'];
        $colsDead = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($deadHeaders as $i => $h) {
            $sheet2->setCellValue("{$colsDead[$i]}4", $h);
        }
        $sheet2->getStyle('A4:H4')->applyFromArray($headerStyle);

        $rowDead = 5;
        $noDead = 1;
        foreach ($report['dead_stock'] as $item) {
            $sheet2->setCellValue("A{$rowDead}", $noDead++);
            $sheet2->setCellValue("B{$rowDead}", $item['sku']);
            $sheet2->setCellValue("C{$rowDead}", $item['product_name']);
            $sheet2->setCellValue("D{$rowDead}", $item['category']);
            $sheet2->setCellValue("E{$rowDead}", $item['store_name']);
            $sheet2->setCellValue("F{$rowDead}", $item['physical_qty']);
            $sheet2->setCellValue("G{$rowDead}", $item['base_unit']);
            $sheet2->setCellValue("H{$rowDead}", $item['current_stock_value']);
            $sheet2->getStyle("F{$rowDead}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet2->getStyle("H{$rowDead}")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowDead++;
        }
        $sheet2->getStyle("A4:H" . ($rowDead - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($colsDead as $c) {
            $sheet2->getColumnDimension($c)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = "Analisa_Perputaran_Barang_{$period['start_date']}_sampai_{$period['end_date']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export Excel Expiry Date.
     */
    protected function exportExpiryExcel(Company $company, array $report, ?Store $store, int $withinDays): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kedaluwarsa Barang');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFB91C1C']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', "LAPORAN KEDALUWARSA BARANG (SISA WAKTU <= {$withinDays} HARI)");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'No. Batch', 'SKU', 'Nama Barang', 'Kategori', 'Cabang', 'Tanggal Expired', 'Sisa Hari', 'Status Risiko', 'Qty Tersisa', 'Satuan', 'Potensi Kerugian (Rp)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}4", $h);
        }
        $sheet->getStyle('A4:L4')->applyFromArray($headerStyle);

        $allBatches = $report['expired']->merge($report['critical'])->merge($report['warning'])->merge($report['caution']);
        $row = 5;
        $no = 1;
        foreach ($allBatches as $item) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $item['batch_number']);
            $sheet->setCellValue("C{$row}", $item['sku']);
            $sheet->setCellValue("D{$row}", $item['product_name']);
            $sheet->setCellValue("E{$row}", $item['category']);
            $sheet->setCellValue("F{$row}", $item['store_name']);
            $sheet->setCellValue("G{$row}", $item['expired_date']);
            $sheet->setCellValue("H{$row}", $item['days_left'] . ' Hari');
            $sheet->setCellValue("I{$row}", $item['status']);
            $sheet->setCellValue("J{$row}", $item['current_qty']);
            $sheet->setCellValue("K{$row}", $item['base_unit']);
            $sheet->setCellValue("L{$row}", $item['potential_loss']);
            $sheet->getStyle("J{$row}:L{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        $sheet->getStyle("A4:L" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = "Rekap_Kedaluwarsa_Barang_" . now()->format('Ymd_His') . ".xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export Excel Selisih Opname.
     */
    protected function exportOpnameDiscrepancyExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Selisih Opname');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', 'LAPORAN SELISIH STOCK OPNAME & AUDIT SHRINKAGE');
        $sheet->setCellValue('A3', "Periode: {$period['label']}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'No. Dokumen Opname', 'Tanggal', 'Cabang', 'SKU', 'Nama Barang', 'Qty Sistem', 'Qty Fisik', 'Selisih Qty', 'Tipe Selisih', 'HPP (Rp)', 'Nilai Selisih / Kerugian (Rp)', 'Status'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}5", $h);
        }
        $sheet->getStyle('A5:M5')->applyFromArray($headerStyle);

        $row = 6;
        $no = 1;
        foreach ($report['items'] as $item) {
            $sheet->setCellValue("A{$row}", $no++);
            $sheet->setCellValue("B{$row}", $item['opname_number']);
            $sheet->setCellValue("C{$row}", $item['opname_date']);
            $sheet->setCellValue("D{$row}", $item['store_name']);
            $sheet->setCellValue("E{$row}", $item['sku']);
            $sheet->setCellValue("F{$row}", $item['product_name']);
            $sheet->setCellValue("G{$row}", $item['system_qty']);
            $sheet->setCellValue("H{$row}", $item['actual_qty']);
            $sheet->setCellValue("I{$row}", $item['discrepancy_qty']);
            $sheet->setCellValue("J{$row}", $item['type']);
            $sheet->setCellValue("K{$row}", $item['unit_cost']);
            $sheet->setCellValue("L{$row}", $item['discrepancy_value']);
            $sheet->setCellValue("M{$row}", $item['status']);

            $sheet->getStyle("G{$row}:I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K{$row}:L{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        $sheet->getStyle("A5:M" . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = "Selisih_Stock_Opname_{$period['start_date']}_sampai_{$period['end_date']}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
