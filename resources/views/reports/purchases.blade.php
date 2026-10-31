@extends('layouts.app')

@section('title', 'Laporan Analisa Pembelian Multi-Dimensi - SIGMA ERP')
@section('tab_title', 'Laporan Pembelian')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    @php
        $selectedStoreName = $storeId ? ($stores->firstWhere('id', $storeId)?->name ?? 'Cabang Terpilih') : 'Semua Cabang Toko (Konsolidasi Pusat)';
        $selectedSupplierName = $supplierId ? ($suppliers->firstWhere('id', $supplierId)?->name ?? 'Supplier Terpilih') : 'Semua Supplier';
    @endphp

    <!-- OFFICIAL CORPORATE PRINT LETTERHEAD (KOP SURAT RESMI) -->
    @include('reports.partials.print-header', [
        'title' => 'LAPORAN REKAPITULASI PEMBELIAN & PENGADAAN BARANG',
        'subtitle' => 'Audit Pengadaan Barang Dagang, Analisa Kategori, Evaluasi Pemasok, dan Monitoring Hutang Dagang (AP)',
        'docCode' => 'SIGMA/RPT-PUR/' . now()->format('Ymd') . '/' . strtoupper(substr(md5($startDate . $endDate . ($storeId ?? 'ALL')), 0, 4)),
        'storeName' => $selectedStoreName,
        'extraFilters' => [
            'Supplier' => $selectedSupplierName,
            'Basis Harga' => 'Moving Average Cost (HPP)',
        ]
    ])

    <!-- BREADCRUMB & HEADER (WEB VIEW ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Pembelian &amp; Hutang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Rekapitulasi Pembelian Multi-Dimensi</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🚚 Laporan Pembelian &amp; Pengadaan Barang</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Monitoring belanja barang dagang per item, jenis/kategori, supplier, dan per cabang toko beserta Rekapitulasi Pembelian &amp; Status Pelunasan supplier (AP).
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

    <!-- FILTER PERIODE UNIVERSAL, CABANG, SUPPLIER -->
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

        <form method="GET" action="{{ route('reports.purchases') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
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

            <!-- Supplier Dropdown -->
            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Supplier</label>
                <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $sp)
                        <option value="{{ $sp->id }}" {{ $supplierId === $sp->id ? 'selected' : '' }}>
                            {{ $sp->name }}
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
        <!-- 1. Total Belanja (Grand Total) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Total Pembelian</div>
            <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                Rp {{ number_format($report['total_amount'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1 font-mono">Subtotal: Rp {{ number_format($report['total_subtotal'] ?? $report['total_amount'], 0, ',', '.') }}</div>
        </div>

        <!-- 2. Terbayar (Lunas) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Sudah Dibayar</div>
            <div class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($report['total_paid'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Kas / Bank Terpotong</div>
        </div>

        <!-- 3. Sisa Hutang Tempo (AP) -->
        <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/50 bg-rose-50/20 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-1">Sisa Hutang (AP)</div>
            <div class="text-base sm:text-lg font-black text-rose-700 dark:text-rose-400">
                Rp {{ number_format($report['total_unpaid'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Kewajiban Tempo Berjalan</div>
        </div>

        <!-- 4. Total Faktur PO -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Total Faktur Masuk</div>
            <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
                {{ number_format($report['total_purchases']) }} Faktur
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Barang Diterima (Received)</div>
        </div>

        <!-- 5. Potongan Diskon Beli -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Diskon dari Supplier</div>
            <div class="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400">
                Rp {{ number_format($report['total_discount'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Hemat Modal Toko</div>
        </div>

        <!-- 6. PPN & Ongkir -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">PPN &amp; Ongkir</div>
            <div class="text-xs font-bold text-blue-600">
                PPN Masukan: Rp {{ number_format($report['total_tax'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-xs font-bold text-slate-500 mt-0.5">
                Ongkir: Rp {{ number_format($report['total_shipping'] ?? 0, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- PRINT-ONLY EXECUTIVE FINANCIAL SUMMARY STRIP (INK-EFFICIENT & PREVENTS PAGE SPLITS) -->
    <div class="hidden print:block print-avoid-break mb-3">
        <div class="text-[7.5pt] font-black uppercase tracking-wider text-slate-800 mb-1 flex items-center justify-between border-b border-slate-300 pb-0.5">
            <span>I. RINGKASAN EKSEKUTIF PENGADAAN &amp; STATUS PELUNASAN</span>
            <span class="text-slate-500 font-mono font-normal">Mata Uang: IDR (Rupiah)</span>
        </div>
        <table class="w-full text-center text-[7.5pt] border-collapse">
            <thead>
                <tr class="bg-slate-100 font-bold text-slate-800">
                    <th class="py-1 px-2 border border-slate-400 text-left">Total Pembelian</th>
                    <th class="py-1 px-2 border border-slate-400">Realisasi Bayar (Kas/Bank)</th>
                    <th class="py-1 px-2 border border-slate-400">Sisa Hutang (AP)</th>
                    <th class="py-1 px-2 border border-slate-400">Jml Faktur</th>
                    <th class="py-1 px-2 border border-slate-400">Diskon Supplier</th>
                    <th class="py-1 px-2 border border-slate-400">PPN &amp; Ongkir</th>
                </tr>
            </thead>
            <tbody>
                <tr class="font-mono font-bold">
                    <td class="py-1 px-2 border border-slate-300 text-left text-slate-900">
                        Rp {{ number_format($report['total_amount'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-emerald-700">
                        Rp {{ number_format($report['total_paid'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-rose-700">
                        Rp {{ number_format($report['total_unpaid'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-800">
                        {{ number_format($report['total_purchases']) }} PO
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-emerald-700">
                        Rp {{ number_format($report['total_discount'] ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-700 text-right">
                        Rp {{ number_format(($report['total_tax'] ?? 0) + ($report['total_shipping'] ?? 0), 0, ',', '.') }}
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
                <span>📦 Per Item Barang Dibeli</span>
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

            <button type="button" @click="tab = 'supplier'"
                    :class="tab === 'supplier' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all whitespace-nowrap flex items-center gap-1.5 cursor-pointer">
                <span>🏭 Per Supplier &amp; Status Hutang</span>
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
        </div>

        <!-- PRINT-ONLY TAB TITLE -->
        <div class="hidden print:flex items-center justify-between px-3 py-1 bg-slate-100 border-b border-slate-400">
            <span class="font-black text-[7.5pt] uppercase tracking-wider text-slate-900">
                II. RINCIAN TRANSAKSI: 
                <span x-text="tab === 'item' ? 'REKAPITULASI PEMBELIAN PER ITEM BARANG' : (tab === 'category' ? 'REKAPITULASI PEMBELIAN PER KATEGORI / JENIS' : (tab === 'supplier' ? 'REKAPITULASI PEMBELIAN PER SUPPLIER & STATUS HUTANG' : 'REKAPITULASI PEMBELIAN PER CABANG TOKO'))"></span>
            </span>
            <span class="text-[7pt] text-slate-500 font-mono">Format Akuntansi Resmi</span>
        </div>

        <!-- 1. TAB: PER ITEM BARANG DIBELI -->
        <div x-show="tab === 'item'" class="p-4 print:p-0 overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">SKU</th>
                        <th class="py-2.5 px-3">Nama Barang</th>
                        <th class="py-2.5 px-3">Kategori</th>
                        <th class="py-2.5 px-3 text-right">Qty Dibeli</th>
                        <th class="py-2.5 px-3 text-right">Total Belanja</th>
                        <th class="py-2.5 px-3 text-right">Rata-rata Harga Beli / Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_item'] as $i => $item)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-bold text-slate-700 dark:text-slate-300">{{ $item['sku'] }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</td>
                        <td class="py-2 px-3 text-slate-500">{{ $item['category'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($item['qty_purchased'], 0, ',', '.') }} {{ $item['base_unit'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">Rp {{ number_format($item['total_amount'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-blue-600 dark:text-blue-400 font-semibold">Rp {{ number_format($item['avg_unit_cost'], 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 italic">Tidak ada transaksi pembelian pada periode ini.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_item']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="4" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL PEMBELIAN PER ITEM:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_item'])->sum('qty_purchased'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_item'])->sum('total_amount'), 0, ',', '.') }}</td>
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
                        <th class="py-2.5 px-3 text-right">Qty Dibeli</th>
                        <th class="py-2.5 px-3 text-right">Total Modal Belanja</th>
                        <th class="py-2.5 px-3 text-right">Porsi Modal (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_category'] as $i => $cat)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $cat['category'] }}</td>
                        <td class="py-2 px-3 text-right font-mono">{{ $cat['item_count'] }} Item</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($cat['qty_purchased'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">Rp {{ number_format($cat['total_amount'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-bold text-blue-600 dark:text-blue-400">{{ $cat['share_percent'] }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400 italic">Tidak ada data pembelian per kategori.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_category']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="3" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL PEMBELIAN KATEGORI:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_category'])->sum('qty_purchased'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_category'])->sum('total_amount'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-bold text-blue-600">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 3. TAB: PER SUPPLIER -->
        <div x-show="tab === 'supplier'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Kode</th>
                        <th class="py-2.5 px-3">Nama Supplier</th>
                        <th class="py-2.5 px-3">NPWP</th>
                        <th class="py-2.5 px-3 text-center">PKP</th>
                        <th class="py-2.5 px-3 text-right">Jml Faktur</th>
                        <th class="py-2.5 px-3 text-right">Total Belanja</th>
                        <th class="py-2.5 px-3 text-right">Sudah Dibayar</th>
                        <th class="py-2.5 px-3 text-right">Sisa Hutang (AP)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_supplier'] as $i => $sup)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono text-slate-500">{{ $sup['supplier_code'] }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $sup['supplier_name'] }}</td>
                        <td class="py-2 px-3 font-mono text-slate-500 text-[11px]">{{ $sup['supplier_npwp'] }}</td>
                        <td class="py-2 px-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $sup['is_pkp'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800' }}">
                                {{ $sup['is_pkp'] ? 'PKP' : 'NON' }}
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($sup['invoice_count']) }} PO</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">Rp {{ number_format($sup['total_amount'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-emerald-600 font-semibold">Rp {{ number_format($sup['total_paid'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold {{ $sup['total_unpaid'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rp {{ number_format($sup['total_unpaid'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400 italic">Tidak ada data supplier.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_supplier']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="5" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL KEWAJIBAN SUPPLIER:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_supplier'])->sum('invoice_count')) }} PO</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_supplier'])->sum('total_amount'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format(collect($report['by_supplier'])->sum('total_paid'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">Rp {{ number_format(collect($report['by_supplier'])->sum('total_unpaid'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        <!-- 4. TAB: PER CABANG -->
        <div x-show="tab === 'store'" class="p-4 print:p-0 overflow-x-auto" style="display: none;">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200 dark:border-slate-800 font-bold">
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3">Kode Cabang</th>
                        <th class="py-2.5 px-3">Nama Toko Cabang</th>
                        <th class="py-2.5 px-3 text-right">Jml Faktur Masuk</th>
                        <th class="py-2.5 px-3 text-right">Total Belanja Cabang</th>
                        <th class="py-2.5 px-3 text-right">Sudah Dibayar</th>
                        <th class="py-2.5 px-3 text-right">Sisa Hutang (AP)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium">
                    @forelse($report['by_store'] as $i => $st)
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                        <td class="py-2 px-3 text-center text-slate-400">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono font-bold text-blue-600 dark:text-blue-400">{{ $st['store_code'] }}</td>
                        <td class="py-2 px-3 font-bold text-slate-900 dark:text-white">{{ $st['store_name'] }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($st['invoice_count']) }} Faktur</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">Rp {{ number_format($st['total_amount'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono text-emerald-600 font-semibold">Rp {{ number_format($st['total_paid'], 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold {{ $st['total_unpaid'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            Rp {{ number_format($st['total_unpaid'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 italic">Tidak ada data cabang toko.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($report['by_store']) > 0)
                <tfoot>
                    <tr class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-400 text-slate-900 dark:text-white">
                        <td colspan="3" class="py-2 px-3 text-right font-black uppercase text-[7.5pt]">TOTAL KONSOLIDASI CABANG:</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format(collect($report['by_store'])->sum('invoice_count')) }} Faktur</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format(collect($report['by_store'])->sum('total_amount'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format(collect($report['by_store'])->sum('total_paid'), 0, ',', '.') }}</td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">Rp {{ number_format(collect($report['by_store'])->sum('total_unpaid'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- OFFICIAL CORPORATE SIGNATURE & APPROVAL BLOCK -->
    @include('reports.partials.print-signatures')
</div>
@endsection
