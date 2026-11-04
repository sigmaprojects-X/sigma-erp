@extends('layouts.app')

@section('title', 'Input Penerimaan Barang / Faktur Pembelian')
@section('tab_title', 'Faktur Pembelian Baru')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- FORM HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:underline">Daftar Pembelian</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Faktur Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📥</span> Input Penerimaan Barang / Faktur Pembelian (Direct Entry)
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-mono">
                <kbd class="font-bold">[F2]</kbd> Cari Item Cepat
            </span>
            <a href="{{ route('purchases.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <!-- INFORMASI HEADER FAKTUR -->
        <div class="bg-slate-50 dark:bg-slate-800/40 p-4 rounded-xl border border-slate-200 dark:border-slate-800 space-y-3">
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider pb-2 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span>📋 Data Faktur Supplier &amp; Transaksi</span>
                <span class="text-[11px] font-mono text-emerald-700 dark:text-emerald-300 bg-emerald-100/60 dark:bg-emerald-950/60 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                    No. Bukti: <strong id="refNumberDisplay">{{ $autoRefNumber }}</strong>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <!-- Gudang Penerima -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Gudang Penerima <span class="text-rose-500">*</span>
                    </label>
                    <select name="store_id" id="storeSelect" required class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-semibold">
                        <option value="{{ $store->id }}">{{ $store->name }} (Pusat / HO)</option>
                    </select>
                </div>

                <!-- Supplier -->
                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Pemasok / Supplier <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id" id="supplierSelect" onchange="onSupplierChange()" required class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold">
                        <option value="">-- Pilih Supplier Pemasok --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}"
                                    data-term="{{ $s->term_of_payment_days }}"
                                    data-is-pkp="{{ $s->is_pkp ? '1' : '0' }}"
                                    data-npwp="{{ $s->npwp }}"
                                    data-tax-name="{{ $s->tax_name }}">
                                {{ $s->name }} ({{ $s->code ?: 'SUP' }}) • {{ $s->is_pkp ? 'PKP' : 'Non-PKP' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- No Faktur Supplier -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-bold text-slate-700 dark:text-slate-300">
                            No. Faktur Supplier
                        </label>
                        <button type="button" onclick="copyRefToInvoice()" class="text-[10px] text-emerald-600 dark:text-emerald-400 hover:underline font-bold cursor-pointer">
                            ⚡ Samakan No. Bukti
                        </button>
                    </div>
                    <input type="text" name="invoice_number" id="invoiceNumberInput" placeholder="(Otomatis jika kosong)"
                           class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-mono uppercase font-semibold">
                </div>

                <!-- Tanggal Beli -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Terima / Faktur *</label>
                    <input type="date" name="purchase_date" id="purchaseDate" value="{{ date('Y-m-d') }}" required
                           class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-medium">
                </div>

                <!-- Jenis Pembayaran -->
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Pembayaran *</label>
                    <select name="payment_type" id="paymentType" onchange="togglePaymentFields()" class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold">
                        <option value="CASH">💵 TUNAI (Cash)</option>
                        <option value="TRANSFER">🏦 TRANSFER BANK</option>
                        <option value="TEMPO">⏳ TEMPO (Hutang Dagang)</option>
                    </select>
                </div>

                <!-- Rekening Bank (Jika Transfer) -->
                <div id="bankAccountDiv" style="display: none;">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rekening Kas/Bank</label>
                    <select name="bank_account_id" id="bankAccountId" class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs">
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->bank_name }} - {{ $ba->account_number }} (a.n {{ $ba->account_holder }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Tanggal Jatuh Tempo (Jika Tempo) -->
                <div id="dueDateDiv" style="display: none;">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-bold text-amber-700 dark:text-amber-400">Jatuh Tempo (Hutang) *</label>
                        <span class="text-[10px] text-slate-400">Pilih Cepat:</span>
                    </div>
                    <input type="date" name="due_date" id="dueDate" value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                           class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 rounded-lg text-xs font-bold text-amber-900 dark:text-amber-300 font-mono shadow-xs">
                    <div class="flex flex-wrap items-center gap-1 mt-1.5">
                        <button type="button" onclick="setDueDatePreset(7)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900 dark:text-amber-300 transition-colors cursor-pointer">+7 Hari</button>
                        <button type="button" onclick="setDueDatePreset(14)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900 dark:text-amber-300 transition-colors cursor-pointer">+14 Hari</button>
                        <button type="button" onclick="setDueDatePreset(30)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900 dark:text-amber-300 transition-colors cursor-pointer">+30 Hari</button>
                        <button type="button" onclick="setDueDatePreset(45)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900 dark:text-amber-300 transition-colors cursor-pointer">+45 Hari</button>
                        <button type="button" onclick="setDueDatePreset(60)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 hover:bg-amber-200 text-amber-800 dark:bg-amber-950 dark:hover:bg-amber-900 dark:text-amber-300 transition-colors cursor-pointer">+60 Hari</button>
                        <button type="button" onclick="setDueDatePreset('eom')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-100 hover:bg-indigo-200 text-indigo-800 dark:bg-indigo-950 dark:hover:bg-indigo-900 dark:text-indigo-300 transition-colors cursor-pointer">Akhir Bulan</button>
                    </div>
                </div>

                <!-- Catatan -->
                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keterangan</label>
                    <input type="text" name="notes" placeholder="Catatan tambahan faktur pembelian..."
                           class="w-full py-1.5 px-3 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs">
                </div>
            </div>
            <input type="hidden" name="reference_number" id="referenceNumberInput" value="{{ $autoRefNumber }}">
        </div>

        <!-- SEARCH BAR CEPAT & KETOKO ITEM LOOKUP -->
        <div class="bg-gradient-to-r from-emerald-700 to-teal-800 p-3.5 rounded-xl text-white shadow-md">
            <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-extrabold uppercase tracking-wider text-emerald-100 flex items-center gap-1.5">
                    <span>⚡</span> Scan Barcode / Input Barang Cepat (Tekan Enter untuk Cari Varian)
                </label>
                <button type="button" onclick="openItemModal()" class="px-2.5 py-1 bg-white/20 hover:bg-white/30 text-white rounded-lg text-xs font-bold flex items-center gap-1 transition-all cursor-pointer">
                    <span>🔍</span> Cari Item Lengkap <kbd class="bg-emerald-950/60 px-1.5 py-0.2 rounded font-mono text-[10px]">[F2]</kbd>
                </button>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-sm">🔍</span>
                    <input type="text"
                           id="quickSearchInput"
                           placeholder="Scan barcode dengan scanner, atau ketik nama barang (misal 'minyak') lalu tekan Enter..."
                           class="w-full pl-9 pr-4 py-2 bg-white text-slate-900 placeholder-slate-400 text-xs font-semibold rounded-lg shadow-inner focus:outline-hidden focus:ring-2 focus:ring-emerald-400">
                </div>
                <button type="button" onclick="handleQuickSearchEnter()" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 font-bold text-xs rounded-lg shadow-xs cursor-pointer transition-colors">
                    Cari / Masukkan
                </button>
            </div>
        </div>

        <!-- DAFTAR BARANG YANG DITERIMA -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                    <span>📦</span> Daftar Barang Faktur Pembelian
                </h3>
                <button type="button" onclick="openItemModal()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg transition-all cursor-pointer shadow-xs flex items-center gap-1">
                    <span>+</span> Tambah Barang [F2]
                </button>
            </div>

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase">
                        <tr>
                            <th class="py-2.5 px-3 min-w-[220px]">Nama Produk &amp; SKU</th>
                            <th class="py-2.5 px-3 w-32">Satuan Beli</th>
                            <th class="py-2.5 px-3 w-28 text-center">Qty Beli</th>
                            <th class="py-2.5 px-3 w-44 text-right">Harga Beli Satuan (Rp)</th>
                            <th class="py-2.5 px-3 w-24 text-right">Disc (%)</th>
                            <th class="py-2.5 px-3 text-right w-36">Subtotal (Rp)</th>
                            <th class="py-2.5 px-3 text-center min-w-[190px]">Analisis Modal (HPP)</th>
                            <th class="py-2.5 px-3 text-center w-12">#</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="tableBody">
                        <!-- Dynamic Rows -->
                    </tbody>
                </table>
            </div>
            <div id="emptyTableNotice" class="text-center py-6 text-slate-400 text-xs border-b border-x border-slate-200 dark:border-slate-800 rounded-b-xl">
                Belum ada barang dalam faktur pembelian. Ketik kata kunci di atas atau tekan <strong class="text-emerald-600 dark:text-emerald-400">[F2] Cari Item</strong>.
            </div>
        </div>

        <!-- FOOTER & RINGKASAN TOTAL -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <div class="p-3 bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900 rounded-xl space-y-1.5 text-xs">
                    <div class="font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                        <span>ℹ️</span> Otomatisasi HPP &amp; Valuasi Stok
                    </div>
                    <p class="text-emerald-700 dark:text-emerald-400 text-[11px] leading-relaxed">
                        Sistem SIGMA secara otomatis menghitung <strong>Moving Average (Weighted Average Cost)</strong> saat barang disimpan. Jika ada kenaikan harga beli dibanding modal lama, sistem akan menawarkan penyesuaian harga jual kasir (Opsi B).
                    </p>
                </div>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 rounded-xl p-4 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-500">
                    <span>Total Baris:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300" id="totalRows">0 Item</span>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Subtotal Faktur:</span>
                    <strong class="font-mono text-slate-800 dark:text-white" id="subtotalDisplay">Rp 0</strong>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <span>Potongan / Diskon Global:</span>
                    <input type="number" step="any" name="discount_amount" id="discountAmountInput" value="0" min="0"
                           oninput="calculateTotal()"
                           class="w-32 py-1 px-2 text-xs text-right bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md font-mono">
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <div class="flex items-center gap-1.5">
                        <span>Ongkos Kirim (Landed Cost):</span>
                        <span class="text-[10px] text-slate-400" title="Dialokasikan otomatis ke HPP Modal Barang">📦 Ekspedisi</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-slate-400 font-mono">Rp</span>
                        <input type="number" step="any" name="shipping_cost" id="shippingCostInput" value="0" min="0"
                               oninput="calculateTotal()"
                               class="w-32 py-1 px-2 text-xs text-right bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md font-mono font-bold text-slate-900 dark:text-white">
                    </div>
                </div>
                <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                    <div class="flex items-center gap-1.5">
                        <span>PPN Masukan:</span>
                        <button type="button" id="btnAutoPpn" onclick="autoCalculatePpn()" style="display: none;"
                                class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 font-bold transition-colors cursor-pointer"
                                title="Hitung PPN 11% Otomatis dari DPP">
                            ⚡ 11%
                        </button>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-slate-400 font-mono">Rp</span>
                        <input type="number" step="any" name="tax_amount" id="taxAmountInput" value="0" min="0"
                               oninput="calculateTotal()"
                               class="w-32 py-1 px-2 text-xs text-right bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-md font-mono font-bold">
                    </div>
                </div>
                <div id="nonPkpNotice" class="text-[10px] text-slate-400 text-right -mt-1 italic" style="display: none;">
                    Supplier Non-PKP tidak mengenakan PPN
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-slate-900 dark:text-white">GRAND TOTAL FAKTUR:</span>
                    <strong class="font-extrabold text-emerald-600 dark:text-emerald-400 text-base font-mono" id="grandTotalDisplay">Rp 0</strong>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTON -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
            <button type="button" onclick="handleSubmit()" class="w-full sm:w-auto px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer">
                <span>💾</span>
                <span>Simpan Penerimaan Barang / Faktur [F9]</span>
            </button>
        </div>

        <!-- CONTAINER FOR HIDDEN PRICE UPDATES -->
        <div id="hiddenPriceUpdatesContainer"></div>
        <input type="hidden" name="print_shelf_tags" id="printShelfTagsInput" value="1">
    </form>
</div>

<!-- ============================================================== -->
<!-- MODAL CARI ITEM CEPAT & LENGKAP (KETOKO-STYLE ITEM LOOKUP) -->
<!-- ============================================================== -->
<div id="itemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-150" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
        
        <!-- MODAL HEADER -->
        <div class="px-5 py-3.5 bg-gradient-to-r from-emerald-700 to-teal-800 text-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <div>
                    <h3 class="text-sm font-black">Cari Item Produk &amp; Informasi Stok Fisik (Pembelian)</h3>
                    <p class="text-[11px] text-emerald-200">Ketik nama barang (contoh: 'minyak'), scan barcode, atau kode SKU</p>
                </div>
            </div>
            <button type="button" onclick="closeItemModal()" class="text-emerald-200 hover:text-white font-bold text-xl cursor-pointer">&times;</button>
        </div>

        <!-- MODAL FILTER & SEARCH BAR -->
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">🔍</span>
                <input type="text"
                       id="modalSearchInput"
                       placeholder="Ketik kata kunci pencarian (misal: 'minyak', 'beras', atau scan barcode)..."
                       class="w-full pl-10 pr-24 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-emerald-500 shadow-inner">
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
                        <th class="p-2.5 text-right">Modal Terakhir (HPP)</th>
                        <th class="p-2.5 text-right">Harga Jual Kasir</th>
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

<!-- ============================================================== -->
<!-- MODAL OPSI B: PERSETUJUAN KENAIKAN HARGA JUAL (APPROVAL) -->
<!-- ============================================================== -->
<div id="approvalModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs transition-opacity duration-150" style="display: none;">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-150">
        
        <div class="bg-gradient-to-r from-amber-500 to-rose-600 p-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-3xl">⚠️</span>
                <div>
                    <h2 class="text-base font-bold tracking-tight">Konfirmasi Kenaikan Harga Jual (Opsi B)</h2>
                    <p class="text-xs text-amber-100">
                        Ditemukan barang dengan kenaikan harga modal pada faktur ini. Tinjau penyesuaian harga kasir POS.
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeApprovalModal()" class="text-white/80 hover:text-white text-xl font-bold p-1 cursor-pointer">&times;</button>
        </div>

        <div class="p-4 overflow-y-auto space-y-4 flex-1 text-xs" id="approvalModalContent">
            <!-- Dynamic Increased Items -->
        </div>

        <div class="p-3 bg-slate-100 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-end gap-2 text-xs">
            <button type="button" onclick="closeApprovalModal()" class="w-full sm:w-auto px-4 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-lg">
                ✕ Batal / Kembali Edit
            </button>
            <button type="button" onclick="submitWithoutPriceUpdates()" class="w-full sm:w-auto px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg shadow-xs">
                ⏳ Pertahankan Harga Lama (Hanya Tambah Stok)
            </button>
            <button type="button" onclick="submitWithPriceUpdates()" class="w-full sm:w-auto px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-lg shadow-md">
                ✅ Setujui &amp; Update Harga Jual Kasir
            </button>
        </div>
    </div>
</div>

<script>
    let purchaseItems = [];
    let modalResults = [];
    let modalHighlightIndex = 0;
    let modalDebounceTimer = null;
    const customerTiersData = @json($customerTiers);

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
            } else if (e.key === 'F4') {
                e.preventDefault();
                document.getElementById('supplierSelect')?.focus();
            } else if (e.key === 'Escape') {
                closeItemModal();
                closeApprovalModal();
            } else if (e.key === 'F9') {
                e.preventDefault();
                handleSubmit();
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

    function copyRefToInvoice() {
        const ref = document.getElementById('referenceNumberInput').value;
        document.getElementById('invoiceNumberInput').value = ref;
    }

    function onSupplierChange() {
        const select = document.getElementById('supplierSelect');
        const opt = select.selectedOptions[0];
        const term = parseInt(opt?.dataset?.term || '0', 10);
        const isPkp = opt?.dataset?.isPkp === '1';

        const paymentTypeSelect = document.getElementById('paymentType');
        const dueDateInput = document.getElementById('dueDate');
        const btnAutoPpn = document.getElementById('btnAutoPpn');
        const nonPkpNotice = document.getElementById('nonPkpNotice');
        const taxAmountInput = document.getElementById('taxAmountInput');

        if (term > 0) {
            const d = new Date();
            d.setDate(d.getDate() + term);
            dueDateInput.value = d.toISOString().split('T')[0];
            paymentTypeSelect.value = 'TEMPO';
        }

        togglePaymentFields();

        if (isPkp) {
            btnAutoPpn.style.display = 'inline-block';
            nonPkpNotice.style.display = 'none';
            taxAmountInput.disabled = false;
            autoCalculatePpn();
        } else {
            btnAutoPpn.style.display = 'none';
            nonPkpNotice.style.display = 'block';
            taxAmountInput.value = 0;
            taxAmountInput.disabled = true;
            calculateTotal();
        }
    }

    
    function setDueDatePreset(days) {
        const baseDate = new Date();
        let targetDate;
        if (days === 'eom') {
            targetDate = new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, 0);
        } else {
            targetDate = new Date();
            targetDate.setDate(targetDate.getDate() + parseInt(days));
        }
        const yyyy = targetDate.getFullYear();
        const mm = String(targetDate.getMonth() + 1).padStart(2, '0');
        const dd = String(targetDate.getDate()).padStart(2, '0');
        const input = document.getElementById('dueDate');
        if (input) input.value = `${yyyy}-${mm}-${dd}`;
    }

    function togglePaymentFields() {
        const method = document.getElementById('paymentType').value;
        const bankDiv = document.getElementById('bankAccountDiv');
        const dueDiv = document.getElementById('dueDateDiv');

        bankDiv.style.display = method === 'TRANSFER' ? 'block' : 'none';
        dueDiv.style.display = method === 'TEMPO' ? 'block' : 'none';
    }

    function autoCalculatePpn() {
        const select = document.getElementById('supplierSelect');
        const isPkp = select.selectedOptions[0]?.dataset?.isPkp === '1';
        if (!isPkp) {
            document.getElementById('taxAmountInput').value = 0;
            calculateTotal();
            return;
        }

        const subtotal = calculateSubtotalNumber();
        const disc = parseFloat(document.getElementById('discountAmountInput')?.value || 0);
        const dpp = Math.max(0, subtotal - disc);
        const ppn = Math.round(dpp * 0.11);
        document.getElementById('taxAmountInput').value = ppn;
        calculateTotal();
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
                addItemToPurchase(data[0]);
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
            const bgClass = isHigh ? 'bg-emerald-100/70 dark:bg-emerald-900/40 ring-1 ring-emerald-500 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/40';

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
                    <td class="p-2.5 text-right font-black font-mono text-slate-700 dark:text-slate-300">
                        Rp ${parseFloat(p.cost_price || 0).toLocaleString('id-ID', { maximumFractionDigits: 4 })}
                    </td>
                    <td class="p-2.5 text-right font-black font-mono text-emerald-600 dark:text-emerald-400">
                        Rp ${parseFloat(p.retail_selling_price || 0).toLocaleString('id-ID', { maximumFractionDigits: 4 })}
                    </td>
                    <td class="p-2.5 text-center text-slate-500">${p.rack_location || '-'}</td>
                    <td class="p-2.5 text-center">
                        <button type="button" onclick="event.stopPropagation(); selectModalItemByIndex(${idx})" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[11px] shadow-2xs">
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
            addItemToPurchase(prod);
            closeItemModal();
        }
    }

    function addItemToPurchase(prod) {
        const existing = purchaseItems.find(i => i.product_id === prod.id);
        if (existing) {
            existing.quantity = Math.round((existing.quantity + 1) * 100) / 100;
            renderPurchaseTable();
            calculateTotal();
            return;
        }

        const baseUnit = prod.base_unit || (prod.units && prod.units[0]) || { id: '', unit_code: 'PCS', conversion_factor: 1 };
        // Default gunakan satuan kemasan pertama atau terbesar
        const defaultUnit = prod.units && prod.units.length > 1 ? prod.units[prod.units.length - 1] : baseUnit;
        const conv = defaultUnit.conversion_factor || 1;

        const initialCostPerUnit = prod.last_purchase_price > 0 ? (prod.last_purchase_price * conv) : (prod.cost_price * conv);

        purchaseItems.push({
            product_id: prod.id,
            product_name: prod.name,
            sku: prod.sku || '',
            barcode: prod.barcode || '',
            total_quantity: prod.total_quantity || 0,
            old_cost_price: prod.cost_price || 0,
            base_unit_id: baseUnit.id,
            base_unit_code: baseUnit.unit_code,
            product_unit_id: defaultUnit.id,
            units: prod.units || [baseUnit],
            quantity: 1,
            unit_price: initialCostPerUnit,
            discount_percent: 0,
            product: prod
        });

        renderPurchaseTable();
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

    function getHppAnalysisBadgeHtml(item) {
        const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { conversion_factor: 1, unit_code: item.base_unit_code || 'PCS' };
        const conv = parseFloat(currentUnit.conversion_factor) || 1;
        const unitCode = currentUnit.unit_code || 'PCS';
        
        // HPP lama diskalakan ke satuan kemasan yang sedang aktif (misal DUS = HPP PCS * conv)
        const oldCostForCurrentUnit = (parseFloat(item.old_cost_price) || 0) * conv;
        const currentPrice = parseFloat(item.unit_price) || 0;
        const diff = currentPrice - oldCostForCurrentUnit;

        if (oldCostForCurrentUnit <= 0) {
            return `
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    <span>Barang Baru / HPP 0</span>
                </div>
                <div class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                    Modal Lama: <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">Rp 0</span> / ${unitCode}
                </div>
            `;
        } else if (diff > 0.01) {
            const pct = ((diff / oldCostForCurrentUnit) * 100).toFixed(1);
            return `
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200 border border-rose-300 dark:border-rose-800 shadow-sm animate-pulse">
                    <span>⚠️ NAIK +${pct}%</span>
                </div>
                <div class="mt-1 text-xs font-bold text-rose-900 dark:text-rose-300">
                    Modal Lama: <span class="font-black text-rose-950 dark:text-white font-mono underline decoration-rose-400">${formatRupiah(oldCostForCurrentUnit, 0, 2)}</span> <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">/ ${unitCode}</span>
                </div>
            `;
        } else if (diff < -0.01) {
            const pct = (Math.abs(diff / oldCostForCurrentUnit) * 100).toFixed(1);
            return `
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200 border border-blue-300 dark:border-blue-800 shadow-sm">
                    <span>📉 TURUN -${pct}%</span>
                </div>
                <div class="mt-1 text-xs font-bold text-blue-900 dark:text-blue-300">
                    Modal Lama: <span class="font-black text-blue-950 dark:text-white font-mono">${formatRupiah(oldCostForCurrentUnit, 0, 2)}</span> <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">/ ${unitCode}</span>
                </div>
            `;
        } else {
            return `
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-800 shadow-sm">
                    <span>✓ HARGA STABIL</span>
                </div>
                <div class="mt-1 text-xs font-bold text-slate-700 dark:text-slate-300">
                    Modal Lama: <span class="font-black text-slate-900 dark:text-white font-mono">${formatRupiah(oldCostForCurrentUnit, 0, 2)}</span> <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">/ ${unitCode}</span>
                </div>
            `;
        }
    }

    function renderPurchaseTable() {
        const tbody = document.getElementById('tableBody');
        const emptyNotice = document.getElementById('emptyTableNotice');
        if (!tbody) return;

        if (purchaseItems.length === 0) {
            tbody.innerHTML = '';
            if (emptyNotice) emptyNotice.style.display = 'block';
            return;
        }

        if (emptyNotice) emptyNotice.style.display = 'none';

        let html = '';
        purchaseItems.forEach((item, index) => {
            const subtotal = Math.max(0, (item.quantity * item.unit_price) * (1 - (item.discount_percent / 100)));
            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { conversion_factor: 1, unit_code: item.base_unit_code || 'PCS' };

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
                        <select name="items[${index}][product_unit_id]" onchange="onItemUnitChange(${index}, this.value)" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 font-medium">
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
                        <input type="number" step="any" min="0" max="100" name="items[${index}][discount_percent]" value="${item.discount_percent}"
                               oninput="onItemDiscChange(${index}, this.value)"
                               class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1.5 px-2 text-right font-medium">
                    </td>
                    <td class="py-2.5 px-3 text-right font-black text-slate-800 dark:text-slate-200 font-mono" id="subtotal-${index}">
                        ${formatRupiah(subtotal, 0, 2)}
                    </td>
                    <td class="py-2.5 px-3 text-center" id="hpp-badge-${index}">
                        ${getHppAnalysisBadgeHtml(item)}
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
        const item = purchaseItems[index];
        if (!item) return;

        const subtotal = Math.max(0, (item.quantity * item.unit_price) * (1 - (item.discount_percent / 100)));
        const subtotalEl = document.getElementById(`subtotal-${index}`);
        if (subtotalEl) {
            subtotalEl.innerText = formatRupiah(subtotal, 0, 2);
        }

        const badgeEl = document.getElementById(`hpp-badge-${index}`);
        if (badgeEl) {
            badgeEl.innerHTML = getHppAnalysisBadgeHtml(item);
        }
    }

    function onItemUnitChange(index, unitId) {
        const item = purchaseItems[index];
        if (!item) return;
        const oldUnit = item.units.find(u => u.id === item.product_unit_id);
        const newUnit = item.units.find(u => u.id === unitId);

        const oldConv = oldUnit ? (parseFloat(oldUnit.conversion_factor) || 1) : 1;
        const newConv = newUnit ? (parseFloat(newUnit.conversion_factor) || 1) : 1;

        // Skala harga satuan beli
        const basePrice = item.unit_price / oldConv;
        item.product_unit_id = unitId;
        item.unit_price = Math.round(basePrice * newConv);

        renderPurchaseTable();
        calculateTotal();
    }

    function onItemQtyChange(index, val) {
        const item = purchaseItems[index];
        if (item) {
            item.quantity = parseFormattedNumber(val);
            const hiddenEl = document.getElementById(`qty-hidden-${index}`);
            if (hiddenEl) hiddenEl.value = item.quantity;

            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { unit_code: item.base_unit_code || 'PCS' };
            const previewEl = document.getElementById(`qty-preview-${index}`);
            if (previewEl) previewEl.innerText = `${item.quantity} ${currentUnit.unit_code}`;

            updateRowDisplay(index);
            calculateTotal();
        }
    }

    function onItemQtyBlur(index, input) {
        const item = purchaseItems[index];
        if (item) {
            input.value = formatInputNumber(item.quantity);
        }
    }

    function onItemPriceChange(index, val) {
        const item = purchaseItems[index];
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
        const item = purchaseItems[index];
        if (item) {
            input.value = formatInputNumber(item.unit_price);
            const previewEl = document.getElementById(`price-preview-${index}`);
            if (previewEl) previewEl.innerText = formatRupiah(item.unit_price, 0, 2);
        }
    }

    function onItemDiscChange(index, val) {
        const item = purchaseItems[index];
        if (item) {
            item.discount_percent = parseFormattedNumber(val);
            updateRowDisplay(index);
            calculateTotal();
        }
    }

    function removeItem(index) {
        purchaseItems.splice(index, 1);
        renderPurchaseTable();
        calculateTotal();
    }

    function calculateSubtotalNumber() {
        let total = 0;
        purchaseItems.forEach(item => {
            const sub = (item.quantity * item.unit_price) * (1 - (item.discount_percent / 100));
            total += Math.max(0, sub);
        });
        return total;
    }

    function calculateTotal() {
        const subtotal = calculateSubtotalNumber();
        const discountAmount = parseFormattedNumber(document.getElementById('discountAmountInput')?.value || 0);
        const shippingCost = parseFormattedNumber(document.getElementById('shippingCostInput')?.value || 0);
        const taxAmount = parseFormattedNumber(document.getElementById('taxAmountInput')?.value || 0);
        const grandTotal = Math.max(0, subtotal - discountAmount + taxAmount + shippingCost);

        document.getElementById('totalRows').innerText = `${purchaseItems.length} Baris`;
        document.getElementById('subtotalDisplay').innerText = formatRupiah(subtotal, 0, 2);
        document.getElementById('grandTotalDisplay').innerText = formatRupiah(grandTotal, 0, 2);
    }

    function hasPriceIncrease() {
        return purchaseItems.some(item => {
            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { conversion_factor: 1 };
            const baseUnitPrice = item.unit_price / (currentUnit.conversion_factor || 1);
            return (baseUnitPrice > item.old_cost_price) && (item.old_cost_price > 0);
        });
    }

    function handleSubmit() {
        if (purchaseItems.length === 0) {
            alert('Silakan masukkan minimal 1 barang ke dalam faktur pembelian.');
            document.getElementById('quickSearchInput')?.focus();
            return;
        }

        const supplierSelect = document.getElementById('supplierSelect');
        if (!supplierSelect.value) {
            alert('Silakan pilih Supplier / Pemasok terlebih dahulu!');
            supplierSelect.focus();
            return;
        }

        const invoiceInput = document.getElementById('invoiceNumberInput');
        if (!invoiceInput.value || invoiceInput.value.trim() === '') {
            invoiceInput.value = document.getElementById('referenceNumberInput').value;
        }

        if (hasPriceIncrease()) {
            openApprovalModal();
        } else {
            document.getElementById('purchaseForm').submit();
        }
    }

    function openApprovalModal() {
        const modal = document.getElementById('approvalModal');
        const content = document.getElementById('approvalModalContent');
        if (!modal || !content) return;

        let html = '';
        purchaseItems.forEach(item => {
            const currentUnit = item.units.find(u => u.id === item.product_unit_id) || { conversion_factor: 1 };
            const conv = currentUnit.conversion_factor || 1;
            const baseUnitPrice = item.unit_price / conv;
            const isInc = (baseUnitPrice > item.old_cost_price) && (item.old_cost_price > 0);

            if (!isInc) return;

            const oldStock = Math.max(0, parseFloat(item.total_quantity) || 0);
            const inQty = item.quantity * conv;
            const estHpp = Math.round(((oldStock * item.old_cost_price) + (inQty * baseUnitPrice)) / (oldStock + inQty));

            let tierInputs = '';
            customerTiersData.forEach(t => {
                const targetMargin = t.code === 'RETAIL' ? 15 : (t.code === 'GROSIR' ? 10 : 8);
                const recPrice = Math.round(estHpp * (1 + (targetMargin / 100)));

                tierInputs += `
                    <div class="bg-white dark:bg-slate-800 p-2 rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            <span>${t.name}</span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none text-slate-400 text-[11px]">Rp</span>
                            <input type="number"
                                   id="tier_input_${item.product_id}_${t.id}"
                                   value="${recPrice}"
                                   class="w-full pl-7 pr-2 py-1 text-xs font-bold text-emerald-800 dark:text-emerald-300 font-mono bg-emerald-50/50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700 rounded">
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl p-3.5 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="font-extrabold text-slate-800 dark:text-white text-sm">${item.product_name}</div>
                            <div class="text-[11px] text-slate-500 font-mono">SKU: ${item.sku || '-'}</div>
                        </div>
                        <label class="inline-flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 shadow-2xs">
                            <input type="checkbox" id="check_update_${item.product_id}" checked class="rounded text-emerald-600 w-4 h-4">
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">Update Harga Jual Kasir</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 bg-white dark:bg-slate-800 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                        <div>
                            <div class="text-slate-400 text-[10px] uppercase font-bold">Modal Lama (HPP)</div>
                            <div class="font-mono font-bold text-slate-700 dark:text-slate-300 text-xs">Rp ${parseFloat(item.old_cost_price || 0).toLocaleString('id-ID', { maximumFractionDigits: 4 })} / ${item.base_unit_code}</div>
                        </div>
                        <div>
                            <div class="text-rose-600 text-[10px] uppercase font-bold">Modal Faktur Baru</div>
                            <div class="font-mono font-extrabold text-rose-700 dark:text-rose-400 text-xs">Rp ${parseFloat(baseUnitPrice || 0).toLocaleString('id-ID', { maximumFractionDigits: 4 })} / ${item.base_unit_code}</div>
                        </div>
                        <div>
                            <div class="text-emerald-700 dark:text-emerald-400 text-[10px] uppercase font-bold">Estimasi HPP Moving Avg</div>
                            <div class="font-mono font-bold text-emerald-800 dark:text-emerald-300 text-xs">Rp ${parseFloat(estHpp || 0).toLocaleString('id-ID', { maximumFractionDigits: 4 })} / ${item.base_unit_code}</div>
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-1">
                        <div class="text-[11px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                            Rekomendasi Harga Jual Baru per Level Pelanggan:
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            ${tierInputs}
                        </div>
                    </div>
                </div>
            `;
        });

        content.innerHTML = html;
        modal.style.display = 'flex';
    }

    function closeApprovalModal() {
        const modal = document.getElementById('approvalModal');
        if (modal) modal.style.display = 'none';
    }

    function submitWithoutPriceUpdates() {
        const container = document.getElementById('hiddenPriceUpdatesContainer');
        container.innerHTML = '';
        document.getElementById('purchaseForm').submit();
    }

    function submitWithPriceUpdates() {
        const container = document.getElementById('hiddenPriceUpdatesContainer');
        container.innerHTML = '';

        purchaseItems.forEach(item => {
            const check = document.getElementById(`check_update_${item.product_id}`);
            const shouldApprove = check && check.checked;

            const inputApprove = document.createElement('input');
            inputApprove.type = 'hidden';
            inputApprove.name = `price_updates[${item.product_id}][approve_update]`;
            inputApprove.value = shouldApprove ? '1' : '0';
            container.appendChild(inputApprove);

            if (shouldApprove) {
                customerTiersData.forEach(t => {
                    const tierInput = document.getElementById(`tier_input_${item.product_id}_${t.id}`);
                    if (tierInput) {
                        const inputTier = document.createElement('input');
                        inputTier.type = 'hidden';
                        inputTier.name = `price_updates[${item.product_id}][tiers][${t.id}]`;
                        inputTier.value = tierInput.value;
                        container.appendChild(inputTier);
                    }
                });
            }
        });

        document.getElementById('purchaseForm').submit();
    }
</script>
@endsection
