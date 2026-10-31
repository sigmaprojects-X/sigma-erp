<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\PosTransaction;
use App\Models\StockMutation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaintenanceController extends Controller
{
    /**
     * Tampilkan halaman Pemeliharaan Sistem, Status Database & Backup.
     */
    public function index(): View
    {
        $company = Company::firstOrFail();

        // 1. Dapatkan Ukuran Database PostgreSQL
        $dbSize = '0 MB';
        $dbVersion = 'PostgreSQL';
        try {
            $currentDb = config('database.connections.pgsql.database', 'sigma_erp');
            $sizeResult = DB::selectOne("SELECT pg_size_pretty(pg_database_size(?)) AS size", [$currentDb]);
            if ($sizeResult && isset($sizeResult->size)) {
                $dbSize = $sizeResult->size;
            }
            $versionResult = DB::selectOne("SELECT version() AS ver");
            if ($versionResult && isset($versionResult->ver)) {
                $dbVersion = explode(',', $versionResult->ver)[0] ?? 'PostgreSQL';
            }
        } catch (\Throwable $e) {
            $dbSize = 'N/A';
        }

        // 2. Statistik Volume Data Transaksi
        $stats = [
            'sales_count' => PosTransaction::count(),
            'journal_entries_count' => JournalEntry::count(),
            'stock_mutations_count' => StockMutation::count(),
            'db_size' => $dbSize,
            'db_version' => $dbVersion,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'current_mode' => app()->isProduction() ? 'production' : 'development',
            'is_debug' => config('app.debug', false),
            'is_cached' => app()->configurationIsCached(),
        ];

        // 3. Daftar File Backup Lokal di storage/app/backups
        $backupDir = storage_path('app'.DIRECTORY_SEPARATOR.'backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        $files = File::files($backupDir);
        $backups = [];
        foreach ($files as $file) {
            if (in_array(strtolower($file->getExtension()), ['sql', 'dump', 'gz', 'json'])) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatBytes($file->getSize()),
                    'raw_size' => $file->getSize(),
                    'created_at' => \Carbon\Carbon::createFromTimestamp($file->getMTime()),
                ];
            }
        }

        // Urutkan backup terbaru di paling atas
        usort($backups, fn($a, $b) => $b['created_at']->timestamp <=> $a['created_at']->timestamp);

        return view('settings.maintenance', compact('company', 'stats', 'backups'));
    }

    /**
     * Jalankan Pemicu Pencadangan Database (Instant Backup).
     */
    public function createBackup(Request $request): RedirectResponse
    {
        $backupDir = storage_path('app'.DIRECTORY_SEPARATOR.'backups');
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "sigma_backup_{$timestamp}.sql";
        $filepath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        try {
            // Hasilkan dump SQL sederhana dengan metadata struktur & data SIGMA ERP
            $tables = [
                'companies',
                'stores',
                'user_groups',
                'users',
                'customer_tiers',
                'customers',
                'suppliers',
                'products',
                'product_units',
                'product_barcodes',
                'product_prices',
                'product_stocks',
                'stock_mutations',
                'transaction_number_patterns',
                'pos_shifts',
                'sales',
                'sale_items',
                'sale_payments',
                'chart_of_accounts',
                'general_ledger_entries',
            ];

            $handle = fopen($filepath, 'w+');
            fwrite($handle, "-- =========================================================\n");
            fwrite($handle, "-- SIGMA ERP & POS DATABASE BACKUP\n");
            fwrite($handle, "-- Tanggal Pembuatan : ".date('Y-m-d H:i:s')."\n");
            fwrite($handle, "-- Sistem           : Laravel ".app()->version()." / PHP ".PHP_VERSION."\n");
            fwrite($handle, "-- =========================================================\n\n");
            fwrite($handle, "SET client_encoding = 'UTF8';\n");
            fwrite($handle, "SET standard_conforming_strings = on;\n\n");

            foreach ($tables as $table) {
                if (! DB::getSchemaBuilder()->hasTable($table)) {
                    continue;
                }

                $rows = DB::table($table)->get();
                fwrite($handle, "-- Data untuk tabel: {$table} (Total: {$rows->count()} baris)\n");

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $cols = array_keys($rowArray);
                    $values = array_map(function ($val) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        if (is_bool($val)) {
                            return $val ? 'TRUE' : 'FALSE';
                        }
                        if (is_numeric($val)) {
                            return $val;
                        }
                        return "'" . addslashes((string) $val) . "'";
                    }, array_values($rowArray));

                    $colsStr = implode(', ', $cols);
                    $valsStr = implode(', ', $values);
                    fwrite($handle, "INSERT INTO {$table} ({$colsStr}) VALUES ({$valsStr}) ON CONFLICT DO NOTHING;\n");
                }
                fwrite($handle, "\n");
            }

            fclose($handle);

            return redirect()->route('settings.maintenance.index')->with('success', "Alhamdulillah, file pencadangan [{$filename}] berhasil dibuat!");
        } catch (\Throwable $e) {
            if (File::exists($filepath)) {
                File::delete($filepath);
            }
            return redirect()->route('settings.maintenance.index')->with('error', 'Gagal membuat file cadangan: ' . $e->getMessage());
        }
    }

    /**
     * Unduh File Backup Database ke Komputer Lokal.
     */
    public function downloadBackup(string $filename): BinaryFileResponse|RedirectResponse
    {
        // Sanitasi nama file untuk mencegah path traversal
        $cleanFilename = basename($filename);
        $filepath = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$cleanFilename);

        if (! File::exists($filepath)) {
            return redirect()->route('settings.maintenance.index')->with('error', 'File cadangan tidak ditemukan.');
        }

        return response()->download($filepath, $cleanFilename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Hapus File Backup dari Server.
     */
    public function deleteBackup(string $filename): RedirectResponse
    {
        $cleanFilename = basename($filename);
        $filepath = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$cleanFilename);

        if (File::exists($filepath)) {
            File::delete($filepath);
            return redirect()->route('settings.maintenance.index')->with('success', "File cadangan [{$cleanFilename}] berhasil dihapus.");
        }

        return redirect()->route('settings.maintenance.index')->with('error', 'File cadangan tidak ditemukan.');
    }

    /**
     * Bersihkan Seluruh Cache Aplikasi & View.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            return redirect()->route('settings.maintenance.index')->with('success', 'Cache aplikasi dan kompilasi view berhasil dibersihkan!');
        } catch (\Throwable $e) {
            return redirect()->route('settings.maintenance.index')->with('error', 'Gagal membersihkan cache: ' . $e->getMessage());
        }
    }

    /**
     * Jalankan Pembersihan Database & Optimasi Indeks (PostgreSQL VACUUM & ANALYZE).
     */
    public function optimizeDatabase(): RedirectResponse
    {
        try {
            // Jalankan ANALYZE untuk memperbarui statistik perencana query PostgreSQL
            DB::statement('ANALYZE;');
            return redirect()->route('settings.maintenance.index')->with('success', 'Optimasi tabel dan indeks database (ANALYZE) berhasil diselesaikan!');
        } catch (\Throwable $e) {
            return redirect()->route('settings.maintenance.index')->with('error', 'Gagal melakukan optimasi database: ' . $e->getMessage());
        }
    }

    /**
     * Beralih antara Mode Development dan Mode Production.
     * Owner only.
     */
    public function switchMode(Request $request): RedirectResponse
    {
        $request->validate([
            'mode' => ['required', 'in:development,production,dev,prod'],
        ]);

        $target = strtolower($request->input('mode'));
        $normalized = in_array($target, ['prod', 'production']) ? 'prod' : 'dev';

        try {
            Artisan::call('sigma:mode', ['target' => $normalized]);
            $msg = $normalized === 'prod'
                ? 'Sistem berhasil dialihkan ke MODE PRODUCTION! Caching aktif & performa transaksi kasir maksimal.'
                : 'Sistem berhasil dialihkan ke MODE DEVELOPMENT! Debug aktif & live reload kode tanpa cache.';

            return redirect()->route('settings.maintenance.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('settings.maintenance.index')->with('error', 'Gagal mengubah mode sistem: ' . $e->getMessage());
        }
    }

    /**
     * Reset Demo Data — Hapus semua data transaksi & reseed data master.
     *
     * HANYA untuk mode demo/presentasi. Dilindungi oleh role:owner.
     * Menghapus: transaksi POS, shift kasir, pesanan penjualan, jurnal, mutasi stok.
     * Mempertahankan: data master (produk, supplier, pelanggan, CoA, dll).
     *
     * AGENTS.md: All writes in DB::transaction, RBAC strictly enforced.
     */
    public function resetDemo(Request $request): RedirectResponse
    {
        // Proteksi Tambahan: Reset demo DILARANG pada Mode Production untuk keamanan data toko
        if (app()->isProduction()) {
            return redirect()->route('settings.maintenance.index')->with('error', '⚠️ Reset Data Demo DITOLAK! Sistem sedang berjalan dalam Mode Production. Silakan alihkan sistem ke Mode Development terlebih dahulu jika ingin mereset data uji coba.');
        }

        // Validasi PIN konfirmasi untuk keamanan extra
        $request->validate([
            'confirm_text' => ['required', 'in:RESET DEMO'],
        ], [
            'confirm_text.in' => 'Ketik "RESET DEMO" untuk konfirmasi.',
        ]);

        try {
            DB::transaction(function () {
                $tablesToEmpty = [
                    // Detail transaksi kasir & shifts
                    'pos_transaction_items',
                    'pos_return_items',
                    'pos_returns',
                    'cash_drops',
                    'pos_transactions',
                    'cashier_shifts',
                    'pos_shifts',

                    // Penjualan & Pesanan
                    'sales_order_items',
                    'sales_orders',

                    // Pembelian, PO & Retur Beli
                    'purchase_items',
                    'purchase_order_items',
                    'purchase_orders',
                    'goods_receipt_items',
                    'goods_receipts',
                    'purchase_returns',
                    'purchase_return_items',
                    'purchases',
                    'supplier_payments',

                    // Hutang & Piutang
                    'accounts_receivable',
                    'accounts_payable',
                    'customer_payments',

                    // Inventaris, Mutasi & Stock Opname
                    'stock_mutations',
                    'stock_opname_items',
                    'stock_opnames',
                    'stock_transfer_items',
                    'stock_transfers',
                    'inventory_adjustments',
                    'quarantine_mutations',

                    // Konsinyasi
                    'consignment_settlement_items',
                    'consignment_settlements',
                    'consignment_stock_mutations',
                    'consignment_items',
                    'consignments',

                    // Akuntansi & Jurnal
                    'journal_entry_lines',
                    'journal_entries',

                    // Produk & Relasi (Bersihkan seluruh item agar reset demo kembali bersih ke item demo default saja)
                    'product_assemblies',
                    'product_boms',
                    'product_prices',
                    'product_barcodes',
                    'product_stocks',
                    'product_units',
                    'products',
                ];

                foreach ($tablesToEmpty as $tbl) {
                    if (\Illuminate\Support\Facades\Schema::hasTable($tbl)) {
                        DB::table($tbl)->delete();
                    }
                }
            });

            // Re-seed data master (stok awal, harga, dsb)
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'MasterDataSeeder',
                '--force' => true,
            ]);
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'AccountingSeeder',
                '--force' => true,
            ]);

            \Illuminate\Support\Facades\Cache::flush();

            return redirect()->route('settings.maintenance.index')
                ->with('success', '✅ Reset demo berhasil! Semua data transaksi dihapus dan data master telah di-reload ulang.');
        } catch (\Throwable $e) {
            return redirect()->route('settings.maintenance.index')
                ->with('error', 'Gagal reset demo: ' . $e->getMessage());
        }
    }

    /**
     * Format ukuran byte ke format terbaca (KB, MB, GB).
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
