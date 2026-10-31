@extends('layouts.app')

@section('title', 'Laporan Analisa Jam Sibuk & Produktivitas Kasir (Peak Hours) - SIGMA ERP')
@section('tab_title', 'Jam Sibuk & Kasir')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Operasional &amp; Kasir</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Analisa Jam Sibuk (Peak Hours)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>⏱️ Analisa Jam Sibuk &amp; Produktivitas Transaksi Kasir</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Distribusi frekuensi transaksi dan perputaran omzet per jam untuk mengoptimalkan alokasi staf dan jadwal shift kasir.
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

        <form method="GET" action="{{ route('reports.operational.peak-hours') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
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
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-100">🔥 Jam Puncak Tersibuk (Peak)</span>
            <div class="text-2xl font-black mt-1">
                {{ $report['peak_hour'] && $report['peak_hour']['tx_count'] > 0 ? $report['peak_hour']['hour_label'] : 'Belum Ada Transaksi' }}
            </div>
            <div class="text-[11px] text-indigo-200 mt-1">
                {{ $report['peak_hour'] ? number_format($report['peak_hour']['tx_count']) . ' transaksi (' . $report['peak_hour']['share_percent'] . '%)' : '-' }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Transaksi Kasir</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($report['total_tx_count']) }} Struk</div>
            <div class="text-[11px] text-slate-500 mt-1">Total volume checkout berhasil</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Omzet Penjualan</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">Rp {{ number_format($report['total_turnover'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Gross sales seluruh kasir</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rata-rata Keranjang (Basket Size)</span>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">Rp {{ number_format($report['avg_basket_size'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Rata-rata belanja per struk kasir</div>
        </div>
    </div>

    <!-- HEATMAP / HOURLY VISUAL BARS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center justify-between">
            <span>📊 Grafik Distribusi Transaksi per Jam (24 Jam)</span>
            <span class="text-xs text-slate-400 font-normal">Tinggi balok menunjukkan proporsi kepadatan transaksi kasir</span>
        </h3>

        @php
            $maxTx = $report['hourly']->max('tx_count') ?: 1;
        @endphp

        <div class="grid grid-cols-6 sm:grid-cols-12 gap-2 pt-8 pb-2">
            @foreach($report['hourly'] as $h)
                @php
                    $pct = round(($h['tx_count'] / $maxTx) * 100);
                    $isPeak = $report['peak_hour'] && $report['peak_hour']['hour_num'] === $h['hour_num'] && $h['tx_count'] > 0;
                @endphp
                <div class="flex flex-col items-center group relative">
                    <!-- Tooltip -->
                    <div class="absolute -top-10 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white text-[10px] rounded px-2 py-1 pointer-events-none whitespace-nowrap z-20 font-mono shadow-lg">
                        {{ $h['hour_label'] }}: {{ $h['tx_count'] }} Tx | Rp {{ number_format($h['turnover'], 0, ',', '.') }}
                    </div>

                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-lg h-24 flex items-end p-1">
                        <div class="w-full rounded-md transition-all duration-300 {{ $isPeak ? 'bg-indigo-600' : ($h['tx_count'] > 0 ? 'bg-blue-500 hover:bg-blue-600' : 'bg-transparent') }}"
                             style="height: {{ max(4, $pct) }}%"></div>
                    </div>
                    <span class="text-[10px] font-mono mt-1 text-slate-500 font-bold {{ $isPeak ? 'text-indigo-600 dark:text-indigo-400' : '' }}">
                        {{ sprintf('%02d', $h['hour_num']) }}
                    </span>
                    <span class="text-[9px] font-mono text-slate-400">
                        {{ $h['tx_count'] }}x
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TABS: RINCIAN TABEL PER JAM & PER HARI -->
    <div x-data="{ activeTab: 'hourly' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto">
            <button @click="activeTab = 'hourly'"
                    :class="activeTab === 'hourly' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>⏱️ Rincian 24 Jam (Hourly Breakdown)</span>
            </button>
            <button @click="activeTab = 'daily'"
                    :class="activeTab === 'daily' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🗓️ Distribusi Hari dalam Seminggu (Senin - Minggu)</span>
            </button>
        </div>

        <!-- TAB CONTENT: HOURLY -->
        <div x-show="activeTab === 'hourly'" class="p-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">Rentang Waktu Jam</th>
                            <th class="py-3 px-3 text-right">Jumlah Transaksi</th>
                            <th class="py-3 px-3 text-right">Porsi (%)</th>
                            <th class="py-3 px-3 text-right font-black">Total Omzet Penjualan</th>
                            <th class="py-3 px-3 text-right">Rata-rata Basket Size</th>
                            <th class="py-3 px-3">Indikator Kepadatan Kasir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @foreach($report['hourly'] as $h)
                            @php
                                $isPeak = $report['peak_hour'] && $report['peak_hour']['hour_num'] === $h['hour_num'] && $h['tx_count'] > 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors {{ $isPeak ? 'bg-indigo-50/40 dark:bg-indigo-950/20' : '' }}">
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $h['hour_label'] }}</span>
                                    @if($isPeak)
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                                            PEAK HOUR
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ number_format($h['tx_count']) }} Tx
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                                    {{ $h['share_percent'] }}%
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($h['turnover'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($h['avg_basket_size'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 max-w-xs overflow-hidden">
                                        <div class="{{ $isPeak ? 'bg-indigo-600' : 'bg-blue-600' }} h-2 rounded-full"
                                             style="width: {{ $report['total_tx_count'] > 0 ? ($h['tx_count'] / $report['total_tx_count']) * 100 : 0 }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONTENT: DAILY -->
        <div x-show="activeTab === 'daily'" class="p-5" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">Hari</th>
                            <th class="py-3 px-3 text-right">Jumlah Transaksi</th>
                            <th class="py-3 px-3 text-right font-black">Total Omzet Penjualan</th>
                            <th class="py-3 px-3 text-right">Rata-rata Basket Size</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @foreach($report['daily'] as $d)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ $d['day_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold">{{ number_format($d['tx_count']) }} Tx</td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($d['turnover'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($d['avg_basket_size'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
