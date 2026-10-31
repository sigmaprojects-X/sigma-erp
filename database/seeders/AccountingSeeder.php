<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\TaxConfiguration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $company = Company::where('code', 'SIGMA-HO')->firstOrFail();

            $accounts = [
                // ASSET (1xxx)
                ['code' => '1001-001', 'name' => 'Kas Toko / Kasir', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1002-001', 'name' => 'Bank BCA (Rek: 123-456-7890)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1002-002', 'name' => 'Bank Mandiri (Rek: 098-765-4321)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1002-003', 'name' => 'Bank BRI (Rek: 567-890-1234)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1002-004', 'name' => 'Bank BNI (Rek: 321-654-0987)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1003-001', 'name' => 'QRIS / Dompet Digital', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1004-001', 'name' => 'Piutang Usaha (Tempo / Kredit)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1005-001', 'name' => 'Persediaan Barang Dagang', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1006-001', 'name' => 'PPN Masukan (VAT In)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1501-001', 'name' => 'Aset Tetap - Peralatan & Mesin', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1502-001', 'name' => 'Akumulasi Penyusutan Peralatan & Mesin', 'type' => 'ASSET', 'normal_balance' => 'CREDIT'],
                ['code' => '1501-002', 'name' => 'Aset Tetap - Kendaraan Operasional', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1502-002', 'name' => 'Akumulasi Penyusutan Kendaraan', 'type' => 'ASSET', 'normal_balance' => 'CREDIT'],
                ['code' => '1501-003', 'name' => 'Aset Tetap - Bangunan & Renovasi', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1502-003', 'name' => 'Akumulasi Penyusutan Bangunan', 'type' => 'ASSET', 'normal_balance' => 'CREDIT'],
                ['code' => '1501-004', 'name' => 'Aset Tetap - Mebel & Inventaris Toko', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
                ['code' => '1502-004', 'name' => 'Akumulasi Penyusutan Mebel & Inventaris', 'type' => 'ASSET', 'normal_balance' => 'CREDIT'],

                // LIABILITY (2xxx)
                ['code' => '2001-001', 'name' => 'Hutang Usaha (Accounts Payable)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
                ['code' => '2002-001', 'name' => 'Pendapatan Diterima Dimuka (Voucher / Gift Card)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
                ['code' => '2003-001', 'name' => 'PPN Keluaran (VAT Out)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
                ['code' => '2004-001', 'name' => 'Hutang Belum Difakturkan (GRNI / Penerimaan Barang)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],

                // EQUITY (3xxx)
                ['code' => '3001-001', 'name' => 'Modal Pemilik', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],
                ['code' => '3002-001', 'name' => 'Laba Ditahan (Retained Earnings)', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],

                // REVENUE (4xxx)
                ['code' => '4001-001', 'name' => 'Pendapatan Penjualan Toko', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
                ['code' => '4001-002', 'name' => 'Potongan / Diskon Penjualan', 'type' => 'REVENUE', 'normal_balance' => 'DEBIT'],
                ['code' => '4002-001', 'name' => 'Retur Penjualan', 'type' => 'REVENUE', 'normal_balance' => 'DEBIT'],
                ['code' => '4003-001', 'name' => 'Pendapatan Selisih Kas (Cash Overage)', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],

                // EXPENSES (5xxx & 6xxx)
                ['code' => '5001-001', 'name' => 'Beban Pokok Penjualan (HPP / COGS)', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
                ['code' => '6001-001', 'name' => 'Beban Penyusutan Aset Tetap', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
                ['code' => '6002-001', 'name' => 'Beban Selisih Kas (Cash Shortage)', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
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

            foreach ($accounts as $acc) {
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

            // Seed Default Accounting Settings / Linked Accounts
            $defaultSettings = [
                ['key' => 'sales_cash_account_id', 'code' => '1001-001', 'category' => 'SALES', 'description' => 'Akun Kas Utama Kasir Toko'],
                ['key' => 'sales_receivable_account_id', 'code' => '1004-001', 'category' => 'SALES', 'description' => 'Akun Piutang Usaha Penjualan Tempo'],
                ['key' => 'sales_revenue_account_id', 'code' => '4001-001', 'category' => 'SALES', 'description' => 'Akun Pendapatan Penjualan Toko'],
                ['key' => 'sales_discount_account_id', 'code' => '4001-002', 'category' => 'SALES', 'description' => 'Akun Potongan / Diskon Penjualan'],
                ['key' => 'sales_return_account_id', 'code' => '4002-001', 'category' => 'SALES', 'description' => 'Akun Retur Penjualan Pelanggan'],
                ['key' => 'purchase_payable_account_id', 'code' => '2001-001', 'category' => 'PURCHASING', 'description' => 'Akun Hutang Usaha Supplier'],
                ['key' => 'grni_account_id', 'code' => '2004-001', 'category' => 'PURCHASING', 'description' => 'Akun Hutang Barang Belum Difakturkan (GRNI)'],
                ['key' => 'inventory_account_id', 'code' => '1005-001', 'category' => 'INVENTORY', 'description' => 'Akun Aset Persediaan Barang Dagang'],
                ['key' => 'cogs_account_id', 'code' => '5001-001', 'category' => 'INVENTORY', 'description' => 'Akun Beban Pokok Penjualan (HPP)'],
                ['key' => 'equity_opening_account_id', 'code' => '3001-001', 'category' => 'EQUITY', 'description' => 'Akun Modal Pemilik / Ekuitas Saldo Awal'],
                ['key' => 'retained_earnings_account_id', 'code' => '3002-001', 'category' => 'EQUITY', 'description' => 'Akun Laba Ditahan (Retained Earnings)'],
            ];

            foreach ($defaultSettings as $setting) {
                $acc = ChartOfAccount::where('company_id', $company->id)->where('code', $setting['code'])->first();
                if ($acc) {
                    \App\Models\AccountingSetting::firstOrCreate(
                        [
                            'company_id' => $company->id,
                            'setting_key' => $setting['key'],
                        ],
                        [
                            'account_id' => $acc->id,
                            'category' => $setting['category'],
                            'description' => $setting['description'],
                        ]
                    );
                }
            }

            // Setup Default Tax Configuration (Non-PKP by default, configurable to PKP with effective dates)
            TaxConfiguration::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'tax_name' => 'PPN',
                ],
                [
                    'is_tax_enabled' => false, // Non-PKP Default
                    'tax_rate_percent' => 11.00,
                    'is_tax_inclusive' => false,
                    'effective_from' => '2026-01-01',
                ]
            );
        });
    }
}
