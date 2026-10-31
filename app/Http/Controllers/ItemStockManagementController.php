<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\Unit;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemStockManagementController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected AccountingService $accountingService
    ) {}

    /**
     * ========================================================
     * 1. SALDO AWAL ITEM (OPENING STOCK)
     * ========================================================
     */

    public function openingStockIndex(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        $categories = ProductCategory::where('company_id', $company->id)->orderBy('name')->get();

        $query = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->with([
                'category',
                'baseUnit',
                'stocks' => fn ($s) => $s->where('store_id', $selectedStore->id),
            ]);

        // Filter Pencarian
        if ($q = trim($request->get('q', ''))) {
            $term = '%'.strtolower($q).'%';
            $query->where(function ($w) use ($term) {
                $w->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$term]);
            });
        }

        // Filter Kategori
        if ($catId = $request->get('category_id')) {
            $query->where('category_id', $catId);
        }

        $products = $query->orderBy('name')->paginate(25)->withQueryString();

        // Hitung Summary Statistik Saldo Awal di Cabang Ini
        $totalItemsWithStock = ProductStock::where('company_id', $company->id)
            ->where('store_id', $selectedStore->id)
            ->where('physical_quantity', '>', 0)
            ->count();

        $totalPhysicalQty = ProductStock::where('company_id', $company->id)
            ->where('store_id', $selectedStore->id)
            ->sum('physical_quantity');

        $totalValuation = DB::table('product_stocks as ps')
            ->join('products as p', 'ps.product_id', '=', 'p.id')
            ->where('ps.company_id', $company->id)
            ->where('ps.store_id', $selectedStore->id)
            ->select(DB::raw('SUM(ps.physical_quantity * p.cost_price) as val'))
            ->value('val') ?? 0.0;

        return view('warehouse.opening_stock.index', compact(
            'company',
            'stores',
            'selectedStore',
            'categories',
            'products',
            'totalItemsWithStock',
            'totalPhysicalQty',
            'totalValuation'
        ));
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

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return $rows;
        }

        if (str_starts_with($firstLine, "\xEF\xBB\xBF")) {
            $firstLine = substr($firstLine, 3);
        }

        $delimiter = str_contains($firstLine, ';') ? ';' : ',';
        $rows[] = str_getcsv(trim($firstLine), $delimiter);

        while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
            $rows[] = $data;
        }

        fclose($handle);
        return $rows;
    }

    public function openingStockStore(Request $request): RedirectResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $validated = $request->validate([
            'store_id' => 'required|uuid|exists:stores,id',
            'product_id' => 'required|uuid|exists:products,id',
            'quantity' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'min_stock_alert' => 'nullable|numeric|min:0',
            'rack_location' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $store = Store::where('company_id', $company->id)->findOrFail($validated['store_id']);
        $product = Product::where('company_id', $company->id)->findOrFail($validated['product_id']);

        try {
            DB::transaction(function () use ($company, $store, $product, $validated) {
                $refNumber = 'SA-ITEM-'.date('Ymd').'-'.strtoupper(Str::random(4));

                $this->inventoryService->recordOpeningStock(
                    product: $product,
                    store: $store,
                    quantity: (float) $validated['quantity'],
                    costPrice: (float) $validated['cost_price'],
                    referenceNumber: $refNumber,
                    notes: $validated['notes'] ?? 'Input manual saldo awal item',
                    userId: Auth::id(),
                    minStock: isset($validated['min_stock_alert']) ? (float) $validated['min_stock_alert'] : null,
                    rackLocation: $validated['rack_location'] ?? null
                );

                // Buat jurnal akuntansi saldo awal persediaan
                $openingValue = (float) $validated['quantity'] * (float) $validated['cost_price'];
                if ($openingValue > 0) {
                    $inventoryAccount = ChartOfAccount::firstOrCreate(
                        ['company_id' => $company->id, 'code' => '1005-001'],
                        ['name' => 'Persediaan Barang Dagang', 'type' => 'ASSET', 'normal_balance' => 'DEBIT', 'is_active' => true]
                    );

                    $equityAccount = ChartOfAccount::firstOrCreate(
                        ['company_id' => $company->id, 'code' => '3001-001'],
                        ['name' => 'Modal Pemilik', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT', 'is_active' => true]
                    );

                    $this->accountingService->createJournalEntry(
                        company: $company,
                        referenceType: 'OPENING_BALANCE',
                        referenceNumber: $refNumber,
                        description: "Saldo Awal Persediaan [{$product->name}] Cabang {$store->name}",
                        lines: [
                            [
                                'account' => $inventoryAccount,
                                'debit' => $openingValue,
                                'credit' => 0.00,
                                'memo' => "Saldo Awal Persediaan {$product->sku}",
                            ],
                            [
                                'account' => $equityAccount,
                                'debit' => 0.00,
                                'credit' => $openingValue,
                                'memo' => "Modal Ekuitas Saldo Awal {$product->sku}",
                            ],
                        ],
                        store: $store,
                        userId: Auth::id()
                    );
                }
            });

            return redirect()->route('warehouse.opening-stock.index', ['store_id' => $store->id])
                ->with('success', "Saldo awal untuk {$product->name} berhasil diperbarui (Qty: {$validated['quantity']} {$product->baseUnit?->unit_code}, HPP: Rp ".number_format($validated['cost_price'], 0, ',', '.').").");
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function openingStockImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360',
            'store_id' => 'required|uuid|exists:stores,id',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();
        $store = Store::where('company_id', $company->id)->findOrFail($request->input('store_id'));

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';

        try {
            $allRows = $this->extractRowsFromFile($filePath, $extension);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal membaca file Excel/CSV: ' . $e->getMessage()]);
        }

        if (empty($allRows)) {
            return back()->withInput()->withErrors(['error' => 'File Excel/CSV yang diunggah kosong atau tidak memiliki baris data.']);
        }

        // Temukan baris header secara dinamis (mengabaikan baris banner judul/petunjuk)
        $headerRowIdx = null;
        $colIndex = [];

        foreach ($allRows as $rIdx => $row) {
            if (! is_array($row) || empty(array_filter($row))) {
                continue;
            }
            $cleanRow = array_map(function ($val) {
                return strtoupper(trim(str_replace([' ', '-', '_', '*', '(', ')', ':'], '', (string) $val)));
            }, $row);

            if (in_array('SKU', $cleanRow) || in_array('KODESKU', $cleanRow) || in_array('KODEITEM', $cleanRow) || in_array('KODE', $cleanRow)) {
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
            return back()->withInput()->withErrors(['error' => 'Baris header kolom tidak ditemukan. Pastikan kolom memuat KODE_SKU, NAMA_ITEM, SATUAN, JUMLAH_SALDO_AWAL, dan HPP_SATUAN.']);
        }

        $getCol = function ($keys, $default = null) use ($colIndex) {
            foreach ($keys as $k) {
                if (isset($colIndex[$k])) {
                    return $colIndex[$k];
                }
            }
            return $default;
        };

        $idxSku = $getCol(['KODESKU', 'SKU', 'KODE', 'KODEBARANG', 'KODEITEM'], 0);
        $idxName = $getCol(['NAMAITEM', 'NAMABARANG', 'NAMAPRODUK', 'NAMA'], 1);
        $idxUnit = $getCol(['SATUAN', 'UOM', 'UNIT', 'SATUANDASAR'], 2);
        $idxQty = $getCol(['JUMLAHSALDOAWAL', 'QTYSALDOAWAL', 'SALDOAWAL', 'QTY', 'JUMLAH', 'STOKAWAL'], 3);
        $idxHpp = $getCol(['HPPSATUAN', 'HARGAPOKOK', 'HPP', 'HARGABELI', 'COSTPRICE'], 4);
        $idxMinStock = $getCol(['STOKMINIMUM', 'MINSTOK', 'MINSTOCK', 'STOKMINIMAL', 'MINSTOCKALERT'], 5);
        $idxRack = $getCol(['LOKASIRAK', 'RAK', 'RACKLOCATION'], 6);

        $rowCount = 0;
        $successCount = 0;
        $totalBatchValue = 0.0;
        $errors = [];

        $cleanNumber = function ($val) {
            if ($val === null || $val === '') return 0.0;
            if (is_numeric($val)) return (float) $val;
            $v = str_replace(['Rp', 'rp', ' ', '.'], '', (string) $val);
            $v = str_replace(',', '.', $v);
            return (float) $v;
        };

        try {
            DB::transaction(function () use (
                $allRows, $headerRowIdx, $company, $store,
                $idxSku, $idxName, $idxUnit, $idxQty, $idxHpp, $idxMinStock, $idxRack,
                $cleanNumber, &$rowCount, &$successCount, &$totalBatchValue, &$errors
            ) {
                $batchRef = 'SA-BATCH-'.date('Ymd').'-'.strtoupper(Str::random(4));

                $totalRows = count($allRows);
                for ($i = $headerRowIdx + 1; $i < $totalRows; $i++) {
                    $row = $allRows[$i];
                    $rowCount++;
                    if (! is_array($row) || empty(array_filter($row))) {
                        continue;
                    }

                    $sku = isset($row[$idxSku]) ? strtoupper(trim((string) $row[$idxSku])) : '';
                    $name = isset($row[$idxName]) ? trim((string) $row[$idxName]) : '';
                    $unitCode = isset($row[$idxUnit]) ? strtoupper(trim((string) $row[$idxUnit])) : 'PCS';
                    $qty = isset($row[$idxQty]) ? $cleanNumber($row[$idxQty]) : 0.0;
                    $costPrice = isset($row[$idxHpp]) ? $cleanNumber($row[$idxHpp]) : 0.0;
                    $minStock = isset($row[$idxMinStock]) && is_numeric($row[$idxMinStock]) ? (float) $row[$idxMinStock] : null;
                    $rackLocation = isset($row[$idxRack]) ? trim((string) $row[$idxRack]) : null;

                    if (empty($sku)) {
                        continue; // Lewati baris kosong
                    }

                    // Cari atau buat produk jika belum ada
                    $product = Product::where('company_id', $company->id)->where('sku', $sku)->first();
                    if (! $product) {
                        $product = Product::create([
                            'company_id' => $company->id,
                            'store_id' => $store->id,
                            'sku' => $sku,
                            'name' => $name ?: 'Produk ' . $sku,
                            'cost_price' => $costPrice,
                            'min_stock_alert' => $minStock ?? 5.0,
                            'rack_location' => $rackLocation,
                            'is_active' => true,
                            'is_point_of_sale' => true,
                        ]);

                        ProductUnit::firstOrCreate(
                            ['company_id' => $company->id, 'product_id' => $product->id, 'unit_code' => $unitCode ?: 'PCS'],
                            ['unit_name' => $unitCode ?: 'PCS', 'conversion_factor' => 1.00, 'is_base_unit' => true, 'is_active' => true]
                        );
                    }

                    $this->inventoryService->recordOpeningStock(
                        product: $product,
                        store: $store,
                        quantity: $qty,
                        costPrice: $costPrice,
                        referenceNumber: $batchRef,
                        notes: "Import Massal Saldo Awal Excel [Baris #{$rowCount}]",
                        userId: Auth::id(),
                        minStock: $minStock,
                        rackLocation: $rackLocation
                    );

                    $totalBatchValue += ($qty * $costPrice);
                    $successCount++;
                }

                // Terbitkan Jurnal Akuntansi Saldo Awal Gabungan Batch
                if ($totalBatchValue > 0) {
                    $inventoryAccount = ChartOfAccount::firstOrCreate(
                        ['company_id' => $company->id, 'code' => '1005-001'],
                        ['name' => 'Persediaan Barang Dagang', 'type' => 'ASSET', 'normal_balance' => 'DEBIT', 'is_active' => true]
                    );

                    $equityAccount = ChartOfAccount::firstOrCreate(
                        ['company_id' => $company->id, 'code' => '3001-001'],
                        ['name' => 'Modal Pemilik', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT', 'is_active' => true]
                    );

                    $this->accountingService->createJournalEntry(
                        company: $company,
                        referenceType: 'OPENING_BALANCE',
                        referenceNumber: $batchRef,
                        description: "Import Saldo Awal Persediaan Cabang {$store->name} ({$successCount} item)",
                        lines: [
                            [
                                'account' => $inventoryAccount,
                                'debit' => $totalBatchValue,
                                'credit' => 0.00,
                                'memo' => "Total Valuasi Persediaan Awal ({$successCount} item)",
                            ],
                            [
                                'account' => $equityAccount,
                                'debit' => 0.00,
                                'credit' => $totalBatchValue,
                                'memo' => "Total Modal Awal Persediaan Migrasi",
                            ],
                        ],
                        store: $store,
                        userId: Auth::id()
                    );
                }
            });

            $msg = "Alhamdulillah, berhasil mengimpor {$successCount} item saldo awal ke cabang {$store->name} (Total Valuasi: Rp ".number_format($totalBatchValue, 0, ',', '.').").";
            if (! empty($errors)) {
                $msg .= " Catatan: " . implode('; ', array_slice($errors, 0, 3));
            }

            return redirect()->route('warehouse.opening-stock.index', ['store_id' => $store->id])
                ->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal memproses file Excel: ' . $e->getMessage()]);
        }
    }

    public function downloadOpeningStockTemplate(): StreamedResponse
    {
        $filename = 'template-saldo-awal-item.xlsx';

        return response()->streamDownload(function () {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Saldo Awal Item');

            // 1. Title Banner
            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', 'SIGMA ERP — TEMPLATE SALDO AWAL PERSEDIAAN ITEM');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(32);

            // 2. Petunjuk Pengisian
            $sheet->mergeCells('A2:G2');
            $sheet->setCellValue('A2', 'PETUNJUK: 1. Kolom bertanda (*) WAJIB diisi. 2. Jangan ubah nama baris header (Baris 4). 3. Simpan file tetap dalam format Excel (.xlsx) lalu unggah pada menu "Import Massal".');
            $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('1E40AF');
            $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
            $sheet->getStyle('A2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(2)->setRowHeight(24);

            // 3. Header Kolom di Baris 4
            $headers = [
                'A4' => 'KODE_SKU (*)',
                'B4' => 'NAMA_ITEM (*)',
                'C4' => 'SATUAN (*)',
                'D4' => 'JUMLAH_SALDO_AWAL (*)',
                'E4' => 'HPP_SATUAN (*)',
                'F4' => 'STOK_MINIMUM',
                'G4' => 'LOKASI_RAK',
            ];

            foreach ($headers as $cell => $text) {
                $sheet->setCellValue($cell, $text);
            }

            $sheet->getStyle('A4:G4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A4:G4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
            $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(4)->setRowHeight(26);

            // 4. Baris Contoh Data Nyata
            $sampleData = [
                ['PRD-BERAS-01', 'Beras Ramos Super 5kg', 'ZAK', 50, 68000, 10, 'RAK-A1'],
                ['PRD-MINYAK-01', 'Minyak Goreng Sania 2L', 'POUCH', 120, 32500, 24, 'RAK-B2'],
                ['PRD-GULA-01', 'Gula Pasir Gulaku 1kg', 'PCS', 80, 16000, 15, 'RAK-C1'],
                ['PRD-MIE-01', 'Indomie Goreng Original 85g', 'DUS', 45, 112000, 10, 'RAK-D1'],
                ['PRD-TELUR-01', 'Telur Ayam Negeri 1kg', 'KG', 65, 27000, 20, 'RAK-A3'],
            ];

            $rowIdx = 5;
            foreach ($sampleData as $row) {
                $sheet->setCellValue('A'.$rowIdx, $row[0]);
                $sheet->setCellValue('B'.$rowIdx, $row[1]);
                $sheet->setCellValue('C'.$rowIdx, $row[2]);
                $sheet->setCellValue('D'.$rowIdx, $row[3]);
                $sheet->setCellValue('E'.$rowIdx, $row[4]);
                $sheet->setCellValue('F'.$rowIdx, $row[5]);
                $sheet->setCellValue('G'.$rowIdx, $row[6]);

                $sheet->getStyle('C'.$rowIdx)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('E'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G'.$rowIdx)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                $sheet->getRowDimension($rowIdx)->setRowHeight(20);
                $rowIdx++;
            }

            $tableRange = 'A4:G'.($rowIdx - 1);
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * ========================================================
     * 2. STOK MINIMUM ITEM & REORDER POINT
     * ========================================================
     */

    public function minimumStockIndex(Request $request): View
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
        $stores = Store::where('company_id', $company->id)->where('is_active', true)->get();

        $selectedStoreId = $request->get('store_id', Auth::user()?->store_id ?? $stores->first()?->id);
        $selectedStore = $stores->firstWhere('id', $selectedStoreId) ?? $stores->first();

        $categories = ProductCategory::where('company_id', $company->id)->orderBy('name')->get();
        $status = $request->get('status', 'ALL'); // ALL, CRITICAL, OUT_OF_STOCK, SAFE

        $query = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->with([
                'category',
                'baseUnit',
                'stocks' => fn ($s) => $s->where('store_id', $selectedStore->id),
            ]);

        // Filter Pencarian
        if ($q = trim($request->get('q', ''))) {
            $term = '%'.strtolower($q).'%';
            $query->where(function ($w) use ($term) {
                $w->whereRaw('LOWER(name) LIKE ?', [$term])
                  ->orWhereRaw('LOWER(sku) LIKE ?', [$term]);
            });
        }

        // Filter Kategori
        if ($catId = $request->get('category_id')) {
            $query->where('category_id', $catId);
        }

        $allProducts = $query->orderBy('name')->get();

        // Hitung status stok untuk setiap item
        $enriched = $allProducts->map(function ($p) use ($selectedStore) {
            $stock = $p->stocks->first();
            $physical = (float) ($stock?->physical_quantity ?? 0);
            $minAlert = (float) $p->min_stock_alert;
            $maxStock = (float) ($p->max_stock ?? ($minAlert > 0 ? $minAlert * 3 : 20));

            $isOutOfStock = ($physical <= 0);
            $isCritical = (! $isOutOfStock && $physical <= $minAlert);
            $isSafe = (! $isOutOfStock && ! $isCritical);

            $recommendedRestock = max(0, $maxStock - $physical);

            $p->current_physical_stock = $physical;
            $p->is_out_of_stock = $isOutOfStock;
            $p->is_critical = $isCritical;
            $p->is_safe = $isSafe;
            $p->recommended_restock_qty = $recommendedRestock;

            return $p;
        });

        // KPI Counts
        $totalMonitored = $enriched->count();
        $totalOutOfStock = $enriched->where('is_out_of_stock', true)->count();
        $totalCritical = $enriched->where('is_critical', true)->count();
        $totalSafe = $enriched->where('is_safe', true)->count();

        // Filter status
        if ($status === 'CRITICAL') {
            $filtered = $enriched->filter(fn ($p) => $p->is_critical || $p->is_out_of_stock);
        } elseif ($status === 'OUT_OF_STOCK') {
            $filtered = $enriched->filter(fn ($p) => $p->is_out_of_stock);
        } elseif ($status === 'SAFE') {
            $filtered = $enriched->filter(fn ($p) => $p->is_safe);
        } else {
            $filtered = $enriched;
        }

        // Manual pagination dari Collection
        $page = (int) $request->get('page', 1);
        $perPage = 25;
        $items = $filtered->slice(($page - 1) * $perPage, $perPage)->values();
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('warehouse.minimum_stock.index', compact(
            'company',
            'stores',
            'selectedStore',
            'categories',
            'products',
            'status',
            'totalMonitored',
            'totalCritical',
            'totalOutOfStock',
            'totalSafe'
        ));
    }

    public function minimumStockUpdate(Request $request): RedirectResponse
    {
        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();

        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'min_stock_alert' => 'required|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
        ]);

        $product = Product::where('company_id', $company->id)->findOrFail($validated['product_id']);

        $product->update([
            'min_stock_alert' => $validated['min_stock_alert'],
            'max_stock' => $validated['max_stock'] ?? null,
        ]);

        return back()->with('success', "Batas stok minimum untuk {$product->name} berhasil diperbarui menjadi {$validated['min_stock_alert']} {$product->baseUnit?->unit_code}.");
    }

    public function minimumStockImport(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360',
        ]);

        $company = Company::where('code', 'SIGMA-HO')->first() ?? Company::firstOrFail();

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';

        try {
            $allRows = $this->extractRowsFromFile($filePath, $extension);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Gagal membaca file Excel/CSV: ' . $e->getMessage()]);
        }

        if (empty($allRows)) {
            return back()->withInput()->withErrors(['error' => 'File Excel/CSV yang diunggah kosong atau tidak memiliki baris data.']);
        }

        $headerRowIdx = null;
        $colIndex = [];

        foreach ($allRows as $rIdx => $row) {
            if (! is_array($row) || empty(array_filter($row))) {
                continue;
            }
            $cleanRow = array_map(function ($val) {
                return strtoupper(trim(str_replace([' ', '-', '_', '*', '(', ')', ':'], '', (string) $val)));
            }, $row);

            if (in_array('SKU', $cleanRow) || in_array('KODESKU', $cleanRow) || in_array('KODEITEM', $cleanRow) || in_array('KODE', $cleanRow)) {
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
            return back()->withInput()->withErrors(['error' => 'Baris header kolom tidak ditemukan. Pastikan kolom memuat KODE_SKU dan STOK_MINIMUM.']);
        }

        $getCol = function ($keys, $default = null) use ($colIndex) {
            foreach ($keys as $k) {
                if (isset($colIndex[$k])) {
                    return $colIndex[$k];
                }
            }
            return $default;
        };

        $idxSku = $getCol(['KODESKU', 'SKU', 'KODE', 'KODEBARANG', 'KODEITEM'], 0);
        $idxMin = $getCol(['STOKMINIMUM', 'MINSTOK', 'MINSTOCK', 'STOKMINIMAL', 'MINSTOCKALERT'], 1);
        $idxMax = $getCol(['STOKMAKSIMUM', 'MAXSTOK', 'MAXSTOCK'], 2);

        $rowCount = 0;
        $successCount = 0;
        $errors = [];

        $cleanNumber = function ($val) {
            if ($val === null || $val === '') return null;
            if (is_numeric($val)) return (float) $val;
            $v = str_replace(['Rp', 'rp', ' ', '.'], '', (string) $val);
            $v = str_replace(',', '.', $v);
            return is_numeric($v) ? (float) $v : null;
        };

        DB::transaction(function () use (
            $allRows, $headerRowIdx, $company, $idxSku, $idxMin, $idxMax,
            $cleanNumber, &$rowCount, &$successCount, &$errors
        ) {
            $totalRows = count($allRows);
            for ($i = $headerRowIdx + 1; $i < $totalRows; $i++) {
                $row = $allRows[$i];
                $rowCount++;
                if (! is_array($row) || empty(array_filter($row))) {
                    continue;
                }

                $sku = isset($row[$idxSku]) ? strtoupper(trim((string) $row[$idxSku])) : '';
                $minStock = isset($row[$idxMin]) ? $cleanNumber($row[$idxMin]) : null;
                $maxStock = isset($row[$idxMax]) ? $cleanNumber($row[$idxMax]) : null;

                if (empty($sku)) {
                    continue;
                }

                if ($minStock === null) {
                    $errors[] = "Baris #{$rowCount}: Nilai stok minimum untuk SKU [{$sku}] tidak valid.";
                    continue;
                }

                $product = Product::where('company_id', $company->id)->where('sku', $sku)->first();
                if (! $product) {
                    $errors[] = "Baris #{$rowCount}: SKU [{$sku}] tidak ditemukan di master produk.";
                    continue;
                }

                $product->update([
                    'min_stock_alert' => $minStock,
                    'max_stock' => $maxStock,
                ]);

                $successCount++;
            }
        });

        $msg = "Alhamdulillah, berhasil memperbarui batas stok minimum untuk {$successCount} item.";
        if (! empty($errors)) {
            $msg .= " Catatan peringatan: " . implode('; ', array_slice($errors, 0, 3));
        }

        return redirect()->route('warehouse.minimum-stock.index')->with('success', $msg);
    }

    public function downloadMinimumStockTemplate(): StreamedResponse
    {
        $filename = 'template-stok-minimum-item.xlsx';

        return response()->streamDownload(function () {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stok Minimum');

            // 1. Title Banner
            $sheet->mergeCells('A1:D1');
            $sheet->setCellValue('A1', 'SIGMA ERP — TEMPLATE UPDATE MASSAL STOK MINIMUM & RESTOCK');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('9A3412');
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(32);

            // 2. Petunjuk Pengisian
            $sheet->mergeCells('A2:D2');
            $sheet->setCellValue('A2', 'PETUNJUK: Masukkan KODE_SKU dan STOK_MINIMUM. Sistem akan memunculkan alarm restock otomatis saat stok fisik mencapai batas ini.');
            $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('9A3412');
            $sheet->getStyle('A2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFEDD5');
            $sheet->getStyle('A2')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(2)->setRowHeight(24);

            // 3. Header Kolom di Baris 4
            $headers = [
                'A4' => 'KODE_SKU (*)',
                'B4' => 'NAMA_ITEM',
                'C4' => 'STOK_MINIMUM (*)',
                'D4' => 'STOK_MAKSIMUM',
            ];

            foreach ($headers as $cell => $text) {
                $sheet->setCellValue($cell, $text);
            }

            $sheet->getStyle('A4:D4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A4:D4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('EA580C');
            $sheet->getStyle('A4:D4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(4)->setRowHeight(26);

            // 4. Baris Contoh Data Nyata
            $sampleData = [
                ['PRD-BERAS-01', 'Beras Ramos Super 5kg', 10, 50],
                ['PRD-MINYAK-01', 'Minyak Goreng Sania 2L', 24, 100],
                ['PRD-GULA-01', 'Gula Pasir Gulaku 1kg', 15, 60],
                ['PRD-MIE-01', 'Indomie Goreng Original 85g', 10, 80],
                ['PRD-TELUR-01', 'Telur Ayam Negeri 1kg', 20, 100],
            ];

            $rowIdx = 5;
            foreach ($sampleData as $row) {
                $sheet->setCellValue('A'.$rowIdx, $row[0]);
                $sheet->setCellValue('B'.$rowIdx, $row[1]);
                $sheet->setCellValue('C'.$rowIdx, $row[2]);
                $sheet->setCellValue('D'.$rowIdx, $row[3]);

                $sheet->getStyle('C'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('D'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getRowDimension($rowIdx)->setRowHeight(20);
                $rowIdx++;
            }

            $tableRange = 'A4:D'.($rowIdx - 1);
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
