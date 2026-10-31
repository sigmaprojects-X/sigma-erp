@extends('layouts.app')

@section('title', 'Input Penyesuaian Stok & Barang Rusak')
@section('tab_title', 'Form Penyesuaian Stok')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.adjustments.index') }}" class="hover:text-blue-600 font-semibold">Penyesuaian Stok</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Form Penyesuaian Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📝</span> Form Penyesuaian Stok &amp; Karantina Barang Rusak
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-mono">
                <kbd class="font-bold">[F2]</kbd> Cari Item Cepat
            </span>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-mono">
                <kbd class="font-bold">[F9]</kbd> Simpan Penyesuaian
            </span>
            <a href="{{ route('warehouse.adjustments.index') }}"
               class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                ← Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs">
            <ul class="list-disc pl-4 space-y-0.5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('warehouse.adjustments.store') }}" method="POST" id="adjustmentForm" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <!-- INFORMASI DASAR PENYESUAIAN -->
        <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider pb-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span>📌 Data Cabang &amp; Tipe Penyesuaian</span>
                <span class="text-[11px] font-mono text-blue-700 dark:text-blue-300 bg-blue-100/60 dark:bg-blue-950/60 px-2.5 py-0.5 rounded-full border border-blue-200 dark:border-blue-800">
                    Otomatis: <strong>ADJ-{{ date('Ymd') }}-XXXX</strong>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                <!-- Toko / Lokasi -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Cabang / Gudang <span class="text-rose-500">*</span>
                    </label>
                    <select name="store_id" id="storeSelect" required
                            class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-500">
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $selectedStore->id === $s->id ? 'selected' : '' }}>
                                🏬 {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tanggal -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Tanggal Penyesuaian <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="adjustment_date" value="{{ old('adjustment_date', date('Y-m-d')) }}" required
                           class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Tipe Penyesuaian -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Tipe Penyesuaian <span class="text-rose-500">*</span>
                    </label>
                    <select name="adjustment_type" id="adjustmentType" required
                            class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold focus:ring-2 focus:ring-blue-500">
                        <option value="DAMAGE_WASTE" selected>🛡️ Barang Rusak / Kadaluarsa (Masuk Karantina)</option>
                        <option value="INTERNAL_USE">🏢 Pemakaian Toko / Operasional (Potong Langsung)</option>
                        <option value="STOCK_IN_CORRECTION">➕ Koreksi Stok Fisik Lebih (Tambah Stok)</option>
                        <option value="STOCK_OUT_CORRECTION">➖ Koreksi Stok Fisik Kurang (Potong Stok)</option>
                    </select>
                </div>

                <!-- Catatan Tambahan -->
                <div class="md:col-span-3">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Misal: Penyesuaian hasil audit rak sayur & kaleng bocor..."
                           class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-medium focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div id="typeNotice" class="mt-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/40 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2">
                <span class="text-base">🛡️</span>
                <div>
                    <strong>Barang Rusak (Karantina):</strong> Stok fisik barang akan dipindahkan ke status <em>Karantina</em>. Kasir POS dan Web PWA tidak akan bisa menjual barang ini, dan barang akan menunggu resolusi (Potong Hutang supplier, Tukar Guling baru, Tunai, atau Pemusnahan).
                </div>
            </div>
        </div>

        <!-- HERO QUICK BARCODE SCAN & SEARCH BAR (IDENTIK DENGAN PEMBELIAN & PENJUALAN BACKOFFICE) -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-3.5 rounded-xl text-white shadow-md">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-extrabold uppercase tracking-wider text-blue-100 flex items-center gap-1.5">
                    <span>⚡</span> Scan Barcode / Input Barang Cepat (Tekan Enter untuk Masuk)
                </label>
                <button type="button" onclick="openItemModal()" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                    <span>🔍</span> Cari Item Lengkap <kbd class="bg-blue-950/60 px-1.5 py-0.2 rounded font-mono text-[10px]">[F2]</kbd>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">🔍</span>
                    <input type="text"
                           id="quickSearchInput"
                           placeholder="Scan barcode dengan scanner, atau ketik nama barang lalu tekan Enter..."
                           class="w-full pl-9 pr-4 py-2 bg-white text-slate-900 placeholder-slate-400 text-xs font-semibold rounded-lg shadow-inner focus:outline-hidden focus:ring-2 focus:ring-blue-400"
                           autocomplete="off">
                </div>
                <button type="button" onclick="handleQuickSearchEnter()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 font-bold text-xs rounded-lg shadow-xs cursor-pointer transition-colors">
                    Cari / Masukkan [Enter]
                </button>
            </div>
        </div>

        <!-- DAFTAR BARANG YANG DISESUAIKAN -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📦</span> Daftar Barang Penyesuaian
                </h3>
                <button type="button" onclick="openItemModal()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg transition-all cursor-pointer shadow-xs flex items-center gap-1">
                    <span>+</span> Tambah Barang [F2]
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-2.5 px-3 min-w-[240px]">Produk / SKU</th>
                            <th class="py-2.5 px-3 w-36">Satuan Kemasan</th>
                            <th class="py-2.5 px-3 w-28 text-right">Stok Toko</th>
                            <th class="py-2.5 px-3 w-32 text-right font-black text-blue-700 dark:text-blue-400">Qty Sesuaikan</th>
                            <th class="py-2.5 px-3 min-w-[200px]">Alasan / Penyebab</th>
                            <th class="py-2.5 px-3 text-center w-12">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="itemsBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>

            <div id="emptyRowPlaceholder" class="text-center py-8 text-slate-400 text-xs border-b border-x border-slate-200 dark:border-slate-800 rounded-b-xl">
                Belum ada barang dipilih. Scan barcode di atas atau tekan <strong class="text-blue-600 dark:text-blue-400">[F2] Cari Item</strong>.
            </div>
        </div>

        <!-- FOOTER & RINGKASAN TOTAL -->
        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800">
            <div class="text-xs text-slate-500 font-semibold" id="totalItemsSummary">
                Total Barang: <span class="font-bold text-slate-900 dark:text-white" id="itemCountDisplay">0</span> SKU
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('warehouse.adjustments.index') }}"
                   class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-colors">
                    Batal
                </a>
                <button type="submit" id="submitBtn"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer">
                    <span>💾</span>
                    <span>Simpan Penyesuaian Stok [F9]</span>
                </button>
            </div>
        </div>
    </form>

</div>

<!-- ============================================================== -->
<!-- MODAL CARI ITEM CEPAT & LENGKAP [F2] -->
<!-- ============================================================== -->
<div id="itemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-150" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
        <!-- MODAL HEADER -->
        <div class="px-5 py-3.5 bg-gradient-to-r from-blue-700 to-indigo-800 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <div>
                    <h3 class="text-sm font-black">Cari Item Produk &amp; Informasi Stok (Penyesuaian)</h3>
                    <p class="text-[11px] text-blue-200">Ketik nama barang, scan barcode, atau kode SKU [F2]</p>
                </div>
            </div>
            <button type="button" onclick="closeItemModal()" class="text-blue-200 hover:text-white font-bold text-xl cursor-pointer">&times;</button>
        </div>

        <!-- MODAL SEARCH BAR -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">🔍</span>
                <input type="text"
                       id="modalSearchInput"
                       placeholder="Ketik kata kunci pencarian (misal: 'minyak', 'beras', atau scan barcode)..."
                       class="w-full pl-10 pr-24 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-blue-500 shadow-inner"
                       autocomplete="off">
                <span id="modalResultCountBadge" class="absolute right-3 top-2.5 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                    0 item
                </span>
            </div>
        </div>

        <!-- MODAL RESULTS TABLE -->
        <div class="overflow-y-auto flex-1 p-2 max-h-[55vh]">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10.5px] uppercase sticky top-0 z-10">
                    <tr>
                        <th class="py-2 px-3">Produk &amp; Barcode</th>
                        <th class="py-2 px-3">Kategori</th>
                        <th class="py-2 px-3 text-right">Stok Fisik (Tersedia)</th>
                        <th class="py-2 px-3 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody id="modalResultsBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td colspan="4" class="p-8 text-center text-slate-400 text-xs font-semibold">
                            Ketik kata kunci atau scan barcode untuk mencari produk.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MODAL FOOTER -->
        <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
            <span>Navigasi: <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 rounded border font-mono">↑</kbd> <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 rounded border font-mono">↓</kbd> Pilih | <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 rounded border font-mono">Enter</kbd> Masukkan | <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 rounded border font-mono">ESC</kbd> Tutup</span>
            <button type="button" onclick="closeItemModal()" class="px-3.5 py-1.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 font-bold rounded-lg cursor-pointer">Tutup</button>
        </div>
    </div>
</div>

<script>
let rowIndex = 0;
let modalDebounceTimer = null;
let currentHighlightedIndex = -1;
let modalDataCache = [];

const reasonsMap = {
    'DAMAGE_WASTE': ['Pecah / Rusak Fisik', 'Kemasan Bocor', 'Kadaluarsa / Basi', 'Cacat Produksi Pabrik', 'Penyok / Tidak Layak Jual'],
    'INTERNAL_USE': ['Pemakaian Operasional Toko', 'Konsumsi Karyawan', 'Perawatan / Kebersihan Toko', 'Tester / Sampling Pelanggan'],
    'STOCK_IN_CORRECTION': ['Koreksi Selisih Opname Lebih', 'Bonus / Sampel Supplier', 'Temuan Barang Terselip'],
    'STOCK_OUT_CORRECTION': ['Koreksi Selisih Opname Kurang', 'Hilang / Susut Toko', 'Kesalahan Input Kasir']
};

document.addEventListener('DOMContentLoaded', () => {
    const quickInput = document.getElementById('quickSearchInput');
    quickInput?.focus();

    quickInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleQuickSearchEnter();
        }
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            openItemModal(quickInput ? quickInput.value : '');
        } else if (e.key === 'F9') {
            e.preventDefault();
            document.getElementById('adjustmentForm')?.submit();
        } else if (e.key === 'Escape') {
            closeItemModal();
        }
    });

    const modalInput = document.getElementById('modalSearchInput');
    modalInput?.addEventListener('input', () => {
        clearTimeout(modalDebounceTimer);
        modalDebounceTimer = setTimeout(() => {
            fetchModalResults(modalInput.value.trim());
        }, 200);
    });

    modalInput?.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateModalResults(1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateModalResults(-1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            selectHighlightedModalItem();
        }
    });

    const adjustmentTypeSelect = document.getElementById('adjustmentType');
    adjustmentTypeSelect?.addEventListener('change', updateNotice);
});

function updateNotice() {
    const val = document.getElementById('adjustmentType').value;
    const typeNotice = document.getElementById('typeNotice');
    if (val === 'DAMAGE_WASTE') {
        typeNotice.className = "mt-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/40 text-xs text-rose-700 dark:text-rose-300 flex items-start gap-2";
        typeNotice.innerHTML = `<span class="text-base">🛡️</span><div><strong>Barang Rusak (Karantina):</strong> Stok fisik barang akan dipindahkan ke status <em>Karantina</em>. Kasir POS dan Web PWA tidak akan bisa menjual barang ini, dan barang akan menunggu resolusi (Potong Hutang supplier, Tukar Guling baru, Tunai, atau Pemusnahan).</div>`;
    } else if (val === 'INTERNAL_USE') {
        typeNotice.className = "mt-2 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 text-xs text-amber-700 dark:text-amber-300 flex items-start gap-2";
        typeNotice.innerHTML = `<span class="text-base">🏢</span><div><strong>Pemakaian Toko:</strong> Stok fisik akan langsung dipotong dari inventori, dan sistem akan mencatat Jurnal Beban Operasional Toko (Debit Biaya Beban, Kredit Persediaan Barang Dagang).</div>`;
    } else if (val === 'STOCK_IN_CORRECTION') {
        typeNotice.className = "mt-2 p-3 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 text-xs text-emerald-700 dark:text-emerald-300 flex items-start gap-2";
        typeNotice.innerHTML = `<span class="text-base">➕</span><div><strong>Koreksi Stok Masuk:</strong> Stok fisik akan bertambah sesuai HPP moving average saat ini, dan dicatat ke Jurnal Koreksi Persediaan.</div>`;
    } else {
        typeNotice.className = "mt-2 p-3 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-700 dark:text-slate-300 flex items-start gap-2";
        typeNotice.innerHTML = `<span class="text-base">➖</span><div><strong>Koreksi Stok Keluar:</strong> Stok fisik akan langsung berkurang sesuai jumlah yang diinput.</div>`;
    }
}

function handleQuickSearchEnter() {
    const quickInput = document.getElementById('quickSearchInput');
    const term = quickInput.value.trim();
    if (!term) return;

    const storeId = document.getElementById('storeSelect').value;
    const url = `{{ route('warehouse.inventory.search-product') }}?store_id=${storeId}&q=${encodeURIComponent(term)}`;

    fetch(url)
        .then(res => res.json())
        .then(products => {
            if (!products || products.length === 0) {
                alert('Produk tidak ditemukan!');
                quickInput.select();
                return;
            }

            if (products.length === 1) {
                addProductRow(products[0]);
                quickInput.value = '';
                quickInput.focus();
            } else {
                openItemModal(term);
            }
        })
        .catch(() => {
            alert('Gagal mengambil data produk.');
        });
}

function openItemModal(initialQuery = '') {
    const modal = document.getElementById('itemModal');
    const modalInput = document.getElementById('modalSearchInput');
    modal.style.display = 'flex';
    modalInput.value = initialQuery;
    modalInput.focus();
    fetchModalResults(initialQuery);
}

function closeItemModal() {
    document.getElementById('itemModal').style.display = 'none';
    document.getElementById('quickSearchInput')?.focus();
}

function fetchModalResults(query) {
    const storeId = document.getElementById('storeSelect').value;
    const tbody = document.getElementById('modalResultsBody');
    const badge = document.getElementById('modalResultCountBadge');

    fetch(`{{ route('warehouse.inventory.search-product') }}?store_id=${storeId}&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(products => {
            modalDataCache = products;
            badge.textContent = `${products.length} item`;

            if (products.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="p-8 text-center text-slate-400">Tidak ada produk yang cocok dengan '${query}'</td></tr>`;
                currentHighlightedIndex = -1;
                return;
            }

            let html = '';
            products.forEach((p, idx) => {
                html += `
                    <tr class="modal-row hover:bg-blue-50/70 dark:hover:bg-slate-800 cursor-pointer transition-colors"
                        id="modalRow_${idx}"
                        onclick="selectModalItemByIndex(${idx})">
                        <td class="py-2 px-3">
                            <div class="font-bold text-slate-900 dark:text-white">${p.name}</div>
                            <div class="text-[10px] text-slate-400 font-mono">SKU: ${p.sku} | Barcode: ${p.barcode}</div>
                        </td>
                        <td class="py-2 px-3 text-slate-600 dark:text-slate-300 font-medium">
                            ${p.category_name}
                        </td>
                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                            ${p.physical_quantity} (${p.available_quantity})
                        </td>
                        <td class="py-2 px-3 text-center">
                            <button type="button" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded text-xs cursor-pointer">
                                + Pilih
                            </button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
            currentHighlightedIndex = 0;
            highlightModalRow(0);
        });
}

function navigateModalResults(direction) {
    if (modalDataCache.length === 0) return;
    const newIndex = currentHighlightedIndex + direction;
    if (newIndex >= 0 && newIndex < modalDataCache.length) {
        currentHighlightedIndex = newIndex;
        highlightModalRow(newIndex);
    }
}

function highlightModalRow(index) {
    document.querySelectorAll('.modal-row').forEach(r => r.classList.remove('bg-blue-100', 'dark:bg-blue-950/60'));
    const target = document.getElementById(`modalRow_${index}`);
    if (target) {
        target.classList.add('bg-blue-100', 'dark:bg-blue-950/60');
        target.scrollIntoView({ block: 'nearest' });
    }
}

function selectHighlightedModalItem() {
    if (currentHighlightedIndex >= 0 && currentHighlightedIndex < modalDataCache.length) {
        selectModalItemByIndex(currentHighlightedIndex);
    }
}

function selectModalItemByIndex(index) {
    const prod = modalDataCache[index];
    if (prod) {
        addProductRow(prod);
        closeItemModal();
    }
}

function addProductRow(p) {
    const placeholder = document.getElementById('emptyRowPlaceholder');
    if (placeholder) placeholder.style.display = 'none';

    const existingRow = document.querySelector(`.item-row[data-product-id="${p.id}"]`);
    if (existingRow) {
        const qtyInput = existingRow.querySelector('.qty-input');
        qtyInput.value = parseFloat(qtyInput.value || 0) + 1;
        qtyInput.focus();
        qtyInput.select();
        updateSummary();
        return;
    }

    const idx = rowIndex++;
    const currentType = document.getElementById('adjustmentType').value;
    const presetReasons = reasonsMap[currentType] || ['Lainnya'];

    let unitsHtml = '';
    p.units.forEach(u => {
        unitsHtml += `<option value="${u.id}">${u.unit_code} (${u.conversion_factor}x)</option>`;
    });

    let reasonsOptions = '';
    presetReasons.forEach(r => {
        reasonsOptions += `<option value="${r}">${r}</option>`;
    });
    reasonsOptions += `<option value="Lainnya">Lainnya...</option>`;

    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.className = 'item-row hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors';
    tr.setAttribute('data-product-id', p.id);
    tr.innerHTML = `
        <td class="py-2.5 px-3">
            <input type="hidden" name="items[${idx}][product_id]" value="${p.id}">
            <div class="font-bold text-slate-900 dark:text-white text-xs">${p.name}</div>
            <div class="text-[10px] font-mono text-slate-400">SKU: ${p.sku} | Barcode: ${p.barcode}</div>
        </td>
        <td class="py-2.5 px-3">
            <select name="items[${idx}][product_unit_id]" class="w-full py-1 px-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                ${unitsHtml}
            </select>
        </td>
        <td class="py-2.5 px-3 text-right font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
            Fisik: ${p.physical_quantity}
        </td>
        <td class="py-2.5 px-3 text-right">
            <input type="number" step="0.01" min="0.01" name="items[${idx}][quantity]" value="1" required
                   class="qty-input w-full py-1 px-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs text-right font-mono font-bold text-blue-700 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
        </td>
        <td class="py-2.5 px-3">
            <select name="items[${idx}][reason]" class="w-full py-1 px-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-medium focus:ring-2 focus:ring-blue-500">
                ${reasonsOptions}
            </select>
        </td>
        <td class="py-2.5 px-3 text-center">
            <button type="button" class="remove-row-btn text-rose-500 hover:text-rose-700 font-bold text-sm cursor-pointer" title="Hapus">✕</button>
        </td>
    `;

    tbody.appendChild(tr);

    const qtyInput = tr.querySelector('.qty-input');
    qtyInput.focus();
    qtyInput.select();

    tr.querySelector('.remove-row-btn').addEventListener('click', function() {
        tr.remove();
        updateSummary();
    });

    updateSummary();
}

function updateSummary() {
    const rows = document.querySelectorAll('#itemsBody tr.item-row');
    document.getElementById('itemCountDisplay').textContent = rows.length;
    const placeholder = document.getElementById('emptyRowPlaceholder');
    if (placeholder) {
        placeholder.style.display = rows.length === 0 ? 'block' : 'none';
    }
}
</script>
@endsection
