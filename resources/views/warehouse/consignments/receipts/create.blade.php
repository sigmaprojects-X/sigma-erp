@extends('layouts.app')

@section('title', 'Terima Barang Titipan Konsinyasi Baru')
@section('tab_title', 'Terima Konsinyasi')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full" x-data="consignmentReceiptForm()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.consignments.receipts.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Konsinyasi Masuk</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Terima Barang Titipan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📥 Form Penerimaan Titipan Konsinyasi</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.consignments.receipts.index') }}"
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

    <form method="POST" action="{{ route('warehouse.consignments.receipts.store') }}" class="space-y-4 text-xs">
        @csrf

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-4">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">1. Data Pengirim &amp; Toko Penerima</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Gudang Penerima *</label>
                    <select name="store_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-teal-500">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id', Auth::user()->store_id) === $st->id ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Supplier Pemilik Barang Titipan *</label>
                    <select name="supplier_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') === $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }} ({{ $sup->contact_person ?: 'Supplier' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Penerimaan</label>
                <input type="text"
                       name="notes"
                       value="{{ old('notes') }}"
                       placeholder="Contoh: Titipan roti harian batch pagi, aksesoris gelang..."
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none">
            </div>
        </div>

        <!-- DAFTAR ITEM TITIPAN -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">2. Rincian Barang Konsinyasi yang Diterima</h2>
                    <p class="text-[11px] text-slate-500">Stok fisik bertambah di toko, namun hutang dagang baru diakui saat barang laku terjual di POS.</p>
                </div>

                <button type="button"
                        @click="addItem()"
                        class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer transition-colors">
                    ➕ Tambah Baris Barang
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-extrabold uppercase tracking-wider">
                        <tr>
                            <th class="p-2 w-8">#</th>
                            <th class="p-2 min-w-[280px]">Produk</th>
                            <th class="p-2 w-40">Satuan</th>
                            <th class="p-2 w-32 text-right">Kuantitas Titip</th>
                            <th class="p-2 w-36 text-right">Harga Konsinyasi</th>
                            <th class="p-2 w-16 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-2 text-slate-400 font-bold" x-text="index + 1"></td>
                                <td class="p-2">
                                    <select :name="`items[${index}][product_id]`"
                                            x-model="item.product_id"
                                            @change="onProductChange(index)"
                                            required
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-teal-500">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-units="{{ json_encode($p->units) }}" data-cost="{{ $p->consignment_cost ?: $p->cost_price }}">
                                                {{ $p->sku }} - {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select :name="`items[${index}][product_unit_id]`"
                                            x-model="item.product_unit_id"
                                            required
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none">
                                        <template x-for="u in item.availableUnits" :key="u.id">
                                            <option :value="u.id" x-text="u.unit_name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="p-2">
                                    <input type="number"
                                           step="0.0001"
                                           min="0.0001"
                                           :name="`items[${index}][quantity]`"
                                           x-model.number="item.quantity"
                                           required
                                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-right focus:outline-none">
                                </td>
                                <td class="p-2">
                                    <input type="number"
                                           step="100"
                                           min="0"
                                           :name="`items[${index}][unit_cost]`"
                                           x-model.number="item.unit_cost"
                                           required
                                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-right text-teal-600 dark:text-teal-400 focus:outline-none">
                                </td>
                                <td class="p-2 text-center">
                                    <button type="button"
                                            @click="removeItem(index)"
                                            class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg cursor-pointer">
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('warehouse.consignments.receipts.index') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-black rounded-xl shadow-md shadow-teal-500/25 cursor-pointer">
                📥 Simpan Penerimaan Konsinyasi
            </button>
        </div>
    </form>

</div>

<script>
function consignmentReceiptForm() {
    return {
        items: [],

        init() {
            this.addItem();
        },

        addItem() {
            this.items.push({
                product_id: '',
                product_unit_id: '',
                quantity: 1,
                unit_cost: 0,
                availableUnits: []
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        onProductChange(index) {
            const row = this.items[index];
            const select = document.querySelector(`select[name="items[${index}][product_id]"]`);
            if (select) {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.units) {
                    row.availableUnits = JSON.parse(opt.dataset.units);
                    if (row.availableUnits.length > 0) {
                        row.product_unit_id = row.availableUnits[0].id;
                    }
                    row.unit_cost = parseFloat(opt.dataset.cost) || 0;
                }
            }
        }
    };
}
</script>
@endsection
