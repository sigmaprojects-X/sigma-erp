@extends('layouts.app')

@section('title', 'Kartu Stok Persediaan')
@section('tab_title', 'Kartu Stok')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full"
     x-data="stockCardApp()"
     @keydown.window.f1.prevent="lookupModal = true">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Kartu Stok Persediaan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>📦 Kartu Stok & Buku Besar Mutasi Barang</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="lookupModal = true"
                    class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🔍 Cari Item Lain [F1]</span>
            </button>
            <button type="button"
                    onclick="window.print()"
                    class="px-3.5 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️ Cetak Kartu Stok</span>
            </button>
            <a href="{{ route('master.products') }}"
               class="px-3.5 py-2 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors">
                <span>Daftar Item &rarr;</span>
            </a>
        </div>
    </div>

    <!-- FILTER BAR (PILIH PRODUK & TANGGAL) -->
    <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs">
        <form id="stockCardForm" method="GET" action="{{ route('master.stock-card') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            
            <!-- 1. PILIH PRODUK (AUTOCOMPLETE & MODAL LOOKUP) -->
            <div class="sm:col-span-6 relative">
                <label class="block font-bold text-slate-700 mb-1 flex items-center justify-between">
                    <span>Pilih Produk / Item :</span>
                    <span class="text-[10px] text-indigo-600 font-mono font-normal">Tekan [F1] untuk buka jendela pencarian</span>
                </label>
                
                <input type="hidden" name="product_id" :value="selectedId">

                <div class="flex items-center gap-1.5">
                    <div class="relative flex-1">
                        <input type="text"
                               x-model="searchQuery"
                               @focus="dropdownOpen = true"
                               @click="dropdownOpen = true"
                               placeholder="Ketik nama, SKU, atau barcode produk..."
                               class="w-full pl-8 pr-3 py-2 border border-slate-300 rounded-lg font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                        <span class="absolute left-2.5 top-2.5 text-slate-400">🔍</span>
                        
                        <!-- DROPDOWN SUGGESTIONS LIST -->
                        <div x-show="dropdownOpen"
                             @click.away="dropdownOpen = false"
                             x-cloak
                             class="absolute left-0 right-0 mt-1 bg-white border border-slate-300 rounded-xl shadow-xl z-50 max-h-64 overflow-y-auto divide-y divide-slate-100 text-xs">
                            <template x-for="prod in filteredDropdownProducts" :key="prod.id">
                                <div @click="selectProduct(prod)"
                                     class="p-2.5 hover:bg-blue-50 cursor-pointer flex items-center justify-between transition-colors">
                                    <div>
                                        <div class="font-extrabold text-slate-900" x-text="prod.name"></div>
                                        <div class="text-[10.5px] text-slate-500 flex items-center gap-2 mt-0.5 font-mono">
                                            <span class="font-bold text-blue-700" x-text="prod.sku"></span>
                                            <span>|</span>
                                            <span x-text="prod.barcode !== '-' ? prod.barcode : 'No Barcode'"></span>
                                            <span>|</span>
                                            <span class="bg-slate-100 px-1.5 py-0.2 rounded font-sans text-slate-600" x-text="prod.category"></span>
                                        </div>
                                    </div>
                                    <div class="text-right pl-2">
                                        <span class="text-[10px] text-slate-400 block">Stok Fisik:</span>
                                        <span class="font-mono font-black text-xs text-slate-800" x-text="prod.stock + ' ' + prod.unit"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="filteredDropdownProducts.length === 0" class="p-4 text-center text-slate-400 italic">
                                Tidak ada produk yang cocok dengan pencarian.
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Browse Modal [...] -->
                    <button type="button"
                            @click="lookupModal = true"
                            class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg shadow-xs flex items-center gap-1 cursor-pointer transition-colors"
                            title="Buka Daftar Item Lengkap [F1]">
                        <span>🔍 Browse...</span>
                    </button>
                </div>
            </div>

            <!-- 2. TANGGAL AWAL -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Dari Tanggal:</label>
                <input type="date"
                       id="inputStartDate"
                       name="start_date"
                       value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full px-2.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
            </div>

            <!-- 3. TANGGAL AKHIR -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Sampai Tanggal:</label>
                <input type="date"
                       id="inputEndDate"
                       name="end_date"
                       value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full px-2.5 py-2 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
            </div>

            <!-- 4. TOMBOL FILTER & SHORTCUT RANGE -->
            <div class="sm:col-span-2 flex flex-col justify-end gap-1">
                <button type="submit"
                        class="w-full py-2 px-3 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center transition-colors cursor-pointer"
                        title="Tampilkan Kartu Stok">
                    <span>Lihat Kartu Stok</span>
                </button>
                <div class="flex items-center gap-1 text-[10px]">
                    <button type="button" @click="setDateRange('today')" class="flex-1 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-slate-600 font-semibold cursor-pointer">Hari Ini</button>
                    <button type="button" @click="setDateRange('month')" class="flex-1 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-slate-600 font-semibold cursor-pointer">Bulan Ini</button>
                    <button type="button" @click="setDateRange('year')" class="flex-1 py-0.5 bg-slate-100 hover:bg-slate-200 rounded text-slate-600 font-semibold cursor-pointer">Tahun Ini</button>
                </div>
            </div>
        </form>
    </div>

    @if($selectedProduct)
        <!-- SELECTED ITEM SUMMARY BANNER -->
        <div class="bg-gradient-to-r from-slate-900 to-blue-950 text-white rounded-xl p-4 shadow-md flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-lg bg-blue-600/30 border border-blue-400/40 flex items-center justify-center text-2xl">
                    📦
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="px-2 py-0.5 bg-blue-500 text-white rounded font-mono font-bold text-xs">
                            {{ $selectedProduct->sku }}
                        </span>
                        <span class="text-xs text-blue-200">
                            Barcode: <strong class="font-mono text-white">{{ $selectedProduct->barcodes->first()?->barcode ?? '-' }}</strong>
                        </span>
                        <span class="px-2 py-0.5 bg-slate-800 text-slate-300 rounded text-[10.5px]">
                            {{ $selectedProduct->category?->name ?? $selectedProduct->category ?? 'Sembako' }}
                        </span>
                    </div>
                    <h2 class="text-base font-black tracking-wide">{{ $selectedProduct->name }}</h2>
                    <p class="text-[11px] text-slate-300 mt-0.5">
                        Satuan Dasar: <strong class="text-white">{{ $selectedProduct->baseUnit?->unit_code ?? 'PCS' }}</strong> |
                        Min. Alert: <strong class="text-amber-300">{{ number_format($selectedProduct->min_stock_alert, 0) }}</strong> |
                        HPP Saat Ini: <strong class="text-emerald-300">Rp {{ number_format($selectedProduct->cost_price, 0, ',', '.') }}</strong>
                    </p>
                </div>
            </div>
            <div class="bg-slate-800/80 border border-slate-700 rounded-lg p-2.5 px-4 text-right">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Sisa Stok Fisik Sekarang:</span>
                <span class="font-mono font-black text-xl text-emerald-400">
                    {{ number_format($selectedProduct->stocks->first()?->available_quantity ?? $selectedProduct->total_quantity ?? 0, 2) }}
                    <span class="text-xs font-semibold text-slate-300">{{ $selectedProduct->baseUnit?->unit_code ?? 'PCS' }}</span>
                </span>
            </div>
        </div>

        <!-- KPI OVERVIEW PERIODE INI -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-white border border-slate-200 rounded-xl p-3 shadow-2xs">
                <span class="text-[10.5px] font-bold text-slate-400 block uppercase">Saldo Awal</span>
                <span class="font-mono font-black text-base text-slate-800">
                    {{ number_format($openingBalance, 2) }}
                </span>
                <span class="text-[10px] text-slate-400 block font-semibold">Sebelum {{ $startDate->format('d/m/Y') }}</span>
            </div>

            <div class="bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 shadow-2xs">
                <span class="text-[10.5px] font-bold text-emerald-700 block uppercase">Total Masuk (+)</span>
                <span class="font-mono font-black text-base text-emerald-800">
                    +{{ number_format($totalIn, 2) }}
                </span>
                <span class="text-[10px] text-emerald-600 block font-semibold">Beli / Opname / Retur</span>
            </div>

            <div class="bg-rose-50/70 border border-rose-200 rounded-xl p-3 shadow-2xs">
                <span class="text-[10.5px] font-bold text-rose-700 block uppercase">Total Keluar (-)</span>
                <span class="font-mono font-black text-base text-rose-800">
                    -{{ number_format($totalOut, 2) }}
                </span>
                <span class="text-[10px] text-rose-600 block font-semibold">Penjualan Kasir POS</span>
            </div>

            <div class="bg-blue-50/70 border border-blue-200 rounded-xl p-3 shadow-2xs">
                <span class="text-[10.5px] font-bold text-blue-700 block uppercase">Saldo Akhir</span>
                <span class="font-mono font-black text-base text-blue-900">
                    {{ number_format($closingBalance, 2) }}
                </span>
                <span class="text-[10px] text-blue-600 block font-semibold">Per {{ $endDate->format('d/m/Y') }}</span>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 shadow-2xs col-span-2 sm:col-span-1">
                <span class="text-[10.5px] font-bold text-slate-400 block uppercase">HPP (Moving Avg)</span>
                <span class="font-mono font-black text-base text-slate-900">
                    Rp {{ number_format($selectedProduct->cost_price, 0, ',', '.') }}
                </span>
                <span class="text-[10px] text-slate-400 block font-semibold">Valuasi Resmi Sistem</span>
            </div>
        </div>

        <!-- TABEL BUKU BESAR MUTASI STOK -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-800 text-xs">
                        Buku Mutasi: <strong>{{ $selectedProduct->name }}</strong> [{{ $selectedProduct->sku }}]
                    </span>
                </div>
                <span class="text-[11px] text-slate-500 font-mono">
                    Periode: {{ $startDate->format('d M Y') }} s/d {{ $endDate->format('d M Y') }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-100/90 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="py-2.5 px-3 w-10 text-center">No</th>
                            <th class="py-2.5 px-3 w-36">Waktu Mutasi</th>
                            <th class="py-2.5 px-3 w-36">No. Bukti / Referensi</th>
                            <th class="py-2.5 px-3 w-36">Tipe Mutasi</th>
                            <th class="py-2.5 px-3 text-right text-emerald-700">Masuk (+)</th>
                            <th class="py-2.5 px-3 text-right text-rose-700">Keluar (-)</th>
                            <th class="py-2.5 px-3 text-right text-blue-800 font-black">Saldo Akhir</th>
                            <th class="py-2.5 px-3 w-36">Petugas / Operator</th>
                            <th class="py-2.5 px-3">Keterangan / Memo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <!-- Baris Saldo Awal -->
                        <tr class="bg-slate-50/60 font-semibold text-slate-600">
                            <td class="py-2 px-3 text-center">-</td>
                            <td class="py-2 px-3 font-mono text-[11px]">{{ $startDate->format('Y-m-d 00:00') }}</td>
                            <td class="py-2 px-3 font-mono text-[11px] text-slate-500">SALDO-AWAL</td>
                            <td class="py-2 px-3 text-slate-500">Saldo Awal Periode</td>
                            <td class="py-2 px-3 text-right font-mono text-slate-400">-</td>
                            <td class="py-2 px-3 text-right font-mono text-slate-400">-</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-slate-800">{{ number_format($openingBalance, 2) }}</td>
                            <td class="py-2 px-3 text-slate-400 text-[10.5px] italic">-</td>
                            <td class="py-2 px-3 text-slate-400 text-[11px] italic">Saldo bawaan sebelum tanggal filter</td>
                        </tr>

                        @forelse($mutations as $idx => $m)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                    {{ $idx + 1 }}
                                </td>
                                <td class="py-2.5 px-3 font-mono text-[11px] text-slate-600">
                                    {{ $m->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-800">
                                    {{ $m->reference_number }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @if(str_contains($m->mutation_type, 'SALE') || str_contains($m->reference_number, 'INV-'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                            🛒 Penjualan POS
                                        </span>
                                    @elseif(str_contains($m->mutation_type, 'PURCHASE') || str_contains($m->reference_number, 'GR-'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            📥 Penerimaan PO
                                        </span>
                                    @elseif(str_contains($m->mutation_type, 'ADJUSTMENT') || str_contains($m->reference_number, 'INIT-'))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                            ⚙️ Stock Opname / Saldo Awal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                            {{ $m->mutation_type }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">
                                    {{ $m->quantity > 0 ? '+'.number_format($m->quantity, 2) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-rose-700">
                                    {{ $m->quantity < 0 ? number_format(abs($m->quantity), 2) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-black text-blue-900">
                                    {{ number_format($m->qty_after, 2) }}
                                </td>
                                <td class="py-2.5 px-3">
                                    @if($m->creator)
                                        <div class="font-bold text-slate-800 dark:text-slate-200 text-xs flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                            <span class="truncate max-w-[120px]">{{ $m->creator->name }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 capitalize pl-3">{{ $m->creator->role }}</div>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Sistem</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                    {{ $m->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 text-center text-slate-400 text-xs">
                                    Tidak ada mutasi transaksi stok untuk barang ini pada rentang tanggal yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL CARI & PILIH ITEM [F1] (LIGHTNING-FAST SEARCH MODAL) -->
    <!-- ============================================================== -->
    <div x-show="lookupModal"
         x-cloak
         @keydown.escape.window="lookupModal = false"
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-4xl overflow-hidden flex flex-col max-h-[85vh]"
             @click.away="lookupModal = false">
            
            <!-- MODAL HEADER -->
            <div class="px-5 py-3.5 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🔍</span>
                    <div>
                        <h3 class="text-sm font-black">Pilih Produk / Item untuk Kartu Stok [F1]</h3>
                        <p class="text-[11px] text-slate-400">Klik baris barang yang ingin ditampilkan kartu mutasinya</p>
                    </div>
                </div>
                <button type="button" @click="lookupModal = false" class="text-slate-400 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <!-- MODAL SEARCH INPUT -->
            <div class="p-3.5 bg-slate-50 border-b border-slate-200">
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                    <input type="text"
                           x-model="modalSearch"
                           x-ref="modalSearchInput"
                           placeholder="Filter cepat berdasarkan SKU, Nama Barang, Barcode, atau Kategori..."
                           class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                </div>
            </div>

            <!-- MODAL ITEMS TABLE -->
            <div class="flex-1 overflow-y-auto p-2">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-100 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase sticky top-0 z-10">
                        <tr>
                            <th class="py-2 px-3 w-32">Kode SKU</th>
                            <th class="py-2 px-3">Nama Produk</th>
                            <th class="py-2 px-3 w-28">Kategori</th>
                            <th class="py-2 px-3 w-24">Satuan</th>
                            <th class="py-2 px-3 w-32 text-right">Stok Fisik</th>
                            <th class="py-2 px-3 w-24 text-center">Pilih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <template x-for="p in filteredModalProducts" :key="p.id">
                            <tr @click="selectProduct(p)"
                                class="hover:bg-indigo-50/70 cursor-pointer transition-colors group">
                                <td class="py-2.5 px-3 font-mono font-bold text-blue-700" x-text="p.sku"></td>
                                <td class="py-2.5 px-3">
                                    <div class="font-extrabold text-slate-900 group-hover:text-indigo-700" x-text="p.name"></div>
                                    <div class="text-[10px] text-slate-400 font-mono" x-text="'Barcode: ' + p.barcode"></div>
                                </td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10.5px]" x-text="p.category"></span>
                                </td>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-700" x-text="p.unit"></td>
                                <td class="py-2.5 px-3 text-right font-mono font-black text-slate-800" x-text="p.stock"></td>
                                <td class="py-2.5 px-3 text-center">
                                    <button type="button"
                                            class="px-2.5 py-1 bg-indigo-600 group-hover:bg-indigo-700 text-white font-bold rounded text-[10.5px] transition-colors shadow-2xs">
                                        Pilih ↵
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- MODAL FOOTER -->
            <div class="p-3 bg-slate-100 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <span x-text="'Menampilkan ' + filteredModalProducts.length + ' item barang'"></span>
                <button type="button"
                        @click="lookupModal = false"
                        class="px-4 py-1.5 bg-white border border-slate-300 hover:bg-slate-200 text-slate-700 font-bold rounded-lg cursor-pointer">
                    Tutup [Esc]
                </button>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
    function stockCardApp() {
        return {
            lookupModal: false,
            dropdownOpen: false,
            searchQuery: @json($selectedProduct ? '[' . $selectedProduct->sku . '] ' . $selectedProduct->name : ''),
            selectedId: @json($selectedProductId),
            modalSearch: '',
            products: @json($productsJson ?? []),

            get filteredDropdownProducts() {
                if (!this.searchQuery) return this.products.slice(0, 10);
                const q = this.searchQuery.toLowerCase();
                return this.products.filter(p => 
                    p.sku.toLowerCase().includes(q) ||
                    p.name.toLowerCase().includes(q) ||
                    p.barcode.toLowerCase().includes(q)
                ).slice(0, 10);
            },

            get filteredModalProducts() {
                if (!this.modalSearch) return this.products;
                const q = this.modalSearch.toLowerCase();
                return this.products.filter(p => 
                    p.sku.toLowerCase().includes(q) ||
                    p.name.toLowerCase().includes(q) ||
                    p.category.toLowerCase().includes(q) ||
                    p.barcode.toLowerCase().includes(q)
                );
            },

            selectProduct(prod) {
                this.selectedId = prod.id;
                this.searchQuery = `[${prod.sku}] ${prod.name}`;
                this.dropdownOpen = false;
                this.lookupModal = false;
                
                // Submit form
                this.$nextTick(() => {
                    document.getElementById('stockCardForm').submit();
                });
            },

            setDateRange(type) {
                const today = new Date();
                let start = new Date();
                let end = new Date();

                if (type === 'today') {
                    start = today;
                    end = today;
                } else if (type === 'month') {
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                    end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                } else if (type === 'year') {
                    start = new Date(today.getFullYear(), 0, 1);
                    end = new Date(today.getFullYear(), 11, 31);
                }

                const formatDate = (d) => {
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                };

                document.getElementById('inputStartDate').value = formatDate(start);
                document.getElementById('inputEndDate').value = formatDate(end);
                document.getElementById('stockCardForm').submit();
            }
        }
    }
</script>
@endpush
@endsection
