<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\AutoNumberService;
use Illuminate\Database\Seeder;

class TransactionNumberPatternSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        if ($company) {
            app(AutoNumberService::class)->seedDefaultPatterns($company);
        }
    }
}
