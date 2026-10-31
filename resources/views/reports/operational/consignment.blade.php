@extends('layouts.app')

@section('title', 'Laporan Penjualan Barang Konsinyasi & Bagi Hasil UMKM - SIGMA ERP')
@section('tab_title', 'Penjualan Konsinyasi')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Operasional &amp; Pembelian</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Titip Jual Konsinyasi UMKM</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🤝 Laporan Penjualan Konsinyasi &amp; Bagi Hasil UMKM</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Kalkulasi transparan omzet barang titipan, hak setor dana ke mitra UMKM/Supplier Non-PKP, dan laba komisi toko.
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

    <!-- FILTER PERIODE UNIVERSAL, CABANG & MITRA UMKM -->
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

        <form method="GET" action="{{ route('reports.operational.consignment') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end text-xs">
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

            <div>
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Filter Mitra UMKM / Supplier</label>
                <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    <option value="">🤝 Semua Mitra UMKM</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ $supplierId == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
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
                <div class="w-20">
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
                <div class="w-20">
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
        <div class="bg-gradient-to-br from-indigo-600 to-blue-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-100">Total Omzet Titip Jual</span>
            <div class="text-2xl font-black mt-1">Rp {{ number_format($report['total_gross_revenue'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-indigo-200 mt-1">Total terjual: {{ number_format($report['total_items_sold']) }} unit</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Wajib Setor ke UMKM (Hutang)</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">Rp {{ number_format($report['total_supplier_payable'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Hak bersih mitra UMKM / Supplier</div>
        </div>

        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-4 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-100">Komisi Laba Toko (Fee)</span>
            <div class="text-2xl font-black mt-1">Rp {{ number_format($report['total_store_commission'], 0, ',', '.') }}</div>
            <div class="text-[11px] text-emerald-200 mt-1">Bagi hasil keuntungan toko</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rata-rata Margin Toko</span>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ $report['avg_commission_percent'] }}%</div>
            <div class="text-[11px] text-slate-500 mt-1">Persentase rata-rata bagi hasil</div>
        </div>
    </div>

    <!-- TABS RINCIAN PER ITEM & REKAP PER SUPPLIER -->
    <div x-data="{ activeTab: 'items' }" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="flex border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 overflow-x-auto">
            <button @click="activeTab = 'items'"
                    :class="activeTab === 'items' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>📦 Rincian Barang Terjual</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600">
                    {{ count($report['items']) }}
                </span>
            </button>
            <button @click="activeTab = 'suppliers'"
                    :class="activeTab === 'suppliers' ? 'border-b-2 border-blue-600 text-blue-600 dark:text-blue-400 bg-white dark:bg-slate-900 font-bold' : 'text-slate-500 hover:text-slate-700 font-medium'"
                    class="px-5 py-3.5 text-xs transition-colors flex items-center gap-2 whitespace-nowrap cursor-pointer">
                <span>🤝 Rekapitulasi per Mitra UMKM</span>
                <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-600">
                    {{ count($report['suppliers']) }}
                </span>
            </button>
        </div>

        <!-- TAB 1: RINCIAN PER ITEM -->
        <div x-show="activeTab === 'items'" class="p-5">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Barang &amp; SKU</th>
                            <th class="py-3 px-3">Mitra UMKM / Supplier</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3 text-right">Qty Terjual</th>
                            <th class="py-3 px-3 text-right">Harga Jual</th>
                            <th class="py-3 px-3 text-right font-black">Total Omzet</th>
                            <th class="py-3 px-3 text-right font-black text-rose-600">Setor ke UMKM</th>
                            <th class="py-3 px-3 text-right font-black text-emerald-600">Komisi Toko</th>
                            <th class="py-3 px-3 text-right">Margin (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['items'] as $idx => $it)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $it['product_name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">SKU: {{ $it['sku'] }}</div>
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $it['supplier_name'] }}</td>
                                <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400">{{ $it['store_name'] }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    {{ number_format($it['qty_sold'], 2, ',', '.') }} {{ $it['base_unit'] }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($it['selling_price'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-slate-900 dark:text-white">
                                    Rp {{ number_format($it['gross_revenue'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-rose-600 dark:text-rose-400">
                                    Rp {{ number_format($it['supplier_payable'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-emerald-600 dark:text-emerald-400">
                                    Rp {{ number_format($it['store_commission'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ $it['commission_percent'] }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-400">
                                    Tidak ada data penjualan barang konsinyasi pada rentang periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['items']) > 0)
                        <tfoot class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-bold text-slate-900 dark:text-white">
                            <tr>
                                <td colspan="4" class="py-3 px-3 text-right uppercase">Total Penjualan Konsinyasi:</td>
                                <td class="py-3 px-3 text-right font-black">{{ number_format($report['total_items_sold'], 2, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right">-</td>
                                <td class="py-3 px-3 text-right font-black">Rp {{ number_format($report['total_gross_revenue'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-black text-rose-600">Rp {{ number_format($report['total_supplier_payable'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-black text-emerald-600">Rp {{ number_format($report['total_store_commission'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-black text-blue-600">{{ $report['avg_commission_percent'] }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- TAB 2: REKAPITULASI PER MITRA UMKM -->
        <div x-show="activeTab === 'suppliers'" class="p-5" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Nama Mitra UMKM / Supplier</th>
                            <th class="py-3 px-3">No. Kontak / HP</th>
                            <th class="py-3 px-3 text-right">Total Qty Terjual</th>
                            <th class="py-3 px-3 text-right font-black">Total Omzet Kotor</th>
                            <th class="py-3 px-3 text-right font-black text-rose-600">Wajib Disetor ke UMKM</th>
                            <th class="py-3 px-3 text-right font-black text-emerald-600">Bagi Hasil Toko</th>
                            <th class="py-3 px-3 text-right">Margin Toko (%)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($report['suppliers'] as $sidx => $sp)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-400">{{ $sidx + 1 }}</td>
                                <td class="py-2.5 px-3 font-bold text-slate-900 dark:text-white">{{ $sp['supplier_name'] }}</td>
                                <td class="py-2.5 px-3 font-mono text-slate-500">{{ $sp['phone'] }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold">{{ number_format($sp['total_qty'], 2, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold font-mono text-slate-900 dark:text-white">
                                    Rp {{ number_format($sp['gross_revenue'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-rose-600 dark:text-rose-400">
                                    Rp {{ number_format($sp['supplier_payable'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-black font-mono text-emerald-600 dark:text-emerald-400">
                                    Rp {{ number_format($sp['store_commission'], 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                    {{ $sp['commission_percent'] }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">Tidak ada data rekapitulasi supplier UMKM.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
