<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\PosTransaction;
use App\Models\StockMutation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class MaintenanceController extends Controller
{
    /**
     * Lokasi folder arsip backup terpadu (sama dengan BACKUP-DATABASE.bat & RESTORE-DATABASE.bat).
     */
    protected function getBackupDirectory(): string
    {
        $customDir = env('SIGMA_BACKUP_DIR');
        if ($customDir && is_string($customDir) && trim($customDir) !== '') {
            return trim($customDir);
        }

        return storage_path('app'.DIRECTORY_SEPARATOR.'backups');
    }

    /**
     * Deteksi binary pg_dump pada sistem.
     */
    protected function resolvePgDumpBinary(): string
    {
        $candidates = [
            'C:\\Program Files\\PostgreSQL\\18\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\17\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\16\\bin\\pg_dump.exe',
            'C:\\Program Files\\PostgreSQL\\15\\bin\\pg_dump.exe',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return 'pg_dump';
    }

    /**
     * Buat instance Symfony Process untuk eksekusi pg_dump.
     *
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     */
    protected function buildPgDumpProcess(array $command, array $env): Process
    {
        $process = new Process($command, base_path(), $env);
        $process->setTimeout(300);

        return $process;
    }
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
        $backupDir = $this->getBackupDirectory();
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
     * Jalankan Pemicu Pencadangan Database (Instant Backup) via pg_dump.
     */
    public function createBackup(Request $request): RedirectResponse
    {
        $backupDir = $this->getBackupDirectory();
        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        $timestamp = date('Ymd_His');
        $filename = "sigma_erp_backup_{$timestamp}.sql";
        $filepath = $backupDir.DIRECTORY_SEPARATOR.$filename;

        try {
            $connection = config('database.default', 'pgsql');
            $dbConfig = config("database.connections.{$connection}", config('database.connections.pgsql', []));

            $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
            $port = (string) ($dbConfig['port'] ?? '5432');
            $database = (string) ($dbConfig['database'] ?? 'sigma_erp');
            $username = (string) ($dbConfig['username'] ?? 'postgres');
            $password = (string) ($dbConfig['password'] ?? '');

            $command = [
                $this->resolvePgDumpBinary(),
                '--clean',
                '--if-exists',
                '--no-owner',
                '-h', $host,
                '-p', $port,
                '-U', $username,
                '-d', $database,
                '-f', $filepath,
            ];

            $process = $this->buildPgDumpProcess($command, [
                'PGPASSWORD' => $password,
            ]);
            $process->run();

            if (! $process->isSuccessful() || $process->getExitCode() !== 0) {
                if (File::exists($filepath)) {
                    File::delete($filepath);
                }

                $errorMsg = trim($process->getErrorOutput() ?: $process->getOutput());

                return redirect()->route('settings.maintenance.index')
                    ->with('error', 'Gagal membuat file cadangan (exit code ' . $process->getExitCode() . '): ' . ($errorMsg ?: 'Eksekusi pg_dump gagal.'));
            }

            clearstatcache(true, $filepath);
            if (! File::exists($filepath) || File::size($filepath) === 0) {
                if (File::exists($filepath)) {
                    File::delete($filepath);
                }

                return redirect()->route('settings.maintenance.index')
                    ->with('error', 'Gagal membuat file cadangan: File hasil pg_dump kosong (0 byte).');
            }

            return redirect()->route('settings.maintenance.index')
                ->with('success', "Alhamdulillah, file pencadangan [{$filename}] berhasil dibuat!");
        } catch (\Throwable $e) {
            if (File::exists($filepath)) {
                File::delete($filepath);
            }

            return redirect()->route('settings.maintenance.index')
                ->with('error', 'Gagal membuat file cadangan: ' . $e->getMessage());
        }
    }

    /**
     * Unduh File Backup Database ke Komputer Lokal.
     */
    public function downloadBackup(string $filename): BinaryFileResponse|RedirectResponse
    {
        // Sanitasi nama file untuk mencegah path traversal
        $cleanFilename = basename($filename);
        $filepath = $this->getBackupDirectory().DIRECTORY_SEPARATOR.$cleanFilename;

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
        $filepath = $this->getBackupDirectory().DIRECTORY_SEPARATOR.$cleanFilename;

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
     * Daftar eksplisit tabel sistem & master yang TIDAK dihapus saat Reset Demo.
     *
     * @return list<string>
     */
    protected function getDemoResetExcludedTables(): array
    {
        return [
            // Tabel sistem & internal Laravel
            'migrations',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'sessions',
            'password_reset_tokens',

            // Tabel master utama & konfigurasi perusahaan/toko/pengguna
            'companies',
            'stores',
            'user_groups',
            'users',

            // Tabel master pelanggan, pemasok, produk, dan harga
            'customer_tiers',
            'customers',
            'suppliers',
            'units',
            'product_categories',
            'products',
            'product_units',
            'product_barcodes',
            'product_prices',
            'product_boms',

            // Tabel master akuntansi, bank, pajak, penomoran, dan aturan promo/loyalitas
            'bank_accounts',
            'chart_of_accounts',
            'accounting_settings',
            'tax_configurations',
            'transaction_number_patterns',
            'loyalty_programs',
            'loyalty_rewards',
            'promotions',
        ];
    }

    /**
     * Bangun daftar tabel transaksi dari skema nyata (Schema::getTables) dengan urutan child-first.
     *
     * @return list<string>
     */
    protected function getTablesToEmptyForDemoReset(): array
    {
        $excluded = array_flip($this->getDemoResetExcludedTables());
        $searchPath = (string) config('database.connections.pgsql.search_path', 'sigma,public');
        $allowedSchemas = array_values(array_filter(array_map('trim', explode(',', $searchPath))));
        $allowedSchemas[] = 'sigma';
        $allowedSchemas[] = 'public';

        $candidates = [];
        foreach (Schema::getTables() as $tableInfo) {
            $name = $tableInfo['name'] ?? null;
            $schema = $tableInfo['schema'] ?? null;

            if (! $name || isset($excluded[$name])) {
                continue;
            }

            if ($schema !== null && ! in_array($schema, $allowedSchemas, true)) {
                continue;
            }

            $candidates[$name] = true;
        }

        $tableNames = array_keys($candidates);

        // Bangun graf ketergantungan foreign key: child (referencing) harus dihapus sebelum parent (referenced)
        $childrenOf = [];
        foreach ($tableNames as $tbl) {
            $childrenOf[$tbl] = [];
        }

        foreach ($tableNames as $tbl) {
            foreach (Schema::getForeignKeys($tbl) as $fk) {
                $parent = $fk['foreign_table'] ?? null;
                if ($parent && $parent !== $tbl && isset($candidates[$parent])) {
                    $childrenOf[$parent][$tbl] = true;
                }
            }
        }

        $visited = [];
        $ordered = [];

        $visit = function (string $tbl) use (&$visit, &$visited, &$ordered, $childrenOf): void {
            if (isset($visited[$tbl])) {
                return;
            }
            $visited[$tbl] = true;

            foreach (array_keys($childrenOf[$tbl] ?? []) as $child) {
                $visit($child);
            }

            $ordered[] = $tbl;
        };

        foreach ($tableNames as $tbl) {
            $visit($tbl);
        }

        return $ordered;
    }

    /**
     * Jalankan seeder data master & akuntansi untuk Reset Demo.
     */
    protected function runDemoSeeders(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'MasterDataSeeder',
            '--force' => true,
        ]);
        Artisan::call('db:seed', [
            '--class' => 'AccountingSeeder',
            '--force' => true,
        ]);
    }

    /**
     * Reset Demo Data — Hapus semua data transaksi & reseed data master.
     *
     * HANYA untuk role owner. Nonaktif di production kecuali SIGMA_ALLOW_DEMO_RESET=true.
     *
     * AGENTS.md: All writes in DB::transaction, RBAC strictly enforced.
     */
    public function resetDemo(Request $request): RedirectResponse
    {
        if ($request->user()?->role !== 'owner') {
            abort(403, 'Hanya Owner yang diizinkan menjalankan Reset Demo.');
        }

        $isProduction = app()->isProduction() || config('app.env') === 'production';
        $allowDemoReset = filter_var(
            config('app.allow_demo_reset', env('SIGMA_ALLOW_DEMO_RESET', false)),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($isProduction && ! $allowDemoReset) {
            return redirect()->route('settings.maintenance.index')->with('error', '⚠️ Reset Data Demo DITOLAK! Sistem sedang berjalan dalam Mode Production tanpa flag SIGMA_ALLOW_DEMO_RESET=true.');
        }

        $request->validate([
            'confirm_text' => ['required', 'in:RESET DEMO'],
        ], [
            'confirm_text.in' => 'Ketik "RESET DEMO" untuk konfirmasi.',
        ]);

        try {
            DB::transaction(function () {
                $tablesToEmpty = $this->getTablesToEmptyForDemoReset();

                foreach ($tablesToEmpty as $tbl) {
                    DB::table($tbl)->delete();
                }

                // Reset saldo turunan pada tabel master yang dipertahankan
                if (Schema::hasTable('customers')) {
                    $customerReset = ['current_credit_balance' => 0];
                    if (Schema::hasColumn('customers', 'loyalty_points')) {
                        $customerReset['loyalty_points'] = 0;
                    }
                    if (Schema::hasColumn('customers', 'lifetime_points')) {
                        $customerReset['lifetime_points'] = 0;
                    }
                    if (Schema::hasColumn('customers', 'deposit_balance')) {
                        $customerReset['deposit_balance'] = 0;
                    }
                    DB::table('customers')->update($customerReset);
                }

                if (Schema::hasTable('suppliers') && Schema::hasColumn('suppliers', 'deposit_balance')) {
                    DB::table('suppliers')->update(['deposit_balance' => 0]);
                }

                if (Schema::hasTable('products')) {
                    $productReset = ['total_quantity' => 0];
                    if (Schema::hasColumn('products', 'total_stock_value')) {
                        $productReset['total_stock_value'] = 0;
                    }
                    DB::table('products')->update($productReset);
                }

                if (Schema::hasTable('transaction_number_patterns') && Schema::hasColumn('transaction_number_patterns', 'last_sequence')) {
                    DB::table('transaction_number_patterns')->update(['last_sequence' => 0]);
                }

                // Jalankan seeding di dalam transaksi yang sama
                $this->runDemoSeeders();

                // Pastikan pelanggan hasil seeder juga memiliki saldo piutang & poin 0 karena seluruh transaksi telah dihapus
                if (Schema::hasTable('customers')) {
                    $customerPostSeedReset = ['current_credit_balance' => 0];
                    if (Schema::hasColumn('customers', 'loyalty_points')) {
                        $customerPostSeedReset['loyalty_points'] = 0;
                    }
                    if (Schema::hasColumn('customers', 'lifetime_points')) {
                        $customerPostSeedReset['lifetime_points'] = 0;
                    }
                    if (Schema::hasColumn('customers', 'deposit_balance')) {
                        $customerPostSeedReset['deposit_balance'] = 0;
                    }
                    DB::table('customers')->update($customerPostSeedReset);
                }
            });

            Cache::flush();

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
