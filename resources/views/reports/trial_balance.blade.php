@extends('layouts.app')

@section('title', 'Neraca Saldo (Trial Balance)')
@section('tab_title', 'Neraca Saldo')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan Keuangan &amp; Akuntansi</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Neraca Saldo</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <span>📑 Neraca Saldo (Trial Balance)</span>
                </h1>
                @if($report['is_balanced'])
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✅ SEIMBANG (Total Debet == Total Kredit)
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 animate-pulse">
                        ⚠️ TIDAK SEIMBANG
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.trial-balance', array_merge(request()->all(), ['export' => 'xlsx'])) }}"
               class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh Excel (.xlsx)</span>
            </a>
            <button onclick="window.print()"
                    class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak Neraca Saldo
            </button>
        </div>
    </div>

    <!-- FILTER -->
    <form method="GET" action="{{ route('reports.trial-balance') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs print:hidden">
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

    <!-- TABEL TRIAL BALANCE -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-28">Kode Akun</th>
                        <th class="p-3">Nama Akun Buku Besar</th>
                        <th class="p-3">Kategori Tipe</th>
                        <th class="p-3 text-center">Posisi Normal</th>
                        <th class="p-3 text-right">Mutasi Debet (Rp)</th>
                        <th class="p-3 text-right">Mutasi Kredit (Rp)</th>
                        <th class="p-3 text-right">Saldo Bersih (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200 font-mono text-[11px]">
                    @foreach($report['rows'] as $row)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors {{ ($row['debit'] == 0 && $row['credit'] == 0) ? 'opacity-40' : '' }}">
                            <td class="p-3 font-bold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('reports.general-ledger', ['account_id' => \App\Models\ChartOfAccount::where('code', $row['code'])->value('id'), 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="hover:underline">
                                    {{ $row['code'] }}
                                </a>
                            </td>
                            <td class="p-3 font-sans font-bold">
                                {{ $row['name'] }}
                            </td>
                            <td class="p-3 font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $row['type'] }}
                                </span>
                            </td>
                            <td class="p-3 text-center font-sans text-slate-500">
                                {{ $row['normal_balance'] }}
                            </td>
                            <td class="p-3 text-right font-bold text-slate-900 dark:text-white">
                                {{ $row['debit'] > 0 ? number_format($row['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-3 text-right font-bold text-slate-900 dark:text-white">
                                {{ $row['credit'] > 0 ? number_format($row['credit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-3 text-right font-black {{ $row['net_balance'] < 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white' }}">
                                {{ number_format($row['net_balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-100 dark:bg-slate-800/80 font-mono font-black text-xs text-slate-900 dark:text-white border-t-2 border-slate-300 dark:border-slate-700">
                    <tr>
                        <td colspan="4" class="p-3 text-right font-sans uppercase">TOTAL MUTASI PERIODE</td>
                        <td class="p-3 text-right text-indigo-700 dark:text-indigo-400">
                            Rp {{ number_format($report['total_debit'], 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right text-indigo-700 dark:text-indigo-400">
                            Rp {{ number_format($report['total_credit'], 0, ',', '.') }}
                        </td>
                        <td class="p-3 text-right font-sans text-[11px] {{ $report['is_balanced'] ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $report['is_balanced'] ? 'SEIMBANG' : 'SELISIH' }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
