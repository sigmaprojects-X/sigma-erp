<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SystemUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected string $backupDir;

    protected string $originalVersionConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        $this->originalVersionConfig = File::get(config_path('version.php'));

        if (File::isDirectory($this->backupDir)) {
            File::cleanDirectory($this->backupDir);
        }
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->backupDir)) {
            File::cleanDirectory($this->backupDir);
        }

        if (! empty($this->originalVersionConfig)) {
            File::put(config_path('version.php'), $this->originalVersionConfig);
        }

        parent::tearDown();
    }

    public function test_perform_update_creates_real_pg_dump_backup_in_storage_app_backups(): void
    {
        $service = app(SystemUpdateService::class);

        $result = $service->performUpdate();

        $this->assertTrue($result['success'], 'performUpdate() harus berhasil ketika pg_dump sukses.');
        $this->assertNotEmpty($result['steps']);
        $this->assertSame('SUCCESS', $result['steps'][0]['status']);

        $files = File::files($this->backupDir);
        $this->assertCount(1, $files, 'File backup pre-update harus benar-benar dibuat di storage/app/backups.');

        $backupFile = $files[0];
        $this->assertStringStartsWith('sigma_erp_backup_PRE_UPDATE_', $backupFile->getFilename());
        $this->assertGreaterThan(0, $backupFile->getSize(), 'File backup pre-update tidak boleh kosong.');

        $content = File::get($backupFile->getPathname());
        $this->assertStringContainsString('PostgreSQL database dump', $content);
        $this->assertStringContainsString('IF EXISTS', $content);
        $this->assertStringContainsString('pos_transactions', $content);
    }

    public function test_perform_update_stops_and_does_not_run_migrate_when_backup_fails(): void
    {
        $migrateCalled = false;

        $service = new class($migrateCalled) extends SystemUpdateService
        {
            public bool $migrateExecuted = false;

            public function __construct(bool &$migrateCalled)
            {
                $this->migrateExecuted = &$migrateCalled;
            }

            protected function buildPgDumpProcess(array $command, array $env): Process
            {
                return new Process([PHP_BINARY, '-r', 'fwrite(STDERR, "pg_dump connection error"); exit(1);']);
            }

            protected function runDatabaseMigrations(): string
            {
                $this->migrateExecuted = true;

                return parent::runDatabaseMigrations();
            }
        };

        $result = $service->performUpdate();

        $this->assertFalse($result['success'], 'performUpdate() harus gagal jika Auto-Backup gagal.');
        $this->assertFalse($migrateCalled, 'Migrate tidak boleh dijalankan jika backup gagal.');
        $this->assertCount(1, $result['steps'], 'Proses update harus berhenti di langkah 1 (Auto-Backup).');
        $this->assertSame('FAILED', $result['steps'][0]['status']);

        $files = File::isDirectory($this->backupDir) ? File::files($this->backupDir) : [];
        $this->assertCount(0, $files, 'File backup yang gagal/kosong harus dihapus.');
    }

    public function test_update_ui_does_not_imply_online_checking_or_downloading(): void
    {
        $response = $this->actingAs($this->owner)->get(route('settings.update.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringNotContainsString('Auto-Updater', $content);
        $this->assertStringNotContainsString('Periksa Update Sekarang', $content);
        $this->assertStringNotContainsString('Stable Enterprise Channel', $content);
        $this->assertStringContainsString('Lokal', $content);
    }
}
