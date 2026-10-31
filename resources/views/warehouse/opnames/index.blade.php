@extends('layouts.app')

@section('title', 'Stock Opname (Audit Fisik)')
@section('tab_title', 'Stock Opname')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Logistik & Gudang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Blind Stock Opname</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 Audit Stok Fisik (Blind Stock Opname)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.opname-pwa.index') }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-700 font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>📱 PWA Mobile Opname</span>
            </a>
            @if(in_array(auth()->user()->role, ['owner', 'manager', 'store_head']))
                <a href="{{ route('warehouse.opnames.create') }}"
                   class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-blue-500/25 cursor-pointer">
                    <span>➕ Mulai Sesi Baru</span>
                </a>
            @endif
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('warehouse.opnames.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Cari No. Opname atau nama toko..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="w-44">
            <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Status</option>
                <option value="IN_PROGRESS" {{ request('status') === 'IN_PROGRESS' ? 'selected' : '' }}>IN_PROGRESS (Sedang Dihitung)</option>
                <option value="PENDING_APPROVAL" {{ request('status') === 'PENDING_APPROVAL' ? 'selected' : '' }}>PENDING_APPROVAL (Menunggu Manager)</option>
                <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>COMPLETED (Selesai Direkonsiliasi)</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
    </form>

    <!-- TABEL STOCK OPNAMES -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">No. Opname</th>
                        <th class="p-3">Tanggal Opname</th>
                        <th class="p-3">Toko / Lokasi</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-right">Total Qty Fisik</th>
                        @if(in_array(auth()->user()->role, ['owner', 'manager', 'store_head']))
                            <th class="p-3 text-right">Selisih Fisik</th>
                            <th class="p-3 text-right">Nilai Selisih (Rp)</th>
                        @endif
                        <th class="p-3">Inisiator</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($opnames as $op)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('warehouse.opnames.show', $op->id) }}" class="hover:underline">
                                    {{ $op->opname_number }}
                                </a>
                            </td>
                            <td class="p-3">
                                {{ $op->opname_date->format('d/m/Y') }}
                            </td>
                            <td class="p-3 font-bold text-slate-700 dark:text-slate-300">
                                {{ $op->store->name }}
                            </td>
                            <td class="p-3 text-center">
                                @if($op->status === 'IN_PROGRESS')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 animate-pulse">📝 IN_PROGRESS</span>
                                @elseif($op->status === 'PENDING_APPROVAL')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">⏳ PENDING APPROVAL</span>
                                @elseif($op->status === 'COMPLETED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✅ COMPLETED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ $op->status }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-bold">
                                {{ number_format($op->total_actual_quantity, 2) }}
                            </td>
                            @if(in_array(auth()->user()->role, ['owner', 'manager', 'store_head']))
                                <td class="p-3 text-right font-mono font-bold">
                                    @if($op->total_discrepancy_quantity > 0)
                                        <span class="text-emerald-600">+{{ number_format($op->total_discrepancy_quantity, 2) }}</span>
                                    @elseif($op->total_discrepancy_quantity < 0)
                                        <span class="text-rose-600">{{ number_format($op->total_discrepancy_quantity, 2) }}</span>
                                    @else
                                        <span class="text-slate-400">0.00</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono font-bold">
                                    @if($op->total_discrepancy_value > 0)
                                        <span class="text-emerald-600">+Rp {{ number_format($op->total_discrepancy_value, 0, ',', '.') }}</span>
                                    @elseif($op->total_discrepancy_value < 0)
                                        <span class="text-rose-600">-Rp {{ number_format(abs($op->total_discrepancy_value), 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-400">Rp 0</span>
                                    @endif
                                </td>
                            @endif
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                {{ $op->creator ? $op->creator->name : '-' }}
                            </td>
                            <td class="p-3 text-center">
                                <a href="{{ route('warehouse.opnames.show', $op->id) }}"
                                   class="px-2.5 py-1 bg-slate-100 hover:bg-blue-600 hover:text-white dark:bg-slate-800 dark:hover:bg-blue-600 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs transition-colors">
                                    {{ $op->status === 'IN_PROGRESS' ? 'Input Fisik' : 'Lihat' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ in_array(auth()->user()->role, ['owner', 'manager', 'store_head']) ? 9 : 7 }}" class="p-8 text-center text-slate-400">
                                Belum ada sesi stock opname tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($opnames->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $opnames->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
