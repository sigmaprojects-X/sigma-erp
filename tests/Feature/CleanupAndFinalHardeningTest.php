<?php

namespace Tests\Feature;

use Tests\TestCase;

class CleanupAndFinalHardeningTest extends TestCase
{
    public function test_junk_files_and_empty_scratch_scripts_folders_are_removed(): void
    {
        $forbiddenPaths = [
            base_path('bootstrap()'),
            base_path('getPdo()'),
            base_path('test_users_page.php'),
            base_path('database/database.sqlite'),
            base_path('scratch'),
            base_path('scripts'),
        ];

        foreach ($forbiddenPaths as $path) {
            $this->assertFileDoesNotExist($path, "File/folder sampah masih ada: {$path}");
        }
    }
}
