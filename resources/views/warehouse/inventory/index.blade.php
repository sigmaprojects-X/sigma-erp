@extends('layouts.app')

@section('title', 'Monitoring & Valuasi Persediaan Toko')
@section('tab_title', 'Persediaan Toko')

@section('content')
<div class="space-y-5 p-4 max-w-7xl mx-auto w-full">

    <!-- HEADER & STORE SELECTOR -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Manajemen Gudang & Persediaan</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Monitoring & Valuasi Stok</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📊 Monitoring & Valuasi Stok Persediaan</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-bold">
                    {{ $selectedStore->name }} ({{ $selectedStore->code }})
                </span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Form Switch Toko -->
            <form method="GET" action="{{ route('warehouse.inventory.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <select name="store_id" onchange="this.form.submit()"
                        class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 shadow-xs cursor-pointer">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $selectedStore->id === $s->id ? 'selected' : '' }}>
                            🏬 {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Export CSV Button -->
            <a href="{{ route('warehouse.inventory.export', ['store_id' => $selectedStore->id, 'q' => request('q'), 'category_id' => request('category_id')]) }}"
               class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Export CSV/Excel</span>
            </a>

            <!-- Quick Action: Buat Adjustment -->
            <a href="{{ route('warehouse.adjustments.create', ['store_id' => $selectedStore->id]) }}"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>+ Penyesuaian / Rusak</span>
            </a>
        </div>
    </div>

    <!-- 4 KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. Total Valuasi Persediaan Toko -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider">Total Nilai Aset Stok</span>
                <span class="p-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">💰</span>
            </div>
            @if(auth()->user()->canViewHpp())
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">
                    Rp {{ number_format($totalValuation, 0, ',', '.') }}
                </div>
                <div class="text-[11px] text-slate-500 font-medium mt-1">
                    Berdasarkan HPP Moving Average
                </div>
            @else
                <div class="text-lg font-bold text-slate-400 font-mono tracking-tight">
                    *** Rahasia ***
                </div>
                <div class="text-[11px] text-slate-400 font-medium mt-1">
                    Akses HPP dibatasi untuk Owner/Finance
                </div>
            @endif
        </div>

        <!-- 2. Total Item Terdaftar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider">Total Varian Produk</span>
                <span class="p-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">📦</span>
            </div>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">
                {{ number_format($totalItems, 0, ',', '.') }} <span class="text-sm font-semibold text-slate-500">SKU</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1">
                Katalog aktif terdata di cabang ini
            </div>
        </div>

        <!-- 3. Stok Menipis & Habis -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider">Perlu Restock Segera</span>
                <span class="p-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">⚠️</span>
            </div>
            <div class="flex items-baseline gap-2">
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono tracking-tight">
                    {{ $lowStockCount }}
                </div>
                <span class="text-xs text-slate-500 font-bold">&lt; ROP</span>
                <span class="text-slate-300 dark:text-slate-700">|</span>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                    {{ $outOfStockCount }}
                </div>
                <span class="text-xs text-slate-500 font-bold">Habis</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1">
                Stok tersedia mendekati/mencapai 0
            </div>
        </div>

        <!-- 4. Barang Karantina / Rusak -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1.5">
                <span class="text-xs font-bold uppercase tracking-wider">Karantina / Barang Rusak</span>
                <span class="p-1.5 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-lg">🛡️</span>
            </div>
            <div class="flex items-baseline justify-between">
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                    {{ $quarantinedCount }} <span class="text-sm font-semibold text-slate-500">Item</span>
                </div>
                @if($quarantinedCount > 0)
                    <a href="{{ route('warehouse.adjustments.quarantine', ['store_id' => $selectedStore->id]) }}"
                       class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                        Lihat & Proses →
                    </a>
                @endif
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1">
                Terkunci otomatis dari kasir POS & PWA
            </div>
        </div>
    </div>

    <!-- FILTER & PENCARIAN BARANG -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('warehouse.inventory.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
            <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">

            <!-- Input Cari Kata Kunci -->
            <div class="md:col-span-5 relative">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Cari SKU, Barcode, atau Nama Produk..."
                       class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Filter Kategori -->
            <div class="md:col-span-3">
                <select name="category_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') === $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Status Stok -->
            <div class="md:col-span-3">
                <select name="status" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="ALL" {{ request('status', 'ALL') === 'ALL' ? 'selected' : '' }}>Semua Status Stok</option>
                    <option value="LOW_STOCK" {{ request('status') === 'LOW_STOCK' ? 'selected' : '' }}>⚠️ Menipis (&lt; ROP)</option>
                    <option value="OUT_OF_STOCK" {{ request('status') === 'OUT_OF_STOCK' ? 'selected' : '' }}>🔴 Habis (0 Unit)</option>
                    <option value="QUARANTINED" {{ request('status') === 'QUARANTINED' ? 'selected' : '' }}>🛡️ Ada Stok Rusak/Karantina</option>
                </select>
            </div>

            <!-- Tombol Filter -->
            <div class="md:col-span-1 flex items-center gap-1.5">
                <button type="submit" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition-colors">
                    Cari
                </button>
                @if(request('q') || request('category_id') || (request('status') && request('status') !== 'ALL'))
                    <a href="{{ route('warehouse.inventory.index', ['store_id' => $selectedStore->id]) }}"
                       class="px-2.5 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-slate-700 dark:text-slate-300 font-bold rounded-xl transition-colors"
                       title="Reset Filter">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR STOK PERSEDIAAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-32">SKU / Barcode</th>
                        <th class="p-3 min-w-[200px]">Nama Produk</th>
                        <th class="p-3 w-28">Kategori / Rak</th>
                        <th class="p-3 w-20 text-center">Satuan</th>
                        <th class="p-3 w-24 text-right">Fisik (Total)</th>
                        <th class="p-3 w-24 text-right">Reserved</th>
                        <th class="p-3 w-24 text-right text-rose-600 dark:text-rose-400">Karantina</th>
                        <th class="p-3 w-28 text-right font-black text-blue-600 dark:text-blue-400">Tersedia (Jual)</th>
                        @if(auth()->user()->canViewHpp())
                            <th class="p-3 w-28 text-right">HPP Moving Avg</th>
                            <th class="p-3 w-32 text-right">Total Nilai Aset</th>
                        @endif
                        <th class="p-3 w-24 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-700 dark:text-slate-300">
                    @forelse($products as $p)
                        @php
                            $stk = $p->stocks->first();
                            $phys = (float) ($stk->physical_quantity ?? 0);
                            $res = (float) ($stk->reserved_quantity ?? 0);
                            $quar = (float) ($stk->quarantined_quantity ?? 0);
                            $avail = max(0, $phys - $res - $quar);
                            $minAlert = (float) ($p->min_stock_alert ?? 0);
                            $cost = (float) $p->cost_price;
                            $val = $phys * $cost;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- SKU & Barcode -->
                            <td class="p-3">
                                <div class="font-mono font-bold text-slate-900 dark:text-white">{{ $p->sku }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ $p->barcodes->first()?->barcode ?? '-' }}</div>
                            </td>

                            <!-- Nama Produk -->
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $p->name }}</div>
                                @if($avail <= 0)
                                    <span class="inline-block mt-0.5 px-2 py-0.2 rounded-md bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 text-[10px] font-bold">
                                        Habis (0 Unit)
                                    </span>
                                @elseif($avail <= $minAlert && $minAlert > 0)
                                    <span class="inline-block mt-0.5 px-2 py-0.2 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 text-[10px] font-bold">
                                        Menipis (&le; {{ $minAlert }})
                                    </span>
                                @endif
                            </td>

                            <!-- Kategori & Lokasi Rak -->
                            <td class="p-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $p->category?->name ?? 'Umum' }}</div>
                                <div class="text-[11px] text-slate-400">Rak: {{ $p->rack_location ?: '-' }}</div>
                            </td>

                            <!-- Satuan Dasar -->
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold">
                                    {{ $p->baseUnit?->unit_code ?? 'PCS' }}
                                </span>
                            </td>

                            <!-- Stok Fisik -->
                            <td class="p-3 text-right font-mono font-bold">
                                {{ number_format($phys, 2, ',', '.') }}
                            </td>

                            <!-- Reserved PWA -->
                            <td class="p-3 text-right font-mono text-slate-500">
                                {{ $res > 0 ? number_format($res, 2, ',', '.') : '-' }}
                            </td>

                            <!-- Karantina (Rusak/BS) -->
                            <td class="p-3 text-right font-mono">
                                @if($quar > 0)
                                    <span class="px-1.5 py-0.5 rounded-md bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 font-bold">
                                        {{ number_format($quar, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Stok Tersedia (Jual) -->
                            <td class="p-3 text-right font-mono font-black {{ $avail <= 0 ? 'text-rose-600' : ($avail <= $minAlert && $minAlert > 0 ? 'text-amber-600' : 'text-emerald-600 dark:text-emerald-400') }}">
                                {{ number_format($avail, 2, ',', '.') }}
                            </td>

                            <!-- HPP Moving Average -->
                            @if(auth()->user()->canViewHpp())
                                <td class="p-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($cost, 0, ',', '.') }}
                                </td>

                                <!-- Total Nilai Aset -->
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($val, 0, ',', '.') }}
                                </td>
                            @endif

                            <!-- Aksi: Kartu Stok -->
                            <td class="p-3 text-center">
                                <a href="{{ route('master.stock-card', ['product_id' => $p->id, 'store_id' => $selectedStore->id]) }}"
                                   class="inline-flex items-center px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 font-bold text-[11px] transition-colors"
                                   title="Buka Kartu Riwayat Mutasi Stok">
                                    Kartu Stok
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canViewHpp() ? 11 : 9 }}" class="p-8 text-center text-slate-400 font-semibold">
                                Tidak ada data stok yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION BAR -->
        @if($totalPages > 1)
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                <div>
                    Halaman <span class="font-bold text-slate-800 dark:text-slate-200">{{ $currentPage }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ $totalPages }}</span> (Total {{ $totalItemsCount }} Produk)
                </div>
                <div class="flex items-center gap-1">
                    @if($currentPage > 1)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}"
                           class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold transition-colors">
                            &larr; Sebelumnya
                        </a>
                    @endif
                    @if($currentPage < $totalPages)
                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}"
                           class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold transition-colors">
                            Selanjutnya &rarr;
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
