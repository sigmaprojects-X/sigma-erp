@extends('layouts.app')

@section('title', 'Laporan Nilai Persediaan Aset Barang (Stock Valuation) - SIGMA ERP')
@section('tab_title', 'Nilai Persediaan')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    @php
        $selectedStoreName = $storeId ? ($stores->firstWhere('id', $storeId)?->name ?? 'Cabang Terpilih') : 'Semua Cabang Toko (Konsolidasi Pusat)';
        $selectedCatName = $categoryId ? ($categories->firstWhere('id', $categoryId)?->name ?? 'Kategori Terpilih') : 'Semua Kategori';
    @endphp

    <!-- OFFICIAL CORPORATE PRINT LETTERHEAD (KOP SURAT RESMI) -->
    @include('reports.partials.print-header', [
        'title' => 'LAPORAN NILAI PERSEDIAAN ASET BARANG (STOCK VALUATION)',
        'subtitle' => 'Kalkulasi Total Nilai Aset Persediaan Menggunakan Metode Moving Average Cost (Weighted Average Cost)',
        'docCode' => 'SIGMA/RPT-VAL/' . now()->format('Ymd') . '/' . strtoupper(substr(md5(($storeId ?? 'ALL') . ($categoryId ?? 'ALL')), 0, 4)),
        'storeName' => $selectedStoreName,
        'extraFilters' => [
            'Kategori' => $selectedCatName,
            'Metode Valuasi' => 'Moving Average (HPP)',
        ]
    ])

    <!-- BREADCRUMB & HEADER (WEB VIEW ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Persediaan &amp; Logistik</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Nilai Persediaan (Stock Valuation)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>📊 Laporan Nilai Persediaan Aset Toko</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Kalkulasi total nilai aset persediaan barang menggunakan metode Moving Average (Weighted Average Cost) real-time.
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

    <!-- FILTER CABANG & KATEGORI -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 print:hidden">
        <form method="GET" action="{{ route('reports.inventory.valuation') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end text-xs">
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
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Filter Kategori Produk</label>
                <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="">🗂️ Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors">
                    🔍 Terapkan Filter
                </button>
                <a href="{{ route('reports.inventory.valuation') }}" class="px-3 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- SUMMARY CARDS (WEB VIEW ONLY) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
        <!-- Total Nilai Persediaan -->
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-100">Total Nilai Persediaan</span>
            <div class="text-2xl font-black mt-1">Rp {{ number_format($report['total_valuation_value'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-blue-200 mt-1">Qty Fisik x HPP Moving Avg</div>
        </div>

        <!-- Total Item Terdata -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jumlah SKU / Produk</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($report['total_sku_count']) }} SKU</div>
            <div class="text-[11px] text-slate-500 mt-1">Varian produk dengan stok</div>
        </div>

        <!-- Total Fisik Unit -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Fisik Unit</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($report['total_physical_qty'], 2, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Unit fisik di gudang &amp; etalase</div>
        </div>

        <!-- Total Dipesan (Reserved PWA) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Reserved (Pesanan PWA)</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($report['total_reserved_qty'], 2, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Stok terkunci pesanan pending</div>
        </div>
    </div>

    <!-- PRINT-ONLY EXECUTIVE SUMMARY STRIP (INK-EFFICIENT) -->
    <div class="hidden print:block print-avoid-break mb-3">
        <div class="text-[7.5pt] font-black uppercase tracking-wider text-slate-800 mb-1 flex items-center justify-between border-b border-slate-300 pb-0.5">
            <span>I. RINGKASAN EKSEKUTIF ASET PERSEDIAAN</span>
            <span class="text-slate-500 font-mono font-normal">Metode: Moving Average Cost</span>
        </div>
        <table class="w-full text-center text-[7.5pt] border-collapse">
            <thead>
                <tr class="bg-slate-100 font-bold text-slate-800">
                    <th class="py-1 px-2 border border-slate-400 text-left">Total Nilai Valuasi Aset</th>
                    <th class="py-1 px-2 border border-slate-400">Total Varian SKU</th>
                    <th class="py-1 px-2 border border-slate-400">Total Fisik Unit</th>
                    <th class="py-1 px-2 border border-slate-400">Reserved (Pesanan PWA)</th>
                    <th class="py-1 px-2 border border-slate-400">Stok Siap Jual (Available)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="font-mono font-bold">
                    <td class="py-1 px-2 border border-slate-300 text-left text-blue-900">
                        Rp {{ number_format($report['total_valuation_value'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-800">
                        {{ number_format($report['total_sku_count']) }} SKU
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-emerald-700">
                        {{ number_format($report['total_physical_qty'], 2, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-amber-700">
                        {{ number_format($report['total_reserved_qty'], 2, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-900">
                        {{ number_format($report['total_physical_qty'] - $report['total_reserved_qty'], 2, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABS ANALISA PERSADAAN -->
    <div x-data="{ activeTab: 'items' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <!-- Tab Headers (Web View Only) -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto print:hidden">
            <button @click="activeTab = 'items'"
                    :class="activeTab === 'items' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>📦 Rincian Per Item Barang</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600 dark:text-slate-400">
                    {{ count($report['items']) }}
                </span>
            </button>
            <button @click="activeTab = 'category'"
                    :class="activeTab === 'category' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🗂️ Nilai Per Kategori</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600 dark:text-slate-400">
                    {{ count($report['by_category']) }}
                </span>
            </button>
            <button @click="activeTab = 'store'"
                    :class="activeTab === 'store' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🏢 Nilai Per Cabang</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600 dark:text-slate-400">
                    {{ count($report['by_store']) }}
                </span>
            </button>
        </div>

        <!-- PRINT-ONLY TAB TITLE -->
        <div class="hidden print:flex items-center justify-between px-3 py-1 bg-slate-100 border-b border-slate-400">
            <span class="font-black text-[7.5pt] uppercase tracking-wider text-slate-900">
                II. RINCIAN PERSEDIAAN: 
                <span x-text="activeTab === 'items' ? 'REKAPITULASI VALUASI PER ITEM BARANG' : (activeTab === 'category' ? 'REKAPITULASI VALUASI PER KATEGORI' : 'REKAPITULASI VALUASI PER CABANG TOKO')"></span>
            </span>
            <span class="text-[7pt] text-slate-500 font-mono">Format Akuntansi Resmi</span>
        </div>
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🏢 Perbandingan Antar Cabang</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600 dark:text-slate-400">
                    {{ count($report['by_store']) }}
                </span>
            </button>
        </div>

        <!-- TAB CONTENT 1: RINCIAN PER ITEM -->
        <div x-show="activeTab === 'items'" class="p-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Supplier Utama</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-right">Fisik</th>
                            <th class="py-3 px-3 text-right">Reserved</th>
                            <th class="py-3 px-3 text-right">Tersedia</th>
                            <th class="py-3 px-3 text-right">HPP Moving Avg</th>
                            <th class="py-3 px-3 text-right font-black">Nilai Aset</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['items'] as $index => $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $item['sku'] }} | Barcode: {{ $item['barcode'] }}</div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                        {{ $item['category'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['supplier_name'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900 dark:text-white">
                                    {{ number_format($item['physical_qty'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-amber-600 dark:text-amber-400 font-bold">
                                    {{ number_format($item['reserved_qty'], 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-emerald-600 dark:text-emerald-400 font-bold">
                                    {{ number_format($item['available_qty'], 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($item['cost_price'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($item['total_value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-400">
                                    Tidak ada data stok barang yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['items']) > 0)
                        <tfoot class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-bold text-slate-900 dark:text-white">
                            <tr>
                                <td colspan="5" class="py-3 px-3 text-right uppercase">Total Konsolidasi:</td>
                                <td class="py-3 px-3 text-right font-black">{{ number_format($report['total_physical_qty'], 2, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-black text-amber-600">{{ number_format($report['total_reserved_qty'], 2, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-black text-emerald-600">{{ number_format(max(0, $report['total_physical_qty'] - $report['total_reserved_qty']), 2, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right">-</td>
                                <td class="py-3 px-3 text-right font-black text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($report['total_valuation_value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- TAB CONTENT 2: PER KATEGORI -->
        <div x-show="activeTab === 'category'" class="p-5" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Kategori Produk</th>
                            <th class="py-3 px-3 text-right">Jumlah SKU</th>
                            <th class="py-3 px-3 text-right">Total Qty Fisik</th>
                            <th class="py-3 px-3 text-right font-black">Total Nilai Persediaan</th>
                            <th class="py-3 px-3 text-right">Porsi Nilai (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['by_category'] as $idx => $cat)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ $cat['category'] }}</td>
                                <td class="py-2.5 px-3 text-right text-slate-600 dark:text-slate-400 font-mono">{{ number_format($cat['sku_count']) }} SKU</td>
                                <td class="py-2.5 px-3 text-right font-mono">{{ number_format($cat['total_qty'], 2, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($cat['total_value'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ min(100, $cat['value_share_percent']) }}%"></div>
                                        </div>
                                        <span class="font-mono text-slate-600 dark:text-slate-400 font-bold">{{ $cat['value_share_percent'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada data kategori.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONTENT 3: PER CABANG -->
        <div x-show="activeTab === 'store'" class="p-5" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Kode Cabang</th>
                            <th class="py-3 px-3">Nama Cabang / Toko</th>
                            <th class="py-3 px-3 text-right">Jumlah SKU</th>
                            <th class="py-3 px-3 text-right">Total Qty Fisik</th>
                            <th class="py-3 px-3 text-right font-black">Total Nilai Persediaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['by_store'] as $sidx => $st)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $sidx + 1 }}</td>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white">{{ $st['store_code'] }}</td>
                                <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-slate-200">{{ $st['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-mono">{{ number_format($st['sku_count']) }} SKU</td>
                                <td class="py-2.5 px-3 text-right font-mono">{{ number_format($st['total_qty'], 2, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-blue-600 dark:text-blue-400">
                                    Rp {{ number_format($st['total_value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">Tidak ada data cabang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- OFFICIAL CORPORATE SIGNATURE & APPROVAL BLOCK -->
    @include('reports.partials.print-signatures')
</div>
@endsection
