@extends('layouts.app')

@section('title', 'Sinkronisasi Versi & Informasi Database - SIGMA ERP')
@section('page_title', 'Sinkronisasi Versi & Migrasi Lokal')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full animate-in fade-in duration-200" x-data="systemUpdateManager()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#E2E8F0] dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-[#64748B] mb-1">
                <span>Pengaturan Sistem</span>
                <span>/</span>
                <span class="text-[#0B1F4D] dark:text-white font-bold">Sinkronisasi Versi &amp; Status Database</span>
            </div>
            <h1 class="text-2xl font-black text-[#0B1F4D] dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🔄 Sinkronisasi Versi &amp; Migrasi Database Lokal</span>
            </h1>
            <p class="text-xs text-[#64748B] dark:text-slate-400 mt-1">
                Jalankan pencadangan database otomatis (pg_dump), migrasi skema lokal, dan pembersihan cache setelah memperbarui file aplikasi secara manual/offline.
            </p>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button"
                    @click="checkUpdates()"
                    :disabled="isChecking"
                    class="px-4 py-2 bg-white dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-[#0B1F4D] dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg class="w-4 h-4 text-[#125BCE]" :class="{ 'animate-spin': isChecking }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span x-text="isChecking ? 'Memeriksa Konfigurasi...' : 'Cek Status Versi Lokal'">Cek Status Versi Lokal</span>
            </button>
        </div>
    </div>

    <!-- HERO UPDATE STATUS BANNER -->
    @if($info['update_available'])
        <div class="rounded-2xl bg-gradient-to-r from-[#0B1F4D] via-[#125BCE] to-[#18BDEB] p-6 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-5">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center text-2xl shrink-0 shadow-inner">
                        🚀
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#FFB800] text-[#071536]">
                                Sinkronisasi Lokal Diperlukan
                            </span>
                            <span class="text-xs text-blue-100 font-mono">v{{ $info['latest_version'] }}</span>
                        </div>
                        <h2 class="text-xl md:text-2xl font-black tracking-tight leading-snug">
                            Sinkronisasi Skema &amp; Versi Lokal v{{ $info['latest_version'] }}
                        </h2>
                        <p class="text-xs text-blue-100/90 mt-1 max-w-xl">
                            File konfigurasi lokal mendeteksi target versi v{{ $info['latest_version'] }}. Halaman ini tidak melakukan pengecekan atau pengunduhan online, melainkan menjalankan Auto-Backup database (pg_dump), migrasi tabel lokal, dan pembersihan cache.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <form method="POST" action="{{ route('settings.update.apply') }}" @submit="isUpdating = true">
                        @csrf
                        <button type="submit"
                                :disabled="isUpdating"
                                class="w-full sm:w-auto px-6 py-3 bg-[#15803D] hover:bg-[#166534] active:bg-[#14532D] text-white font-extrabold text-xs rounded-xl shadow-lg shadow-emerald-950/40 border border-emerald-400/40 transition-all hover:scale-102 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                            <span x-show="!isUpdating">⚡ Jalankan Backup &amp; Migrasi Lokal</span>
                            <span x-show="isUpdating" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                Memproses Auto-Backup &amp; Migrasi Lokal...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-2xl bg-white dark:bg-slate-800 p-6 border border-[#E2E8F0] dark:border-slate-700 shadow-xs flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-xl shrink-0">
                    ✓
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-bold text-[#0B1F4D] dark:text-white">Versi Konfigurasi Lokal Sudah Sinkron</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                            v{{ $info['current_version'] }} (Sinkron)
                        </span>
                    </div>
                    <p class="text-xs text-[#64748B] dark:text-slate-400 mt-0.5">
                        Penanda versi aktif dan konfigurasi lokal sudah selaras (fitur ini tidak melakukan pengecekan atau pengunduhan online).
                    </p>
                </div>
            </div>
            <div class="hidden sm:block text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider text-[#64748B]">Mode Pembaruan</div>
                <div class="text-xs font-bold text-[#125BCE]">Sinkronisasi Paket Lokal / Offline</div>
            </div>
        </div>
    @endif

    <!-- GRID INFORMASI PENEMPATAN DATABASE & HARDWARE -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        
        <!-- CARD 1: LOKASI PENYIMPANAN DATABASE (FITUR UTAMA YG DITANYAKAN USER) -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-[#E2E8F0] dark:border-slate-700 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#64748B] dark:text-slate-400">Penyimpanan Database</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse" title="Database Aktif"></span>
                </div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-[#125BCE] dark:text-[#18BDEB] flex items-center justify-center text-lg font-bold">
                        💾
                    </div>
                    <div>
                        <div class="text-xs font-bold text-[#0B1F4D] dark:text-white">{{ $info['database_engine'] }}</div>
                        <div class="text-[11px] text-[#64748B] dark:text-slate-400">Database: <strong class="text-slate-800 dark:text-slate-200">{{ $info['database_name'] }}</strong></div>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs pt-2 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-[#64748B] dark:text-slate-400 font-semibold uppercase">Lokasi Folder Data Fisik:</span>
                        <code class="text-[11px] font-mono font-bold text-[#0B1F4D] dark:text-[#18BDEB] bg-slate-100 dark:bg-slate-900 px-2 py-1 rounded mt-0.5 break-all">
                            {{ $info['database_storage_path'] }}
                        </code>
                        <span class="text-[9.5px] text-slate-400 mt-0.5">
                            @if(!empty($info['is_windows']))
                                *Dapat dipindah ke Drive D:\ atau folder eksternal kapan saja.
                            @else
                                *Dapat dipindah ke partisi storage / mount NVMe terpisah kapan saja.
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-[#64748B] dark:text-slate-400">Ukuran Database:</span>
                        <span class="font-bold text-slate-800 dark:text-white font-mono">{{ $info['database_size'] }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <a href="{{ route('settings.maintenance.index') }}" class="text-xs font-bold text-[#125BCE] hover:underline flex items-center gap-1">
                    <span>Lihat Backup Database</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>

        <!-- CARD 2: KAPASITAS STORAGE & DRIVE INSTALASI -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-[#E2E8F0] dark:border-slate-700 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#64748B] dark:text-slate-400">Drive &amp; Kapasitas Server</span>
                    <span class="text-xs font-mono font-bold text-[#125BCE]">{{ $info['install_drive'] }}</span>
                </div>
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-[#15803D] dark:text-emerald-400 flex items-center justify-center text-lg font-bold">
                        💽
                    </div>
                    <div>
                        <div class="text-xs font-bold text-[#0B1F4D] dark:text-white">Ruang Penyimpanan Server</div>
                        <div class="text-[11px] text-[#64748B] dark:text-slate-400">
                            Sisa Bebas: <strong class="text-emerald-600 dark:text-emerald-400">{{ $info['disk_free_space'] }}</strong>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 text-xs pt-2 border-t border-slate-100 dark:border-slate-700">
                    <!-- Progress Bar Storage Disk -->
                    <div class="space-y-1">
                        <div class="flex justify-between items-center text-[10.5px]">
                            <span class="text-[#64748B] dark:text-slate-400">Storage Disk:</span>
                            <span class="font-bold text-slate-800 dark:text-white font-mono">{{ $info['disk_used_space'] }} / {{ $info['disk_total_space'] }} ({{ $info['disk_used_percentage'] }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2 overflow-hidden flex">
                            <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $info['disk_used_percentage'] }}%"></div>
                        </div>
                    </div>

                    <!-- RAM / Memory Server -->
                    @if($info['ram_total'] !== 'N/A')
                        <div class="space-y-1 pt-1">
                            <div class="flex justify-between items-center text-[10.5px]">
                                <span class="text-[#64748B] dark:text-slate-400">RAM Server:</span>
                                <span class="font-bold text-slate-800 dark:text-white font-mono">{{ $info['ram_used'] }} / {{ $info['ram_total'] }} ({{ $info['ram_percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden flex">
                                <div class="bg-blue-600 dark:bg-blue-500 h-1.5 rounded-full" style="width: {{ $info['ram_percentage'] }}%"></div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-between items-center pt-1">
                        <span class="text-[#64748B] dark:text-slate-400">Sistem Operasi:</span>
                        <span class="font-bold text-slate-800 dark:text-white truncate max-w-[150px]">{{ $info['server_os'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B] dark:text-slate-400">Waktu Server:</span>
                        <span class="font-mono text-[10.5px] text-slate-600 dark:text-slate-300 font-bold">{{ now()->translatedFormat('H:i:s T') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <span>✓</span> Ruang penyimpanan sangat aman untuk database ritel
                </span>
            </div>
        </div>

        <!-- CARD 3: INFORMASI VERSI SISTEM & RUNTIME -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-[#E2E8F0] dark:border-slate-700 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#64748B] dark:text-slate-400">Versi &amp; Runtime Engine</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300">PHP {{ $info['php_version'] }}</span>
                </div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg font-bold">
                        ⚙️
                    </div>
                    <div>
                        <div class="text-xs font-bold text-[#0B1F4D] dark:text-white">{{ $info['app_name'] }}</div>
                        <div class="text-[11px] text-[#64748B] dark:text-slate-400">Edisi: Enterprise Retail &amp; POS</div>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs pt-2 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B] dark:text-slate-400">Versi Terinstal:</span>
                        <span class="font-bold text-[#0B1F4D] dark:text-white font-mono">v{{ $info['current_version'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B] dark:text-slate-400">Versi Terbaru:</span>
                        <span class="font-bold text-[#125BCE] font-mono">v{{ $info['latest_version'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[#64748B] dark:text-slate-400">Framework Core:</span>
                        <span class="font-bold text-slate-800 dark:text-white font-mono">Laravel {{ $info['laravel_version'] }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <span class="text-[10px] text-slate-400 font-mono">Dirilis: {{ $info['release_date'] }}</span>
            </div>
        </div>

    </div>

    <!-- CATATAN PEMBARUAN RESMI (CHANGELOG) -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-[#E2E8F0] dark:border-slate-700 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-[#E2E8F0] dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="text-base font-bold text-[#0B1F4D] dark:text-white">📜 Catatan Rilis &amp; Fitur Baru (Changelog)</span>
            </div>
            <span class="text-xs text-[#64748B] font-mono">Komersial Siap Distribusi</span>
        </div>

        <div class="p-6 divide-y divide-slate-100 dark:divide-slate-700">
            @foreach($changelogs as $ver => $log)
                <div class="py-4 first:pt-0 last:pb-0 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-[#125BCE] text-white font-mono">
                                {{ $ver }}
                            </span>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                {{ $log['title'] }}
                            </h3>
                        </div>
                        <span class="text-xs text-[#64748B] dark:text-slate-400 font-mono">
                            {{ $log['release_date'] }}
                        </span>
                    </div>

                    @if(!empty($log['features']))
                        <div class="space-y-1.5 pl-4 border-l-2 border-emerald-500/40">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">✨ Fitur Baru &amp; Penyempurnaan:</span>
                            <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                                @foreach($log['features'] as $feat)
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-500 font-bold">&bull;</span>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(!empty($log['fixes']))
                        <div class="space-y-1.5 pl-4 border-l-2 border-blue-500/40">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">🔧 Perbaikan Bug:</span>
                            <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                                @foreach($log['fixes'] as $fix)
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-500 font-bold">&bull;</span>
                                        <span>{{ $fix }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

</div>

<script>
function systemUpdateManager() {
    return {
        isChecking: false,
        isUpdating: false,

        async checkUpdates() {
            this.isChecking = true;
            try {
                const res = await fetch('{{ route("settings.update.check") }}');
                const data = await res.json();
                if (data.update_available) {
                    alert('Konfigurasi lokal mendeteksi versi target v' + data.latest_version + ' (versi aktif saat ini v' + data.current_version + '). Silakan jalankan backup & migrasi lokal.');
                    window.location.reload();
                } else {
                    alert('Versi konfigurasi lokal sudah sinkron (v' + data.current_version + '). Tidak ada pengecekan atau pengunduhan online yang dilakukan.');
                }
            } catch (e) {
                alert('Gagal membaca status versi lokal: ' + e.message);
            } finally {
                this.isChecking = false;
            }
        }
    };
}
</script>
@endsection
