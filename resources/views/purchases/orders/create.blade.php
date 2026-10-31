@extends('layouts.app')

@section('title', 'Buat Purchase Order Baru')
@section('tab_title', 'Buat PO Baru')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.orders.index') }}" class="hover:underline">Pesanan Pembelian (PO)</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Buat PO Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📑</span> Form Buat Surat Pesanan (Purchase Order)
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pilih pemasok, lihat sisa stok fisik saat ini, dan masukkan kuantitas berdasarkan rekomendasi restock otomatis.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-mono">
                <kbd class="font-bold">[F2]</kbd> Cari Item Cepat
            </span>
            <a href="{{ route('purchases.orders.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('purchases.orders.store') }}" method="POST" id="poForm" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <!-- HEADER PO: SUPPLIER & TANGGAL -->
        <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="block font-bold text-slate-700 dark:text-slate-300">
                        Pemasok / Supplier: <span class="text-rose-500">*</span>
                    </label>
                    <button type="button" id="loadSupplierRestockBtn" onclick="loadSupplierRestockRecommendations()"
                            class="hidden text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 items-center gap-1 cursor-pointer transition-colors">
                        <span>⚡ Tarik Saran Restok (<span id="supplierRestockBadge">0 item</span>)</span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <select name="supplier_id" id="supplierSelect" required class="flex-1 px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-bold text-xs">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ (isset($selectedSupplierId) && $selectedSupplierId === $s->id) ? 'selected' : '' }}>
                                {{ $s->name }} ({{ $s->code }}) • {{ $s->is_pkp ? 'PKP' : 'Non-PKP' }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" id="loadRestockActionBtn" onclick="loadSupplierRestockRecommendations()"
                            title="Tarik seluruh produk supplier ini yang stoknya menipis"
                            class="px-3 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer transition-all shrink-0">
                        <span>⚡</span>
                        <span class="hidden sm:inline">Saran Restok</span>
                    </button>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Tanggal Pesanan: <span class="text-rose-500">*</span>
                </label>
                <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required
                       class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Estimasi Tiba (Delivery):</label>
                <input type="date" name="expected_delivery_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}"
                       class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-semibold text-xs">
            </div>

            <div class="md:col-span-4">
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan Pesanan:</label>
                <input type="text" name="notes" placeholder="Contoh: Harap kirimkan langsung ke Gudang Utama..."
                       class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-medium text-xs">
            </div>
        </div>

        <!-- SEARCH BAR CEPAT & KETOKO ITEM LOOKUP -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-3.5 rounded-xl text-white shadow-md">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-extrabold uppercase tracking-wider text-blue-100 flex items-center gap-1.5">
                    <span>⚡</span> Scan Barcode / Cari &amp; Tambah Barang ke PO (Tekan Enter untuk Cari Varian)
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
                           placeholder="Scan barcode dengan scanner, atau ketik nama barang (misal 'minyak') lalu tekan Enter..."
                           class="w-full pl-9 pr-4 py-2 bg-white text-slate-900 placeholder-slate-400 text-xs font-semibold rounded-lg shadow-inner focus:outline-hidden focus:ring-2 focus:ring-blue-400">
                </div>
                <button type="button" onclick="handleQuickSearchEnter()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 font-bold text-xs rounded-lg shadow-xs cursor-pointer transition-colors">
                    Cari / Masukkan
                </button>
            </div>
        </div>

        <!-- DAFTAR ITEM PO -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📦</span> Rincian Barang yang Dipesan (Purchase Order)
                </h3>
                <button type="button" onclick="openItemModal()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg transition-all cursor-pointer shadow-xs flex items-center gap-1">
                    <span>+</span> Tambah Barang [F2]
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 min-w-[240px]">Nama Produk &amp; SKU</th>
                            <th class="py-2.5 px-3 w-44 text-center">Stok Fisik &amp; Saran Restok</th>
                            <th class="py-2.5 px-3 w-32">Satuan Kemasan</th>
                            <th class="py-2.5 px-3 w-24 text-center">Qty Pesan</th>
                            <th class="py-2.5 px-3 w-36 text-right">Harga Beli Satuan (Rp)</th>
                            <th class="py-2.5 px-3 text-right w-36">Subtotal (Rp)</th>
                            <th class="py-2.5 px-3 text-center w-12">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="poTableBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
            <div id="emptyRow" class="text-center py-6 text-slate-400 text-xs border-b border-x border-slate-200 dark:border-slate-800 rounded-b-xl">
                Belum ada barang dalam PO. Ketik kata kunci di atas atau tekan <strong class="text-blue-600 dark:text-blue-400">[F2] Cari Item</strong>.
            </div>
        </div>

        <!-- FOOTER & RINGKASAN PO -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <div class="p-3 bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 rounded-xl text-xs space-y-1">
                    <div class="font-bold text-blue-900 dark:text-blue-300 flex items-center gap-1">
                        <span>ℹ️</span> Informasi Status PO
                    </div>
                    <p class="text-blue-700 dark:text-blue-400 text-[11px] leading-relaxed">
                        Dokumen PO yang disimpan berstatus <strong>DRAFT</strong>. Setelah barang dikirimkan oleh pemasok, staf gudang dapat memproses penerimaan barang fisik (Goods Receipt) via LPB atau Faktur Pembelian.
                    </p>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-500">
                    <span>Total Baris:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300" id="totalRows">0 Item</span>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-slate-900 dark:text-white">ESTIMASI TOTAL PO:</span>
                    <strong class="font-extrabold text-blue-600 dark:text-blue-400 text-base font-mono" id="grandTotalDisplay">Rp 0</strong>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTON -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
            <button type="button" onclick="submitPO()" class="w-full sm:w-auto px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span>📑</span>
                <span>Simpan Surat Pesanan (PO) [F9]</span>
            </button>
        </div>
    </form>
</div>

<!-- ============================================================== -->
<!-- MODAL CARI ITEM CEPAT & LENGKAP (KETOKO-STYLE ITEM LOOKUP) -->
<!-- ============================================================== -->
<div id="itemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-150" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
        
        <!-- MODAL HEADER -->
        <div class="px-5 py-3.5 bg-gradient-to-r from-blue-700 to-indigo-800 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <div>
                    <h3 class="text-sm font-black">Cari Item Produk &amp; Informasi Stok Fisik (Purchase Order)</h3>
                    <p class="text-[11px] text-blue-200">Ketik nama barang (contoh: 'minyak'), scan barcode, atau kode SKU</p>
                </div>
            </div>
            <button type="button" onclick="closeItemModal()" class="text-blue-200 hover:text-white font-bold text-xl cursor-pointer">&times;</button>
        </div>

        <!-- MODAL FILTER & SEARCH BAR -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">🔍</span>
                <input type="text"
                       id="modalSearchInput"
                       placeholder="Ketik kata kunci pencarian (misal: 'minyak', 'beras', atau scan barcode)..."
                       class="w-full pl-10 pr-24 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-blue-500 shadow-inner">
                <span id="modalResultCountBadge" class="absolute right-3 top-2.5 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                    Ditemukan: 0 item
                </span>
            </div>
        </div>

        <!-- MODAL RESULTS TABLE -->
        <div class="overflow-y-auto flex-1 p-2 max-h-[55vh]">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="sticky top-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold uppercase text-[10.5px] border-b border-slate-200 dark:border-slate-700 shadow-2xs">
                    <tr>
                        <th class="p-2.5">Kode Item / SKU</th>
                        <th class="p-2.5">Barcode</th>
                        <th class="p-2.5">Nama Item</th>
                        <th class="p-2.5 text-center">Satuan</th>
                        <th class="p-2.5 text-center">Sisa Stok</th>
                        <th class="p-2.5 text-center">Saran Restok</th>
                        <th class="p-2.5 text-right">Modal Terakhir (HPP)</th>
                        <th class="p-2.5 text-center">Rak</th>
                        <th class="p-2.5 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody id="modalTableBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td colspan="9" class="text-center py-8 text-slate-400">
                            Silakan ketik nama barang atau barcode di kolom pencarian di atas.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MODAL FOOTER -->
        <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
            <div class="text-slate-500 dark:text-slate-400 font-medium">
                Gunakan <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-bold font-mono">↑</kbd> <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-bold font-mono">↓</kbd> untuk memilih baris • <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-bold font-mono">Enter</kbd> Pilih Item • <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-bold font-mono">Esc</kbd> Tutup
            </div>
            <button type="button" onclick="closeItemModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition-colors">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    let poItems = [];
    let modalResults = [];
    let modalHighlightIndex = 0;
    let modalDebounceTimer = null;

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
            } else if (e.key === 'Escape') {
                closeItemModal();
            } else if (e.key === 'F9') {
                e.preventDefault();
                submitPO();
            }
        });

        const modalInput = document.getElementById('modalSearchInput');
        modalInput?.addEventListener('input', () => {
            clearTimeout(modalDebounceTimer);
            modalDebounceTimer = setTimeout(() => {
                fetchModalResults(modalInput.value.trim());
            }, 250);
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

        const supSelect = document.getElementById('supplierSelect');
        supSelect?.addEventListener('change', checkSupplierRestockCount);
        if (supSelect && supSelect.value) {
            checkSupplierRestockCount();
            @if(!empty($selectedProductIds))
                loadSupplierRestockRecommendations();
            @endif
        }
    });

    async function checkSupplierRestockCount() {
        const supId = document.getElementById('supplierSelect')?.value;
        const btn = document.getElementById('loadSupplierRestockBtn');
        const badge = document.getElementById('supplierRestockBadge');
        if (!supId) {
            if (btn) btn.classList.add('hidden');
            return;
        }

        try {
            const res = await fetch(`{{ route('purchases.recommendations') }}?supplier_id=${supId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.count > 0) {
                if (badge) badge.innerText = `${data.count} item`;
                if (btn) {
                    btn.classList.remove('hidden');
                    btn.classList.add('inline-flex');
                }
            } else {
                if (btn) btn.classList.add('hidden');
            }
        } catch (e) {
            console.error('Check restock error', e);
        }
    }

    async function loadSupplierRestockRecommendations() {
        const supId = document.getElementById('supplierSelect')?.value;
        if (!supId) {
            alert('Harap pilih Supplier terlebih dahulu!');
            document.getElementById('supplierSelect')?.focus();
            return;
        }

        const actionBtn = document.getElementById('loadRestockActionBtn');
        const originalText = actionBtn ? actionBtn.innerHTML : '';
        if (actionBtn) actionBtn.innerHTML = `<span>⏳</span><span class="hidden sm:inline">Memuat...</span>`;

        try {
            const res = await fetch(`{{ route('purchases.recommendations') }}?supplier_id=${supId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!data.recommendations || data.recommendations.length === 0) {
                alert('Tidak ada rekomendasi restok untuk supplier ini (semua stok produk aman).');
                return;
            }

            const filterProductIds = @json($selectedProductIds ?? []);
            let itemsToLoad = data.recommendations;
            if (Array.isArray(filterProductIds) && filterProductIds.length > 0) {
                const filtered = itemsToLoad.filter(rec => filterProductIds.includes(rec.product_id));
                if (filtered.length > 0) {
                    itemsToLoad = filtered;
                }
            }

            let addedCount = 0;
            itemsToLoad.forEach(rec => {
                const existing = poItems.find(i => i.product_id === rec.product_id);
                if (existing) {
                    existing.quantity = Math.max(existing.quantity, rec.recommended_quantity);
                } else {
                    poItems.push({
                        product_id: rec.product_id,
                        product_name: rec.product_name,
                        sku: rec.sku || '',
                        barcode: '',
                        total_quantity: rec.current_stock,
                        recommended_restock: rec.recommended_quantity,
                        base_unit_code: rec.recommended_unit_code,
                        product_unit_id: rec.recommended_unit_id,
                        units: [{
                            id: rec.recommended_unit_id,
                            unit_code: rec.recommended_unit_code,
                            unit_name: rec.recommended_unit_code,
                            conversion_factor: rec.conversion_factor,
                            is_base_unit: true
                        }],
                        quantity: rec.recommended_quantity,
                        unit_price: rec.estimated_unit_cost
                    });
                    addedCount++;
                }
            });

            renderPOTable();
            calculatePOTotal();
            alert(`Berhasil memuat ${addedCount} produk saran restok dari supplier ini.`);
        } catch (e) {
            console.error('Load restock error', e);
            alert('Gagal memuat rekomendasi restok supplier.');
        } finally {
            if (actionBtn) actionBtn.innerHTML = originalText;
        }
    }

    async function handleQuickSearchEnter() {
        const input = document.getElementById('quickSearchInput');
        const q = input ? input.value.trim() : '';
        if (q.length < 1) {
            openItemModal('');
            return;
        }

        try {
            const res = await fetch(`{{ route('purchases.search-product') }}?q=${encodeURIComponent(q)}&limit=10`);
            const data = await res.json();

            if (data.length === 1) {
                addItemToPO(data[0]);
                input.value = '';
            } else {
                openItemModal(q);
            }
        } catch (e) {
            console.error('Quick search error', e);
            openItemModal(q);
        }
    }

    function openItemModal(initialQuery = '') {
        const modal = document.getElementById('itemModal');
        const modalInput = document.getElementById('modalSearchInput');
        if (!modal) return;

        modal.style.display = 'flex';
        modalHighlightIndex = 0;

        if (modalInput) {
            modalInput.value = initialQuery;
            modalInput.focus();
            modalInput.select();
            fetchModalResults(initialQuery.trim());
        }
    }

    function closeItemModal() {
        const modal = document.getElementById('itemModal');
        if (modal) modal.style.display = 'none';
        document.getElementById('quickSearchInput')?.focus();
    }

    async function fetchModalResults(q) {
        const tbody = document.getElementById('modalTableBody');
        const countBadge = document.getElementById('modalResultCountBadge');

        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-slate-400">Sedang memuat data...</td></tr>`;

        try {
            const res = await fetch(`{{ route('purchases.search-product') }}?q=${encodeURIComponent(q)}&limit=50`);
            modalResults = await res.json();
            modalHighlightIndex = 0;

            if (countBadge) countBadge.innerText = `Ditemukan: ${modalResults.length} item`;
            renderModalTable();
        } catch (e) {
            console.error('Fetch modal items error', e);
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-rose-500 font-bold">Gagal memuat data produk</td></tr>`;
        }
    }

    function renderModalTable() {
        const tbody = document.getElementById('modalTableBody');
        if (!tbody) return;

        if (modalResults.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-6 text-slate-400">Tidak ada produk ditemukan.</td></tr>`;
            return;
        }

        let html = '';
        modalResults.forEach((p, idx) => {
            const isHigh = idx === modalHighlightIndex;
            const stockColor = p.total_quantity > 0 ? 'text-emerald-700 bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300' : 'text-rose-700 bg-rose-100 dark:bg-rose-950 dark:text-rose-300';
            const bgClass = isHigh ? 'bg-blue-100/70 dark:bg-blue-900/40 ring-1 ring-blue-500 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/40';

            const restockBadge = p.recommended_restock > 0
                ? `<span class="px-2 py-0.5 rounded text-[11px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200">+${p.recommended_restock}</span>`
                : `<span class="text-slate-400 text-[10px]">Aman</span>`;

            html += `
                <tr class="transition-colors cursor-pointer ${bgClass}" onclick="selectModalItemByIndex(${idx})">
                    <td class="p-2.5 font-mono text-slate-700 dark:text-slate-300">${p.sku || '-'}</td>
                    <td class="p-2.5 font-mono text-slate-500">${p.barcode || '-'}</td>
                    <td class="p-2.5">
                        <div class="font-bold text-slate-800 dark:text-white">${p.name}</div>
                        ${p.recent_purchases && p.recent_purchases.length > 0 ? `
                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                <span class="text-[9px] font-extrabold uppercase text-slate-400">Riwayat Beli:</span>
                                ${p.recent_purchases.map(rp => `
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9.5px] bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700" title="${rp.supplier_name}">
                                        <span>${rp.date}</span>
                                        <strong class="font-bold text-emerald-700 dark:text-emerald-400 font-mono">Rp ${Math.round(rp.unit_price).toLocaleString('id-ID')}/${rp.unit_code}</strong>
                                        <span class="text-slate-400">(${rp.supplier_name.substring(0, 10)})</span>
                                    </span>
                                `).join('')}
                            </div>
                        ` : ''}
                    </td>
                    <td class="p-2.5 text-center text-slate-500">${p.base_unit ? p.base_unit.unit_code : 'PCS'}</td>
                    <td class="p-2.5 text-center font-mono">
                        <span class="px-2 py-0.5 rounded text-[11px] font-black ${stockColor}">
                            ${p.total_quantity}
                        </span>
                    </td>
                    <td class="p-2.5 text-center font-mono">
                        ${restockBadge}
                    </td>
                    <td class="p-2.5 text-right font-black font-mono text-slate-700 dark:text-slate-300">
                        Rp ${Math.round(p.cost_price || 0).toLocaleString('id-ID')}
                    </td>
                    <td class="p-2.5 text-center text-slate-500">${p.rack_location || '-'}</td>
                    <td class="p-2.5 text-center">
                        <button type="button" onclick="event.stopPropagation(); selectModalItemByIndex(${idx})" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold text-[11px] shadow-2xs">
                            Pilih
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function navigateModalResults(delta) {
        if (modalResults.length === 0) return;
        modalHighlightIndex = (modalHighlightIndex + delta + modalResults.length) % modalResults.length;
        renderModalTable();
    }

    function selectHighlightedModalItem() {
        if (modalResults.length > 0 && modalHighlightIndex >= 0 && modalHighlightIndex < modalResults.length) {
            selectModalItemByIndex(modalHighlightIndex);
        }
    }

    function selectModalItemByIndex(idx) {
        const prod = modalResults[idx];
        if (prod) {
            addItemToPO(prod);
            closeItemModal();
        }
    }

    function addItemToPO(prod) {
        const existing = poItems.find(i => i.product_id === prod.id);
        if (existing) {
            existing.quantity = Math.round((existing.quantity + 1) * 100) / 100;
            renderPOTable();
            calculatePOTotal();
            return;
        }

        const baseUnit = prod.base_unit || (prod.units && prod.units[0]) || { id: '', unit_code: 'PCS', conversion_factor: 1 };
        const defaultUnit = prod.units && prod.units.length > 1 ? prod.units[prod.units.length - 1] : baseUnit;
        const conv = defaultUnit.conversion_factor || 1;

        const initialCost = prod.last_purchase_price > 0 ? (prod.last_purchase_price * conv) : (prod.cost_price * conv);
        // Default kuantitas: gunakan saran restok jika ada
        const suggestedQty = prod.recommended_restock > 0 ? Math.max(1, Math.ceil(prod.recommended_restock / conv)) : 1;

        poItems.push({
            product_id: prod.id,
            product_name: prod.name,
            sku: prod.sku || '',
            barcode: prod.barcode || '',
            total_quantity: prod.total_quantity || 0,
            recommended_restock: prod.recommended_restock || 0,
            base_unit_code: baseUnit.unit_code,
            product_unit_id: defaultUnit.id,
            units: prod.units || [baseUnit],
            quantity: suggestedQty,
            unit_price: initialCost
        });

        renderPOTable();
        calculatePOTotal();
    }

    function formatInputNumber(val) {
        if (val === null || val === undefined || val === '') return '0';
        const num = parseFloat(val);
        if (isNaN(num)) return '0';
        return num.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
    }

    function parseFormattedNumber(val) {
        if (val === null || val === undefined || val === '') return 0;
        if (typeof val === 'number') return val;
        let cleaned = val.toString().trim();
        cleaned = cleaned.replace(/\./g, '');
        cleaned = cleaned.replace(/,/g, '.');
        const num = parseFloat(cleaned);
        return isNaN(num) ? 0 : num;
    }

    function formatRupiah(val, minDec = 0, maxDec = 2) {
        const num = parseFloat(val);
        if (isNaN(num)) return 'Rp 0';
        const formatted = num.toLocaleString('id-ID', {
            minimumFractionDigits: minDec,
            maximumFractionDigits: maxDec
        });
        return 'Rp ' + formatted;
    }

    function renderPOTable() {
        const tbody = document.getElementById('poTableBody');
        const emptyRow = document.getElementById('emptyRow');
        if (!tbody) return;

        if (poItems.length === 0) {
            tbody.innerHTML = '';
            if (emptyRow) emptyRow.style.display = '';
            return;
        }

        if (emptyRow) emptyRow.style.display = 'none';

        let html = '';
        poItems.forEach((item, index) => {
            const subtotal = item.quantity * item.unit_price;
            let unitOptions = '';
            (item.units || []).forEach(u => {
                const isSel = u.id === item.product_unit_id ? 'selected' : '';
                unitOptions += `<option value="${u.id}" ${isSel}>${u.unit_code} (x${u.conversion_factor})</option>`;
            });

            html += `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="p-2.5 font-bold text-center text-slate-500">${index + 1}</td>
                    <td class="p-2.5">
                        <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                        <div class="font-bold text-slate-800 dark:text-white">${item.product_name}</div>
                        <div class="text-[10px] text-slate-400 font-mono">SKU: ${item.sku}</div>
                    </td>
                    <td class="p-2.5">
                        <select name="items[${index}][product_unit_id]" onchange="onPOUnitChange(${index}, this.value)"
                                class="w-full px-2 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-semibold">
                            ${unitOptions}
                        </select>
                    </td>
                    <td class="p-2.5 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                        ${item.total_quantity}
                    </td>
                    <td class="p-2.5 text-right">
                        <input type="hidden" name="items[${index}][quantity]" id="qty-hidden-${index}" value="${item.quantity}">
                        <input type="text"
                               id="qty-input-${index}"
                               value="${formatInputNumber(item.quantity)}"
                               oninput="onPOQtyChange(${index}, this.value)"
                               onblur="onPOQtyBlur(${index}, this)"
                               class="w-24 px-2 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-right focus:ring-2 focus:ring-blue-500">
                        <div id="qty-preview-${index}" class="text-[10px] text-slate-400 font-semibold mt-0.5">
                            ${item.quantity} ${item.base_unit_code || 'PCS'}
                        </div>
                    </td>
                    <td class="p-2.5 text-right">
                        <input type="hidden" name="items[${index}][unit_price]" id="price-hidden-${index}" value="${item.unit_price}">
                        <input type="text"
                               id="price-input-${index}"
                               value="${formatInputNumber(item.unit_price)}"
                               oninput="onPOPriceChange(${index}, this.value)"
                               onblur="onPOPriceBlur(${index}, this)"
                               class="w-32 px-2 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono font-bold text-right focus:ring-2 focus:ring-blue-500">
                        <div id="price-preview-${index}" class="text-[10px] text-slate-400 font-semibold mt-0.5">
                            ${formatRupiah(item.unit_price, 0, 2)}
                        </div>
                    </td>
                    <td class="p-2.5 text-right font-mono font-extrabold text-slate-900 dark:text-white" id="subtotal-${index}">
                        ${formatRupiah(subtotal, 0, 2)}
                    </td>
                    <td class="p-2.5 text-center">
                        <button type="button" onclick="removePOItem(${index})" class="text-rose-500 hover:text-rose-700 font-bold text-base cursor-pointer">
                            &times;
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function updateRowDisplay(index) {
        const item = poItems[index];
        if (!item) return;

        const subtotal = item.quantity * item.unit_price;
        const subtotalEl = document.getElementById(`subtotal-${index}`);
        if (subtotalEl) {
            subtotalEl.innerText = formatRupiah(subtotal, 0, 2);
        }
    }

    function onPOUnitChange(index, unitId) {
        const item = poItems[index];
        if (!item) return;

        const oldUnit = item.units.find(u => u.id === item.product_unit_id);
        const newUnit = item.units.find(u => u.id === unitId);

        const oldConv = oldUnit ? (parseFloat(oldUnit.conversion_factor) || 1) : 1;
        const newConv = newUnit ? (parseFloat(newUnit.conversion_factor) || 1) : 1;

        const basePrice = item.unit_price / oldConv;
        item.product_unit_id = unitId;
        item.unit_price = Math.round(basePrice * newConv);

        renderPOTable();
        calculatePOTotal();
    }

    function onPOQtyChange(index, val) {
        const item = poItems[index];
        if (item) {
            item.quantity = parseFormattedNumber(val);
            const hiddenEl = document.getElementById(`qty-hidden-${index}`);
            if (hiddenEl) hiddenEl.value = item.quantity;

            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { unit_code: item.base_unit_code || 'PCS' };
            const previewEl = document.getElementById(`qty-preview-${index}`);
            if (previewEl) previewEl.innerText = `${item.quantity} ${currentUnit.unit_code}`;

            updateRowDisplay(index);
            calculatePOTotal();
        }
    }

    function onPOQtyBlur(index, input) {
        const item = poItems[index];
        if (item) {
            input.value = formatInputNumber(item.quantity);
        }
    }

    function onPOPriceChange(index, val) {
        const item = poItems[index];
        if (item) {
            item.unit_price = parseFormattedNumber(val);
            const hiddenEl = document.getElementById(`price-hidden-${index}`);
            if (hiddenEl) hiddenEl.value = item.unit_price;

            const previewEl = document.getElementById(`price-preview-${index}`);
            if (previewEl) previewEl.innerText = formatRupiah(item.unit_price, 0, 2);

            updateRowDisplay(index);
            calculatePOTotal();
        }
    }

    function onPOPriceBlur(index, input) {
        const item = poItems[index];
        if (item) {
            input.value = formatInputNumber(item.unit_price);
            const previewEl = document.getElementById(`price-preview-${index}`);
            if (previewEl) previewEl.innerText = formatRupiah(item.unit_price, 0, 2);
        }
    }

    function removePOItem(index) {
        poItems.splice(index, 1);
        renderPOTable();
        calculatePOTotal();
    }

    function calculatePOTotal() {
        let total = 0;
        poItems.forEach(item => {
            total += (item.quantity * item.unit_price);
        });

        document.getElementById('totalRows').innerText = `${poItems.length} Baris`;
        document.getElementById('grandTotalDisplay').innerText = formatRupiah(total, 0, 2);
    }

    function submitPO() {
        if (poItems.length === 0) {
            alert('Silakan masukkan minimal 1 barang ke dalam pesanan pembelian (PO).');
            document.getElementById('quickSearchInput')?.focus();
            return;
        }

        const supplierSelect = document.getElementById('supplierSelect');
        if (!supplierSelect.value) {
            alert('Silakan pilih Supplier / Pemasok terlebih dahulu!');
            supplierSelect.focus();
            return;
        }

        document.getElementById('poForm').submit();
    }
</script>
@endsection
