<?php

use App\Models\Company;
use App\Models\Store;
use App\Services\CompanyService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jangan jalankan data seeding jika sedang menjalankan unit/feature test suite
        if (app()->environment('testing')) {
            return;
        }

        DB::transaction(function () {
            $companyService = app(CompanyService::class);

            // 1. Perusahaan 1: PT SIGMA JAYA NUSANTARA
            $company1 = Company::where('code', 'SIGMA-HO')->first() ?? Company::first();
            if ($company1) {
                $company1->update([
                    'code' => 'SIGMA-JAYA',
                    'name' => 'PT SIGMA JAYA NUSANTARA',
                    'receipt_footer' => "Terima Kasih Atas Kunjungan Anda\nPT SIGMA JAYA NUSANTARA",
                ]);

                $store1 = Store::where('company_id', $company1->id)->where('is_main_store', true)->first()
                    ?? Store::where('company_id', $company1->id)->first();
                if ($store1) {
                    $store1->update(['name' => 'CABANG UTAMA PT SIGMA']);
                }
            }

            // 2. Perusahaan 2: CV OCTA MAKMUR JAYA
            if (!Company::where('code', 'OCTA-MAKMUR')->exists()) {
                $companyService->createCompany([
                    'code' => 'OCTA-MAKMUR',
                    'name' => 'CV OCTA MAKMUR JAYA',
                    'email' => 'contact@octamakmur.co.id',
                    'phone' => '021-5558899',
                    'address' => 'Kawasan Industri & Pergudangan Terpadu, Jakarta',
                    'store_code' => 'STR-OCTA-01',
                    'store_name' => 'CABANG UTAMA CV OCTA',
                    'receipt_footer' => "Terima Kasih Atas Kunjungan Anda\nCV OCTA MAKMUR JAYA",
                    'is_tax_registered' => false,
                ]);
            }

            // 3. Perusahaan 3: ENDOGAN
            if (!Company::where('code', 'ENDOGAN')->exists()) {
                $companyService->createCompany([
                    'code' => 'ENDOGAN',
                    'name' => 'ENDOGAN',
                    'email' => 'sales@endogan.com',
                    'phone' => '021-5557766',
                    'address' => 'Sentra Retail & Distribusi Pangan',
                    'store_code' => 'STR-ENDOGAN-01',
                    'store_name' => 'TOKO UTAMA ENDOGAN',
                    'receipt_footer' => "Terima Kasih Atas Kunjungan Anda\nENDOGAN",
                    'is_tax_registered' => false,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap pertahankan data jika rollback untuk mencegah data loss
    }
};
