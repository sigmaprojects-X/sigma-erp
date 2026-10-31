<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_settings', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('setting_key', 80);
            $table->foreignUuid('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->string('category', 50)->default('GENERAL'); // SALES, PURCHASING, INVENTORY, EQUITY, GENERAL
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'setting_key'], 'uk_acc_settings_company_key');
            $table->index(['company_id', 'category'], 'idx_acc_settings_company_cat');
        });

        // Seed default mappings for existing companies
        $companies = DB::table('companies')->select('id')->get();
        $defaultMappings = [
            // Penjualan & Kasir
            ['key' => 'sales_cash_account_id', 'code' => '1001-001', 'category' => 'SALES', 'description' => 'Akun Kas Utama Kasir Toko'],
            ['key' => 'sales_receivable_account_id', 'code' => '1004-001', 'category' => 'SALES', 'description' => 'Akun Piutang Usaha Penjualan Tempo'],
            ['key' => 'sales_revenue_account_id', 'code' => '4001-001', 'category' => 'SALES', 'description' => 'Akun Pendapatan Penjualan Toko'],
            ['key' => 'sales_discount_account_id', 'code' => '4001-002', 'category' => 'SALES', 'description' => 'Akun Potongan / Diskon Penjualan'],
            ['key' => 'sales_return_account_id', 'code' => '4002-001', 'category' => 'SALES', 'description' => 'Akun Retur Penjualan Pelanggan'],

            // Pembelian & Hutang
            ['key' => 'purchase_payable_account_id', 'code' => '2001-001', 'category' => 'PURCHASING', 'description' => 'Akun Hutang Usaha Supplier'],
            ['key' => 'grni_account_id', 'code' => '2004-001', 'category' => 'PURCHASING', 'description' => 'Akun Hutang Barang Belum Difakturkan (GRNI)'],

            // Persediaan & HPP
            ['key' => 'inventory_account_id', 'code' => '1005-001', 'category' => 'INVENTORY', 'description' => 'Akun Aset Persediaan Barang Dagang'],
            ['key' => 'cogs_account_id', 'code' => '5001-001', 'category' => 'INVENTORY', 'description' => 'Akun Beban Pokok Penjualan (HPP)'],

            // Ekuitas & Saldo Awal
            ['key' => 'equity_opening_account_id', 'code' => '3001-001', 'category' => 'EQUITY', 'description' => 'Akun Modal Pemilik / Ekuitas Saldo Awal'],
            ['key' => 'retained_earnings_account_id', 'code' => '3002-001', 'category' => 'EQUITY', 'description' => 'Akun Laba Ditahan (Retained Earnings)'],
        ];

        foreach ($companies as $comp) {
            foreach ($defaultMappings as $mapping) {
                $account = DB::table('chart_of_accounts')
                    ->where('company_id', $comp->id)
                    ->where('code', $mapping['code'])
                    ->first();

                if ($account) {
                    DB::table('accounting_settings')->updateOrInsert(
                        [
                            'company_id' => $comp->id,
                            'setting_key' => $mapping['key'],
                        ],
                        [
                            'account_id' => $account->id,
                            'category' => $mapping['category'],
                            'description' => $mapping['description'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
