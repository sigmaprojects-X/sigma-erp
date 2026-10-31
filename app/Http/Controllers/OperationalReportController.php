<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\FinancialReportService;
use App\Services\OperationalAuditReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperationalReportController extends Controller
{
    public function __construct(
        protected OperationalAuditReportService $auditService
    ) {}

    /**
     * 1. Laporan Analisa Jam Sibuk & Produktivitas Kasir (Peak Hours Heatmap).
     */
    public function peakHours(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->auditService->getHourlyPeakHoursReport($company, $startDate, $endDate, $store);

        if ($request->input('export') === 'xlsx') {
            return $this->exportPeakHoursExcel($company, $report, $store, $period);
        }

        return view('reports.operational.peak_hours', compact('report', 'stores', 'company', 'storeId', 'period'));
    }

    /**
     * 2. Laporan Audit Pembatalan Kasir & Retur (Void & Returns).
     */
    public function voidReturns(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $report = $this->auditService->getVoidReturnAuditReport($company, $startDate, $endDate, $store);

        if ($request->input('export') === 'xlsx') {
            return $this->exportVoidReturnsExcel($company, $report, $store, $period);
        }

        return view('reports.operational.void_returns', compact('report', 'stores', 'company', 'storeId', 'period'));
    }

    /**
     * 3. Laporan Penjualan Titip Jual Konsinyasi (UMKM / Non-PKP).
     */
    public function consignment(Request $request): View|StreamedResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $period = FinancialReportService::resolvePeriodDates($request);
        $startDate = $period['start_date'];
        $endDate = $period['end_date'];
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;
        $supplierId = $request->input('supplier_id');

        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();
        $suppliers = Supplier::where('company_id', $company->id)->orderBy('name')->get();

        $report = $this->auditService->getConsignmentSalesReport($company, $startDate, $endDate, $store, $supplierId);

        if ($request->input('export') === 'xlsx') {
            return $this->exportConsignmentExcel($company, $report, $store, $period);
        }

        return view('reports.operational.consignment', compact('report', 'stores', 'suppliers', 'company', 'storeId', 'supplierId', 'period'));
    }

    /**
     * Export Excel Jam Sibuk & Produktivitas Kasir.
     */
    protected function exportPeakHoursExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // Sheet 1: Distribusi Per Jam
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Analisa Jam Sibuk');

        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko (Konsolidasi)";
        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', 'LAPORAN ANALISA JAM SIBUK & PRODUKTIVITAS TRANSAKSI (PEAK HOURS)');
        $sheet->setCellValue('A3', "Periode: {$period['label']} ({$report['start_date']} s/d {$report['end_date']}) | {$storeText}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['Rentang Jam', 'Jumlah Transaksi', 'Total Omzet (Rp)', 'Rata-rata Keranjang / Basket Size (Rp)', 'Porsi Transaksi (%)'];
        $cols = ['A', 'B', 'C', 'D', 'E'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}5", $h);
        }
        $sheet->getStyle('A5:E5')->applyFromArray($headerStyle);

        $rowNum = 6;
        foreach ($report['hourly'] as $h) {
            $sheet->setCellValue("A{$rowNum}", $h['hour_label']);
            $sheet->setCellValue("B{$rowNum}", $h['tx_count']);
            $sheet->setCellValue("C{$rowNum}", $h['turnover']);
            $sheet->setCellValue("D{$rowNum}", $h['avg_basket_size']);
            $sheet->setCellValue("E{$rowNum}", $h['share_percent'] . '%');
            $rowNum++;
        }

        // Sheet 2: Distribusi Hari dalam Seminggu
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Distribusi Hari');
        $sheet2->setCellValue('A1', strtoupper($company->name));
        $sheet2->setCellValue('A2', 'DISTRIBUSI TRANSAKSI PER HARI DALAM SEMINGGU');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $hDay = ['Hari', 'Jumlah Transaksi', 'Total Omzet (Rp)', 'Rata-rata Keranjang (Rp)'];
        $cDay = ['A', 'B', 'C', 'D'];
        foreach ($hDay as $idx => $hd) {
            $sheet2->setCellValue("{$cDay[$idx]}4", $hd);
        }
        $sheet2->getStyle('A4:D4')->applyFromArray($headerStyle);

        $rDay = 5;
        foreach ($report['daily'] as $d) {
            $sheet2->setCellValue("A{$rDay}", $d['day_name']);
            $sheet2->setCellValue("B{$rDay}", $d['tx_count']);
            $sheet2->setCellValue("C{$rDay}", $d['turnover']);
            $sheet2->setCellValue("D{$rDay}", $d['avg_basket_size']);
            $rDay++;
        }

        foreach ([$sheet, $sheet2] as $s) {
            foreach (range('A', 'E') as $col) {
                $s->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $filename = 'Laporan_Jam_Sibuk_Transaski_' . date('Ymd_His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Excel Audit Pembatalan Kasir (Void) & Retur.
     */
    protected function exportVoidReturnsExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // Sheet 1: Rincian Insiden Audit
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Audit Void & Retur');

        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko (Konsolidasi)";
        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', 'LAPORAN AUDIT PEMBATALAN STRUK (VOID) & RETUR PENJUALAN KASIR');
        $sheet->setCellValue('A3', "Periode: {$period['label']} ({$report['start_date']} s/d {$report['end_date']}) | {$storeText}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'Tipe Insiden', 'Waktu Kejadian', 'Cabang', 'No. Referensi (Invoice/Retur)', 'Kasir Pelaksana', 'Otorisator (Manager)', 'Nilai Nominal (Rp)', 'Rincian Item', 'Alasan / Keterangan'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}5", $h);
        }
        $sheet->getStyle('A5:J5')->applyFromArray($headerStyle);

        $rowNum = 6;
        foreach ($report['rows'] as $idx => $r) {
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValue("B{$rowNum}", $r['type']);
            $sheet->setCellValue("C{$rowNum}", $r['datetime']);
            $sheet->setCellValue("D{$rowNum}", $r['store_name']);
            $sheet->setCellValue("E{$rowNum}", $r['reference_number']);
            $sheet->setCellValue("F{$rowNum}", $r['cashier_name']);
            $sheet->setCellValue("G{$rowNum}", $r['authorizer_name']);
            $sheet->setCellValue("H{$rowNum}", $r['amount']);
            $sheet->setCellValue("I{$rowNum}", $r['item_summary']);
            $sheet->setCellValue("J{$rowNum}", $r['reason']);
            $rowNum++;
        }

        // Sheet 2: Rekapitulasi Kasir
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rekap Risiko Kasir');
        $sheet2->setCellValue('A1', strtoupper($company->name));
        $sheet2->setCellValue('A2', 'REKAPITULASI INSIDEN VOID & RETUR PER KASIR');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $hCashier = ['Nama Kasir', 'Jumlah Void', 'Total Nilai Void (Rp)', 'Jumlah Retur', 'Total Nilai Retur (Rp)', 'Total Insiden'];
        $cCashier = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($hCashier as $ci => $hc) {
            $sheet2->setCellValue("{$cCashier[$ci]}4", $hc);
        }
        $sheet2->getStyle('A4:F4')->applyFromArray($headerStyle);

        $rC = 5;
        foreach ($report['cashier_summary'] as $cs) {
            $sheet2->setCellValue("A{$rC}", $cs['cashier_name']);
            $sheet2->setCellValue("B{$rC}", $cs['void_count']);
            $sheet2->setCellValue("C{$rC}", $cs['void_amount']);
            $sheet2->setCellValue("D{$rC}", $cs['return_count']);
            $sheet2->setCellValue("E{$rC}", $cs['return_amount']);
            $sheet2->setCellValue("F{$rC}", $cs['total_incidents']);
            $rC++;
        }

        foreach ([$sheet, $sheet2] as $s) {
            foreach (range('A', 'J') as $col) {
                $s->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $filename = 'Laporan_Audit_Void_Retur_' . date('Ymd_His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Excel Laporan Penjualan Konsinyasi UMKM.
     */
    protected function exportConsignmentExcel(Company $company, array $report, ?Store $store, array $period): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // Sheet 1: Rincian Barang Terjual
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penjualan Konsinyasi per Item');

        $storeText = $store ? "Cabang: {$store->name}" : "Semua Cabang Toko (Konsolidasi)";
        $sheet->setCellValue('A1', strtoupper($company->name));
        $sheet->setCellValue('A2', 'LAPORAN PENJUALAN TITIP JUAL KONSINYASI (UMKM / SUPPLIER NON-PKP)');
        $sheet->setCellValue('A3', "Periode: {$period['label']} ({$report['start_date']} s/d {$report['end_date']}) | {$storeText}");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['No', 'SKU', 'Nama Barang', 'Supplier Titip / UMKM', 'Kategori', 'Cabang', 'Qty Terjual', 'Satuan', 'Harga Jual (Rp)', 'Total Omzet Kotor (Rp)', 'Biaya Setor UMKM (Rp)', 'Komisi Bagi Hasil Toko (Rp)', 'Margin Toko (%)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue("{$cols[$i]}5", $h);
        }
        $sheet->getStyle('A5:M5')->applyFromArray($headerStyle);

        $rowNum = 6;
        foreach ($report['items'] as $idx => $it) {
            $sheet->setCellValue("A{$rowNum}", $idx + 1);
            $sheet->setCellValue("B{$rowNum}", $it['sku']);
            $sheet->setCellValue("C{$rowNum}", $it['product_name']);
            $sheet->setCellValue("D{$rowNum}", $it['supplier_name']);
            $sheet->setCellValue("E{$rowNum}", $it['category']);
            $sheet->setCellValue("F{$rowNum}", $it['store_name']);
            $sheet->setCellValue("G{$rowNum}", $it['qty_sold']);
            $sheet->setCellValue("H{$rowNum}", $it['base_unit']);
            $sheet->setCellValue("I{$rowNum}", $it['selling_price']);
            $sheet->setCellValue("J{$rowNum}", $it['gross_revenue']);
            $sheet->setCellValue("K{$rowNum}", $it['supplier_payable']);
            $sheet->setCellValue("L{$rowNum}", $it['store_commission']);
            $sheet->setCellValue("M{$rowNum}", $it['commission_percent'] . '%');
            $rowNum++;
        }

        // Sheet 2: Rekapitulasi per Supplier UMKM
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rekapitulasi per UMKM');
        $sheet2->setCellValue('A1', strtoupper($company->name));
        $sheet2->setCellValue('A2', 'REKAPITULASI BAGI HASIL PENJUALAN PER SUPPLIER UMKM');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet2->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $hSup = ['Nama UMKM / Supplier', 'No. Telepon / HP', 'Total Qty Terjual', 'Total Omzet (Rp)', 'Wajib Disetor ke UMKM (Rp)', 'Komisi Laba Toko (Rp)', 'Persentase Bagi Hasil (%)'];
        $cSup = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($hSup as $si => $hs) {
            $sheet2->setCellValue("{$cSup[$si]}4", $hs);
        }
        $sheet2->getStyle('A4:G4')->applyFromArray($headerStyle);

        $rSup = 5;
        foreach ($report['suppliers'] as $sp) {
            $sheet2->setCellValue("A{$rSup}", $sp['supplier_name']);
            $sheet2->setCellValue("B{$rSup}", $sp['phone']);
            $sheet2->setCellValue("C{$rSup}", $sp['total_qty']);
            $sheet2->setCellValue("D{$rSup}", $sp['gross_revenue']);
            $sheet2->setCellValue("E{$rSup}", $sp['supplier_payable']);
            $sheet2->setCellValue("F{$rSup}", $sp['store_commission']);
            $sheet2->setCellValue("G{$rSup}", $sp['commission_percent'] . '%');
            $rSup++;
        }

        foreach ([$sheet, $sheet2] as $s) {
            foreach (range('A', 'M') as $col) {
                $s->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $filename = 'Laporan_Penjualan_Konsinyasi_UMKM_' . date('Ymd_His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
