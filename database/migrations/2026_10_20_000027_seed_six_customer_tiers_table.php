<?php

use App\Models\Company;
use App\Models\CustomerTier;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $company = Company::where('code', 'SIGMA-HO')->first();
        if (! $company) {
            return;
        }

        $tiers = [
            [
                'code' => 'RETAIL',
                'name' => 'Level 1: Umum / Eceran',
                'description' => 'Harga standar retail untuk pelanggan umum tanpa membership',
                'default_margin_percentage' => 20.00,
                'is_default' => true,
            ],
            [
                'code' => 'GROSIR',
                'name' => 'Level 2: Grosir / Toko',
                'description' => 'Harga khusus pedagang, warung, dan agen kulakan',
                'default_margin_percentage' => 15.00,
                'is_default' => false,
            ],
            [
                'code' => 'AGEN',
                'name' => 'Level 3: Agen / Reseller',
                'description' => 'Harga agen dan toko mitra kulakan',
                'default_margin_percentage' => 12.00,
                'is_default' => false,
            ],
            [
                'code' => 'DISTRIBUTOR',
                'name' => 'Level 4: Distributor / Partai',
                'description' => 'Harga pembelian partai besar / distributor',
                'default_margin_percentage' => 8.00,
                'is_default' => false,
            ],
            [
                'code' => 'MEMBER',
                'name' => 'Level 5: Member / VIP',
                'description' => 'Harga pelanggan loyal / kartu member',
                'default_margin_percentage' => 18.00,
                'is_default' => false,
            ],
            [
                'code' => 'KHUSUS',
                'name' => 'Level 6: Mitra Khusus / Proyek',
                'description' => 'Harga kontrak proyek dan instansi',
                'default_margin_percentage' => 5.00,
                'is_default' => false,
            ],
        ];

        foreach ($tiers as $t) {
            CustomerTier::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $t['code'],
                ],
                [
                    'name' => $t['name'],
                    'description' => $t['description'],
                    'default_margin_percentage' => $t['default_margin_percentage'],
                    'is_default' => $t['is_default'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep tiers
    }
};
