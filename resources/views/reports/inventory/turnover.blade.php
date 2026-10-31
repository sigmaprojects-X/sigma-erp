@extends('layouts.app')

@section('title', 'Laporan Analisa Perputaran Barang (Fast/Slow Moving & Dead Stock) - SIGMA ERP')
@section('tab_title', 'Perputaran Barang')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Persediaan &amp; Logistik</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Analisa Perputaran Stok</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🔄 Analisa Perputaran Barang (Fast / Slow / Dead Stock)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Identifikasi produk terlaris perputaran cepat, barang perputaran lambat, dan stok macet (dead stock) yang membekukan modal kerja.
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

    <!-- FILTER PERIODE UNIVERSAL & CABANG -->
    <div x-data="{ periodType: '{{ $period['period_type'] ?? 'monthly' }}' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 print:hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex flex-wrap items-center gap-1 bg-slate-100 dark:bg-slate-800/60 p-1 rounded-xl text-xs font-bold">
                <button type="button" @click="periodType = 'daily'"
                        :class="periodType === 'daily' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all">
                    📅 Harian
                </button>
                <button type="button" @click="periodType = 'monthly'"
                        :class="periodType === 'monthly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all">
                    🗓️ Bulanan
                </button>
                <button type="button" @click="periodType = 'quarterly'"
                        :class="periodType === 'quarterly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all">
                    📊 Kuartal (Q1 - Q4)
                </button>
                <button type="button" @click="periodType = 'yearly'"
                        :class="periodType === 'yearly' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all">
                    📈 Tahunan
                </button>
                <button type="button" @click="periodType = 'custom'"
                        :class="periodType === 'custom' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition-all">
                    ⚙️ Rentang Kustom
                </button>
            </div>

            <div class="text-xs text-slate-500 flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $period['label'] }}</span>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.inventory.turnover') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
            <input type="hidden" name="period_type" :value="periodType">

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

            <!-- Sub-input Harian -->
            <div x-show="periodType === 'daily'">
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Pilih Tanggal</label>
                <input type="date" name="daily_date" value="{{ $period['daily_date'] ?? now()->toDateString() }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
            </div>

            <!-- Sub-input Bulanan -->
            <div x-show="periodType === 'monthly'" class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Bulan</label>
                    <select name="monthly_month" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ ($period['monthly_month'] ?? now()->month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Tahun</label>
                    <input type="number" name="monthly_year" value="{{ $period['monthly_year'] ?? now()->year }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
            </div>

            <!-- Sub-input Kuartal -->
            <div x-show="periodType === 'quarterly'" class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Kuartal</label>
                    <select name="quarterly_q" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                        <option value="1" {{ ($period['quarterly_q'] ?? 1) == 1 ? 'selected' : '' }}>Q1 (Jan - Mar)</option>
                        <option value="2" {{ ($period['quarterly_q'] ?? 1) == 2 ? 'selected' : '' }}>Q2 (Apr - Jun)</option>
                        <option value="3" {{ ($period['quarterly_q'] ?? 1) == 3 ? 'selected' : '' }}>Q3 (Jul - Sep)</option>
                        <option value="4" {{ ($period['quarterly_q'] ?? 1) == 4 ? 'selected' : '' }}>Q4 (Okt - Des)</option>
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Tahun</label>
                    <input type="number" name="quarterly_year" value="{{ $period['quarterly_year'] ?? now()->year }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
            </div>

            <!-- Sub-input Tahunan -->
            <div x-show="periodType === 'yearly'">
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Tahun Buku</label>
                <input type="number" name="yearly_year" value="{{ $period['yearly_year'] ?? now()->year }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
            </div>

            <!-- Sub-input Rentang Kustom -->
            <div x-show="periodType === 'custom'" class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Dari</label>
                    <input type="date" name="custom_start_date" value="{{ $period['start_date'] }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Sampai</label>
                    <input type="date" name="custom_end_date" value="{{ $period['end_date'] }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors">
                    🔍 Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-100">⚡ Fast Moving</span>
            <div class="text-3xl font-black mt-1">{{ number_format($report['fast_count']) }} SKU</div>
            <div class="text-[11px] text-emerald-200 mt-1">Perputaran tinggi &amp; rutin terjual</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">🐢 Slow Moving</span>
            <div class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($report['slow_count']) }} SKU</div>
            <div class="text-[11px] text-slate-500 mt-1">Perputaran lambat di periode ini</div>
        </div>

        <div class="bg-gradient-to-br from-rose-600 to-red-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-100">⚠️ Dead Stock (Macet)</span>
            <div class="text-3xl font-black mt-1">{{ number_format($report['dead_count']) }} SKU</div>
            <div class="text-[11px] text-rose-200 mt-1">Ada stok fisik tapi 0 penjualan</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Modal Tertahan Dead Stock</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">Rp {{ number_format($report['dead_stock_value'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Potensi modal beku / tidak produktif</div>
        </div>
    </div>

    <!-- TABS ANALISA PERPUTARAN BARANG -->
    <div x-data="{ activeTab: 'dead' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <!-- Tab Headers -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto">
            <button @click="activeTab = 'dead'"
                    :class="activeTab === 'dead' ? 'border-b-2 border-rose-600 text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>⚠️ Dead Stock (Stok Macet)</span>
                <span class="px-2 py-0.5 text-[10px] bg-rose-100 dark:bg-rose-950/60 rounded-full font-bold text-rose-600">
                    {{ $report['dead_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'fast'"
                    :class="activeTab === 'fast' ? 'border-b-2 border-emerald-600 text-emerald-600 dark:text-emerald-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>⚡ Fast Moving (Terlaris)</span>
                <span class="px-2 py-0.5 text-[10px] bg-emerald-100 dark:bg-emerald-950/60 rounded-full font-bold text-emerald-600">
                    {{ $report['fast_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'slow'"
                    :class="activeTab === 'slow' ? 'border-b-2 border-amber-600 text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🐢 Slow Moving</span>
                <span class="px-2 py-0.5 text-[10px] bg-amber-100 dark:bg-amber-950/60 rounded-full font-bold text-amber-600">
                    {{ $report['slow_count'] }}
                </span>
            </button>
        </div>

        <!-- TAB 1: DEAD STOCK -->
        <div x-show="activeTab === 'dead'" class="p-5">
            <div class="mb-3 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200/60 dark:border-rose-900/40 text-xs text-rose-800 dark:text-rose-300 flex items-center justify-between">
                <div>
                    <strong>Rekomendasi Tindakan:</strong> Buat program promo bundling (Buy 1 Get 1) atau diskon cuci gudang untuk mencairkan modal kerja yang tertahan.
                </div>
                <div class="font-bold">
                    Total Nilai Macet: Rp {{ number_format($report['dead_stock_value'], 0, ',', '.') }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-right">Sisa Stok Fisik</th>
                            <th class="py-3 px-3 text-right text-rose-600 font-black">Terjual (Periode Ini)</th>
                            <th class="py-3 px-3 text-right font-black">Modal Tertahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['dead_stock'] as $didx => $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $didx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $item['sku'] ?? '-' }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['category'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-800 dark:text-slate-200">
                                    {{ number_format($item['physical_qty'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black text-rose-600">
                                    0 {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-rose-600 dark:text-rose-400">
                                    Rp {{ number_format($item['current_stock_value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    🎉 Hebat! Tidak ditemukan Dead Stock pada periode ini. Semua produk bergerak terjual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: FAST MOVING -->
        <div x-show="activeTab === 'fast'" class="p-5" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-right font-black text-emerald-600">Qty Terjual</th>
                            <th class="py-3 px-3 text-right">Frekuensi Transaksi</th>
                            <th class="py-3 px-3 text-right font-black text-blue-600">Total Omzet Penjualan</th>
                            <th class="py-3 px-3 text-right">Sisa Stok Fisik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['fast_moving'] as $fidx => $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $fidx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $item['sku'] ?? '-' }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['category'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-black text-emerald-600 font-mono">
                                    {{ number_format($item['qty_sold'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    {{ number_format($item['frequency']) }}x
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($item['revenue'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-800 dark:text-slate-200">
                                    {{ number_format($item['physical_qty'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">Belum ada data barang Fast Moving di periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: SLOW MOVING -->
        <div x-show="activeTab === 'slow'" class="p-5" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-right font-black text-amber-600">Qty Terjual</th>
                            <th class="py-3 px-3 text-right">Frekuensi Transaksi</th>
                            <th class="py-3 px-3 text-right font-black">Total Omzet Penjualan</th>
                            <th class="py-3 px-3 text-right">Sisa Stok Fisik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['slow_moving'] as $sidx => $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $sidx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $item['sku'] ?? '-' }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['category'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-black text-amber-600 font-mono">
                                    {{ number_format($item['qty_sold'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    {{ number_format($item['frequency']) }}x
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-slate-900 dark:text-white">
                                    Rp {{ number_format($item['revenue'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-800 dark:text-slate-200">
                                    {{ number_format($item['physical_qty'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada data barang Slow Moving.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
