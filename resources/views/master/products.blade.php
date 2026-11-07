@extends('layouts.app')

@section('title', 'Master Produk & Harga 3D')
@section('tab_title', 'Daftar Item')

@section('content')
<div class="space-y-4" x-data="{ createModal: false, importModal: false }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Master Data</span>
                <span>/</span>
                <span class="text-slate-700 font-bold">Produk & Harga 3D</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏷️ Master Data Produk & Harga Bertingkat</span>
            </h1>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('master.barcodes.print') }}"
               class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-200 transition-all cursor-pointer flex items-center gap-1.5"
               title="Pusat Cetak Stiker Barcode & Price Tag Gondola">
                <span>🏷️ Cetak Label Rak</span>
            </a>
            <a href="{{ route('master.products.export') }}"
               class="px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 transition-all cursor-pointer flex items-center gap-1.5"
               title="Export Seluruh Produk ke Excel / CSV">
                <span>📤 Export CSV</span>
            </a>
            @can('manage-products')
            <button type="button"
                    @click="importModal = true"
                    class="px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs rounded-lg border border-amber-200 transition-all cursor-pointer flex items-center gap-1.5"
                    title="Import Data Produk Massal dari Excel">
                <span>📥 Import CSV</span>
            </button>
            <a href="{{ route('master.products.create') }}"
               class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Item Baru</span>
            </a>
            <button type="button"
                    @click="createModal = true"
                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg border border-slate-300 transition-all cursor-pointer">
                <span>⚡ Cepat</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200/90 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('master.products') }}" class="flex flex-wrap items-center gap-3 text-xs">
            <div class="relative flex-1 min-w-[220px]">
                <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari berdasarkan SKU, Nama Barang, Kategori, atau Barcode..."
                       class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
            </div>
            <div>
                <select name="type"
                        class="py-1.5 px-3 border border-slate-300 rounded-lg font-semibold text-xs bg-white focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    <option value="">-- Semua Tipe Produk --</option>
                    <option value="BARANG" {{ request('type') === 'BARANG' ? 'selected' : '' }}>📦 Barang Fisik</option>
                    <option value="JASA" {{ request('type') === 'JASA' ? 'selected' : '' }}>🔧 Jasa / Service</option>
                    <option value="RAKITAN_PROSES" {{ request('type') === 'RAKITAN_PROSES' ? 'selected' : '' }}>⚙️ Rakitan Produksi</option>
                    <option value="RAKITAN_NON_PROSES" {{ request('type') === 'RAKITAN_NON_PROSES' ? 'selected' : '' }}>🎁 Paket Bundling</option>
                    <option value="NON_INVENTORY" {{ request('type') === 'NON_INVENTORY' ? 'selected' : '' }}>📝 Non Inventory</option>
                    <option value="BIAYA" {{ request('type') === 'BIAYA' ? 'selected' : '' }}>💸 Biaya / Beban</option>
                </select>
            </div>
            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 font-semibold text-slate-600">
                <input type="checkbox"
                       name="show_discontinued"
                       value="1"
                       {{ request()->boolean('show_discontinued') ? 'checked' : '' }}
                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Tampilkan item Discontinue
            </label>
            <button type="submit"
                    class="py-1.5 px-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-lg transition-colors cursor-pointer">
                Cari Produk
            </button>
            @if(request('search') || request('type') || request()->boolean('show_discontinued'))
                <a href="{{ route('master.products') }}"
                   class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- DATAGRID PRODUK -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10.5px]">
                    <tr>
                        <th class="px-3.5 py-3">SKU & Barcode</th>
                        <th class="px-3 py-3">Nama Produk & Kategori</th>
                        <th class="px-3 py-3">Satuan Kemasan</th>
                        @can('view-hpp')
                            <th class="px-3 py-3 text-right">HPP (Moving Avg)</th>
                        @endcan
                        <th class="px-3 py-3 text-right">Harga Jual Retail</th>
                        <th class="px-3 py-3 text-right">Harga Jual Grosir</th>
                        <th class="px-3 py-3 text-center">Stok Fisik (STR-001)</th>
                        <th class="px-3 py-3 text-center">Status</th>
                        <th class="px-3 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11.5px]">
                    @forelse($products as $prod)
                        @php
                            $retailPrice = $prod->prices->where('is_active', true)->firstWhere('customerTier.code', 'RETAIL')?->price;
                            $grosirPrice = $prod->prices->where('is_active', true)->firstWhere('customerTier.code', 'GROSIR')?->price;
                            $stock = $prod->stocks->first();
                            $primaryBarcode = $prod->barcodes->firstWhere('is_primary', true)?->barcode ?? $prod->barcodes->first()?->barcode;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3.5 py-2.5">
                                <div class="font-bold text-slate-900">{{ $prod->sku }}</div>
                                <div class="text-[10px] text-slate-400">{{ $primaryBarcode ?: '-' }}</div>
                            </td>
                            <td class="px-3 py-2.5 font-sans">
                                <div class="font-bold text-slate-900">{{ $prod->name }}</div>
                                <div class="flex flex-wrap items-center gap-1.5 mt-0.5">
                                    <span class="px-1.5 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded text-[10px] font-bold">{{ $prod->type_label }}</span>
                                    <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-semibold">{{ $prod->category }}</span>
                                    @if($prod->tracksInventory())
                                        <a href="{{ route('master.stock-card', ['product_id' => $prod->id]) }}"
                                           class="px-1.5 py-0.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 rounded text-[10px] font-bold inline-flex items-center gap-0.5 transition-colors"
                                           title="Lihat Riwayat & Kartu Stok Produk Ini">
                                            <span>📦</span> <span>Kartu Stok</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2.5 font-sans">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($prod->units as $u)
                                        <span class="px-1.5 py-0.5 rounded bg-blue-50 border border-blue-200 text-blue-900 text-[10px] font-bold">
                                            {{ $u->unit_code }} (x{{ (float)$u->conversion_factor }})
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            @can('view-hpp')
                                <td class="px-3 py-2.5 text-right font-bold text-slate-600">
                                    Rp {{ \App\Models\Product::formatDecimalCurrency($prod->cost_price) }}
                                </td>
                            @endcan
                            <td class="px-3 py-2.5 text-right font-bold text-slate-900">
                                @if($retailPrice)
                                    Rp {{ \App\Models\Product::formatDecimalCurrency($retailPrice) }}
                                @else
                                    <span class="text-slate-400 font-normal">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-emerald-700">
                                @if($grosirPrice && (float)$grosirPrice > 0)
                                    Rp {{ \App\Models\Product::formatDecimalCurrency($grosirPrice) }}
                                @else
                                    <span class="text-[10.5px] text-slate-400 font-semibold italic">— (Ikut Retail)</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center font-bold">
                                @if(! $prod->tracksInventory())
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-sans font-semibold text-[10px]">Tanpa Stok</span>
                                @elseif($stock)
                                    <span class="{{ (float)$stock->available_quantity <= (float)$prod->min_stock_alert ? 'text-amber-600 font-black' : 'text-slate-800' }}">
                                        {{ number_format((float)$stock->available_quantity, 1) }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0.0</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                @if($prod->sale_status === 'DISCONTINUED')
                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px]" title="Barang Tidak Dijual (Discontinue)">Discontinue</span>
                                @elseif($prod->is_active)
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[10px]">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center font-sans">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('master.barcodes.print', ['product_id' => $prod->id]) }}"
                                       class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded text-[11px] font-bold transition-colors"
                                       title="Cetak Label Rak & Barcode">
                                        🏷️ Label
                                    </a>
                                    @can('manage-products')
                                        <a href="{{ route('master.products.edit', $prod->id) }}"
                                           class="px-2 py-1 bg-slate-100 hover:bg-blue-100 text-slate-700 hover:text-blue-700 rounded text-[11px] font-bold transition-colors"
                                           title="Edit Data Item">
                                            ✏️ Edit
                                        </a>
                                        <form action="{{ route('master.products.destroy', $prod->id) }}" method="POST" class="inline"
                                              data-confirm
                                              data-confirm-message="Hapus item [{{ $prod->name }}]? Sistem akan menolak penghapusan jika item sudah memiliki riwayat transaksi agar catatan audit tetap terjaga.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-800 rounded text-[11px] font-bold transition-colors cursor-pointer"
                                                    title="Hapus Barang (Hanya jika belum pernah bertransaksi)">
                                                🗑️ Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 text-[10px]">-</span>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->canViewHpp() ? 9 : 8 }}" class="px-4 py-12 text-center text-slate-400 font-sans text-xs">
                                Tidak ada data produk yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH PRODUK BARU -->
    <div x-show="createModal"
         x-cloak
         @keydown.escape.window="createModal = false"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl max-w-xl w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs">
            
            <!-- HEADER MODAL -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 text-white px-4 py-3 flex items-center justify-between border-b border-slate-700">
                <div class="flex items-center gap-2">
                    <span class="text-xl">➕</span>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">Input Produk & Harga 3D Baru</h3>
                        <p class="text-[10px] text-slate-400">Tambahkan barang dagangan ke database SIGMA ERP</p>
                    </div>
                </div>
                <button type="button"
                        @click="createModal = false"
                        class="text-slate-400 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('master.products.store') }}">
                @csrf
                <div class="p-4 space-y-3 max-h-[75vh] overflow-y-auto">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">SKU Produk * :</label>
                            <input type="text" name="sku" required placeholder="Contoh: SBK-MYK-001" class="w-full p-2 border border-slate-300 rounded uppercase font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kategori Produk :</label>
                            @if(isset($categories) && $categories->isNotEmpty())
                                <select name="category_id" class="w-full p-2 border border-slate-300 rounded font-semibold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="category" placeholder="Sembako / Minuman / Bumbu" value="Sembako" class="w-full p-2 border border-slate-300 rounded font-semibold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Produk Barang * :</label>
                        <input type="text" name="name" required placeholder="Contoh: Minyak Goreng Bimoli 2 Liter" class="w-full p-2 border border-slate-300 rounded font-bold text-slate-900 focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Satuan Dasar (Unit Code) * :</label>
                            @if(isset($units) && $units->isNotEmpty())
                                <select name="unit_code" required class="w-full p-2 border border-slate-300 rounded uppercase font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                                    @foreach($units as $u)
                                        <option value="{{ $u->code }}">{{ $u->code }} - {{ $u->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" name="unit_code" required placeholder="PCS / BTL / SAK / KG" value="PCS" class="w-full p-2 border border-slate-300 rounded uppercase font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            @endif
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Barcode Utama :</label>
                            <input type="text" name="barcode" placeholder="899..." class="w-full p-2 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                    </div>

                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg space-y-2.5">
                        <div class="font-extrabold text-blue-900 text-xs pb-1 border-b border-slate-200">
                            Struktur Harga 3 Dimensi & HPP:
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1">HPP Awal (Rp) * :</label>
                                <input type="number" step="any" name="cost_price" required min="0" placeholder="0" class="w-full p-1.5 border border-slate-300 rounded text-right font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1">Jual Retail (Rp) * :</label>
                                <input type="number" step="any" name="price_retail" required min="0" placeholder="0" class="w-full p-1.5 border border-slate-300 rounded text-right font-mono font-bold text-slate-900 focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 text-[11px] mb-1">Jual Grosir (Rp) :</label>
                                <input type="number" step="any" name="price_grosir" min="0" placeholder="0" class="w-full p-1.5 border border-slate-300 rounded text-right font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- FOOTER MODAL -->
                <div class="bg-slate-100 px-4 py-3 border-t border-slate-300 flex items-center justify-between">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-white border border-slate-300 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded shadow-md cursor-pointer">
                        💾 Simpan Produk
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL IMPORT DATA PRODUK EXCEL / CSV                           -->
    <!-- ============================================================== -->
    @can('manage-products')
    <div x-show="importModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200"
             @click.away="importModal = false">
            <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📥</span>
                    <h3 class="font-black text-sm">Import Data Produk Massal (Excel / CSV)</h3>
                </div>
                <button type="button" @click="importModal = false" class="text-white hover:text-amber-200 text-lg font-bold">✕</button>
            </div>

            <form action="{{ route('master.products.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="p-6 space-y-4 text-xs">
                    <!-- PETUNJUK -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 text-amber-900 space-y-2">
                        <div class="font-bold flex items-center gap-1.5">
                            <span>💡 Panduan Format Import:</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            Pastikan file berformat <strong>.CSV</strong> dengan pemisah koma (<code>,</code>) atau titik koma (<code>;</code>). Kolom wajib terisi adalah <strong>SKU</strong> dan <strong>Nama Produk</strong>. Kolom harga beli (HPP), harga retail, dan harga grosir akan otomatis diatur.
                        </p>
                        <div class="pt-1">
                            <a href="{{ route('master.products.template') }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition-all shadow-xs text-[11px]">
                                <span>📥 Download Template CSV Excel</span>
                            </a>
                        </div>
                    </div>

                    <!-- FILE INPUT -->
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Pilih File CSV (.csv / .txt) :</label>
                        <input type="file"
                               name="file"
                               required
                               accept=".csv, text/csv, text/plain"
                               class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 border border-slate-300 rounded-lg p-1.5 focus:outline-hidden focus:ring-2 focus:ring-amber-200">
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-3 border-t border-slate-200 flex items-center justify-between">
                    <button type="button"
                            @click="importModal = false"
                            class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-lg hover:bg-slate-100 cursor-pointer text-xs">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg shadow-md cursor-pointer text-xs flex items-center gap-1.5">
                        <span>🚀 Mulai Import Produk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

</div>
@endsection
