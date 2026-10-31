@extends('layouts.app')

@section('title', 'Daftar Purchase Order (PO)')
@section('tab_title', 'Purchase Orders')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Purchase Orders (PO)</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📑 Daftar Purchase Order (PO)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Monitoring status pesanan pembelian ke pemasok, persetujuan wewenang, dan integrasi pasokan restok.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.recommendations') }}"
               class="px-3.5 py-2 bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/20 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>⚡ Smart Restock</span>
            </a>

            @if(auth()->check() && in_array(auth()->user()->role, ['owner', 'manager', 'purchasing']))
                <a href="{{ route('purchases.orders.create') }}"
                   class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-blue-500/25 cursor-pointer">
                    <span>➕ Buat PO Baru</span>
                </a>
            @endif
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <form method="GET" action="{{ route('purchases.orders.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[220px]">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Cari Nomor PO atau Nama Pemasok..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
        </div>

        <div class="w-44">
            <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs dark:text-slate-200">
                <option value="">Semua Status PO</option>
                <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>DRAFT</option>
                <option value="ORDERED" {{ request('status') === 'ORDERED' ? 'selected' : '' }}>ORDERED</option>
                <option value="PARTIALLY_RECEIVED" {{ request('status') === 'PARTIALLY_RECEIVED' ? 'selected' : '' }}>PARTIALLY_RECEIVED</option>
                <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
            </select>
        </div>

        <div class="w-48">
            <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs dark:text-slate-200">
                <option value="">Semua Supplier</option>
                @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ request('supplier_id') === $sup->id ? 'selected' : '' }}>
                        {{ $sup->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer">
            🔍 Filter
        </button>

        @if(request()->hasAny(['q', 'status', 'supplier_id']))
            <a href="{{ route('purchases.orders.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-xl transition-colors">
                Reset
            </a>
        @endif
    </form>

    <!-- TABEL PURCHASE ORDERS -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800 tracking-wider">
                    <tr>
                        <th class="p-3">Nomor PO</th>
                        <th class="p-3">Tanggal PO</th>
                        <th class="p-3">Pemasok</th>
                        <th class="p-3">Toko Tujuan</th>
                        <th class="p-3 text-center">Item</th>
                        <th class="p-3 text-right">Total Estimasi</th>
                        <th class="p-3 text-center">Status PO</th>
                        <th class="p-3 text-center">Status Persetujuan</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- NOMOR PO -->
                            <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('purchases.orders.show', $po->id) }}" class="hover:underline flex items-center gap-1.5">
                                    <span>📄</span>
                                    <span>{{ $po->po_number }}</span>
                                </a>
                            </td>

                            <!-- TANGGAL PO -->
                            <td class="p-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ $po->order_date ? $po->order_date->format('d/m/Y') : '-' }}
                            </td>

                            <!-- SUPPLIER -->
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $po->supplier?->name ?? 'Belum Ditentukan' }}
                            </td>

                            <!-- TOKO TUJUAN -->
                            <td class="p-3 text-slate-600 dark:text-slate-300 font-semibold">
                                <span class="inline-flex items-center gap-1">
                                    <span>🏪</span>
                                    <span>{{ $po->store?->name ?? 'Gudang Utama' }}</span>
                                </span>
                            </td>

                            <!-- JUMLAH ITEM -->
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-lg text-[10.5px] font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    {{ $po->items_count ?? $po->items->count() }} Item
                                </span>
                            </td>

                            <!-- TOTAL ESTIMASI NILAI -->
                            <td class="p-3 text-right font-black text-slate-900 dark:text-emerald-400 font-mono text-[12px]">
                                Rp {{ number_format($po->grand_total, 0, ',', '.') }}
                            </td>

                            <!-- STATUS PO -->
                            <td class="p-3 text-center whitespace-nowrap">
                                @if($po->status === 'DRAFT')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">DRAFT</span>
                                @elseif($po->status === 'ORDERED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-300 dark:border-blue-800">ORDERED</span>
                                @elseif($po->status === 'PARTIALLY_RECEIVED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">PARTIAL (SEBAGIAN)</span>
                                @elseif($po->status === 'COMPLETED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">COMPLETED</span>
                                @elseif($po->status === 'CANCELLED')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800">CANCELLED</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700">{{ $po->status }}</span>
                                @endif
                            </td>

                            <!-- STATUS PERSETUJUAN -->
                            <td class="p-3 text-center whitespace-nowrap text-[10.5px]">
                                @if($po->approved_by && $po->approver)
                                    <div class="inline-flex flex-col items-center">
                                        <span class="px-2 py-0.5 rounded-full font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300/40">
                                            ✓ Disetujui
                                        </span>
                                        <span class="text-[9.5px] text-slate-400 mt-0.5">
                                            {{ $po->approver->name }} ({{ $po->approved_at ? $po->approved_at->format('d/m/Y') : '' }})
                                        </span>
                                    </div>
                                @elseif($po->status === 'CANCELLED')
                                    <span class="px-2 py-0.5 rounded-full font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300/40">
                                        ✗ Dibatalkan
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300/40">
                                        ⏳ Menunggu Persetujuan
                                    </span>
                                @endif
                            </td>

                            <!-- TOMBOL DETAIL & CETAK -->
                            <td class="p-3 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('purchases.orders.print', $po->id) }}"
                                       target="_blank"
                                       title="Cetak Surat Pesanan (PO A4)"
                                       class="px-2 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl font-bold text-xs transition-colors inline-flex items-center gap-1 border border-slate-300 dark:border-slate-700">
                                        <span>🖨️</span>
                                    </a>
                                    <a href="{{ route('purchases.orders.show', $po->id) }}"
                                       class="px-3 py-1.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 rounded-xl font-bold text-xs transition-colors inline-flex items-center gap-1 border border-blue-200 dark:border-blue-800">
                                        <span>Detail</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="text-3xl">📭</span>
                                    <p class="font-bold text-sm text-slate-600 dark:text-slate-400">Tidak ada Purchase Order yang ditemukan.</p>
                                    <p class="text-xs text-slate-400">Gunakan filter status lain atau buat Purchase Order baru.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseOrders->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $purchaseOrders->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
