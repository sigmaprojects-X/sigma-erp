@extends('layouts.app')

@section('title', 'Saldo Awal Item & Persediaan — SIGMA ERP')

@section('content')
<div class="space-y-6" x-data="{
    showManualModal: false,
    showImportModal: false,
    selectedProduct: null,
    productId: '',
    productName: '',
    unitCode: 'PCS',
    currentQty: 0,
    costPrice: 0,
    minStock: 5,
    rackLocation: '',
    openManual(p) {
        this.selectedProduct = p;
        this.productId = p ? p.id : '';
        this.productName = p ? p.name : '';
        this.unitCode = p && p.base_unit ? p.base_unit.unit_code : 'PCS';
        this.currentQty = p && p.stocks && p.stocks[0] ? Number(p.stocks[0].physical_quantity) : 0;
        this.costPrice = p ? Number(p.cost_price) : 0;
        this.minStock = p ? Number(p.min_stock_alert) : 5;
        this.rackLocation = p ? (p.rack_location || '') : '';
        this.showManualModal = true;
    }
}">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="p-2.5 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Saldo Awal Item &amp; Persediaan</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Inisialisasi stok fisik dan HPP awal saat migrasi toko (Fitur Persediaan iPos 5 / Ketoko).</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('warehouse.opening-stock.template') }}" class="inline-flex items-center space-x-2 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Unduh Template Excel (.xlsx)</span>
            </a>

            <button @click="showImportModal = true" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-xs font-bold shadow-md hover:from-emerald-700 hover:to-teal-700 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span>Import Massal Excel / CSV</span>
            </button>

            <button @click="openManual(null)" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-xs font-bold shadow-md hover:from-blue-700 hover:to-indigo-700 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Input Satuan</span>
            </button>
        </div>
    </div>

    <!-- Store Selector & Branch Context -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <span class="text-xs font-bold text-slate-500 uppercase">Pilih Gudang / Cabang:</span>
            <form method="GET" action="{{ route('warehouse.opening-stock.index') }}" class="inline">
                <select name="store_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $selectedStore->id === $s->id ? 'selected' : '' }}>{{ $s->code }} — {{ $s->name }}</option>
                    @endforeach
                </select>
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif
                @if(request('category_id'))
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                @endif
            </form>
        </div>

        <div class="flex items-center space-x-2 text-xs text-slate-500">
            <span>Stok Fisik di Cabang:</span>
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $selectedStore->name }}</span>
        </div>
    </div>

    <!-- KPI / Highlights Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Item Bersaldo Awal</p>
                <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">{{ number_format($totalItemsWithStock) }} Item</h3>
                <p class="text-xs text-slate-400 mt-0.5">Memiliki kuantitas fisik > 0 di cabang ini</p>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-950/60 rounded-xl text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Kuantitas Fisik</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($totalPhysicalQty, 0, ',', '.') }} Unit</h3>
                <p class="text-xs text-slate-400 mt-0.5">Akumulasi kuantitas barang di gudang</p>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/60 rounded-xl text-emerald-600 dark:text-emerald-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Valuasi Persediaan Awal</p>
                <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">Rp {{ number_format($totalValuation, 0, ',', '.') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Otomatis terbit jurnal ke Akun 1005-001</p>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $errors->first('error') }}</span>
        </div>
    @endif

    <!-- Search & Filter Form -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('warehouse.opening-stock.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
            <div class="relative flex-1 w-full">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama barang atau SKU..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <select name="category_id" class="px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">Filter</button>
            @if(request('q') || request('category_id'))
                <a href="{{ route('warehouse.opening-stock.index', ['store_id' => $selectedStore->id]) }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>
    </div>

    <!-- Items Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">SKU &amp; Nama Barang</th>
                        <th class="px-4 py-3.5">Kategori / Rak</th>
                        <th class="px-4 py-3.5">Satuan</th>
                        <th class="px-4 py-3.5 text-right">Stok Fisik Saat Ini</th>
                        <th class="px-4 py-3.5 text-right">HPP Satuan (Rp)</th>
                        <th class="px-4 py-3.5 text-right">Subtotal Valuasi</th>
                        <th class="px-5 py-3.5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($products as $p)
                        @php
                            $stk = $p->stocks->first();
                            $physical = (float)($stk?->physical_quantity ?? 0);
                            $cost = (float)$p->cost_price;
                            $subtotal = $physical * $cost;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                <div class="font-bold">{{ $p->name }}</div>
                                <div class="text-xs font-mono text-slate-400">{{ $p->sku }}</div>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <div>{{ $p->category?->name ?? 'Umum' }}</div>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $p->rack_location ? 'Rak: '.$p->rack_location : '-' }}</span>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $p->baseUnit?->unit_code ?? 'PCS' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right font-black {{ $physical > 0 ? 'text-blue-600 dark:text-blue-400 text-base' : 'text-slate-400' }}">
                                {{ number_format($physical, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-right text-xs font-semibold text-slate-700 dark:text-slate-300">
                                Rp {{ number_format($cost, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-right font-bold text-xs text-slate-900 dark:text-white">
                                Rp {{ number_format($subtotal, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <button @click="openManual({{ json_encode($p) }})" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400 transition cursor-pointer">
                                    ✏️ Input / Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                Tidak ada data produk yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: INPUT MANUAL / EDIT SALDO AWAL -->
    <div x-show="showManualModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showManualModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-blue-100 dark:bg-blue-900/50 text-blue-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Input / Edit Saldo Awal Item</h3>
                </div>
                <button @click="showManualModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('warehouse.opening-stock.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Produk / Barang</label>
                    <template x-if="selectedProduct">
                        <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                            <input type="hidden" name="product_id" :value="productId">
                            <div class="font-bold text-slate-900 dark:text-white" x-text="productName"></div>
                            <div class="text-xs text-slate-400 font-mono" x-text="'SKU: ' + (selectedProduct.sku || '')"></div>
                        </div>
                    </template>
                    <template x-if="!selectedProduct">
                        <select name="product_id" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                            <option value="">-- Pilih Barang --</option>
                            @foreach($products as $prod)
                                <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                            @endforeach
                        </select>
                    </template>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                            Jumlah Saldo Awal (<span x-text="unitCode"></span>)
                        </label>
                        <input type="number" step="0.01" min="0" name="quantity" x-model="currentQty" required placeholder="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold text-base">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">HPP Satuan (Rp)</label>
                        <input type="number" step="0.01" min="0" name="cost_price" x-model="costPrice" required placeholder="0" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold text-base">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Stok Minimum Alert</label>
                        <input type="number" step="0.01" min="0" name="min_stock_alert" x-model="minStock" placeholder="5" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Lokasi Rak Gudang</label>
                        <input type="text" name="rack_location" x-model="rackLocation" placeholder="Contoh: RAK-A1" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Keterangan</label>
                    <input type="text" name="notes" placeholder="Contoh: Saldo awal opname cut-off migrasi" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl text-xs text-blue-700 dark:text-blue-300">
                    💡 Sistem otomatis memperbarui <strong>Stok Fisik</strong>, <strong>HPP Moving Average</strong>, dan membukukan jurnal persediaan seimbang.
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showManualModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-md transition cursor-pointer">Simpan Saldo Awal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: IMPORT MASSAL CSV SALDO AWAL -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showImportModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Import Massal Saldo Awal Item (Excel / CSV)</h3>
                </div>
                <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('warehouse.opening-stock.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">

                <div class="p-3 bg-emerald-50/80 dark:bg-emerald-950/40 rounded-xl text-xs space-y-1 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    <div class="font-bold">Gudang / Cabang Tujuan: {{ $selectedStore->name }}</div>
                    <p>Format file: Excel (<code>.xlsx</code> / <code>.xls</code>) atau CSV. Sangat mudah diisi via Microsoft Excel / Google Sheets.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-2">Pilih File Dokumen Excel / CSV</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('warehouse.opening-stock.template') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh Contoh Template Excel (.xlsx)</span>
                    </a>
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition cursor-pointer">Unggah &amp; Proses Saldo Awal</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
