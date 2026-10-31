@extends('layouts.app')

@section('title', 'Cetak Barcode & Label Rak Gondola')
@section('tab_title', 'Cetak Barcode & Rak')

@section('content')
<div class="space-y-4"
     x-data="barcodePrintApp({
         initialProducts: {{ json_encode($products->map(function($p) {
             $barcode = $p->barcodes->firstWhere('is_primary', true)?->barcode ?? $p->barcodes->first()?->barcode ?? $p->sku;
             $unit = $p->units->firstWhere('is_base_unit', true) ?? $p->units->first();
             $tierRetail = $p->prices->firstWhere('customerTier.code', 'RETAIL') ?? $p->prices->first();
             $tierGrosir = $p->prices->firstWhere('customerTier.code', 'GROSIR');
             return [
                 'id' => $p->id,
                 'sku' => $p->sku,
                 'name' => $p->name,
                 'category_name' => $p->category?->name ?? $p->category ?? '-',
                 'barcode' => $barcode,
                 'unit_code' => $unit?->unit_code ?? 'PCS',
                 'price_retail' => (float)($tierRetail?->price ?? 0),
                 'price_grosir' => (float)($tierGrosir?->price ?? 0),
                 'min_qty_grosir' => (float)($tierGrosir?->min_quantity ?? 3),
                 'rack' => $p->rack_location ?? '-',
             ];
         })) }},
         preselected: {{ $preselectedProduct ? json_encode([
             'id' => $preselectedProduct->id,
             'sku' => $preselectedProduct->sku,
             'name' => $preselectedProduct->name,
             'category_name' => $preselectedProduct->category?->name ?? $preselectedProduct->category ?? '-',
             'barcode' => $preselectedProduct->barcodes->firstWhere('is_primary', true)?->barcode ?? $preselectedProduct->barcodes->first()?->barcode ?? $preselectedProduct->sku,
             'unit_code' => $preselectedProduct->units->firstWhere('is_base_unit', true)?->unit_code ?? 'PCS',
             'price_retail' => (float)($preselectedProduct->prices->firstWhere('customerTier.code', 'RETAIL')?->price ?? 0),
             'price_grosir' => (float)($preselectedProduct->prices->firstWhere('customerTier.code', 'GROSIR')?->price ?? 0),
             'min_qty_grosir' => (float)($preselectedProduct->prices->firstWhere('customerTier.code', 'GROSIR')?->min_quantity ?? 3),
             'rack' => $preselectedProduct->rack_location ?? '-',
         ]) : 'null' }}
     })">

    <!-- CSS PRINT OVERRIDE -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printableArea, #printableArea * {
                visibility: visible;
            }
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
                background: white;
            }
            header, aside, footer, nav, .no-print {
                display: none !important;
            }
        }

        /* STIKER RAK GONDOLA (A4 SHEET 3 COLUMNS) */
        .shelf-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin: 0 auto;
        }

        .shelf-card {
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 8px 10px;
            height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: white;
            page-break-inside: avoid;
            box-sizing: border-box;
        }

        /* STIKER PRODUK KECIL (33x15mm atau continuous thermal) */
        .item-sticker-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin: 0 auto;
        }

        .item-sticker-card {
            border: 1px dashed #64748b;
            border-radius: 4px;
            padding: 6px;
            height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-align: center;
            background: white;
            page-break-inside: avoid;
            box-sizing: border-box;
        }
    </style>

    <!-- HEADER TITLE & BREADCRUMB (NO-PRINT) -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('master.products') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Cetak Barcode & Label</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏷️ Pusat Cetak Barcode & Price Tag Rak Gondola</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="printLabels()"
                    :disabled="queue.length === 0"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-2 transition-all active:scale-95 cursor-pointer">
                <span>🖨️ Cetak Sekarang</span>
                <span class="px-2 py-0.5 bg-emerald-800 rounded-full text-[10px]" x-text="totalLabelsCount() + ' Lembar'"></span>
            </button>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN WORKSPACE (NO-PRINT) -->
    <div class="no-print grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- KOLOM KIRI: KATALOG & FILTER PRODUK (lg:col-span-5) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="font-extrabold text-slate-900 text-sm flex items-center gap-1.5">
                        <span>📦 Pilih Item Produk</span>
                    </h2>
                    <span class="text-[11px] text-slate-400" x-text="filteredProducts.length + ' item ditemukan'"></span>
                </div>

                <!-- PENCARIAN & FILTER KATEGORI -->
                <div class="space-y-2">
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Cari nama, SKU, atau barcode..."
                               class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                    </div>

                    <div class="flex items-center gap-2">
                        <select x-model="selectedCategory"
                                class="w-full p-2 border border-slate-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            <option value="">Semua Kategori Produk</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="button"
                                @click="addAllFilteredToQueue()"
                                title="Tambahkan semua item hasil filter ke antrean cetak"
                                class="px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs rounded-xl border border-blue-200 whitespace-nowrap cursor-pointer">
                            + Semua
                        </button>
                    </div>
                </div>

                <!-- DAFTAR ITEM HASIL PENCARIAN -->
                <div class="overflow-y-auto max-h-[460px] space-y-2 pr-1">
                    <template x-for="item in filteredProducts" :key="item.id">
                        <div class="p-2.5 border border-slate-100 hover:border-blue-300 rounded-xl bg-slate-50/50 hover:bg-blue-50/30 transition-all flex items-center justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 text-xs truncate" x-text="item.name"></div>
                                <div class="flex items-center gap-2 text-[10px] text-slate-500 font-mono mt-0.5">
                                    <span class="font-bold text-blue-700" x-text="item.sku"></span>
                                    <span>•</span>
                                    <span x-text="item.barcode || '-'"></span>
                                    <span>•</span>
                                    <span class="px-1.5 py-0.2 bg-slate-200 rounded text-slate-700 font-sans" x-text="item.unit_code"></span>
                                </div>
                                <div class="text-[11px] font-black text-slate-800 mt-1">
                                    Rp <span x-text="formatRupiah(item.price_retail)"></span>
                                </div>
                            </div>
                            <button type="button"
                                    @click="addToQueue(item)"
                                    class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-xs transition-all active:scale-95 cursor-pointer flex items-center gap-1">
                                <span>➕</span>
                                <span>Pilih</span>
                            </button>
                        </div>
                    </template>

                    <div x-show="filteredProducts.length === 0" class="py-8 text-center text-slate-400 text-xs">
                        Tidak ada item yang cocok dengan pencarian.
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: PENGATURAN CETAK & ANTREAN ITEM (lg:col-span-7) -->
        <div class="lg:col-span-7 space-y-4">

            <!-- KARTU PENGATURAN FORMAT LABEL -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h2 class="font-extrabold text-slate-900 text-sm flex items-center gap-1.5">
                        <span>⚙️ Konfigurasi Format Label</span>
                    </h2>
                    <button type="button"
                            @click="clearQueue()"
                            x-show="queue.length > 0"
                            class="text-xs text-rose-600 hover:text-rose-800 font-bold cursor-pointer">
                        Kosongkan Antrean (✕)
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipe Label :</label>
                        <select x-model="labelType" class="w-full p-2 border border-slate-300 rounded-xl font-bold text-xs bg-slate-50 focus:ring-2 focus:ring-blue-200">
                            <option value="SHELF">Price Tag Rak Gondola</option>
                            <option value="STICKER">Stiker Barcode Produk</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Toko di Label :</label>
                        <input type="text"
                               x-model="storeName"
                               class="w-full p-2 border border-slate-300 rounded-xl font-bold text-xs focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Isi Semua Qty Cetak :</label>
                        <div class="flex items-center gap-1.5">
                            <input type="number"
                                   min="1"
                                   max="100"
                                   x-model="bulkQty"
                                   class="w-full p-2 border border-slate-300 rounded-xl font-bold text-xs text-center">
                            <button type="button"
                                    @click="applyBulkQty()"
                                    class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl whitespace-nowrap cursor-pointer">
                                Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KARTU DAFTAR ANTREAN PRODUK -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-1.5">
                        <span>📋 Antrean Item Siap Cetak</span>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[11px] font-black rounded-full" x-text="queue.length + ' Item'"></span>
                    </h3>
                </div>

                <div x-show="queue.length === 0" class="py-12 text-center text-slate-400 space-y-2">
                    <div class="text-3xl">📭</div>
                    <div class="text-xs font-semibold">Antrean cetak masih kosong.</div>
                    <div class="text-[11px] text-slate-400">Pilih item dari kolom kiri untuk mulai mencetak label rak atau stiker barcode.</div>
                </div>

                <div x-show="queue.length > 0" class="overflow-y-auto max-h-[300px] space-y-2">
                    <template x-for="(qItem, idx) in queue" :key="qItem.id">
                        <div class="p-3 border border-slate-200 rounded-xl bg-slate-50 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-bold text-slate-900 text-xs truncate" x-text="qItem.name"></div>
                                <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                    <span class="font-bold text-blue-700" x-text="qItem.sku"></span>
                                    <span>•</span>
                                    <span x-text="qItem.barcode"></span>
                                    <span>•</span>
                                    <span class="text-emerald-700 font-bold">Rp <span x-text="formatRupiah(qItem.price_retail)"></span></span>
                                </div>
                            </div>

                            <!-- ATUR QTY CETAK -->
                            <div class="flex items-center gap-1.5">
                                <span class="text-[11px] text-slate-500 font-semibold">Keping:</span>
                                <button type="button"
                                        @click="decreaseQty(idx)"
                                        class="w-7 h-7 bg-white border border-slate-300 rounded-lg font-bold text-xs text-slate-700 hover:bg-slate-100 cursor-pointer">
                                    -
                                </button>
                                <input type="number"
                                       min="1"
                                       max="200"
                                       x-model.number="qItem.qty"
                                       class="w-12 h-7 border border-slate-300 rounded-lg text-center font-bold text-xs">
                                <button type="button"
                                        @click="increaseQty(idx)"
                                        class="w-7 h-7 bg-white border border-slate-300 rounded-lg font-bold text-xs text-slate-700 hover:bg-slate-100 cursor-pointer">
                                    +
                                </button>
                                <button type="button"
                                        @click="removeFromQueue(idx)"
                                        class="ml-2 w-7 h-7 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg font-bold text-xs cursor-pointer"
                                        title="Hapus dari antrean">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- PRATINJAU LANGSUNG DI LAYAR (SCREEN PREVIEW) -->
            <div class="bg-slate-800 rounded-2xl p-4 text-white space-y-3 shadow-md">
                <div class="flex items-center justify-between border-b border-slate-700 pb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                        <span>👁️ Pratinjau Tampilan Cetak</span>
                    </span>
                    <span class="text-[11px] text-slate-400 font-mono">Format: <strong class="text-emerald-400" x-text="labelType === 'SHELF' ? 'Price Tag Rak Gondola (A4/Sheet)' : 'Stiker Barcode Produk'"></strong></span>
                </div>
                <div class="text-[11px] text-slate-400 leading-relaxed">
                    Tampilan di bawah adalah lembaran yang akan langsung tercetak saat Anda menekan tombol <strong>Cetak Sekarang</strong>. Barcode 1D tercetak tajam dan siap di-scan scanner kasir.
                </div>
            </div>

        </div>

    </div>

    <!-- ============================================================== -->
    <!-- WADAH CETAK (PRINTABLE AREA - VISIBLE ON SCREEN & PRINT)        -->
    <!-- ============================================================== -->
    <div id="printableArea" class="mt-4 p-4 bg-white rounded-2xl border border-slate-200">

        <!-- 1. MODE PRICE TAG RAK GONDOLA -->
        <template x-if="labelType === 'SHELF'">
            <div class="shelf-grid">
                <template x-for="tag in flattenedQueue()" :key="tag.uniqueKey">
                    <div class="shelf-card">
                        <!-- HEADER TAG -->
                        <div class="flex items-center justify-between border-b border-slate-900 pb-1">
                            <span class="font-black text-[11px] tracking-tight text-slate-900 truncate" x-text="storeName"></span>
                            <span class="text-[9px] font-mono font-bold text-slate-600" x-text="currentDateFormatted()"></span>
                        </div>

                        <!-- BODY TAG: NAMA PRODUK -->
                        <div class="my-1">
                            <div class="font-black text-[12px] leading-tight text-slate-900 line-clamp-2" x-text="tag.name"></div>
                            <div class="text-[9px] text-slate-600 font-semibold mt-0.5">
                                Satuan: <strong class="text-slate-900" x-text="tag.unit_code"></strong>
                                <span x-show="tag.rack && tag.rack !== '-'"> | Rak: <strong x-text="tag.rack"></strong></span>
                            </div>
                        </div>

                        <!-- FOOTER TAG: HARGA BESAR & BARCODE -->
                        <div class="pt-1 border-t border-slate-900 flex items-end justify-between">
                            <div>
                                <div class="text-[8px] uppercase tracking-wider text-slate-500 font-bold">Harga Eceran</div>
                                <div class="text-base font-black text-slate-900 leading-none">
                                    Rp <span x-text="formatRupiah(tag.price_retail)"></span>
                                </div>
                                <div x-show="tag.price_grosir > 0" class="text-[8.5px] font-bold text-slate-700 mt-0.5">
                                    Grosir: Rp <span x-text="formatRupiah(tag.price_grosir)"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-[8.5px] font-mono font-bold text-slate-800" x-text="tag.barcode || tag.sku"></div>
                                <div class="font-mono text-[16px] tracking-widest text-slate-900 leading-none" style="font-family: 'Courier New', monospace;">
                                    *<span x-text="tag.barcode || tag.sku"></span>*
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- 2. MODE STIKER BARCODE PRODUK -->
        <template x-if="labelType === 'STICKER'">
            <div class="item-sticker-grid">
                <template x-for="stk in flattenedQueue()" :key="stk.uniqueKey">
                    <div class="item-sticker-card">
                        <div class="text-[9px] font-extrabold uppercase text-slate-800 tracking-tight truncate" x-text="storeName"></div>
                        <div class="text-[10px] font-black text-slate-900 truncate leading-tight my-0.5" x-text="stk.name"></div>
                        <div class="my-auto py-1">
                            <div class="font-mono text-[18px] tracking-widest text-slate-900 leading-none" style="font-family: 'Courier New', monospace;">
                                *<span x-text="stk.barcode || stk.sku"></span>*
                            </div>
                            <div class="text-[8.5px] font-mono font-bold text-slate-700 mt-0.5" x-text="stk.barcode || stk.sku"></div>
                        </div>
                        <div class="text-[11px] font-black text-slate-900">
                            Rp <span x-text="formatRupiah(stk.price_retail)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>

    </div>

</div>

<!-- JAVASCRIPT STATE ENGINE -->
<script>
function barcodePrintApp(config) {
    return {
        products: config.initialProducts || [],
        searchQuery: '',
        selectedCategory: '',
        labelType: 'SHELF', // 'SHELF' atau 'STICKER'
        storeName: 'SIGMA MART ENTERPRISE',
        bulkQty: 1,
        queue: [],

        init() {
            if (config.preselected) {
                this.addToQueue(config.preselected);
            }
        },

        get filteredProducts() {
            return this.products.filter(item => {
                const q = this.searchQuery.toLowerCase().trim();
                const matchQuery = !q ||
                    (item.name && item.name.toLowerCase().includes(q)) ||
                    (item.sku && item.sku.toLowerCase().includes(q)) ||
                    (item.barcode && item.barcode.toLowerCase().includes(q));

                const matchCat = !this.selectedCategory || item.category_name === this.selectedCategory;

                return matchQuery && matchCat;
            });
        },

        addToQueue(item) {
            const existing = this.queue.find(q => q.id === item.id);
            if (existing) {
                existing.qty++;
            } else {
                this.queue.push({
                    ...item,
                    qty: 1
                });
            }
        },

        addAllFilteredToQueue() {
            this.filteredProducts.forEach(item => {
                this.addToQueue(item);
            });
        },

        increaseQty(idx) {
            this.queue[idx].qty++;
        },

        decreaseQty(idx) {
            if (this.queue[idx].qty > 1) {
                this.queue[idx].qty--;
            } else {
                this.removeFromQueue(idx);
            }
        },

        removeFromQueue(idx) {
            this.queue.splice(idx, 1);
        },

        clearQueue() {
            this.queue = [];
        },

        applyBulkQty() {
            const q = Math.max(1, parseInt(this.bulkQty) || 1);
            this.queue.forEach(item => {
                item.qty = q;
            });
        },

        totalLabelsCount() {
            return this.queue.reduce((acc, curr) => acc + (parseInt(curr.qty) || 0), 0);
        },

        flattenedQueue() {
            const result = [];
            this.queue.forEach(item => {
                const count = parseInt(item.qty) || 1;
                for (let i = 0; i < count; i++) {
                    result.push({
                        ...item,
                        uniqueKey: item.id + '_' + i
                    });
                }
            });
            return result;
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(num || 0);
        },

        currentDateFormatted() {
            const now = new Date();
            const d = String(now.getDate()).padStart(2, '0');
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const y = now.getFullYear();
            return `${d}/${m}/${y}`;
        },

        printLabels() {
            window.print();
        }
    };
}
</script>
@endsection
