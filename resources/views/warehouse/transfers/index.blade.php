@extends('layouts.app')

@section('title', 'Transfer Antar-Cabang')
@section('tab_title', 'Transfer Cabang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Logistik & Gudang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Transfer Antar Cabang</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🚚 Mutasi & Transfer Antar-Cabang</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.transfers.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-blue-500/25 cursor-pointer">
                <span>➕ Buat Pesanan / Transfer Baru</span>
            </a>
        </div>
    </div>

    <!-- QUICK TAB STATUS -->
    <div class="flex items-center gap-2 text-xs overflow-x-auto pb-1">
        <a href="{{ route('warehouse.transfers.index') }}"
           class="px-3.5 py-1.5 rounded-xl font-bold transition-all {{ !request('status') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
            Semua Transfer
        </a>
        <a href="{{ route('warehouse.transfers.index', ['status' => 'DRAFT']) }}"
           class="px-3.5 py-1.5 rounded-xl font-bold transition-all {{ request('status') === 'DRAFT' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
            📋 Pesanan Transfer (Draft)
        </a>
        <a href="{{ route('warehouse.transfers.index', ['status' => 'IN_TRANSIT']) }}"
           class="px-3.5 py-1.5 rounded-xl font-bold transition-all {{ request('status') === 'IN_TRANSIT' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
            🚚 Dalam Perjalanan
        </a>
        <a href="{{ route('warehouse.transfers.index', ['status' => 'RECEIVED']) }}"
           class="px-3.5 py-1.5 rounded-xl font-bold transition-all {{ request('status') === 'RECEIVED' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50' }}">
            ✓ Selesai Diterima
        </a>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('warehouse.transfers.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Cari No. Transfer, toko, driver, atau plat..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="w-44">
            <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Status</option>
                <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                <option value="IN_TRANSIT" {{ request('status') === 'IN_TRANSIT' ? 'selected' : '' }}>IN_TRANSIT (Dalam Perjalanan)</option>
                <option value="RECEIVED" {{ request('status') === 'RECEIVED' ? 'selected' : '' }}>RECEIVED (Telah Diterima)</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
    </form>

    <!-- TABEL TRANSFER -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">No. Transfer</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Dari (Asal)</th>
                        <th class="p-3">Ke (Tujuan)</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">Driver / Armada</th>
                        <th class="p-3 text-right">Total Qty Dikirim</th>
                        <th class="p-3 text-right">Total Qty Diterima</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($transfers as $tr)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('warehouse.transfers.show', $tr->id) }}" class="hover:underline">
                                    {{ $tr->transfer_number }}
                                </a>
                            </td>
                            <td class="p-3">
                                {{ $tr->transfer_date->format('d/m/Y') }}
                            </td>
                            <td class="p-3 font-bold text-slate-700 dark:text-slate-300">
                                {{ $tr->sourceStore->name }}
                            </td>
                            <td class="p-3 font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $tr->destinationStore->name }}
                            </td>
                            <td class="p-3 text-center">
                                @if($tr->status === 'DRAFT')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">DRAFT</span>
                                @elseif($tr->status === 'IN_TRANSIT')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 animate-pulse">🚚 IN_TRANSIT</span>
                                @elseif($tr->status === 'RECEIVED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✅ RECEIVED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ $tr->status }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-center text-slate-600 dark:text-slate-400">
                                @if($tr->driver_name)
                                    <div>{{ $tr->driver_name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $tr->vehicle_number }}</div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-bold">
                                {{ number_format($tr->total_sent_quantity, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold">
                                {{ number_format($tr->total_received_quantity, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                <a href="{{ route('warehouse.transfers.show', $tr->id) }}"
                                   class="px-2.5 py-1 bg-slate-100 hover:bg-blue-600 hover:text-white dark:bg-slate-800 dark:hover:bg-blue-600 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                Belum ada data transfer antar-cabang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transfers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
