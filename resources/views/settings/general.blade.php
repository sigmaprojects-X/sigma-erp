@extends('layouts.app')

@section('title', 'Profil Perusahaan, Pajak & Tampilan')
@section('tab_title', 'Pengaturan Umum & Pajak')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="generalSettingsApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Profil Perusahaan &amp; Faktur Pajak</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>⚙️ Pengaturan Umum &amp; Wallpaper Desktop</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.stores.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏪 Cabang &amp; Gudang</span>
            </a>
            <a href="{{ route('settings.users.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>👥 Pengguna &amp; Hak Akses</span>
            </a>
            <a href="{{ route('settings.printer') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️ Printer &amp; Struk</span>
            </a>
            <a href="{{ route('settings.numbering.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🔢 Penomoran</span>
            </a>
            <a href="{{ route('settings.cashier') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>💼 Kasir</span>
            </a>
            <a href="{{ route('settings.maintenance.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🛡️ Pemeliharaan &amp; Backup</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
                <span>⚡ BUKA KASIR [F12]</span>
            </a>
        </div>
    </div>

    <!-- MAIN FORM -->
    <form action="{{ route('settings.general.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- ============================================================== -->
            <!-- KOLOM KIRI (7/12): PROFIL BISNIS & METADATA FAKTUR PAJAK       -->
            <!-- ============================================================== -->
            <div class="lg:col-span-7 space-y-5">

                <!-- CARD 1: IDENTITAS RESMI PERUSAHAAN & LOGO -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🏢</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900">Identitas Perusahaan &amp; Toko</h2>
                                <p class="text-xs text-slate-500">Informasi hukum, profil bisnis dan logo resmi sistem SIGMA ERP</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-[10.5px] font-mono font-bold rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                            ID: {{ $company->code }}
                        </span>
                    </div>

                    <!-- LOGO UPLOAD & PREVIEW SECTION -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-700">Logo Resmi Perusahaan (Kop Struk &amp; Dokumen):</label>
                            @if($company->logo_path)
                                <button type="button"
                                        @click="confirmResetLogo()"
                                        class="text-[11px] font-bold text-rose-600 hover:text-rose-800 transition-colors cursor-pointer flex items-center gap-1">
                                    <span>🗑️</span>
                                    <span>Hapus Logo</span>
                                </button>
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-4">
                            <!-- Logo Preview Thumbnail -->
                            <div class="w-24 h-24 rounded-lg border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden shrink-0 shadow-2xs">
                                <template x-if="logoPreviewUrl">
                                    <img :src="logoPreviewUrl" alt="Logo Preview" class="w-full h-full object-contain p-1">
                                </template>
                                <template x-if="!logoPreviewUrl">
                                    @if($company->logo_url)
                                        <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="w-full h-full object-contain p-1">
                                    @else
                                        <div class="text-center p-2">
                                            <span class="text-2xl block text-slate-300">🏢</span>
                                            <span class="text-[9px] text-slate-400 font-bold">Tanpa Logo</span>
                                        </div>
                                    @endif
                                </template>
                            </div>

                            <!-- Logo Input Controls -->
                            <div class="flex-1 space-y-2">
                                <input type="file"
                                       id="logoInput"
                                       name="logo"
                                       accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                       @change="handleLogoChange($event)"
                                       class="hidden">

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="document.getElementById('logoInput').click()"
                                            class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-lg shadow-2xs cursor-pointer flex items-center gap-1.5 transition-colors">
                                        <span>📁</span>
                                        <span>Pilih File Logo...</span>
                                    </button>
                                    <span class="text-[11px] text-slate-500 font-mono truncate" x-text="logoFileName || 'Maks. 2MB (PNG/JPG/SVG)'"></span>
                                </div>
                                <p class="text-[10.5px] text-slate-500 leading-tight">
                                    Logo ini akan dicetak pada kop struk thermal POS, nota pesanan penjualan, faktur tagihan, serta dokumen surat jalan.
                                </p>
                                @error('logo')
                                    <p class="text-rose-600 font-semibold text-[11px]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- FORM FIELDS IDENTITAS -->
                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Legal Perusahaan / Toko: <span class="text-rose-500">*</span></label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $company->name) }}"
                                   required
                                   class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            @error('name')
                                <p class="text-rose-600 font-semibold mt-1 text-[11px]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">No. Telepon Kantor:</label>
                                <input type="text"
                                       name="phone"
                                       value="{{ old('phone', $company->phone) }}"
                                       placeholder="021-xxxxxx / 0812xxxx"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">WhatsApp Bisnis / CS:</label>
                                <input type="text"
                                       name="whatsapp"
                                       value="{{ old('whatsapp', $company->whatsapp) }}"
                                       placeholder="628123456789"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Email Resmi Perusahaan:</label>
                                <input type="email"
                                       name="email"
                                       value="{{ old('email', $company->email) }}"
                                       placeholder="admin@endogan.com"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Situs Web Resmi (Website):</label>
                                <input type="text"
                                       name="website"
                                       value="{{ old('website', $company->website) }}"
                                       placeholder="https://www.endogan.com"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alamat Kantor Pusat / Toko Utama:</label>
                            <textarea name="address"
                                      rows="2"
                                      placeholder="Alamat lengkap jalan, nomor, RT/RW, kecamatan, kota..."
                                      class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">{{ old('address', $company->address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: METADATA FAKTUR PAJAK & KEPATUHAN PERPAJAKAN -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🧾</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900">Metadata Faktur Pajak &amp; PPN (Tax Compliance)</h2>
                                <p class="text-xs text-slate-500">Konfigurasi Pengusaha Kena Pajak (PKP), NPWP, dan tarif PPN standar</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-0.5 text-[11px] font-bold rounded-full {{ old('is_tax_registered', $company->is_tax_registered) ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-700 border border-slate-300' }}">
                            {{ old('is_tax_registered', $company->is_tax_registered) ? 'PKP AKTIF' : 'NON-PKP' }}
                        </span>
                    </div>

                    <!-- TOGGLE STATUS PKP -->
                    <div class="bg-blue-50/70 border border-blue-200/80 rounded-xl p-3.5 flex items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-black text-blue-950 block">Status Pengusaha Kena Pajak (PKP)</span>
                            <span class="text-[11px] text-blue-800">
                                Aktifkan jika perusahaan Anda wajib menerbitkan Faktur Pajak dan memungut PPN (Pajak Pertambahan Nilai).
                            </span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox"
                                   name="is_tax_registered"
                                   value="1"
                                   x-model="isTaxRegistered"
                                   class="sr-only peer"
                                   {{ old('is_tax_registered', $company->is_tax_registered) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- TAX FIELDS -->
                    <div class="space-y-3 text-xs" x-show="isTaxRegistered" x-transition>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nomor Pokok Wajib Pajak (NPWP 16 Digit):</label>
                                <input type="text"
                                       name="tax_id"
                                       value="{{ old('tax_id', $company->tax_id) }}"
                                       placeholder="01.234.567.8-901.000 / 16 digit NIK"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Nama Terdaftar Wajib Pajak (PKP):</label>
                                <input type="text"
                                       name="tax_name"
                                       value="{{ old('tax_name', $company->tax_name) }}"
                                       placeholder="Contoh: PT SIGMA SEJAHTERA BERSAMA"
                                       class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Alamat Terdaftar Faktur Pajak:</label>
                            <textarea name="tax_address"
                                      rows="2"
                                      placeholder="Alamat legal sesuai SKPKP (Surat Pengukuhan Pengusaha Kena Pajak)..."
                                      class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">{{ old('tax_address', $company->tax_address) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Tarif Standar PPN (%):</label>
                                <div class="relative">
                                    <input type="number"
                                           name="default_tax_rate"
                                           step="0.01"
                                           min="0"
                                           max="100"
                                           value="{{ old('default_tax_rate', $company->default_tax_rate ?? 11.00) }}"
                                           class="w-full pl-3 pr-8 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                                    <span class="absolute right-3 top-2 text-slate-400 font-bold">%</span>
                                </div>
                                <span class="text-[10px] text-slate-400">Regulasi Indonesia saat ini: 11% (atau 12% sesuai PMK).</span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Mode Perhitungan Pajak:</label>
                                <select name="tax_mode"
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                                    <option value="INCLUSIVE" {{ old('tax_mode', $company->tax_mode) === 'INCLUSIVE' ? 'selected' : '' }}>Termasuk Pajak (Tax Inclusive / Gross)</option>
                                    <option value="EXCLUSIVE" {{ old('tax_mode', $company->tax_mode) === 'EXCLUSIVE' ? 'selected' : '' }}>Belum Termasuk Pajak (Tax Exclusive / Net)</option>
                                    <option value="NON_TAX" {{ old('tax_mode', $company->tax_mode) === 'NON_TAX' ? 'selected' : '' }}>Bebas Pajak (Non-Taxable)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="flex items-center justify-between pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-lg shadow-md flex items-center gap-2 transition-transform active:scale-95 cursor-pointer">
                        <span>💾</span>
                        <span>SIMPAN SEMUA PENGATURAN</span>
                    </button>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- KOLOM KANAN (5/12): WALLPAPER DESKTOP & PEMBULATAN TRANSAKSI   -->
            <!-- ============================================================== -->
            <div class="lg:col-span-5 space-y-5">

                <!-- CARD 3: WALLPAPER DESKTOP -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🖼️</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900">Wallpaper Background Desktop</h2>
                                <p class="text-xs text-slate-500">Gambar latar belakang halaman menu utama</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-[10.5px] font-bold rounded-full {{ $company->wallpaper_path ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-blue-100 text-blue-800 border border-blue-300' }}">
                            {{ $company->wallpaper_path ? 'Kustom' : 'Default' }}
                        </span>
                    </div>

                    <!-- PREVIEW DISPLAY AREA -->
                    <div class="space-y-3">
                        <div class="relative w-full h-44 rounded-xl overflow-hidden border-2 border-dashed border-slate-300 bg-slate-900 flex items-center justify-center shadow-inner group">
                            <img :src="wallpaperPreviewUrl || '{{ $company->wallpaper_url }}'"
                                 alt="Wallpaper Preview"
                                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">

                            <div class="absolute bottom-2 left-2 right-2 bg-slate-950/80 backdrop-blur-xs text-white p-2 rounded-lg border border-slate-700/60 flex items-center justify-between text-[11px]">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-emerald-400 font-bold">● Live Wallpaper</span>
                                    <span class="text-slate-400 font-mono text-[10px] truncate max-w-[120px]" x-text="wallpaperFileName || '{{ $company->wallpaper_path ? basename($company->wallpaper_path) : 'banner.jpg' }}'"></span>
                                </div>
                                <span class="text-[9.5px] bg-slate-800 px-1.5 py-0.5 rounded text-slate-300 font-mono">1920x1080</span>
                            </div>
                        </div>

                        <!-- UPLOAD CONTROLS -->
                        <div>
                            <input type="file"
                                   id="wallpaperInput"
                                   name="wallpaper"
                                   accept="image/jpeg,image/png,image/jpg,image/webp"
                                   @change="handleWallpaperChange($event)"
                                   class="hidden">

                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="document.getElementById('wallpaperInput').click()"
                                        class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-sm flex items-center justify-center gap-1.5 cursor-pointer transition-colors active:scale-95">
                                    <span>📁</span>
                                    <span>Pilih Gambar dari Komputer...</span>
                                </button>
                                <span class="text-xs text-slate-500 font-medium truncate" x-text="wallpaperFileName ? 'Dipilih: ' + wallpaperFileName : 'Maks. 5 MB'"></span>
                            </div>
                            @error('wallpaper')
                                <p class="text-xs font-semibold text-rose-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($company->wallpaper_path)
                            <button type="button"
                                    @click="confirmResetWallpaper()"
                                    class="w-full py-1.5 px-3 bg-white hover:bg-rose-50 border border-slate-300 hover:border-rose-300 text-slate-700 hover:text-rose-700 font-bold text-xs rounded-lg shadow-2xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                                <span>⏪</span>
                                <span>Kembalikan ke Wallpaper Asli Endogan</span>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- CARD 4: PEMBULATAN TRANSAKSI (ROUNDING ENGINE) -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🪙</span>
                            <div>
                                <h2 class="text-sm font-black text-slate-900">Pembulatan Transaksi Akhir (POS)</h2>
                                <p class="text-xs text-slate-500">Otomasi pembulatan kembalian kasir</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="rounding_enabled" value="1" class="sr-only peer" {{ old('rounding_enabled', $company->rounding_enabled) ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                        </label>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kelipatan Pembulatan:</label>
                            <select name="rounding_step" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                                <option value="50" {{ old('rounding_step', $company->rounding_step) == 50 ? 'selected' : '' }}>Rp 50</option>
                                <option value="100" {{ old('rounding_step', $company->rounding_step) == 100 ? 'selected' : '' }}>Rp 100 (Standar Retail)</option>
                                <option value="500" {{ old('rounding_step', $company->rounding_step) == 500 ? 'selected' : '' }}>Rp 500</option>
                                <option value="1000" {{ old('rounding_step', $company->rounding_step) == 1000 ? 'selected' : '' }}>Rp 1.000</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Arah Pembulatan:</label>
                            <select name="rounding_mode" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                                <option value="ROUND_NEAREST" {{ old('rounding_mode', $company->rounding_mode) === 'ROUND_NEAREST' ? 'selected' : '' }}>Ke Kelipatan Terdekat (Standard)</option>
                                <option value="ROUND_UP" {{ old('rounding_mode', $company->rounding_mode) === 'ROUND_UP' ? 'selected' : '' }}>Selalu Bulatkan ke Atas (Ceil)</option>
                                <option value="ROUND_DOWN" {{ old('rounding_mode', $company->rounding_mode) === 'ROUND_DOWN' ? 'selected' : '' }}>Selalu Bulatkan ke Bawah (Floor)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Berlaku Untuk:</label>
                            <select name="rounding_applies_to" class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                                <option value="CASH_ONLY" {{ old('rounding_applies_to', $company->rounding_applies_to) === 'CASH_ONLY' ? 'selected' : '' }}>Hanya Tunai &amp; Split Tunai</option>
                                <option value="ALL" {{ old('rounding_applies_to', $company->rounding_applies_to) === 'ALL' ? 'selected' : '' }}>Semua Metode Pembayaran</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- CARD 5: FOOTER STRUK KASIR -->
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                        <span class="text-xl">🧾</span>
                        <div>
                            <h2 class="text-sm font-black text-slate-900">Catatan Footer Struk Kasir</h2>
                            <p class="text-xs text-slate-500">Teks penutup di bagian bawah struk</p>
                        </div>
                    </div>
                    <div>
                        <textarea name="receipt_footer"
                                  rows="3"
                                  placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar. Terima kasih atas kunjungan Anda!"
                                  class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">{{ old('receipt_footer', $company->receipt_footer) }}</textarea>
                        <span class="text-[10px] text-slate-400 mt-1 block">Untuk pengaturan font, ukuran kertas 58mm/80mm, dan auto-cutter, buka tab <a href="{{ route('settings.printer') }}" class="text-blue-600 font-bold hover:underline">Printer &amp; Struk Kasir</a>.</span>
                    </div>
                </div>

            </div>

        </div>
    </form>

    <!-- HIDDEN FORMS FOR RESETS -->
    <form id="resetWallpaperForm" action="{{ route('settings.wallpaper.reset') }}" method="POST" class="hidden">
        @csrf
    </form>
    <form id="resetLogoForm" action="{{ route('settings.logo.reset') }}" method="POST" class="hidden">
        @csrf
    </form>

</div>

@push('scripts')
<script>
    function generalSettingsApp() {
        return {
            wallpaperPreviewUrl: null,
            wallpaperFileName: '',
            logoPreviewUrl: null,
            logoFileName: '',
            isTaxRegistered: {{ old('is_tax_registered', $company->is_tax_registered) ? 'true' : 'false' }},

            handleWallpaperChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.wallpaperFileName = file.name;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.wallpaperPreviewUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            handleLogoChange(event) {
                const file = event.target.files[0];
                if (file) {
                    this.logoFileName = file.name;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.logoPreviewUrl = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            },

            confirmResetWallpaper() {
                if (confirm('Kembalikan wallpaper desktop ke tampilan bawaan Endogan?')) {
                    document.getElementById('resetWallpaperForm').submit();
                }
            },

            confirmResetLogo() {
                if (confirm('Apakah Anda yakin ingin menghapus logo perusahaan?')) {
                    document.getElementById('resetLogoForm').submit();
                }
            }
        }
    }
</script>
@endpush
@endsection
