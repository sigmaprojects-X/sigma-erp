@extends('layouts.app')

@section('title', 'Daftar Retur Penjualan')
@section('tab_title', 'Retur Penjualan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:underline">Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Retur Penjualan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🔄</span> Daftar Retur Penjualan (Credit Notes &amp; Refunds)
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.transactions') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                <span>&larr; Riwayat Faktur</span>
            </a>
            <a href="{{ route('sales.returns.create') }}"
               class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>➕ Input Retur Penjualan Baru</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif

    <!-- FILTER BAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <form method="GET" action="{{ route('sales.returns.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            <div class="sm:col-span-4">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Cari No. Retur / Faktur / Pelanggan:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik RET-..., POS-..., nama..."
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
            </div>

            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Metode Pengembalian (Refund):</label>
                <select name="refund_method" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 font-semibold">
                    <option value="">Semua Metode</option>
                    <option value="CASH" {{ request('refund_method') === 'CASH' ? 'selected' : '' }}>💵 Tunai (Kas Laci)</option>
                    <option value="TRANSFER" {{ request('refund_method') === 'TRANSFER' ? 'selected' : '' }}>🏦 Transfer Bank</option>
                    <option value="CREDIT_REDUCTION" {{ request('refund_method') === 'CREDIT_REDUCTION' ? 'selected' : '' }}>📝 Potong Piutang (Tempo)</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Mulai Tanggal:</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
            </div>

            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                       class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
            </div>

            <div class="sm:col-span-1 flex items-end">
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl cursor-pointer">
                    🔍 Filter
                </button>
            </div>
        </form>
    </div>

    <!-- TABEL DATA RETUR PENJUALAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">No. Retur</th>
                        <th class="py-3 px-4">Faktur Asal</th>
                        <th class="py-3 px-4">Pelanggan</th>
                        <th class="py-3 px-4">Alasan Retur</th>
                        <th class="py-3 px-4">Metode Refund</th>
                        <th class="py-3 px-4 text-right">Total Refund (Rp)</th>
                        <th class="py-3 px-4">Petugas / Approval</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($returns as $ret)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-mono font-black text-rose-600 dark:text-rose-400">{{ $ret->return_number }}</div>
                                <div class="text-[10px] text-slate-400">{{ $ret->return_date ? $ret->return_date->format('d/m/Y') : '-' }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ $ret->transaction->invoice_number ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $ret->transaction->customer->name ?? 'Pelanggan Umum' }}</div>
                            </td>
                            <td class="py-3 px-4 max-w-xs truncate" title="{{ $ret->reason }}">
                                {{ $ret->reason ?: 'Retur barang' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($ret->refund_method === 'CASH')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        💵 TUNAI
                                    </span>
                                @elseif($ret->refund_method === 'TRANSFER')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        🏦 TRANSFER
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        📝 POTONG PIUTANG
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-black text-rose-600 dark:text-rose-400">
                                Rp {{ number_format((float) $ret->total_refund_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-[11px] text-slate-500">
                                <div>Pemroses: <strong>{{ $ret->processor->name ?? '-' }}</strong></div>
                                <div>Approve: <strong>{{ $ret->approver->name ?? 'Auto/Owner' }}</strong></div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    {{ $ret->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('sales.returns.print', $ret->id) }}"
                                   target="_blank"
                                   class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-extrabold text-[11px] rounded-lg border border-rose-200 dark:border-rose-800 shadow-2xs inline-flex items-center gap-1">
                                    <span>📄</span> Nota Retur
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 font-semibold">
                                Belum ada riwayat retur penjualan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $returns->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
