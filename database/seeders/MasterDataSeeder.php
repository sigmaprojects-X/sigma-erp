<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\StockMutation;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Enforce strict DB::transaction per AGENTS.md Section 1
        DB::transaction(function () {
            // 0. User Owner, Kasir & Manager Toko
            User::updateOrCreate(
                ['email' => 'owner@sigma.test'],
                [
                    'name' => 'Owner SIGMA',
                    'password' => Hash::make('password123'),
                    'role' => 'owner',
                    'is_active' => true,
                ]
            );

            $userKasir = User::updateOrCreate(
                ['email' => 'kasir1@sigma.test'],
                [
                    'name' => 'Budi Santoso (Kasir 1)',
                    'password' => Hash::make('password123'),
                    'role' => 'cashier',
                    'is_active' => true,
                ]
            );

            $userManager = User::updateOrCreate(
                ['email' => 'manager@sigma.test'],
                [
                    'name' => 'Hendro Wijaya (Store Manager)',
                    'password' => Hash::make('password123'),
                    'role' => 'manager',
                    'manager_pin' => Hash::make('1234'),
                    'is_active' => true,
                ]
            );

            // 0b. Pegawai Sales Toko & Lapangan (Untuk Komisi Sales - Pos Salesperson)
            User::updateOrCreate(
                ['email' => 'sales1@sigma.test'],
                [
                    'name' => 'Rian Pratama (Sales Counter)',
                    'password' => Hash::make('password123'),
                    'role' => 'sales',
                    'is_active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'sales2@sigma.test'],
                [
                    'name' => 'Dewi Lestari (Sales Grosir)',
                    'password' => Hash::make('password123'),
                    'role' => 'sales',
                    'is_active' => true,
                ]
            );

            // 0c. Staf Gudang & Finance (Untuk RBAC Testing & Operasional)
            User::updateOrCreate(
                ['email' => 'warehouse@sigma.test'],
                [
                    'name' => 'Anton Kusuma (Staf Gudang)',
                    'password' => Hash::make('password123'),
                    'role' => 'warehouse',
                    'is_active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'finance@sigma.test'],
                [
                    'name' => 'Rina Dewanti (Staf Finance)',
                    'password' => Hash::make('password123'),
                    'role' => 'finance',
                    'is_active' => true,
                ]
            );

            User::updateOrCreate(
                ['email' => 'purchasing@sigma.test'],
                [
                    'name' => 'Agus Prasetyo (Staf Purchasing)',
                    'password' => Hash::make('password123'),
                    'role' => 'purchasing',
                    'is_active' => true,
                ]
            );

            // 1. Buat Perusahaan (Company Multi-Tenancy Root)
            $company = Company::firstOrCreate(
                ['code' => 'SIGMA-HO'],
                [
                    'name' => 'PT SIGMA RETAIL INDONESIA',
                    'tax_id' => '01.234.567.8-901.000',
                    'email' => 'hq@sigmamart.co.id',
                    'phone' => '021-5551234',
                    'address' => 'Jl. Jendral Sudirman No. 88, Jakarta Pusat',
                    'currency_code' => 'IDR',
                    'is_tax_registered' => true,
                    'is_active' => true,
                ]
            );

            // 2. Buat Cabang (Store / Outlet)
            $store = Store::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'STR-001',
                ],
                [
                    'name' => 'SIGMA MART CABANG UTAMA',
                    'type' => 'STORE',
                    'phone' => '021-5559876',
                    'address' => 'Jl. Gatot Subroto Kav. 12, Jakarta Selatan',
                    'is_active' => true,
                ]
            );

            // Tautkan staf operasional cabang ke Store STR-001
            $userKasir->update(['store_id' => $store->id]);
            $userManager->update(['store_id' => $store->id]);
            User::whereIn('email', ['sales1@sigma.test', 'sales2@sigma.test', 'warehouse@sigma.test'])
                ->update(['store_id' => $store->id]);

            // 3. Buat Level Pelanggan (Customer Tiers - Level 1 s/d Level 6 Standar Multi-Tier)
            $tierRetail = CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'RETAIL',
                ],
                [
                    'store_id' => null,
                    'name' => 'Level 1: Umum / Eceran',
                    'description' => 'Harga standar retail untuk pelanggan umum tanpa membership',
                    'default_margin_percentage' => 20.00,
                    'is_default' => true,
                    'is_active' => true,
                ]
            );

            $tierGrosir = CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'GROSIR',
                ],
                [
                    'store_id' => null,
                    'name' => 'Level 2: Grosir / Toko',
                    'description' => 'Harga khusus pedagang, warung, dan agen kulakan',
                    'default_margin_percentage' => 15.00,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );

            CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'AGEN',
                ],
                [
                    'store_id' => null,
                    'name' => 'Level 3: Agen / Reseller',
                    'description' => 'Harga agen dan toko mitra kulakan',
                    'default_margin_percentage' => 12.00,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );

            CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'DISTRIBUTOR',
                ],
                [
                    'store_id' => null,
                    'name' => 'Level 4: Distributor / Partai',
                    'description' => 'Harga pembelian partai besar / distributor',
                    'default_margin_percentage' => 8.00,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );

            CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'MEMBER',
                ],
                [
                    'store_id' => null,
                    'name' => 'Level 5: Member / VIP',
                    'description' => 'Harga pelanggan loyal / kartu member',
                    'default_margin_percentage' => 18.00,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );

            CustomerTier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'KHUSUS',
                ],
                [
                    'store_id' => null,
                    'name' => 'Level 6: Mitra Khusus / Proyek',
                    'description' => 'Harga kontrak proyek dan instansi',
                    'default_margin_percentage' => 5.00,
                    'is_default' => false,
                    'is_active' => true,
                ]
            );

            // 3b. Buat Master Pelanggan (Customer Master Data - AGENTS.md Section 4A)
            Customer::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'CUST-UMUM',
                ],
                [
                    'store_id' => $store->id,
                    'customer_tier_id' => $tierRetail->id,
                    'name' => 'UMUM (Pelanggan Umum / Eceran)',
                    'phone' => '-',
                    'address' => 'Walk-in Customer (Tunai)',
                    'credit_limit' => 0.00,
                    'current_credit_balance' => 0.00,
                    'credit_term_days' => 0,
                    'is_active' => true,
                ]
            );

            Customer::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'CUST-001',
                ],
                [
                    'store_id' => $store->id,
                    'customer_tier_id' => $tierGrosir->id,
                    'name' => 'Toko Berkah (Grosir)',
                    'phone' => '081234567890',
                    'address' => 'Pasar Induk Kramat Jati Kios B-12',
                    'credit_limit' => 10000000.00,
                    'current_credit_balance' => 2500000.00,
                    'credit_term_days' => 14,
                    'is_active' => true,
                ]
            );

            Customer::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'CUST-002',
                ],
                [
                    'store_id' => $store->id,
                    'customer_tier_id' => $tierGrosir->id,
                    'name' => 'Warung Bu Siti (Grosir)',
                    'phone' => '081398765432',
                    'address' => 'Jl. Kebon Jeruk No. 8',
                    'credit_limit' => 5000000.00,
                    'current_credit_balance' => 1200000.00,
                    'credit_term_days' => 7,
                    'is_active' => true,
                ]
            );

            Customer::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => 'CUST-003',
                ],
                [
                    'store_id' => $store->id,
                    'customer_tier_id' => $tierGrosir->id,
                    'name' => 'CV Maju Jaya (Grosir)',
                    'phone' => '081122334455',
                    'address' => 'Ruko Sentra Niaga Blok C-5',
                    'credit_limit' => 25000000.00,
                    'current_credit_balance' => 0.00,
                    'credit_term_days' => 30,
                    'is_active' => true,
                ]
            );

            // 3b. Master Kategori Produk (Product Categories)
            $catSembako = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'SEMBAKO'],
                ['name' => 'Sembako & Bahan Pokok', 'description' => 'Beras, minyak goreng, gula, tepung, telur', 'is_active' => true]
            );

            $catMinuman = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'MINUMAN'],
                ['name' => 'Minuman & Kemasan', 'description' => 'Air mineral, teh, kopi, minuman bersoda', 'is_active' => true]
            );

            $catSnack = ProductCategory::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'SNACK'],
                ['name' => 'Makanan Ringan & Biskuit', 'description' => 'Snack, biskuit, wafer, permen', 'is_active' => true]
            );

            // 3c. Master Satuan Standar (Units of Measure)
            $unitsData = [
                ['code' => 'PCS', 'name' => 'Pieces / Satuan'],
                ['code' => 'SAK', 'name' => 'Sak / Karung'],
                ['code' => 'DUS', 'name' => 'Dus / Karton'],
                ['code' => 'KG', 'name' => 'Kilogram'],
                ['code' => 'LITER', 'name' => 'Liter'],
                ['code' => 'PACK', 'name' => 'Pack / Bungkus'],
                ['code' => 'RENCENG', 'name' => 'Renceng'],
                ['code' => 'BOX', 'name' => 'Box / Kotak'],
            ];
            foreach ($unitsData as $u) {
                Unit::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $u['code']],
                    ['name' => $u['name'], 'is_active' => true]
                );
            }

            // 3d. Master Rekening Bank & E-Money
            $bankAccountsData = [
                [
                    'bank_code' => 'BCA',
                    'bank_name' => 'Bank Central Asia',
                    'account_number' => '1234567890',
                    'account_holder' => 'PT SIGMA RETAIL INDONESIA',
                    'branch' => 'KCU Sudirman Jakarta',
                ],
                [
                    'bank_code' => 'MANDIRI',
                    'bank_name' => 'Bank Mandiri',
                    'account_number' => '0987654321',
                    'account_holder' => 'PT SIGMA RETAIL INDONESIA',
                    'branch' => 'KCP Gatot Subroto',
                ],
                [
                    'bank_code' => 'BRI',
                    'bank_name' => 'Bank Rakyat Indonesia',
                    'account_number' => '334455667788',
                    'account_holder' => 'PT SIGMA RETAIL INDONESIA',
                    'branch' => 'KC Jakarta Thamrin',
                ],
                [
                    'bank_code' => 'QRIS',
                    'bank_name' => 'QRIS Dinamis / Statis Toko',
                    'account_number' => 'ID102030405060',
                    'account_holder' => 'SIGMA MART UTAMA',
                    'branch' => 'Merchant Aggregator',
                ],
            ];
            foreach ($bankAccountsData as $b) {
                BankAccount::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'bank_code' => $b['bank_code'],
                        'account_number' => $b['account_number'],
                    ],
                    $b
                );
            }

            // 3e. Master Supplier (Pemasok)
            $suppliersData = [
                [
                    'code' => 'SUP-001',
                    'name' => 'PT INDOFOOD CBP SUKSES MAKMUR',
                    'contact_person' => 'Bambang Irawan (Sales Spv)',
                    'phone' => '021-57958822',
                    'email' => 'sales@indofood.co.id',
                    'address' => 'Sudirman Plaza, Indofood Tower Lt. 23, Jakarta',
                    'term_of_payment_days' => 30,
                    'bank_name' => 'BCA',
                    'bank_account_no' => '0019283746',
                    'bank_account_holder' => 'PT Indofood CBP Sukses Makmur Tbk',
                ],
                [
                    'code' => 'SUP-002',
                    'name' => 'PT WINGS SURYA DISTRIBUTION',
                    'contact_person' => 'Agus Hartono',
                    'phone' => '021-4600101',
                    'email' => 'order@wingscorp.com',
                    'address' => 'Kawasan Industri Pulogadung Blok B-3, Jakarta Timur',
                    'term_of_payment_days' => 14,
                    'bank_name' => 'Mandiri',
                    'bank_account_no' => '1250009876543',
                    'bank_account_holder' => 'PT Wings Surya Distribution',
                ],
                [
                    'code' => 'SUP-003',
                    'name' => 'CV SUMBER BERAS MAKMUR JAYA',
                    'contact_person' => 'Haji Mansyur',
                    'phone' => '081288990011',
                    'email' => 'sumberberas@gmail.com',
                    'address' => 'Pasar Induk Beras Cipinang Kios AL-09, Jakarta Timur',
                    'term_of_payment_days' => 0, // Tunai / Cash saat bongkar
                    'bank_name' => 'BRI',
                    'bank_account_no' => '012301000999501',
                    'bank_account_holder' => 'H. Mansyur / CV Sumber Beras',
                ],
            ];
            foreach ($suppliersData as $s) {
                Supplier::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $s['code']],
                    $s
                );
            }

            // 4. Produk Sembako 1: Beras Premium Pandan Wangi 5kg
            $productBeras = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'SBK-BRS-001'],
                [
                    'category_id' => $catSembako->id,
                    'name' => 'Beras Premium Pandan Wangi 5kg',
                    'slug' => 'beras-premium-pandan-wangi-5kg',
                    'category' => 'Sembako',
                    'brand' => 'Pandan Wangi',
                    'cost_price' => 68000.0000,
                    'last_purchase_price' => 68000.0000,
                    'total_stock_value' => 6800000.0000,
                    'total_quantity' => 100.0000,
                    'min_stock_alert' => 20.00,
                    'is_point_of_sale' => true,
                    'is_active' => true,
                ]
            );

            $unitBerasSak = ProductUnit::firstOrCreate(
                ['product_id' => $productBeras->id, 'unit_code' => 'SAK'],
                ['company_id' => $company->id, 'unit_name' => 'Sak 5 Kg', 'conversion_factor' => 1.0000, 'is_base_unit' => true, 'is_active' => true]
            );
            $unitBerasBal = ProductUnit::firstOrCreate(
                ['product_id' => $productBeras->id, 'unit_code' => 'BAL'],
                ['company_id' => $company->id, 'unit_name' => 'Bal (isi 5 Sak)', 'conversion_factor' => 5.0000, 'is_base_unit' => false, 'is_active' => true]
            );

            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560011'], ['product_id' => $productBeras->id, 'product_unit_id' => $unitBerasSak->id, 'is_primary' => true]);
            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560012'], ['product_id' => $productBeras->id, 'product_unit_id' => $unitBerasBal->id, 'is_primary' => true]);

            $this->seedPrices($company, $productBeras, [
                ['unit' => $unitBerasSak, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 75000.00, 'margin' => 10.29],
                ['unit' => $unitBerasSak, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 71000.00, 'margin' => 4.41],
                ['unit' => $unitBerasSak, 'tier' => $tierGrosir, 'min_qty' => 10.00, 'price' => 69500.00, 'margin' => 2.21],
                ['unit' => $unitBerasBal, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 370000.00, 'margin' => 8.82],
                ['unit' => $unitBerasBal, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 350000.00, 'margin' => 2.94],
                ['unit' => $unitBerasBal, 'tier' => $tierGrosir, 'min_qty' => 5.00, 'price' => 345000.00, 'margin' => 1.47],
            ]);
            $this->seedStock($company, $store, $productBeras, 100.0, 68000.0, 'Saldo awal stok Beras Premium');

            // 5. Produk Sembako 2: Minyak Goreng Sawit 1 Liter
            $productMinyak = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'SBK-MYK-001'],
                [
                    'name' => 'Minyak Goreng Sawit 1 Liter',
                    'slug' => 'minyak-goreng-sawit-1-liter',
                    'category' => 'Sembako',
                    'brand' => 'Sanira',
                    'cost_price' => 15000.0000,
                    'last_purchase_price' => 15000.0000,
                    'total_stock_value' => 3600000.0000,
                    'total_quantity' => 240.0000,
                    'min_stock_alert' => 48.00,
                    'is_point_of_sale' => true,
                    'is_active' => true,
                ]
            );

            $unitMinyakPcs = ProductUnit::firstOrCreate(
                ['product_id' => $productMinyak->id, 'unit_code' => 'PCS'],
                ['company_id' => $company->id, 'unit_name' => 'Botol 1 Liter', 'conversion_factor' => 1.0000, 'is_base_unit' => true, 'is_active' => true]
            );
            $unitMinyakDus = ProductUnit::firstOrCreate(
                ['product_id' => $productMinyak->id, 'unit_code' => 'DUS'],
                ['company_id' => $company->id, 'unit_name' => 'Karton Dus (isi 12 Botol)', 'conversion_factor' => 12.0000, 'is_base_unit' => false, 'is_active' => true]
            );

            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560021'], ['product_id' => $productMinyak->id, 'product_unit_id' => $unitMinyakPcs->id, 'is_primary' => true]);
            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560022'], ['product_id' => $productMinyak->id, 'product_unit_id' => $unitMinyakDus->id, 'is_primary' => true]);

            $this->seedPrices($company, $productMinyak, [
                ['unit' => $unitMinyakPcs, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 17500.00, 'margin' => 16.67],
                ['unit' => $unitMinyakPcs, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 16200.00, 'margin' => 8.00],
                ['unit' => $unitMinyakPcs, 'tier' => $tierGrosir, 'min_qty' => 12.00, 'price' => 15800.00, 'margin' => 5.33],
                ['unit' => $unitMinyakDus, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 205000.00, 'margin' => 13.89],
                ['unit' => $unitMinyakDus, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 190000.00, 'margin' => 5.56],
                ['unit' => $unitMinyakDus, 'tier' => $tierGrosir, 'min_qty' => 5.00, 'price' => 186000.00, 'margin' => 3.33],
            ]);
            $this->seedStock($company, $store, $productMinyak, 240.0, 15000.0, 'Saldo awal stok Minyak Goreng');

            // 6. Produk Sembako 3: Gula Pasir Putih Premium 1kg
            $productGula = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'SBK-GLA-001'],
                [
                    'name' => 'Gula Pasir Putih Premium 1kg',
                    'slug' => 'gula-pasir-putih-premium-1kg',
                    'category' => 'Sembako',
                    'brand' => 'Gulaku',
                    'cost_price' => 14500.0000,
                    'last_purchase_price' => 14500.0000,
                    'total_stock_value' => 2900000.0000,
                    'total_quantity' => 200.0000,
                    'min_stock_alert' => 30.00,
                    'is_point_of_sale' => true,
                    'is_active' => true,
                ]
            );

            $unitGulaBks = ProductUnit::firstOrCreate(
                ['product_id' => $productGula->id, 'unit_code' => 'BKS'],
                ['company_id' => $company->id, 'unit_name' => 'Bungkus 1 Kg', 'conversion_factor' => 1.0000, 'is_base_unit' => true, 'is_active' => true]
            );
            $unitGulaKarung = ProductUnit::firstOrCreate(
                ['product_id' => $productGula->id, 'unit_code' => 'KRG'],
                ['company_id' => $company->id, 'unit_name' => 'Karung (isi 50 Bks)', 'conversion_factor' => 50.0000, 'is_base_unit' => false, 'is_active' => true]
            );

            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560031'], ['product_id' => $productGula->id, 'product_unit_id' => $unitGulaBks->id, 'is_primary' => true]);
            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560032'], ['product_id' => $productGula->id, 'product_unit_id' => $unitGulaKarung->id, 'is_primary' => true]);

            $this->seedPrices($company, $productGula, [
                ['unit' => $unitGulaBks, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 17000.00, 'margin' => 17.24],
                ['unit' => $unitGulaBks, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 15800.00, 'margin' => 8.97],
                ['unit' => $unitGulaBks, 'tier' => $tierGrosir, 'min_qty' => 10.00, 'price' => 15300.00, 'margin' => 5.52],
                ['unit' => $unitGulaKarung, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 825000.00, 'margin' => 13.79],
                ['unit' => $unitGulaKarung, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 765000.00, 'margin' => 5.52],
            ]);
            $this->seedStock($company, $store, $productGula, 200.0, 14500.0, 'Saldo awal stok Gula Pasir');

            // 7. Produk Sembako 4: Tepung Terigu Segitiga Biru 1kg
            $productTerigu = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'SBK-TRG-001'],
                [
                    'name' => 'Tepung Terigu Segitiga Biru 1kg',
                    'slug' => 'tepung-terigu-segitiga-biru-1kg',
                    'category' => 'Sembako',
                    'brand' => 'Bogasari',
                    'cost_price' => 11000.0000,
                    'last_purchase_price' => 11000.0000,
                    'total_stock_value' => 1650000.0000,
                    'total_quantity' => 150.0000,
                    'min_stock_alert' => 25.00,
                    'is_point_of_sale' => true,
                    'is_active' => true,
                ]
            );

            $unitTeriguPcs = ProductUnit::firstOrCreate(
                ['product_id' => $productTerigu->id, 'unit_code' => 'PCS'],
                ['company_id' => $company->id, 'unit_name' => 'Kemasan 1 Kg', 'conversion_factor' => 1.0000, 'is_base_unit' => true, 'is_active' => true]
            );
            $unitTeriguDus = ProductUnit::firstOrCreate(
                ['product_id' => $productTerigu->id, 'unit_code' => 'DUS'],
                ['company_id' => $company->id, 'unit_name' => 'Dus Karton (isi 12 Pcs)', 'conversion_factor' => 12.0000, 'is_base_unit' => false, 'is_active' => true]
            );

            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560041'], ['product_id' => $productTerigu->id, 'product_unit_id' => $unitTeriguPcs->id, 'is_primary' => true]);
            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560042'], ['product_id' => $productTerigu->id, 'product_unit_id' => $unitTeriguDus->id, 'is_primary' => true]);

            $this->seedPrices($company, $productTerigu, [
                ['unit' => $unitTeriguPcs, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 13500.00, 'margin' => 22.73],
                ['unit' => $unitTeriguPcs, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 12200.00, 'margin' => 10.91],
                ['unit' => $unitTeriguPcs, 'tier' => $tierGrosir, 'min_qty' => 12.00, 'price' => 11800.00, 'margin' => 7.27],
                ['unit' => $unitTeriguDus, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 155000.00, 'margin' => 17.42],
                ['unit' => $unitTeriguDus, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 141000.00, 'margin' => 6.82],
            ]);
            $this->seedStock($company, $store, $productTerigu, 150.0, 11000.0, 'Saldo awal stok Tepung Terigu');

            // 8. Produk Sembako 5: Telur Ayam Ras Segar (Piring & Ikat)
            $productTelur = Product::firstOrCreate(
                ['company_id' => $company->id, 'sku' => 'SBK-TLR-001'],
                [
                    'name' => 'Telur Ayam Ras Segar 1 Piring',
                    'slug' => 'telur-ayam-ras-segar-1-piring',
                    'category' => 'Sembako',
                    'brand' => 'Peternak Lokal',
                    'cost_price' => 48000.0000,
                    'last_purchase_price' => 48000.0000,
                    'total_stock_value' => 3840000.0000,
                    'total_quantity' => 80.0000,
                    'min_stock_alert' => 15.00,
                    'is_point_of_sale' => true,
                    'is_active' => true,
                ]
            );

            $unitTelurPrg = ProductUnit::firstOrCreate(
                ['product_id' => $productTelur->id, 'unit_code' => 'PRG'],
                ['company_id' => $company->id, 'unit_name' => 'Piring (isi 30 Butir)', 'conversion_factor' => 1.0000, 'is_base_unit' => true, 'is_active' => true]
            );
            $unitTelurIkt = ProductUnit::firstOrCreate(
                ['product_id' => $productTelur->id, 'unit_code' => 'IKT'],
                ['company_id' => $company->id, 'unit_name' => 'Ikat (isi 5 Piring / 150 Butir)', 'conversion_factor' => 5.0000, 'is_base_unit' => false, 'is_active' => true]
            );

            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560051'], ['product_id' => $productTelur->id, 'product_unit_id' => $unitTelurPrg->id, 'is_primary' => true]);
            ProductBarcode::firstOrCreate(['company_id' => $company->id, 'barcode' => '8991234560052'], ['product_id' => $productTelur->id, 'product_unit_id' => $unitTelurIkt->id, 'is_primary' => true]);

            $this->seedPrices($company, $productTelur, [
                ['unit' => $unitTelurPrg, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 55000.00, 'margin' => 14.58],
                ['unit' => $unitTelurPrg, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 51500.00, 'margin' => 7.29],
                ['unit' => $unitTelurPrg, 'tier' => $tierGrosir, 'min_qty' => 5.00, 'price' => 50000.00, 'margin' => 4.17],
                ['unit' => $unitTelurIkt, 'tier' => $tierRetail, 'min_qty' => 1.00, 'price' => 265000.00, 'margin' => 10.42],
                ['unit' => $unitTelurIkt, 'tier' => $tierGrosir, 'min_qty' => 1.00, 'price' => 248000.00, 'margin' => 3.33],
            ]);
            $this->seedStock($company, $store, $productTelur, 80.0, 48000.0, 'Saldo awal stok Telur Ayam');

            // 9. Contoh Voucher Siap Pakai
            Voucher::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'HEMAT20K'],
                [
                    'type' => 'VALUE',
                    'amount' => 20000.00,
                    'min_spend' => 50000.00,
                    'status' => 'ISSUED',
                    'valid_from' => now()->subDay(),
                    'valid_until' => now()->addMonth(),
                ]
            );

            Voucher::firstOrCreate(
                ['company_id' => $company->id, 'code' => 'DISKON10'],
                [
                    'type' => 'DISCOUNT_PERCENT',
                    'amount' => 10.00,
                    'min_spend' => 100000.00,
                    'status' => 'ISSUED',
                    'valid_from' => now()->subDay(),
                    'valid_until' => now()->addMonth(),
                ]
            );
        });
    }

    protected function seedPrices(Company $company, Product $product, array $prices): void
    {
        foreach ($prices as $p) {
            ProductPrice::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'store_id' => null,
                    'product_id' => $product->id,
                    'product_unit_id' => $p['unit']->id,
                    'customer_tier_id' => $p['tier']->id,
                    'min_quantity' => $p['min_qty'],
                ],
                [
                    'price' => $p['price'],
                    'margin_percentage' => $p['margin'],
                    'is_active' => true,
                ]
            );
        }
    }

    protected function seedStock(Company $company, Store $store, Product $product, float $qty, float $cost, string $notes): void
    {
        ProductStock::updateOrCreate(
            [
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $product->id,
            ],
            [
                'physical_quantity' => $qty,
                'reserved_quantity' => 0.0000,
                'quarantined_quantity' => 0.0000,
            ]
        );

        StockMutation::firstOrCreate(
            [
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'reference_number' => 'INIT-'.$product->sku,
            ],
            [
                'mutation_type' => 'STOCK_OPNAME_ADJUSTMENT',
                'quantity' => $qty,
                'unit_cost' => $cost,
                'total_cost' => $qty * $cost,
                'qty_before' => 0.0000,
                'qty_after' => $qty,
                'notes' => $notes,
            ]
        );
    }
}
