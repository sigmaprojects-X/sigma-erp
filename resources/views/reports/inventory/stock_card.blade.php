@extends('layouts.app')

@section('title', 'Kartu Mutasi Stok Barang (Stock Card) - SIGMA ERP')
@section('tab_title', 'Kartu Stok')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Persediaan &amp; Logistik</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Kartu Mutasi Stok (Stock Card)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>📜 Kartu Stok Barang Real-Time</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Buku besar riwayat mutasi masuk, keluar, dan saldo berjalan per item produk secara rinci dan terverifikasi audit.
            </p>
        </div>

        @if($report)
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
        @endif
    </div>

    <!-- FILTER BARANG & PERIODE UNIVERSAL -->
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

        <form method="GET" action="{{ route('reports.inventory.stock-card') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end text-xs">
            <input type="hidden" name="period_type" :value="periodType">

            <!-- Pilih Barang -->
            <div class="col-span-1 sm:col-span-2">
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Pilih Item Barang / SKU *</label>
                <select name="product_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ $productId == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} (SKU: {{ $p->sku ?? '-' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Pilih Cabang -->
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
                    <input type="date" name="custom_start_date" value="{{ $startDate }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-slate-400 mb-1">Sampai</label>
                    <input type="date" name="custom_end_date" value="{{ $endDate }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors">
                    🔍 Tampilkan Kartu Stok
                </button>
            </div>
        </form>
    </div>

    @if($report)
        <!-- PRODUCT INFO CARD & KPI STATS -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-5 shadow-xs">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-[10px] font-bold tracking-widest uppercase text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 px-2.5 py-1 rounded-md">
                        Item Terpilih
                    </span>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white mt-1.5 flex items-center gap-2">
                        <span>{{ $report['product']->name }}</span>
                    </h2>
                    <div class="text-xs text-slate-500 mt-1 flex flex-wrap gap-4">
                        <span><strong>SKU:</strong> {{ $report['product']->sku ?? '-' }}</span>
                        <span><strong>Barcode:</strong> {{ $report['product']->barcode ?? '-' }}</span>
                        <span><strong>Kategori:</strong> {{ is_object($report['product']->category) ? $report['product']->category->name : ($report['product']->category ?: 'Umum') }}</span>
                        <span><strong>Satuan:</strong> {{ $report['product']->baseUnit?->unit_name ?? 'Pcs' }}</span>
                        <span><strong>HPP Moving Avg:</strong> Rp {{ number_format($report['product']->cost_price, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-xs text-slate-400">Lingkup Cabang</span>
                    <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
                        {{ $report['store'] ? $report['store']->name : 'Semua Cabang (Konsolidasi)' }}
                    </div>
                </div>
            </div>

            <!-- KPI BOXES -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-800">
                    <span class="text-[10px] font-bold uppercase text-slate-400">Saldo Awal</span>
                    <div class="text-xl font-black text-slate-800 dark:text-slate-100 mt-0.5">
                        {{ number_format($report['opening_balance'], 2, ',', '.') }}
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">Posisi sebelum {{ \Carbon\Carbon::parse($report['start_date'])->format('d/m/Y') }}</div>
                </div>

                <div class="p-3.5 rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-900/30">
                    <span class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">Total Masuk (+)</span>
                    <div class="text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                        +{{ number_format($report['total_in'], 2, ',', '.') }}
                    </div>
                    <div class="text-[10px] text-emerald-600/80 mt-0.5">Penerimaan PO / Retur / Transfer Masuk</div>
                </div>

                <div class="p-3.5 rounded-xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-900/30">
                    <span class="text-[10px] font-bold uppercase text-rose-600 dark:text-rose-400">Total Keluar (-)</span>
                    <div class="text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5">
                        -{{ number_format($report['total_out'], 2, ',', '.') }}
                    </div>
                    <div class="text-[10px] text-rose-600/80 mt-0.5">Penjualan POS / Retur Beli / Transfer Keluar</div>
                </div>

                <div class="p-3.5 rounded-xl bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40">
                    <span class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400">Saldo Akhir</span>
                    <div class="text-xl font-black text-blue-600 dark:text-blue-400 mt-0.5">
                        {{ number_format($report['closing_balance'], 2, ',', '.') }}
                    </div>
                    <div class="text-[10px] text-blue-600/80 mt-0.5">Posisi per {{ \Carbon\Carbon::parse($report['end_date'])->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        <!-- MUTATION LEDGER TABLE -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <span>📋 Mutasi Pergerakan Barang</span>
                    <span class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 rounded-full font-bold text-slate-500">
                        {{ count($report['rows']) }} Transaksi
                    </span>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="py-3 px-3">Tanggal &amp; Waktu</th>
                            <th class="py-3 px-3">Cabang</th>
                            <th class="py-3 px-3">No. Referensi</th>
                            <th class="py-3 px-3">Jenis Mutasi</th>
                            <th class="py-3 px-3 text-right text-emerald-600 dark:text-emerald-400">Masuk (+)</th>
                            <th class="py-3 px-3 text-right text-rose-600 dark:text-rose-400">Keluar (-)</th>
                            <th class="py-3 px-3 text-right font-black">Sisa Saldo</th>
                            <th class="py-3 px-3 text-right">Biaya Satuan</th>
                            <th class="py-3 px-3 text-right">Total Biaya</th>
                            <th class="py-3 px-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-700 dark:text-slate-300">
                        <!-- Baris Saldo Awal -->
                        <tr class="bg-slate-50/60 dark:bg-slate-800/30 italic">
                            <td class="py-2.5 px-3 text-slate-400">{{ \Carbon\Carbon::parse($report['start_date'])->format('d/m/Y 00:00') }}</td>
                            <td class="py-2.5 px-3">-</td>
                            <td class="py-2.5 px-3 font-mono font-bold">SALDO-AWAL</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                    Saldo Awal Periode
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-right">-</td>
                            <td class="py-2.5 px-3 text-right">-</td>
                            <td class="py-2.5 px-3 text-right font-black font-mono text-slate-900 dark:text-white">
                                {{ number_format($report['opening_balance'], 2, ',', '.') }}
                            </td>
                            <td class="py-2.5 px-3 text-right">-</td>
                            <td class="py-2.5 px-3 text-right">-</td>
                            <td class="py-2.5 px-3 text-slate-400">Saldo pembukaan mutasi</td>
                        </tr>

                        @forelse($report['rows'] as $r)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">{{ $r['datetime'] }}</td>
                                <td class="py-2.5 px-3 whitespace-nowrap">{{ $r['store_name'] }}</td>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ $r['reference_number'] }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $r['qty_in'] > 0 ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300' }}">
                                        {{ $r['type_label'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ $r['qty_in'] > 0 ? '+' . number_format($r['qty_in'], 2, ',', '.') : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                                    {{ $r['qty_out'] > 0 ? '-' . number_format($r['qty_out'], 2, ',', '.') : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900 dark:text-white">
                                    {{ number_format($r['balance'], 2, ',', '.') }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                                    {{ $r['unit_cost'] > 0 ? 'Rp ' . number_format($r['unit_cost'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-500">
                                    {{ $r['total_cost'] > 0 ? 'Rp ' . number_format($r['total_cost'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-slate-500 text-[11px] max-w-xs truncate" title="{{ $r['notes'] }}">
                                    {{ $r['notes'] ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-6 text-center text-slate-400">
                                    Tidak ada mutasi transaksi pada rentang periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="border-t-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 font-bold text-slate-900 dark:text-white">
                        <tr>
                            <td colspan="4" class="py-3 px-3 text-right uppercase">Akumulasi Mutasi:</td>
                            <td class="py-3 px-3 text-right font-black text-emerald-600">+{{ number_format($report['total_in'], 2, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-black text-rose-600">-{{ number_format($report['total_out'], 2, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right font-black text-blue-600 dark:text-blue-400">{{ number_format($report['closing_balance'], 2, ',', '.') }}</td>
                            <td colspan="3" class="py-3 px-3 text-slate-400 text-right">Saldo Akhir Penutupan</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
