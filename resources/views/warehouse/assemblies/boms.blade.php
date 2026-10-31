@extends('layouts.app')

@section('title', 'Master Template Resep BOM (Bill of Materials)')
@section('tab_title', 'Resep BOM')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full" x-data="bomManager()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.assemblies.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Perakitan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Master Resep BOM</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 Resep BOM (Bill of Materials) Paket Bundling</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.assemblies.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali ke Riwayat
            </a>
            <button type="button"
                    @click="openModal()"
                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-blue-500/25 cursor-pointer">
                <span>➕ Atur Resep Baru</span>
            </button>
        </div>
    </div>

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

    <!-- DAFTAR RESEP BOM YANG TERDAFTAR -->
    <div class="space-y-4">
        @forelse($boms as $parentId => $items)
            @php
                $parent = $items->first()->parentProduct;
            @endphp
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-slate-100 dark:border-slate-800 gap-2">
                    <div>
                        <div class="text-[11px] font-mono text-slate-400 font-bold">{{ $parent->sku }}</div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>📦 {{ $parent->name }}</span>
                        </h3>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('warehouse.assemblies.create') }}?parent_id={{ $parent->id }}"
                           class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 font-bold text-xs rounded-lg border border-emerald-200 dark:border-emerald-800">
                            ⚡ Rakit Paket Ini
                        </a>
                    </div>
                </div>

                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-400 uppercase font-bold text-[10px] tracking-wider">
                            <tr>
                                <th class="p-1.5 w-6">#</th>
                                <th class="p-1.5">Komponen Bahan Baku</th>
                                <th class="p-1.5">Satuan</th>
                                <th class="p-1.5 text-right">Kebutuhan per 1 Paket</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($items as $idx => $bom)
                                <tr>
                                    <td class="p-1.5 text-slate-400">{{ $idx + 1 }}</td>
                                    <td class="p-1.5 font-medium text-slate-800 dark:text-slate-200">
                                        {{ $bom->componentProduct->name ?? '-' }}
                                        <span class="text-[10px] text-slate-400 font-mono">({{ $bom->componentProduct->sku ?? '' }})</span>
                                    </td>
                                    <td class="p-1.5 text-slate-600 dark:text-slate-400">
                                        {{ $bom->componentUnit->unit_name ?? '-' }}
                                    </td>
                                    <td class="p-1.5 text-right font-black text-slate-900 dark:text-white font-mono">
                                        {{ number_format($bom->quantity, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center text-slate-400 shadow-xs">
                Belum ada resep BOM yang dikonfigurasi. Klik tombol <b>Atur Resep Baru</b> untuk mulai mendaftarkan paket bundling / hampers.
            </div>
        @endforelse
    </div>

    <!-- MODAL ATUR RESEP BOM -->
    <div x-show="showModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xs"
         style="display: none;">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full p-5 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4 max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📋 Konfigurasi Template Resep BOM</span>
                </h3>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer">✕</button>
            </div>

            <form method="POST" action="{{ route('warehouse.assemblies.boms.store') }}" class="space-y-4 overflow-y-auto pr-1">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Produk Paket (Parent) *</label>
                    <select name="parent_product_id"
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Produk Paket --</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->sku }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold uppercase text-slate-400 tracking-wider">Komponen Bahan Baku</label>
                        <button type="button"
                                @click="addComponent()"
                                class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white font-bold text-[11px] rounded-lg cursor-pointer">
                            ➕ Tambah Komponen
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(comp, cIdx) in components" :key="cIdx">
                            <div class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700 text-xs">
                                <div class="flex-1">
                                    <select :name="`components[${cIdx}][component_product_id]`"
                                            x-model="comp.component_product_id"
                                            @change="onCompProductChange(cIdx)"
                                            required
                                            class="w-full px-2 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium">
                                        <option value="">-- Pilih Komponen --</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-units="{{ json_encode($p->units) }}">
                                                {{ $p->sku }} - {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="w-36">
                                    <select :name="`components[${cIdx}][component_product_unit_id]`"
                                            x-model="comp.component_product_unit_id"
                                            required
                                            class="w-full px-2 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium">
                                        <template x-for="u in comp.availableUnits" :key="u.id">
                                            <option :value="u.id" x-text="u.unit_name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="w-28">
                                    <input type="number"
                                           step="0.0001"
                                           min="0.0001"
                                           :name="`components[${cIdx}][quantity]`"
                                           x-model.number="comp.quantity"
                                           placeholder="Qty"
                                           required
                                           class="w-full px-2 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-mono text-right font-bold">
                                </div>

                                <button type="button"
                                        @click="removeComponent(cIdx)"
                                        class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg cursor-pointer">
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button"
                            @click="showModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-500/25 cursor-pointer">
                        Simpan Resep BOM
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function bomManager() {
    return {
        showModal: false,
        components: [],

        openModal() {
            this.components = [];
            this.addComponent();
            this.showModal = true;
        },

        addComponent() {
            this.components.push({
                component_product_id: '',
                component_product_unit_id: '',
                quantity: 1,
                availableUnits: []
            });
        },

        removeComponent(idx) {
            this.components.splice(idx, 1);
        },

        onCompProductChange(idx) {
            const comp = this.components[idx];
            const select = document.querySelector(`select[name="components[${idx}][component_product_id]"]`);
            if (select) {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.units) {
                    comp.availableUnits = JSON.parse(opt.dataset.units);
                    if (comp.availableUnits.length > 0) {
                        comp.component_product_unit_id = comp.availableUnits[0].id;
                    }
                }
            }
        }
    };
}
</script>
@endsection
