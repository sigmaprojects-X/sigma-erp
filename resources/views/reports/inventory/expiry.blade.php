@extends('layouts.app')

@section('title', 'Laporan Kedaluwarsa Barang & Batch (Expiry Date Report) - SIGMA ERP')
@section('tab_title', 'Kedaluwarsa Barang')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Persediaan &amp; Logistik</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Kedaluwarsa Barang</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>⏳ Rekapitulasi Kedaluwarsa Produk (Batch &amp; Expiry)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Monitoring masa berlaku nomor batch produk guna mencegah kerugian finansial akibat barang expired di gudang/rak display.
            </p>
        </div>

        <div class="flex items-center gap-2 print:hidden">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"
               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Unduh Excel (.xlsx)</span>
            </a>
            <button onclick="window.print()"
                    class="px-3.5 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak
            </button>
        </div>
    </div>

    <!-- FILTER CABANG & RENTANG HARI -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 print:hidden">
        <form method="GET" action="{{ route('reports.inventory.expiry') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end text-xs">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Filter Cabang Toko</label>
                <select name="store_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="">🏢 Semua Cabang (Konsolidasi)</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                            {{ $st->name }} ({{ $st->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Jangka Waktu Peringatan</label>
                <select name="within_days" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="30" {{ $withinDays == 30 ? 'selected' : '' }}>🚨 30 Hari ke Depan (Sangat Kritis)</option>
                    <option value="60" {{ $withinDays == 60 ? 'selected' : '' }}>⚠️ 60 Hari ke Depan (Waspada)</option>
                    <option value="90" {{ $withinDays == 90 ? 'selected' : '' }}>📅 90 Hari ke Depan (3 Bulan - Standar)</option>
                    <option value="180" {{ $withinDays == 180 ? 'selected' : '' }}>🗓️ 180 Hari ke Depan (6 Bulan)</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors">
                    🔍 Terapkan Filter
                </button>
                <a href="{{ route('reports.inventory.expiry') }}" class="px-3 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-rose-700 to-red-800 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-100">Sudah Kedaluwarsa (Expired)</span>
            <div class="text-3xl font-black mt-1">{{ number_format($report['expired_count']) }} Batch</div>
            <div class="text-[11px] text-rose-200 mt-1">Harus segera ditarik / dimusnahkan</div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-100">Kritis (&le; 30 Hari)</span>
            <div class="text-3xl font-black mt-1">{{ number_format($report['critical_count']) }} Batch</div>
            <div class="text-[11px] text-amber-100 mt-1">Segera prioritaskan penjualan / clearance</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Batch Berisiko (&le; {{ $withinDays }} Hari)</span>
            <div class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($report['total_risk_count']) }} Batch</div>
            <div class="text-[11px] text-slate-500 mt-1">Termasuk expired dan mendekati ED</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Potensi Kerugian Finansial</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">Rp {{ number_format($report['total_potential_loss'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Nilai modal batch expired &amp; kritis</div>
        </div>
    </div>

    <!-- TABS EXPIRY REPORT -->
    <div x-data="{ activeTab: 'all' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <!-- Tab Headers -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto">
            <button @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>⚠️ Semua Batch Berisiko</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600">
                    {{ $report['total_risk_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'expired'"
                    :class="activeTab === 'expired' ? 'border-b-2 border-rose-600 text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🛑 Sudah Expired</span>
                <span class="px-2 py-0.5 text-[10px] bg-rose-100 dark:bg-rose-950/60 rounded-full font-bold text-rose-600">
                    {{ $report['expired_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'critical'"
                    :class="activeTab === 'critical' ? 'border-b-2 border-orange-600 text-orange-600 dark:text-orange-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🚨 Kritis (&le; 30 Hari)</span>
                <span class="px-2 py-0.5 text-[10px] bg-orange-100 dark:bg-orange-950/60 rounded-full font-bold text-orange-600">
                    {{ $report['critical_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'warning'"
                    :class="activeTab === 'warning' ? 'border-b-2 border-amber-600 text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🟡 Waspada (31 - 60 Hari)</span>
                <span class="px-2 py-0.5 text-[10px] bg-amber-100 dark:bg-amber-950/60 rounded-full font-bold text-amber-600">
                    {{ $report['warning_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'caution'"
                    :class="activeTab === 'caution' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🔵 Perhatian (61 - {{ $withinDays }} Hari)</span>
                <span class="px-2 py-0.5 text-[10px] bg-blue-100 dark:bg-blue-950/60 rounded-full font-bold text-blue-600">
                    {{ $report['caution_count'] }}
                </span>
            </button>
        </div>

        @php
            $allBatches = $report['expired']->merge($report['critical'])->merge($report['warning'])->merge($report['caution']);
        @endphp

        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">No. Batch</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-center">Tgl Kedaluwarsa</th>
                            <th class="py-3 px-3 text-center">Sisa Hari</th>
                            <th class="py-3 px-3 text-right">Sisa Stok Fisik</th>
                            <th class="py-3 px-3 text-right">HPP Satuan</th>
                            <th class="py-3 px-3 text-right font-black">Potensi Kerugian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($allBatches as $b)
                            <tr x-show="activeTab === 'all' || activeTab === '{{ strtolower($b['status']) }}'"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3">
                                    @if($b['status'] === 'EXPIRED')
                                        <span class="px-2 py-1 rounded text-[10px] font-black bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300">
                                            🛑 EXPIRED
                                        </span>
                                    @elseif($b['status'] === 'CRITICAL')
                                        <span class="px-2 py-1 rounded text-[10px] font-black bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300">
                                            🚨 &le; 30 HARI
                                        </span>
                                    @elseif($b['status'] === 'WARNING')
                                        <span class="px-2 py-1 rounded text-[10px] font-black bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300">
                                            ⚠️ 31-60 HARI
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded text-[10px] font-black bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300">
                                            ℹ️ 61-{{ $withinDays }} HARI
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $b['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $b['sku'] ?? '-' }}</div>
                                </td>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $b['batch_number'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $b['category'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $b['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $b['expired_date'] }}</td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold {{ $b['days_left'] < 0 ? 'text-rose-600' : ($b['days_left'] <= 30 ? 'text-orange-600' : 'text-slate-700 dark:text-slate-300') }}">
                                    @if($b['days_left'] < 0)
                                        Lewat {{ abs($b['days_left']) }} hari
                                    @else
                                        {{ $b['days_left'] }} hari lagi
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ number_format($b['current_qty'], 2, ',', '.') }} {{ $b['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                                    Rp {{ number_format($b['unit_cost'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-black {{ $b['days_left'] <= 30 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-slate-200' }}">
                                    Rp {{ number_format($b['potential_loss'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-400">
                                    🎉 Hebat! Tidak ada batch produk yang kedaluwarsa atau mendekati masa kedaluwarsa dalam rentang {{ $withinDays }} hari.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
