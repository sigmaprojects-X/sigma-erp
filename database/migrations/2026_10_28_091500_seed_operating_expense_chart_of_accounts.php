<?php

use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companies = Company::all();

        $expenseAccounts = [
            ['code' => '6101-001', 'name' => 'Beban Gaji & Upah Karyawan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6102-001', 'name' => 'Beban Listrik (PLN)', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6103-001', 'name' => 'Beban Internet, Air & Komunikasi', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6104-001', 'name' => 'Beban Sewa Toko / Tempat', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6105-001', 'name' => 'Beban BBM & Transportasi Operasional', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6106-001', 'name' => 'Beban Perlengkapan Toko & ATK', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6107-001', 'name' => 'Beban Pemeliharaan & Perbaikan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6108-001', 'name' => 'Beban Konsumsi & Kesejahteraan Karyawan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6109-001', 'name' => 'Beban Iklan, Promosi & Pemasaran', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6110-001', 'name' => 'Beban Iuran Kebersihan & Keamanan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6111-001', 'name' => 'Beban Administrasi Bank & MDR', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6199-001', 'name' => 'Beban Operasional Lain-lain', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
        ];

        foreach ($companies as $company) {
            foreach ($expenseAccounts as $acc) {
                ChartOfAccount::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $acc['code'],
                    ],
                    [
                        'name' => $acc['name'],
                        'type' => $acc['type'],
                        'normal_balance' => $acc['normal_balance'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $codes = [
            '6101-001', '6102-001', '6103-001', '6104-001', '6105-001',
            '6106-001', '6107-001', '6108-001', '6109-001', '6110-001',
            '6111-001', '6199-001',
        ];

        ChartOfAccount::whereIn('code', $codes)->delete();
    }
};
