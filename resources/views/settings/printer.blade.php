@extends('layouts.app')

@section('title', 'Konfigurasi Printer Struk Thermal & Format Layout')
@section('tab_title', 'Printer & Struk Kasir')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="printerSettingsApp({
    paperSize: '{{ $company->receipt_paper_size ?? '58' }}',
    fontFamily: '{{ $company->receipt_font_family ?? 'monospace' }}',
    fontSize: '{{ $company->receipt_font_size ?? 'normal' }}',
    itemLayout: '{{ $company->receipt_item_layout ?? 'compact_double' }}',
    showLogo: {{ ($company->receipt_show_logo ?? true) ? 'true' : 'false' }},
    logoUrl: '{{ $company->logo_path ? asset($company->logo_path) : '' }}',
    storeName: '{{ addslashes($store->name ?? $company->name) }}',
    companyName: '{{ addslashes($company->name) }}',
    storeAddress: '{{ addslashes($store->address ?? 'Pusat Distribusi Retail & Grosir') }}',
    storePhone: '{{ addslashes($store->phone ?? '0812-3456-7890') }}',
    header1: '{{ addslashes($company->receipt_header_line1 ?? '') }}',
    header2: '{{ addslashes($company->receipt_header_line2 ?? '') }}',
    footer1: '{{ addslashes(str_replace(["\r\n", "\r", "\n"], "\\n", $company->receipt_footer_line1 ?? $company->receipt_footer ?? "Terima Kasih Atas Kunjungan Anda\nBarang yang dibeli tidak dapat ditukar/dikembalikan")) }}',
    footer2: '{{ addslashes($company->receipt_footer_line2 ?? '') }}',
    showCustomer: {{ ($company->receipt_show_customer ?? true) ? 'true' : 'false' }},
    showCashier: {{ ($company->receipt_show_cashier ?? true) ? 'true' : 'false' }},
    showBarcode: {{ ($company->receipt_show_barcode ?? true) ? 'true' : 'false' }},
    autoPrint: {{ ($company->receipt_auto_print ?? true) ? 'true' : 'false' }},
    autoCut: {{ ($company->receipt_auto_cut ?? true) ? 'true' : 'false' }},
    openDrawer: {{ ($company->receipt_open_drawer ?? true) ? 'true' : 'false' }},
    copies: {{ (int) ($company->receipt_print_copies ?? 1) }}
})">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('settings.general') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Printer Struk Kasir &amp; Layout Kertas</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🖨️ Konfigurasi Printer Struk Thermal, Auto-Cutter &amp; Layout Kertas</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.general') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏢 Profil &amp; Pajak</span>
            </a>
            <a href="{{ route('settings.stores.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏪 Cabang &amp; Gudang</span>
            </a>
            <a href="{{ route('settings.users.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>👥 Pengguna &amp; RBAC</span>
            </a>
            <a href="{{ route('settings.numbering.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🔢 Penomoran</span>
            </a>
            <a href="{{ route('settings.cashier') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>💼 Kasir</span>
            </a>
            <a href="{{ route('settings.maintenance.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🛡️ Pemeliharaan</span>
            </a>
            <a href="{{ route('settings.printer.test') }}" target="_blank"
               class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-sm flex items-center gap-1.5 transition-all cursor-pointer">
                <span>🖨️ Tes Cetak</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
                <span>⚡ BUKA KASIR [F12]</span>
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span>{{ $errors->first() }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- SUB-NAVIGATION TABS -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <a href="{{ route('settings.general') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>⚙️ Umum &amp; Wallpaper</span>
        </a>
        <a href="{{ route('settings.users.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>👥 Pengguna &amp; Hak Akses (RBAC)</span>
        </a>
        <a href="{{ route('settings.printer') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-black transition-all bg-blue-600 text-white shadow-sm flex items-center gap-1.5">
            <span>🖨️ Printer &amp; Struk Kasir</span>
        </a>
        <a href="{{ route('settings.numbering.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>🔢 Penomoran Otomatis</span>
        </a>
        <a href="{{ route('settings.cashier') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>💼 Operasional Kasir</span>
        </a>
    </div>

    <!-- MAIN TWO-COLUMN CONTAINER: FORM (LEFT) + LIVE THERMAL PREVIEW (RIGHT) -->
    <form action="{{ route('settings.printer.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ============================================================== -->
            <!-- KOLOM KIRI (KONTROL & FORM KONFIGURASI)                        -->
            <!-- ============================================================== -->
            <div class="lg:col-span-7 space-y-5">

                <!-- 1. UKURAN KERTAS & TIPOGRAFI STRUK -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📐</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900 dark:text-white">Ukuran Kertas &amp; Format Tipografi</h2>
                                <p class="text-xs text-slate-500">Pilih lebar kertas thermal dan jenis huruf yang optimal untuk printer kasir Anda</p>
                            </div>
                        </div>
                    </div>

                    <!-- PILIH LEBAR KERTAS (58mm vs 80mm) -->
                    <div>
                        <label class="block text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider mb-2">
                            Lebar Kertas Thermal POS:
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- 58mm Card -->
                            <label class="relative flex flex-col p-3.5 border-2 rounded-xl cursor-pointer transition-all"
                                   :class="paperSize === '58' ? 'border-blue-600 bg-blue-50/60 dark:bg-blue-950/40 dark:border-blue-500' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/40'">
                                <input type="radio" name="receipt_paper_size" value="58" x-model="paperSize" class="sr-only">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-black text-slate-900 dark:text-white">58 mm (Mini / Portable)</span>
                                    <span x-show="paperSize === '58'" class="w-4 h-4 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center">✓</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Standar printer thermal Bluetooth portable &amp; USB kasir meja (lebar cetak ~48mm, 32 kolom).
                                </p>
                            </label>

                            <!-- 80mm Card -->
                            <label class="relative flex flex-col p-3.5 border-2 rounded-xl cursor-pointer transition-all"
                                   :class="paperSize === '80' ? 'border-blue-600 bg-blue-50/60 dark:bg-blue-950/40 dark:border-blue-500' : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/40'">
                                <input type="radio" name="receipt_paper_size" value="80" x-model="paperSize" class="sr-only">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-black text-slate-900 dark:text-white">80 mm (Standar POS Desktop)</span>
                                    <span x-show="paperSize === '80'" class="w-4 h-4 rounded-full bg-blue-600 text-white text-[10px] font-bold flex items-center justify-center">✓</span>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                                    Epson TM-T82, Bixolon, Star (lebar cetak ~72mm, 42–48 kolom). Layout lebih luas dan detail.
                                </p>
                            </label>
                        </div>
                    </div>

                    <!-- FONT FAMILY & FONT SIZE -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Gaya Font Thermal:</label>
                            <select name="receipt_font_family" x-model="fontFamily"
                                    class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="monospace">Courier Mono (Thermal Klasik)</option>
                                <option value="courier">Courier New (Mesin Kasir Tajam)</option>
                                <option value="sans-serif">Sans-Serif Clean (Rapi Modern)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Skala Ukuran Huruf:</label>
                            <select name="receipt_font_size" x-model="fontSize"
                                    class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="compact">Kompak (Hemat Kertas Struk)</option>
                                <option value="normal">Normal (Standar Struk Kasir)</option>
                                <option value="large">Besar (Sangat Jelas Terbaca)</option>
                            </select>
                        </div>
                    </div>

                    <!-- LAYOUT BARANG & JUMLAH SALINAN -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1 border-t border-slate-100 dark:border-slate-700">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Format Rincian Barang:</label>
                            <select name="receipt_item_layout" x-model="itemLayout"
                                    class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="compact_double">Dua Baris (Nama, lalu Qty x Harga &amp; Total)</option>
                                <option value="single_line">Satu Baris (Hanya untuk 80mm Cepat)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jumlah Lembar Cetak per Struk:</label>
                            <select name="receipt_print_copies" x-model="copies"
                                    class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                <option value="1">1 Lembar (Untuk Konsumen Saja)</option>
                                <option value="2">2 Lembar (Konsumen + Arsip Toko/Kasir)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. LOGO & HEADER IDENTITAS STRUK -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🏢</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900 dark:text-white">Logo &amp; Header Struk Toko</h2>
                                <p class="text-xs text-slate-500">Unggah logo hitam putih dan atur slogan/media sosial di bagian atas struk</p>
                            </div>
                        </div>
                    </div>

                    <!-- UPLOAD LOGO STRUK -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Logo Struk (Monokrom / Hitam Putih):</label>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="receipt_show_logo" value="1" x-model="showLogo"
                                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Tampilkan Logo di Struk</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-4 p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl border border-slate-200 dark:border-slate-700">
                            <template x-if="logoUrl">
                                <div class="w-16 h-16 bg-white rounded-lg border border-slate-300 p-1 flex items-center justify-center shrink-0 shadow-xs">
                                    <img :src="logoUrl" alt="Logo Preview" class="max-h-full max-w-full object-contain filter grayscale contrast-200">
                                </div>
                            </template>
                            <template x-if="!logoUrl">
                                <div class="w-16 h-16 bg-slate-200 dark:bg-slate-700 rounded-lg border border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center shrink-0 text-slate-400 text-xs">
                                    No Logo
                                </div>
                            </template>

                            <div class="flex-1 space-y-1.5">
                                <input type="file" name="logo" accept="image/*" @change="previewUploadedLogo($event)"
                                       class="block w-full text-xs text-slate-500 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                <p class="text-[10.5px] text-slate-400">Format PNG/JPG transparan/putih disarankan. Maksimal 2MB.</p>
                                @if($company->logo_path)
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-rose-600 font-bold cursor-pointer pt-0.5">
                                        <input type="checkbox" name="remove_logo" value="1" @change="logoUrl = ''">
                                        <span>Hapus Logo Saat Ini</span>
                                    </label>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- SLOGAN / HEADER EXTRA -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Header Baris 1 (Slogan / Info Toko):</label>
                            <input type="text" name="receipt_header_line1" x-model="header1" maxlength="150"
                                   placeholder="Contoh: Pusat Grosir & Eceran Terlengkap"
                                   class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Header Baris 2 (Media Sosial / Website):</label>
                            <input type="text" name="receipt_header_line2" x-model="header2" maxlength="150"
                                   placeholder="Contoh: IG: @sigmart.official | www.sigmart.id"
                                   class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- 3. FOOTER & KEBIJAKAN RETUR TOKO -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">💬</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900 dark:text-white">Catatan Footer &amp; Kebijakan Toko</h2>
                                <p class="text-xs text-slate-500">Pesan terima kasih, syarat tukar barang, atau layanan kritik &amp; saran konsumen</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pesan Utama Footer / Kebijakan Retur:
                        </label>
                        <textarea name="receipt_footer_line1" x-model="footer1" rows="2" maxlength="500"
                                  class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                                  placeholder="Terima Kasih Atas Kunjungan Anda&#10;Barang yang dibeli tidak dapat ditukar/dikembalikan"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pesan Tambahan Baris 2 (Kritik &amp; Saran / Undian):
                        </label>
                        <input type="text" name="receipt_footer_line2" x-model="footer2" maxlength="250"
                               placeholder="Contoh: Kritik & Saran Hubungi WA: 0812-3456-7890"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>

                <!-- 4. PERANGKAT KERAS (HARDWARE & AUTO-CUT) -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-3.5">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">⚙️</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900 dark:text-white">Opsi Perangkat Keras &amp; Kasir Otomatis</h2>
                                <p class="text-xs text-slate-500">Perilaku printer saat transaksi selesai di layar kasir</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 pt-1">
                        <!-- Auto print -->
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="receipt_auto_print" value="1" x-model="autoPrint"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Auto-Print Dialog Begitu Pembayaran Sukses</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Membuka dialog cetak otomatis seketika kasir menyelesaikan transaksi tanpa perlu klik manual.</p>
                            </div>
                        </label>

                        <!-- Auto cut -->
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="receipt_auto_cut" value="1" x-model="autoCut"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Auto-Cutter Paper Feed Buffer</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Menambahkan margin kosong di bawah struk agar pisau pemotong (*cutter*) printer memotong pas di bawah teks.</p>
                            </div>
                        </label>

                        <!-- Drawer Kick -->
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="receipt_open_drawer" value="1" x-model="openDrawer"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Buka Laci Kasir Otomatis (Drawer Kick) Saat Bayar Tunai</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Mengirim sinyal pulsa RJ11 ke laci uang saat kasir menerima pembayaran tunai atau split cash.</p>
                            </div>
                        </label>

                        <!-- Barcode Transaksi -->
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="receipt_show_barcode" value="1" x-model="showBarcode"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Cetak Barcode No. Faktur Transaksi di Bawah Struk</span>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Mempermudah kasir memindai struk untuk proses retur, klaim garansi, atau reprint di kemudian hari.</p>
                            </div>
                        </label>

                        <!-- Tampilkan Kasir & Pelanggan -->
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100 dark:border-slate-700">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="receipt_show_cashier" value="1" x-model="showCashier"
                                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Tampilkan Nama Kasir</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="receipt_show_customer" value="1" x-model="showCustomer"
                                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Tampilkan Pelanggan</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- SAVE BUTTON -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xs rounded-xl shadow-lg shadow-blue-900/20 flex items-center gap-2 transition-all active:scale-95 cursor-pointer">
                        <span>💾 SIMPAN KONFIGURASI PRINTER STRUK</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- KOLOM KANAN (SIMULASI LIVE THERMAL STRUK PREVIEW REAL-TIME)    -->
            <!-- ============================================================== -->
            <div class="lg:col-span-5 sticky top-4">
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-2xl flex flex-col space-y-4">
                    
                    <!-- PREVIEW TOP BAR -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 text-white">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🧾</span>
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-slate-200">Simulasi Struk Real-Time</h3>
                                <p class="text-[10px] text-slate-400">Preview live sesuai kertas &amp; font yang dipilih</p>
                            </div>
                        </div>

                        <!-- Paper width badge -->
                        <span class="px-2.5 py-1 rounded-md text-[11px] font-black tracking-wider transition-all"
                              :class="paperSize === '80' ? 'bg-indigo-900/80 text-indigo-300 border border-indigo-700' : 'bg-blue-900/80 text-blue-300 border border-blue-700'">
                            <span x-text="paperSize + ' mm'"></span>
                        </span>
                    </div>

                    <!-- THERMAL PAPER CONTAINER -->
                    <div class="flex justify-center p-3 bg-slate-950/60 rounded-xl overflow-x-auto min-h-[460px]">
                        
                        <!-- SIMULATED PHYSICAL THERMAL PAPER SHEET -->
                        <div class="bg-white text-black shadow-2xl transition-all duration-200 rounded-xs select-none"
                             :style="{
                                 width: paperSize === '80' ? '300px' : '230px',
                                 fontFamily: getComputedFontFamily(),
                                 fontSize: getComputedFontSize(),
                                 lineHeight: '1.25',
                                 padding: paperSize === '80' ? '12px 14px' : '8px 10px',
                                 color: '#000000'
                             }">

                            @for($previewCopy = 1; $previewCopy <= 1; $previewCopy++)
                                <!-- COPIES BADGE IF MULTI-COPY -->
                                <template x-if="copies > 1">
                                    <div class="text-center mb-1">
                                        <span class="inline-block border border-black px-1.5 py-0.5 text-[8px] font-bold uppercase">
                                            *** LEMBAR KONSUMEN ***
                                        </span>
                                    </div>
                                </template>

                                <!-- STORE LOGO (IF ACTIVE) -->
                                <template x-if="showLogo && logoUrl">
                                    <div class="text-center mb-1.5">
                                        <img :src="logoUrl" alt="Logo" class="max-h-8 max-w-[70%] mx-auto filter grayscale contrast-200">
                                    </div>
                                </template>

                                <!-- STORE NAME & ADDRESS -->
                                <div class="text-center leading-tight">
                                    <div class="font-bold uppercase tracking-tight" :style="{ fontSize: getComputedTitleSize() }" x-text="companyName"></div>
                                    <div class="font-bold text-[10.5px]" x-text="storeName"></div>
                                    <div class="text-[9px] text-slate-800" x-text="storeAddress"></div>
                                    <div class="text-[9px] text-slate-800">Telp: <span x-text="storePhone"></span></div>
                                    
                                    <template x-if="header1">
                                        <div class="text-[8.5px] italic text-slate-700 mt-0.5" x-text="header1"></div>
                                    </template>
                                    <template x-if="header2">
                                        <div class="text-[8.5px] italic text-slate-700" x-text="header2"></div>
                                    </template>
                                </div>

                                <div class="border-b border-dashed border-black my-1.5"></div>

                                <!-- TRANSACTION METADATA -->
                                <div class="text-[10px] space-y-0.5">
                                    <div class="flex justify-between">
                                        <span>No: POS-{{ date('Ymd') }}-0042</span>
                                        <span class="text-right">{{ date('d/m/y H:i') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span x-show="showCashier">Kasir: {{ auth()->user()?->name ?? 'Kasir Bertugas' }}</span>
                                        <span class="ml-auto text-right">Tier: Umum</span>
                                    </div>
                                    <template x-if="showCustomer">
                                        <div>Pelanggan: Bpk. Hendrawan Santoso</div>
                                    </template>
                                </div>

                                <div class="border-b border-dashed border-black my-1.5"></div>

                                <!-- ITEMS LIST -->
                                <div class="space-y-1 text-[10px]">
                                    <!-- Item 1 -->
                                    <div>
                                        <div class="font-bold">Kopi Kapal Api Spesial 24g</div>
                                        <div class="flex justify-between pl-1">
                                            <span>2 PCS x 3.500</span>
                                            <span class="font-bold">7.000</span>
                                        </div>
                                    </div>

                                    <!-- Item 2 -->
                                    <div>
                                        <div class="font-bold">Gula Pasir Gulaku 1kg</div>
                                        <div class="flex justify-between pl-1">
                                            <span>1 BKS x 17.500</span>
                                            <span class="font-bold">17.500</span>
                                        </div>
                                    </div>

                                    <!-- Item 3 -->
                                    <div>
                                        <div class="font-bold">Minyak Goreng Bimoli 2L</div>
                                        <div class="flex justify-between pl-1">
                                            <span>1 PCH x 36.500</span>
                                            <span class="font-bold">36.500</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-b border-dashed border-black my-1.5"></div>

                                <!-- TOTALS & PAYMENT -->
                                <div class="space-y-0.5 text-[10px]">
                                    <div class="flex justify-between">
                                        <span>Subtotal:</span>
                                        <span class="font-bold">61.000</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Diskon Promo:</span>
                                        <span>-1.000</span>
                                    </div>
                                    <div class="flex justify-between font-black pt-1 border-t border-black text-[11.5px]" :style="{ fontSize: getComputedTitleSize() }">
                                        <span>TOTAL:</span>
                                        <span>Rp 60.000</span>
                                    </div>
                                    <div class="border-b border-dashed border-black my-1"></div>
                                    <div class="flex justify-between">
                                        <span>Bayar (TUNAI):</span>
                                        <span class="font-bold">Rp 100.000</span>
                                    </div>
                                    <div class="flex justify-between font-bold">
                                        <span>Kembali:</span>
                                        <span>Rp 40.000</span>
                                    </div>
                                </div>

                                <div class="border-b-2 border-double border-black my-2"></div>

                                <!-- FOOTER & POLICIES -->
                                <div class="text-center text-[8.5px] leading-tight space-y-1">
                                    <div class="whitespace-pre-line" x-text="footer1"></div>
                                    <template x-if="footer2">
                                        <div class="italic text-slate-700" x-text="footer2"></div>
                                    </template>
                                    <div class="font-bold tracking-wider pt-0.5">SIGMA POS PRO</div>
                                </div>

                                <!-- BARCODE OF INVOICE NUMBER -->
                                <template x-if="showBarcode">
                                    <div class="text-center mt-2">
                                        <div class="font-mono text-[18px] tracking-widest leading-none">*POS-{{ date('Ymd') }}-0042*</div>
                                        <div class="text-[7.5px] font-mono tracking-wider mt-0.5">POS-{{ date('Ymd') }}-0042</div>
                                    </div>
                                </template>

                                <!-- CUT FEED BUFFER -->
                                <template x-if="autoCut">
                                    <div class="text-center text-[8px] text-slate-400 mt-2 border-t border-dotted border-slate-300 pt-1">
                                        ✂️ [Area Garis Potong Auto-Cutter]
                                    </div>
                                </template>
                            @endfor

                        </div>
                    </div>

                    <!-- PREVIEW ACTION BUTTONS -->
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-800">
                        <a href="{{ route('settings.printer.test') }}" target="_blank"
                           class="flex-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition-all text-center cursor-pointer shadow-sm">
                            <span>🖨️ Tes Cetak Langsung</span>
                        </a>
                        <button type="button" @click="paperSize = (paperSize === '58' ? '80' : '58')"
                                class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition-all cursor-pointer">
                            Switch <span x-text="paperSize === '58' ? '80mm' : '58mm'"></span>
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </form>

</div>

<!-- ALPINE LOGIC SCRIPT -->
<script>
function printerSettingsApp(config) {
    return {
        paperSize: config.paperSize || '58',
        fontFamily: config.fontFamily || 'monospace',
        fontSize: config.fontSize || 'normal',
        itemLayout: config.itemLayout || 'compact_double',
        showLogo: config.showLogo,
        logoUrl: config.logoUrl,
        storeName: config.storeName,
        companyName: config.companyName,
        storeAddress: config.storeAddress,
        storePhone: config.storePhone,
        header1: config.header1,
        header2: config.header2,
        footer1: config.footer1.replace(/\\n/g, '\n'),
        footer2: config.footer2,
        showCustomer: config.showCustomer,
        showCashier: config.showCashier,
        showBarcode: config.showBarcode,
        autoPrint: config.autoPrint,
        autoCut: config.autoCut,
        openDrawer: config.openDrawer,
        copies: config.copies || 1,

        getComputedFontFamily() {
            switch(this.fontFamily) {
                case 'courier': return "'Courier New', Courier, monospace";
                case 'sans-serif': return "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
                default: return "'Courier New', Courier, monospace";
            }
        },

        getComputedFontSize() {
            if (this.paperSize === '80') {
                switch(this.fontSize) {
                    case 'compact': return '11px';
                    case 'large': return '13px';
                    default: return '12px';
                }
            } else {
                switch(this.fontSize) {
                    case 'compact': return '9.5px';
                    case 'large': return '11px';
                    default: return '10.5px';
                }
            }
        },

        getComputedTitleSize() {
            if (this.paperSize === '80') {
                switch(this.fontSize) {
                    case 'compact': return '12.5px';
                    case 'large': return '15px';
                    default: return '14px';
                }
            } else {
                switch(this.fontSize) {
                    case 'compact': return '11px';
                    case 'large': return '13px';
                    default: return '12px';
                }
            }
        },

        previewUploadedLogo(e) {
            const file = e.target.files[0];
            if (file) {
                this.logoUrl = URL.createObjectURL(file);
                this.showLogo = true;
            }
        }
    };
}
</script>
@endsection
