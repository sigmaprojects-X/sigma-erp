@extends('layouts.app')

@section('title', 'Buat Tagihan Konsinyasi Terjual')
@section('tab_title', 'Buat Tagihan')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full" x-data="consignmentSettlementForm()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.consignments.settlements.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Tagihan Konsinyasi</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Rekonsiliasi Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📑 Form Rekonsiliasi &amp; Tagihan Konsinyasi Terjual</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.consignments.settlements.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            ✕ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('warehouse.consignments.settlements.store') }}" class="space-y-4 text-xs">
        @csrf

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-4">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">1. Parameter Rekonsiliasi Supplier</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Cabang Penjualan *</label>
                    <select name="store_id"
                            x-model="storeId"
                            @change="fetchUnsettledItems()"
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id', Auth::user()->store_id) === $st->id ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Supplier Konsinyasi *</label>
                    <select name="supplier_id"
                            x-model="supplierId"
                            @change="fetchUnsettledItems()"
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') === $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Periode Penjualan Awal (Opsional)</label>
                    <input type="date" name="period_start" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Periode Penjualan Akhir (Opsional)</label>
                    <input type="date" name="period_end" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tagihan</label>
                <input type="text"
                       name="notes"
                       value="{{ old('notes') }}"
                       placeholder="Contoh: Tagihan penjualan roti titipan minggu ke-3..."
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none">
            </div>
        </div>

        <!-- DAFTAR ITEM KONSINYASI YANG LAKU TERJUAL -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">2. Daftar Barang yang Laku Terjual di Kasir POS</h2>
                    <p class="text-[11px] text-slate-500">Centang barang yang akan diakui menjadi tagihan hutang dagang resmi ke supplier.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="selectAll()"
                            class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-[11px] rounded-lg">
                        Pilih Semua
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-extrabold uppercase tracking-wider">
                        <tr>
                            <th class="p-2 w-8 text-center">Pilih</th>
                            <th class="p-2">No. Penerimaan</th>
                            <th class="p-2">Produk Konsinyasi</th>
                            <th class="p-2 text-right">Laku Terjual</th>
                            <th class="p-2 text-right">Qty Ditagihkan</th>
                            <th class="p-2 text-right">Harga Satuan</th>
                            <th class="p-2 text-right">Subtotal Tagihan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        <template x-for="(item, idx) in availableItems" :key="item.id">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-2 text-center">
                                    <input type="checkbox"
                                           x-model="item.selected"
                                           class="h-4 w-4 text-blue-600 rounded border-slate-300">
                                </td>
                                <td class="p-2 font-mono text-slate-500" x-text="item.receipt_number"></td>
                                <td class="p-2">
                                    <div class="font-bold text-slate-900 dark:text-white" x-text="item.product_name"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="item.sku"></div>
                                </td>
                                <td class="p-2 text-right font-mono font-bold text-slate-700 dark:text-slate-300"
                                    x-text="item.unsettled_quantity + ' ' + item.unit_name"></td>
                                <td class="p-2 text-right">
                                    <input type="hidden" :name="`selections[${idx}][consignment_receipt_item_id]`" :value="item.selected ? item.id : ''">
                                    <input type="number"
                                           step="0.0001"
                                           :max="item.unsettled_quantity"
                                           min="0.0001"
                                           :name="`selections[${idx}][quantity]`"
                                           x-model.number="item.qtyToSettle"
                                           :disabled="!item.selected"
                                           class="w-24 px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-right font-mono font-bold">
                                </td>
                                <td class="p-2 text-right font-mono text-slate-600 dark:text-slate-400"
                                    x-text="'Rp ' + Number(item.unit_cost).toLocaleString('id-ID')"></td>
                                <td class="p-2 text-right font-mono font-black text-slate-900 dark:text-white"
                                    x-text="'Rp ' + Number(item.qtyToSettle * item.unit_cost).toLocaleString('id-ID')"></td>
                            </tr>
                        </template>

                        <tr x-show="availableItems.length === 0">
                            <td colspan="7" class="p-6 text-center text-slate-400">
                                Tidak ada barang konsinyasi yang laku terjual dan belum ditagihkan untuk supplier ini.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t border-slate-200 dark:border-slate-800 font-black">
                        <tr>
                            <td colspan="6" class="p-3 text-right uppercase text-slate-500">Total Tagihan Hutang Konsinyasi:</td>
                            <td class="p-3 text-right font-mono text-base text-blue-600 dark:text-blue-400"
                                x-text="'Rp ' + Number(calculateTotal()).toLocaleString('id-ID')"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('warehouse.consignments.settlements.index') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-black rounded-xl shadow-md shadow-blue-500/25 cursor-pointer">
                📑 Terbitkan Tagihan Konsinyasi
            </button>
        </div>
    </form>

</div>

<script>
function consignmentSettlementForm() {
    return {
        storeId: '{{ old('store_id', Auth::user()->store_id) }}',
        supplierId: '{{ old('supplier_id') }}',
        availableItems: [],

        init() {
            if (this.supplierId) {
                this.fetchUnsettledItems();
            }
        },

        async fetchUnsettledItems() {
            if (!this.supplierId) {
                this.availableItems = [];
                return;
            }

            try {
                const res = await fetch(`/warehouse/consignments/unsettled-items/${this.supplierId}?store_id=${this.storeId}`);
                const data = await res.json();
                if (data.success && data.items) {
                    this.availableItems = data.items.map(it => {
                        it.selected = true;
                        it.qtyToSettle = it.unsettled_quantity;
                        return it;
                    });
                } else {
                    this.availableItems = [];
                }
            } catch (err) {
                console.error(err);
                this.availableItems = [];
            }
        },

        selectAll() {
            this.availableItems.forEach(it => it.selected = true);
        },

        calculateTotal() {
            return this.availableItems
                .filter(it => it.selected)
                .reduce((sum, it) => sum + (it.qtyToSettle * it.unit_cost), 0);
        }
    };
}
</script>
@endsection
