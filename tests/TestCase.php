<?php

namespace Tests;

use Database\Seeders\MinimalTestSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedMinimalData(): void
    {
        $this->seed(MinimalTestSeeder::class);
    }
}
