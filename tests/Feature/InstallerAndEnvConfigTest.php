<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerAndEnvConfigTest extends TestCase
{
    public function test_env_example_uses_production_pgsql_and_uncommented_db_variables(): void
    {
        $envExample = File::get(base_path('.env.example'));

        $this->assertStringContainsString('APP_ENV=production', $envExample);
        $this->assertStringContainsString('APP_DEBUG=false', $envExample);
        $this->assertStringContainsString('DB_CONNECTION=pgsql', $envExample);
        $this->assertMatchesRegularExpression('/^DB_HOST=127\.0\.0\.1$/m', $envExample);
        $this->assertMatchesRegularExpression('/^DB_PORT=5432$/m', $envExample);
        $this->assertMatchesRegularExpression('/^DB_DATABASE=sigma_erp$/m', $envExample);
        $this->assertMatchesRegularExpression('/^DB_USERNAME=postgres$/m', $envExample);
        $this->assertMatchesRegularExpression('/^DB_PASSWORD=.*$/m', $envExample);

        $this->assertStringNotContainsString('DB_CONNECTION=sqlite', $envExample);
        $this->assertStringNotContainsString('APP_ENV=local', $envExample);
        $this->assertStringNotContainsString('APP_DEBUG=true', $envExample);
    }

    public function test_php_version_requirement_is_8_3_plus_across_config_and_installers(): void
    {
        $this->assertSame('8.3.0', config('version.min_php_version'));

        $filesToCheck = [
            base_path('SETUP-KOMPUTER-BARU.bat'),
            base_path('installer/SETUP-STANDALONE.bat'),
            base_path('SETUP.bat'),
            base_path('installer/sigma_erp_setup.iss'),
            base_path('START-SIGMA-SERVER.bat'),
        ];

        foreach ($filesToCheck as $filePath) {
            $this->assertFileExists($filePath);
            $content = File::get($filePath);
            $this->assertStringNotContainsString('8.2+', $content, "File {$filePath} masih menyebut PHP 8.2+");
            $this->assertStringContainsString('8.3+', $content, "File {$filePath} harus menyebut PHP 8.3+");
        }
    }

    public function test_installers_seed_database_check_errors_and_remove_fake_database_storage_path(): void
    {
        $filesToCheck = [
            base_path('SETUP-KOMPUTER-BARU.bat'),
            base_path('installer/SETUP-STANDALONE.bat'),
            base_path('SETUP.bat'),
            base_path('installer/sigma_erp_setup.iss'),
        ];

        foreach ($filesToCheck as $filePath) {
            $this->assertFileExists($filePath);
            $content = File::get($filePath);

            $this->assertStringContainsString('db:seed', $content, "File {$filePath} wajib menjalankan db:seed");
            $this->assertStringNotContainsString('database_storage_path.txt', $content, "File {$filePath} tidak boleh membuat klaim palsu database_storage_path.txt");
            $this->assertStringNotContainsString('migrate --force >nul', $content, "File {$filePath} tidak boleh membuang output migrate ke nul");
        }
    }
}
