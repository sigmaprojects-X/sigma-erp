@extends('layouts.app')

@section('title', 'Pesanan Penjualan (Sales Orders)')
@section('tab_title', 'Pesanan Penjualan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:underline">Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Sales Orders</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 Pesanan Penjualan (Sales Orders)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.orders.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>➕ Buat Pesanan Baru</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('sales.orders.index') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari No. Pesanan (SO-...) atau Nama Pelanggan..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="w-44">
            <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Status</option>
                <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>PENDING (Menunggu)</option>
                <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>APPROVED (Disetujui)</option>
                <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>COMPLETED (Selesai)</option>
                <option value="PROCESSED" {{ request('status') === 'PROCESSED' ? 'selected' : '' }}>PROCESSED (Faktur Terbit)</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED (Batal)</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
    </form>

    <!-- TABEL SALES ORDERS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">No. Pesanan</th>
                        <th class="py-3 px-4">Tanggal Order</th>
                        <th class="py-3 px-4">Toko / Cabang</th>
                        <th class="py-3 px-4">Pelanggan</th>
                        <th class="py-3 px-4 text-right">Total Nilai</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($orders as $ord)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4">
                                <a href="{{ route('sales.orders.show', $ord->id) }}" class="font-mono font-black text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $ord->order_number }}
                                </a>
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $ord->order_date ? $ord->order_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="py-3 px-4 font-semibold">
                                {{ $ord->store?->name }}
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                                {{ $ord->customer?->name ?? 'Pelanggan Umum' }}
                            </td>
                            <td class="py-3 px-4 text-right font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($ord->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($ord->status === 'PENDING')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        ● MENUNGGU
                                    </span>
                                @elseif($ord->status === 'APPROVED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                        ✓ DISETUJUI
                                    </span>
                                @elseif($ord->status === 'COMPLETED' || $ord->status === 'SELESAI')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        ✓ SELESAI
                                    </span>
                                @elseif($ord->status === 'PROCESSED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300">
                                        ★ FAKTUR TERBIT
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-500">
                                        ✕ BATAL
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('sales.orders.show', $ord->id) }}" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-semibold">
                                Belum ada pesanan penjualan. Klik "Buat Pesanan Baru" untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
