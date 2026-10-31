@extends('layouts.app')

@section('title', 'Input Faktur Penjualan Back-Office')
@section('tab_title', 'Faktur Penjualan Baru')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.transactions') }}" class="hover:underline">Riwayat Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Faktur Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📄</span> Input Faktur Penjualan Back-Office (Direct Entry)
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-mono">
                <kbd class="font-bold">[F2]</kbd> Cari Item Cepat
            </span>
            <a href="{{ route('sales.transactions') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('sales.invoices.store') }}" method="POST" id="invoiceForm" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <!-- HEADER FAKTUR DATA -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Gudang Penjual *</label>
                <select name="store_id" id="storeSelect" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pelanggan</label>
                <select name="customer_id" id="customerSelect" onchange="onCustomerChange(this)" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    <option value="" data-tier="">-- Pelanggan Umum (Walk-in) --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-tier="{{ $c->customer_tier_id }}">{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tingkatan Harga (Tier)</label>
                <select name="customer_tier_id" id="tierSelect" onchange="onTierChange()" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    @foreach($tiers as $tier)
                        <option value="{{ $tier->id }}" {{ $tier->is_default ? 'selected' : '' }}>{{ $tier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Transaksi</label>
                <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" readonly
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-500 px-3 py-2">
            </div>
        </div>

        <!-- METODE PEMBAYARAN & TEMPO -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Pembayaran *</label>
                <select name="payment_method" id="paymentMethod" onchange="togglePaymentFields()" required class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    <option value="CASH">💵 TUNAI (CASH)</option>
                    <option value="TRANSFER">🏦 TRANSFER BANK / QRIS</option>
                    <option value="CREDIT">⏳ KREDIT / TEMPO (PIUTANG)</option>
                    <option value="SPLIT">🔀 SPLIT (SEBAGIAN TUNAI)</option>
                </select>
            </div>

            <div id="dueDateDiv" style="display: none;">
                <label class="block text-xs font-bold text-amber-700 dark:text-amber-400 mb-1">Jatuh Tempo (Due Date) *</label>
                <input type="date" name="due_date" id="dueDate" value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                       class="w-full text-xs rounded-xl border border-amber-300 dark:border-amber-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nominal Dibayar (Rp) *</label>
                <input type="number" step="any" name="paid_amount" id="paidAmount" value="0" required
                       class="w-full text-xs font-black text-blue-700 dark:text-blue-400 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2"
                       oninput="calculateTotal()">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Voucher Belanja</label>
                <input type="text" name="voucher_code" placeholder="Contoh: VCH-XXXX-XXXX"
                       class="w-full text-xs font-mono uppercase rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
        </div>

        <!-- SEARCH BAR CEPAT & KETOKO-STYLE ITEM LOOKUP -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-3.5 rounded-xl text-white shadow-md">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-extrabold uppercase tracking-wider text-blue-100 flex items-center gap-1.5">
                    <span>⚡</span> Scan Barcode / Input Barang Cepat (Tekan Enter untuk Cari Varian)
                </label>
                <button type="button" onclick="openItemModal()" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                    <span>🔍</span> Cari Item Lengkap <kbd class="bg-blue-900/60 px-1.5 py-0.2 rounded font-mono text-[10px]">[F2]</kbd>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">🔍</span>
                    <input type="text"
                           id="quickSearchInput"
                           placeholder="Scan barcode dengan scanner, atau ketik kata kunci (misal 'minyak') lalu tekan Enter..."
                           class="w-full pl-9 pr-4 py-2 bg-white text-slate-900 placeholder-slate-400 text-xs font-semibold rounded-lg shadow-inner focus:outline-hidden focus:ring-2 focus:ring-blue-400">
                </div>
                <button type="button" onclick="handleQuickSearchEnter()" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 font-bold text-xs rounded-lg shadow-xs cursor-pointer transition-colors">
                    Cari / Masukkan
                </button>
            </div>
        </div>

        <!-- DAFTAR ITEM BARANG -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📦</span> Rincian Barang yang Dijual
                </h3>
                <button type="button" onclick="openItemModal()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg transition-all cursor-pointer shadow-xs flex items-center gap-1">
                    <span>+</span> Tambah Barang [F2]
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 min-w-[240px]">Nama Produk / Barcode</th>
                            <th class="py-2.5 px-3 w-32">Satuan (UOM)</th>
                            <th class="py-2.5 px-3 w-24 text-center">Jumlah</th>
                            <th class="py-2.5 px-3 w-32 text-right">Harga Satuan (Rp)</th>
                            <th class="py-2.5 px-3 w-28 text-right">Diskon (Rp)</th>
                            <th class="py-2.5 px-3 text-right w-36">Subtotal (Rp)</th>
                            <th class="py-2.5 px-3 text-center w-12">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="tableBody">
                        <!-- Dynamic JS Rows -->
                    </tbody>
                </table>
            </div>
            <div id="emptyTableNotice" class="text-center py-6 text-slate-400 text-xs border-b border-x border-slate-200 dark:border-slate-800 rounded-b-xl">
                Belum ada barang dalam faktur. Ketik kata kunci di atas atau tekan <strong class="text-blue-600 dark:text-blue-400">[F2] Cari Item</strong>.
            </div>
        </div>

        <!-- FOOTER & RINGKASAN -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Faktur / Nomor Referensi PO Pelanggan</label>
                <textarea name="notes" rows="3" placeholder="Misal: Penjualan Grosir Proyek / No PO: PO-2026-99"
                          class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2"></textarea>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Total Baris:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300" id="totalRows">0 Item</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300" id="subtotalDisplay">Rp 0</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>Diskon Baris:</span>
                    <span class="font-bold text-emerald-600" id="discountDisplay">- Rp 0</span>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-slate-900 dark:text-white">TOTAL FAKTUR:</span>
                    <span class="text-lg font-black text-blue-600 dark:text-blue-400" id="grandTotalDisplay">Rp 0</span>
                </div>
                <div class="flex items-center justify-between text-xs pt-1 border-t border-dashed border-slate-200 dark:border-slate-700">
                    <span class="text-slate-500" id="balanceLabel">Kembalian / Sisa Piutang:</span>
                    <span class="font-bold" id="balanceDisplay">Rp 0</span>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <input type="hidden" name="action" id="actionField" value="save">
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-end gap-2">
            <button type="button" onclick="submitWithAction('save')" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                💾 Simpan Faktur Saja
            </button>
            <button type="button" onclick="submitWithAction('print_do')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                🚚 Simpan & Cetak Surat Jalan
            </button>
            <button type="button" onclick="submitWithAction('print_invoice')" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl shadow-sm transition-all cursor-pointer flex items-center gap-1.5">
                <span>📄</span> Simpan & Cetak Faktur (A4 / Dot Matrix)
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
                    <h3 class="text-sm font-black">Cari Item Produk &amp; Informasi Stok Fisik (Penjualan)</h3>
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
                        <th class="p-2.5 text-center">Stok Fisik</th>
                        <th class="p-2.5 text-right">Harga Jual</th>
                        <th class="p-2.5 text-center">Rak</th>
                        <th class="p-2.5 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody id="modalTableBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td colspan="8" class="text-center py-8 text-slate-400">
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
    let invoiceItems = [];
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
                submitWithAction('save');
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
    });

    function onCustomerChange(select) {
        const option = select.options[select.selectedIndex];
        const tierId = option.getAttribute('data-tier');
        if (tierId) {
            const tierSelect = document.getElementById('tierSelect');
            if (tierSelect) {
                tierSelect.value = tierId;
                onTierChange();
            }
        }
    }

    function onTierChange() {
        const tierId = document.getElementById('tierSelect')?.value;
        if (!tierId || invoiceItems.length === 0) return;

        // Auto-update harga satuan jika ada harga tier untuk produk tsb
        invoiceItems.forEach(item => {
            if (item.product && item.product.prices) {
                const pr = item.product.prices.find(p => p.customer_tier_id === tierId && p.product_unit_id === item.product_unit_id);
                if (pr) {
                    item.unit_price = pr.price;
                }
            }
        });
        renderTable();
        calculateTotal();
    }

    function togglePaymentFields() {
        const method = document.getElementById('paymentMethod').value;
        const dueDiv = document.getElementById('dueDateDiv');
        const paidInput = document.getElementById('paidAmount');
        const grandTotal = getGrandTotalNumber();

        if (method === 'CREDIT') {
            dueDiv.style.display = 'block';
            paidInput.value = 0;
        } else {
            dueDiv.style.display = 'none';
            if (paidInput.value == 0 && grandTotal > 0) {
                paidInput.value = grandTotal;
            }
        }
        calculateTotal();
    }

    async function handleQuickSearchEnter() {
        const input = document.getElementById('quickSearchInput');
        const q = input ? input.value.trim() : '';
        if (q.length < 1) {
            openItemModal('');
            return;
        }

        const tierId = document.getElementById('tierSelect')?.value || '';
        try {
            const res = await fetch(`{{ route('sales.search-product') }}?q=${encodeURIComponent(q)}&tier_id=${encodeURIComponent(tierId)}&limit=10`);
            const data = await res.json();

            if (data.length === 1) {
                // Exact 1 match: langsung tambahkan ke faktur!
                addItemToInvoice(data[0]);
                input.value = '';
            } else {
                // Multi match atau tidak ada: buka modal pencarian lengkap
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
            if (initialQuery.trim().length >= 1) {
                fetchModalResults(initialQuery.trim());
            } else {
                fetchModalResults('');
            }
        }
    }

    function closeItemModal() {
        const modal = document.getElementById('itemModal');
        if (modal) modal.style.display = 'none';
        document.getElementById('quickSearchInput')?.focus();
    }

    async function fetchModalResults(q) {
        const tierId = document.getElementById('tierSelect')?.value || '';
        const tbody = document.getElementById('modalTableBody');
        const countBadge = document.getElementById('modalResultCountBadge');

        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400">Sedang memuat data...</td></tr>`;

        try {
            const res = await fetch(`{{ route('sales.search-product') }}?q=${encodeURIComponent(q)}&tier_id=${encodeURIComponent(tierId)}&limit=50`);
            modalResults = await res.json();
            modalHighlightIndex = 0;

            if (countBadge) countBadge.innerText = `Ditemukan: ${modalResults.length} item`;
            renderModalTable();
        } catch (e) {
            console.error('Fetch modal items error', e);
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-rose-500 font-bold">Gagal memuat data produk</td></tr>`;
        }
    }

    function renderModalTable() {
        const tbody = document.getElementById('modalTableBody');
        if (!tbody) return;

        if (modalResults.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400">Tidak ada produk ditemukan.</td></tr>`;
            return;
        }

        let html = '';
        modalResults.forEach((p, idx) => {
            const isHigh = idx === modalHighlightIndex;
            const stockColor = p.total_quantity > 0 ? 'text-emerald-700 bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300' : 'text-rose-700 bg-rose-100 dark:bg-rose-950 dark:text-rose-300';
            const bgClass = isHigh ? 'bg-blue-100/70 dark:bg-blue-900/40 ring-1 ring-blue-500 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/40';

            html += `
                <tr class="transition-colors cursor-pointer ${bgClass}" onclick="selectModalItemByIndex(${idx})">
                    <td class="p-2.5 font-mono text-slate-700 dark:text-slate-300">${p.sku || '-'}</td>
                    <td class="p-2.5 font-mono text-slate-500">${p.barcode || '-'}</td>
                    <td class="p-2.5 font-bold text-slate-800 dark:text-white">${p.name}</td>
                    <td class="p-2.5 text-center text-slate-500">${p.base_unit ? p.base_unit.unit_code : 'PCS'}</td>
                    <td class="p-2.5 text-center font-mono">
                        <span class="px-2 py-0.5 rounded text-[11px] font-black ${stockColor}">
                            ${p.total_quantity}
                        </span>
                    </td>
                    <td class="p-2.5 text-right font-black font-mono text-blue-700 dark:text-blue-400">
                        Rp ${Math.round(p.selling_price || 0).toLocaleString('id-ID')}
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
            addItemToInvoice(prod);
            closeItemModal();
        }
    }

    function addItemToInvoice(prod) {
        // Cek apakah produk sudah ada di baris
        const existing = invoiceItems.find(i => i.product_id === prod.id);
        if (existing) {
            existing.quantity = Math.round((existing.quantity + 1) * 100) / 100;
            renderTable();
            calculateTotal();
            return;
        }

        const baseUnit = prod.base_unit || (prod.units && prod.units[0]) || { id: '', unit_code: 'PCS', conversion_factor: 1 };
        const defaultPrice = prod.selling_price || 0;

        invoiceItems.push({
            unique_id: 'item_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4),
            product_id: prod.id,
            product_name: prod.name,
            sku: prod.sku || '',
            barcode: prod.barcode || '',
            total_quantity: prod.total_quantity || 0,
            product: prod,
            product_unit_id: baseUnit.id,
            units: prod.units || [baseUnit],
            quantity: 1,
            unit_price: defaultPrice,
            discount_amount: 0
        });

        renderTable();
        calculateTotal();
    }

    function formatInputNumber(val) {
        if (val === null || val === undefined || val === '') return '0';
        const num = parseFloat(val);
        if (isNaN(num)) return '0';
        const parts = num.toString().split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return parts.length > 1 ? parts[0] + ',' + parts[1] : parts[0];
    }

    function parseFormattedNumber(val) {
        if (val === null || val === undefined || val === '') return 0;
        if (typeof val === 'number') return isNaN(val) ? 0 : val;
        let str = val.toString().trim();
        if (!str) return 0;
        str = str.replace(/[^\d.,-]/g, '');
        
        if (str.includes('.') && str.includes(',')) {
            str = str.replace(/\./g, '').replace(',', '.');
        } else if (str.includes(',') && !str.includes('.')) {
            str = str.replace(',', '.');
        } else if (str.includes('.') && !str.includes(',')) {
            const dotCount = (str.match(/\./g) || []).length;
            if (dotCount > 1) {
                str = str.replace(/\./g, '');
            } else {
                const parts = str.split('.');
                if (parts[1].length === 3 && parts[0].length >= 1) {
                    str = str.replace('.', '');
                }
            }
        }
        const num = parseFloat(str);
        return isNaN(num) ? 0 : num;
    }

    function formatRupiah(val, minDec = 0, maxDec = 2) {
        const num = parseFloat(val) || 0;
        const hasDec = (num % 1 !== 0);
        const dec = hasDec ? Math.min(2, Math.max(minDec, 2)) : minDec;
        return 'Rp ' + num.toLocaleString('id-ID', {
            minimumFractionDigits: dec,
            maximumFractionDigits: maxDec
        });
    }

    function renderTable() {
        const tbody = document.getElementById('tableBody');
        const emptyNotice = document.getElementById('emptyTableNotice');
        if (!tbody) return;

        if (invoiceItems.length === 0) {
            tbody.innerHTML = '';
            if (emptyNotice) emptyNotice.style.display = 'block';
            return;
        }

        if (emptyNotice) emptyNotice.style.display = 'none';

        let html = '';
        invoiceItems.forEach((item, index) => {
            const subtotal = Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);
            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { conversion_factor: 1, unit_code: (item.product && item.product.base_unit ? item.product.base_unit.unit_code : 'PCS') };

            let unitOptions = '';
            item.units.forEach(u => {
                const sel = u.id === item.product_unit_id ? 'selected' : '';
                unitOptions += `<option value="${u.id}" ${sel}>${u.unit_code} (x${u.conversion_factor})</option>`;
            });

            html += `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="py-2.5 px-3">
                        <div class="font-bold text-slate-800 dark:text-white">${item.product_name}</div>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono mt-0.5">
                            <span>SKU: ${item.sku || '-'}</span>
                            <span>•</span>
                            <span class="font-bold ${item.total_quantity > 0 ? 'text-emerald-600' : 'text-rose-500'}">Stok: ${item.total_quantity}</span>
                        </div>
                        <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                    </td>
                    <td class="py-2.5 px-3">
                        <select name="items[${index}][product_unit_id]" onchange="onItemUnitChange(${index}, this.value)" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2">
                            ${unitOptions}
                        </select>
                    </td>
                    <td class="py-2.5 px-3">
                        <div class="relative">
                            <input type="text"
                                   inputmode="decimal"
                                   id="qty-input-${index}"
                                   value="${formatInputNumber(item.quantity)}"
                                   required
                                   onfocus="this.select()"
                                   oninput="onItemQtyChange(${index}, this.value)"
                                   onblur="onItemQtyBlur(${index}, this)"
                                   placeholder="1"
                                   class="w-full text-xs font-bold font-mono rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-center focus:ring-2 focus:ring-emerald-500 shadow-sm">
                            <input type="hidden" name="items[${index}][quantity]" id="qty-hidden-${index}" value="${item.quantity}">
                        </div>
                        <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 text-center mt-1" id="qty-preview-${index}">
                            ${item.quantity} ${currentUnit.unit_code}
                        </div>
                    </td>
                    <td class="py-2.5 px-3">
                        <div class="relative">
                            <input type="text"
                                   inputmode="decimal"
                                   id="price-input-${index}"
                                   value="${formatInputNumber(item.unit_price)}"
                                   required
                                   onfocus="this.select()"
                                   oninput="onItemPriceChange(${index}, this.value)"
                                   onblur="onItemPriceBlur(${index}, this)"
                                   placeholder="0"
                                   class="w-full text-xs font-bold font-mono rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-right focus:ring-2 focus:ring-indigo-500 shadow-sm">
                            <input type="hidden" name="items[${index}][unit_price]" id="price-hidden-${index}" value="${item.unit_price}">
                        </div>
                        <div class="text-[11px] font-black text-emerald-700 dark:text-emerald-400 font-mono text-right mt-1 tracking-tight" id="price-preview-${index}">
                            ${formatRupiah(item.unit_price, 0, 2)}
                        </div>
                    </td>
                    <td class="py-2.5 px-3">
                        <input type="number" step="any" min="0" name="items[${index}][discount_amount]" value="${item.discount_amount}"
                               oninput="onItemDiscChange(${index}, this.value)"
                               class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-right font-semibold text-rose-600">
                    </td>
                    <td class="py-2.5 px-3 text-right font-black text-slate-800 dark:text-slate-200 font-mono" id="subtotal-${index}">
                        ${formatRupiah(subtotal, 0, 2)}
                    </td>
                    <td class="py-2.5 px-3 text-center">
                        <button type="button" onclick="removeItem(${index})" class="text-rose-500 hover:text-rose-700 font-bold text-sm cursor-pointer p-1">
                            ✕
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function updateRowDisplay(index) {
        const item = invoiceItems[index];
        if (!item) return;

        const subtotal = Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);
        const subtotalEl = document.getElementById(`subtotal-${index}`);
        if (subtotalEl) {
            subtotalEl.innerText = formatRupiah(subtotal, 0, 2);
        }
    }

    function onItemUnitChange(index, unitId) {
        const item = invoiceItems[index];
        if (!item) return;

        const oldUnit = item.units.find(u => u.id === item.product_unit_id);
        const newUnit = item.units.find(u => u.id === unitId);

        const oldConv = oldUnit ? (parseFloat(oldUnit.conversion_factor) || 1) : 1;
        const newConv = newUnit ? (parseFloat(newUnit.conversion_factor) || 1) : 1;

        const basePrice = item.unit_price / oldConv;
        item.product_unit_id = unitId;
        item.unit_price = Math.round(basePrice * newConv);

        renderTable();
        calculateTotal();
    }

    function onItemQtyChange(index, val) {
        const item = invoiceItems[index];
        if (item) {
            item.quantity = parseFormattedNumber(val);
            const hiddenEl = document.getElementById(`qty-hidden-${index}`);
            if (hiddenEl) hiddenEl.value = item.quantity;

            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { unit_code: 'PCS' };
            const previewEl = document.getElementById(`qty-preview-${index}`);
            if (previewEl) previewEl.innerText = `${item.quantity} ${currentUnit.unit_code}`;

            updateRowDisplay(index);
            calculateTotal();
        }
    }

    function onItemQtyBlur(index, input) {
        const item = invoiceItems[index];
        if (item) {
            input.value = formatInputNumber(item.quantity);
        }
    }

    function onItemPriceChange(index, val) {
        const item = invoiceItems[index];
        if (item) {
            item.unit_price = parseFormattedNumber(val);
            const hiddenEl = document.getElementById(`price-hidden-${index}`);
            if (hiddenEl) hiddenEl.value = item.unit_price;

            const previewEl = document.getElementById(`price-preview-${index}`);
            if (previewEl) previewEl.innerText = formatRupiah(item.unit_price, 0, 2);

            updateRowDisplay(index);
            calculateTotal();
        }
    }

    function onItemPriceBlur(index, input) {
        const item = invoiceItems[index];
        if (item) {
            input.value = formatInputNumber(item.unit_price);
            const previewEl = document.getElementById(`price-preview-${index}`);
            if (previewEl) previewEl.innerText = formatRupiah(item.unit_price, 0, 2);
        }
    }

    function onItemDiscChange(index, val) {
        const item = invoiceItems[index];
        if (item) {
            item.discount_amount = parseFormattedNumber(val);
            updateRowDisplay(index);
            calculateTotal();
        }
    }

    function removeItem(index) {
        invoiceItems.splice(index, 1);
        renderTable();
        calculateTotal();
    }

    function getGrandTotalNumber() {
        let total = 0;
        invoiceItems.forEach(item => {
            const rowSub = (item.quantity * item.unit_price) - item.discount_amount;
            total += Math.max(0, rowSub);
        });
        return total;
    }

    function calculateTotal() {
        let totalSubtotal = 0;
        let totalDisc = 0;
        let grandTotal = 0;

        invoiceItems.forEach(item => {
            const rowSub = item.quantity * item.unit_price;
            totalSubtotal += rowSub;
            totalDisc += item.discount_amount;
            grandTotal += Math.max(0, rowSub - item.discount_amount);
        });

        document.getElementById('totalRows').innerText = `${invoiceItems.length} Baris`;
        document.getElementById('subtotalDisplay').innerText = formatRupiah(totalSubtotal, 0, 2);
        document.getElementById('discountDisplay').innerText = '- ' + formatRupiah(totalDisc, 0, 2);
        document.getElementById('grandTotalDisplay').innerText = formatRupiah(grandTotal, 0, 2);

        const method = document.getElementById('paymentMethod').value;
        const paidInput = document.getElementById('paidAmount');
        let paid = parseFormattedNumber(paidInput.value || 0);

        // Jika non-kredit dan belum diisi, auto-isi paid dengan grandTotal
        if (method !== 'CREDIT' && paid === 0 && grandTotal > 0) {
            paid = grandTotal;
            paidInput.value = grandTotal;
        }

        const balance = paid - grandTotal;
        const balLabel = document.getElementById('balanceLabel');
        const balDisplay = document.getElementById('balanceDisplay');

        if (balance >= 0) {
            balLabel.innerText = 'Kembalian:';
            balDisplay.innerText = formatRupiah(balance, 0, 2);
            balDisplay.className = 'font-bold text-emerald-600 font-mono';
        } else {
            balLabel.innerText = 'Sisa Piutang / Tempo:';
            balDisplay.innerText = formatRupiah(Math.abs(balance), 0, 2);
            balDisplay.className = 'font-bold text-amber-600 font-mono';
        }
    }

    function submitWithAction(actionType) {
        if (invoiceItems.length === 0) {
            alert('Silakan masukkan minimal 1 barang ke dalam faktur penjualan.');
            document.getElementById('quickSearchInput')?.focus();
            return;
        }
        document.getElementById('actionField').value = actionType;
        document.getElementById('invoiceForm').submit();
    }
</script>
@endsection
