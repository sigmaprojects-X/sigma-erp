@extends('layouts.app')

@section('title', 'Laporan Penerimaan Barang (LPB Gudang)')
@section('tab_title', 'LPB Gudang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Penerimaan Fisik Barang (LPB)</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📦 Laporan Penerimaan Barang (LPB)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pencatatan fisik barang masuk dari Surat Jalan / Delivery Order supplier, alokasi Landed Cost (ongkir), dan verifikasi Three-Way Matching.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.orders.index') }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>📑 Daftar PO Supplier</span>
            </a>
            <a href="{{ route('purchases.index') }}"
               class="px-3.5 py-2 bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/30 hover:bg-blue-500/20 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>🧾 Faktur Pembelian (AP)</span>
            </a>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <form method="GET" action="{{ route('purchases.receipts.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[220px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari No. LPB, Surat Jalan, Supplier, atau PO..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white">
        </div>

        <div class="w-48">
            <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs dark:text-slate-200">
                <option value="">Semua Status Matching</option>
                <option value="RECEIVED" {{ request('status') === 'RECEIVED' ? 'selected' : '' }}>⏳ Menunggu Faktur (GRNI)</option>
                <option value="MATCHED" {{ request('status') === 'MATCHED' ? 'selected' : '' }}>✅ Selesai Di-Match (Faktur AP)</option>
                <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>❌ Dibatalkan</option>
            </select>
        </div>

        <div class="w-56">
            <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs dark:text-slate-200">
                <option value="">Semua Pemasok / Supplier</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ request('supplier_id') === $supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl cursor-pointer">
            Filter
        </button>

        @if(request()->hasAny(['search', 'status', 'supplier_id']))
            <a href="{{ route('purchases.receipts.index') }}" class="text-xs text-rose-500 hover:underline font-semibold">
                Reset
            </a>
        @endif
    </form>

    <!-- TABEL LPB -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">No. LPB & Surat Jalan</th>
                        <th class="p-3">Tgl Penerimaan</th>
                        <th class="p-3">Pemasok & No. PO</th>
                        <th class="p-3">Penerima Gudang</th>
                        <th class="p-3 text-right">Landed Cost</th>
                        <th class="p-3 text-right">Nilai Barang</th>
                        <th class="p-3 text-center">Status Matching</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($receipts as $receipt)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3">
                                <div class="font-bold text-blue-600 dark:text-blue-400">
                                    <a href="{{ route('purchases.receipts.show', $receipt->id) }}" class="hover:underline">
                                        {{ $receipt->receipt_number }}
                                    </a>
                                </div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1 font-mono">
                                    <span>SJ:</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ $receipt->delivery_order_number }}</span>
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $receipt->receipt_date?->format('d/m/Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $receipt->receipt_date?->diffForHumans() }}</div>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $receipt->supplier->name }}</div>
                                @if($receipt->purchaseOrder)
                                    <a href="{{ route('purchases.orders.show', $receipt->purchaseOrder->id) }}" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-mono">
                                        PO: {{ $receipt->purchaseOrder->po_number }}
                                    </a>
                                @else
                                    <span class="text-[11px] text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                <div class="font-semibold">{{ $receipt->receiver?->name ?? 'Gudang' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $receipt->items->count() }} Item Diterima</div>
                            </td>
                            <td class="p-3 text-right">
                                @php
                                    $totalLanded = (float)$receipt->shipping_fee + (float)$receipt->handling_fee;
                                @endphp
                                @if($totalLanded > 0)
                                    <div class="font-bold text-amber-600">Rp {{ number_format($totalLanded, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-slate-400">Ongkir: {{ number_format($receipt->shipping_fee, 0, ',', '.') }}</div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($receipt->total_estimated_amount, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center">
                                @if($receipt->status === 'MATCHED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800">
                                        ✅ MATCHED
                                    </span>
                                @elseif($receipt->status === 'CANCELLED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300 dark:border-rose-800">
                                        ❌ BATAL
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                        ⏳ MENUNGGU FAKTUR
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('purchases.receipts.show', $receipt->id) }}"
                                       title="Lihat Detail LPB"
                                       class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg font-bold text-[11px] transition-colors">
                                        Detail
                                    </a>
                                    <a href="{{ route('purchases.receipts.print', $receipt->id) }}"
                                       target="_blank"
                                       title="Cetak Surat LPB Fisik"
                                       class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg font-bold text-[11px] transition-colors">
                                        🖨️
                                    </a>
                                    @if($receipt->status === 'RECEIVED' && in_array(auth()->user()?->role, ['owner', 'manager', 'finance']))
                                        <a href="{{ route('purchases.receipts.match', $receipt->id) }}"
                                           title="Proses Three-Way Matching Finance"
                                           class="px-2.5 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg font-extrabold text-[11px] shadow-xs transition-all">
                                            Match Faktur
                                        </a>
                                    @elseif($receipt->purchase_id)
                                        <a href="{{ route('purchases.show', $receipt->purchase_id) }}"
                                           title="Lihat Faktur Pembelian (AP)"
                                           class="px-2 py-1 text-blue-600 dark:text-blue-400 hover:underline font-bold text-[11px]">
                                            Faktur &rarr;
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-2">📦</div>
                                <div class="font-bold text-sm text-slate-600 dark:text-slate-400">Belum ada Laporan Penerimaan Barang (LPB)</div>
                                <div class="text-xs text-slate-400 mt-1">Penerimaan fisik barang dibuat berdasarkan Purchase Order (PO) yang telah disetujui.</div>
                                <div class="mt-4">
                                    <a href="{{ route('purchases.orders.index') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs inline-block">
                                        Buka Daftar Purchase Order
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
