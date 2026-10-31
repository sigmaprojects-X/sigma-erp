@extends('layouts.app')

@section('title', 'Manajemen Aset Tetap & Depresiasi')

@section('content')
<div class="space-y-5" x-data="{ bulkModalOpen: false, selectedPeriod: '{{ now()->endOfMonth()->toDateString() }}' }">

    <!-- HEADER & ACTION -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div>
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-blue-50 dark:bg-blue-950/50 rounded-xl text-blue-600 dark:text-blue-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Aset Tetap & Depresiasi Garis Lurus</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pencatatan perolehan aset, otomasi jurnal penyusutan bulanan (Straight-Line), dan kalkulasi nilai buku bersih.
                    </p>
                </div>
            </div>
        </div>
        <div class="flex items-center flex-wrap gap-2.5">
            <button type="button"
                    @click="bulkModalOpen = true"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 dark:text-indigo-300 font-semibold rounded-xl text-xs transition-all border border-indigo-200 dark:border-indigo-800 shadow-2xs cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Jurnal Susut Massal Akhir Bulan</span>
            </button>
            <a href="{{ route('assets.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl text-xs transition-all shadow-sm shadow-blue-500/25">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Daftarkan Aset Baru</span>
            </a>
        </div>
    </div>

    <!-- KPI STATS CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Nilai Perolehan -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Harga Perolehan</span>
                <span class="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl text-xs font-bold">1501</span>
            </div>
            <div class="mt-2.5 text-xl font-bold text-slate-800 dark:text-slate-100">
                Rp {{ number_format($stats['total_cost'] ?? 0, 0, ',', '.') }}
            </div>
            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Nilai akuisisi historis seluruh aset aktif</p>
        </div>

        <!-- Akumulasi Penyusutan -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akumulasi Penyusutan</span>
                <span class="p-2 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold">1502</span>
            </div>
            <div class="mt-2.5 text-xl font-bold text-rose-600 dark:text-rose-400">
                Rp {{ number_format($stats['total_accumulated'] ?? 0, 0, ',', '.') }}
            </div>
            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Total depresiasi yang telah dijurnal</p>
        </div>

        <!-- Nilai Buku Bersih (Net Book Value) -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Nilai Buku Bersih (NBV)</span>
                <span class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl text-xs font-bold">Net</span>
            </div>
            <div class="mt-2.5 text-xl font-bold text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($stats['total_book_value'] ?? 0, 0, ',', '.') }}
            </div>
            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Nilai aset di neraca keuangan</p>
        </div>

        <!-- Jumlah Aset Aktif -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Aset Beroperasi</span>
                <span class="p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl text-xs font-bold">Unit</span>
            </div>
            <div class="mt-2.5 text-xl font-bold text-slate-800 dark:text-slate-100">
                {{ $stats['active_count'] ?? 0 }} <span class="text-xs font-normal text-slate-500">Aset</span>
            </div>
            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Status ACTIVE yang disusutkan rutin</p>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('assets.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari Kode Aset atau Nama Aset..."
                           class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 rounded-xl focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>
            </div>
            <div>
                <select name="category" class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 rounded-xl focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Golongan Kategori</option>
                    <option value="EQUIPMENT" {{ request('category') === 'EQUIPMENT' ? 'selected' : '' }}>Peralatan & Mesin (1501-001)</option>
                    <option value="VEHICLE" {{ request('category') === 'VEHICLE' ? 'selected' : '' }}>Kendaraan Operasional (1501-002)</option>
                    <option value="BUILDING" {{ request('category') === 'BUILDING' ? 'selected' : '' }}>Bangunan & Renovasi (1501-003)</option>
                    <option value="FURNITURE" {{ request('category') === 'FURNITURE' ? 'selected' : '' }}>Mebel & Inventaris Toko (1501-004)</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <select name="status" class="flex-1 py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-slate-100 rounded-xl focus:bg-white dark:focus:bg-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">Semua Status</option>
                    <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>Aktif Beroperasi (ACTIVE)</option>
                    <option value="FULLY_DEPRECIATED" {{ request('status') === 'FULLY_DEPRECIATED' ? 'selected' : '' }}>Habis Susut (FULLY_DEPRECIATED)</option>
                    <option value="DISPOSED" {{ request('status') === 'DISPOSED' ? 'selected' : '' }}>Dilepas / Dijual (DISPOSED)</option>
                </select>
                <button type="submit" class="py-2 px-4 bg-slate-800 hover:bg-slate-900 text-white dark:bg-slate-700 dark:hover:bg-slate-600 text-xs font-semibold rounded-xl transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'category', 'status']))
                    <a href="{{ route('assets.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABLE DAFTAR ASET -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 text-xs font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3 px-4">Kode & Nama Aset</th>
                        <th class="py-3 px-4">Golongan / Lokasi</th>
                        <th class="py-3 px-4">Tgl Perolehan & Umur</th>
                        <th class="py-3 px-4 text-right">Harga Perolehan</th>
                        <th class="py-3 px-4 text-right">Akumulasi Susut</th>
                        <th class="py-3 px-4 text-right">Nilai Buku Bersih</th>
                        <th class="py-3 px-4 text-right">Susut / Bulan</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($assets as $asset)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- Kode & Nama -->
                            <td class="py-3 px-4">
                                <a href="{{ route('assets.show', $asset->id) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $asset->name }}
                                </a>
                                <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $asset->asset_code }}
                                </div>
                            </td>

                            <!-- Golongan & Lokasi -->
                            <td class="py-3 px-4">
                                <div>
                                    @switch($asset->category)
                                        @case('EQUIPMENT')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                                Peralatan (1501-001)
                                            </span>
                                            @break
                                        @case('VEHICLE')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                Kendaraan (1501-002)
                                            </span>
                                            @break
                                        @case('BUILDING')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                                Bangunan (1501-003)
                                            </span>
                                            @break
                                        @case('FURNITURE')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                                Mebel (1501-004)
                                            </span>
                                            @break
                                        @default
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                                {{ $asset->category }}
                                            </span>
                                    @endswitch
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                                    📍 {{ $asset->store ? $asset->store->name : 'Kantor Pusat / HO' }}
                                </div>
                            </td>

                            <!-- Tanggal Perolehan & Masa Manfaat -->
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-700 dark:text-slate-200">
                                    {{ $asset->acquisition_date ? $asset->acquisition_date->format('d M Y') : '-' }}
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    Masa: <span class="font-semibold">{{ $asset->useful_life_months }} bln</span>
                                    ({{ round($asset->useful_life_months / 12, 1) }} thn)
                                </div>
                            </td>

                            <!-- Harga Perolehan -->
                            <td class="py-3 px-4 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}
                            </td>

                            <!-- Akumulasi Penyusutan -->
                            <td class="py-3 px-4 text-right font-medium text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}
                            </td>

                            <!-- Nilai Buku Bersih -->
                            <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-slate-100">
                                Rp {{ number_format($asset->book_value, 0, ',', '.') }}
                            </td>

                            <!-- Susut per Bulan -->
                            <td class="py-3 px-4 text-right font-medium text-slate-600 dark:text-slate-400">
                                Rp {{ number_format($asset->monthly_depreciation_amount, 0, ',', '.') }}
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 text-center">
                                @if($asset->status === 'ACTIVE')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ● AKTIF
                                    </span>
                                @elseif($asset->status === 'FULLY_DEPRECIATED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        HABIS SUSUT
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        DILEPAS
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <a href="{{ route('assets.show', $asset->id) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 font-semibold rounded-lg text-xs transition-colors">
                                    <span>Detail</span>
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-4xl mb-2">🏢</span>
                                    <p class="font-medium text-slate-600 dark:text-slate-400">Belum ada aset tetap yang terdaftar.</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Daftarkan inventaris atau peralatan toko untuk memulai depresiasi otomatis.</p>
                                    <a href="{{ route('assets.create') }}" class="mt-4 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                                        + Daftarkan Aset Sekarang
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assets->hasPages())
            <div class="p-4 border-t border-slate-200/80 dark:border-slate-800">
                {{ $assets->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL SUSUT MASSAL AKHIR BULAN -->
    <div x-show="bulkModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="bulkModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="bulkModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="bulkModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6">
                <form action="{{ route('assets.depreciate-all') }}" method="POST">
                    @csrf
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Eksekusi Jurnal Susut Massal</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Jurnal penyusutan bulanan untuk seluruh aset berstatus AKTIF.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            💡 <strong>Prinsip Akuntansi Otomatis:</strong> Sistem akan membuat jurnal berpasangan di General Ledger:
                            <div class="mt-2 font-mono text-[11px] bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 space-y-1">
                                <div>[DR] 6001-001 Beban Penyusutan Aset Tetap</div>
                                <div class="pl-4">[CR] 1502-00x Akumulasi Penyusutan (Sesuai Kategori)</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Periode Akhir Bulan Jurnal:
                            </label>
                            <input type="date"
                                   name="period_date"
                                   x-model="selectedPeriod"
                                   required
                                   class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-indigo-500 text-slate-900 dark:text-slate-100">
                            <span class="text-[11px] text-slate-400 mt-1 block">Aset yang sudah disusutkan pada periode ini akan dilewati secara otomatis (aman dari dobel jurnal).</span>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2.5">
                        <button type="button"
                                @click="bulkModalOpen = false"
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
                            ⚡ Proses Susut Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
