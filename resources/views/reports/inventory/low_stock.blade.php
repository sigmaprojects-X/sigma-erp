@extends('layouts.app')

@section('title', 'Laporan Peringatan Stok Minimum (Low Stock Alert) - SIGMA ERP')
@section('tab_title', 'Peringatan Stok')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Persediaan &amp; Logistik</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Peringatan Stok Minimum</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>⚠️ Laporan Peringatan Stok Minimum &amp; Restock</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Deteksi otomatis produk yang habis atau di bawah batas minimum beserta kalkulasi saran kuantitas Purchase Order (PO).
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
            <a href="{{ route('purchases.create') }}"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <span>🛒 Buat Pembelian PO Baru</span>
            </a>
            <button onclick="window.print()"
                    class="px-3.5 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak
            </button>
        </div>
    </div>

    <!-- FILTER CABANG TOKO -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 print:hidden">
        <form method="GET" action="{{ route('reports.inventory.low-stock') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end text-xs">
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

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors">
                    🔍 Terapkan Filter
                </button>
                <a href="{{ route('reports.inventory.low-stock') }}" class="px-3 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold rounded-xl transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    @php
        $totalEstimatedBudget = $report['all_alerts']->sum('estimated_restock_cost');
    @endphp

    <!-- KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-rose-600 to-red-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-rose-100">Stok Habis (Kosong)</span>
            <div class="text-3xl font-black mt-1">{{ number_format($report['critical_count']) }} SKU</div>
            <div class="text-[11px] text-rose-200 mt-1">Stok 0 unit (Darurat Restock)</div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-yellow-600 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-100">Di Bawah Batas Minimum</span>
            <div class="text-3xl font-black mt-1">{{ number_format($report['warning_count']) }} SKU</div>
            <div class="text-[11px] text-amber-100 mt-1">Stok fisik &le; batas minimum</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Produk Peringatan</span>
            <div class="text-3xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($report['total_alert_count']) }} SKU</div>
            <div class="text-[11px] text-slate-500 mt-1">Memerlukan pesanan restock</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Estimasi Kebutuhan Modal</span>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">Rp {{ number_format($totalEstimatedBudget, 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Saran Restock x HPP Moving Avg</div>
        </div>
    </div>

    <!-- ALERT PRODUCTS TABLE -->
    <div x-data="{ activeTab: 'all' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <!-- Tab Headers -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto">
            <button @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>⚠️ Semua Peringatan</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600">
                    {{ $report['total_alert_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'critical'"
                    :class="activeTab === 'critical' ? 'border-b-2 border-rose-600 text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🔴 Stok Habis (Out of Stock)</span>
                <span class="px-2 py-0.5 text-[10px] bg-rose-100 dark:bg-rose-950/60 rounded-full font-bold text-rose-600">
                    {{ $report['critical_count'] }}
                </span>
            </button>
            <button @click="activeTab = 'warning'"
                    :class="activeTab === 'warning' ? 'border-b-2 border-amber-600 text-amber-600 dark:text-amber-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🟡 Menipis (Di Bawah Minimum)</span>
                <span class="px-2 py-0.5 text-[10px] bg-amber-100 dark:bg-amber-950/60 rounded-full font-bold text-amber-600">
                    {{ $report['warning_count'] }}
                </span>
            </button>
        </div>

        <div class="p-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">Kategori</th>
                            <th class="py-3 px-3">Supplier Utama</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-right">Stok Fisik</th>
                            <th class="py-3 px-3 text-right">Batas Min</th>
                            <th class="py-3 px-3 text-right font-black text-blue-600 dark:text-blue-400">Saran PO Restock</th>
                            <th class="py-3 px-3 text-right">HPP Satuan</th>
                            <th class="py-3 px-3 text-right font-black">Estimasi Modal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['all_alerts'] as $item)
                            <tr x-show="activeTab === 'all' || (activeTab === 'critical' && '{{ $item['status'] }}' === 'OUT_OF_STOCK') || (activeTab === 'warning' && '{{ $item['status'] }}' === 'LOW_STOCK')"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3">
                                    @if($item['status'] === 'OUT_OF_STOCK')
                                        <span class="px-2 py-1 rounded-md text-[10px] font-black bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            HABIS (0)
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-md text-[10px] font-black bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 inline-flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                            MENIPIS
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $item['sku'] ?? '-' }}</div>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['category'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['supplier_name'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $item['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-black {{ $item['physical_qty'] <= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ number_format($item['physical_qty'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    {{ number_format($item['min_stock_alert'], 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-blue-600 dark:text-blue-400">
                                    +{{ number_format($item['recommended_restock_qty'], 2, ',', '.') }} {{ $item['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                                    Rp {{ number_format($item['cost_price'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-slate-900 dark:text-white">
                                    Rp {{ number_format($item['estimated_restock_cost'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-400">
                                    🎉 Hebat! Tidak ada produk yang habis atau berada di bawah batas minimum saat ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($report['total_alert_count'] > 0)
                        <tfoot class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-bold text-slate-900 dark:text-white">
                            <tr>
                                <td colspan="7" class="py-3 px-3 text-right uppercase">Total Kebutuhan Modal Restock:</td>
                                <td class="py-3 px-3 text-right font-black text-blue-600 dark:text-blue-400">+{{ number_format($report['all_alerts']->sum('recommended_restock_qty'), 2, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right">-</td>
                                <td class="py-3 px-3 text-right font-black text-slate-900 dark:text-white">
                                    Rp {{ number_format($totalEstimatedBudget, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
