@extends('layouts.app')

@section('title', 'Backup & Pemeliharaan Database')
@section('tab_title', 'Pemeliharaan & Backup')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="maintenanceApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('settings.general') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Pemeliharaan &amp; Cadangan Data</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🛡️ Backup, Restore &amp; Pemeliharaan Database</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Cadangkan data transaksi, unduh file arsip SQL, dan optimalkan performa perencana query database.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
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

    @if(session('error'))
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- SUB-NAVIGATION TABS (LENGKAP & KONSISTEN) -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
        <a href="{{ route('settings.general') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>⚙️ Profil &amp; Pajak</span>
        </a>
        <a href="{{ route('settings.stores.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>🏪 Cabang &amp; Toko</span>
        </a>
        <a href="{{ route('settings.users.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>👥 Pengguna &amp; RBAC</span>
        </a>
        <a href="{{ route('settings.printer') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>🖨️ Printer &amp; Struk</span>
        </a>
        <a href="{{ route('settings.numbering.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>🔢 Penomoran Otomatis</span>
        </a>
        <a href="{{ route('settings.cashier') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>💼 Operasional Kasir</span>
        </a>
        <a href="{{ route('settings.maintenance.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-black transition-all bg-blue-600 text-white shadow-sm flex items-center gap-1.5 whitespace-nowrap">
            <span>🛡️ Pemeliharaan &amp; Backup</span>
        </a>
    </div>

    <!-- SYSTEM METRICS & HEALTH CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-lg shrink-0">
                💾
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Ukuran Database</span>
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ $stats['db_size'] ?? '0 MB' }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-lg shrink-0">
                🧾
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Faktur Kasir</span>
                <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ number_format($stats['sales_count'] ?? 0) }} Struk</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-lg shrink-0">
                📑
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Jurnal GL</span>
                <span class="text-lg font-black text-blue-600 dark:text-blue-400">{{ number_format($stats['journal_entries_count'] ?? 0) }} Baris</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-lg shrink-0">
                📦
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Mutasi Stok</span>
                <span class="text-lg font-black text-amber-600 dark:text-amber-400">{{ number_format($stats['stock_mutations_count'] ?? 0) }} Log</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- ============================================================== -->
        <!-- KOLOM KIRI (8/12): RIWAYAT BACKUP & DOWNLOAD FILE CADANGAN     -->
        <!-- ============================================================== -->
        <div class="lg:col-span-8 space-y-4">

            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Arsip File Pencadangan Database (Backup History)</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">File SQL dump cadangan yang tersimpan di server lokal SIGMA ERP</p>
                    </div>

                    <!-- TOMBOL PICU BACKUP SEKARANG -->
                    <form action="{{ route('settings.maintenance.backup') }}" method="POST" class="inline" onsubmit="return confirm('Mulai proses pencadangan database saat ini?');">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-sm flex items-center gap-1.5 transition-transform active:scale-95 cursor-pointer">
                            <span>💾</span>
                            <span>CADANGKAN SEKARANG</span>
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-extrabold uppercase tracking-wider text-[11px]">
                                <th class="py-3 px-4">Waktu Pencadangan</th>
                                <th class="py-3 px-4">Nama File Cadangan</th>
                                <th class="py-3 px-4 text-center">Ukuran File</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300 font-medium">
                            @forelse($backups as $bk)
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $bk['created_at']->isoFormat('D MMMM Y, HH:mm:ss') }}</div>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $bk['created_at']->diffForHumans() }}</span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="font-mono font-bold text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                                            <span>📄</span>
                                            <span>{{ $bk['name'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-semibold">
                                        {{ $bk['size'] }}
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Tombol Download -->
                                            <a href="{{ route('settings.maintenance.download', $bk['name']) }}"
                                               class="px-2.5 py-1.5 bg-blue-50 dark:bg-blue-900/40 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700 rounded-lg font-bold text-xs flex items-center gap-1 transition-colors"
                                               title="Unduh ke Komputer">
                                                <span>⬇️</span>
                                                <span>Unduh</span>
                                            </a>

                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('settings.maintenance.delete', $bk['name']) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip file backup [{{ $bk['name'] }}]?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="p-1.5 bg-rose-50 dark:bg-rose-900/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700 rounded-lg transition-colors cursor-pointer"
                                                        title="Hapus File Cadangan">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-10 text-center text-slate-400 dark:text-slate-500">
                                        <div class="space-y-2">
                                            <span class="text-3xl block">📦</span>
                                            <p class="font-semibold text-slate-600 dark:text-slate-300">Belum ada file backup database yang dibuat.</p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500">Tekan tombol <strong>"CADANGKAN SEKARANG"</strong> di atas untuk membuat cadangan data pertama Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECURITY & BEST PRACTICE ALERT -->
            <div class="bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl p-4 text-xs text-amber-900 dark:text-amber-300 space-y-2 shadow-xs">
                <div class="flex items-center gap-2 font-bold text-amber-950 dark:text-amber-200">
                    <span class="text-base">⚠️</span>
                    <span>SOP Keamanan &amp; Protokol Pencadangan Data:</span>
                </div>
                <ul class="list-disc list-inside space-y-1 text-[11px] text-amber-800 dark:text-amber-300 leading-relaxed">
                    <li>Disarankan untuk melakukan <strong>Unduh (Download)</strong> file backup dan menyimpannya di flashdisk / hard disk eksternal secara berkala (minimal seminggu sekali).</li>
                    <li>File cadangan berisi seluruh transaksi POS, stok opname, jurnal akuntansi, dan master data pelanggan/supplier.</li>
                    <li>Untuk memulihkan (Restore) database dari file cadangan, pastikan aktivitas kasir sedang ditutup dan hubungi tim administrator IT.</li>
                </ul>
            </div>

        </div>

        <!-- ============================================================== -->
        <!-- KOLOM KANAN (4/12): AKSI PEMELIHARAAN & INFORMASI ENVIRONMENT  -->
        <!-- ============================================================== -->
        <div class="lg:col-span-4 space-y-4">

            <!-- KARTU AKSI PEMELIHARAAN SISTEM -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">🧹</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Tindakan Pemeliharaan Cepat</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Optimasi performa &amp; pembersihan memori sistem</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <!-- BERSIHKAN CACHE -->
                    <form action="{{ route('settings.maintenance.clear-cache') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 px-3 bg-white dark:bg-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-2xs flex items-center justify-between transition-colors cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <span class="text-base group-hover:rotate-180 transition-transform duration-300">⚡</span>
                                <div class="text-left">
                                    <span class="block text-slate-900 dark:text-white font-extrabold">Bersihkan Cache &amp; Route</span>
                                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-normal">Kompilasi ulang cache Laravel &amp; Blade</span>
                                </div>
                            </div>
                            <span class="text-slate-400 group-hover:text-blue-600 font-black">&rarr;</span>
                        </button>
                    </form>

                    <!-- OPTIMASI TABEL DATABASE (ANALYZE) -->
                    <form action="{{ route('settings.maintenance.optimize-db') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 px-3 bg-white dark:bg-slate-700/60 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 border border-slate-300 dark:border-slate-600 hover:border-indigo-300 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-2xs flex items-center justify-between transition-colors cursor-pointer group">
                            <div class="flex items-center gap-2">
                                <span class="text-base">⚙️</span>
                                <div class="text-left">
                                    <span class="block text-indigo-950 dark:text-indigo-300 font-extrabold">Optimasi Indeks Database</span>
                                    <span class="text-[10px] text-indigo-700 dark:text-indigo-400 font-normal">Jalankan PostgreSQL ANALYZE</span>
                                </div>
                            </div>
                            <span class="text-slate-400 group-hover:text-indigo-600 font-black">&rarr;</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- KARTU PENGALIH MODE OPERASIONAL (DEVELOPMENT VS PRODUCTION) -->
            <div class="bg-white dark:bg-slate-800 border-2 {{ app()->isProduction() ? 'border-emerald-300 dark:border-emerald-800/80' : 'border-amber-300 dark:border-amber-800/80' }} rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                    <div class="flex items-center gap-2.5">
                        <span class="text-2xl">{{ app()->isProduction() ? '🚀' : '🛠️' }}</span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-black text-slate-900 dark:text-white">Mode Operasional Sistem</h2>
                                @if(app()->isProduction())
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        PRODUCTION
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950/80 dark:text-amber-300 border border-amber-300 dark:border-amber-800 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        DEVELOPMENT
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ app()->isProduction() 
                                    ? 'Dioptimalkan untuk komputer kasir & cabang: Transaksi kilat & data sistem terlindungi.' 
                                    : 'Dioptimalkan untuk developer: Live reload kode tanpa cache & penelusuran bug detail.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Status Caching</span>
                        <span class="font-bold {{ app()->configurationIsCached() ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ app()->configurationIsCached() ? '⚡ Aktif (Cache RAM)' : '🔄 Nonaktif (Live Reload)' }}
                        </span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
                        <span class="text-slate-400 text-[10px] uppercase font-bold block">Debug Log</span>
                        <span class="font-bold {{ config('app.debug') ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ config('app.debug') ? '🔍 Detail (Ignition On)' : '🛡️ Terkunci (Safe Mode)' }}
                        </span>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 p-3 text-xs text-slate-600 dark:text-slate-300">
                    <p class="font-bold text-slate-800 dark:text-slate-100">Mode environment hanya-baca</p>
                    <p class="mt-1">Mode ini ditetapkan melalui konfigurasi deployment, bukan dari aplikasi. Untuk uji coba karyawan, gunakan staging dengan database terpisah. Jangan aktifkan APP_DEBUG pada server publik.</p>
                </div>
            </div>

            <!-- KARTU DEMO RESET (Owner Only) -->
            <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-300 dark:border-rose-800 rounded-2xl p-5 shadow-xs space-y-3"
                 x-data="{ showDemoReset: false }">
                <div class="flex items-center gap-2 pb-3 border-b border-rose-200 dark:border-rose-800">
                    <span class="text-xl">🔄</span>
                    <div>
                        <h2 class="text-sm font-black text-rose-900 dark:text-rose-200">Reset Data Demo</h2>
                        <p class="text-xs text-rose-600 dark:text-rose-400">Hapus semua transaksi & muat ulang data master untuk presentasi</p>
                    </div>
                </div>

                @if(app()->isProduction())
                    <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-xl text-xs text-amber-900 dark:text-amber-200 space-y-1">
                        <p class="font-bold flex items-center gap-1.5">
                            <span>🔒</span>
                            <span>Fitur Reset Demo Terkunci di Mode Production</span>
                        </p>
                        <p class="text-[11px] text-amber-700 dark:text-amber-400">
                            Untuk mencegah terhapusnya data riil toko secara tidak sengaja, tombol Reset Demo dinonaktifkan saat sistem berada di Mode Production. Silakan alihkan ke Mode Development di atas jika ingin menggunakan fitur ini.
                        </p>
                    </div>
                @else
                    <div class="text-xs text-rose-700 dark:text-rose-300 bg-rose-100 dark:bg-rose-900/30 rounded-xl p-3 space-y-1">
                        <p class="font-bold">⚠️ PERHATIAN — Tindakan ini tidak dapat dibatalkan!</p>
                        <p>Yang akan dihapus: semua transaksi POS, shift kasir, pesanan penjualan, pembelian, jurnal akuntansi, dan mutasi stok.</p>
                        <p>Yang dipertahankan: produk, supplier, pelanggan, Chart of Accounts, dan konfigurasi sistem.</p>
                    </div>

                    <button type="button" @click="showDemoReset = !showDemoReset"
                            class="w-full py-2.5 px-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-sm flex items-center justify-between transition-colors cursor-pointer">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🔄</span>
                            <span>Reset Data Demo (Presentasi)</span>
                        </div>
                        <span x-text="showDemoReset ? '▲' : '▼'" class="font-black"></span>
                    </button>

                    <div x-show="showDemoReset" x-transition class="space-y-3 pt-1">
                        <form action="{{ route('settings.maintenance.reset-demo') }}" method="POST"
                              onsubmit="return confirm('⚠️ KONFIRMASI TERAKHIR!\n\nSemua data transaksi akan DIHAPUS PERMANEN.\n\nLanjutkan reset data demo?')">
                            @csrf
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-rose-800 dark:text-rose-300">
                                    Ketik <code class="bg-rose-200 dark:bg-rose-900 px-1 rounded font-mono">RESET DEMO</code> untuk konfirmasi:
                                </label>
                                <input type="text"
                                       name="confirm_text"
                                       placeholder="RESET DEMO"
                                       autocomplete="off"
                                       class="w-full px-3 py-2 border-2 border-rose-300 dark:border-rose-700 rounded-xl text-xs font-mono font-bold bg-white dark:bg-slate-800 text-rose-900 dark:text-rose-200 focus:outline-none focus:border-rose-500 placeholder-rose-300 dark:placeholder-rose-700">
                                @error('confirm_text')
                                    <p class="text-rose-600 text-[10px] font-bold">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="mt-2 w-full py-2.5 px-3 bg-rose-700 hover:bg-rose-800 text-white font-black text-xs rounded-xl shadow-sm flex items-center justify-center gap-2 transition-colors cursor-pointer">
                                🗑️ HAPUS SEMUA DATA TRANSAKSI & RESET DEMO
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            <!-- KARTU INFORMASI LINGKUNGAN SISTEM -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">🖥️</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Informasi Lingkungan Sistem</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Spesifikasi teknis engine SIGMA ERP</p>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400">Mode Sistem:</span>
                        <span class="font-bold {{ app()->isProduction() ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ app()->isProduction() ? 'PRODUCTION (Live Kasir)' : 'DEVELOPMENT (Uji Coba)' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400">Database Engine:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white truncate max-w-[180px]" title="{{ $stats['db_version'] ?? 'PostgreSQL' }}">{{ $stats['db_version'] ?? 'PostgreSQL' }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400">Versi PHP:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">PHP {{ $stats['php_version'] ?? PHP_VERSION }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400">Versi Laravel:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">Laravel {{ $stats['laravel_version'] ?? app()->version() }}</span>
                    </div>

                    <div class="flex items-center justify-between py-1.5 border-b border-slate-100 dark:border-slate-700/60">
                        <span class="text-slate-500 dark:text-slate-400">Koneksi Database:</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Terhubung (Online)</span>
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-slate-500 dark:text-slate-400">Zona Waktu Sistem:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">{{ config('app.timezone', 'Asia/Jakarta') }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- SCRIPT LOGIC -->
<script>
function maintenanceApp() {
    return {
        // Alpine data handler for maintenance screen
    };
}
</script>
@endsection
