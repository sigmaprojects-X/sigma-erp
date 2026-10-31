@extends('layouts.app')

@section('title', 'Laporan Neraca Keuangan (Balance Sheet)')
@section('tab_title', 'Neraca Keuangan')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan Keuangan &amp; Akuntansi</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Neraca Keuangan</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <span>⚖️ Laporan Neraca (Balance Sheet)</span>
                </h1>
                @if($report['is_balanced'])
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✅ SEIMBANG (A = L + E)
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 animate-pulse">
                        ⚠️ TIDAK SEIMBANG
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.balance-sheet', array_merge(request()->all(), ['export' => 'xlsx'])) }}"
               class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh Excel (.xlsx)</span>
            </a>
            <button onclick="window.print()"
                    class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak Neraca
            </button>
        </div>
    </div>

    <!-- FILTER PER TANGGAL & TOKO -->
    <form method="GET" action="{{ route('reports.balance-sheet') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs print:hidden">
        <div class="w-44">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Posisi Per Tanggal</label>
            <input type="date" name="as_of_date" value="{{ $asOfDate }}"
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
                Tampilkan Neraca
            </button>
        </div>
    </form>

    <!-- NERACA SHEET -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-xs text-xs font-sans">
        <div class="text-center border-b border-slate-200 dark:border-slate-800 pb-4 mb-6">
            <h2 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ $company->name }}</h2>
            <div class="text-xs font-bold text-slate-600 dark:text-slate-400">LAPORAN POSISI KEUANGAN (NERACA)</div>
            <div class="text-[11px] text-slate-500">Per Tanggal: {{ date('d F Y', strtotime($asOfDate)) }}</div>
            @if($report['store'])
                <div class="text-[11px] font-bold text-blue-600">Cabang: {{ $report['store']->name }}</div>
            @endif
        </div>

        <!-- DUA KOLOM: AKTIVA VS PASIVA -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- SISI KIRI: AKTIVA / ASET -->
            <div class="space-y-4">
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-xs border-b-2 border-slate-900 dark:border-slate-700 pb-1.5 flex justify-between">
                    <span>ASET (AKTIVA)</span>
                </div>

                <!-- Aset Lancar -->
                <div>
                    <div class="font-bold text-slate-600 dark:text-slate-400 uppercase text-[10px] mb-1">A. Aset Lancar</div>
                    <table class="w-full text-left font-mono">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($report['current_assets'] as $ca)
                                <tr>
                                    <td class="py-1 font-sans text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-400 font-mono text-[10px] mr-1.5">{{ $ca['code'] }}</span>
                                        {{ $ca['name'] }}
                                    </td>
                                    <td class="py-1 text-right font-bold text-slate-900 dark:text-white">
                                        Rp {{ number_format($ca['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Aset Tetap -->
                @if(!empty($report['fixed_assets']))
                    <div>
                        <div class="font-bold text-slate-600 dark:text-slate-400 uppercase text-[10px] mb-1">B. Aset Tetap &amp; Akumulasi</div>
                        <table class="w-full text-left font-mono">
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                @foreach($report['fixed_assets'] as $fa)
                                    <tr>
                                        <td class="py-1 font-sans text-slate-700 dark:text-slate-300">
                                            <span class="text-slate-400 font-mono text-[10px] mr-1.5">{{ $fa['code'] }}</span>
                                            {{ $fa['name'] }}
                                        </td>
                                        <td class="py-1 text-right font-bold text-slate-900 dark:text-white">
                                            Rp {{ number_format($fa['amount'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Total Aset -->
                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl flex items-center justify-between font-black text-xs text-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-900">
                    <span class="uppercase">TOTAL ASET (AKTIVA)</span>
                    <span class="font-mono text-sm">
                        Rp {{ number_format($report['total_assets'], 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- SISI KANAN: KEWAJIBAN & EKUITAS -->
            <div class="space-y-4">
                <div class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-xs border-b-2 border-slate-900 dark:border-slate-700 pb-1.5 flex justify-between">
                    <span>KEWAJIBAN &amp; EKUITAS (PASIVA)</span>
                </div>

                <!-- Liabilitas Jangka Pendek -->
                <div>
                    <div class="font-bold text-slate-600 dark:text-slate-400 uppercase text-[10px] mb-1">A. Liabilitas / Hutang</div>
                    <table class="w-full text-left font-mono">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($report['liabilities'] as $li)
                                <tr>
                                    <td class="py-1 font-sans text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-400 font-mono text-[10px] mr-1.5">{{ $li['code'] }}</span>
                                        {{ $li['name'] }}
                                    </td>
                                    <td class="py-1 text-right font-bold text-slate-900 dark:text-white">
                                        Rp {{ number_format($li['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-bold text-slate-800 dark:text-slate-200">
                                <td class="py-1.5 font-sans">Total Liabilitas</td>
                                <td class="py-1.5 text-right">Rp {{ number_format($report['total_liabilities'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Ekuitas / Modal -->
                <div>
                    <div class="font-bold text-slate-600 dark:text-slate-400 uppercase text-[10px] mb-1">B. Ekuitas / Modal Pemilik</div>
                    <table class="w-full text-left font-mono">
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($report['equity'] as $eq)
                                <tr>
                                    <td class="py-1 font-sans text-slate-700 dark:text-slate-300">
                                        <span class="text-slate-400 font-mono text-[10px] mr-1.5">{{ $eq['code'] }}</span>
                                        {{ $eq['name'] }}
                                    </td>
                                    <td class="py-1 text-right font-bold text-slate-900 dark:text-white">
                                        Rp {{ number_format($eq['amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="py-1 font-sans text-indigo-700 dark:text-indigo-400 font-bold">
                                    Laba Bersih Tahun Berjalan
                                </td>
                                <td class="py-1 text-right font-bold text-indigo-700 dark:text-indigo-400">
                                    Rp {{ number_format($report['current_earnings'], 0, ',', '.') }}
                                </td>
                            </tr>
                            <tr class="font-bold text-slate-800 dark:text-slate-200">
                                <td class="py-1.5 font-sans">Total Ekuitas</td>
                                <td class="py-1.5 text-right">Rp {{ number_format($report['total_equity'], 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Total Liabilitas & Ekuitas -->
                <div class="p-3 bg-purple-50 dark:bg-purple-950/40 rounded-xl flex items-center justify-between font-black text-xs text-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-900">
                    <span class="uppercase">TOTAL LIABILITAS &amp; EKUITAS</span>
                    <span class="font-mono text-sm">
                        Rp {{ number_format($report['total_liabilities_and_equity'], 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
