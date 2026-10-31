@extends('layouts.app')

@section('title', 'Daftar Batch Lot & Expired Date (FEFO)')
@section('tab_title', 'Batch & Expired')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Logistik & Gudang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Pelacakan Batch & FEFO</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🏷️ Pelacakan Nomor Batch &amp; Tanggal Kedaluwarsa</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.batches.near-expired') }}"
               class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs rounded-xl shadow-md shadow-amber-500/25 flex items-center gap-1.5 transition-all cursor-pointer">
                <span>⚠️ Peringatan Hampir Expired</span>
            </a>
            <a href="{{ route('warehouse.batches.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-500/25 flex items-center gap-1.5 transition-all cursor-pointer">
                <span>➕ Tambah Batch Baru</span>
            </a>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('warehouse.batches.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari No. Batch, nama produk, atau SKU..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="w-40">
            <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Status</option>
                <option value="ACTIVE" {{ request('status') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE (Aktif)</option>
                <option value="EXPIRED" {{ request('status') === 'EXPIRED' ? 'selected' : '' }}>EXPIRED (Kedaluwarsa)</option>
                <option value="DEPLETED" {{ request('status') === 'DEPLETED' ? 'selected' : '' }}>DEPLETED (Habis)</option>
            </select>
        </div>

        <div class="w-48">
            <select name="store_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Toko / Gudang</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ request('store_id') === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
        @if(request()->anyFilled(['search', 'status', 'store_id']))
            <a href="{{ route('warehouse.batches.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 font-semibold text-xs">
                Reset
            </a>
        @endif
    </form>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            ✕ {{ $errors->first() }}
        </div>
    @endif

    <!-- TABLE BATCH -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold">
                    <tr>
                        <th class="p-3">No. Batch / Lot</th>
                        <th class="p-3">Produk</th>
                        <th class="p-3">Toko / Gudang</th>
                        <th class="p-3">Tanggal Expired</th>
                        <th class="p-3 text-center">Sisa Hari</th>
                        <th class="p-3 text-right">Stok Awal</th>
                        <th class="p-3 text-right">Sisa Stok</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($batches as $b)
                        @php
                            $daysRemaining = $b->days_remaining;
                        @endphp
                        <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $b->batch_number }}
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $b->product->name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $b->product->sku ?? '' }}</div>
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                {{ $b->store->name ?? '-' }}
                            </td>
                            <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ \Carbon\Carbon::parse($b->expired_date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3 text-center font-bold">
                                @if($daysRemaining < 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                        Expired ({{ abs($daysRemaining) }} hari lalu)
                                    </span>
                                @elseif($daysRemaining <= 30)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                        ⚡ H-{{ $daysRemaining }} hari
                                    </span>
                                @else
                                    <span class="text-slate-600 dark:text-slate-400">
                                        {{ $daysRemaining }} hari
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono text-slate-500">
                                {{ number_format($b->initial_quantity, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-slate-900 dark:text-white">
                                {{ number_format($b->current_quantity, 2) }}
                            </td>
                            <td class="p-3 text-center">
                                @if($b->status === 'ACTIVE')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        AKTIF
                                    </span>
                                @elseif($b->status === 'EXPIRED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                        KEDALUWARSA
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">
                                        HABIS
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                Belum ada data nomor batch yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($batches->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $batches->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
