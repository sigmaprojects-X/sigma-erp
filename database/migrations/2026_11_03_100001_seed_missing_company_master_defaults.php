<?php

use App\Models\Company;
use App\Services\CompanyService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companyService = app(CompanyService::class);

        // Pastikan seluruh perusahaan memiliki kategori, satuan standar, dan CUST-UMUM
        $companies = Company::all();
        foreach ($companies as $company) {
            $companyService->seedCompanyDefaults($company);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed
    }
};
