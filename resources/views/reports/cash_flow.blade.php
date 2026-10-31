@extends('layouts.app')

@section('title', 'Laporan Arus Kas (Cash Flow)')
@section('tab_title', 'Arus Kas')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan Keuangan &amp; Akuntansi</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Arus Kas</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>💵 Laporan Arus Kas (Cash Flow Statement)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak Arus Kas
            </button>
        </div>
    </div>

    <!-- FILTER -->
    <form method="GET" action="{{ route('reports.cash-flow') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs print:hidden">
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
                Tampilkan
            </button>
        </div>
    </form>

    <!-- SUMMARY TILES -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Saldo Awal Kas &amp; Bank</span>
            <div class="text-lg font-black text-slate-900 dark:text-white mt-1 font-mono">
                Rp {{ number_format($report['starting_cash'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-slate-400">Posisi awal periode</div>
        </div>

        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-3.5 shadow-xs">
            <span class="text-emerald-700 dark:text-emerald-400 font-bold uppercase text-[10px]">Total Kas Masuk (Inflow)</span>
            <div class="text-lg font-black text-emerald-700 dark:text-emerald-400 mt-1 font-mono">
                +Rp {{ number_format($report['total_inflow'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-emerald-800/80 dark:text-emerald-300/80">{{ count($report['inflows']) }} transaksi masuk</div>
        </div>

        <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-3.5 shadow-xs">
            <span class="text-rose-700 dark:text-rose-400 font-bold uppercase text-[10px]">Total Kas Keluar (Outflow)</span>
            <div class="text-lg font-black text-rose-700 dark:text-rose-400 mt-1 font-mono">
                -Rp {{ number_format($report['total_outflow'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-rose-800/80 dark:text-rose-300/80">{{ count($report['outflows']) }} transaksi keluar</div>
        </div>

        <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-3.5 shadow-xs">
            <span class="text-blue-700 dark:text-blue-400 font-bold uppercase text-[10px]">Saldo Akhir Kas &amp; Bank</span>
            <div class="text-lg font-black text-blue-700 dark:text-blue-400 mt-1 font-mono">
                Rp {{ number_format($report['ending_cash'], 0, ',', '.') }}
            </div>
            <div class="text-[10px] text-blue-800/80 dark:text-blue-300/80">Arus Bersih: Rp {{ number_format($report['net_cash_flow'], 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- SHEET ARUS KAS -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs text-xs font-sans">
        <div class="text-center border-b border-slate-200 dark:border-slate-800 pb-4 mb-4">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ $company->name }}</h2>
            <div class="text-xs font-bold text-slate-600 dark:text-slate-400">LAPORAN ARUS KAS</div>
            <div class="text-[11px] text-slate-500">Periode: {{ date('d/m/Y', strtotime($startDate)) }} s/d {{ date('d/m/Y', strtotime($endDate)) }}</div>
            @if($report['store'])
                <div class="text-[11px] font-bold text-blue-600">Cabang: {{ $report['store']->name }}</div>
            @endif
        </div>

        <div class="space-y-6">
            <!-- 1. ARUS KAS MASUK -->
            <div>
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-xs border-b border-slate-200 dark:border-slate-800 pb-1.5 flex justify-between">
                    <span>1. PENERIMAAN KAS &amp; BANK (CASH INFLOWS)</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-mono">+Rp {{ number_format($report['total_inflow'], 0, ',', '.') }}</span>
                </div>
                <div class="overflow-x-auto mt-2">
                    <table class="w-full text-left font-mono">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-[11px]">
                            @forelse($report['inflows'] as $in)
                                <tr>
                                    <td class="py-1.5 font-sans w-24 text-slate-500">{{ $in['date']->format('d/m/Y') }}</td>
                                    <td class="py-1.5 font-bold text-blue-600 dark:text-blue-400 w-44">{{ $in['reference'] ?: '-' }}</td>
                                    <td class="py-1.5 font-sans text-slate-700 dark:text-slate-300">{{ $in['description'] }}</td>
                                    <td class="py-1.5 text-right font-black text-emerald-600 dark:text-emerald-400 w-36">
                                        Rp {{ number_format($in['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-2 pl-4 text-slate-400 font-sans italic">Tidak ada arus kas masuk pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. ARUS KAS KELUAR -->
            <div>
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-xs border-b border-slate-200 dark:border-slate-800 pb-1.5 flex justify-between">
                    <span>2. PENGELUARAN KAS &amp; BANK (CASH OUTFLOWS)</span>
                    <span class="text-rose-600 dark:text-rose-400 font-mono">-Rp {{ number_format($report['total_outflow'], 0, ',', '.') }}</span>
                </div>
                <div class="overflow-x-auto mt-2">
                    <table class="w-full text-left font-mono">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-[11px]">
                            @forelse($report['outflows'] as $out)
                                <tr>
                                    <td class="py-1.5 font-sans w-24 text-slate-500">{{ $out['date']->format('d/m/Y') }}</td>
                                    <td class="py-1.5 font-bold text-rose-600 dark:text-rose-400 w-44">{{ $out['reference'] ?: '-' }}</td>
                                    <td class="py-1.5 font-sans text-slate-700 dark:text-slate-300">{{ $out['description'] }}</td>
                                    <td class="py-1.5 text-right font-black text-rose-600 dark:text-rose-400 w-36">
                                        (Rp {{ number_format($out['amount'], 0, ',', '.') }})
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-2 pl-4 text-slate-400 font-sans italic">Tidak ada pengeluaran kas pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REKAPITULASI AKHIR -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl space-y-1.5 font-mono text-xs border border-slate-200 dark:border-slate-800">
                <div class="flex justify-between font-sans text-slate-600 dark:text-slate-400">
                    <span>Saldo Awal Kas &amp; Bank:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">Rp {{ number_format($report['starting_cash'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-sans text-slate-600 dark:text-slate-400">
                    <span>Arus Bersih Periode Berjalan:</span>
                    <span class="font-mono font-bold {{ $report['net_cash_flow'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $report['net_cash_flow'] >= 0 ? '+' : '' }}Rp {{ number_format($report['net_cash_flow'], 0, ',', '.') }}
                    </span>
                </div>
                <div class="flex justify-between font-sans font-black text-sm text-slate-900 dark:text-white pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span class="uppercase">SALDO AKHIR KAS &amp; BANK:</span>
                    <span class="font-mono text-base text-blue-700 dark:text-blue-400">Rp {{ number_format($report['ending_cash'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
