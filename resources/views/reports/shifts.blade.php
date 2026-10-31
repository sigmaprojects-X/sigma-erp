@extends('layouts.app')

@section('title', 'Laporan Penutupan Shift Kasir (Z-Report)')
@section('tab_title', 'Audit Shift Kasir')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- HEADER & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan &amp; Keuangan</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Shift Kasir</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📊 Laporan Penutupan Shift Kasir &amp; Z-Report</span>
            </h1>
            <p class="text-xs text-slate-500">
                Akses terbatas khusus Owner, Finance &amp; Store Manager untuk audit rekonsiliasi kas laci kasir (Blind Closing).
            </p>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-3 shadow-xs">
        <form method="GET" action="{{ route('reports.shifts') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Toko / Outlet:</label>
                <select name="store_id" class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800">
                    <option value="">Semua Outlet</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ request('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Tanggal Tutup:</label>
                <input type="date" name="date" value="{{ request('date') }}"
                       class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800">
            </div>
            <div class="sm:col-span-2 flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg cursor-pointer">
                    🔍 Filter Laporan
                </button>
                <a href="{{ route('reports.shifts') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-lg">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- TABEL SHIFT KASIR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">No. Shift</th>
                        <th class="py-3 px-4">Toko / Outlet</th>
                        <th class="py-3 px-4">Petugas Kasir</th>
                        <th class="py-3 px-4">Waktu Buka / Tutup</th>
                        <th class="py-3 px-4 text-right">Modal Awal</th>
                        <th class="py-3 px-4 text-right">Kas Sistem</th>
                        <th class="py-3 px-4 text-right">Kas Fisik</th>
                        <th class="py-3 px-4 text-center">Selisih (Discrepancy)</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($shifts as $s)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ $s->shift_number }}
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $s->store->name ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                {{ $s->cashier->name ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-[11px] text-slate-500">
                                <div>{{ $s->opened_at ? $s->opened_at->format('d/m/Y H:i') : '-' }}</div>
                                <div class="text-slate-400">s/d {{ $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold">
                                Rp {{ number_format((float) $s->starting_cash, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">
                                Rp {{ number_format((float) $s->expected_cash, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-black text-blue-700 dark:text-blue-400">
                                Rp {{ number_format((float) $s->actual_cash_counted, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @php $diff = (float) $s->cash_discrepancy; @endphp
                                @if(abs($diff) < 0.01)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        ✓ BALANCE (PAS)
                                    </span>
                                @elseif($diff > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        ▲ LEBIH Rp {{ number_format($diff, 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                        ▼ KURANG Rp {{ number_format(abs($diff), 0, ',', '.') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('reports.shifts.z-report', $s->id) }}"
                                   target="_blank"
                                   class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 font-extrabold text-[11px] rounded-lg border border-blue-200 dark:border-blue-800 shadow-2xs inline-flex items-center gap-1">
                                    <span>📄</span> Z-Report
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 font-semibold">
                                Belum ada riwayat penutupan shift kasir.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shifts->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
