@extends('layouts.app')

@section('title', 'Laporan Laba Rugi (P&L)')
@section('tab_title', 'Laba Rugi')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan Keuangan &amp; Akuntansi</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Laba Rugi Komprehensif</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📊 Laporan Laba Rugi (Income Statement)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.income-statement', array_merge(request()->all(), ['export' => 'xlsx'])) }}"
               class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh Excel (.xlsx)</span>
            </a>
            <button onclick="window.print()"
                    class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak Laporan
            </button>
        </div>
    </div>

    <!-- FILTER PERIODE & TOKO -->
    <form method="GET" action="{{ route('reports.income-statement') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs print:hidden">
        <div class="w-36">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}"
                   class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
        </div>

        <div class="w-36">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}"
                   class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
        </div>

        <div class="w-48">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Filter Cabang / Toko</label>
            <select name="store_id" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
                <option value="">Semua Cabang Toko</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>
                        {{ $st->name }} ({{ $st->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="pt-3.5">
            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs cursor-pointer">
                Terapkan Filter
            </button>
        </div>
    </form>

    <!-- SUMMARY KPI TILES -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Total Pendapatan Bersih</span>
            <div class="text-xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">Penjualan kotor dikurangi retur & voucher</div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Laba Kotor (Gross Profit)</span>
            <div class="text-xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">
                Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 mt-0.5">Margin Laba Kotor: <span class="font-bold font-mono">{{ $report['gross_profit_margin'] }}%</span></div>
        </div>

        <div class="{{ $report['net_profit'] >= 0 ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-rose-500/10 border-rose-500/20' }} border rounded-2xl p-4 shadow-xs">
            <span class="{{ $report['net_profit'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }} font-bold uppercase text-[10px]">
                Laba / Rugi Bersih (Net Profit)
            </span>
            <div class="text-2xl font-black {{ $report['net_profit'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }} mt-1 font-mono">
                Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
            </div>
            <div class="text-[11px] {{ $report['net_profit'] >= 0 ? 'text-emerald-700/80 dark:text-emerald-300/80' : 'text-rose-700/80 dark:text-rose-300/80' }} mt-0.5">
                Margin Bersih: <span class="font-bold font-mono">{{ $report['net_profit_margin'] }}%</span>
            </div>
        </div>
    </div>

    <!-- SHEET LAPORAN LABA RUGI -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs text-xs font-sans">
        <div class="text-center border-b border-slate-200 dark:border-slate-800 pb-4 mb-4">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ $company->name }}</h2>
            <div class="text-xs font-bold text-slate-600 dark:text-slate-400">LAPORAN LABA RUGI KOMPREHENSIF</div>
            <div class="text-[11px] text-slate-500">Periode: {{ date('d/m/Y', strtotime($startDate)) }} s/d {{ date('d/m/Y', strtotime($endDate)) }}</div>
            @if($report['store'])
                <div class="text-[11px] font-bold text-blue-600">Cabang: {{ $report['store']->name }}</div>
            @endif
        </div>

        <div class="space-y-4">
            <!-- 1. PENDAPATAN -->
            <div>
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px] border-b border-slate-200 dark:border-slate-800 pb-1">
                    1. PENDAPATAN OPERASIONAL (REVENUES)
                </div>
                <table class="w-full text-left mt-2">
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono">
                        @foreach($report['revenues'] as $rev)
                            <tr>
                                <td class="py-1.5 pl-4 font-sans text-slate-700 dark:text-slate-300">
                                    <span class="text-slate-400 font-mono text-[10px] mr-2">{{ $rev['code'] }}</span>
                                    {{ $rev['name'] }}
                                </td>
                                <td class="py-1.5 pr-4 text-right font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($rev['amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50 dark:bg-slate-800/40 font-bold">
                            <td class="py-2 pl-2 font-sans text-slate-900 dark:text-white">TOTAL PENDAPATAN OPERASIONAL</td>
                            <td class="py-2 pr-4 text-right text-slate-900 dark:text-white">
                                Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 2. BEBAN POKOK PENJUALAN (HPP) -->
            <div>
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px] border-b border-slate-200 dark:border-slate-800 pb-1">
                    2. BEBAN POKOK PENJUALAN (COGS / HPP)
                </div>
                <table class="w-full text-left mt-2">
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono">
                        <tr>
                            <td class="py-1.5 pl-4 font-sans text-slate-700 dark:text-slate-300">
                                <span class="text-slate-400 font-mono text-[10px] mr-2">5001-001</span>
                                Beban Pokok Penjualan (HPP Moving Average)
                            </td>
                            <td class="py-1.5 pr-4 text-right font-bold text-rose-600 dark:text-rose-400">
                                (Rp {{ number_format($report['cogs'], 0, ',', '.') }})
                            </td>
                        </tr>
                        <tr class="bg-indigo-50 dark:bg-indigo-950/40 font-black">
                            <td class="py-2 pl-2 font-sans text-indigo-900 dark:text-indigo-300">LABA KOTOR (GROSS PROFIT)</td>
                            <td class="py-2 pr-4 text-right text-indigo-900 dark:text-indigo-300">
                                Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 3. BEBAN OPERASIONAL -->
            <div>
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-[11px] border-b border-slate-200 dark:border-slate-800 pb-1">
                    3. BEBAN OPERASIONAL (OPERATING EXPENSES)
                </div>
                <table class="w-full text-left mt-2">
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono">
                        @forelse($report['operating_expenses'] as $exp)
                            <tr>
                                <td class="py-1.5 pl-4 font-sans text-slate-700 dark:text-slate-300">
                                    <span class="text-slate-400 font-mono text-[10px] mr-2">{{ $exp['code'] }}</span>
                                    {{ $exp['name'] }}
                                </td>
                                <td class="py-1.5 pr-4 text-right font-bold text-rose-600 dark:text-rose-400">
                                    (Rp {{ number_format($exp['amount'], 0, ',', '.') }})
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-2 pl-4 text-slate-400 italic">Tidak ada beban operasional pada periode ini.</td>
                            </tr>
                        @endforelse
                        <tr class="bg-slate-50 dark:bg-slate-800/40 font-bold">
                            <td class="py-2 pl-2 font-sans text-slate-900 dark:text-white">TOTAL BEBAN OPERASIONAL</td>
                            <td class="py-2 pr-4 text-right text-rose-600 dark:text-rose-400">
                                (Rp {{ number_format($report['total_operating_expenses'], 0, ',', '.') }})
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. LABA BERSIH -->
            <div class="p-3 {{ $report['net_profit'] >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-950 dark:text-emerald-200' : 'bg-rose-100 dark:bg-rose-950/80 text-rose-950 dark:text-rose-200' }} rounded-xl flex items-center justify-between font-black text-sm">
                <span class="uppercase">LABA / (RUGI) BERSIH TAHUN BERJALAN (NET PROFIT)</span>
                <span class="font-mono text-base">
                    Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
