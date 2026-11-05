<?php

namespace App\Services;

use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\TaxConfiguration;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyService
{
    public function __construct(
        protected AutoNumberService $autoNumberService
    ) {}

    /**
     * Dapatkan model Perusahaan yang sedang aktif.
     */
    public function getActiveCompany(): Company
    {
        $user = Auth::user();

        // 1. Jika user adalah Owner, baca dari session active_company_id
        if ($user && $user->role === 'owner') {
            $sessionCompanyId = session('active_company_id');

            if ($sessionCompanyId) {
                $company = Company::where('id', $sessionCompanyId)->where('is_active', true)->first();
                if ($company) {
                    return $company;
                }
            }

            // Fallback default untuk owner: ambil perusahaan aktif pertama
            $company = Company::where('is_active', true)->orderBy('created_at')->first() ?? Company::firstOrFail();
            session(['active_company_id' => $company->id]);
            return $company;
        }

        // 2. Jika user adalah staf / kasir / manager yang terikat dengan store
        if ($user && $user->store && $user->store->company) {
            $company = $user->store->company;
            session(['active_company_id' => $company->id]);
            return $company;
        }

        // 3. Fallback jika sesi guest / PWA / CLI
        $sessionCompanyId = session('active_company_id');
        if ($sessionCompanyId) {
            $company = Company::where('id', $sessionCompanyId)->first();
            if ($company) {
                return $company;
            }
        }

        return Company::where('is_active', true)->orderBy('created_at')->first() ?? Company::firstOrFail();
    }

    /**
     * Dapatkan UUID perusahaan aktif.
     */
    public function getActiveCompanyId(): string
    {
        return $this->getActiveCompany()->id;
    }

    /**
     * Ganti perusahaan aktif (khusus Owner).
     */
    public function switchCompany(string $companyId): Company
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'owner') {
            abort(403, 'Hanya Owner yang memiliki wewenang untuk mengganti perusahaan.');
        }

        $company = Company::where('id', $companyId)->where('is_active', true)->firstOrFail();

        session(['active_company_id' => $company->id]);

        return $company;
    }

    /**
     * Dapatkan daftar perusahaan yang dapat diakses oleh user saat ini.
     *
     * @return Collection<int, Company>
     */
    public function getAvailableCompanies(): Collection
    {
        $user = Auth::user();

        if ($user && $user->role === 'owner') {
            return Company::where('is_active', true)->orderBy('name')->get();
        }

        if ($user && $user->store && $user->store->company) {
            return collect([$user->store->company]);
        }

        return collect([$this->getActiveCompany()]);
    }

    /**
     * Buat perusahaan baru lengkap dengan Store utama, Customer Tiers,
     * Pola Nomor Faktur Otomatis, dan Bagan Akun Akuntansi.
     */
    public function createCompany(array $attributes): Company
    {
        return DB::transaction(function () use ($attributes) {
            $company = Company::create([
                'code' => strtoupper($attributes['code'] ?? Str::slug($attributes['name'] ?? 'COMPANY')),
                'name' => $attributes['name'],
                'tax_id' => $attributes['tax_id'] ?? null,
                'email' => $attributes['email'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'address' => $attributes['address'] ?? null,
                'receipt_footer' => $attributes['receipt_footer'] ?? ("Terima Kasih Atas Kunjungan Anda\n" . $attributes['name']),
                'currency_code' => $attributes['currency_code'] ?? 'IDR',
                'is_tax_registered' => (bool) ($attributes['is_tax_registered'] ?? false),
                'is_active' => true,
            ]);

            $storeCode = $attributes['store_code'] ?? ('STR-' . substr(preg_replace('/[^A-Za-z0-9]/', '', $company->code), 0, 6) . '-01');
            $storeName = $attributes['store_name'] ?? ('CABANG UTAMA ' . $company->name);

            $this->seedCompanyDefaults($company, $storeCode, $storeName);

            return $company;
        });
    }

    /**
     * Seed entitas standar untuk perusahaan baru.
     */
    public function seedCompanyDefaults(Company $company, ?string $storeCode = null, ?string $storeName = null): void
    {
        // 1. Toko Utama Default
        Store::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => $storeCode ?? ('STR-' . substr(preg_replace('/[^A-Za-z0-9]/', '', $company->code), 0, 6) . '-01'),
            ],
            [
                'name' => $storeName ?? ('CABANG UTAMA ' . $company->name),
                'type' => 'STORE',
                'is_main_store' => true,
                'is_active' => true,
            ]
        );

        // 2. Customer Tiers Standar
        $tiers = [
            ['code' => 'RETAIL', 'name' => 'Level 1: Umum / Eceran', 'margin' => 20.00, 'default' => true],
            ['code' => 'GROSIR', 'name' => 'Level 2: Grosir / Toko', 'margin' => 15.00, 'default' => false],
            ['code' => 'AGEN', 'name' => 'Level 3: Agen / Reseller', 'margin' => 10.00, 'default' => false],
            ['code' => 'DISTRIBUTOR', 'name' => 'Level 4: Distributor / Partai', 'margin' => 7.50, 'default' => false],
            ['code' => 'MEMBER', 'name' => 'Level 5: Member / VIP', 'margin' => 18.00, 'default' => false],
            ['code' => 'KHUSUS', 'name' => 'Level 6: Mitra Khusus / Proyek', 'margin' => 12.00, 'default' => false],
        ];

        foreach ($tiers as $tier) {
            CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $tier['code'],
                ],
                [
                    'name' => $tier['name'],
                    'default_margin_percentage' => $tier['margin'],
                    'is_default' => $tier['default'],
                    'is_active' => true,
                ]
            );
        }

        // 2b. Kategori Produk Standar Bawaan Toko
        $categories = [
            ['code' => 'UMUM', 'name' => 'Umum / Aneka Barang', 'description' => 'Kategori umum dan aneka produk'],
            ['code' => 'SEMBAKO', 'name' => 'Sembako & Bahan Pokok', 'description' => 'Beras, minyak, gula, tepung dan kebutuhan pokok'],
            ['code' => 'MINUMAN', 'name' => 'Minuman & Kemasan', 'description' => 'Air mineral, teh, kopi, jus dan minuman kemasan'],
            ['code' => 'SNACK', 'name' => 'Makanan Ringan & Biskuit', 'description' => 'Camilan, keripik, wafer dan makanan ringan'],
        ];
        foreach ($categories as $cat) {
            ProductCategory::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $cat['code'],
                ],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'is_active' => true,
                ]
            );
        }

        // 2c. Satuan Produk Standar Bawaan Toko (Units)
        $units = [
            ['code' => 'PCS', 'name' => 'Pieces / Satuan', 'description' => 'Satuan eceran terkecil barang'],
            ['code' => 'DUS', 'name' => 'Dus / Karton', 'description' => 'Kemasan karton / kardus besar'],
            ['code' => 'PACK', 'name' => 'Pack / Bungkus', 'description' => 'Kemasan pack / plastik / pak'],
            ['code' => 'KG', 'name' => 'Kilogram', 'description' => 'Satuan berat kilogram'],
            ['code' => 'LITER', 'name' => 'Liter', 'description' => 'Satuan volume cairan liter'],
            ['code' => 'SAK', 'name' => 'Sak / Karung', 'description' => 'Karung / sak beras atau semen'],
            ['code' => 'BOX', 'name' => 'Box / Kotak', 'description' => 'Kemasan kotak / boks'],
            ['code' => 'RENCENG', 'name' => 'Renceng', 'description' => 'Kemasan gantungan / renceng'],
        ];
        foreach ($units as $u) {
            Unit::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $u['code'],
                ],
                [
                    'name' => $u['name'],
                    'description' => $u['description'],
                    'is_active' => true,
                ]
            );
        }

        // 2d. Pelanggan Umum Bawaan Kasir POS
        $defaultTier = CustomerTier::where('company_id', $company->id)->where('code', 'RETAIL')->first();
        if ($defaultTier) {
            Customer::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'CUST-UMUM',
                ],
                [
                    'name' => 'UMUM (Pelanggan Umum / Eceran)',
                    'customer_tier_id' => $defaultTier->id,
                    'phone' => '-',
                    'address' => '-',
                    'credit_limit' => 0.00,
                    'current_credit_balance' => 0.00,
                    'credit_term_days' => 0,
                    'is_active' => true,
                ]
            );
        }

        // 2e. Supplier Umum Bawaan (dipakai otomatis bila supplier barang dikosongkan)
        \App\Models\Supplier::generalFor($company->id);

        // 3. Pola Nomor Faktur Otomatis
        $this->autoNumberService->seedDefaultPatterns($company);

        // 4. Bagan Akun Akuntansi (Chart of Accounts)
        $accounts = [
            ['code' => '1001-001', 'name' => 'Kas Toko / Kasir', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1002-001', 'name' => 'Bank BCA', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1003-001', 'name' => 'QRIS / Dompet Digital', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1004-001', 'name' => 'Piutang Usaha (Tempo / Kredit)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1005-001', 'name' => 'Persediaan Barang Dagang', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '1006-001', 'name' => 'PPN Masukan (VAT In)', 'type' => 'ASSET', 'normal_balance' => 'DEBIT'],
            ['code' => '2001-001', 'name' => 'Hutang Usaha (Accounts Payable)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '2002-001', 'name' => 'Pendapatan Diterima Dimuka (Voucher)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '2003-001', 'name' => 'PPN Keluaran (VAT Out)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '2004-001', 'name' => 'Hutang Belum Difakturkan (GRNI)', 'type' => 'LIABILITY', 'normal_balance' => 'CREDIT'],
            ['code' => '3001-001', 'name' => 'Modal Pemilik', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],
            ['code' => '3002-001', 'name' => 'Laba Ditahan (Retained Earnings)', 'type' => 'EQUITY', 'normal_balance' => 'CREDIT'],
            ['code' => '4001-001', 'name' => 'Pendapatan Penjualan Toko', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
            ['code' => '4001-002', 'name' => 'Potongan / Diskon Penjualan', 'type' => 'REVENUE', 'normal_balance' => 'DEBIT'],
            ['code' => '4002-001', 'name' => 'Retur Penjualan', 'type' => 'REVENUE', 'normal_balance' => 'DEBIT'],
            ['code' => '4003-001', 'name' => 'Pendapatan Selisih Kas (Cash Overage)', 'type' => 'REVENUE', 'normal_balance' => 'CREDIT'],
            ['code' => '5001-001', 'name' => 'Beban Pokok Penjualan (HPP / COGS)', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6002-001', 'name' => 'Beban Selisih Kas (Cash Shortage)', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6101-001', 'name' => 'Beban Gaji & Upah Karyawan', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6102-001', 'name' => 'Beban Listrik (PLN)', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6103-001', 'name' => 'Beban Internet & Komunikasi', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
            ['code' => '6104-001', 'name' => 'Beban Sewa Toko / Tempat', 'type' => 'EXPENSE', 'normal_balance' => 'DEBIT'],
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

        // 5. Default Accounting Settings / Linked Accounts
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
                AccountingSetting::firstOrCreate(
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

        // 6. Tax Configuration (Non-PKP Default)
        TaxConfiguration::firstOrCreate(
            [
                'company_id' => $company->id,
                'tax_name' => 'PPN',
            ],
            [
                'is_tax_enabled' => false,
                'tax_rate_percent' => 11.00,
                'is_tax_inclusive' => false,
                'effective_from' => '2026-01-01',
            ]
        );
    }
}
