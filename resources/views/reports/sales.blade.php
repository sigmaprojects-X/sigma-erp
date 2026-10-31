@extends('layouts.app')

@section('title', 'Laporan Analisa Penjualan Multi-Dimensi - SIGMA ERP')
@section('tab_title', 'Laporan Penjualan')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    @php
        $selectedStoreName = $storeId ? ($stores->firstWhere('id', $storeId)?->name ?? 'Cabang Terpilih') : 'Semua Cabang Toko (Konsolidasi Pusat)';
        $selectedCashierName = $cashierId ? ($cashiers->firstWhere('id', $cashierId)?->name ?? 'Kasir Terpilih') : 'Semua Kasir';
        $selectedPaymentName = $paymentMethod ? strtoupper($paymentMethod) : 'Semua Metode';
    @endphp

    <!-- OFFICIAL CORPORATE PRINT LETTERHEAD (KOP SURAT RESMI) -->
    @include('reports.partials.print-header', [
        'title' => 'LAPORAN ANALISA PENJUALAN RITEL & GROSIR',
        'subtitle' => 'Evaluasi Komprehensif Omzet, HPP Moving Average, Laba Kotor, dan Produktivitas Kasir/Cabang',
        'docCode' => 'SIGMA/RPT-SLS/' . now()->format('Ymd') . '/' . strtoupper(substr(md5($startDate . $endDate . ($storeId ?? 'ALL')), 0, 4)),
        'storeName' => $selectedStoreName,
        'extraFilters' => [
            'Kasir' => $selectedCashierName,
            'Metode Pembayaran' => $selectedPaymentName,
        ]
    ])

    <!-- BREADCRUMB & HEADER (WEB VIEW ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Bisnis &amp; Ritel</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Analisa Penjualan Multi-Dimensi</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🛒 Laporan Analisa Penjualan Ritel &amp; Grosir</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Evaluasi komprehensif omzet, HPP, dan laba kotor per item barang, jenis/kategori, kasir, pelanggan, supplier, dan per cabang.
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
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-colors">
                CSV
            </a>
            <button onclick="window.print()"
                    class="px-3.5 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak
            </button>
        </div>
    </div>

    <!-- FILTER PERIODE UNIVERSAL, CABANG, KASIR, METODE BAYAR -->
    <div x-data="{ periodType: '{{ $period['period_type'] ?? 'monthly' }}' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 print:hidden">
        <!-- Quick Period Selection Tabs -->
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

        <form method="GET" action="{{ route('reports.sales') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
            <input type="hidden" name="period_type" :value="periodType">

            <!-- Sub-input Harian -->
            <div x-show="periodType === 'daily'" class="col-span-1 sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Pilih Tanggal</label>
                <input type="date" name="daily_date" value="{{ $period['daily_date'] ?? now()->toDateString() }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
            </div>

            <!-- Sub-input Bulanan -->
            <div x-show="periodType === 'monthly'" class="col-span-1 sm:col-span-2 flex gap-2">
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
                <div class="w-28">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Tahun</label>
                    <select name="monthly_year" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                        @foreach(range(now()->year - 3, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ ($period['monthly_year'] ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Sub-input Kuartalan -->
            <div x-show="periodType === 'quarterly'" class="col-span-1 sm:col-span-2 flex gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Kuartal</label>
                    <select name="quarter_val" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                        <option value="Q1" {{ ($period['quarter_val'] ?? '') === 'Q1' ? 'selected' : '' }}>Q1 (Januari - Maret)</option>
                        <option value="Q2" {{ ($period['quarter_val'] ?? '') === 'Q2' ? 'selected' : '' }}>Q2 (April - Juni)</option>
                        <option value="Q3" {{ ($period['quarter_val'] ?? '') === 'Q3' ? 'selected' : '' }}>Q3 (Juli - September)</option>
                        <option value="Q4" {{ ($period['quarter_val'] ?? '') === 'Q4' ? 'selected' : '' }}>Q4 (Oktober - Desember)</option>
                    </select>
                </div>
                <div class="w-28">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Tahun</label>
                    <select name="quarter_year" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                        @foreach(range(now()->year - 3, now()->year + 1) as $y)
                            <option value="{{ $y }}" {{ ($period['quarter_year'] ?? now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Sub-input Tahunan -->
            <div x-show="periodType === 'yearly'" class="col-span-1 sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Tahun Buku Penuh</label>
                <select name="yearly_year" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    @foreach(range(now()->year - 3, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ ($period['yearly_year'] ?? now()->year) == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Sub-input Kustom -->
            <div x-show="periodType === 'custom'" class="col-span-1 sm:col-span-2 flex gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
            </div>

            <!-- Cabang / Toko Dropdown -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Cabang Toko</label>
                <select name="store_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="">🏢 Semua Cabang (Konsolidasi)</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>
                            {{ $st->name }} ({{ $st->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Kasir Dropdown -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Kasir</label>
                <select name="cashier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $cs)
                        <option value="{{ $cs->id }}" {{ $cashierId === $cs->id ? 'selected' : '' }}>
                            {{ $cs->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Filter -->
            <div class="flex gap-2">
                <button type="submit"
                        class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    🔍 Filter
                </button>
            </div>
        </form>
    </div>

    <!-- KPI CARDS SUMMARY (6 METRICS - WEB DASHBOARD VIEW) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 print:hidden">
        <!-- 1. Omzet Bersih -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Omzet Bersih</div>
            <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                Rp {{ number_format($report['total_net_sales'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1 font-mono">Kotor: Rp {{ number_format($report['total_gross_sales'], 0, ',', '.') }}</div>
        </div>

        <!-- 2. HPP Barang Terjual -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Total HPP (COGS)</div>
            <div class="text-base sm:text-lg font-black text-slate-700 dark:text-slate-300">
                Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Biaya Pokok Barang</div>
        </div>

        <!-- 3. Laba Kotor & Margin -->
        <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/20 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1">Laba Kotor (Gross)</div>
            <div class="text-base sm:text-lg font-black text-emerald-700 dark:text-emerald-400">
                Rp {{ number_format($report['total_gross_profit'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] font-bold text-emerald-600 mt-1">Margin: {{ $report['profit_margin'] }}%</div>
        </div>

        <!-- 4. Total Transaksi -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Total Transaksi</div>
            <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                {{ number_format($report['total_transactions']) }} Struk
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Struk Kasir Selesai</div>
        </div>

        <!-- 5. Rata-rata Basket Size -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Basket Size / ATV</div>
            <div class="text-base sm:text-lg font-black text-blue-600 dark:text-blue-400">
                Rp {{ number_format($report['avg_transaction'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Rata-rata per Struk</div>
        </div>

        <!-- 6. Potongan Diskon & PPN -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Diskon &amp; PPN</div>
            <div class="text-xs font-bold text-rose-600">
                Diskon: Rp {{ number_format($report['total_discount'], 0, ',', '.') }}
            </div>
            <div class="text-xs font-bold text-blue-600 mt-0.5">
                PPN: Rp {{ number_format($report['total_tax'], 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- PRINT-ONLY EXECUTIVE FINANCIAL SUMMARY STRIP (INK-EFFICIENT & PREVENTS PAGE SPLITS) -->
    <div class="hidden print:block print-avoid-break mb-3">
        <div class="text-[7.5pt] font-black uppercase tracking-wider text-slate-800 mb-1 flex items-center justify-between border-b border-slate-300 pb-0.5">
            <span>I. RINGKASAN EKSEKUTIF KINERJA PENJUALAN &amp; LABA KOTOR</span>
            <span class="text-slate-500 font-mono font-normal">Mata Uang: IDR (Rupiah)</span>
        </div>
        <table class="w-full text-center text-[7.5pt] border-collapse">
            <thead>
                <tr class="bg-slate-100 font-bold text-slate-800">
                    <th class="py-1 px-2 border border-slate-400 text-left">Omzet Bersih</th>
                    <th class="py-1 px-2 border border-slate-400">Total HPP (COGS)</th>
                    <th class="py-1 px-2 border border-slate-400">Laba Kotor (Gross Profit)</th>
                    <th class="py-1 px-2 border border-slate-400">Margin (%)</th>
                    <th class="py-1 px-2 border border-slate-400">Jml Transaksi</th>
                    <th class="py-1 px-2 border border-slate-400">Rata-rata / Struk</th>
                </tr>
            </thead>
            <tbody>
                <tr class="font-mono font-bold">
                    <td class="py-1 px-2 border border-slate-300 text-left text-slate-900">
                        Rp {{ number_format($report['total_net_sales'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-700">
                        Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-emerald-700">
                        Rp {{ number_format($report['total_gross_profit'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-blue-700">
                        {{ $report['profit_margin'] }}%
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-800">
                        {{ number_format($report['total_transactions']) }} Struk
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-700 text-right">
                        Rp {{ number_format($report['avg_transaction'], 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- MULTI-DIMENSIONAL TABS -->
    <div x-data="{ tab: 'item' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <!-- Tab Headers (Web View Only) -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 px-4 pt-2 overflow-x-auto gap-2 print:hidden">
            <button type="button" @click="tab = 'item'"
                    :class="tab === 'item' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>📦 Per Item Barang</span>
                <span class="px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                    {{ count($report['by_item']) }}
                </span>
            </button>

            <button type="button" @click="tab = 'category'"
                    :class="tab === 'category' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏷️ Per Jenis / Kategori</span>
                <span class="px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                    {{ count($report['by_category']) }}
                </span>
            </button>

            <button type="button" @click="tab = 'cashier'"
                    :class="tab === 'cashier' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>👤 Per Kasir</span>
                <span class="px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                    {{ count($report['by_cashier']) }}
                </span>
            </button>

            <button type="button" @click="tab = 'customer'"
                    :class="tab === 'customer' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>👥 Per Pelanggan</span>
                <span class="px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                    {{ count($report['by_customer']) }}
                </span>
            </button>

            <button type="button" @click="tab = 'supplier'"
                    :class="tab === 'supplier' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏭 Per Supplier Pemasok</span>
                <span class="px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                    {{ count($report['by_supplier']) }}
                </span>
            </button>

            <button type="button" @click="tab = 'store'"
                    :class="tab === 'store' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏢 Per Cabang Toko</span>
                <span class="px-1.5 py-0.2 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 text-[10px] font-bold">
                    {{ count($report['by_store']) }}
                </span>
            </button>

            <button type="button" @click="tab = 'payment'"
                    :class="tab === 'payment' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>💳 Metode Bayar</span>
            </button>
        </div>

        <!-- PRINT-ONLY TAB TITLE -->
        <div class="hidden print:flex items-center justify-between px-3 py-1 bg-slate-100 border-b border-slate-400">
            <span class="font-black text-[7.5pt] uppercase tracking-wider text-slate-900">
                II. RINCIAN TRANSAKSI: 
                <span x-text="tab === 'item' ? 'REKAPITULASI PENJUALAN PER ITEM BARANG' : (tab === 'category' ? 'REKAPITULASI PENJUALAN PER KATEGORI / JENIS' : (tab === 'cashier' ? 'REKAPITULASI PENJUALAN PER KASIR' : (tab === 'customer' ? 'REKAPITULASI PENJUALAN PER PELANGGAN' : (tab === 'supplier' ? 'REKAPITULASI PENJUALAN PER SUPPLIER' : (tab === 'store' ? 'REKAPITULASI PENJUALAN PER CABANG TOKO' : 'REKAPITULASI METODE PEMBAYARAN')))))"></span>
            </span>
            <span class="text-[7pt] text-slate-500 font-mono">Format Akuntansi Resmi</span>
        </div>

        <!-- 1. TAB: PER ITEM BARANG -->
        <div x-show="tab === 'item'" class="p-4 print:p-0 overflow-x-auto">
            <div class="mb-3 flex items-center justify-between print:hidden">
                <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300">Top 20 Produk Terlaris &amp; Rincian Penjualan per Item</h3>
            </div>
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">SKU</th>
                        <th class="py-2.5 px-3">Nama Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3">Supplier Utama</th>
                        <th class="py-2.5 px-3 text-right">Qty Terjual</th>
                        <th class="py-2.5 px-3 text-right">Total Omzet</th>
                        <th class="py-2.5 px-3 text-right">Total HPP</th>
                        <th class="py-2.5 px-3 text-right">Laba Kotor</th>
                        <th class="py-2.5 px-3 text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_item'] as $i => $item)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item['sku'] }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</td>
                        <td class="py-2 px-3 text-slate-500">{{ $item['category'] }}</td>
                        <td class="py-2 px-3 text-slate-500">{{ $item['supplier_name'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($item['qty_sold'], 0, ',', '.') }} {{ $item['base_unit'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format($item['total_sales'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-500">Rp {{ number_format($item['total_cogs'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($item['gross_profit'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">{{ $item['profit_margin'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-8 text-center text-slate-400 italic">Tidak ada transaksi penjualan pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_item']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="5" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL PENJUALAN PER ITEM:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_item'])->sum('qty_sold'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_item'])->sum('total_sales'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-500">Rp {{ number_format(collect($report['by_item'])->sum('total_cogs'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format(collect($report['by_item'])->sum('gross_profit'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 2. TAB: PER KATEGORI -->
        <div x-show="tab === 'category'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Kategori Barang</th>
                        <th class="py-2.5 px-3 text-right">Variasi Item</th>
                        <th class="py-2.5 px-3 text-right">Qty Terjual</th>
                        <th class="py-2.5 px-3 text-right">Total Omzet</th>
                        <th class="py-2.5 px-3 text-right">Total HPP</th>
                        <th class="py-2.5 px-3 text-right">Laba Kotor</th>
                        <th class="py-2.5 px-3 text-right">Margin %</th>
                        <th class="py-2.5 px-3 text-right">Porsi Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_category'] as $i => $cat)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $cat['category'] }}</td>
                        <td class="py-2 px-3 text-right font-mono">{{ $cat['item_count'] }} Item</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($cat['qty_sold'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format($cat['total_sales'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-500">Rp {{ number_format($cat['total_cogs'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($cat['gross_profit'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-bold text-slate-700 dark:text-slate-300">{{ $cat['profit_margin'] }}%</td>
                        <td class="py-2 px-3 text-right font-bold text-blue-600 dark:text-blue-400">{{ $cat['sales_share_percent'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400 italic">Tidak ada data penjualan per kategori.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_category']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="3" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL KATEGORI:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_category'])->sum('qty_sold'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_category'])->sum('total_sales'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-500">Rp {{ number_format(collect($report['by_category'])->sum('total_cogs'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format(collect($report['by_category'])->sum('gross_profit'), 0, ',', '.') }}</td>
                        <td></td>
                        <td class="py-2 px-3 text-right font-bold text-blue-600">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 3. TAB: PER KASIR -->
        <div x-show="tab === 'cashier'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Nama Kasir</th>
                        <th class="py-2.5 px-3 text-right">Jumlah Struk</th>
                        <th class="py-2.5 px-3 text-right">Total Omzet Penjualan</th>
                        <th class="py-2.5 px-3 text-right">Rata-rata per Struk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_cashier'] as $i => $cs)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $cs['cashier_name'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($cs['transaction_count']) }} Struk</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($cs['total_sales'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono">Rp {{ number_format($cs['avg_per_tx'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400 italic">Tidak ada data kasir.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_cashier']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="2" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL OMZET KASIR:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_cashier'])->sum('transaction_count')) }} Struk</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600">Rp {{ number_format(collect($report['by_cashier'])->sum('total_sales'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 4. TAB: PER PELANGGAN -->
        <div x-show="tab === 'customer'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Kode</th>
                        <th class="py-2.5 px-3">Nama Pelanggan</th>
                        <th class="py-2.5 px-3">Tier</th>
                        <th class="py-2.5 px-3 text-right">Jml Transaksi</th>
                        <th class="py-2.5 px-3 text-right">Total Belanja</th>
                        <th class="py-2.5 px-3 text-right">Sisa Piutang Berjalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_customer'] as $i => $cust)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono text-slate-500">{{ $cust['customer_code'] }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $cust['customer_name'] }}</td>
                        <td class="py-2 px-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                {{ $cust['tier_name'] }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right font-mono">{{ $cust['transaction_count'] }} kali</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($cust['total_sales'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold {{ $cust['current_credit_balance'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rp {{ number_format($cust['current_credit_balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 italic">Tidak ada data pelanggan.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_customer']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="4" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL PELANGGAN:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_customer'])->sum('transaction_count')) }} kali</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600">Rp {{ number_format(collect($report['by_customer'])->sum('total_sales'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">Rp {{ number_format(collect($report['by_customer'])->sum('current_credit_balance'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 5. TAB: PER SUPPLIER -->
        <div x-show="tab === 'supplier'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Nama Supplier Pemasok</th>
                        <th class="py-2.5 px-3 text-right">Qty Terjual</th>
                        <th class="py-2.5 px-3 text-right">Total Omzet</th>
                        <th class="py-2.5 px-3 text-right">Total HPP</th>
                        <th class="py-2.5 px-3 text-right">Laba Kotor Toko</th>
                        <th class="py-2.5 px-3 text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_supplier'] as $i => $sup)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $sup['supplier_name'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($sup['qty_sold'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format($sup['total_sales'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-500">Rp {{ number_format($sup['total_cogs'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($sup['gross_profit'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-bold text-slate-700 dark:text-slate-300">{{ $sup['profit_margin'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 italic">Tidak ada data supplier.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_supplier']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="2" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL OMZET SUPPLIER:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_supplier'])->sum('qty_sold'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_supplier'])->sum('total_sales'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-slate-500">Rp {{ number_format(collect($report['by_supplier'])->sum('total_cogs'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format(collect($report['by_supplier'])->sum('gross_profit'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 6. TAB: PER CABANG (BENCHMARK MULTI-CABANG) -->
        <div x-show="tab === 'store'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Kode Cabang</th>
                        <th class="py-2.5 px-3">Nama Toko Cabang</th>
                        <th class="py-2.5 px-3 text-right">Jml Transaksi</th>
                        <th class="py-2.5 px-3 text-right">Total Omzet Penjualan</th>
                        <th class="py-2.5 px-3 text-right">Rata-rata per Struk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_store'] as $i => $st)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $st['store_code'] }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $st['store_name'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($st['transaction_count']) }} Struk</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($st['total_sales'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono">Rp {{ number_format($st['avg_per_tx'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 italic">Tidak ada data cabang toko.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_store']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="3" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL OMZET CABANG:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_store'])->sum('transaction_count')) }} Struk</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format(collect($report['by_store'])->sum('total_sales'), 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 7. TAB: METODE PEMBAYARAN -->
        <div x-show="tab === 'payment'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <div class="mb-3 flex items-center justify-between print:hidden">
                <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300">Komposisi Metode Pembayaran</h3>
            </div>
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Metode Pembayaran</th>
                        <th class="py-2.5 px-3 text-right">Frekuensi Transaksi</th>
                        <th class="py-2.5 px-3 text-right">Total Uang Masuk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_payment_method'] as $i => $pm)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">
                            <span class="px-2 py-0.5 rounded font-mono text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold">
                                {{ $pm['method'] }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($pm['count']) }} kali</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($pm['total_amount'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-slate-400 italic">Tidak ada data transaksi.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_payment_method']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="2" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL PEMBAYARAN MASUK:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_payment_method'])->sum('count')) }} kali</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-blue-600">Rp {{ number_format(collect($report['by_payment_method'])->sum('total_amount'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    <!-- OFFICIAL CORPORATE SIGNATURE & APPROVAL BLOCK -->
    @include('reports.partials.print-signatures')
</div>
@endsection
