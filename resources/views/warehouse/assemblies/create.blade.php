@extends('layouts.app')

@section('title', 'Rakit Paket Bundling Baru')
@section('tab_title', 'Rakit Paket')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full" x-data="assemblyForm()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.assemblies.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Perakitan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Rakit Paket Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⚡ Eksekusi Perakitan Paket (Assembly)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.assemblies.index') }}"
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

    <form method="POST" action="{{ route('warehouse.assemblies.store') }}" class="space-y-4">
        @csrf

        <!-- INFORMASI DASAR PERAKITAN -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-4">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">1. Data Target Barang Jadi / Paket</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Gudang Eksekusi *</label>
                    <select name="store_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id', Auth::user()->store_id) === $st->id ? 'selected' : '' }}>
                                {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Produk Paket (Parent) *</label>
                    <select name="parent_product_id"
                            x-model="selectedParentId"
                            @change="onParentChange()"
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">-- Pilih Produk Paket / Jadi --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" data-units="{{ json_encode($p->units) }}">
                                {{ $p->sku }} - {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Satuan *</label>
                        <select name="parent_product_unit_id"
                                x-model="selectedParentUnitId"
                                required
                                class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <template x-for="u in availableParentUnits" :key="u.id">
                                <option :value="u.id" x-text="u.unit_name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Qty Rakit *</label>
                        <input type="number"
                               step="0.01"
                               min="0.01"
                               name="quantity"
                               x-model.number="parentQty"
                               required
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-black text-emerald-600 dark:text-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Perakitan</label>
                <input type="text"
                       name="notes"
                       value="{{ old('notes') }}"
                       placeholder="Contoh: Paket Hampers Idul Fitri batch 1, dsb..."
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none">
            </div>
        </div>

        <!-- DAFTAR KOMPONEN BAHAN BAKU -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">2. Komponen Bahan Baku yang Dipakai</h2>
                    <p class="text-[11px] text-slate-500">Stok komponen akan dipotong otomatis dari gudang toko terkait.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="loadBomRecipe()"
                            x-show="selectedParentId"
                            class="px-3 py-1.5 bg-blue-50 dark:bg-blue-950/50 hover:bg-blue-100 text-blue-700 dark:text-blue-300 font-bold text-xs rounded-xl border border-blue-200 dark:border-blue-800 cursor-pointer transition-colors">
                        🔄 Muat Resep BOM
                    </button>
                    <button type="button"
                            @click="addItem()"
                            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer transition-colors">
                        ➕ Tambah Komponen
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-extrabold uppercase tracking-wider">
                        <tr>
                            <th class="p-2 w-8">#</th>
                            <th class="p-2 min-w-[280px]">Produk Komponen</th>
                            <th class="p-2 w-48">Satuan Komponen</th>
                            <th class="p-2 w-36 text-right">Qty per 1 Paket</th>
                            <th class="p-2 w-16 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        <template x-for="(item, index) in items" :key="index">
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="p-2 text-slate-400 font-bold" x-text="index + 1"></td>
                                <td class="p-2">
                                    <select :name="`items[${index}][component_product_id]`"
                                            x-model="item.component_product_id"
                                            @change="onComponentChange(index)"
                                            required
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                        <option value="">-- Pilih Bahan Baku --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-units="{{ json_encode($p->units) }}">
                                                {{ $p->sku }} - {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="p-2">
                                    <select :name="`items[${index}][component_product_unit_id]`"
                                            x-model="item.component_product_unit_id"
                                            required
                                            class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-emerald-500">
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
                                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-right focus:outline-none focus:ring-1 focus:ring-emerald-500">
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

                        <tr x-show="items.length === 0">
                            <td colspan="5" class="p-6 text-center text-slate-400">
                                Belum ada komponen bahan baku. Klik <b>Tambah Komponen</b> atau <b>Muat Resep BOM</b>.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('warehouse.assemblies.index') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs rounded-xl shadow-md shadow-emerald-500/25 cursor-pointer">
                ⚡ Proses Perakitan Sekarang
            </button>
        </div>
    </form>

</div>

<script>
function assemblyForm() {
    return {
        selectedParentId: '{{ old('parent_product_id') }}',
        selectedParentUnitId: '{{ old('parent_product_unit_id') }}',
        parentQty: {{ old('quantity', 1) }},
        availableParentUnits: [],
        items: [],

        init() {
            if (this.selectedParentId) {
                this.onParentChange();
            } else {
                this.addItem();
            }
        },

        onParentChange() {
            const select = document.querySelector('select[name="parent_product_id"]');
            const selectedOpt = select.options[select.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.units) {
                this.availableParentUnits = JSON.parse(selectedOpt.dataset.units);
                if (this.availableParentUnits.length > 0) {
                    this.selectedParentUnitId = this.availableParentUnits[0].id;
                }
            } else {
                this.availableParentUnits = [];
                this.selectedParentUnitId = '';
            }
        },

        addItem(productId = '', unitId = '', qty = 1, availableUnits = []) {
            this.items.push({
                component_product_id: productId,
                component_product_unit_id: unitId,
                quantity: qty,
                availableUnits: availableUnits
            });
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        onComponentChange(index) {
            const row = this.items[index];
            const select = document.querySelector(`select[name="items[${index}][component_product_id]"]`);
            if (select) {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.units) {
                    row.availableUnits = JSON.parse(opt.dataset.units);
                    if (row.availableUnits.length > 0) {
                        row.component_product_unit_id = row.availableUnits[0].id;
                    }
                }
            }
        },

        async loadBomRecipe() {
            if (!this.selectedParentId) return;

            try {
                const res = await fetch(`/warehouse/assemblies/bom-data/${this.selectedParentId}`);
                const data = await res.json();

                if (data.success && data.items && data.items.length > 0) {
                    this.items = [];
                    data.items.forEach(bom => {
                        this.items.push({
                            component_product_id: bom.component_product_id,
                            component_product_unit_id: bom.component_product_unit_id,
                            quantity: parseFloat(bom.quantity) || 1,
                            availableUnits: [bom.component_unit]
                        });
                    });
                } else {
                    alert('Tidak ditemukan resep BOM untuk produk ini. Silakan buat resep terlebih dahulu di menu Kelola Resep BOM atau input komponen manual.');
                }
            } catch (err) {
                alert('Gagal mengambil data BOM: ' + err.message);
            }
        }
    };
}
</script>
@endsection
