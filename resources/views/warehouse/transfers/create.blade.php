@extends('layouts.app')

@section('title', 'Kirim Transfer Antar Cabang')
@section('tab_title', 'Form Transfer Cabang')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.transfers.index') }}" class="hover:text-blue-600 font-semibold">Transfer Antar Cabang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Kirim Transfer Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📦 Form Pengiriman Transfer Antar-Cabang</span>
            </h1>
        </div>

        <a href="{{ route('warehouse.transfers.index') }}"
           class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
            Kembali
        </a>
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

    <form action="{{ route('warehouse.transfers.store') }}" method="POST" id="transferForm" class="space-y-4">
        @csrf

        <!-- INFORMASI CABANG & PENGIRIMAN -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                <span>📍 Toko Asal, Tujuan &amp; Informasi Pengiriman</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <!-- Toko Asal -->
                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Toko Asal (Pengirim) <span class="text-rose-500">*</span>
                    </label>
                    <select name="source_store_id" id="sourceStore" required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Toko Asal --</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ (auth()->user()->store_id === $s->id || old('source_store_id') === $s->id) ? 'selected' : '' }}>
                                🏬 {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10.5px] text-slate-400 mt-1">Stok akan dipotong dari toko ini setelah transfer di-dispatch.</p>
                </div>

                <!-- Toko Tujuan -->
                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Toko Tujuan (Penerima) <span class="text-rose-500">*</span>
                    </label>
                    <select name="destination_store_id" id="destStore" required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Toko Tujuan --</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ old('destination_store_id') === $s->id ? 'selected' : '' }}>
                                🏬 {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10.5px] text-slate-400 mt-1">Toko cabang yang akan menerima kiriman barang.</p>
                </div>

                <!-- Tanggal Pengiriman -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Tanggal Pengiriman <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="transfer_date" value="{{ old('transfer_date', date('Y-m-d')) }}" required
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Driver & Plat -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Supir / Kurir</label>
                    <input type="text" name="driver_name" value="{{ old('driver_name') }}" placeholder="Contoh: Pak Joko"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nomor Polisi / Plat</label>
                    <input type="text" name="vehicle_number" value="{{ old('vehicle_number') }}" placeholder="Contoh: B 1234 SGM"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Misal: Restock akhir pekan / pesanan cabang"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- HERO QUICK BARCODE SCAN & SEARCH BAR (IDENTIK DENGAN PEMBELIAN & PENJUALAN BACKOFFICE) -->
        <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-indigo-800 p-3.5 rounded-2xl text-white shadow-md">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-extrabold uppercase tracking-wider text-blue-100 flex items-center gap-1.5">
                    <span>⚡</span> Scan Barcode / Input Barang Cepat (Tekan Enter untuk Masuk)
                </label>
                <button type="button" onclick="openItemModal()" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all cursor-pointer shadow-xs">
                    <span>🔍</span> Cari Item Lengkap <kbd class="bg-indigo-950/60 px-1.5 py-0.2 rounded font-mono text-[10px]">[F2]</kbd>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">🔍</span>
                    <input type="text"
                           id="quickSearchInput"
                           placeholder="Scan barcode dengan scanner fisik, atau ketik nama/SKU lalu tekan Enter..."
                           class="w-full pl-9 pr-4 py-2 bg-white text-slate-900 placeholder-slate-400 text-xs font-semibold rounded-xl shadow-inner focus:outline-hidden focus:ring-2 focus:ring-blue-400"
                           autocomplete="off">
                </div>
                <button type="button" onclick="handleQuickSearchEnter()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 font-bold text-xs rounded-xl shadow-xs cursor-pointer transition-colors">
                    Cari / Masukkan [Enter]
                </button>
            </div>
        </div>

        <!-- DAFTAR BARANG YANG DITRANSFER -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        📦 Rincian Barang yang Ditransfer
                    </h2>
                    <span class="text-[11px] text-slate-400 font-medium">Tekan <strong>[F2]</strong> untuk daftar katalog</span>
                </div>
                <button type="button" onclick="openItemModal()"
                        class="px-3.5 py-1.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 font-bold text-xs rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-colors flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span>+ Tambah Barang [F2]</span>
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-2.5 min-w-[280px]">Produk / SKU</th>
                            <th class="p-2.5 w-44">Satuan Kemasan</th>
                            <th class="p-2.5 w-36 text-right">Sisa Stok Toko Asal</th>
                            <th class="p-2.5 w-36 text-right font-black text-blue-700 dark:text-blue-400">Qty Transfer</th>
                            <th class="p-2.5 w-14 text-center">#</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody" class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>

            <div id="emptyRowPlaceholder" class="text-center py-8 text-slate-400 text-xs border-b border-x border-slate-200 dark:border-slate-800 rounded-b-xl">
                Belum ada barang dipilih. Scan barcode di atas atau tekan <strong class="text-blue-600 dark:text-blue-400">[F2] Cari Item</strong>.
            </div>
        </div>

        <!-- FOOTER & RINGKASAN TOTAL -->
        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs">
            <div class="text-xs text-slate-500 font-semibold flex items-center gap-4">
                <div>Total Barang: <span class="font-bold text-slate-900 dark:text-white" id="itemCountDisplay">0</span> SKU</div>
                <div>Total Kuantitas: <span class="font-bold text-blue-600 dark:text-blue-400" id="totalQtyDisplay">0</span> Unit Dasar</div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('warehouse.transfers.index') }}"
                   class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                    Batal
                </a>
                <button type="submit" id="submitTransferBtn"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-blue-500/25 cursor-pointer flex items-center gap-2">
                    <span>💾</span>
                    <span>Simpan Draft Transfer [F9]</span>
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
                    <h3 class="text-sm font-black">Cari Item Produk Transfer &amp; Sisa Stok Toko Asal</h3>
                    <p class="text-[11px] text-blue-200">Ketik nama barang, barcode, atau SKU [F2]</p>
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
                       placeholder="Ketik kata kunci pencarian (misal: 'minyak', 'kopi', atau scan barcode)..."
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
                        <th class="py-2 px-3">Kategori &amp; Rak</th>
                        <th class="py-2 px-3 text-right">Stok Toko Asal</th>
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
            document.getElementById('transferForm')?.submit();
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
        const rows = document.querySelectorAll('.modal-result-row');
        if (rows.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentHighlightedIndex = (currentHighlightedIndex + 1) % rows.length;
            highlightRow(rows, currentHighlightedIndex);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentHighlightedIndex = (currentHighlightedIndex - 1 + rows.length) % rows.length;
            highlightRow(rows, currentHighlightedIndex);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentHighlightedIndex >= 0 && currentHighlightedIndex < modalDataCache.length) {
                addProductRow(modalDataCache[currentHighlightedIndex]);
                closeItemModal();
            } else if (modalDataCache.length === 1) {
                addProductRow(modalDataCache[0]);
                closeItemModal();
            }
        }
    });

    // Alert jika toko asal diganti saat sudah ada item di tabel
    document.getElementById('sourceStore')?.addEventListener('change', () => {
        const rows = document.querySelectorAll('#itemsBody tr.item-row');
        if (rows.length > 0) {
            alert('Perhatian: Toko asal diubah. Harap periksa kembali sisa stok barang yang ada di tabel transfer!');
        }
    });
});

function highlightRow(rows, index) {
    rows.forEach((r, idx) => {
        if (idx === index) {
            r.classList.add('bg-blue-100', 'dark:bg-blue-900/50');
            r.scrollIntoView({ block: 'nearest' });
        } else {
            r.classList.remove('bg-blue-100', 'dark:bg-blue-900/50');
        }
    });
}

function handleQuickSearchEnter() {
    const quickInput = document.getElementById('quickSearchInput');
    const term = quickInput?.value.trim();
    if (!term) return;

    const sourceStoreId = document.getElementById('sourceStore')?.value;
    if (!sourceStoreId) {
        alert('Harap pilih Toko Asal (Pengirim) terlebih dahulu!');
        document.getElementById('sourceStore')?.focus();
        return;
    }

    const url = `{{ route('warehouse.inventory.search-product') }}?store_id=${sourceStoreId}&q=${encodeURIComponent(term)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.length === 1) {
                addProductRow(data[0]);
                quickInput.value = '';
                quickInput.focus();
            } else if (data.length > 1) {
                openItemModal(term);
            } else {
                alert(`Barang dengan kata kunci/barcode "${term}" tidak ditemukan.`);
                quickInput.select();
            }
        })
        .catch(err => {
            console.error(err);
            openItemModal(term);
        });
}

function openItemModal(initialQuery = '') {
    const sourceStoreId = document.getElementById('sourceStore')?.value;
    if (!sourceStoreId) {
        alert('Harap pilih Toko Asal (Pengirim) terlebih dahulu!');
        document.getElementById('sourceStore')?.focus();
        return;
    }

    const modal = document.getElementById('itemModal');
    const modalInput = document.getElementById('modalSearchInput');
    modal.style.display = 'flex';
    modalInput.value = initialQuery;
    modalInput.focus();
    currentHighlightedIndex = -1;

    fetchModalResults(initialQuery);
}

function closeItemModal() {
    const modal = document.getElementById('itemModal');
    modal.style.display = 'none';
    document.getElementById('quickSearchInput')?.focus();
}

function fetchModalResults(query) {
    const sourceStoreId = document.getElementById('sourceStore')?.value;
    const body = document.getElementById('modalResultsBody');
    const badge = document.getElementById('modalResultCountBadge');

    body.innerHTML = `<tr><td colspan="4" class="p-6 text-center text-slate-400 font-semibold text-xs">Memuat data barang...</td></tr>`;

    const url = `{{ route('warehouse.inventory.search-product') }}?store_id=${sourceStoreId}&q=${encodeURIComponent(query)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            modalDataCache = data;
            badge.innerText = `${data.length} item`;
            currentHighlightedIndex = -1;

            if (data.length === 0) {
                body.innerHTML = `<tr><td colspan="4" class="p-6 text-center text-slate-400 font-semibold text-xs">Tidak ditemukan barang yang cocok.</td></tr>`;
                return;
            }

            let html = '';
            data.forEach((p, idx) => {
                const avail = p.available_quantity;
                const phys = p.physical_quantity;
                const stockColor = avail <= 0 ? 'text-rose-600' : 'text-blue-600 dark:text-blue-400';

                html += `
                    <tr class="modal-result-row hover:bg-blue-50/80 dark:hover:bg-slate-800/80 cursor-pointer transition-colors border-b border-slate-100 dark:border-slate-800"
                        onclick="selectModalProduct(${idx})">
                        <td class="py-2 px-3">
                            <div class="font-bold text-slate-900 dark:text-white">${p.name}</div>
                            <div class="text-[10.5px] font-mono text-slate-400">SKU: ${p.sku} | Barcode: ${p.barcode}</div>
                        </td>
                        <td class="py-2 px-3 text-[11px] text-slate-600 dark:text-slate-300">
                            <div>${p.category_name}</div>
                            <div class="text-[10px] text-slate-400 font-mono">Rak: ${p.rack_location}</div>
                        </td>
                        <td class="py-2 px-3 text-right">
                            <div class="font-mono font-black ${stockColor}">${avail} Unit</div>
                            <div class="text-[10px] text-slate-400">Fisik: ${phys}</div>
                        </td>
                        <td class="py-2 px-3 text-center">
                            <button type="button" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition-colors cursor-pointer">
                                + Pilih
                            </button>
                        </td>
                    </tr>
                `;
            });

            body.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            body.innerHTML = `<tr><td colspan="4" class="p-4 text-center text-rose-500 font-semibold text-xs">Gagal memuat barang. Silakan coba lagi.</td></tr>`;
        });
}

function selectModalProduct(index) {
    if (modalDataCache[index]) {
        addProductRow(modalDataCache[index]);
        closeItemModal();
    }
}

function addProductRow(product) {
    const emptyRow = document.getElementById('emptyRowPlaceholder');
    if (emptyRow) {
        emptyRow.style.display = 'none';
    }

    const itemsBody = document.getElementById('itemsBody');

    // Cek jika produk sudah ada di tabel -> naikkan kuantitas (+1)
    const existingRow = itemsBody.querySelector(`tr[data-product-id="${product.id}"]`);
    if (existingRow) {
        const qtyInput = existingRow.querySelector('.qty-input');
        qtyInput.value = parseFloat(qtyInput.value || 0) + 1;
        qtyInput.dispatchEvent(new Event('input'));

        existingRow.classList.add('bg-blue-100', 'dark:bg-blue-900/60');
        setTimeout(() => {
            existingRow.classList.remove('bg-blue-100', 'dark:bg-blue-900/60');
        }, 600);
        updateSummary();
        return;
    }

    const idx = rowIndex++;
    let unitsHtml = '';
    (product.units || []).forEach(u => {
        unitsHtml += `<option value="${u.id}" data-factor="${u.conversion_factor}">${u.unit_code} (x${u.conversion_factor})</option>`;
    });

    const availStock = parseFloat(product.available_quantity || 0);
    const tr = document.createElement('tr');
    tr.className = 'item-row hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors';
    tr.setAttribute('data-product-id', product.id);
    tr.setAttribute('data-available', availStock);

    tr.innerHTML = `
        <td class="p-2.5">
            <input type="hidden" name="items[${idx}][product_id]" value="${product.id}">
            <div class="font-bold text-slate-900 dark:text-white text-xs">${product.name}</div>
            <div class="text-[10px] font-mono text-slate-400">SKU: ${product.sku} | Barcode: ${product.barcode}</div>
        </td>
        <td class="p-2.5">
            <select name="items[${idx}][product_unit_id]" class="unit-select w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500">
                ${unitsHtml}
            </select>
        </td>
        <td class="p-2.5 text-right font-mono text-xs">
            <div class="font-black ${availStock <= 0 ? 'text-rose-600' : 'text-blue-600 dark:text-blue-400'}">
                ${availStock} Unit
            </div>
            <div class="text-[10px] text-slate-400">Fisik: ${product.physical_quantity}</div>
        </td>
        <td class="p-2.5 text-right">
            <input type="number" step="0.01" min="0.01" name="items[${idx}][quantity]" value="1" required
                   class="qty-input w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-right font-mono font-bold focus:ring-2 focus:ring-blue-500">
            <div class="stock-warning text-[10px] text-rose-500 font-bold hidden mt-0.5">⚠️ Melebihi Stok!</div>
        </td>
        <td class="p-2.5 text-center">
            <button type="button" class="remove-row-btn text-rose-500 hover:text-rose-700 font-bold text-base cursor-pointer" title="Hapus Baris">&times;</button>
        </td>
    `;

    itemsBody.appendChild(tr);

    const qtyInput = tr.querySelector('.qty-input');
    const unitSelect = tr.querySelector('.unit-select');
    const warning = tr.querySelector('.stock-warning');

    function checkStock() {
        const factor = parseFloat(unitSelect.options[unitSelect.selectedIndex]?.dataset?.factor || 1);
        const qty = parseFloat(qtyInput.value || 0);
        const baseQty = qty * factor;
        const avail = parseFloat(tr.getAttribute('data-available') || 0);

        if (baseQty > avail) {
            warning.classList.remove('hidden');
            qtyInput.classList.add('border-rose-500', 'bg-rose-50', 'dark:bg-rose-950/40');
        } else {
            warning.classList.add('hidden');
            qtyInput.classList.remove('border-rose-500', 'bg-rose-50', 'dark:bg-rose-950/40');
        }
        updateSummary();
    }

    qtyInput.addEventListener('input', checkStock);
    unitSelect.addEventListener('change', checkStock);
    checkStock();

    tr.querySelector('.remove-row-btn').addEventListener('click', () => {
        tr.remove();
        updateSummary();
    });

    updateSummary();
    qtyInput.focus();
    qtyInput.select();
}

function updateSummary() {
    const rows = document.querySelectorAll('#itemsBody tr.item-row');
    const countDisplay = document.getElementById('itemCountDisplay');
    const totalQtyDisplay = document.getElementById('totalQtyDisplay');
    const emptyRow = document.getElementById('emptyRowPlaceholder');

    countDisplay.textContent = rows.length;

    let totalBaseQty = 0;
    rows.forEach(r => {
        const qty = parseFloat(r.querySelector('.qty-input')?.value || 0);
        const unitSelect = r.querySelector('.unit-select');
        const factor = parseFloat(unitSelect?.options[unitSelect.selectedIndex]?.dataset?.factor || 1);
        totalBaseQty += (qty * factor);
    });

    if (totalQtyDisplay) {
        totalQtyDisplay.textContent = totalBaseQty.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    if (rows.length === 0 && emptyRow) {
        emptyRow.style.display = 'block';
    }
}
</script>
@endsection
