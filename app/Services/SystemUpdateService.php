<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class SystemUpdateService
{
    /**
     * Lokasi folder arsip backup terpadu (sama dengan MaintenanceController & skrip .bat).
     */
    protected function getBackupDirectory(): string
    {
        return storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
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
     * Jalankan migrasi database lokal.
     */
    protected function runDatabaseMigrations(): string
    {
        Artisan::call('migrate', ['--force' => true]);

        return trim(Artisan::output());
    }
    /**
     * Dapatkan versi sistem SIGMA ERP saat ini.
     */
    public function getCurrentVersion(): string
    {
        return config('version.version', '1.0.0');
    }

    /**
     * Dapatkan versi rilis terbaru yang tersedia.
     */
    public function getLatestVersion(): string
    {
        return config('version.latest_version', '1.0.1');
    }

    /**
     * Cek apakah terdapat pembaruan yang siap diinstal.
     */
    public function isUpdateAvailable(): bool
    {
        $current = $this->getCurrentVersion();
        $latest = $this->getLatestVersion();

        return version_compare($latest, $current, '>');
    }

    /**
     * Ambil informasi hardware, database, dan environment instalasi.
     */
    public function getSystemInfo(): array
    {
        $dataDir = 'Default';
        $dbSize = '0 MB';

        try {
            $dirRow = DB::select('SHOW data_directory');
            if (!empty($dirRow)) {
                $dataDir = $dirRow[0]->data_directory ?? 'Default';
            }

            $sizeRow = DB::select("SELECT pg_size_pretty(pg_database_size(current_database())) as size");
            if (!empty($sizeRow)) {
                $dbSize = $sizeRow[0]->size ?? 'N/A';
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal membaca info PostgreSQL: ' . $e->getMessage());
        }

        // Deteksi sisa kapasitas harddisk
        $drive = substr(base_path(), 0, 3);
        $freeSpace = @disk_free_space($drive);
        $totalSpace = @disk_total_space($drive);

        $freeSpaceFormatted = $freeSpace ? round($freeSpace / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A';
        $totalSpaceFormatted = $totalSpace ? round($totalSpace / (1024 * 1024 * 1024), 2) . ' GB' : 'N/A';

        return [
            'app_name' => config('version.app_name', 'SIGMA ERP & POS Enterprise'),
            'current_version' => $this->getCurrentVersion(),
            'latest_version' => $this->getLatestVersion(),
            'update_available' => $this->isUpdateAvailable(),
            'release_date' => config('version.release_date', '2026-10-30'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_engine' => 'PostgreSQL 16+',
            'database_name' => config('database.connections.pgsql.database', 'sigma_erp'),
            'database_storage_path' => $dataDir,
            'database_size' => $dbSize,
            'install_drive' => $drive,
            'disk_free_space' => $freeSpaceFormatted,
            'disk_total_space' => $totalSpaceFormatted,
            'server_os' => PHP_OS_FAMILY . ' (' . php_uname('s') . ')',
            'server_time' => now()->translatedFormat('l, d F Y - H:i:s T'),
        ];
    }

    /**
     * Dapatkan catatan rilis / pembaruan.
     */
    public function getChangelog(): array
    {
        return config('version.changelog', []);
    }

    /**
     * Jalankan proses sinkronisasi pembaruan lokal (Pre-Update Backup, Migrate, Cache Clear).
     */
    public function performUpdate(): array
    {
        $steps = [];
        $startTime = microtime(true);

        try {
            // Langkah 1: Buat Backup Pengaman Database via pg_dump (sama dengan MaintenanceController)
            $backupDir = $this->getBackupDirectory();
            if (! File::isDirectory($backupDir)) {
                File::makeDirectory($backupDir, 0755, true, true);
            }

            $backupFilename = 'sigma_erp_backup_PRE_UPDATE_' . now()->format('Ymd_His') . '.sql';
            $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupFilename;

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
                    '-f', $backupPath,
                ];

                $process = $this->buildPgDumpProcess($command, [
                    'PGPASSWORD' => $password,
                ]);
                $process->run();

                if (! $process->isSuccessful() || $process->getExitCode() !== 0) {
                    if (File::exists($backupPath)) {
                        File::delete($backupPath);
                    }

                    $errorMsg = trim($process->getErrorOutput() ?: $process->getOutput());
                    $detail = 'Gagal membuat file cadangan (exit code ' . $process->getExitCode() . '): ' . ($errorMsg ?: 'Eksekusi pg_dump gagal.');

                    $steps[] = [
                        'step' => 1,
                        'name' => 'Auto-Backup Database Pre-Update',
                        'status' => 'FAILED',
                        'detail' => $detail,
                    ];

                    return [
                        'success' => false,
                        'message' => 'Pembaruan dihentikan karena Auto-Backup gagal. Migrasi database tidak dijalankan. (' . $detail . ')',
                        'steps' => $steps,
                    ];
                }

                clearstatcache(true, $backupPath);
                if (! File::exists($backupPath) || File::size($backupPath) === 0) {
                    if (File::exists($backupPath)) {
                        File::delete($backupPath);
                    }

                    $detail = 'Gagal membuat file cadangan: File hasil pg_dump kosong (0 byte).';

                    $steps[] = [
                        'step' => 1,
                        'name' => 'Auto-Backup Database Pre-Update',
                        'status' => 'FAILED',
                        'detail' => $detail,
                    ];

                    return [
                        'success' => false,
                        'message' => 'Pembaruan dihentikan karena Auto-Backup menghasilkan file kosong. Migrasi database tidak dijalankan.',
                        'steps' => $steps,
                    ];
                }
            } catch (\Throwable $backupException) {
                if (File::exists($backupPath)) {
                    File::delete($backupPath);
                }

                $detail = 'Gagal membuat file cadangan: ' . $backupException->getMessage();

                $steps[] = [
                    'step' => 1,
                    'name' => 'Auto-Backup Database Pre-Update',
                    'status' => 'FAILED',
                    'detail' => $detail,
                ];

                return [
                    'success' => false,
                    'message' => 'Pembaruan dihentikan karena Auto-Backup gagal: ' . $backupException->getMessage(),
                    'steps' => $steps,
                ];
            }

            $steps[] = [
                'step' => 1,
                'name' => 'Auto-Backup Database Pre-Update',
                'status' => 'SUCCESS',
                'detail' => 'Database cadangan dibuat di storage/app/backups/' . $backupFilename,
            ];

            // Langkah 2: Jalankan Migrasi Database untuk tabel/kolom baru
            $migrateOutput = $this->runDatabaseMigrations();

            $steps[] = [
                'step' => 2,
                'name' => 'Sinkronisasi Struktur Database (Migrate)',
                'status' => 'SUCCESS',
                'detail' => $migrateOutput ?: 'Semua skema tabel telah mutakhir.',
            ];

            // Langkah 3: Bersihkan & Optimasi Seluruh Cache
            Artisan::call('optimize:clear');
            $steps[] = [
                'step' => 3,
                'name' => 'Pembersihan & Refresh Cache Aplikasi',
                'status' => 'SUCCESS',
                'detail' => 'Cache config, route, dan view Blade telah direfresh.',
            ];

            // Langkah 4: Sinkronisasi nomor versi aktif
            $latestVer = $this->getLatestVersion();
            $configPath = config_path('version.php');
            if (File::exists($configPath)) {
                $content = File::get($configPath);
                $content = preg_replace("/'version'\s*=>\s*'[^']+'/", "'version' => '{$latestVer}'", $content);
                $content = preg_replace("/'update_available'\s*=>\s*(true|false)/", "'update_available' => false", $content);
                File::put($configPath, $content);
            }

            $steps[] = [
                'step' => 4,
                'name' => 'Aktivasi Versi Resmi',
                'status' => 'SUCCESS',
                'detail' => "Sistem berhasil disinkronkan ke SIGMA ERP v{$latestVer}.",
            ];

            $duration = round(microtime(true) - $startTime, 2);

            return [
                'success' => true,
                'message' => "Sinkronisasi pembaruan lokal ke v{$latestVer} berhasil diselesaikan dalam {$duration} detik!",
                'new_version' => $latestVer,
                'steps' => $steps,
                'duration' => $duration . ' detik',
            ];

        } catch (\Throwable $e) {
            Log::error('Update error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses pembaruan: ' . $e->getMessage(),
                'steps' => $steps,
            ];
        }
    }
}
