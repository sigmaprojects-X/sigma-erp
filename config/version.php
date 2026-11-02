<?php

return [
    'app_name' => 'SIGMA ERP & POS Enterprise',
    'version' => '1.0.3',
    'build_number' => '20261102',
    'release_date' => '2026-11-02',
    'channel' => 'stable',
    'min_php_version' => '8.3.0',
    'min_postgres_version' => '15.0',
    'latest_version' => '1.0.3',
    'update_available' => false,
    'changelog' => [
        'v1.0.3' => [
            'release_date' => '2026-11-02',
            'title' => 'Bundled Portable PHP 8.3 Runtime, Smart PostgreSQL Auto-Detection & Zero-Config Installer',
            'features' => [
                'Bundled Portable PHP 8.3 Runtime: Installer menyertakan runtime engine PHP 8.3 bawaan sehingga pengguna di komputer baru tidak perlu mendownload PHP atau mengonfigurasi php.ini secara manual',
                'Smart PostgreSQL Auto-Detection: Deteksi otomatis path binary psql pada PostgreSQL 15, 16, 17, 18 tanpa bergantung pada Windows System PATH',
                'Zero-Config Installation: Kunci enkripsi APP_KEY, relasi storage, schema database, dan seeder data awal dijalankan otomatis menggunakan PHP embedded mandiri',
                'Peningkatan Ketahanan Izin Berkas: Otomasi pencabutan atribut proteksi ReadOnly pada storage dan bootstrap/cache saat instalasi dan startup server',
            ],
            'fixes' => [
                'Pencegahan error "Gagal membuat APP_KEY Laravel" pada komputer baru tanpa PHP environment terkonfigurasi',
                'Dukungan kompilator modern Inno Setup 7 dengan eliminasi preprocessor directive warnings',
            ]
        ],
        'v1.0.2' => [
            'release_date' => '2026-10-30',
            'title' => 'Update Integritas Master Produk, Discontinue & Reset Demo Bersih',
            'features' => [
                'Sistem Dual-Mode Operasional: Dukungan Mode Development (live coding & error trace) vs Mode Production (caching penuh untuk kecepatan kasir toko)',
                '1-Klik Mode Switcher: Script launcher batch (MODE-DEVELOPMENT.bat, MODE-PRODUCTION.bat, GANTI-MODE-SISTEM.bat) dan tombol beralih mode di menu Pemeliharaan Sistem',
                'Proteksi Ganda Reset Demo: Tombol reset demo otomatis terkunci di Mode Production untuk melindungi data riil toko dari penghapusan tidak sengaja',
                'Badge Status Mode Sistem: Indikator visual real-time pada topbar navigasi Back-Office (PROD hijau / DEV kuning)',
                'Fitur Hapus Barang Salah Input: Master produk yang belum pernah bertransaksi dapat dihapus dengan aman beserta relasi child master tanpa merusak integritas data',
                'Aturan Validasi Discontinue: Barang hanya dapat di-discontinue apabila sisa stok fisik sudah kosong (0 unit)',
                'Blacklist Penjualan Kasir POS: Barang berstatus Discontinue otomatis diblokir dari pencarian barcode, barcode timbangan, dan checkout kasir',
                'Penyempurnaan Reset Demo: Mengosongkan seluruh item uji coba dan hanya menyisakan 5 item default master demo bersaldo stok awal bersih',
            ],
            'fixes' => [
                'Pencegahan UniqueConstraintViolationException pada pergantian atau penambahan multi-satuan kemasan produk (Two-Phase Unit Update & Duplicate Validation)',
                'Pencegahan error penghapusan unit kemasan yang memiliki riwayat transaksi operasional/akuntansi',
            ]
        ],
        'v1.0.1' => [
            'release_date' => '2026-10-30',
            'title' => 'Update Resmi SIGMA Design System & Auto-Accordion Sidebar',
            'features' => [
                'Implementasi resmi palet warna 3D SIGMA: Primary Navy (#0B1F4D) & Primary Blue (#125BCE)',
                'Auto-Accordion Sidebar: Menutup otomatis submenu yang tidak aktif saat modul lain dibuka',
                'Sentralisasi mode & tarif pajak kasir POS terkunci dari konfigurasi Back-Office ERP',
                'Launcher portable 1-klik (START-SIGMA-SERVER.bat) dengan deteksi IP Wi-Fi/LAN lokal',
                'Cloudflare Zero-Config Tunnel (ONLINE-TUNNEL-CABANG.bat) untuk akses kasir cabang luar toko'
            ],
            'fixes' => [
                'Penyelarasan validasi mode pajak INCLUSIVE / PPN_INCLUDE pada saat checkout POS kasir',
                'Pencegahan dobel dropdown terbuka pada navigasi 7 modul ERP',
                'Optimasi performa query laporan penjualan dan kartu hutang supplier'
            ]
        ]
    ]
];
