<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\OpeningBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpeningBalanceController extends Controller
{
    public function __construct(
        protected OpeningBalanceService $openingService
    ) {}

    /**
     * ========================================================
     * 1. SALDO AWAL PERKIRAAN (COA OPENING BALANCE)
     * ========================================================
     */

    public function coaIndex(): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $accounts = $this->openingService->getCoaOpeningBalances($company);

        $totalDebit = $accounts->sum('opening_debit');
        $totalCredit = $accounts->sum('opening_credit');
        $difference = round(abs($totalDebit - $totalCredit), 2);

        // Cari tanggal jurnal saldo awal yang tersimpan (jika ada)
        $existingJournal = \App\Models\JournalEntry::where('company_id', $company->id)
            ->where('reference_type', 'OPENING_BALANCE')
            ->where('reference_number', 'SALDO-AWAL-COA')
            ->first();

        $cutoffDate = $existingJournal ? $existingJournal->entry_date->toDateString() : date('Y-01-01');

        return view('accounting.opening.coa', compact(
            'company',
            'accounts',
            'totalDebit',
            'totalCredit',
            'difference',
            'cutoffDate'
        ));
    }

    public function coaStore(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'cutoff_date' => 'required|date',
            'balances' => 'required|array',
            'balances.*.debit' => 'nullable|numeric|min:0',
            'balances.*.credit' => 'nullable|numeric|min:0',
        ]);

        try {
            $journal = $this->openingService->saveCoaOpeningBalance(
                company: $company,
                cutoffDate: $validated['cutoff_date'],
                balances: $validated['balances'],
                user: Auth::user()
            );

            return redirect()->route('accounting.opening.coa.index')
                ->with('success', "Saldo Awal Perkiraan berhasil disimpan & dibukukan seimbang (No. Jurnal: {$journal->entry_number}).");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * ========================================================
     * 2. SALDO AWAL PIUTANG PELANGGAN (OPENING AR)
     * ========================================================
     */

    public function receivablesIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;

        $receivables = $this->openingService->getCustomerOpeningReceivables($company, $store);
        $totalReceivable = $receivables->sum('remaining_receivable');

        $customers = Customer::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        return view('accounting.opening.receivables', compact(
            'company',
            'receivables',
            'totalReceivable',
            'customers',
            'stores',
            'storeId'
        ));
    }

    public function receivablesStore(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'invoice_number' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $tx = $this->openingService->recordCustomerOpeningReceivable($company, $validated, Auth::user());

            return redirect()->route('accounting.opening.receivables.index')
                ->with('success', "Saldo Awal Piutang pelanggan {$tx->customer?->name} sebesar Rp ".number_format($tx->grand_total, 0, ',', '.')." berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function receivablesDestroy(string $id): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        try {
            $this->openingService->deleteCustomerOpeningReceivable($company, $id);
            return redirect()->route('accounting.opening.receivables.index')
                ->with('success', 'Saldo Awal Piutang berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download Template Excel Saldo Awal Piutang Pelanggan (.xlsx).
     */
    public function receivablesTemplate(): StreamedResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $customers = Customer::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->take(25)->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Saldo Awal Piutang');

        // Banner Judul & Petunjuk
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT SALDO AWAL PIUTANG PELANGGAN - SIGMA ERP');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Petunjuk: 1. Kolom KODE_PELANGGAN atau NAMA_PELANGGAN wajib diisi. 2. NILAI_PIUTANG hanya angka tanpa titik/koma rupiah. 3. Format Tanggal: YYYY-MM-DD.');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
        $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

        $headers = ['KODE_PELANGGAN', 'NAMA_PELANGGAN', 'NO_FAKTUR_NOTA', 'TANGGAL_NOTA', 'JATUH_TEMPO', 'NILAI_PIUTANG', 'KETERANGAN'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $idx => $h) {
            $cell = $cols[$idx].'4';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }

        $rowIdx = 5;
        if ($customers->isNotEmpty()) {
            foreach ($customers as $c) {
                $sheet->setCellValue('A'.$rowIdx, $c->code);
                $sheet->setCellValue('B'.$rowIdx, $c->name);
                $sheet->setCellValue('C'.$rowIdx, 'NOTA-'.date('Ym').'-'.str_pad($rowIdx - 4, 3, '0', STR_PAD_LEFT));
                $sheet->setCellValue('D'.$rowIdx, now()->subDays(15)->format('Y-m-d'));
                $sheet->setCellValue('E'.$rowIdx, now()->addDays(15)->format('Y-m-d'));
                $sheet->setCellValue('F'.$rowIdx, 0);
                $sheet->setCellValue('G'.$rowIdx, 'Saldo awal piutang nota tempo migrasi');
                $sheet->getStyle('F'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $rowIdx++;
            }
        } else {
            $sheet->setCellValue('A5', 'CUST-001');
            $sheet->setCellValue('B5', 'Toko Berkah Mandiri');
            $sheet->setCellValue('C5', 'NOTA-2026-001');
            $sheet->setCellValue('D5', '2026-10-01');
            $sheet->setCellValue('E5', '2026-10-31');
            $sheet->setCellValue('F5', 1500000);
            $sheet->setCellValue('G5', 'Saldo awal piutang nota tempo migrasi');
            $sheet->getStyle('F5')->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template-saldo-awal-piutang.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import Massal Saldo Awal Piutang Pelanggan dari File Excel / CSV.
     */
    public function receivablesImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360',
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = ! empty($request->input('store_id')) ? Store::where('company_id', $company->id)->find($request->input('store_id')) : Store::where('company_id', $company->id)->first();

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';

        try {
            $allRows = $this->extractRowsFromFile($filePath, $extension);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal membaca file Excel/CSV: '.$e->getMessage()]);
        }

        if (empty($allRows)) {
            return back()->withInput()->withErrors(['error' => 'File yang diunggah kosong atau tidak memiliki baris data.']);
        }

        // Cari Baris Header Kolom
        $headerRowIdx = null;
        $colIndex = [];

        foreach ($allRows as $rIdx => $row) {
            if (! is_array($row) || empty(array_filter($row))) {
                continue;
            }
            $cleanRow = array_map(function ($val) {
                return strtoupper(trim(str_replace([' ', '-', '_', '*', '(', ')', ':'], '', (string) $val)));
            }, $row);

            if (in_array('KODEPELANGGAN', $cleanRow) || in_array('NAMAPELANGGAN', $cleanRow) || in_array('NILAIPAIUTANG', $cleanRow) || in_array('NILAIPUTANG', $cleanRow) || in_array('NILAIPUTANG', $cleanRow) || in_array('NILAIPiutang', $cleanRow) || in_array('NILAIPUTANG', $cleanRow) || in_array('NILAIPUTANG', $cleanRow) || in_array('NILAIPUTANG', $cleanRow) || in_array('JUMLAHPIUTANG', $cleanRow) || in_array('PIUTANG', $cleanRow)) {
                $headerRowIdx = $rIdx;
                foreach ($cleanRow as $idx => $name) {
                    if ($name !== '') {
                        $colIndex[$name] = $idx;
                    }
                }
                break;
            }
        }

        if ($headerRowIdx === null) {
            return back()->withInput()->withErrors(['error' => 'Baris header tidak ditemukan. Pastikan terdapat kolom KODE_PELANGGAN, NAMA_PELANGGAN, dan NILAI_PIUTANG.']);
        }

        $getCol = function ($keys, $default = null) use ($colIndex) {
            foreach ($keys as $k) {
                if (isset($colIndex[$k])) {
                    return $colIndex[$k];
                }
            }
            return $default;
        };

        $idxCustCode = $getCol(['KODEPELANGGAN', 'KODECUST', 'KODE', 'CUSTOMERCODE', 'IDPELANGGAN'], 0);
        $idxCustName = $getCol(['NAMAPELANGGAN', 'NAMACUST', 'NAMA', 'CUSTOMERNAME'], 1);
        $idxInvoiceNo = $getCol(['NOFAKTURNOTA', 'NOFAKTUR', 'NONOTA', 'INVOICENUMBER', 'NOINVOICE', 'NOMORNOTA'], 2);
        $idxTxDate = $getCol(['TANGGALNOTA', 'TANGGALFAKTUR', 'TANGGAL', 'TRANSACTIONDATE', 'DATE'], 3);
        $idxDueDate = $getCol(['JATUHTEMPO', 'DUEDATE', 'TANGGALJATUHTEMPO'], 4);
        $idxAmount = $getCol(['NILAIPUTANG', 'NILAIPAIUTANG', 'NILAIPUTANG', 'NILAIPiutang', 'NILAIPUTANG', 'JUMLAHPIUTANG', 'PIUTANG', 'NOMINAL', 'SALDOAWAL', 'AMOUNT'], 5);
        $idxNotes = $getCol(['KETERANGAN', 'NOTES', 'CATATAN', 'MEMO'], 6);

        $cleanNumber = function ($val) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $v = str_replace(['Rp', 'rp', ' ', '.'], '', (string) $val);
            $v = str_replace(',', '.', $v);
            return (float) $v;
        };

        $successCount = 0;
        $totalAmount = 0.0;
        $errors = [];

        try {
            DB::transaction(function () use (
                $allRows, $headerRowIdx, $company, $store,
                $idxCustCode, $idxCustName, $idxInvoiceNo, $idxTxDate, $idxDueDate, $idxAmount, $idxNotes,
                $cleanNumber, &$successCount, &$totalAmount, &$errors
            ) {
                $totalRows = count($allRows);
                for ($i = $headerRowIdx + 1; $i < $totalRows; $i++) {
                    $row = $allRows[$i];
                    if (! is_array($row) || empty(array_filter($row))) {
                        continue;
                    }

                    $custCode = isset($row[$idxCustCode]) ? trim((string) $row[$idxCustCode]) : '';
                    $custName = isset($row[$idxCustName]) ? trim((string) $row[$idxCustName]) : '';
                    $amount = isset($row[$idxAmount]) ? $cleanNumber($row[$idxAmount]) : 0.0;

                    if ($amount <= 0) {
                        continue;
                    }

                    // Cari pelanggan berdasarkan kode atau nama
                    $customer = null;
                    if ($custCode) {
                        $customer = Customer::where('company_id', $company->id)->where('code', $custCode)->first();
                    }
                    if (! $customer && $custName) {
                        $customer = Customer::where('company_id', $company->id)
                            ->where(DB::raw('LOWER(name)'), strtolower($custName))
                            ->first();
                    }

                    // Jika pelanggan belum ada, otomatis buatkan master pelanggan baru
                    if (! $customer && $custName) {
                        $defaultTier = \App\Models\CustomerTier::where('company_id', $company->id)->first();
                        $customer = Customer::create([
                            'company_id' => $company->id,
                            'store_id' => $store?->id,
                            'customer_tier_id' => $defaultTier?->id,
                            'code' => $custCode ?: ('CUST-'.date('ymd').'-'.strtoupper(Str::random(3))),
                            'name' => $custName,
                            'credit_limit' => 50000000.00,
                            'current_credit_balance' => 0.00,
                            'credit_term_days' => 30,
                            'is_active' => true,
                        ]);
                    }

                    if (! $customer) {
                        $errors[] = "Baris #".($i + 1).": Data pelanggan tidak ditemukan dan nama pelanggan kosong.";
                        continue;
                    }

                    $rawInv = isset($row[$idxInvoiceNo]) ? trim((string) $row[$idxInvoiceNo]) : '';
                    $txDate = isset($row[$idxTxDate]) && strtotime((string) $row[$idxTxDate]) ? date('Y-m-d', strtotime((string) $row[$idxTxDate])) : now()->toDateString();
                    $dueDate = isset($row[$idxDueDate]) && strtotime((string) $row[$idxDueDate]) ? date('Y-m-d', strtotime((string) $row[$idxDueDate])) : date('Y-m-d', strtotime($txDate.' +30 days'));
                    $notes = isset($row[$idxNotes]) ? trim((string) $row[$idxNotes]) : 'Import massal saldo awal piutang';

                    $this->openingService->recordCustomerOpeningReceivable($company, [
                        'customer_id' => $customer->id,
                        'store_id' => $store?->id,
                        'amount' => $amount,
                        'invoice_number' => $rawInv,
                        'transaction_date' => $txDate,
                        'due_date' => $dueDate,
                        'notes' => $notes,
                    ], Auth::user());

                    $successCount++;
                    $totalAmount += $amount;
                }
            });

            $msg = "Berhasil mengimpor {$successCount} faktur Saldo Awal Piutang senilai Rp ".number_format($totalAmount, 0, ',', '.').".";
            if (! empty($errors)) {
                $msg .= ' Peringatan: '.implode(', ', array_slice($errors, 0, 3));
            }

            return redirect()->route('accounting.opening.receivables.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal memproses import data: '.$e->getMessage()]);
        }
    }

    /**
     * ========================================================
     * 3. SALDO AWAL HUTANG SUPPLIER (OPENING AP)
     * ========================================================
     */

    public function payablesIndex(Request $request): View
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $storeId = $request->input('store_id');
        $store = $storeId ? Store::find($storeId) : null;

        $payables = $this->openingService->getSupplierOpeningPayables($company, $store);
        $totalPayable = $payables->sum('grand_total');

        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        return view('accounting.opening.payables', compact(
            'company',
            'payables',
            'totalPayable',
            'suppliers',
            'stores',
            'storeId'
        ));
    }

    public function payablesStore(Request $request): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        $validated = $request->validate([
            'supplier_id' => 'required|uuid|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'invoice_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'store_id' => 'nullable|uuid|exists:stores,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $purchase = $this->openingService->recordSupplierOpeningPayable($company, $validated, Auth::user());

            return redirect()->route('accounting.opening.payables.index')
                ->with('success', "Saldo Awal Hutang ke supplier {$purchase->supplier?->name} sebesar Rp ".number_format($purchase->grand_total, 0, ',', '.')." berhasil dicatat.");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function payablesDestroy(string $id): RedirectResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();

        try {
            $this->openingService->deleteSupplierOpeningPayable($company, $id);
            return redirect()->route('accounting.opening.payables.index')
                ->with('success', 'Saldo Awal Hutang supplier berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download Template Excel Saldo Awal Hutang Supplier (.xlsx).
     */
    public function payablesTemplate(): StreamedResponse
    {
        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $suppliers = Supplier::where('company_id', $company->id)->where('is_active', true)->orderBy('name')->take(25)->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Saldo Awal Hutang');

        // Banner Judul & Petunjuk
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT SALDO AWAL HUTANG SUPPLIER - SIGMA ERP');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Petunjuk: 1. Kolom KODE_SUPPLIER atau NAMA_SUPPLIER wajib diisi. 2. NILAI_HUTANG hanya angka tanpa titik/koma rupiah. 3. Format Tanggal: YYYY-MM-DD.');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
        $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF1F5F9');

        $headers = ['KODE_SUPPLIER', 'NAMA_SUPPLIER', 'NO_FAKTUR_SUPPLIER', 'TANGGAL_FAKTUR', 'JATUH_TEMPO', 'NILAI_HUTANG', 'KETERANGAN'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        foreach ($headers as $idx => $h) {
            $cell = $cols[$idx].'4';
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
            $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F172A');
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        }

        $rowIdx = 5;
        if ($suppliers->isNotEmpty()) {
            foreach ($suppliers as $s) {
                $sheet->setCellValue('A'.$rowIdx, $s->code);
                $sheet->setCellValue('B'.$rowIdx, $s->name);
                $sheet->setCellValue('C'.$rowIdx, 'INV-SUP-'.date('Ym').'-'.str_pad($rowIdx - 4, 3, '0', STR_PAD_LEFT));
                $sheet->setCellValue('D'.$rowIdx, now()->subDays(15)->format('Y-m-d'));
                $sheet->setCellValue('E'.$rowIdx, now()->addDays(15)->format('Y-m-d'));
                $sheet->setCellValue('F'.$rowIdx, 0);
                $sheet->setCellValue('G'.$rowIdx, 'Saldo awal hutang faktur pembelian lama');
                $sheet->getStyle('F'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $rowIdx++;
            }
        } else {
            $sheet->setCellValue('A5', 'SUPP-001');
            $sheet->setCellValue('B5', 'PT Indofood Sukses Makmur');
            $sheet->setCellValue('C5', 'INV-SUP-2026-001');
            $sheet->setCellValue('D5', '2026-10-01');
            $sheet->setCellValue('E5', '2026-10-31');
            $sheet->setCellValue('F5', 3500000);
            $sheet->setCellValue('G5', 'Saldo awal hutang faktur pembelian lama');
            $sheet->getStyle('F5')->getNumberFormat()->setFormatCode('#,##0');
        }

        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'template-saldo-awal-hutang.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import Massal Saldo Awal Hutang Supplier dari File Excel / CSV.
     */
    public function payablesImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360',
            'store_id' => 'nullable|uuid|exists:stores,id',
        ]);

        $company = app(\App\Services\CompanyService::class)->getActiveCompany();
        $store = ! empty($request->input('store_id')) ? Store::where('company_id', $company->id)->find($request->input('store_id')) : Store::where('company_id', $company->id)->first();

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';

        try {
            $allRows = $this->extractRowsFromFile($filePath, $extension);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal membaca file Excel/CSV: '.$e->getMessage()]);
        }

        if (empty($allRows)) {
            return back()->withInput()->withErrors(['error' => 'File yang diunggah kosong atau tidak memiliki baris data.']);
        }

        // Cari Baris Header Kolom
        $headerRowIdx = null;
        $colIndex = [];

        foreach ($allRows as $rIdx => $row) {
            if (! is_array($row) || empty(array_filter($row))) {
                continue;
            }
            $cleanRow = array_map(function ($val) {
                return strtoupper(trim(str_replace([' ', '-', '_', '*', '(', ')', ':'], '', (string) $val)));
            }, $row);

            if (in_array('KODESUPPLIER', $cleanRow) || in_array('NAMASUPPLIER', $cleanRow) || in_array('NILAIHUTANG', $cleanRow) || in_array('JUMLAHHUTANG', $cleanRow) || in_array('HUTANG', $cleanRow) || in_array('SALDOHUTANG', $cleanRow)) {
                $headerRowIdx = $rIdx;
                foreach ($cleanRow as $idx => $name) {
                    if ($name !== '') {
                        $colIndex[$name] = $idx;
                    }
                }
                break;
            }
        }

        if ($headerRowIdx === null) {
            return back()->withInput()->withErrors(['error' => 'Baris header tidak ditemukan. Pastikan terdapat kolom KODE_SUPPLIER, NAMA_SUPPLIER, dan NILAI_HUTANG.']);
        }

        $getCol = function ($keys, $default = null) use ($colIndex) {
            foreach ($keys as $k) {
                if (isset($colIndex[$k])) {
                    return $colIndex[$k];
                }
            }
            return $default;
        };

        $idxSuppCode = $getCol(['KODESUPPLIER', 'KODESUPP', 'KODE', 'SUPPLIERCODE', 'IDSUPPLIER'], 0);
        $idxSuppName = $getCol(['NAMASUPPLIER', 'NAMASUPP', 'NAMA', 'SUPPLIERNAME'], 1);
        $idxInvoiceNo = $getCol(['NOFAKTURSUPPLIER', 'NOFAKTUR', 'INVOICENUMBER', 'NOINVOICE', 'NOMORFAKTUR'], 2);
        $idxTxDate = $getCol(['TANGGALFAKTUR', 'TANGGALPEMBELIAN', 'TANGGAL', 'PURCHASEDATE', 'DATE'], 3);
        $idxDueDate = $getCol(['JATUHTEMPO', 'DUEDATE', 'TANGGALJATUHTEMPO'], 4);
        $idxAmount = $getCol(['NILAIHUTANG', 'JUMLAHHUTANG', 'HUTANG', 'NOMINAL', 'SALDOAWAL', 'AMOUNT'], 5);
        $idxNotes = $getCol(['KETERANGAN', 'NOTES', 'CATATAN', 'MEMO'], 6);

        $cleanNumber = function ($val) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $v = str_replace(['Rp', 'rp', ' ', '.'], '', (string) $val);
            $v = str_replace(',', '.', $v);
            return (float) $v;
        };

        $successCount = 0;
        $totalAmount = 0.0;
        $errors = [];

        try {
            DB::transaction(function () use (
                $allRows, $headerRowIdx, $company, $store,
                $idxSuppCode, $idxSuppName, $idxInvoiceNo, $idxTxDate, $idxDueDate, $idxAmount, $idxNotes,
                $cleanNumber, &$successCount, &$totalAmount, &$errors
            ) {
                $totalRows = count($allRows);
                for ($i = $headerRowIdx + 1; $i < $totalRows; $i++) {
                    $row = $allRows[$i];
                    if (! is_array($row) || empty(array_filter($row))) {
                        continue;
                    }

                    $suppCode = isset($row[$idxSuppCode]) ? trim((string) $row[$idxSuppCode]) : '';
                    $suppName = isset($row[$idxSuppName]) ? trim((string) $row[$idxSuppName]) : '';
                    $amount = isset($row[$idxAmount]) ? $cleanNumber($row[$idxAmount]) : 0.0;

                    if ($amount <= 0) {
                        continue;
                    }

                    // Cari supplier berdasarkan kode atau nama
                    $supplier = null;
                    if ($suppCode) {
                        $supplier = Supplier::where('company_id', $company->id)->where('code', $suppCode)->first();
                    }
                    if (! $supplier && $suppName) {
                        $supplier = Supplier::where('company_id', $company->id)
                            ->where(DB::raw('LOWER(name)'), strtolower($suppName))
                            ->first();
                    }

                    // Jika supplier belum ada, otomatis buatkan master supplier baru
                    if (! $supplier && $suppName) {
                        $supplier = Supplier::create([
                            'company_id' => $company->id,
                            'code' => $suppCode ?: ('SUPP-'.date('ymd').'-'.strtoupper(Str::random(3))),
                            'name' => $suppName,
                            'term_of_payment_days' => 30,
                            'is_active' => true,
                        ]);
                    }

                    if (! $supplier) {
                        $errors[] = "Baris #".($i + 1).": Data supplier tidak ditemukan dan nama supplier kosong.";
                        continue;
                    }

                    $rawInv = isset($row[$idxInvoiceNo]) ? trim((string) $row[$idxInvoiceNo]) : '';
                    $txDate = isset($row[$idxTxDate]) && strtotime((string) $row[$idxTxDate]) ? date('Y-m-d', strtotime((string) $row[$idxTxDate])) : now()->toDateString();
                    $dueDate = isset($row[$idxDueDate]) && strtotime((string) $row[$idxDueDate]) ? date('Y-m-d', strtotime((string) $row[$idxDueDate])) : date('Y-m-d', strtotime($txDate.' +30 days'));
                    $notes = isset($row[$idxNotes]) ? trim((string) $row[$idxNotes]) : 'Import massal saldo awal hutang';

                    $this->openingService->recordSupplierOpeningPayable($company, [
                        'supplier_id' => $supplier->id,
                        'store_id' => $store?->id,
                        'amount' => $amount,
                        'invoice_number' => $rawInv,
                        'purchase_date' => $txDate,
                        'due_date' => $dueDate,
                        'notes' => $notes,
                    ], Auth::user());

                    $successCount++;
                    $totalAmount += $amount;
                }
            });

            $msg = "Berhasil mengimpor {$successCount} faktur Saldo Awal Hutang senilai Rp ".number_format($totalAmount, 0, ',', '.').".";
            if (! empty($errors)) {
                $msg .= ' Peringatan: '.implode(', ', array_slice($errors, 0, 3));
            }

            return redirect()->route('accounting.opening.payables.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal memproses import data: '.$e->getMessage()]);
        }
    }

    /**
     * Ekstraksi baris data dari file Excel (.xlsx, .xls) atau CSV secara fleksibel.
     *
     * @return array<int, array<int, mixed>>
     */
    protected function extractRowsFromFile(string $filePath, string $extension): array
    {
        $extension = strtolower($extension);

        if (in_array($extension, ['xlsx', 'xls', 'ods'])) {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            return $sheet->toArray(null, true, true, false);
        }

        // CSV fallback
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return $rows;
        }

        while (($data = fgetcsv($handle, 4096, ',')) !== false) {
            if (count($data) === 1 && str_contains($data[0], ';')) {
                $data = str_getcsv($data[0], ';');
            }
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }
}
