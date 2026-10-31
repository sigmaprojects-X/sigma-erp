<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PosTransaction;
use App\Models\Purchase;
use App\Models\TaxConfiguration;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxManagementController extends Controller
{
    /**
     * Tampilkan Halaman Pengaturan Perpajakan (PKP / Non-PKP).
     */
    public function settings(Request $request): View
    {
        $user = $request->user();
        $company = $user?->store?->company ?? Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $taxConfig = TaxConfiguration::where('company_id', $company->id)
            ->latest('effective_from')
            ->first();

        if (!$taxConfig) {
            $taxConfig = new TaxConfiguration([
                'company_id' => $company->id,
                'is_tax_enabled' => (bool) ($company->is_tax_registered ?? false),
                'tax_name' => 'PPN',
                'tax_rate_percent' => 11.00,
                'is_tax_inclusive' => false,
                'effective_from' => now()->startOfYear()->toDateString(),
            ]);
        }

        return view('accounting.tax.settings', compact('company', 'taxConfig'));
    }

    /**
     * Simpan Perubahan Pengaturan Perpajakan.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tax_id' => ['nullable', 'string', 'max:35'],
            'is_tax_enabled' => ['nullable', 'boolean'],
            'tax_name' => ['required', 'string', 'max:50'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_tax_inclusive' => ['nullable', 'boolean'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $user = $request->user();
        $company = $user?->store?->company ?? Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        DB::transaction(function () use ($request, $company) {
            $isTaxEnabled = $request->boolean('is_tax_enabled');

            // 1. Update status PKP & NPWP perusahaan
            $company->update([
                'tax_id' => $request->input('tax_id'),
                'is_tax_registered' => $isTaxEnabled,
                'tax_mode' => $isTaxEnabled ? ($request->boolean('is_tax_inclusive') ? 'INCLUSIVE' : 'EXCLUSIVE') : 'NON_TAX',
                'default_tax_rate' => (float) $request->input('tax_rate_percent', 11.00),
            ]);

            // 2. Simpan atau perbarui konfigurasi pajak aktif
            TaxConfiguration::updateOrCreate(
                [
                    'company_id' => $company->id,
                ],
                [
                    'is_tax_enabled' => $isTaxEnabled,
                    'tax_name' => $request->input('tax_name', 'PPN'),
                    'tax_rate_percent' => (float) $request->input('tax_rate_percent', 11.00),
                    'is_tax_inclusive' => $request->boolean('is_tax_inclusive'),
                    'effective_from' => $request->input('effective_from'),
                    'effective_to' => $request->input('effective_to'),
                ]
            );
        });

        return redirect()->route('accounting.tax.settings')
            ->with('status', 'Pengaturan Perpajakan (PKP & PPN) berhasil diperbarui.');
    }

    /**
     * Laporan Rekapitulasi Pajak PPN (SPT Masa PPN).
     */
    public function report(Request $request): View|StreamedResponse
    {
        $user = $request->user();
        $company = $user?->store?->company ?? Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        // Default rentang tanggal: Bulan berjalan
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // Ambil Data Penjualan (PPN Keluaran)
        $salesQuery = PosTransaction::query()
            ->where('company_id', $company->id)
            ->where('status', '!=', 'VOID')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->with(['customer', 'store'])
            ->orderBy('created_at', 'asc');

        $allSales = $salesQuery->get();
        $taxableSales = $allSales->filter(fn ($s) => (float) $s->tax_amount > 0);
        $nonTaxableSales = $allSales->filter(fn ($s) => (float) $s->tax_amount <= 0);

        $totalDppSales = (float) $taxableSales->sum('subtotal');
        $totalPpnKeluaran = (float) $taxableSales->sum('tax_amount');
        $totalNonTaxSales = (float) $nonTaxableSales->sum('subtotal');
        $grandTotalSales = (float) $allSales->sum('grand_total');

        // Ambil Data Pembelian (PPN Masukan)
        $purchasesQuery = Purchase::query()
            ->where('company_id', $company->id)
            ->where('status', '!=', 'CANCELLED')
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->with(['supplier', 'store'])
            ->orderBy('purchase_date', 'asc');

        $allPurchases = $purchasesQuery->get();
        $taxablePurchases = $allPurchases->filter(fn ($p) => (float) $p->tax_amount > 0);
        $nonTaxablePurchases = $allPurchases->filter(fn ($p) => (float) $p->tax_amount <= 0);

        $totalDppPurchases = (float) $taxablePurchases->sum('subtotal');
        $totalPpnMasukan = (float) $taxablePurchases->sum('tax_amount');
        $totalNonTaxPurchases = (float) $nonTaxablePurchases->sum('subtotal');
        $grandTotalPurchases = (float) $allPurchases->sum('grand_total');

        // Penghitungan PPN Kurang / (Lebih) Bayar
        $netPpn = round($totalPpnKeluaran - $totalPpnMasukan, 2);
        $statusPpn = $netPpn > 0 ? 'KURANG_BAYAR' : ($netPpn < 0 ? 'LEBIH_BAYAR' : 'NIHIL');

        if ($request->query('export') === 'xlsx') {
            return $this->exportTaxReportExcel(
                company: $company,
                startDate: $startDate,
                endDate: $endDate,
                taxableSales: $taxableSales,
                taxablePurchases: $taxablePurchases,
                totalDppSales: $totalDppSales,
                totalPpnKeluaran: $totalPpnKeluaran,
                totalNonTaxSales: $totalNonTaxSales,
                grandTotalSales: $grandTotalSales,
                totalDppPurchases: $totalDppPurchases,
                totalPpnMasukan: $totalPpnMasukan,
                totalNonTaxPurchases: $totalNonTaxPurchases,
                grandTotalPurchases: $grandTotalPurchases,
                netPpn: $netPpn,
                statusPpn: $statusPpn
            );
        }

        return view('accounting.tax.report', compact(
            'company',
            'startDate',
            'endDate',
            'allSales',
            'taxableSales',
            'nonTaxableSales',
            'allPurchases',
            'taxablePurchases',
            'nonTaxablePurchases',
            'totalDppSales',
            'totalPpnKeluaran',
            'totalNonTaxSales',
            'grandTotalSales',
            'totalDppPurchases',
            'totalPpnMasukan',
            'totalNonTaxPurchases',
            'grandTotalPurchases',
            'netPpn',
            'statusPpn'
        ));
    }

    /**
     * Ekspor Rekap SPT Masa PPN ke Format Excel Resmi (.xlsx).
     */
    protected function exportTaxReportExcel(
        Company $company,
        string $startDate,
        string $endDate,
        $taxableSales,
        $taxablePurchases,
        float $totalDppSales,
        float $totalPpnKeluaran,
        float $totalNonTaxSales,
        float $grandTotalSales,
        float $totalDppPurchases,
        float $totalPpnMasukan,
        float $totalNonTaxPurchases,
        float $grandTotalPurchases,
        float $netPpn,
        string $statusPpn
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();

        // ----------------------------------------------------
        // SHEET 1: REKAPITULASI SPT MASA PPN
        // ----------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekap SPT PPN');

        // Header Perusahaan
        $sheet1->setCellValue('A1', strtoupper($company->name));
        $sheet1->setCellValue('A2', 'REKAPITULASI SPT MASA PPN (PAJAK PERTAMBAHAN NILAI)');
        $sheet1->setCellValue('A3', "NPWP: " . ($company->tax_id ?: '00.000.000.0-000.000') . " | Status: " . ($company->is_tax_registered ? 'PKP' : 'NON-PKP'));
        $sheet1->setCellValue('A4', "Periode Pajak: {$startDate} s/d {$endDate}");

        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet1->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet1->getStyle('A3:A4')->getFont()->setSize(10)->setColor(new Color('FF64748B'));

        // Tabel Ringkasan Komputasi
        $sheet1->setCellValue('A6', 'BAGIAN');
        $sheet1->setCellValue('B6', 'URAIAN PENGHITUNGAN PAJAK');
        $sheet1->setCellValue('C6', 'DPP (DASAR PENGENAAN PAJAK)');
        $sheet1->setCellValue('D6', 'JUMLAH PPN (RP)');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet1->getStyle('A6:D6')->applyFromArray($headerStyle);

        $row = 7;
        // I. Penyerahan BKP / JKP (PPN Keluaran)
        $sheet1->setCellValue("A{$row}", 'I');
        $sheet1->setCellValue("B{$row}", 'PENYERAHAN BKP / JKP (PAJAK KELUARAN)');
        $sheet1->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet1->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row++;

        $sheet1->setCellValue("B{$row}", 'a. Penyerahan Terutang PPN (Faktur Pajak Keluaran)');
        $sheet1->setCellValue("C{$row}", $totalDppSales);
        $sheet1->setCellValue("D{$row}", $totalPpnKeluaran);
        $sheet1->getStyle("C{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $sheet1->setCellValue("B{$row}", 'b. Penyerahan Tidak Terutang PPN / Dibebaskan');
        $sheet1->setCellValue("C{$row}", $totalNonTaxSales);
        $sheet1->setCellValue("D{$row}", 0);
        $sheet1->getStyle("C{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $sheet1->setCellValue("B{$row}", 'JUMLAH PAJAK KELUARAN (I.a)');
        $sheet1->setCellValue("D{$row}", $totalPpnKeluaran);
        $sheet1->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet1->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet1->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $row += 2;

        // II. Perolehan BKP / JKP (PPN Masukan)
        $sheet1->setCellValue("A{$row}", 'II');
        $sheet1->setCellValue("B{$row}", 'PEROLEHAN BKP / JKP (PAJAK MASUKAN)');
        $sheet1->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet1->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row++;

        $sheet1->setCellValue("B{$row}", 'a. Perolehan Terutang PPN (Faktur Pajak Masukan Dapat Dikreditkan)');
        $sheet1->setCellValue("C{$row}", $totalDppPurchases);
        $sheet1->setCellValue("D{$row}", $totalPpnMasukan);
        $sheet1->getStyle("C{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $sheet1->setCellValue("B{$row}", 'b. Perolehan Tanpa PPN / Non-PKP');
        $sheet1->setCellValue("C{$row}", $totalNonTaxPurchases);
        $sheet1->setCellValue("D{$row}", 0);
        $sheet1->getStyle("C{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $row++;

        $sheet1->setCellValue("B{$row}", 'JUMLAH PAJAK MASUKAN (II.a)');
        $sheet1->setCellValue("D{$row}", $totalPpnMasukan);
        $sheet1->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet1->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet1->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E7FF');
        $row += 2;

        // III. Penghitungan PPN Kurang / Lebih Bayar
        $sheet1->setCellValue("A{$row}", 'III');
        $sheet1->setCellValue("B{$row}", 'PENGHITUNGAN PPN KURANG / (LEBIH) BAYAR');
        $sheet1->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet1->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        $row++;

        $statusText = match ($statusPpn) {
            'KURANG_BAYAR' => 'PPN KURANG BAYAR (Disetor ke Kas Negara via SSE Pajak)',
            'LEBIH_BAYAR' => 'PPN LEBIH BAYAR (Dapat Dikompensasikan ke Masa Pajak Berikutnya)',
            default => 'PPN NIHIL',
        };

        $sheet1->setCellValue("B{$row}", $statusText);
        $sheet1->setCellValue("D{$row}", abs($netPpn));
        $sheet1->getStyle("A{$row}:D{$row}")->getFont()->setBold(true)->setSize(11);
        $sheet1->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');

        $bgColor = match ($statusPpn) {
            'KURANG_BAYAR' => 'FFFEE2E2', // light red
            'LEBIH_BAYAR' => 'FFDCFCE7', // light green
            default => 'FFF3F4F6',
        };
        $sheet1->getStyle("A{$row}:D{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);

        $sheet1->getStyle("A6:D{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        // ----------------------------------------------------
        // SHEET 2: DAFTAR FAKTUR PAJAK KELUARAN (SALES)
        // ----------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Faktur Pajak Keluaran');

        $sheet2->setCellValue('A1', 'DAFTAR FAKTUR PAJAK KELUARAN (PENJUALAN POS)');
        $sheet2->setCellValue('A2', "Periode: {$startDate} s/d {$endDate}");
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersSales = ['No', 'Tanggal', 'No. Invoice', 'Toko / Cabang', 'Pelanggan', 'Mode PPN', 'DPP (Rp)', 'PPN (Rp)', 'Total (Rp)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($headersSales as $idx => $header) {
            $sheet2->setCellValue("{$cols[$idx]}4", $header);
        }
        $sheet2->getStyle('A4:I4')->applyFromArray($headerStyle);

        $sRow = 5;
        $sNo = 1;
        foreach ($taxableSales as $tx) {
            $sheet2->setCellValue("A{$sRow}", $sNo++);
            $sheet2->setCellValue("B{$sRow}", Carbon::parse($tx->created_at)->format('d/m/Y H:i'));
            $sheet2->setCellValue("C{$sRow}", $tx->invoice_number);
            $sheet2->setCellValue("D{$sRow}", $tx->store?->name ?? '-');
            $sheet2->setCellValue("E{$sRow}", $tx->customer?->name ?? 'Pelanggan Umum');
            $sheet2->setCellValue("F{$sRow}", $tx->tax_mode ?? 'INC');
            $sheet2->setCellValue("G{$sRow}", (float) $tx->subtotal);
            $sheet2->setCellValue("H{$sRow}", (float) $tx->tax_amount);
            $sheet2->setCellValue("I{$sRow}", (float) $tx->grand_total);

            $sheet2->getStyle("G{$sRow}:I{$sRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sRow++;
        }

        if ($taxableSales->isEmpty()) {
            $sheet2->setCellValue('A5', 'Tidak ada data faktur pajak keluaran pada periode ini.');
            $sheet2->mergeCells('A5:I5');
            $sRow = 6;
        } else {
            // Row Total
            $sheet2->setCellValue("E{$sRow}", 'TOTAL:');
            $sheet2->setCellValue("G{$sRow}", $totalDppSales);
            $sheet2->setCellValue("H{$sRow}", $totalPpnKeluaran);
            $sheet2->setCellValue("I{$sRow}", $grandTotalSales);
            $sheet2->getStyle("A{$sRow}:I{$sRow}")->getFont()->setBold(true);
            $sheet2->getStyle("G{$sRow}:I{$sRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet2->getStyle("A{$sRow}:I{$sRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        }

        $sheet2->getStyle("A4:I{$sRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ----------------------------------------------------
        // SHEET 3: DAFTAR FAKTUR PAJAK MASUKAN (PURCHASES)
        // ----------------------------------------------------
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Faktur Pajak Masukan');

        $sheet3->setCellValue('A1', 'DAFTAR FAKTUR PAJAK MASUKAN (PEMBELIAN BARANG)');
        $sheet3->setCellValue('A2', "Periode: {$startDate} s/d {$endDate}");
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $headersPurchases = ['No', 'Tanggal', 'No. Faktur Supplier', 'No. Ref Internal', 'Nama Supplier', 'NPWP Supplier', 'DPP (Rp)', 'PPN Masukan (Rp)', 'Total (Rp)'];
        foreach ($headersPurchases as $idx => $header) {
            $sheet3->setCellValue("{$cols[$idx]}4", $header);
        }
        $sheet3->getStyle('A4:I4')->applyFromArray($headerStyle);

        $pRow = 5;
        $pNo = 1;
        foreach ($taxablePurchases as $p) {
            $sheet3->setCellValue("A{$pRow}", $pNo++);
            $sheet3->setCellValue("B{$pRow}", Carbon::parse($p->purchase_date)->format('d/m/Y'));
            $sheet3->setCellValue("C{$pRow}", $p->invoice_number);
            $sheet3->setCellValue("D{$pRow}", $p->reference_number ?? '-');
            $sheet3->setCellValue("E{$pRow}", $p->supplier?->name ?? '-');
            $sheet3->setCellValue("F{$pRow}", $p->supplier?->npwp ?? '-');
            $sheet3->setCellValue("G{$pRow}", (float) $p->subtotal);
            $sheet3->setCellValue("H{$pRow}", (float) $p->tax_amount);
            $sheet3->setCellValue("I{$pRow}", (float) $p->grand_total);

            $sheet3->getStyle("G{$pRow}:I{$pRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $pRow++;
        }

        if ($taxablePurchases->isEmpty()) {
            $sheet3->setCellValue('A5', 'Tidak ada data faktur pajak masukan pada periode ini.');
            $sheet3->mergeCells('A5:I5');
            $pRow = 6;
        } else {
            // Row Total
            $sheet3->setCellValue("E{$pRow}", 'TOTAL:');
            $sheet3->setCellValue("G{$pRow}", $totalDppPurchases);
            $sheet3->setCellValue("H{$pRow}", $totalPpnMasukan);
            $sheet3->setCellValue("I{$pRow}", $grandTotalPurchases);
            $sheet3->getStyle("A{$pRow}:I{$pRow}")->getFont()->setBold(true);
            $sheet3->getStyle("G{$pRow}:I{$pRow}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet3->getStyle("A{$pRow}:I{$pRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');
        }

        $sheet3->getStyle("A4:I{$pRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($cols as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        // Kembali ke sheet pertama saat dibuka di Excel
        $spreadsheet->setActiveSheetIndex(0);

        $filename = "Rekap_SPT_Masa_PPN_{$startDate}_sampai_{$endDate}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
