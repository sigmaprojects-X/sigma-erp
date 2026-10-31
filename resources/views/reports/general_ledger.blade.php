@extends('layouts.app')

@section('title', 'Buku Besar Akun (General Ledger)')
@section('tab_title', 'Buku Besar')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan Keuangan &amp; Akuntansi</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Buku Besar Akun</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📖 Buku Besar Akun (General Ledger)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="window.print()"
                    class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak Buku Besar
            </button>
        </div>
    </div>

    <!-- FILTER AKUN & PERIODE -->
    <form method="GET" action="{{ route('reports.general-ledger') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-wrap items-center gap-3 text-xs print:hidden">
        <div class="flex-1 min-w-[240px]">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Pilih Akun Buku Besar</label>
            <select name="account_id" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
                @foreach($accounts as $acc)
                    <option value="{{ $acc->id }}" {{ $selectedAccount?->id === $acc->id ? 'selected' : '' }}>
                        {{ $acc->code }} - {{ $acc->name }} ({{ $acc->type }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="w-32">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}"
                   class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
        </div>

        <div class="w-32">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}"
                   class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
        </div>

        <div class="w-40">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Cabang Toko</label>
            <select name="store_id" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
                <option value="">Semua Cabang</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
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

    @if($report)
        <!-- SUMMARY BANNER AKUN -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs">
                <span class="text-slate-400 font-bold uppercase text-[10px]">Akun Terpilih</span>
                <div class="font-black text-sm text-slate-900 dark:text-white mt-1">{{ $report['account']->name }}</div>
                <div class="text-[11px] font-mono text-blue-600 dark:text-blue-400">{{ $report['account']->code }} ({{ $report['account']->type }})</div>
            </div>

            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs">
                <span class="text-slate-400 font-bold uppercase text-[10px]">Saldo Awal</span>
                <div class="font-black text-base text-slate-900 dark:text-white mt-1 font-mono">
                    Rp {{ number_format($report['starting_balance'], 0, ',', '.') }}
                </div>
                <div class="text-[10px] text-slate-400">Posisi Normal: {{ $report['account']->normal_balance }}</div>
            </div>

            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs">
                <span class="text-slate-400 font-bold uppercase text-[10px]">Total Mutasi Debet / Kredit</span>
                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1 font-mono">
                    Debet: <span class="font-black text-slate-900 dark:text-white">Rp {{ number_format($report['total_debit'], 0, ',', '.') }}</span>
                </div>
                <div class="text-xs font-bold text-slate-700 dark:text-slate-300 font-mono">
                    Kredit: <span class="font-black text-slate-900 dark:text-white">Rp {{ number_format($report['total_credit'], 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-3.5 shadow-xs">
                <span class="text-blue-600 dark:text-blue-400 font-bold uppercase text-[10px]">Saldo Akhir Berjalan</span>
                <div class="font-black text-lg text-blue-600 dark:text-blue-400 mt-1 font-mono">
                    Rp {{ number_format($report['ending_balance'], 0, ',', '.') }}
                </div>
                <div class="text-[10px] text-blue-700/80 dark:text-blue-300/80">Per {{ date('d/m/Y', strtotime($endDate)) }}</div>
            </div>
        </div>

        <!-- TABEL RINCIAN BUKU BESAR -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">Tanggal</th>
                            <th class="p-3">No. Jurnal</th>
                            <th class="p-3">No. Referensi</th>
                            <th class="p-3">Keterangan / Memo</th>
                            <th class="p-3 text-right">Debet (Rp)</th>
                            <th class="p-3 text-right">Kredit (Rp)</th>
                            <th class="p-3 text-right">Saldo Berjalan (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-[11px] text-slate-800 dark:text-slate-200">
                        <!-- Baris Saldo Awal -->
                        <tr class="bg-slate-50/50 dark:bg-slate-800/20 font-bold">
                            <td class="p-3 font-sans">{{ date('d/m/Y', strtotime($startDate)) }}</td>
                            <td class="p-3 font-sans text-slate-400">-</td>
                            <td class="p-3 font-sans text-slate-400">-</td>
                            <td class="p-3 font-sans text-slate-600 dark:text-slate-400 italic">SALDO AWAL PERIODE</td>
                            <td class="p-3 text-right">-</td>
                            <td class="p-3 text-right">-</td>
                            <td class="p-3 text-right font-black">Rp {{ number_format($report['starting_balance'], 0, ',', '.') }}</td>
                        </tr>

                        @forelse($report['entries'] as $ent)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3 font-sans">{{ $ent['date']->format('d/m/Y') }}</td>
                                <td class="p-3 font-bold text-blue-600 dark:text-blue-400">{{ $ent['entry_number'] }}</td>
                                <td class="p-3 font-sans">{{ $ent['reference_number'] ?: '-' }}</td>
                                <td class="p-3 font-sans text-slate-700 dark:text-slate-300">{{ $ent['memo'] }}</td>
                                <td class="p-3 text-right font-bold text-slate-900 dark:text-white">
                                    {{ $ent['debit'] > 0 ? number_format($ent['debit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="p-3 text-right font-bold text-slate-900 dark:text-white">
                                    {{ $ent['credit'] > 0 ? number_format($ent['credit'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                    Rp {{ number_format($ent['balance'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-slate-400 font-sans text-xs">
                                    Tidak ada mutasi jurnal untuk akun ini pada periode yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-100 dark:bg-slate-800/80 font-mono font-black text-xs text-slate-900 dark:text-white border-t-2 border-slate-300 dark:border-slate-700">
                        <tr>
                            <td colspan="4" class="p-3 text-right font-sans uppercase">TOTAL MUTASI &amp; SALDO AKHIR</td>
                            <td class="p-3 text-right text-indigo-700 dark:text-indigo-400">
                                Rp {{ number_format($report['total_debit'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right text-indigo-700 dark:text-indigo-400">
                                Rp {{ number_format($report['total_credit'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right text-blue-700 dark:text-blue-400">
                                Rp {{ number_format($report['ending_balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
