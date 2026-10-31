@extends('layouts.app')

@section('title', 'Tagihan & Rekonsiliasi Konsinyasi')
@section('tab_title', 'Tagihan Konsinyasi')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="{ showPayModal: false, selectedSettlement: null }">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Logistik &amp; Gudang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Konsinyasi</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📑 Rekonsiliasi &amp; Tagihan Konsinyasi Terjual</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.consignments.receipts.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Penerimaan Barang Titipan
            </a>
            <a href="{{ route('warehouse.consignments.settlements.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-500/25 flex items-center gap-1.5 transition-all cursor-pointer">
                <span>➕ Buat Tagihan Penjualan</span>
            </a>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('warehouse.consignments.settlements.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="w-56">
            <select name="supplier_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Supplier</option>
                @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ request('supplier_id') === $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-40">
            <select name="payment_status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Status Bayar</option>
                <option value="UNPAID" {{ request('payment_status') === 'UNPAID' ? 'selected' : '' }}>UNPAID (Belum Lunas)</option>
                <option value="PAID" {{ request('payment_status') === 'PAID' ? 'selected' : '' }}>PAID (Lunas)</option>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
        @if(request()->anyFilled(['supplier_id', 'payment_status']))
            <a href="{{ route('warehouse.consignments.settlements.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 font-semibold text-xs">
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

    <!-- TABEL SETTLEMENT KONSINYASI -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold">
                    <tr>
                        <th class="p-3">No. Tagihan</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Supplier Konsinyasi</th>
                        <th class="p-3">Toko</th>
                        <th class="p-3 text-right">Qty Terjual</th>
                        <th class="p-3 text-right">Total Tagihan (HPP)</th>
                        <th class="p-3 text-center">Status Bayar</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($settlements as $s)
                        <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ $s->settlement_number }}
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($s->settlement_date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $s->supplier->name ?? '-' }}
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                {{ $s->store->name ?? '-' }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-slate-800 dark:text-slate-200">
                                {{ number_format($s->total_sold_quantity, 2) }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-slate-900 dark:text-white text-sm">
                                Rp {{ number_format($s->total_settlement_amount, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center">
                                @if($s->payment_status === 'PAID')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        LUNAS ({{ $s->payment_method }})
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                        BELUM DIBAYAR
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if($s->payment_status === 'UNPAID')
                                    <button type="button"
                                            @click="selectedSettlement = {{ json_encode($s) }}; showPayModal = true"
                                            class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs cursor-pointer shadow-xs">
                                        Bayar Tagihan
                                    </button>
                                @else
                                    <span class="text-slate-400 text-[11px] font-semibold">Terselesaikan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                Belum ada tagihan rekonsiliasi konsinyasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($settlements->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $settlements->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL PELUNASAN PEMBAYARAN KONSINYASI -->
    <div x-show="showPayModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xs"
         style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-black text-slate-900 dark:text-white">💰 Pelunasan Tagihan Konsinyasi</h3>
                <button type="button" @click="showPayModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
            </div>

            <template x-if="selectedSettlement">
                <form :action="`/warehouse/consignments/settlements/${selectedSettlement.id}/pay`" method="POST" class="space-y-3 text-xs">
                    @csrf

                    <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl space-y-1">
                        <div class="text-[11px] text-slate-400 font-bold">Nomor Tagihan:</div>
                        <div class="font-mono font-black text-slate-800 dark:text-slate-200" x-text="selectedSettlement.settlement_number"></div>
                        <div class="text-[11px] text-slate-400 font-bold pt-1">Total yang Harus Dibayar:</div>
                        <div class="text-base font-black font-mono text-emerald-600 dark:text-emerald-400"
                             x-text="'Rp ' + Number(selectedSettlement.total_settlement_amount).toLocaleString('id-ID')"></div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Pembayaran *</label>
                        <select name="payment_method" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold">
                            <option value="CASH">Kas Tunai Toko (1001-001)</option>
                            <option value="TRANSFER">Transfer Bank (1002-001)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showPayModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-md shadow-emerald-500/25 cursor-pointer">
                            Bayar Sekarang
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
