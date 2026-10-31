<?php

namespace Tests\Feature;

use App\Http\Controllers\MaintenanceController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MaintenanceBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');

        if (File::isDirectory($this->backupDir)) {
            File::cleanDirectory($this->backupDir);
        }
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->backupDir)) {
            File::cleanDirectory($this->backupDir);
        }

        parent::tearDown();
    }

    public function test_create_backup_runs_pg_dump_with_clean_if_exists_no_owner_and_creates_valid_sql_dump(): void
    {
        $response = $this->actingAs($this->owner)->post(route('settings.maintenance.backup'));

        $response->assertRedirect(route('settings.maintenance.index'));
        $response->assertSessionHas('success');

        $files = File::files($this->backupDir);
        $this->assertCount(1, $files, 'Harus ada 1 file backup SQL yang dibuat di storage/app/backups.');

        $backupFile = $files[0];
        $this->assertGreaterThan(0, $backupFile->getSize(), 'File backup tidak boleh kosong.');

        $content = File::get($backupFile->getPathname());
        $this->assertStringContainsString('PostgreSQL database dump', $content);
        $this->assertStringContainsString('IF EXISTS', $content);
        $this->assertStringContainsString('pos_transactions', $content);
        $this->assertStringContainsString('cashier_shifts', $content);
        $this->assertStringNotContainsString('INSERT INTO pos_shifts', $content);
        $this->assertStringNotContainsString('INSERT INTO general_ledger_entries', $content);
    }

    public function test_create_backup_fails_and_deletes_file_when_exit_code_non_zero_or_file_empty(): void
    {
        // 1. Simulasi exit code != 0
        $failingController = new class extends MaintenanceController
        {
            protected function buildPgDumpProcess(array $command, array $env): Process
            {
                return new Process(['php', '-r', 'fwrite(STDERR, "pg_dump simulated error"); exit(1);']);
            }
        };
        $this->app->instance(MaintenanceController::class, $failingController);

        $resFail = $this->actingAs($this->owner)->post(route('settings.maintenance.backup'));
        $resFail->assertRedirect(route('settings.maintenance.index'));
        $resFail->assertSessionHas('error');
        $this->assertCount(0, File::files($this->backupDir), 'File gagal harus dihapus.');

        // 2. Simulasi exit code 0 tetapi file 0 byte (kosong)
        $emptyFileController = new class extends MaintenanceController
        {
            protected function buildPgDumpProcess(array $command, array $env): Process
            {
                return new Process(['php', '-r', 'exit(0);']);
            }
        };
        $this->app->instance(MaintenanceController::class, $emptyFileController);

        $resEmpty = $this->actingAs($this->owner)->post(route('settings.maintenance.backup'));
        $resEmpty->assertRedirect(route('settings.maintenance.index'));
        $resEmpty->assertSessionHas('error');
        $this->assertCount(0, File::files($this->backupDir), 'File 0 byte harus dihapus.');
    }

    public function test_batch_scripts_use_shared_backup_dir_powershell_get_date_on_error_stop_and_safety_backup(): void
    {
        $backupBat = File::get(base_path('BACKUP-DATABASE.bat'));
        $restoreBat = File::get(base_path('RESTORE-DATABASE.bat'));

        // Tidak boleh memakai wmic yang sudah dihapus di Windows 11 24H2
        $this->assertStringNotContainsString('wmic', strtolower($backupBat));
        $this->assertStringNotContainsString('wmic', strtolower($restoreBat));

        // Wajib memakai PowerShell Get-Date untuk timestamp
        $this->assertStringContainsString('Get-Date', $backupBat);
        $this->assertStringContainsString('Get-Date', $restoreBat);

        // Lokasi folder backup harus sama dengan UI (storage\app\backups)
        $this->assertStringContainsString('storage\app\backups', $backupBat);
        $this->assertStringContainsString('storage\app\backups', $restoreBat);

        // RESTORE-DATABASE.bat wajib memakai ON_ERROR_STOP=1, konfirmasi, backup pengaman, dan tidak membuang stderr psql ke nul
        $this->assertStringContainsString('ON_ERROR_STOP=1', $restoreBat);
        $this->assertStringContainsString('SET /P', strtoupper($restoreBat));
        $this->assertStringContainsString('PG_DUMP', strtoupper($restoreBat));
        $this->assertDoesNotMatchRegularExpression('/%PSQL_BIN%[^\r\n]*2>&1/i', $restoreBat);
        $this->assertDoesNotMatchRegularExpression('/%PSQL_BIN%[^\r\n]*2>nul/i', $restoreBat);
    }
}
