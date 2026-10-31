@extends('layouts.app')

@section('title', 'Detail Aset Tetap - ' . $asset->name)

@section('content')
@php
    $depreciableBase = max(0.01, (float)$asset->acquisition_cost - (float)$asset->salvage_value);
    $depreciatedPercent = min(100, round(((float)$asset->accumulated_depreciation / $depreciableBase) * 100, 1));
@endphp

<div class="space-y-6"
     x-data="{
         depreciateModalOpen: false,
         disposalModalOpen: false,
         depreciatePeriod: '{{ now()->endOfMonth()->toDateString() }}',
         disposalDate: '{{ date('Y-m-d') }}',
         disposalProceeds: 0,
         disposalMethod: 'CASH',
         formatRp(val) {
             return 'Rp ' + Math.round(val || 0).toLocaleString('id-ID');
         }
     }">

    <!-- BACK & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('assets.index') }}" class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ $asset->name }}</h1>
                    @if($asset->status === 'ACTIVE')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                            ● AKTIF
                        </span>
                    @elseif($asset->status === 'FULLY_DEPRECIATED')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                            HABIS SUSUT
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            DILEPAS / WRITE-OFF
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-mono">Kode: {{ $asset->asset_code }} &bull; Lokasi: {{ $asset->store ? $asset->store->name : 'Kantor Pusat / HO' }}</p>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center flex-wrap gap-2.5">
            @if($asset->status === 'ACTIVE')
                <button type="button"
                        @click="depreciateModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl text-xs transition-all shadow-sm shadow-blue-500/25">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Susutkan Bulan Ini</span>
                </button>
                <button type="button"
                        @click="disposalModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 dark:text-rose-300 font-semibold rounded-xl text-xs transition-colors border border-rose-200 dark:border-rose-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Pelepasan / Hapus Aset</span>
                </button>
            @endif
        </div>
    </div>

    <!-- METRICS & PROGRESS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Harga Perolehan -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Harga Perolehan (Cost)</span>
            <div class="mt-2 text-xl font-bold text-slate-800 dark:text-slate-100">
                Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-[11px] text-slate-400">
                Residu: Rp {{ number_format($asset->salvage_value, 0, ',', '.') }}
            </div>
        </div>

        <!-- Akumulasi Depresiasi -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Akumulasi Depresiasi</span>
            <div class="mt-2 text-xl font-bold text-rose-600 dark:text-rose-400">
                Rp {{ number_format($asset->accumulated_depreciation, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-[11px] text-slate-400">
                Susut/bln: Rp {{ number_format($asset->monthly_depreciation_amount, 0, ',', '.') }}
            </div>
        </div>

        <!-- Nilai Buku Bersih (NBV) -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Nilai Buku Bersih (NBV)</span>
            <div class="mt-2 text-xl font-bold text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($asset->book_value, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-[11px] text-slate-400">
                Per tgl: {{ $asset->last_depreciation_date ? $asset->last_depreciation_date->format('d M Y') : 'Awal' }}
            </div>
        </div>

        <!-- Masa Manfaat -->
        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Umur Ekonomis</span>
            <div class="mt-2 text-xl font-bold text-slate-800 dark:text-slate-100">
                {{ $asset->useful_life_months }} <span class="text-xs font-normal text-slate-500">Bulan</span>
            </div>
            <div class="mt-1 text-[11px] text-slate-400">
                {{ round($asset->useful_life_months / 12, 1) }} Tahun &bull; Garis Lurus
            </div>
        </div>
    </div>

    <!-- PROGRESS BAR UMUR PENYUSUTAN -->
    <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex items-center justify-between text-xs mb-2">
            <span class="font-bold text-slate-700 dark:text-slate-200">Progres Penyusutan Aset:</span>
            <span class="font-bold text-blue-600 dark:text-blue-400">{{ $depreciatedPercent }}% Disusutkan</span>
        </div>
        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500"
                 style="width: {{ $depreciatedPercent }}%"></div>
        </div>
        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2">
            <span>Tgl Perolehan: {{ $asset->acquisition_date ? $asset->acquisition_date->format('d M Y') : '-' }}</span>
            <span>Nilai Residu Terlindungi: Rp {{ number_format($asset->salvage_value, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- JADWAL & RIWAYAT DEPRESIASI -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-4.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl text-xs font-bold">GL</span>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Buku Riwayat Penyusutan & Jurnal Terkait</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Daftar pencatatan jurnal depresiasi bulanan yang telah diposting ke General Ledger.</p>
                </div>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ $asset->depreciationRecords->count() }} Kali Disusutkan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/50 text-slate-600 dark:text-slate-400 text-xs font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3 px-4 text-center">No</th>
                        <th class="py-3 px-4">Periode Tutup Buku</th>
                        <th class="py-3 px-4">No. Jurnal General Ledger</th>
                        <th class="py-3 px-4 text-right">Beban Penyusutan (DR)</th>
                        <th class="py-3 px-4 text-right">Sisa Nilai Buku</th>
                        <th class="py-3 px-4 text-center">Waktu Eksekusi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs">
                    @forelse($asset->depreciationRecords->sortByDesc('period_date') as $index => $rec)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 text-center font-bold text-slate-400">
                                {{ $loop->iteration }}
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-800 dark:text-slate-200">
                                {{ $rec->period_date ? $rec->period_date->format('d M Y') : '-' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($rec->journalEntry)
                                    <a href="{{ route('reports.general-ledger') }}?account_id=6001-001" class="font-mono text-blue-600 dark:text-blue-400 hover:underline">
                                        {{ $rec->journalEntry->entry_number }}
                                    </a>
                                @else
                                    <span class="text-slate-400 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($rec->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-medium text-slate-800 dark:text-slate-200">
                                Rp {{ number_format($rec->book_value_after, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center text-slate-500">
                                {{ $rec->created_at ? $rec->created_at->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                Belum ada riwayat penyusutan. Klik tombol <strong>"Susutkan Bulan Ini"</strong> untuk memposting depresiasi perdana.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL SUSUTKAN BULAN INI (SINGLE ASSET) -->
    <div x-show="depreciateModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="depreciateModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6">
                <form action="{{ route('assets.depreciate', $asset->id) }}" method="POST">
                    @csrf
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 rounded-xl">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Eksekusi Jurnal Penyusutan</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $asset->name }}</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-4 text-xs">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                            <div class="flex justify-between py-1">
                                <span class="text-slate-500">Estimasi Beban Susut:</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($asset->monthly_depreciation_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between py-1 border-t border-slate-200/50 dark:border-slate-700/50">
                                <span class="text-slate-500">Sisa Nilai Buku Saat Ini:</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($asset->book_value, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                                Periode Akhir Bulan Jurnal:
                            </label>
                            <input type="date"
                                   name="period_date"
                                   x-model="depreciatePeriod"
                                   required
                                   class="w-full py-2 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2.5">
                        <button type="button" @click="depreciateModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs">
                            Susutkan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL PELEPASAN ASET (DISPOSAL / WRITE-OFF) -->
    <div x-show="disposalModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div @click="disposalModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6">
                <form action="{{ route('assets.dispose', $asset->id) }}" method="POST">
                    @csrf
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 rounded-xl">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">Pelepasan / Hapus Aset (Disposal)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Penjualan aset bekas atau penghapusan aset rusak</p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-900/50 text-[11px] text-amber-800 dark:text-amber-300">
                        ⚠️ <strong>Penting:</strong> Tindakan ini akan menutup akun aset dan akumulasi depresiasi di GL serta mencatat Laba / Rugi Pelepasan Aset Tetap secara otomatis.
                    </div>

                    <div class="mt-4 space-y-3.5 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Tanggal Pelepasan <span class="text-rose-500">*</span>
                            </label>
                            <input type="date"
                                   name="disposal_date"
                                   x-model="disposalDate"
                                   required
                                   class="w-full py-2 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-rose-500 text-slate-900 dark:text-slate-100">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Nilai Hasil Penjualan / Scrap (Rp)
                            </label>
                            <input type="number"
                                   name="disposal_proceeds"
                                   x-model.number="disposalProceeds"
                                   min="0"
                                   step="0.01"
                                   placeholder="0 jika dibuang/rusak tanpa hasil"
                                   class="w-full py-2 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-rose-500 text-slate-900 dark:text-slate-100 font-bold">
                            <span class="text-[11px] text-slate-400 mt-0.5 block">Isi 0 jika aset dibuang/dihapusbukukan tanpa kompensasi.</span>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2.5">
                        <button type="button" @click="disposalModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow-xs">
                            Konfirmasi Pelepasan Aset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
