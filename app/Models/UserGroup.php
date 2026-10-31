<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    use HasUuids;

    protected $table = 'user_groups';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'permissions',
        'is_system',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_system' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'user_group_id');
    }

    /**
     * Cek apakah kelompok user memiliki izin tertentu.
     */
    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions ?? [];

        if ($this->code === 'ADMINISTRATOR' || in_array('*', $perms, true)) {
            return true;
        }

        if (in_array($permission, $perms, true)) {
            return true;
        }

        if (str_contains($permission, '.')) {
            [$module] = explode('.', $permission, 2);
            if (in_array("{$module}.*", $perms, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Daftar izin default berdasarkan role jika pengguna belum dikaitkan ke UserGroup tertentu.
     *
     * @return list<string>
     */
    public static function getDefaultPermissionsForRole(string $role): array
    {
        $presets = self::getPresets();

        return match ($role) {
            'owner' => ['*'],
            'manager' => $presets['MANAGER']['permissions'],
            'store_head' => $presets['SUPERVISOR']['permissions'],
            'cashier' => $presets['KASIR']['permissions'],
            'warehouse' => $presets['GUDANG']['permissions'],
            'purchasing' => $presets['PURCHASING']['permissions'],
            'finance' => $presets['KEUANGAN']['permissions'],
            'sales' => $presets['SALES']['permissions'],
            default => [],
        };
    }

    /**
     * Katalog Resmi Seluruh Hak Akses Terstruktur Berdasarkan Kategori Modul (Standar Ketoko.id & iPos).
     */
    public static function getPermissionCatalog(): array
    {
        return [
            'master_data' => [
                'name' => 'Master Data',
                'icon' => '📁',
                'description' => 'Data Barang Item, Pelanggan, Supplier, Satuan, dan Kategori',
                'items' => [
                    [
                        'key' => 'master_item',
                        'label' => 'Master Item / Barang Dagang',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'master_supplier',
                        'label' => 'Master Pemasok / Supplier',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'master_customer',
                        'label' => 'Master Pelanggan & Plafon Kredit',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'master_sales',
                        'label' => 'Master Sales / Pramuniaga Toko',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'master_warehouse',
                        'label' => 'Data Departemen & Gudang Toko',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'kartu_stok',
                        'label' => 'Kartu Stok & Mutasi Real-Time',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'view_cost_price',
                        'label' => '🔒 Tampil Harga Pokok Beli (HPP) pada Master Data',
                        'description' => 'Sembunyikan dari kasir untuk menjaga rahasia dagang toko',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'view_avg_cost',
                        'label' => '🔒 Tampil Nilai HPP Rata-Rata (Moving Average) pada Master Item',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'view_selling_price',
                        'label' => 'Tampil Harga Jual pada Pencarian & Informasi Data Item',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'master_satuan_kategori',
                        'label' => 'Satuan, Kategori, Sub-Jenis & Merek',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                ],
            ],

            'pembelian' => [
                'name' => 'Pembelian',
                'icon' => '🛒',
                'description' => 'Purchase Order, Penerimaan Barang LPB, dan Hutang Usaha',
                'items' => [
                    [
                        'key' => 'purchase_order',
                        'label' => 'Pesanan Pembelian (Purchase Order / PO)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'purchase_receipt',
                        'label' => 'Penerimaan Fisik Barang Gudang (LPB / Surat Jalan)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'purchase_invoice',
                        'label' => 'Faktur Pembelian & Three-Way Matching',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'purchase_payment',
                        'label' => 'Pembayaran Hutang Usaha Supplier (AP)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'purchase_return',
                        'label' => 'Retur Pembelian Barang ke Supplier & Tukar Guling',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'purchase_price_history',
                        'label' => 'Riwayat / History Harga Beli Supplier',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'purchase_show_cost',
                        'label' => 'Tampil Harga Pokok pada Pencarian Item Pembelian',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                ],
            ],

            'penjualan' => [
                'name' => 'Penjualan & Kasir POS',
                'icon' => '💵',
                'description' => 'Operasional Kasir, Point of Sale, Struk, Piutang, dan SOP Transaksi',
                'items' => [
                    [
                        'key' => 'pos_terminal',
                        'label' => 'Layar Kasir POS (Point of Sale Desktop)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'sales_order',
                        'label' => 'Pesanan Penjualan (Sales Order Grosir)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'sales_invoice',
                        'label' => 'Faktur Penjualan Back-Office / Pengiriman',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'sales_receivable_payment',
                        'label' => 'Penerimaan Pembayaran Piutang Pelanggan (AR)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'pos_return',
                        'label' => 'Retur Penjualan / Tukar Barang Konsumen di Kasir',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'pos_shift_management',
                        'label' => 'Buka Shift, Tutup Shift Kasir (Blind Closing) & Cash Drop',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_shift_discrepancy_resolve',
                        'label' => '🔒 Resolusi & Persetujuan Selisih Kas Kasir (Manager)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_cash_drawer',
                        'label' => 'Buka Laci Kasir Manual (No-Sale Drawer Kick)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_reprint_past_shift',
                        'label' => '🔒 Cetak Ulang Struk Transaksi Shift Lampau (Butuh PIN)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_credit_payment',
                        'label' => 'Modul Kasir: Aktifkan Opsi Pembayaran Kredit / Tempo',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_sell_below_min_margin',
                        'label' => '🔒 Boleh Jual di Bawah Keuntungan Minimal (Override Margin)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_sell_below_cost',
                        'label' => '🔒 Boleh Jual di Bawah Harga Modal Beli HPP (Sangat Sensitif)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_lock_customer_field',
                        'label' => 'Modul Kasir: Kunci Kolom Pelanggan (Hanya Kasir Cepat Umum)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'pos_void_cancel_item',
                        'label' => 'Batal & Hapus Detail Item pada Keranjang Kasir',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => true, 'kunci' => false],
                    ],
                ],
            ],

            'persediaan' => [
                'name' => 'Persediaan & Gudang',
                'icon' => '📦',
                'description' => 'Mutasi Stok, Transfer Antar Cabang, dan Stock Opname Buta',
                'items' => [
                    [
                        'key' => 'stock_in',
                        'label' => 'Item Masuk (Penyesuaian Masuk)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'stock_out',
                        'label' => 'Item Keluar (Penyesuaian Keluar / Rusak / Expired)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'stock_transfer',
                        'label' => 'Transfer Antar Cabang (Surat Jalan & Dispatch)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'stock_opname',
                        'label' => 'Stock Opname Fisik (Blind Counting)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'stock_opname_approve',
                        'label' => '🔒 Persetujuan Selisih Opname Gudang (Manager)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'batch_expiry_fefo',
                        'label' => 'Lot Batch Number, Expired Date & FEFO Recommendations',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'consignment_management',
                        'label' => 'Konsinyasi Titipan Pemasok & Rekonsiliasi Terjual',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                ],
            ],

            'perakitan' => [
                'name' => 'Perakitan & Paket Bundling',
                'icon' => '🔨',
                'description' => 'Bill of Materials (BOM), Perakitan Produk & Pembongkaran',
                'items' => [
                    [
                        'key' => 'assembly_bom',
                        'label' => 'Rumus Formula Paket Bundling (Bill of Materials)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'assembly_process',
                        'label' => 'Proses Perakitan Barang Jadi',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'assembly_disassemble',
                        'label' => 'Pembongkaran Paket Bundling ke Item Komponen',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                ],
            ],

            'akuntansi' => [
                'name' => 'Akuntansi & Kas',
                'icon' => '📑',
                'description' => 'Kas Masuk, Kas Keluar, Bagan Akun COA, Jurnal Umum, dan Deposito',
                'items' => [
                    [
                        'key' => 'accounting_coa',
                        'label' => 'Bagan Akun (Chart of Accounts / COA)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'cash_in',
                        'label' => 'Kas Masuk (Bukti Kas Masuk / BKM)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'cash_out',
                        'label' => 'Kas Keluar & Biaya Operasional (BKK)',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'cash_transfer',
                        'label' => 'Transfer Antar Rekening Kas & Bank',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'accounting_journals',
                        'label' => 'Jurnal Umum Manual & Memorial',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => true],
                    ],
                    [
                        'key' => 'accounting_ledger',
                        'label' => 'Buku Besar Akun (General Ledger)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'accounting_settings_map',
                        'label' => 'Setting Pemetaan Akun Jurnal Otomatis',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => true, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'fixed_assets',
                        'label' => 'Aset Tetap & Depresiasi Garis Lurus Otomatis',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                ],
            ],

            'laporan' => [
                'name' => 'Laporan Eksekutif & Analisis',
                'icon' => '📊',
                'description' => 'Laporan Omzet, Laba Rugi, Kartu Stok, Hutang Piutang, dan Analisis Cabang',
                'items' => [
                    [
                        'key' => 'report_sales',
                        'label' => 'Laporan Penjualan (Per Item, Kasir, Cabang, Periode)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'report_purchases',
                        'label' => 'Laporan Pembelian & Pasokan Supplier',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'report_profit_loss',
                        'label' => '🔒 Laporan Laba Rugi Toko & Gross Profit HPP (Sensitif)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'report_balance_sheet',
                        'label' => '🔒 Laporan Neraca Keuangan Toko',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'report_aging',
                        'label' => 'Laporan Umur Piutang (AR) & Umur Hutang (AP)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'report_cash_flow',
                        'label' => 'Laporan Arus Kas (Cash Flow Toko)',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'report_stock_valuation',
                        'label' => 'Laporan Valuasi Nilai Persediaan Stok Toko',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                ],
            ],

            'tampilan_menu' => [
                'name' => 'Tampilan Menu & Dashboard',
                'icon' => '🖥️',
                'description' => 'Visibilitas Menu di Desktop dan Alur Saat Login',
                'items' => [
                    [
                        'key' => 'menu_direct_pos',
                        'label' => '⚡ Login Langsung Tampil Modul Kasir POS (Tanpa Desktop)',
                        'description' => 'Sangat cocok untuk staf kasir agar langsung bekerja di POS',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_dashboard_charts',
                        'label' => 'Tampil Grafik Omzet & Statistik pada Dashboard Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_view_master',
                        'label' => 'Tampil Menu Master Data di Navigasi Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_view_purchasing',
                        'label' => 'Tampil Menu Pembelian di Navigasi Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_view_sales',
                        'label' => 'Tampil Menu Penjualan di Navigasi Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_view_inventory',
                        'label' => 'Tampil Menu Persediaan Gudang di Navigasi Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_view_accounting',
                        'label' => 'Tampil Menu Akuntansi & Kas di Navigasi Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'menu_view_reports',
                        'label' => 'Tampil Menu Laporan di Navigasi Utama',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => false, 'hapus' => false, 'kunci' => false],
                    ],
                ],
            ],

            'pengaturan_lainnya' => [
                'name' => 'Pengaturan Sistem & Admin',
                'icon' => '⚙️',
                'description' => 'Akses Backup, Manajemen User, dan Konfigurasi Toko',
                'items' => [
                    [
                        'key' => 'settings_general',
                        'label' => 'Pengaturan Umum, Wallpaper Desktop & Identitas Toko',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => true, 'hapus' => false, 'kunci' => false],
                    ],
                    [
                        'key' => 'settings_users_rbac',
                        'label' => '🔒 Manajemen Pengguna, Staf Kasir & Kelompok Hak Akses',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => true, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'settings_backup_db',
                        'label' => '🔒 Akses Backup Database PostgreSQL & Download File Cadangan',
                        'actions' => ['buka' => true, 'baru' => true, 'ubah' => false, 'hapus' => true, 'kunci' => false],
                    ],
                    [
                        'key' => 'settings_numbering',
                        'label' => 'Pengaturan Format Penomoran Faktur & Dokumen Otomatis',
                        'actions' => ['buka' => true, 'baru' => false, 'ubah' => true, 'hapus' => false, 'kunci' => false],
                    ],
                ],
            ],
        ];
    }

    /**
     * Preset Cepat untuk Role Standar Toko Ritel.
     */
    public static function getPresets(): array
    {
        return [
            'ADMINISTRATOR' => [
                'name' => 'Administrator / Owner',
                'description' => 'Akses Penuh Seluruh Modul, HPP, Finansial, dan Pengaturan Sistem',
                'permissions' => ['*'],
            ],
            'MANAGER' => [
                'name' => 'Manajer Operasional Toko',
                'description' => 'Otorisasi Penuh Operasional Toko, Pembelian, Penjualan, Gudang, dan Laporan',
                'permissions' => [
                    'master_item.buka', 'master_item.baru', 'master_item.ubah', 'master_item.hapus',
                    'master_supplier.buka', 'master_supplier.baru', 'master_supplier.ubah', 'master_supplier.hapus',
                    'master_customer.buka', 'master_customer.baru', 'master_customer.ubah', 'master_customer.hapus',
                    'master_sales.buka', 'master_sales.baru', 'master_sales.ubah', 'master_sales.hapus',
                    'master_warehouse.buka', 'master_warehouse.baru', 'master_warehouse.ubah', 'master_warehouse.hapus',
                    'master_satuan_kategori.buka', 'master_satuan_kategori.baru', 'master_satuan_kategori.ubah', 'master_satuan_kategori.hapus',
                    'kartu_stok.buka', 'view_cost_price.buka', 'view_avg_cost.buka', 'view_selling_price.buka',
                    'purchase_order.buka', 'purchase_order.baru', 'purchase_order.ubah', 'purchase_order.hapus', 'purchase_order.kunci',
                    'purchase_receipt.buka', 'purchase_receipt.baru', 'purchase_receipt.ubah', 'purchase_receipt.hapus',
                    'purchase_invoice.buka', 'purchase_invoice.baru', 'purchase_invoice.ubah', 'purchase_invoice.hapus',
                    'purchase_payment.buka', 'purchase_payment.baru', 'purchase_payment.ubah', 'purchase_payment.hapus',
                    'purchase_return.buka', 'purchase_return.baru', 'purchase_return.ubah', 'purchase_return.hapus',
                    'purchase_price_history.buka', 'purchase_show_cost.buka',
                    'pos_terminal.buka', 'pos_terminal.baru',
                    'sales_order.buka', 'sales_order.baru', 'sales_order.ubah', 'sales_order.hapus',
                    'sales_invoice.buka', 'sales_invoice.baru', 'sales_invoice.ubah', 'sales_invoice.hapus',
                    'sales_receivable_payment.buka', 'sales_receivable_payment.baru', 'sales_receivable_payment.ubah',
                    'pos_return.buka', 'pos_return.baru', 'pos_return.hapus',
                    'pos_shift_management.buka', 'pos_shift_management.baru',
                    'pos_shift_discrepancy_resolve.buka', 'pos_shift_discrepancy_resolve.baru',
                    'pos_cash_drawer.buka', 'pos_reprint_past_shift.buka', 'pos_credit_payment.buka',
                    'pos_sell_below_min_margin.buka', 'pos_void_cancel_item.buka', 'pos_void_cancel_item.hapus',
                    'stock_in.buka', 'stock_in.baru', 'stock_in.ubah', 'stock_in.hapus',
                    'stock_out.buka', 'stock_out.baru', 'stock_out.ubah', 'stock_out.hapus',
                    'stock_transfer.buka', 'stock_transfer.baru', 'stock_transfer.ubah', 'stock_transfer.hapus',
                    'stock_opname.buka', 'stock_opname.baru', 'stock_opname.ubah', 'stock_opname.hapus',
                    'stock_opname_approve.buka', 'stock_opname_approve.baru',
                    'batch_expiry_fefo.buka', 'batch_expiry_fefo.baru', 'batch_expiry_fefo.ubah',
                    'consignment_management.buka', 'consignment_management.baru', 'consignment_management.ubah', 'consignment_management.hapus',
                    'assembly_bom.buka', 'assembly_bom.baru', 'assembly_bom.ubah', 'assembly_bom.hapus',
                    'assembly_process.buka', 'assembly_process.baru', 'assembly_process.ubah', 'assembly_process.hapus',
                    'assembly_disassemble.buka', 'assembly_disassemble.baru',
                    'accounting_coa.buka', 'accounting_coa.baru', 'accounting_coa.ubah',
                    'cash_in.buka', 'cash_in.baru', 'cash_in.ubah', 'cash_in.hapus',
                    'cash_out.buka', 'cash_out.baru', 'cash_out.ubah', 'cash_out.hapus',
                    'cash_transfer.buka', 'cash_transfer.baru', 'cash_transfer.ubah', 'cash_transfer.hapus',
                    'accounting_journals.buka', 'accounting_journals.baru', 'accounting_journals.ubah', 'accounting_journals.hapus',
                    'accounting_ledger.buka', 'accounting_settings_map.buka', 'accounting_settings_map.ubah',
                    'fixed_assets.buka', 'fixed_assets.baru', 'fixed_assets.ubah', 'fixed_assets.hapus',
                    'report_sales.buka', 'report_purchases.buka', 'report_profit_loss.buka', 'report_balance_sheet.buka', 'report_aging.buka', 'report_cash_flow.buka', 'report_stock_valuation.buka',
                    'menu_dashboard_charts.buka', 'menu_view_master.buka', 'menu_view_purchasing.buka', 'menu_view_sales.buka', 'menu_view_inventory.buka', 'menu_view_accounting.buka', 'menu_view_reports.buka',
                    'settings_general.buka', 'settings_general.ubah',
                    'settings_users_rbac.buka', 'settings_users_rbac.baru', 'settings_users_rbac.ubah', 'settings_users_rbac.hapus',
                    'settings_backup_db.buka', 'settings_backup_db.baru', 'settings_backup_db.hapus',
                    'settings_numbering.buka', 'settings_numbering.ubah',
                ],
            ],
            'SUPERVISOR' => [
                'name' => 'Kepala Toko / Supervisor',
                'description' => 'Supervisi Kasir, Otorisasi Diskon/Void, Cetak Ulang Shift Lampau, dan Opname',
                'permissions' => [
                    'master_item.buka', 'master_customer.buka', 'master_supplier.buka', 'master_warehouse.buka', 'master_satuan_kategori.buka', 'kartu_stok.buka', 'view_selling_price.buka',
                    'pos_terminal.buka', 'pos_terminal.baru',
                    'sales_order.buka', 'sales_order.baru', 'sales_order.ubah', 'sales_order.hapus',
                    'sales_invoice.buka', 'sales_invoice.baru', 'sales_invoice.ubah',
                    'sales_receivable_payment.buka', 'sales_receivable_payment.baru',
                    'pos_return.buka', 'pos_return.baru',
                    'pos_shift_management.buka', 'pos_shift_management.baru',
                    'pos_shift_discrepancy_resolve.buka', 'pos_shift_discrepancy_resolve.baru', 'pos_cash_drawer.buka',
                    'pos_reprint_past_shift.buka', 'pos_credit_payment.buka',
                    'pos_void_cancel_item.buka', 'pos_void_cancel_item.hapus',
                    'purchase_receipt.buka', 'purchase_receipt.baru',
                    'stock_in.buka', 'stock_in.baru', 'stock_out.buka', 'stock_out.baru',
                    'stock_transfer.buka', 'stock_transfer.baru', 'stock_transfer.ubah',
                    'stock_opname.buka', 'stock_opname.baru', 'stock_opname_approve.buka', 'stock_opname_approve.baru',
                    'batch_expiry_fefo.buka', 'batch_expiry_fefo.baru',
                    'consignment_management.buka', 'consignment_management.baru', 'consignment_management.ubah',
                    'assembly_bom.buka', 'assembly_bom.baru', 'assembly_process.buka', 'assembly_process.baru', 'assembly_disassemble.buka', 'assembly_disassemble.baru',
                    'cash_in.buka', 'cash_in.baru', 'cash_out.buka', 'cash_out.baru', 'cash_transfer.buka', 'cash_transfer.baru',
                    'report_sales.buka', 'menu_dashboard_charts.buka', 'menu_view_sales.buka', 'menu_view_inventory.buka', 'menu_view_reports.buka',
                ],
            ],
            'KASIR' => [
                'name' => 'Kasir Toko (Cashier Standard)',
                'description' => 'Layar POS Kasir, Buka/Tutup Shift, Cash Drop. HPP & Laba Dirahasiakan.',
                'permissions' => [
                    'pos_terminal.buka', 'pos_terminal.baru',
                    'pos_shift_management.buka', 'pos_shift_management.baru',
                    'pos_return.buka', 'pos_return.baru',
                    'pos_credit_payment.buka',
                    'sales_receivable_payment.buka', 'sales_receivable_payment.baru',
                    'cash_in.buka', 'cash_in.baru', 'cash_out.buka', 'cash_out.baru', 'cash_transfer.buka', 'cash_transfer.baru',
                    'stock_opname.buka', 'stock_opname.baru',
                    'master_item.buka', 'master_customer.buka',
                    'view_selling_price.buka',
                    'menu_direct_pos.buka', // Otomatis langsung masuk layar kasir saat login
                    'menu_view_sales.buka',
                ],
            ],
            'GUDANG' => [
                'name' => 'Staf Logistik & Gudang',
                'description' => 'Penerimaan LPB Supplier, Mutasi Stok, Transfer Cabang, dan Stock Opname Buta.',
                'permissions' => [
                    'master_item.buka', 'master_supplier.buka', 'master_customer.buka', 'master_warehouse.buka', 'master_satuan_kategori.buka', 'kartu_stok.buka',
                    'purchase_receipt.buka', 'purchase_receipt.baru',
                    'stock_in.buka', 'stock_in.baru', 'stock_in.ubah',
                    'stock_out.buka', 'stock_out.baru', 'stock_out.ubah',
                    'stock_transfer.buka', 'stock_transfer.baru', 'stock_transfer.ubah',
                    'stock_opname.buka', 'stock_opname.baru', 'stock_opname.ubah',
                    'batch_expiry_fefo.buka', 'batch_expiry_fefo.baru', 'batch_expiry_fefo.ubah',
                    'consignment_management.buka', 'consignment_management.baru', 'consignment_management.ubah',
                    'assembly_bom.buka', 'assembly_bom.baru', 'assembly_process.buka', 'assembly_process.baru', 'assembly_disassemble.buka', 'assembly_disassemble.baru',
                    'menu_view_inventory.buka',
                ],
            ],
            'PURCHASING' => [
                'name' => 'Staf Pembelian / Purchasing',
                'description' => 'Kelola Master Supplier, Draft Purchase Order, dan Riwayat Harga Beli.',
                'permissions' => [
                    'master_item.buka', 'master_supplier.buka', 'master_supplier.baru', 'master_supplier.ubah',
                    'master_customer.buka', 'master_warehouse.buka', 'master_satuan_kategori.buka', 'kartu_stok.buka',
                    'purchase_order.buka', 'purchase_order.baru', 'purchase_order.ubah', 'purchase_order.hapus',
                    'purchase_receipt.buka', 'purchase_receipt.baru',
                    'purchase_invoice.buka', 'purchase_invoice.baru', 'purchase_invoice.ubah',
                    'purchase_payment.buka',
                    'purchase_return.buka', 'purchase_return.baru', 'purchase_return.ubah', 'purchase_return.hapus',
                    'purchase_price_history.buka', 'purchase_show_cost.buka',
                    'view_cost_price.buka', 'view_avg_cost.buka',
                    'menu_view_purchasing.buka', 'report_purchases.buka',
                ],
            ],
            'KEUANGAN' => [
                'name' => 'Staf Keuangan & Akuntansi',
                'description' => 'Kas Masuk, Kas Keluar, Buku Kasir, Pelunasan Hutang/Piutang, Jurnal, dan Laporan.',
                'permissions' => [
                    'master_item.buka', 'master_supplier.buka', 'master_customer.buka', 'master_warehouse.buka', 'master_satuan_kategori.buka', 'kartu_stok.buka',
                    'purchase_order.buka', 'purchase_receipt.buka',
                    'purchase_invoice.buka', 'purchase_invoice.baru', 'purchase_invoice.ubah',
                    'purchase_payment.buka', 'purchase_payment.baru',
                    'purchase_return.buka',
                    'sales_order.buka', 'sales_order.baru', 'sales_order.ubah',
                    'sales_invoice.buka', 'sales_invoice.baru', 'sales_invoice.ubah',
                    'sales_receivable_payment.buka', 'sales_receivable_payment.baru',
                    'pos_return.buka', 'pos_return.baru',
                    'cash_in.buka', 'cash_in.baru', 'cash_in.ubah',
                    'cash_out.buka', 'cash_out.baru', 'cash_out.ubah',
                    'cash_transfer.buka', 'cash_transfer.baru',
                    'accounting_coa.buka', 'accounting_coa.baru', 'accounting_coa.ubah',
                    'accounting_journals.buka', 'accounting_journals.baru',
                    'accounting_ledger.buka', 'accounting_settings_map.buka', 'accounting_settings_map.ubah',
                    'fixed_assets.buka', 'fixed_assets.baru', 'fixed_assets.ubah',
                    'view_cost_price.buka', 'view_avg_cost.buka',
                    'report_sales.buka', 'report_purchases.buka', 'report_profit_loss.buka', 'report_balance_sheet.buka', 'report_aging.buka', 'report_cash_flow.buka', 'report_stock_valuation.buka',
                    'menu_view_accounting.buka', 'menu_view_reports.buka',
                ],
            ],
            'SALES' => [
                'name' => 'Salesman / Counter Sales',
                'description' => 'Pesanan Penjualan (Sales Order), Kasir POS, dan Piutang Pelanggan.',
                'permissions' => [
                    'master_item.buka', 'master_customer.buka', 'view_selling_price.buka',
                    'pos_terminal.buka', 'pos_terminal.baru',
                    'pos_shift_management.buka', 'pos_shift_management.baru',
                    'pos_return.buka', 'pos_return.baru',
                    'pos_credit_payment.buka',
                    'sales_order.buka', 'sales_order.baru', 'sales_order.ubah',
                    'sales_invoice.buka', 'sales_invoice.baru',
                    'sales_receivable_payment.buka', 'sales_receivable_payment.baru',
                    'menu_view_sales.buka',
                ],
            ],
        ];
    }
}
