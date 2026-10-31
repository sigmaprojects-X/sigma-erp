@extends('layouts.app')

@section('title', 'Penerimaan Barang Konsinyasi (Titipan Supplier)')
@section('tab_title', 'Konsinyasi Masuk')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Logistik &amp; Gudang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Konsinyasi Masuk</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📥 Penerimaan Barang Titipan Konsinyasi</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.consignments.settlements.index') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <span>📑 Tagihan &amp; Rekonsiliasi</span>
            </a>
            <a href="{{ route('warehouse.consignments.receipts.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-teal-500/25 flex items-center gap-1.5 transition-all cursor-pointer">
                <span>➕ Terima Konsinyasi Baru</span>
            </a>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('warehouse.consignments.receipts.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="w-56">
            <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Supplier Konsinyasi</option>
                @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ request('supplier_id') === $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-48">
            <select name="store_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Toko / Cabang</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ request('store_id') === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
        @if(request()->anyFilled(['supplier_id', 'store_id']))
            <a href="{{ route('warehouse.consignments.receipts.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 font-semibold text-xs">
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

    <!-- TABEL PENERIMAAN KONSINYASI -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold">
                    <tr>
                        <th class="p-3">No. Penerimaan</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Supplier Pemilik</th>
                        <th class="p-3">Toko Penerima</th>
                        <th class="p-3 text-right">Total Item</th>
                        <th class="p-3 text-right">Estimasi Nilai Titipan</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($receipts as $r)
                        <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-teal-700 dark:text-teal-400">
                                {{ $r->receipt_number }}
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($r->receipt_date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $r->supplier->name ?? '-' }}
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                {{ $r->store->name ?? '-' }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-slate-800 dark:text-slate-200">
                                {{ number_format($r->total_items) }} Item
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                @can('view-hpp')
                                    Rp {{ number_format($r->total_estimated_amount, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic">***</span>
                                @endcan
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-teal-100 text-teal-800">
                                    {{ $r->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                Belum ada riwayat penerimaan barang titipan konsinyasi.
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
