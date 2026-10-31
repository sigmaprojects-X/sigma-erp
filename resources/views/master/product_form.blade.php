@extends('layouts.app')

@section('title', isset($product) ? 'Edit Item: ' . $product->name : 'Tambah Item Baru - SIGMA ERP')
@section('tab_title', isset($product) ? 'Edit Item' : 'Tambah Item')

@section('content')
@php
    $isEdit = isset($product);
    $customerTiersList = $customerTiers->values()->all();

    $initialUnits = [];
    if ($isEdit && $product->units->count() > 0) {
        foreach ($product->units as $idx => $u) {
            $uBarcode = $u->barcodes->first()?->barcode ?? '';

            // Map level prices (Customer Tiers Level 1 s/d Level 6)
            $tierPrices = [];
            foreach ($customerTiers as $t) {
                $pRecord = $u->prices->where('customer_tier_id', $t->id)->where('min_quantity', 1.00)->first();
                $tierPrices[$t->id] = [
                    'tier_name' => $t->name,
                    'tier_code' => $t->code,
                    'margin' => $pRecord && $pRecord->margin_percentage !== null ? (float)$pRecord->margin_percentage : (float)$t->default_margin_percentage,
                    'price' => $pRecord ? (float)$pRecord->price : 0,
                ];
            }

            // Map tiered quantity prices (Grosir Min Qty bertingkat, min_quantity > 1)
            $tieredPrices = [];
            $tieredRecords = $u->prices->where('min_quantity', '>', 1.00)->sortBy('min_quantity');
            foreach ($tieredRecords as $tr) {
                $tieredPrices[] = [
                    'min_qty' => (float)$tr->min_quantity,
                    'margin' => $tr->margin_percentage !== null ? (float)$tr->margin_percentage : 0,
                    'price' => (float)$tr->price,
                ];
            }

            $firstPrice = $u->prices->first();
            $initialUnits[] = [
                'id' => $u->id,
                'unit_code' => $u->unit_code,
                'unit_name' => $u->unit_name,
                'conversion_factor' => (float)$u->conversion_factor,
                'is_base_unit' => (bool)$u->is_base_unit,
                'is_active' => (bool)$u->is_active,
                'barcode' => $uBarcode,
                'tier_prices' => $tierPrices,
                'tiered_prices' => $tieredPrices,
                'weight_gram' => (float)($u->weight_gram ?? 0),
                'length_cm' => (float)($u->length_cm ?? 0),
                'width_cm' => (float)($u->width_cm ?? 0),
                'height_cm' => (float)($u->height_cm ?? 0),
                'point_reward' => (float)($u->point_reward ?? 0),
                'commission_rate' => (float)($u->commission_rate ?? 0),
                'discount_1' => (float)($firstPrice?->discount_1 ?? 0),
                'discount_2' => (float)($firstPrice?->discount_2 ?? 0),
                'discount_3' => (float)($firstPrice?->discount_3 ?? 0),
                'discount_4' => (float)($firstPrice?->discount_4 ?? 0),
            ];
        }
    } else {
        $defaultTierPrices = [];
        foreach ($customerTiers as $t) {
            $defaultTierPrices[$t->id] = [
                'tier_name' => $t->name,
                'tier_code' => $t->code,
                'margin' => (float)$t->default_margin_percentage,
                'price' => 0,
            ];
        }

        $initialUnits[] = [
            'id' => '',
            'unit_code' => 'PCS',
            'unit_name' => 'Pieces / Satuan Terkecil',
            'conversion_factor' => 1,
            'is_base_unit' => true,
            'is_active' => true,
            'barcode' => '',
            'tier_prices' => $defaultTierPrices,
            'tiered_prices' => [
                ['min_qty' => 12, 'margin' => 15, 'price' => 0],
                ['min_qty' => 60, 'margin' => 10, 'price' => 0],
            ],
            'weight_gram' => 0,
            'length_cm' => 0,
            'width_cm' => 0,
            'height_cm' => 0,
            'point_reward' => 0,
            'commission_rate' => 0,
            'discount_1' => 0,
            'discount_2' => 0,
            'discount_3' => 0,
            'discount_4' => 0,
        ];
    }

    $initialBomComponents = [];
    if ($isEdit && $product->relationLoaded('boms') && $product->boms->count() > 0) {
        foreach ($product->boms as $bomRow) {
            $initialBomComponents[] = [
                'component_product_id' => $bomRow->component_product_id,
                'component_product_unit_id' => $bomRow->component_product_unit_id,
                'quantity' => (float) $bomRow->quantity,
            ];
        }
    }

    $componentProductsList = isset($componentProducts)
        ? $componentProducts->map(fn ($cp) => [
            'id' => $cp->id,
            'sku' => $cp->sku,
            'name' => $cp->name,
            'cost_price' => (float) $cp->cost_price,
            'units' => $cp->units->map(fn ($cu) => [
                'id' => $cu->id,
                'unit_code' => $cu->unit_code,
                'unit_name' => $cu->unit_name,
                'conversion_factor' => (float) $cu->conversion_factor,
            ])->values()->all(),
        ])->values()->all()
        : [];
@endphp

<div x-data="itemForm({
        isEdit: {{ $isEdit ? 'true' : 'false' }},
        currentStock: {{ $isEdit ? (float)($product->total_quantity ?? 0) : 0 }},
        costPrice: {{ $isEdit ? (float)$product->cost_price : 0 }},
        autoSkuGenerated: '{{ $autoSku ?? '' }}',
        currentSku: '{{ $isEdit ? $product->sku : ($autoSku ?? '') }}',
        productName: '{{ $isEdit ? addslashes($product->name) : '' }}',
        customerTiersList: {{ json_encode($customerTiersList) }},
        masterUnits: {{ json_encode($units->pluck('code')->all()) }},
        units: {{ json_encode($initialUnits) }},
        bomComponents: {{ json_encode($initialBomComponents) }},
        componentProductsList: {{ json_encode($componentProductsList) }}
     })"
     @keydown.window.f10.prevent="submitForm()"
     @keydown.window.escape="cancelForm()"
     class="space-y-5 max-w-7xl mx-auto pb-20">

    <!-- FORM ELEMENT -->
    <form id="productMainForm"
          method="POST"
          action="{{ $isEdit ? route('master.products.update', $product->id) : route('master.products.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <!-- ========================================================================= -->
        <!-- 1. EXECUTIVE GLASSMORPHIC HEADER HERO                                     -->
        <!-- ========================================================================= -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 shadow-xl text-white p-6 md:p-7">
            <!-- Ambient Glow Shapes -->
            <div class="absolute -top-24 -right-24 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <!-- Breadcrumb Pills -->
                    <div class="flex items-center gap-2 text-[11px] font-semibold text-slate-400 mb-2">
                        <a href="{{ route('master.products') }}" class="hover:text-blue-400 transition-colors flex items-center gap-1">
                            <span>📦</span> <span>Master Data</span>
                        </a>
                        <span>/</span>
                        <a href="{{ route('master.products') }}" class="hover:text-blue-400 transition-colors">Daftar Item</a>
                        <span>/</span>
                        <span class="text-slate-200 font-bold px-2 py-0.5 rounded-md bg-white/10">{{ $isEdit ? 'Mode Edit' : 'Tambah Baru' }}</span>
                    </div>

                    <!-- Title & Live SKU Badge -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 text-white flex items-center justify-center text-xl shadow-lg border border-white/20">
                            {{ $isEdit ? '✏️' : '✨' }}
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-black tracking-tight text-white flex items-center gap-2.5">
                                <span>{{ $isEdit ? 'Edit Data Item: ' . $product->name : 'Tambah Item / Produk Baru' }}</span>
                            </h1>
                            <div class="flex flex-wrap items-center gap-2 mt-1 text-xs">
                                <span class="font-mono px-2 py-0.5 rounded-lg bg-blue-500/20 text-blue-300 border border-blue-400/30 font-bold"
                                      x-text="currentSku ? currentSku : 'SKU-BELUM-DIISI'"></span>
                                <span class="text-slate-400">•</span>
                                <span class="text-emerald-400 font-bold flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>Tersinkronisasi Otomatis ke Kasir POS [F12]</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Button Group with Keyboard Pills -->
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('master.products') }}"
                       class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white border border-white/15 text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-xs active:scale-95">
                        <span>Batal</span>
                        <kbd class="px-1.5 py-0.2 rounded bg-black/40 text-[10px] font-mono text-slate-300">Esc</kbd>
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-black text-xs transition-all shadow-lg shadow-emerald-950/40 border border-emerald-400/40 flex items-center gap-2 cursor-pointer active:scale-95">
                        <span>💾 Simpan Produk</span>
                        <kbd class="px-1.5 py-0.2 rounded bg-emerald-900/60 text-[10px] font-mono text-white">F10</kbd>
                    </button>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="bg-rose-50 border-2 border-rose-300/80 rounded-2xl p-4 text-xs text-rose-800 shadow-sm flex items-start gap-3">
                <span class="text-xl">⚠️</span>
                <div>
                    <h3 class="font-extrabold text-sm mb-1">Terdapat data yang belum sesuai:</h3>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- 2. MODERN FLOATING TAB BAR                                                -->
        <!-- ========================================================================= -->
        <div class="bg-white/95 backdrop-blur-md p-1.5 rounded-2xl border border-slate-200/90 shadow-xs flex flex-wrap gap-1.5 text-xs font-bold">
            <button type="button"
                    @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                <span>📋</span>
                <span>1. Data Umum</span>
                <span class="text-[9.5px] opacity-70 font-mono hidden md:inline">[Alt+1]</span>
            </button>

            <button type="button"
                    @click="activeTab = 'pricing'"
                    :class="activeTab === 'pricing' ? 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 cursor-pointer relative">
                <span>💰</span>
                <span>2. Harga & Multi Satuan</span>
                <span class="px-1.5 py-0.2 rounded-full bg-emerald-400 text-slate-950 font-black text-[9px]">Grid Pro</span>
                <span class="text-[9.5px] opacity-70 font-mono hidden md:inline">[Alt+2]</span>
            </button>

            <button type="button"
                    x-show="tracksInventory()"
                    @click="activeTab = 'dimensions'"
                    :class="activeTab === 'dimensions' ? 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                <span>📦</span>
                <span>3. Dimensi & Berat</span>
                <span class="text-[9.5px] opacity-70 font-mono hidden md:inline">[Alt+3]</span>
            </button>

            <button type="button"
                    @click="activeTab = 'discounts'"
                    :class="activeTab === 'discounts' ? 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                <span>🏷️</span>
                <span>4. Potongan & Diskon</span>
                <span class="text-[9.5px] opacity-70 font-mono hidden md:inline">[Alt+4]</span>
            </button>

            <button type="button"
                    @click="activeTab = 'gallery'"
                    :class="activeTab === 'gallery' ? 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                <span>🖼️</span>
                <span>5. Foto & Galeri</span>
                <span class="text-[9.5px] opacity-70 font-mono hidden md:inline">[Alt+5]</span>
            </button>

            <button type="button"
                    @click="activeTab = 'accounting'"
                    :class="activeTab === 'accounting' ? 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'"
                    class="px-4 py-2.5 rounded-xl transition-all flex items-center gap-2 cursor-pointer">
                <span>🏛️</span>
                <span>6. Pajak & Akuntansi</span>
                <span class="text-[9.5px] opacity-70 font-mono hidden md:inline">[Alt+6]</span>
            </button>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 1: DATA UMUM                                                          -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'general'" class="space-y-5">
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-6">

                <!-- 1.1 TIPE ITEM / KLASIFIKASI PRODUK -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                            <span>🏷️</span>
                            <span>Pilih Tipe Produk / Klasifikasi Barang</span>
                            <span class="text-rose-500">*</span>
                        </label>
                        <span class="text-[11px] text-slate-400 font-medium">Menentukan apakah item melacak stok fisik atau jasa</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2.5 text-xs">
                        @php
                            $types = [
                                'BARANG' => ['label' => 'Barang Fisik', 'desc' => 'Melacak Stok', 'icon' => '📦', 'color' => 'blue'],
                                'JASA' => ['label' => 'Jasa / Service', 'desc' => 'Tanpa Stok', 'icon' => '🔧', 'color' => 'teal'],
                                'RAKITAN_PROSES' => ['label' => 'Rakitan Produksi', 'desc' => 'BOM Assembly', 'icon' => '⚙️', 'color' => 'indigo'],
                                'RAKITAN_NON_PROSES' => ['label' => 'Paket Bundling', 'desc' => 'Langsung Jual', 'icon' => '🎁', 'color' => 'purple'],
                                'NON_INVENTORY' => ['label' => 'Non Inventory', 'desc' => 'Habis Pakai', 'icon' => '📝', 'color' => 'amber'],
                                'BIAYA' => ['label' => 'Biaya / Beban', 'desc' => 'Operasional', 'icon' => '💸', 'color' => 'rose'],
                            ];
                        @endphp
                        @foreach($types as $tKey => $tVal)
                            <label class="relative flex flex-col p-3 rounded-2xl border-2 cursor-pointer transition-all hover:scale-102 hover:shadow-xs"
                                   :class="selectedType === '{{ $tKey }}' ? 'border-blue-600 bg-blue-50/70 shadow-sm ring-2 ring-blue-500/20' : 'border-slate-200/80 bg-white hover:border-slate-300'">
                                <input type="radio"
                                       name="type"
                                       value="{{ $tKey }}"
                                       x-model="selectedType"
                                       @change="onTypeChange()"
                                       class="sr-only">
                                <span class="text-2xl mb-1">{{ $tVal['icon'] }}</span>
                                <span class="font-extrabold text-slate-900 leading-tight text-[11.5px]">{{ $tVal['label'] }}</span>
                                <span class="text-[10px] text-slate-500 mt-0.5">{{ $tVal['desc'] }}</span>
                            </label>
                        @endforeach
                    </div>

                    <!-- INFO BANNER PERILAKU TIPE PRODUK -->
                    <div class="mt-3 p-3 rounded-xl border text-xs flex items-start gap-2.5"
                         :class="selectedType === 'BIAYA' ? 'bg-rose-50 border-rose-200 text-rose-900' : (tracksInventory() ? 'bg-blue-50/70 border-blue-200 text-blue-900' : 'bg-teal-50/80 border-teal-200 text-teal-900')">
                        <span class="text-base" x-text="selectedType === 'BIAYA' ? '💸' : (tracksInventory() ? '📦' : 'ℹ️')"></span>
                        <div>
                            <template x-if="selectedType === 'BARANG'">
                                <span><strong>Barang Fisik:</strong> Melacak kartu stok fisik, HPP Moving Average, batas minimum restock, lokasi rak, serta opsi nomor seri / batch kadaluarsa.</span>
                            </template>
                            <template x-if="selectedType === 'JASA'">
                                <span><strong>Jasa / Service:</strong> Tidak melacak stok fisik gudang. Dapat langsung ditransaksikan di Kasir POS maupun Faktur Penjualan meskipun stok 0.</span>
                            </template>
                            <template x-if="selectedType === 'RAKITAN_PROSES'">
                                <span><strong>Rakitan Produksi (BOM Assembly):</strong> Barang jadi yang stok fisiknya bertambah melalui proses perakitan komponen di menu <a href="{{ route('warehouse.assemblies.boms') }}" target="_blank" class="underline font-bold">Gudang &rarr; Resep BOM &amp; Perakitan</a>.</span>
                            </template>
                            <template x-if="selectedType === 'RAKITAN_NON_PROSES'">
                                <span><strong>Paket Bundling (Langsung Jual):</strong> Paket jual langsung tanpa stok fisik tersendiri; komponen penyusunnya diatur pada menu <a href="{{ route('warehouse.assemblies.boms') }}" target="_blank" class="underline font-bold">Gudang &rarr; Resep BOM</a>.</span>
                            </template>
                            <template x-if="selectedType === 'NON_INVENTORY'">
                                <span><strong>Non Inventory (Habis Pakai):</strong> Item perlengkapan/pendukung yang tidak melacak saldo stok fisik gudang.</span>
                            </template>
                            <template x-if="selectedType === 'BIAYA'">
                                <span><strong>Biaya / Beban Operasional:</strong> Khusus pencatatan pembelian/pengeluaran operasional. Otomatis diset <em>Bisa Beli Tapi Tidak Dijual</em> dan disembunyikan dari layar Kasir POS.</span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- 1.1B KOMPONEN PENYUSUN (BOM / PAKET BUNDLING) -->
                <div x-show="['RAKITAN_PROSES', 'RAKITAN_NON_PROSES'].includes(selectedType)"
                     class="p-5 rounded-2xl bg-gradient-to-b from-indigo-50/80 to-purple-50/40 border-2 border-indigo-200/90 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-indigo-950 flex items-center gap-2">
                                <span x-text="selectedType === 'RAKITAN_PROSES' ? '⚙️' : '🎁'"></span>
                                <span x-text="selectedType === 'RAKITAN_PROSES' ? 'Resep Komponen Rakitan Produksi (BOM Assembly)' : 'Daftar Isi Komponen Paket Bundling'"></span>
                            </h3>
                            <p class="text-[11px] text-indigo-800 mt-0.5">
                                <template x-if="selectedType === 'RAKITAN_PROSES'">
                                    <span>Komponen di bawah ini akan dikurangi saat melakukan proses perakitan di Gudang.</span>
                                </template>
                                <template x-if="selectedType === 'RAKITAN_NON_PROSES'">
                                    <span>Saat paket bundling ini terjual di Kasir POS, stok fisik masing-masing komponen di bawah ini otomatis terpotong.</span>
                                </template>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="syncCostPriceFromBom()"
                                    x-show="bomComponents.length > 0"
                                    class="px-3 py-1.5 bg-white hover:bg-indigo-50 text-indigo-800 border border-indigo-300 font-extrabold text-[11px] rounded-xl transition-all cursor-pointer">
                                <span>🔄 Gunakan Total HPP Komponen</span>
                            </button>
                            <button type="button"
                                    @click="addBomRow()"
                                    class="px-3.5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                                <span>➕ Tambah Komponen</span>
                            </button>
                        </div>
                    </div>

                    <template x-if="bomComponents.length === 0">
                        <div class="p-4 rounded-xl bg-white/80 border border-dashed border-indigo-300 text-center text-xs text-slate-500">
                            Belum ada komponen penyusun. Klik <strong>+ Tambah Komponen</strong> untuk memilih barang penyusun rakitan/paket.
                        </div>
                    </template>

                    <template x-if="bomComponents.length > 0">
                        <div class="bg-white border border-indigo-200 rounded-2xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-xs divide-y divide-slate-200">
                                <thead class="bg-indigo-100/70 text-indigo-950 font-black text-[10.5px] uppercase">
                                    <tr>
                                        <th class="px-4 py-2.5">Barang Komponen Penyusun</th>
                                        <th class="px-3 py-2.5 w-44">Satuan Komponen</th>
                                        <th class="px-3 py-2.5 w-32 text-center">Kuantitas</th>
                                        <th class="px-4 py-2.5 w-40 text-right">Estimasi HPP</th>
                                        <th class="px-3 py-2.5 w-16 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(comp, cIdx) in bomComponents" :key="cIdx">
                                        <tr>
                                            <td class="px-4 py-2">
                                                <select :name="'bom_components[' + cIdx + '][component_product_id]'"
                                                        x-model="comp.component_product_id"
                                                        @change="onBomProductChange(cIdx)"
                                                        class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-bold bg-white">
                                                    <option value="">-- Pilih Barang Komponen --</option>
                                                    <template x-for="cp in componentProductsList" :key="cp.id">
                                                        <option :value="cp.id" x-text="cp.sku + ' - ' + cp.name"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <select :name="'bom_components[' + cIdx + '][component_product_unit_id]'"
                                                        x-model="comp.component_product_unit_id"
                                                        class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-bold bg-white">
                                                    <template x-for="cu in getComponentUnits(comp.component_product_id)" :key="cu.id">
                                                        <option :value="cu.id" x-text="cu.unit_code + ' (x' + cu.conversion_factor + ')'"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number"
                                                       step="any"
                                                       min="0.0001"
                                                       :name="'bom_components[' + cIdx + '][quantity]'"
                                                       x-model.number="comp.quantity"
                                                       class="w-24 px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold text-center bg-white">
                                            </td>
                                            <td class="px-4 py-2 text-right font-mono font-bold text-slate-700"
                                                x-text="formatRupiah(getBomRowCost(comp))">
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button"
                                                        @click="removeBomRow(cIdx)"
                                                        class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg font-bold text-xs cursor-pointer">
                                                    🗑️
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot class="bg-indigo-50/70 font-mono text-xs font-black text-indigo-950">
                                    <tr>
                                        <td colspan="3" class="px-4 py-2.5 text-right uppercase">Total Estimasi HPP Komponen:</td>
                                        <td class="px-4 py-2.5 text-right" x-text="formatRupiah(getTotalBomCost())"></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </template>
                </div>

                <!-- 1.2 IDENTITAS UTAMA (SKU & NAMA) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 pt-4 border-t border-slate-100">
                    <!-- SKU / KODE ITEM -->
                    <div class="md:col-span-4 space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">
                            Kode Item / Barcode SKU <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-slate-400 font-mono text-xs">#</span>
                            <input type="text"
                                   name="sku"
                                   x-model="currentSku"
                                   required
                                   placeholder="Contoh: SBK-BRS-001"
                                   class="w-full pl-8 pr-24 py-2.5 border-2 border-slate-200 rounded-xl text-xs font-mono font-bold uppercase focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-slate-50/50 hover:bg-white transition-all">
                            <button type="button"
                                    @click="generateRandomSku()"
                                    class="absolute right-1.5 px-3 py-1.5 bg-slate-900 hover:bg-blue-700 text-white text-[11px] font-extrabold rounded-lg transition-colors cursor-pointer flex items-center gap-1 shadow-xs">
                                <span>🎲 Auto</span>
                            </button>
                        </div>
                        <p class="text-[10.5px] text-slate-400">Kode unik pencarian secepat kilat saat kasir scan.</p>
                    </div>

                    <!-- NAMA PRODUK -->
                    <div class="md:col-span-8 space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">
                            Nama Lengkap Produk / Item <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               x-model="productName"
                               value="{{ old('name', $isEdit ? $product->name : '') }}"
                               required
                               placeholder="Contoh: Minyak Goreng Tropical Botol 2 Liter"
                               class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm font-black text-slate-900 focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-slate-50/50 hover:bg-white transition-all">
                        <p class="text-[10.5px] text-slate-400">Nama lengkap yang dicetak pada struk kasir, nota, dan etalase PWA.</p>
                    </div>
                </div>

                <!-- 1.2B HARGA BELI / HARGA POKOK MODAL (SANGAT JELAS & MENONJOL) -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border-2 border-emerald-300 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">💰</span>
                                <label class="text-xs font-black uppercase text-emerald-950 tracking-wider">
                                    Harga Beli / Modal Pokok (HPP Satuan Dasar) <span class="text-rose-500">*</span>
                                </label>
                                <span class="px-2 py-0.2 bg-emerald-200/80 text-emerald-900 font-extrabold text-[10px] rounded-full">Wajib Diisi</span>
                            </div>
                            <p class="text-[11px] text-emerald-800">
                                Masukkan harga beli dari supplier per satuan dasar (PCS/KG). Sistem otomatis menghitung margin keuntungan dan harga jual di Tab 2.
                            </p>
                        </div>
                        <div class="flex items-center gap-2.5 bg-white px-4 py-2.5 rounded-xl border-2 border-emerald-400 shadow-sm self-start sm:self-auto">
                            <span class="font-black text-sm text-emerald-900">Rp</span>
                            <input type="number"
                                   step="any"
                                   name="cost_price"
                                   x-model.number="costPrice"
                                   @input="recalculateAllUnits()"
                                   required
                                   placeholder="0"
                                   class="w-48 font-mono font-black text-base text-slate-900 focus:outline-hidden text-right">
                        </div>
                    </div>
                </div>

                <!-- 1.3 KLASIFIKASI & SUPPLIER -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-slate-100">
                    <!-- KATEGORI -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-extrabold text-slate-800">Kategori / Jenis</label>
                            <a href="{{ route('master.categories') }}" target="_blank" class="text-[10.5px] font-bold text-blue-600 hover:underline">+ Kelola</a>
                        </div>
                        <select name="category_id"
                                class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-semibold focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-white">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $isEdit ? $product->category_id : '') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }} ({{ $cat->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- SUB KATEGORI -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Sub Jenis / Sub Kategori</label>
                        <input type="text"
                               name="sub_category"
                               value="{{ old('sub_category', $isEdit ? $product->sub_category : '') }}"
                               placeholder="Contoh: Minyak Sawit, Beras Pandan"
                               class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-medium focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-white">
                    </div>

                    <!-- MEREK / BRAND -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Merek / Brand</label>
                        <input type="text"
                               name="brand"
                               value="{{ old('brand', $isEdit ? $product->brand : '') }}"
                               placeholder="Contoh: Tropical, Indomie, Unilever"
                               class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-medium focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-white">
                    </div>

                    <!-- SUPPLIER UTAMA -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-extrabold text-slate-800">Supplier Utama</label>
                            <a href="{{ route('master.suppliers') }}" target="_blank" class="text-[10.5px] font-bold text-blue-600 hover:underline">+ Kelola</a>
                        </div>
                        <select name="supplier_id"
                                class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-semibold focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-white">
                            <option value="">-- Pilih Supplier --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $isEdit ? $product->supplier_id : '') == $sup->id ? 'selected' : '' }}>
                                    {{ $sup->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 1.4 LOKASI RAK & STATUS JUAL -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 pt-4 border-t border-slate-100">
                    <!-- RAK / LOKASI GUDANG -->
                    <div class="space-y-1.5" x-show="tracksInventory()">
                        <label class="block text-xs font-extrabold text-slate-800">Rak / Bin Lokasi Penyimpanan</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-slate-400 text-xs">📍</span>
                            <input type="text"
                                   name="rack_location"
                                   value="{{ old('rack_location', $isEdit ? $product->rack_location : '') }}"
                                   placeholder="Contoh: Rak B-03, Gudang Belakang"
                                   class="w-full pl-8 pr-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-medium focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-white">
                        </div>
                    </div>

                    <!-- STATUS PENJUALAN -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Status Penjualan Kasir</label>
                        @php
                            $currentSaleStatus = old('sale_status', $isEdit ? $product->sale_status : 'ACTIVE');
                        @endphp
                        <select name="sale_status"
                                id="sale_status_select"
                                x-model="saleStatus"
                                class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-extrabold focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-white">
                            <option value="ACTIVE" x-bind:disabled="selectedType === 'BIAYA'" {{ $currentSaleStatus === 'ACTIVE' ? 'selected' : '' }}>🟢 Masih Dijual (Aktif di Kasir POS)</option>
                            <option value="DISCONTINUED" {{ $currentSaleStatus === 'DISCONTINUED' ? 'selected' : '' }}>🔴 Tidak Dijual (Discontinue)</option>
                            <option value="BUY_ONLY" {{ $currentSaleStatus === 'BUY_ONLY' ? 'selected' : '' }}>🟡 Bisa Beli Tapi Tidak Dijual</option>
                        </select>
                        <p class="text-[11px] text-slate-500 font-medium" x-show="tracksInventory()">
                            <span class="text-rose-600 font-bold">* Catatan:</span> Status <strong>Discontinue</strong> hanya dapat dipilih jika sisa stok fisik barang sudah <strong>0 (kosong)</strong>.
                        </p>
                    </div>

                    <!-- RESTOCK THRESHOLD ALERTS -->
                    <div class="grid grid-cols-2 gap-2 space-y-0" x-show="tracksInventory()">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800">Stok Min (Alert)</label>
                            <input type="number"
                                   step="any"
                                   name="min_stock_alert"
                                   value="{{ old('min_stock_alert', $isEdit ? (float)$product->min_stock_alert : 5) }}"
                                   class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-mono font-bold text-amber-700 bg-amber-50/40 focus:border-amber-600 focus:outline-hidden text-center">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-extrabold text-slate-800">Stok Maksimal</label>
                            <input type="number"
                                   step="any"
                                   name="max_stock"
                                   value="{{ old('max_stock', $isEdit && $product->max_stock ? (float)$product->max_stock : '') }}"
                                   placeholder="Opsional"
                                   class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-700 bg-slate-50 focus:border-blue-600 focus:outline-hidden text-center">
                        </div>
                    </div>
                </div>

                <!-- 1.5 TOGGLE SERIAL / BATCH EXPIRY & CATATAN -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 pt-4 border-t border-slate-100">
                    <div class="md:col-span-5 space-y-2.5" x-show="tracksInventory()">
                        <label class="flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-200 hover:border-blue-300 hover:bg-blue-50/30 transition-all cursor-pointer">
                            <input type="checkbox"
                                   name="has_serial_number"
                                   value="1"
                                   {{ old('has_serial_number', $isEdit ? $product->has_serial_number : false) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <div>
                                <span class="text-xs font-extrabold text-slate-900 block">Item Memiliki Serial No / IMEI</span>
                                <span class="text-[10px] text-slate-500">Wajib input nomor seri saat kasir scan barcode.</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-2xl border-2 border-slate-200 hover:border-blue-300 hover:bg-blue-50/30 transition-all cursor-pointer">
                            <input type="checkbox"
                                   name="has_batch_expiry"
                                   value="1"
                                   {{ old('has_batch_expiry', $isEdit ? $product->has_batch_expiry : false) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <div>
                                <span class="text-xs font-extrabold text-slate-900 block">Pelacakan Batch & Tanggal Expired</span>
                                <span class="text-[10px] text-slate-500">Peringatan otomatis barang mendekati kadaluarsa.</span>
                            </div>
                        </label>
                    </div>

                    <div :class="tracksInventory() ? 'md:col-span-7' : 'md:col-span-12'" class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Catatan / Deskripsi Spesifikasi</label>
                        <textarea name="notes"
                                  rows="3"
                                  placeholder="Tuliskan catatan teknis produk, instruksi penyimpanan, garansi, dsb."
                                  class="w-full px-3.5 py-2.5 border-2 border-slate-200 rounded-xl text-xs font-medium focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 focus:outline-hidden bg-slate-50/50 hover:bg-white transition-all">{{ old('notes', $isEdit ? $product->notes : '') }}</textarea>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: HARGA & MULTI SATUAN (GRID KEBAWAH ELEGAN)                         -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'pricing'" class="space-y-6">

            <!-- 2.1 HERO HPP BANNER WITH QUICK SIMULATOR -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 md:p-7 shadow-lg border border-slate-800">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center text-lg">💰</span>
                            <h2 class="text-lg font-black tracking-tight text-white">HARGA BELI / HARGA POKOK MODAL (HPP SATUAN DASAR)</h2>
                        </div>
                        <p class="text-xs text-slate-300 max-w-xl">
                            Harga beli dari supplier per satuan dasar terkecil (Base Unit). Nilai ini menjadi patokan modal untuk menghitung otomatis margin % dan harga jual semua kemasan di bawah.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 bg-white/10 p-3 rounded-2xl border border-white/15 backdrop-blur-md">
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-emerald-300 block tracking-wider">Harga Beli Satuan (Modal)</span>
                            <span class="text-xs font-bold text-slate-300">Rp</span>
                        </div>
                        <input type="number"
                               step="any"
                               name="cost_price"
                               x-model.number="costPrice"
                               @input="recalculateAllUnits()"
                               required
                               placeholder="0"
                               class="w-48 px-3.5 py-2 rounded-xl text-lg font-mono font-black text-slate-900 bg-white focus:ring-4 focus:ring-emerald-400/30 focus:outline-hidden text-right shadow-inner">
                    </div>
                </div>
            </div>

            <!-- 2.2 DAFTAR KEMASAN / SATUAN (GRID BERTINGKAT KE BAWAH) -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                            <span>📦</span>
                            <span>Daftar Kemasan & Satuan Bertingkat (Grid ke Bawah)</span>
                        </h3>
                        <p class="text-xs text-slate-500">
                            Setiap kemasan memiliki pengaturan harga lengkap berdasarkan <strong>6 Level Pelanggan</strong> dan <strong>Jumlah Pembelian Grosir</strong>.
                        </p>
                    </div>
                    <button type="button"
                            @click="addUnitRow()"
                            class="px-4 py-2.5 bg-gradient-to-r from-blue-700 to-indigo-700 hover:from-blue-800 hover:to-indigo-800 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                        <span>➕ Tambah Kemasan Baru</span>
                    </button>
                </div>

                <!-- BANNER PANDUAN PERUBAHAN ISI KEMASAN PABRIK -->
                <div class="p-3.5 bg-amber-50/80 border border-amber-200 rounded-2xl flex items-start gap-3 text-xs text-amber-900 shadow-2xs">
                    <span class="text-base shrink-0">💡</span>
                    <div class="leading-relaxed">
                        <strong>Panduan Perubahan Kemasan / Isi Dus Supplier:</strong><br>
                        Jika pabrik mengganti isi kemasan (misal 1 Dus tadinya isi 12 pcs berganti menjadi isi 24 pcs), <strong>JANGAN mengubah angka konversi pada kemasan lama</strong>. Cukup klik tombol <span class="font-bold text-indigo-700">+ Tambah Kemasan Baru</span> dengan kode kemasan baru (misal: <code>DUS-24</code>) dan barcode barunya. Kemasan lama <code>DUS-12</code> cukup diubah statusnya menjadi <strong>Non-Aktif</strong> agar sisa stok fisik di gudang tetap aman terjual tanpa selisih stok.
                    </div>
                </div>

                <!-- STACKED CARDS CONTAINER -->
                <div class="space-y-6">
                    <template x-for="(u, index) in units" :key="index">
                        <div class="bg-white border-2 rounded-3xl p-6 shadow-sm space-y-6 transition-all hover:shadow-md"
                             :class="index === 0 ? 'border-indigo-400/80 bg-indigo-50/15 ring-2 ring-indigo-500/10' : 'border-slate-200 bg-white'">

                            <!-- CARD HEADER BAR -->
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pb-4 border-b border-slate-200/80">
                                <div class="flex flex-wrap items-center gap-3">
                                    <template x-if="index === 0">
                                        <div class="flex items-center gap-2 px-3.5 py-1.5 bg-emerald-600 text-white font-black text-xs rounded-xl shadow-xs">
                                            <span>⭐</span>
                                            <span>SATUAN 1: SATUAN DASAR (BASE UNIT)</span>
                                        </div>
                                    </template>
                                    <template x-if="index > 0">
                                        <div class="flex items-center gap-2 px-3.5 py-1.5 bg-indigo-700 text-white font-black text-xs rounded-xl shadow-xs">
                                            <span>📦</span>
                                            <span x-text="'SATUAN ' + (index + 1) + ': KEMASAN BERTINGKAT'"></span>
                                        </div>
                                    </template>

                                    <!-- LIVE CONVERSION VISUAL BADGE -->
                                    <div class="px-3 py-1 bg-slate-100 border border-slate-200 rounded-xl text-xs font-mono font-bold text-slate-700 flex items-center gap-1.5">
                                        <span>1</span>
                                        <span class="text-blue-700 font-extrabold" x-text="u.unit_code"></span>
                                        <span>=</span>
                                        <span class="text-indigo-700 font-extrabold" x-text="u.conversion_factor"></span>
                                        <span x-text="units[0] ? units[0].unit_code : 'PCS'"></span>
                                    </div>

                                    <!-- EFFECTIVE COST PRICE CHIP -->
                                    <div class="px-3 py-1 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-mono font-extrabold text-emerald-800">
                                        <span>HPP Satuan: </span>
                                        <span x-text="formatRupiah(costPrice * (u.conversion_factor || 1))"></span>
                                    </div>
                                </div>

                                <!-- TOGGLE STATUS & DELETE UNIT BUTTON -->
                                <div class="flex items-center gap-2">
                                    <template x-if="index > 0">
                                        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-bold cursor-pointer transition-colors"
                                               :class="u.is_active ? 'bg-emerald-50 text-emerald-800 border-emerald-300' : 'bg-slate-100 text-slate-500 border-slate-300'">
                                            <input type="checkbox"
                                                   :name="'units[' + index + '][is_active]'"
                                                   value="1"
                                                   x-model="u.is_active"
                                                   class="rounded text-emerald-600 focus:ring-emerald-500">
                                            <span x-text="u.is_active ? '● Kemasan Aktif' : '○ Non-Aktif (Discontinued)'"></span>
                                        </label>
                                    </template>
                                    <template x-if="index === 0">
                                        <input type="hidden" :name="'units[' + index + '][is_active]'" value="1">
                                    </template>
                                    <template x-if="index > 0">
                                        <button type="button"
                                                @click="removeUnitRow(index)"
                                                class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-extrabold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer">
                                            <span>🗑️ Hapus</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- ROW 1: IDENTITAS SATUAN & BARCODE -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-4 text-xs">
                                <input type="hidden" :name="'units[' + index + '][is_base_unit]'" :value="index === 0 ? '1' : '0'">
                                <template x-if="u.id">
                                    <input type="hidden" :name="'units[' + index + '][id]'" :value="u.id">
                                </template>

                                <!-- PILIH SATUAN DARI MASTER -->
                                <div class="md:col-span-3 space-y-1">
                                    <label class="block font-extrabold text-slate-800">Pilih Satuan / UOM <span class="text-rose-500">*</span></label>
                                    <select x-model="u.unit_code"
                                            :name="'units[' + index + '][unit_code]'"
                                            @change="recalculateUnit(index)"
                                            required
                                            class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-black text-xs uppercase focus:border-blue-600 focus:outline-hidden bg-white">
                                        @foreach($units as $mu)
                                            <option value="{{ $mu->code }}">{{ $mu->code }} - {{ $mu->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-slate-400">Pilih satuan kemasan barang.</p>
                                </div>

                                <!-- NAMA KEMASAN -->
                                <div class="md:col-span-4 space-y-1">
                                    <label class="block font-extrabold text-slate-800">Nama / Keterangan Kemasan</label>
                                    <input type="text"
                                           :name="'units[' + index + '][unit_name]'"
                                           x-model="u.unit_name"
                                           placeholder="Contoh: Dus isi 24 Botol"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-medium text-xs focus:border-blue-600 focus:outline-hidden bg-white">
                                    <p class="text-[10px] text-slate-400">Keterangan kemasan di faktur.</p>
                                </div>

                                <!-- FAKTOR KONVERSI -->
                                <div class="md:col-span-2 space-y-1">
                                    <label class="block font-extrabold text-slate-800">Faktor Konversi</label>
                                    <input type="number"
                                           step="any"
                                           :name="'units[' + index + '][conversion_factor]'"
                                           x-model.number="u.conversion_factor"
                                           :readonly="index === 0"
                                           @input="recalculateUnit(index)"
                                           required
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono font-black text-center text-xs focus:border-blue-600 focus:outline-hidden"
                                           :class="index === 0 ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-white text-slate-900'">
                                    <p class="text-[10px] text-slate-400 text-center" x-text="index === 0 ? 'Satuan dasar = 1' : 'Pengali satuan dasar'"></p>
                                </div>

                                <!-- BARCODE KHUSUS KEMASAN INI -->
                                <div class="md:col-span-3 space-y-1">
                                    <label class="block font-extrabold text-slate-800">Barcode Kemasan Ini</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-slate-400 font-mono text-xs">🏷️</span>
                                        <input type="text"
                                               :name="'units[' + index + '][barcode]'"
                                               x-model="u.barcode"
                                               placeholder="Barcode dus / pack..."
                                               class="w-full pl-8 pr-3 py-2 border-2 border-slate-200 rounded-xl font-mono text-xs focus:border-blue-600 focus:outline-hidden bg-white">
                                    </div>
                                    <p class="text-[10px] text-slate-400">Barcode khusus kemasan ini saat scan kasir.</p>
                                </div>
                            </div>

                            <!-- STRIP HARGA BELI / MODAL KEMASAN INI -->
                            <div class="px-4 py-3 rounded-2xl bg-emerald-50 border-2 border-emerald-200/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-2xs">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-lg bg-emerald-200/80 text-emerald-900 flex items-center justify-center text-sm font-bold">💰</span>
                                    <div>
                                        <span class="font-black text-emerald-950 uppercase tracking-wide">Harga Beli (Modal Pokok) Satuan Ini:</span>
                                        <span class="text-emerald-800 text-[11px] ml-1 font-medium" x-text="'(HPP Satuan Dasar × ' + (u.conversion_factor || 1) + ' pcs)'"></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 self-start sm:self-auto font-mono">
                                    <span class="text-xs font-bold text-slate-500">Rp</span>
                                    <span class="text-base font-black text-emerald-950 bg-white px-3.5 py-1.5 rounded-xl border border-emerald-300 shadow-xs"
                                          x-text="formatRupiah(costPrice * (u.conversion_factor || 1))"></span>
                                </div>
                            </div>

                            <!-- ROW 2: SETTING HARGA BERDASARKAN LEVEL PELANGGAN (LEVEL 1 S/D LEVEL 6) -->
                            <div class="bg-gradient-to-b from-slate-50 to-slate-100/60 border border-slate-200 rounded-2xl p-5 space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-200 pb-3">
                                    <div>
                                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                            <span>👥</span>
                                            <span>1. Setting Harga Berdasarkan Level Pelanggan (Customer Tiers Level 1 s/d 6)</span>
                                        </h4>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            Harga normal saat scan di POS. Sistem otomatis menerapkan harga sesuai level customer yang dipilih kasir.
                                        </p>
                                    </div>
                                    <span class="px-2.5 py-1 bg-indigo-100 text-indigo-900 font-black rounded-lg text-[10px] tracking-wide self-start sm:self-auto">
                                        6 LEVEL HARGA AKTIF
                                    </span>
                                </div>

                                <!-- GRID 6 LEVEL KARTU -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">
                                    @php
                                        $tierColorMap = [
                                            'RETAIL' => ['border' => 'border-indigo-200 hover:border-indigo-400', 'badge' => 'bg-indigo-100 text-indigo-800', 'icon' => '🛒'],
                                            'GROSIR' => ['border' => 'border-emerald-200 hover:border-emerald-400', 'badge' => 'bg-emerald-100 text-emerald-800', 'icon' => '🏬'],
                                            'AGEN' => ['border' => 'border-blue-200 hover:border-blue-400', 'badge' => 'bg-blue-100 text-blue-800', 'icon' => '🤝'],
                                            'DISTRIBUTOR' => ['border' => 'border-purple-200 hover:border-purple-400', 'badge' => 'bg-purple-100 text-purple-800', 'icon' => '🚚'],
                                            'MEMBER' => ['border' => 'border-amber-200 hover:border-amber-400', 'badge' => 'bg-amber-100 text-amber-900', 'icon' => '👑'],
                                            'KHUSUS' => ['border' => 'border-rose-200 hover:border-rose-400', 'badge' => 'bg-rose-100 text-rose-800', 'icon' => '📑'],
                                        ];
                                    @endphp
                                    @foreach($customerTiers as $t)
                                        @php
                                            $cStyle = $tierColorMap[$t->code] ?? ['border' => 'border-slate-200', 'badge' => 'bg-slate-100 text-slate-800', 'icon' => '🏷️'];
                                        @endphp
                                        <div class="bg-white border-2 {{ $cStyle['border'] }} rounded-2xl p-3.5 shadow-2xs space-y-2.5 transition-all">
                                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                                <div class="flex items-center gap-1.5">
                                                    <span>{{ $cStyle['icon'] }}</span>
                                                    <span class="font-extrabold text-xs text-slate-900">{{ $t->name }}</span>
                                                </div>
                                                <span class="px-2 py-0.5 rounded-md font-mono text-[9.5px] font-black {{ $cStyle['badge'] }}">{{ $t->code }}</span>
                                            </div>

                                            <div class="grid grid-cols-12 gap-2 text-xs items-center">
                                                <!-- MARGIN % -->
                                                <div class="col-span-5 space-y-1">
                                                    <label class="block text-[10.5px] font-bold text-slate-500">Margin %</label>
                                                    <div class="relative">
                                                        <input type="number"
                                                               step="0.1"
                                                               :name="'units[' + index + '][tier_prices][{{ $t->id }}][margin]'"
                                                               x-model.number="u.tier_prices['{{ $t->id }}'].margin"
                                                               @input="updateTierPriceByMargin(index, '{{ $t->id }}')"
                                                               class="w-full px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold text-blue-700 text-center focus:ring-2 focus:ring-blue-500">
                                                    </div>
                                                </div>

                                                <!-- HARGA JUAL SATUAN -->
                                                <div class="col-span-7 space-y-1">
                                                    <label class="block text-[10.5px] font-bold text-slate-500">Harga Jual (Rp)</label>
                                                    <input type="number"
                                                           step="any"
                                                           :name="'units[' + index + '][tier_prices][{{ $t->id }}][price]'"
                                                           x-model.number="u.tier_prices['{{ $t->id }}'].price"
                                                           @input="updateTierMarginByPrice(index, '{{ $t->id }}')"
                                                           class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-black text-slate-900 text-right focus:ring-2 focus:ring-blue-500">
                                                </div>
                                            </div>

                                            <!-- PROFIT PER ITEM PREVIEW CHIP -->
                                            <div class="flex items-center justify-between text-[10.5px] font-mono pt-1 text-slate-500 border-t border-slate-50">
                                                <span>Laba:</span>
                                                <span class="font-extrabold text-emerald-700"
                                                      x-text="formatRupiah((u.tier_prices['{{ $t->id }}'].price || 0) - (costPrice * (u.conversion_factor || 1)))"></span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- ROW 3: SETTING HARGA BERDASARKAN JUMLAH PEMBELIAN (TIERED QTY / GROSIR BERTINGKAT) -->
                            <div class="bg-gradient-to-b from-amber-50/60 to-amber-100/30 border border-amber-200/90 rounded-2xl p-5 space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-amber-200 pb-3">
                                    <div>
                                        <h4 class="text-xs font-black text-amber-950 uppercase tracking-wider flex items-center gap-2">
                                            <span>📊</span>
                                            <span>2. Setting Harga Berdasarkan Jumlah Pembelian (Grosir Bertingkat)</span>
                                        </h4>
                                        <p class="text-[11px] text-amber-800 mt-0.5">
                                            Beli semakin banyak kuantitas, harga per item otomatis semakin murah saat kasir menambah kuantitas di POS.
                                        </p>
                                    </div>
                                    <button type="button"
                                            @click="addTieredQtyRow(index)"
                                            class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer self-start sm:self-auto active:scale-95">
                                        <span>➕ Tambah Rentang Jumlah (Qty)</span>
                                    </button>
                                </div>

                                <!-- TABLE RENTANG JUMLAH PEMBELIAN -->
                                <div class="bg-white border border-amber-200 rounded-2xl overflow-hidden shadow-2xs">
                                    <table class="w-full text-left text-xs divide-y divide-slate-200">
                                        <thead class="bg-amber-100/70 text-amber-950 font-black text-[10.5px] uppercase">
                                            <tr>
                                                <th class="px-4 py-2.5 w-44">Min. Pembelian (Qty &ge;)</th>
                                                <th class="px-3 py-2.5 w-32 text-center">Target Margin %</th>
                                                <th class="px-4 py-2.5 w-48 text-right">Harga Jual Satuan (Rp)</th>
                                                <th class="px-3 py-2.5 text-center">Hemat Pelanggan</th>
                                                <th class="px-3 py-2.5 w-16 text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 font-mono text-[11.5px]">
                                            <template x-for="(tq, qIdx) in (u.tiered_prices || [])" :key="qIdx">
                                                <tr class="hover:bg-amber-50/60 transition-colors">
                                                    <!-- MIN QTY -->
                                                    <td class="px-4 py-2 font-sans">
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="font-bold text-slate-500 text-sm">&ge;</span>
                                                            <input type="number"
                                                                   step="any"
                                                                   :name="'units[' + index + '][tiered_prices][' + qIdx + '][min_qty]'"
                                                                   x-model.number="tq.min_qty"
                                                                   required
                                                                   placeholder="Contoh: 12"
                                                                   class="w-24 px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold text-center focus:ring-2 focus:ring-amber-500 bg-white">
                                                            <span class="text-slate-600 font-bold text-xs" x-text="u.unit_code"></span>
                                                        </div>
                                                    </td>

                                                    <!-- TARGET MARGIN % -->
                                                    <td class="px-3 py-2 text-center">
                                                        <input type="number"
                                                               step="0.1"
                                                               :name="'units[' + index + '][tiered_prices][' + qIdx + '][margin]'"
                                                               x-model.number="tq.margin"
                                                               @input="updateTieredQtyPriceByMargin(index, qIdx)"
                                                               class="w-20 px-2 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold text-center text-amber-800 focus:ring-2 focus:ring-amber-500 bg-white">
                                                    </td>

                                                    <!-- HARGA JUAL TIERED QTY -->
                                                    <td class="px-4 py-2 text-right">
                                                        <input type="number"
                                                               step="any"
                                                               :name="'units[' + index + '][tiered_prices][' + qIdx + '][price]'"
                                                               x-model.number="tq.price"
                                                               @input="updateTieredQtyMarginByPrice(index, qIdx)"
                                                               required
                                                               class="w-36 px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-black text-right text-slate-900 focus:ring-2 focus:ring-amber-500 bg-white">
                                                    </td>

                                                    <!-- HEMAT PELANGGAN -->
                                                    <td class="px-3 py-2 text-center font-sans">
                                                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                                            Grosir Khusus
                                                        </span>
                                                    </td>

                                                    <!-- AKSI HAPUS -->
                                                    <td class="px-3 py-2 text-center font-sans">
                                                        <button type="button"
                                                                @click="removeTieredQtyRow(index, qIdx)"
                                                                class="px-2 py-1 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors cursor-pointer"
                                                                title="Hapus Baris Ini">
                                                            🗑️
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <template x-if="!u.tiered_prices || u.tiered_prices.length === 0">
                                                <tr>
                                                    <td colspan="5" class="px-4 py-4 text-center text-slate-400 font-sans text-xs">
                                                        Belum ada rentang kuantitas grosir untuk kemasan ini. Klik tombol <strong>+ Tambah Rentang Jumlah (Qty)</strong> di atas.
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>

                <!-- ADD ANOTHER PACKAGING UNIT BUTTON (BIG WELCOMING DASHED CARD) -->
                <div class="pt-2">
                    <button type="button"
                            @click="addUnitRow()"
                            class="w-full py-4 border-2 border-dashed border-blue-400 hover:border-blue-600 bg-gradient-to-r from-blue-50/40 via-indigo-50/40 to-blue-50/40 hover:from-blue-50 hover:to-indigo-50 text-blue-800 font-black text-xs rounded-3xl transition-all flex items-center justify-center gap-2.5 cursor-pointer shadow-xs hover:shadow-md active:scale-99">
                        <span class="text-xl">➕</span>
                        <span>Tambah Kemasan / Satuan Bertingkat Baru (Grid ke Bawah)</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- TAB 3: DIMENSI & KARGO (SCREENSHOT 1505)   -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'dimensions'" class="space-y-5">
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-6">

                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>📦</span>
                        <span>Dimensi & Berat Fisik per Kemasan</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Digunakan untuk kalkulasi ongkir ekspedisi logistik (JNE, J&T, SiCepat, Kargo) dan tata letak rak penyimpanan gudang.
                    </p>
                </div>

                <div class="space-y-4">
                    <template x-for="(u, index) in units" :key="index">
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                                <div class="font-extrabold text-xs text-slate-900 flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-[11px] font-mono font-bold" x-text="u.unit_code"></span>
                                    <span>Dimensi Kemasan:</span>
                                    <span class="text-slate-600" x-text="u.unit_name || u.unit_code"></span>
                                </div>
                                <span class="text-xs font-mono text-slate-500" x-text="'Konversi: ' + u.conversion_factor + ' pcs'"></span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Berat Barang (Gram)</label>
                                    <input type="number"
                                           step="any"
                                           :name="'units[' + index + '][weight_gram]'"
                                           x-model.number="u.weight_gram"
                                           placeholder="Gram"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white">
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Panjang (cm)</label>
                                    <input type="number"
                                           step="any"
                                           :name="'units[' + index + '][length_cm]'"
                                           x-model.number="u.length_cm"
                                           placeholder="cm"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white">
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Lebar (cm)</label>
                                    <input type="number"
                                           step="any"
                                           :name="'units[' + index + '][width_cm]'"
                                           x-model.number="u.width_cm"
                                           placeholder="cm"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white">
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Tinggi (cm)</label>
                                    <input type="number"
                                           step="any"
                                           :name="'units[' + index + '][height_cm]'"
                                           x-model.number="u.height_cm"
                                           placeholder="cm"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white">
                                </div>
                            </div>

                            <!-- LIVE VOLUME & VOLUMETRIC WEIGHT PREVIEW -->
                            <div class="flex flex-wrap items-center gap-5 text-xs text-slate-600 pt-2 border-t border-slate-200 font-mono">
                                <div class="flex items-center gap-1.5">
                                    <span>Volume Kubikasi: </span>
                                    <strong class="text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-200"
                                            x-text="((u.length_cm || 0) * (u.width_cm || 0) * (u.height_cm || 0)).toLocaleString() + ' cm³'"></strong>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span>Estimasi Bobot Kargo (P×L×T / 6000): </span>
                                    <strong class="text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200"
                                            x-text="(((u.length_cm || 0) * (u.width_cm || 0) * (u.height_cm || 0)) / 6000).toFixed(2) + ' Kg'"></strong>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: POTONGAN & DISKON (1506)            -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'discounts'" class="space-y-5">
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-6">

                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🏷️</span>
                        <span>Potongan Harga Jual Bertingkat</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Mendukung diskon bertingkat (misal: 5% + 2% + 1%) otomatis terpotong saat kasir melakukan scan di kasir POS.
                    </p>
                </div>

                <div class="space-y-4">
                    <template x-for="(u, index) in units" :key="index">
                        <div class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4">
                            <div class="font-extrabold text-xs text-slate-900 flex items-center gap-2 border-b border-slate-200 pb-3">
                                <span class="px-2.5 py-1 rounded-lg bg-blue-100 text-blue-800 text-[11px] font-mono font-bold" x-text="u.unit_code"></span>
                                <span>Potongan Diskon untuk Satuan:</span>
                                <span class="text-slate-600" x-text="u.unit_name || u.unit_code"></span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Potongan 1 (%)</label>
                                    <input type="number"
                                           step="0.1"
                                           :name="'units[' + index + '][discount_1]'"
                                           x-model.number="u.discount_1"
                                           placeholder="%"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white text-center">
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Potongan 2 (%)</label>
                                    <input type="number"
                                           step="0.1"
                                           :name="'units[' + index + '][discount_2]'"
                                           x-model.number="u.discount_2"
                                           placeholder="%"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white text-center">
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Potongan 3 (%)</label>
                                    <input type="number"
                                           step="0.1"
                                           :name="'units[' + index + '][discount_3]'"
                                           x-model.number="u.discount_3"
                                           placeholder="%"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white text-center">
                                </div>
                                <div class="space-y-1">
                                    <label class="block font-bold text-slate-700">Potongan 4 (%)</label>
                                    <input type="number"
                                           step="0.1"
                                           :name="'units[' + index + '][discount_4]'"
                                           x-model.number="u.discount_4"
                                           placeholder="%"
                                           class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl font-mono focus:border-blue-600 focus:outline-hidden bg-white text-center">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 5: GAMBAR & GALERI (1507)              -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'gallery'" class="space-y-5">
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-6">

                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🖼️</span>
                        <span>Foto Produk & Galeri Gambar</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Foto akan tampil pada layar sentuh POS kasir dan etalase toko online / PWA Customer Ordering.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- FOTO UTAMA PRODUK -->
                    <div class="md:col-span-5 space-y-3">
                        <label class="block text-xs font-black uppercase text-slate-800">Foto Utama Produk</label>
                        <div class="border-2 border-dashed border-slate-300 rounded-3xl p-5 text-center hover:border-blue-500 transition-colors bg-slate-50/50">
                            <!-- PREVIEW BOX -->
                            <div class="w-full h-56 rounded-2xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden mb-3.5 relative shadow-inner">
                                <template x-if="mainImagePreview">
                                    <img :src="mainImagePreview" alt="Preview Foto Utama" class="w-full h-full object-contain">
                                </template>
                                <template x-if="!mainImagePreview">
                                    @if($isEdit && $product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-contain">
                                    @else
                                        <div class="text-center p-4">
                                            <span class="text-5xl text-slate-300 block mb-2">📷</span>
                                            <span class="text-xs text-slate-400 font-bold">Belum ada foto utama</span>
                                        </div>
                                    @endif
                                </template>
                            </div>

                            <input type="file"
                                   name="image"
                                   id="mainImageInput"
                                   accept="image/*"
                                   @change="previewMainImage($event)"
                                   class="hidden">
                            <label for="mainImageInput"
                                   class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-black text-xs rounded-xl shadow-xs transition-all cursor-pointer inline-flex items-center gap-2">
                                <span>📤 Pilih / Upload Foto Utama</span>
                            </label>
                            <p class="text-[10.5px] text-slate-400 mt-2">Mendukung file JPG, PNG, WEBP hingga 2 MB</p>
                        </div>
                    </div>

                    <!-- GALERI FOTO TAMBAHAN -->
                    <div class="md:col-span-7 space-y-3">
                        <label class="block text-xs font-black uppercase text-slate-800">Galeri Foto Tambahan (Hingga 5 Gambar)</label>
                        <div class="border-2 border-dashed border-slate-300 rounded-3xl p-5 hover:border-blue-500 transition-colors bg-slate-50/50 space-y-4">
                            <input type="file"
                                   name="additional_images[]"
                                   id="additionalImagesInput"
                                   accept="image/*"
                                   multiple
                                   class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">

                            @if($isEdit && !empty($product->additional_images))
                                <div class="pt-2">
                                    <div class="text-[11px] font-bold text-slate-600 mb-2">Foto tersimpan:</div>
                                    <div class="grid grid-cols-4 gap-2.5">
                                        @foreach($product->additional_images as $galImg)
                                            <div class="h-24 rounded-xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                                                <img src="{{ asset('storage/' . $galImg) }}" class="w-full h-full object-cover">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <p class="text-[10.5px] text-slate-400">Pilih beberapa foto dari sudut depan, samping, kemasan, atau stiker label.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 6: PAJAK & AKUNTANSI (1508-1509)       -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'accounting'" class="space-y-5">
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-6">

                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🏛️</span>
                        <span>Perpajakan (DJP e-Faktur) & Konfigurasi Buku Besar (GL)</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Integrasi otomatis dengan jurnal umum akuntansi double-entry dan pelaporan faktur pajak pertambahan nilai (PPN).
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                    <!-- JENIS PAJAK -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Skema Pajak Produk</label>
                        @php
                            $currentTaxType = old('tax_type', $isEdit ? $product->tax_type : 'NON');
                        @endphp
                        <select name="tax_type"
                                class="w-full px-3.5 py-2.5 border-2 border-slate-200 rounded-xl text-xs font-bold focus:border-blue-600 focus:outline-hidden bg-white">
                            <option value="NON" {{ $currentTaxType === 'NON' ? 'selected' : '' }}>Non Pajak (Bebas PPN 0%)</option>
                            <option value="PPN" {{ $currentTaxType === 'PPN' ? 'selected' : '' }}>PPN Standar (11% / 12% Sesuai Tarif Berlaku)</option>
                            <option value="PPN_MEWAH" {{ $currentTaxType === 'PPN_MEWAH' ? 'selected' : '' }}>PPN + PPnBM (Pajak Penjualan Barang Mewah)</option>
                        </select>
                        <p class="text-[10.5px] text-slate-400">Mengikuti konfigurasi PKP / Non-PKP toko di AGENTS.md Section 5.</p>
                    </div>

                    <!-- KODE REFERENSI DJP E-FAKTUR -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Kode Barang DJP (e-Faktur)</label>
                        <input type="text"
                               name="tax_reference_code"
                               value="{{ old('tax_reference_code', $isEdit ? $product->tax_reference_code : '') }}"
                               placeholder="Contoh: 000000 / Kode Klasifikasi DJP"
                               class="w-full px-3.5 py-2.5 border-2 border-slate-200 rounded-xl text-xs font-mono focus:border-blue-600 focus:outline-hidden bg-white">
                        <p class="text-[10.5px] text-slate-400">Kode klasifikasi komoditas e-Faktur Pajak Keluaran.</p>
                    </div>

                    <!-- AKUN GL DEFAULT -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-800">Akun Buku Besar (GL Default)</label>
                        <div class="p-3 bg-slate-50 border-2 border-slate-200 rounded-xl text-xs text-slate-700 space-y-1 font-medium">
                            <div>Persediaan: <span class="font-mono font-bold text-slate-900">11300 - Persediaan Barang</span></div>
                            <div>Penjualan: <span class="font-mono font-bold text-slate-900">41000 - Pendapatan Penjualan</span></div>
                            <div>HPP: <span class="font-mono font-bold text-slate-900">51000 - Beban Pokok Penjualan</span></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 3. BOTTOM STICKY FLOATING ACTION BAR                                      -->
        <!-- ========================================================================= -->
        <div class="sticky bottom-4 z-40 bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-4 shadow-xl flex items-center justify-between gap-4">
            <div class="text-xs text-slate-600 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Tekan <kbd class="px-2 py-0.5 bg-slate-800 text-white font-mono rounded text-[10.5px] font-bold">F10</kbd> untuk Simpan langsung, atau <kbd class="px-2 py-0.5 bg-slate-200 text-slate-700 font-mono rounded text-[10.5px] font-bold">Esc</kbd> untuk Batal.</span>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('master.products') }}"
                   class="px-4 py-2 border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl transition-all cursor-pointer">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs rounded-xl shadow-md transition-all active:scale-95 cursor-pointer">
                    💾 Simpan Data Item [F10]
                </button>
            </div>
        </div>

    </form>
</div>

<!-- JAVASCRIPT / ALPINE LOGIC -->
<script>
function itemForm(config) {
    return {
        activeTab: 'general', // Default mulai dari Tab 1: Data Umum
        selectedType: '{{ old('type', $isEdit ? $product->type : 'BARANG') }}',
        saleStatus: '{{ old('sale_status', $isEdit ? $product->sale_status : 'ACTIVE') }}',
        currentStock: config.currentStock || 0,
        costPrice: config.costPrice || 0,
        currentSku: config.currentSku || '',
        productName: config.productName || '',
        customerTiersList: config.customerTiersList || [],
        masterUnits: config.masterUnits || [],
        units: config.units || [],
        bomComponents: config.bomComponents || [],
        componentProductsList: config.componentProductsList || [],
        mainImagePreview: null,

        tracksInventory() {
            return ['BARANG', 'RAKITAN_PROSES'].includes(this.selectedType);
        },

        onTypeChange() {
            if (this.selectedType === 'BIAYA') {
                this.saleStatus = 'BUY_ONLY';
            }
            if (!this.tracksInventory() && this.activeTab === 'dimensions') {
                this.activeTab = 'general';
            }
            if (['RAKITAN_PROSES', 'RAKITAN_NON_PROSES'].includes(this.selectedType) && this.bomComponents.length === 0) {
                this.addBomRow();
            }
        },

        addBomRow() {
            const firstProd = this.componentProductsList[0] || null;
            const firstUnit = (firstProd && firstProd.units && firstProd.units[0]) ? firstProd.units[0].id : '';
            this.bomComponents.push({
                component_product_id: firstProd ? firstProd.id : '',
                component_product_unit_id: firstUnit,
                quantity: 1,
            });
        },

        removeBomRow(idx) {
            this.bomComponents.splice(idx, 1);
        },

        onBomProductChange(idx) {
            const row = this.bomComponents[idx];
            const units = this.getComponentUnits(row.component_product_id);
            row.component_product_unit_id = units.length > 0 ? units[0].id : '';
        },

        getComponentUnits(productId) {
            const found = this.componentProductsList.find(p => p.id === productId);
            return found && found.units ? found.units : [];
        },

        getBomRowCost(row) {
            const prod = this.componentProductsList.find(p => p.id === row.component_product_id);
            if (!prod) return 0;
            const unit = (prod.units || []).find(u => u.id === row.component_product_unit_id);
            const conv = unit ? (parseFloat(unit.conversion_factor) || 1) : 1;
            return (parseFloat(prod.cost_price) || 0) * conv * (parseFloat(row.quantity) || 0);
        },

        getTotalBomCost() {
            return this.bomComponents.reduce((sum, r) => sum + this.getBomRowCost(r), 0);
        },

        syncCostPriceFromBom() {
            const total = this.getTotalBomCost();
            if (total > 0) {
                this.costPrice = Math.round(total);
                this.recalculateAllUnits();
            }
        },

        init() {
            // Hotkeys Alt+1 to Alt+6 for switching tabs
            window.addEventListener('keydown', (e) => {
                if (e.altKey) {
                    if (e.key === '1') { this.activeTab = 'general'; e.preventDefault(); }
                    if (e.key === '2') { this.activeTab = 'pricing'; e.preventDefault(); }
                    if (e.key === '3') { this.activeTab = 'dimensions'; e.preventDefault(); }
                    if (e.key === '4') { this.activeTab = 'discounts'; e.preventDefault(); }
                    if (e.key === '5') { this.activeTab = 'gallery'; e.preventDefault(); }
                    if (e.key === '6') { this.activeTab = 'accounting'; e.preventDefault(); }
                }
            });

            // Initial calculation
            this.recalculateAllUnits();
        },

        generateRandomSku() {
            const rand = Math.random().toString(36).substring(2, 6).toUpperCase();
            const dateStr = new Date().toISOString().slice(2, 10).replace(/-/g, '');
            this.currentSku = `PRD-${dateStr}-${rand}`;
        },

        addUnitRow() {
            const nextIdx = this.units.length;
            const defaultTiers = {};
            this.customerTiersList.forEach(t => {
                defaultTiers[t.id] = {
                    tier_name: t.name,
                    tier_code: t.code,
                    margin: parseFloat(t.default_margin_percentage || 10),
                    price: 0
                };
            });

            const candidateCodes = ['DUS', 'PACK', 'BOX', 'BAL', 'KARTON', 'LUSIN', 'SAK', 'BKS', 'IKAT', 'PCS'];
            const usedCodes = this.units.map(u => (u.unit_code || '').toUpperCase());
            let suggestedCode = 'DUS';
            for (const cand of candidateCodes) {
                if (!usedCodes.includes(cand)) {
                    suggestedCode = cand;
                    break;
                }
            }
            const suggestedConversion = suggestedCode === 'DUS' ? 24 : (suggestedCode === 'PACK' ? 10 : (suggestedCode === 'BOX' ? 12 : 50));
            const suggestedName = suggestedCode === 'DUS' ? 'Dus Kemasan Besar' : (suggestedCode === 'PACK' ? 'Pack Isi 10' : (suggestedCode === 'BOX' ? 'Box Karton' : suggestedCode));

            this.units.push({
                id: '',
                unit_code: suggestedCode,
                unit_name: suggestedName,
                conversion_factor: suggestedConversion,
                is_base_unit: false,
                is_active: true,
                barcode: '',
                tier_prices: defaultTiers,
                tiered_prices: [
                    { min_qty: 12, margin: 12, price: 0 }
                ],
                weight_gram: 0,
                length_cm: 0,
                width_cm: 0,
                height_cm: 0,
                point_reward: 0,
                commission_rate: 0,
                discount_1: 0,
                discount_2: 0,
                discount_3: 0,
                discount_4: 0,
            });

            this.recalculateUnit(this.units.length - 1);
        },

        removeUnitRow(index) {
            if (index > 0) {
                this.units.splice(index, 1);
            }
        },

        addTieredQtyRow(unitIndex) {
            if (!this.units[unitIndex].tiered_prices) {
                this.units[unitIndex].tiered_prices = [];
            }
            const currentLen = this.units[unitIndex].tiered_prices.length;
            const nextMinQty = currentLen === 0 ? 12 : (currentLen === 1 ? 60 : 120);
            const nextMargin = Math.max(2, 15 - (currentLen * 4));

            this.units[unitIndex].tiered_prices.push({
                min_qty: nextMinQty,
                margin: nextMargin,
                price: 0
            });
            this.updateTieredQtyPriceByMargin(unitIndex, this.units[unitIndex].tiered_prices.length - 1);
        },

        removeTieredQtyRow(unitIndex, qtyIndex) {
            this.units[unitIndex].tiered_prices.splice(qtyIndex, 1);
        },

        recalculateAllUnits() {
            this.units.forEach((u, i) => {
                this.recalculateUnit(i);
            });
        },

        recalculateUnit(index) {
            const u = this.units[index];
            const effHpp = (this.costPrice || 0) * (u.conversion_factor || 1);

            // Level Prices calculation
            if (u.tier_prices) {
                Object.keys(u.tier_prices).forEach(tierId => {
                    const tp = u.tier_prices[tierId];
                    if (effHpp > 0) {
                        if (!tp.price || tp.price === 0) {
                            tp.price = Math.round(effHpp * (1 + ((tp.margin || 0) / 100)));
                        } else {
                            tp.margin = parseFloat((((tp.price - effHpp) / effHpp) * 100).toFixed(1));
                        }
                    }
                });
            }

            // Tiered Quantity Prices calculation
            if (u.tiered_prices && Array.isArray(u.tiered_prices)) {
                u.tiered_prices.forEach((tq, qIdx) => {
                    if (effHpp > 0) {
                        if (!tq.price || tq.price === 0) {
                            tq.price = Math.round(effHpp * (1 + ((tq.margin || 0) / 100)));
                        } else {
                            tq.margin = parseFloat((((tq.price - effHpp) / effHpp) * 100).toFixed(1));
                        }
                    }
                });
            }
        },

        updateTierPriceByMargin(unitIndex, tierId) {
            const u = this.units[unitIndex];
            const effHpp = (this.costPrice || 0) * (u.conversion_factor || 1);
            if (effHpp > 0 && u.tier_prices && u.tier_prices[tierId]) {
                const margin = u.tier_prices[tierId].margin || 0;
                u.tier_prices[tierId].price = Math.round(effHpp * (1 + (margin / 100)));
            }
        },

        updateTierMarginByPrice(unitIndex, tierId) {
            const u = this.units[unitIndex];
            const effHpp = (this.costPrice || 0) * (u.conversion_factor || 1);
            if (effHpp > 0 && u.tier_prices && u.tier_prices[tierId]) {
                const price = u.tier_prices[tierId].price || 0;
                u.tier_prices[tierId].margin = parseFloat((((price - effHpp) / effHpp) * 100).toFixed(1));
            }
        },

        updateTieredQtyPriceByMargin(unitIndex, qIdx) {
            const u = this.units[unitIndex];
            const effHpp = (this.costPrice || 0) * (u.conversion_factor || 1);
            if (effHpp > 0 && u.tiered_prices && u.tiered_prices[qIdx]) {
                const margin = u.tiered_prices[qIdx].margin || 0;
                u.tiered_prices[qIdx].price = Math.round(effHpp * (1 + (margin / 100)));
            }
        },

        updateTieredQtyMarginByPrice(unitIndex, qIdx) {
            const u = this.units[unitIndex];
            const effHpp = (this.costPrice || 0) * (u.conversion_factor || 1);
            if (effHpp > 0 && u.tiered_prices && u.tiered_prices[qIdx]) {
                const price = u.tiered_prices[qIdx].price || 0;
                u.tiered_prices[qIdx].margin = parseFloat((((price - effHpp) / effHpp) * 100).toFixed(1));
            }
        },

        formatRupiah(amount) {
            return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
        },

        previewMainImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.mainImagePreview = URL.createObjectURL(file);
            }
        },

        submitForm() {
            const saleStatusSelect = document.getElementById('sale_status_select') || document.querySelector('select[name="sale_status"]');
            if (saleStatusSelect && saleStatusSelect.value === 'DISCONTINUED' && this.currentStock > 0) {
                alert('PERHATIAN: Barang tidak dapat di-discontinue karena masih memiliki sisa stok fisik (' + this.currentStock + ' unit). Sisa stok harus kosong (0) terlebih dahulu sebelum status dapat diubah menjadi Discontinue.');
                return;
            }

            // Validasi duplikasi kode satuan kemasan
            const codes = [];
            for (let i = 0; i < this.units.length; i++) {
                const c = (this.units[i].unit_code || '').trim().toUpperCase();
                if (!c) {
                    alert('PERHATIAN: Satuan / UOM pada baris ke-' + (i + 1) + ' wajib dipilih/diisi.');
                    return;
                }
                if (codes.includes(c)) {
                    alert('PERHATIAN: Satuan [' + c + '] digunakan lebih dari satu kali. Setiap baris kemasan wajib menggunakan satuan yang berbeda.');
                    return;
                }
                codes.push(c);
            }

            document.getElementById('productMainForm').submit();
        },

        cancelForm() {
            window.location.href = '{{ route('master.products') }}';
        }
    };
}
</script>
@endsection
