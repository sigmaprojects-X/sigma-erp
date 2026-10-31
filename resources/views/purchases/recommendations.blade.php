@extends('layouts.app')

@section('title', 'Smart Replenishment & Rekomendasi Restock')
@section('tab_title', 'Rekomendasi Restock')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="smartReplenishmentApp()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Smart Replenishment</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⚡ Smart Replenishment &amp; Rekomendasi Restock</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Otomasi kalkulasi restock barang berdasarkan batas minimum stok (Safety Stock &amp; ROP) di {{ $store->name }}.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.orders.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>📋 Daftar PO</span>
            </a>
            <a href="{{ route('purchases.debt-ledger') }}"
               class="px-3.5 py-2 bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30 hover:bg-amber-500/20 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>💰 Buku Hutang Supplier</span>
            </a>
        </div>
    </div>

    <!-- FILTER SUPPLIER & STATS SUMMARY -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Item Perlu Restock</div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                {{ $recommendations->count() }} <span class="text-xs font-semibold text-slate-400">Produk</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Estimasi Total Modal Restock</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($recommendations->sum('estimated_total_cost'), 0, ',', '.') }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs md:col-span-2 flex items-center justify-between">
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Aksi Cepat Smart PO</div>
                <div class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                    Konversi rekomendasi restock terpilih menjadi Draft Purchase Order dalam 1 klik.
                </div>
            </div>
            <button type="button"
                    @click="convertSelectedToPo()"
                    :disabled="selectedItems.length === 0"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-1.5 shadow-md transition-all cursor-pointer"
                    :class="selectedItems.length > 0 ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-blue-500/25 hover:scale-102' : 'bg-slate-200 text-slate-400 dark:bg-slate-800 cursor-not-allowed'">
                <span>➕ Buat PO Terpilih (<span x-text="selectedItems.length"></span>)</span>
            </button>
        </div>
    </div>

    <!-- TABEL REKOMENDASI RESTOCK -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40">
            <div class="font-extrabold text-sm text-slate-800 dark:text-white flex items-center gap-2">
                <span>📦 Rekomendasi Restock Gudang &amp; Toko</span>
            </div>
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 cursor-pointer">
                <input type="checkbox" @change="toggleSelectAll($event)" class="rounded text-blue-600 w-4 h-4">
                <span>Pilih Semua Item</span>
            </label>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-8 text-center">Pilih</th>
                        <th class="p-3">SKU &amp; Nama Produk</th>
                        <th class="p-3 text-center">Stok Fisik</th>
                        <th class="p-3 text-center">Stok Tersedia</th>
                        <th class="p-3 text-center">Min. Alert</th>
                        <th class="p-3 text-center">Defisit</th>
                        <th class="p-3 text-right">Rekomendasi Pesan</th>
                        <th class="p-3 text-right">Estimasi Modal</th>
                        <th class="p-3">Pemasok Utama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($recommendations as $rec)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 text-center">
                                <input type="checkbox"
                                       value="{{ $rec['product_id'] }}"
                                       data-unit-id="{{ $rec['recommended_unit_id'] }}"
                                       data-qty="{{ $rec['recommended_quantity'] }}"
                                       data-cost="{{ $rec['estimated_unit_cost'] }}"
                                       data-supplier="{{ $rec['primary_supplier_id'] }}"
                                       x-model="selectedItems"
                                       class="rounded text-blue-600 w-4 h-4 cursor-pointer">
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $rec['product_name'] }}</div>
                                <div class="text-[10.5px] font-mono text-slate-400">{{ $rec['sku'] }} • {{ $rec['category'] }}</div>
                            </td>
                            <td class="p-3 text-center font-bold">
                                {{ number_format($rec['current_stock'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center font-black text-rose-600 dark:text-rose-400">
                                {{ number_format($rec['available_stock'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center font-semibold text-slate-500">
                                {{ number_format($rec['min_stock_alert'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center font-bold text-amber-600">
                                -{{ number_format($rec['deficit_base_qty'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-black text-blue-600 dark:text-blue-400">
                                {{ number_format($rec['recommended_quantity'], 0, ',', '.') }} {{ $rec['recommended_unit_code'] }}
                            </td>
                            <td class="p-3 text-right font-bold">
                                Rp {{ number_format($rec['estimated_total_cost'], 0, ',', '.') }}
                            </td>
                            <td class="p-3">
                                <span class="px-2 py-0.5 rounded-lg text-[10.5px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $rec['primary_supplier_name'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-1">🎉</div>
                                <div class="font-bold text-slate-600 dark:text-slate-300">Stok Barang Terjaga Sempurna!</div>
                                <div class="text-xs">Tidak ada produk yang berada di bawah ambang batas minimum alert saat ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function smartReplenishmentApp() {
        return {
            selectedItems: [],

            toggleSelectAll(event) {
                const checked = event.target.checked;
                const checkboxes = document.querySelectorAll('input[type="checkbox"][data-unit-id]');
                if (checked) {
                    this.selectedItems = Array.from(checkboxes).map(cb => cb.value);
                } else {
                    this.selectedItems = [];
                }
            },

            convertSelectedToPo() {
                if (this.selectedItems.length === 0) return;
                const firstSelected = document.querySelector('input[type="checkbox"][data-supplier]:checked');
                const supplierId = firstSelected ? firstSelected.dataset.supplier : '';
                const productIds = this.selectedItems.join(',');
                window.location.href = `{{ route('purchases.orders.create') }}?supplier_id=${supplierId}&product_ids=${productIds}`;
            }
        };
    }
</script>
@endpush
@endsection
