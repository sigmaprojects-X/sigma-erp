@extends('layouts.app')

@section('title', 'Stok Minimum & Peringatan Restock — SIGMA ERP')

@section('content')
<div class="space-y-6" x-data="{
    showEditModal: false,
    showImportModal: false,
    selectedProduct: null,
    productId: '',
    productName: '',
    sku: '',
    unitCode: 'PCS',
    currentMinStock: 5,
    currentMaxStock: 20,
    openEdit(p) {
        this.selectedProduct = p;
        this.productId = p.id;
        this.productName = p.name;
        this.sku = p.sku;
        this.unitCode = p.base_unit ? p.base_unit.unit_code : 'PCS';
        this.currentMinStock = Number(p.min_stock_alert);
        this.currentMaxStock = p.max_stock ? Number(p.max_stock) : (Number(p.min_stock_alert) * 3);
        this.showEditModal = true;
    }
}">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="p-2.5 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Stok Minimum &amp; Peringatan Restock</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pengaturan batas reorder point dan deteksi dini kehabisan stok barang (Fitur iPos 5 / Ketoko).</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('warehouse.minimum-stock.template') }}" class="inline-flex items-center space-x-2 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Unduh Template Excel (.xlsx)</span>
            </a>

            <button @click="showImportModal = true" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 text-white text-xs font-bold shadow-md hover:from-amber-700 hover:to-orange-700 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span>Update Massal Excel / CSV</span>
            </button>
        </div>
    </div>

    <!-- Store Selector & Branch Context -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <span class="text-xs font-bold text-slate-500 uppercase">Pantau Gudang / Cabang:</span>
            <form method="GET" action="{{ route('warehouse.minimum-stock.index') }}" class="inline">
                <select name="store_id" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-amber-500">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $selectedStore->id === $s->id ? 'selected' : '' }}>{{ $s->code }} — {{ $s->name }}</option>
                    @endforeach
                </select>
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
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

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Item Dipantau</p>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($totalMonitored) }} Item</h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Seluruh katalog aktif</p>
            </div>
            <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-600 dark:text-slate-300">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-amber-900/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">🚨 Butuh Restock</p>
                <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($totalCritical) }} Item</h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Stok fisik &le; Stok Minimum</p>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-950/60 rounded-xl text-amber-600 dark:text-amber-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">⚠️ Stok Habis</p>
                <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ number_format($totalOutOfStock) }} Item</h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Stok fisik = 0 unit</p>
            </div>
            <div class="p-3 bg-rose-50 dark:bg-rose-950/60 rounded-xl text-rose-600 dark:text-rose-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-900/60 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">✅ Stok Aman</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($totalSafe) }} Item</h3>
                <p class="text-[10px] text-slate-400 mt-0.5">Stok fisik &gt; Stok Minimum</p>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/60 rounded-xl text-emerald-600 dark:text-emerald-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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

    <!-- Filter Bar & Search Form -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col lg:flex-row items-center justify-between gap-4">
        <!-- Status Filter Pills -->
        <div class="flex items-center space-x-2 w-full lg:w-auto overflow-x-auto pb-2 lg:pb-0">
            <a href="{{ route('warehouse.minimum-stock.index', array_merge(request()->query(), ['status' => 'ALL'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ ($status === 'ALL' || !$status) ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' }}">
                Semua Item ({{ $totalMonitored }})
            </a>
            <a href="{{ route('warehouse.minimum-stock.index', array_merge(request()->query(), ['status' => 'CRITICAL'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'CRITICAL' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400 hover:bg-amber-100' }}">
                🚨 Butuh Restock ({{ $totalCritical }})
            </a>
            <a href="{{ route('warehouse.minimum-stock.index', array_merge(request()->query(), ['status' => 'OUT_OF_STOCK'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'OUT_OF_STOCK' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400 hover:bg-rose-100' }}">
                ⚠️ Stok Habis ({{ $totalOutOfStock }})
            </a>
            <a href="{{ route('warehouse.minimum-stock.index', array_merge(request()->query(), ['status' => 'SAFE'])) }}" class="px-3 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'SAFE' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400 hover:bg-emerald-100' }}">
                ✅ Stok Aman ({{ $totalSafe }})
            </a>
        </div>

        <form method="GET" action="{{ route('warehouse.minimum-stock.index') }}" class="w-full lg:w-auto flex items-center space-x-2">
            <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari SKU atau nama barang..." class="w-full pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-3 py-1.5 text-xs font-bold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200">Cari</button>
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
                        <th class="px-4 py-3.5 text-right">Stok Fisik Saat Ini</th>
                        <th class="px-4 py-3.5 text-right">Stok Minimum (Alert)</th>
                        <th class="px-4 py-3.5 text-right">Stok Maksimum</th>
                        <th class="px-4 py-3.5 text-center">Status Peringatan</th>
                        <th class="px-4 py-3.5 text-right">Saran Restock</th>
                        <th class="px-5 py-3.5 text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($products as $p)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                <div class="font-bold">{{ $p->name }}</div>
                                <div class="text-xs font-mono text-slate-400">{{ $p->sku }}</div>
                            </td>
                            <td class="px-4 py-4 text-xs">
                                <div>{{ $p->category?->name ?? 'Umum' }}</div>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $p->rack_location ? 'Rak: '.$p->rack_location : '-' }}</span>
                            </td>
                            <td class="px-4 py-4 text-right font-black {{ $p->current_physical_stock <= 0 ? 'text-rose-600' : ($p->current_physical_stock <= $p->min_stock_alert ? 'text-amber-600' : 'text-slate-800 dark:text-slate-200') }}">
                                {{ number_format($p->current_physical_stock, 2, ',', '.') }} {{ $p->baseUnit?->unit_code }}
                            </td>
                            <td class="px-4 py-4 text-right text-xs font-bold text-slate-700 dark:text-slate-300">
                                {{ number_format($p->min_stock_alert, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-right text-xs font-medium text-slate-500">
                                {{ $p->max_stock ? number_format($p->max_stock, 2, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-4 text-center">
                                @if($p->is_out_of_stock)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                        ⚠️ HABIS (0)
                                    </span>
                                @elseif($p->is_critical)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">
                                        🚨 BUTUH RESTOCK
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        ✅ AMAN
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                @if($p->recommended_restock_qty > 0)
                                    +{{ number_format($p->recommended_restock_qty, 0, ',', '.') }} {{ $p->baseUnit?->unit_code }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openEdit({{ json_encode($p) }})" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-400 transition cursor-pointer">
                                        Ubah Min
                                    </button>
                                    @if($p->is_critical || $p->is_out_of_stock)
                                        <a href="{{ route('purchases.orders.create') }}" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-300 transition">
                                            Order PO
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                Tidak ada data barang yang sesuai dengan filter.
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

    <!-- MODAL 1: EDIT MINIMUM STOCK SATUAN -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showEditModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Ubah Batas Stok Minimum</h3>
                </div>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('warehouse.minimum-stock.update') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="product_id" :value="productId">

                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="font-bold text-slate-900 dark:text-white" x-text="productName"></div>
                    <div class="text-xs text-slate-400 font-mono" x-text="'SKU: ' + sku"></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Batas Stok Minimum (<span x-text="unitCode"></span>)
                    </label>
                    <input type="number" step="0.01" min="0" name="min_stock_alert" x-model="currentMinStock" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold text-base">
                    <p class="text-[10px] text-slate-400 mt-1">Sistem akan memunculkan alarm restock ketika stok fisik mencapai atau di bawah batas ini.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">
                        Target Stok Maksimum (<span x-text="unitCode"></span>) - Opsional
                    </label>
                    <input type="number" step="0.01" min="0" name="max_stock" x-model="currentMaxStock" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                    <p class="text-[10px] text-slate-400 mt-1">Digunakan untuk menghitung kuantitas saran order restock otomatis.</p>
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-md transition cursor-pointer">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: UPDATE MASSAL CSV STOK MINIMUM -->
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showImportModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Update Massal Stok Minimum (Excel / CSV)</h3>
                </div>
                <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('warehouse.minimum-stock.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="p-3 bg-amber-50/80 dark:bg-amber-950/40 rounded-xl text-xs space-y-1 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                    <p class="font-bold">Format Dokumen Excel (.xlsx / .xls) atau CSV:</p>
                    <p>Kolom: <code>KODE_SKU, NAMA_ITEM, STOK_MINIMUM, STOK_MAKSIMUM</code></p>
                    <p class="text-amber-600 dark:text-amber-400">Cocokkan SKU dengan master produk untuk memperbarui batas minimum ratusan item sekaligus.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-2">Pilih File Dokumen Excel / CSV</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 cursor-pointer">
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('warehouse.minimum-stock.template') }}" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center space-x-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh Contoh Template Excel (.xlsx)</span>
                    </a>
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showImportModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-md transition cursor-pointer">Unggah &amp; Perbarui Batas Stok</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
