@extends('layouts.app')

@section('title', 'Buat Pesanan Penjualan (SO)')
@section('tab_title', 'Tambah Sales Order')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full">

    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.orders.index') }}" class="hover:underline">Pesanan Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>➕</span> Buat Pesanan Penjualan (Sales Order)
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-lg text-xs font-mono">
                <kbd class="font-bold">[F2]</kbd> Cari Item Cepat
            </span>
            <a href="{{ route('sales.orders.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('sales.orders.store') }}" method="POST" id="soForm" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <!-- HEADER DATA -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Cabang Penjual *</label>
                <select name="store_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pelanggan / Pemesan</label>
                <select name="customer_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    <option value="">-- Pelanggan Umum (Non-Member) --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Pesanan *</label>
                <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
        </div>

        <!-- SEARCH BAR CEPAT & KETOKO LOOKUP -->
        <div class="bg-gradient-to-r from-blue-700 to-indigo-800 p-3.5 rounded-xl text-white shadow-md">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-extrabold uppercase tracking-wider text-blue-100 flex items-center gap-1.5">
                    <span>⚡</span> Scan Barcode / Cari & Tambah Barang ke Pesanan (SO)
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
                    Cari / Tambah
                </button>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📦</span> Daftar Barang Pesanan (SO)
                </h3>
                <button type="button" onclick="openItemModal()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg transition-all cursor-pointer shadow-xs flex items-center gap-1">
                    <span>+</span> Tambah Barang [F2]
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 min-w-[240px]">Produk / SKU</th>
                            <th class="py-2.5 px-3 w-32">Satuan</th>
                            <th class="py-2.5 px-3 w-24 text-center">Jumlah</th>
                            <th class="py-2.5 px-3 w-32 text-right">Harga Satuan (Rp)</th>
                            <th class="py-2.5 px-3 w-28 text-right">Diskon (Rp)</th>
                            <th class="py-2.5 px-3 text-right w-36">Subtotal</th>
                            <th class="py-2.5 px-3 text-center w-12">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="tableBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
            <div id="emptyTableNotice" class="text-center py-6 text-slate-400 text-xs border-b border-x border-slate-200 dark:border-slate-800 rounded-b-xl">
                Belum ada barang dalam pesanan. Ketik kata kunci di atas atau tekan <strong class="text-blue-600 dark:text-blue-400">[F2] Cari Item</strong>.
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Pesanan / Pengiriman</label>
                <textarea name="notes" rows="3" placeholder="Instruksi pengiriman, PO referensi pelanggan, dll..."
                          class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2"></textarea>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl p-4 flex flex-col justify-between">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>Total Estimasi Pesanan:</span>
                </div>
                <div class="text-2xl font-black text-blue-700 dark:text-blue-400 font-mono" id="grandTotalDisplay">
                    Rp 0
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    Pesanan dapat dikonfirmasi dan diproses langsung menjadi faktur kasir POS.
                </div>
            </div>
        </div>

        <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                Simpan Pesanan Penjualan (SO)
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
                    <h3 class="text-sm font-black">Cari Item Produk &amp; Informasi Stok Fisik (Sales Order)</h3>
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
    let soItems = [];
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

    async function handleQuickSearchEnter() {
        const input = document.getElementById('quickSearchInput');
        const q = input ? input.value.trim() : '';
        if (q.length < 1) {
            openItemModal('');
            return;
        }

        try {
            const res = await fetch(`{{ route('sales.search-product') }}?q=${encodeURIComponent(q)}&limit=10`);
            const data = await res.json();

            if (data.length === 1) {
                addItemToSO(data[0]);
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

        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-6 text-slate-400">Sedang memuat data...</td></tr>`;

        try {
            const res = await fetch(`{{ route('sales.search-product') }}?q=${encodeURIComponent(q)}&limit=50`);
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
            addItemToSO(prod);
            closeItemModal();
        }
    }

    function addItemToSO(prod) {
        const existing = soItems.find(i => i.product_id === prod.id);
        if (existing) {
            existing.quantity = Math.round((existing.quantity + 1) * 100) / 100;
            renderSOTable();
            recalcSOTotal();
            return;
        }

        const baseUnit = prod.base_unit || (prod.units && prod.units[0]) || { id: '', unit_code: 'PCS', conversion_factor: 1 };
        const defaultPrice = prod.selling_price || 0;

        soItems.push({
            product_id: prod.id,
            product_name: prod.name,
            sku: prod.sku || '',
            barcode: prod.barcode || '',
            total_quantity: prod.total_quantity || 0,
            product_unit_id: baseUnit.id,
            units: prod.units || [baseUnit],
            quantity: 1,
            unit_price: defaultPrice,
            discount_amount: 0
        });

        renderSOTable();
        recalcSOTotal();
    }

    function renderSOTable() {
        const tbody = document.getElementById('tableBody');
        const emptyNotice = document.getElementById('emptyTableNotice');
        if (!tbody) return;

        if (soItems.length === 0) {
            tbody.innerHTML = '';
            if (emptyNotice) emptyNotice.style.display = 'block';
            return;
        }

        if (emptyNotice) emptyNotice.style.display = 'none';

        let html = '';
        soItems.forEach((item, index) => {
            const subtotal = Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);

            let unitOptions = '';
            item.units.forEach(u => {
                const sel = u.id === item.product_unit_id ? 'selected' : '';
                unitOptions += `<option value="${u.id}" ${sel}>${u.unit_code} (x${u.conversion_factor})</option>`;
            });

            html += `
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <td class="py-2 px-3">
                        <div class="font-bold text-slate-800 dark:text-white">${item.product_name}</div>
                        <div class="flex items-center gap-2 text-[10px] text-slate-400 font-mono mt-0.5">
                            <span>SKU: ${item.sku || '-'}</span>
                            <span>•</span>
                            <span class="font-bold ${item.total_quantity > 0 ? 'text-emerald-600' : 'text-rose-500'}">Stok: ${item.total_quantity}</span>
                        </div>
                        <input type="hidden" name="items[${index}][product_id]" value="${item.product_id}">
                    </td>
                    <td class="py-2 px-3">
                        <select name="items[${index}][product_unit_id]" onchange="onSOUnitChange(${index}, this.value)" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2">
                            ${unitOptions}
                        </select>
                    </td>
                    <td class="py-2 px-3">
                        <input type="number" step="any" min="0.01" name="items[${index}][quantity]" value="${item.quantity}" required
                               oninput="onSOQtyChange(${index}, this.value)"
                               class="w-full text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-center">
                    </td>
                    <td class="py-2 px-3">
                        <input type="number" step="any" min="0" name="items[${index}][unit_price]" value="${item.unit_price}" required
                               oninput="onSOPriceChange(${index}, this.value)"
                               class="w-full text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-right">
                    </td>
                    <td class="py-2 px-3">
                        <input type="number" step="any" min="0" name="items[${index}][discount_amount]" value="${item.discount_amount}"
                               oninput="onSODiscChange(${index}, this.value)"
                               class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-right font-semibold text-rose-600">
                    </td>
                    <td class="py-2 px-3 text-right font-black text-slate-800 dark:text-slate-200 font-mono" id="subtotal-${index}">
                        ${formatRupiah(subtotal, 0, 2)}
                    </td>
                    <td class="py-2 px-3 text-center">
                        <button type="button" onclick="removeSOItem(${index})" class="text-rose-500 hover:text-rose-700 font-bold text-sm cursor-pointer">
                            ✕
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function parseFormattedNumber(val) {
        if (val === null || val === undefined || val === '') return 0;
        if (typeof val === 'number') return val;
        let str = val.toString().trim();
        if (str.includes(',') && !str.includes('.')) {
            str = str.replace(',', '.');
        } else if (str.includes('.') && str.includes(',')) {
            str = str.replace(/\./g, '').replace(',', '.');
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

    function updateRowDisplay(index) {
        const item = soItems[index];
        if (!item) return;

        const subtotal = Math.max(0, (item.quantity * item.unit_price) - item.discount_amount);
        const subtotalEl = document.getElementById(`subtotal-${index}`);
        if (subtotalEl) {
            subtotalEl.innerText = formatRupiah(subtotal, 0, 2);
        }
    }

    function onSOUnitChange(index, unitId) {
        const item = soItems[index];
        if (!item) return;
        item.product_unit_id = unitId;
        renderSOTable();
        recalcSOTotal();
    }

    function onSOQtyChange(index, val) {
        const item = soItems[index];
        if (item) {
            item.quantity = parseFormattedNumber(val);
            updateRowDisplay(index);
            recalcSOTotal();
        }
    }

    function onSOPriceChange(index, val) {
        const item = soItems[index];
        if (item) {
            item.unit_price = parseFormattedNumber(val);
            updateRowDisplay(index);
            recalcSOTotal();
        }
    }

    function onSODiscChange(index, val) {
        const item = soItems[index];
        if (item) {
            item.discount_amount = parseFormattedNumber(val);
            updateRowDisplay(index);
            recalcSOTotal();
        }
    }

    function removeSOItem(index) {
        soItems.splice(index, 1);
        renderSOTable();
        recalcSOTotal();
    }

    function recalcSOTotal() {
        let grand = 0;
        soItems.forEach(item => {
            const sub = (item.quantity * item.unit_price) - item.discount_amount;
            grand += Math.max(0, sub);
        });
        document.getElementById('grandTotalDisplay').innerText = formatRupiah(grand, 0, 2);
    }
</script>
@endsection
