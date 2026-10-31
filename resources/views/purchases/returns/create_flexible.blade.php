@extends('layouts.app')

@section('title', 'Buat Retur Pembelian Fleksibel ke Supplier')

@section('content')
<div class="space-y-4" x-data="flexibleReturnApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">📦</span>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Retur Fleksibel ke Supplier (Jalur 2)</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                    Non-Faktur / Manual Return
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Pengembalian barang langsung ke supplier tanpa referensi faktur pembelian asal. Stok divalidasi dan dokumen diverifikasi bertahap.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.returns.index') }}"
               class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-lg transition-colors border border-slate-200 dark:border-slate-700">
                ← Kembali ke Daftar Retur
            </a>
        </div>
    </div>

    <!-- MAIN FORM -->
    <form action="{{ route('purchases.returns.store-flexible') }}" method="POST" @submit="handleSubmit">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- KOLOM KIRI: INFORMASI HEADER & SUPPLIER -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs space-y-3.5 text-xs">
                    <div class="font-bold text-slate-800 dark:text-slate-200 pb-2 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <span>📋 Informasi Dokumen Retur</span>
                        <span class="text-blue-700 dark:text-blue-400 font-mono text-[11px]">{{ $autoReturnNumber }}</span>
                    </div>

                    <!-- Toko / Gudang -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Gudang / Toko Asal <span class="text-rose-500">*</span>:</label>
                        <select name="store_id" x-model="storeId" required class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                            @foreach($stores as $st)
                                <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Supplier Tujuan -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Supplier Tujuan <span class="text-rose-500">*</span>:</label>
                        <select name="supplier_id" x-model="supplierId" required class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Supplier Tujuan --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} ({{ $sup->code }})</option>
                            @endforeach
                        </select>
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Semua item pada dokumen ini harus ditujukan ke supplier yang sama.</span>
                    </div>

                    <!-- Tanggal Retur -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Retur <span class="text-rose-500">*</span>:</label>
                        <input type="date" name="return_date" x-model="returnDate" required class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Metode Penyelesaian -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Penyelesaian <span class="text-rose-500">*</span>:</label>
                        <select name="refund_type" x-model="refundType" @change="onRefundTypeChange" required class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                            <option value="DEBT_REDUCTION">📝 Potong Hutang Supplier (DEBT REDUCTION)</option>
                            <option value="CASH">💵 Kas Tunai Fisik (CASH REFUND)</option>
                        </select>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1" x-show="refundType === 'DEBT_REDUCTION'">
                            * Pemotongan hutang wajib dialokasikan ke faktur tempo aktif supplier terkait.
                        </p>
                    </div>

                    <!-- Konfirmasi Kas Fisik jika CASH -->
                    <div x-show="refundType === 'CASH'" class="p-2.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-300 dark:border-emerald-800 rounded-lg space-y-1">
                        <label class="flex items-start gap-2 cursor-pointer">
                            <input type="checkbox" name="is_cash_received" value="1" x-model="isCashReceived" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500">
                            <span class="text-[11px] font-bold text-emerald-900 dark:text-emerald-200">Konfirmasi Kas Fisik Diterima</span>
                        </label>
                        <p class="text-[10px] text-emerald-700 dark:text-emerald-300 pl-5">
                            Centang ini jika uang tunai pengembalian telah benar-benar diterima di kas toko saat serah terima barang.
                        </p>
                    </div>

                    <!-- Opsi Pajak PPN Dinamis -->
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-lg">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_taxable" value="1" x-model="isTaxable" class="rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-[11px] font-bold text-slate-800 dark:text-slate-200">Retur Kena Pajak (PPN Masukan)</span>
                        </label>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400 pl-5 mt-0.5">
                            Gunakan jika supplier adalah PKP dan retur membalik faktur pajak masukan aktif.
                        </p>
                    </div>

                    <!-- Alasan Retur -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan Pengembalian (Wajib) <span class="text-rose-500">*</span>:</label>
                        <textarea name="reason" x-model="reason" rows="2" required placeholder="Contoh: Barang cacat pabrik / mendekati kedaluwarsa / kesepakatan konsinyasi" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional):</label>
                        <textarea name="notes" x-model="notes" rows="2" placeholder="Catatan internal pengiriman atau kesepakatan vendor..." class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <!-- Otorisasi Langsung Khusus Owner -->
                    @if(auth()->user()?->role === 'owner')
                        <div class="p-2.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800 rounded-lg space-y-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="approve_now" value="1" x-model="approveNow" class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                <span class="font-bold text-amber-900 dark:text-amber-200 text-[11px]">Setujui Langsung (Owner Authority)</span>
                            </label>
                            <p class="text-[10px] text-amber-800 dark:text-amber-300 pl-5">
                                Jika dicentang, stok fisik akan langsung dipotong dan jurnal akuntansi langsung dicatat tanpa menunggu status DRAFT.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- KOLOM KANAN: PENCARIAN & DAFTAR ITEM BARANG -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs space-y-3.5 text-xs">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                        <span class="font-bold text-slate-800 dark:text-slate-200">🔍 Cari &amp; Tambahkan Barang yang Diretur</span>
                        <span class="text-[11px] text-slate-400" x-text="items.length + ' item dipilih'"></span>
                    </div>

                    <!-- Autocomplete Search Bar -->
                    <div class="relative">
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                                <input type="text"
                                       x-model="searchQuery"
                                       @input.debounce.300ms="searchProducts()"
                                       @keydown.escape="searchResults = []"
                                       placeholder="Ketik nama produk, SKU, atau scan barcode..."
                                       class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Dropdown Hasil Pencarian -->
                        <div x-show="searchResults.length > 0"
                             x-cloak
                             @click.away="searchResults = []"
                             class="absolute left-0 right-0 top-full mt-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg shadow-xl z-30 max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                            <template x-for="p in searchResults" :key="p.id">
                                <div @click="addItem(p)" class="p-2.5 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer flex items-center justify-between transition-colors">
                                    <div>
                                        <div class="font-bold text-slate-800 dark:text-slate-100" x-text="p.name"></div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="'SKU: ' + p.sku + ' | Barcode: ' + (p.barcode || '-')"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-emerald-700 dark:text-emerald-400 font-mono" x-text="'HPP: Rp ' + formatRupiah(p.cost_price || 0)"></div>
                                        <div class="text-[10px] text-slate-500" x-text="'Stok Total: ' + p.total_quantity + ' ' + (p.base_unit?.unit_code || 'PCS')"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Tabel Item Retur -->
                    <div class="border border-slate-200 dark:border-slate-800 rounded-lg overflow-x-auto">
                        <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="px-3 py-2">Nama Barang &amp; SKU</th>
                                    <th class="px-2 py-2 text-center w-28">Satuan</th>
                                    <th class="px-2 py-2 text-center w-24">Kondisi</th>
                                    <th class="px-2 py-2 text-center w-20">Qty Retur</th>
                                    <th class="px-3 py-2 text-right w-28">Nilai Satuan</th>
                                    <th class="px-3 py-2 text-right w-28">Subtotal</th>
                                    <th class="px-2 py-2 text-center w-8">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-sans">
                                <template x-for="(item, idx) in items" :key="item.product_id + '-' + item.product_unit_id">
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                        <td class="px-3 py-2">
                                            <div class="font-bold text-slate-800 dark:text-slate-200" x-text="item.name"></div>
                                            <div class="text-[10px] text-slate-400 font-mono" x-text="item.sku"></div>
                                            <input type="hidden" :name="'items[' + idx + '][product_id]'" :value="item.product_id">
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <select :name="'items[' + idx + '][product_unit_id]'"
                                                    x-model="item.product_unit_id"
                                                    @change="updateUnit(item)"
                                                    class="w-full py-1 px-1.5 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded font-semibold text-slate-700 dark:text-slate-200">
                                                <template x-for="u in item.units" :key="u.id">
                                                    <option :value="u.id" x-text="u.unit_code + ' (x' + u.conversion_factor + ')'"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <select :name="'items[' + idx + '][condition]'"
                                                    x-model="item.condition"
                                                    class="w-full py-1 px-1.5 text-[10.5px] font-bold border rounded bg-white dark:bg-slate-800"
                                                    :class="item.condition === 'DAMAGED' ? 'text-amber-700 border-amber-300' : 'text-emerald-700 border-emerald-300'">
                                                <option value="GOOD">✅ Layak Jual</option>
                                                <option value="DAMAGED">⚠️ Rusak</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <input type="number"
                                                   step="1"
                                                   min="1"
                                                   :name="'items[' + idx + '][quantity]'"
                                                   x-model.number="item.quantity"
                                                   class="w-16 text-center font-mono font-bold py-1 border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <input type="number"
                                                   step="100"
                                                   min="0"
                                                   :name="'items[' + idx + '][unit_price]'"
                                                   x-model.number="item.unit_price"
                                                   title="Nilai retur per satuan kemasan. Otomatis terisi HPP berjalan."
                                                   class="w-24 text-right font-mono font-bold py-1 px-1.5 border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 dark:text-slate-100"
                                            x-text="'Rp ' + formatRupiah(item.quantity * item.unit_price)">
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 font-bold text-sm cursor-pointer" title="Hapus Item">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="7" class="py-8 text-center text-slate-400 font-sans">
                                        Belum ada barang yang ditambahkan. Gunakan kolom pencarian di atas untuk menambah barang.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- RINGKASAN TOTAL -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg space-y-1 font-mono text-xs border border-slate-200 dark:border-slate-800">
                        <div class="flex justify-between items-center text-sm font-extrabold text-slate-900 dark:text-slate-100">
                            <span class="font-sans">Total Nilai Retur Pembelian:</span>
                            <span class="text-rose-700 dark:text-rose-400 font-mono text-base" x-text="'Rp ' + formatRupiah(calculateTotal())"></span>
                        </div>
                    </div>

                    <!-- PANEL ALOKASI FAKTUR HUTANG SUPPLIER (DEBT REDUCTION) -->
                    <div x-show="refundType === 'DEBT_REDUCTION'" class="p-3.5 bg-blue-50/70 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900/60 rounded-xl space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-2 border-b border-blue-100 dark:border-blue-900/40">
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-slate-100 text-xs flex items-center gap-1.5">
                                    <span>📑</span>
                                    <span>Alokasi Faktur Hutang Supplier (Wajib Sama dengan Total Retur)</span>
                                </h3>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Pilih faktur tempo belum lunas milik supplier ini yang akan dipotong nilai hutangnya.</p>
                            </div>
                            <button type="button" @click="autoAllocate" class="px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[11px] font-bold cursor-pointer transition-colors shadow-2xs self-start sm:self-auto">
                                ⚡ Alokasi Otomatis (FIFO)
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-blue-200 dark:border-blue-800 text-[11px] font-extrabold text-slate-600 dark:text-slate-300">
                                        <th class="py-1.5 px-2">No. Faktur</th>
                                        <th class="py-1.5 px-2">Tgl Faktur</th>
                                        <th class="py-1.5 px-2">Jatuh Tempo</th>
                                        <th class="py-1.5 px-2 text-right">Sisa Hutang</th>
                                        <th class="py-1.5 px-2 text-right w-36">Alokasi Retur (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(inv, idx) in unpaidInvoices" :key="inv.id">
                                        <tr class="border-b border-blue-100 dark:border-blue-950 hover:bg-blue-100/30">
                                            <td class="py-1.5 px-2 font-mono font-bold text-blue-900 dark:text-blue-300" x-text="inv.invoice_number"></td>
                                            <td class="py-1.5 px-2 text-slate-600 dark:text-slate-400" x-text="inv.purchase_date"></td>
                                            <td class="py-1.5 px-2 text-slate-600 dark:text-slate-400" x-text="inv.due_date"></td>
                                            <td class="py-1.5 px-2 text-right font-mono text-slate-700 dark:text-slate-300" x-text="'Rp ' + formatRupiah(inv.remaining_debt)"></td>
                                            <td class="py-1.5 px-2 text-right">
                                                <input type="hidden" :name="'allocations[' + idx + '][purchase_id]'" :value="inv.id">
                                                <input type="number" step="0.01" min="0" :max="inv.remaining_debt"
                                                       :name="'allocations[' + idx + '][amount]'"
                                                       x-model.number="inv.allocated_amount"
                                                       placeholder="0"
                                                       class="w-32 text-right font-mono font-bold py-1 px-1.5 border border-slate-300 dark:border-slate-700 rounded bg-white dark:bg-slate-800 text-xs focus:ring-2 focus:ring-blue-500">
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="unpaidInvoices.length === 0">
                                        <td colspan="5" class="py-4 text-center text-slate-400 italic">
                                            <span x-show="!supplierId">Pilih supplier terlebih dahulu untuk memuat daftar faktur tempo.</span>
                                            <span x-show="supplierId">Tidak ada faktur tempo belum lunas untuk supplier ini di toko terpilih.</span>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="font-bold border-t border-blue-200 dark:border-blue-800">
                                        <td colspan="4" class="py-2 px-2 text-right">Total Teralokasi:</td>
                                        <td class="py-2 px-2 text-right font-mono font-extrabold text-blue-700 dark:text-blue-400"
                                            x-text="'Rp ' + formatRupiah(calculateTotalAllocated())"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('purchases.returns.index') }}" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold hover:bg-slate-200">
                            Batal
                        </a>
                        <button type="submit"
                                :disabled="isSubmitting || items.length === 0 || calculateTotal() <= 0"
                                class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-lg shadow-sm transition-all flex items-center gap-2 disabled:opacity-50 cursor-pointer">
                            <span x-show="!isSubmitting">💾 Simpan Dokumen Retur</span>
                            <span x-show="isSubmitting">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
function flexibleReturnApp() {
    return {
        storeId: '{{ $stores->first()?->id ?? "" }}',
        supplierId: '',
        returnDate: '{{ now()->toDateString() }}',
        refundType: 'DEBT_REDUCTION',
        reason: '',
        notes: '',
        approveNow: false,
        searchQuery: '',
        searchResults: [],
        items: [],
        isSubmitting: false,
        isTaxable: false,
        isCashReceived: false,
        unpaidInvoices: [],

        init() {
            this.$watch('supplierId', () => this.fetchUnpaidInvoices());
            this.$watch('storeId', () => this.fetchUnpaidInvoices());
        },

        onRefundTypeChange() {
            if (this.refundType === 'DEBT_REDUCTION') {
                this.fetchUnpaidInvoices();
            }
        },

        async fetchUnpaidInvoices() {
            if (!this.supplierId) {
                this.unpaidInvoices = [];
                return;
            }
            try {
                const res = await fetch('/purchases/suppliers/' + this.supplierId + '/unpaid-invoices?store_id=' + this.storeId);
                const data = await res.json();
                if (data.success) {
                    this.unpaidInvoices = data.invoices.map(inv => ({
                        ...inv,
                        allocated_amount: null
                    }));
                }
            } catch (e) {
                this.unpaidInvoices = [];
            }
        },

        calculateTotalAllocated() {
            return this.unpaidInvoices.reduce((acc, inv) => {
                return acc + (parseFloat(inv.allocated_amount) || 0);
            }, 0);
        },

        autoAllocate() {
            let remaining = this.calculateTotal();
            if (remaining <= 0) {
                alert('Tambahkan barang terlebih dahulu sebelum mengalokasikan hutang.');
                return;
            }

            for (let inv of this.unpaidInvoices) {
                if (remaining <= 0) {
                    inv.allocated_amount = null;
                    continue;
                }
                const maxAlloc = parseFloat(inv.remaining_debt) || 0;
                const toAlloc = Math.min(remaining, maxAlloc);
                inv.allocated_amount = Math.round(toAlloc * 100) / 100;
                remaining -= toAlloc;
            }

            if (remaining > 0.01) {
                alert('Perhatian: Total sisa hutang faktur supplier (' + this.formatRupiah(this.calculateTotal() - remaining) + ') lebih kecil dari nilai retur (' + this.formatRupiah(this.calculateTotal()) + ').');
            }
        },

        async searchProducts() {
            const q = this.searchQuery.trim();
            if (q.length < 2) {
                this.searchResults = [];
                return;
            }

            try {
                const res = await fetch('/purchases/search-product?q=' + encodeURIComponent(q));
                const data = await res.json();
                this.searchResults = data;
            } catch (e) {
                this.searchResults = [];
            }
        },

        addItem(product) {
            const baseUnit = product.units ? product.units.find(u => u.is_base_unit) || product.units[0] : null;
            const conv = baseUnit ? parseFloat(baseUnit.conversion_factor) || 1.0 : 1.0;
            const hpp = parseFloat(product.cost_price) || 0;
            const initialPrice = Math.round(hpp * conv);

            // Cek apakah item sudah ada di daftar
            const existing = this.items.find(i => i.product_id === product.id && i.product_unit_id === (baseUnit?.id || ''));
            if (existing) {
                existing.quantity += 1;
            } else {
                this.items.push({
                    product_id: product.id,
                    name: product.name,
                    sku: product.sku,
                    product_unit_id: baseUnit ? baseUnit.id : '',
                    units: product.units || [],
                    conversion: conv,
                    cost_price: hpp,
                    unit_price: initialPrice,
                    quantity: 1,
                    condition: 'GOOD'
                });
            }

            this.searchQuery = '';
            this.searchResults = [];
        },

        updateUnit(item) {
            const u = item.units.find(unit => unit.id === item.product_unit_id);
            if (u) {
                item.conversion = parseFloat(u.conversion_factor) || 1.0;
                item.unit_price = Math.round(item.cost_price * item.conversion);
            }
        },

        removeItem(idx) {
            this.items.splice(idx, 1);
        },

        calculateTotal() {
            return this.items.reduce((acc, it) => {
                return acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unit_price) || 0));
            }, 0);
        },

        formatRupiah(val) {
            let num = parseFloat(val) || 0;
            return Math.round(num).toLocaleString('id-ID');
        },

        handleSubmit(e) {
            if (!this.supplierId) {
                alert('Pilih supplier tujuan terlebih dahulu.');
                e.preventDefault();
                return;
            }

            if (this.items.length === 0) {
                alert('Tambahkan minimal satu barang ke dalam daftar retur.');
                e.preventDefault();
                return;
            }

            for (let it of this.items) {
                if (!it.quantity || it.quantity <= 0) {
                    alert('Kuantitas untuk [' + it.name + '] harus lebih besar dari 0.');
                    e.preventDefault();
                    return;
                }
            }

            if (this.refundType === 'DEBT_REDUCTION') {
                const totalAlloc = this.calculateTotalAllocated();
                const totalRet = this.calculateTotal();
                if (Math.abs(totalAlloc - totalRet) > 0.01) {
                    this.autoAllocate();
                    const newTotalAlloc = this.calculateTotalAllocated();
                    if (Math.abs(newTotalAlloc - totalRet) > 0.01) {
                        alert('Total alokasi faktur hutang (Rp ' + this.formatRupiah(newTotalAlloc) + ') harus sama persis dengan total retur (Rp ' + this.formatRupiah(totalRet) + ').');
                        e.preventDefault();
                        return;
                    }
                }
            }

            if (this.refundType === 'CASH' && !this.isCashReceived) {
                if (!confirm('Peringatan: Anda belum mencentang konfirmasi penerimaan fisik kas. Apakah fisik uang kas benar-benar sudah diterima?')) {
                    e.preventDefault();
                    return;
                }
            }

            if (!confirm('Apakah Anda yakin ingin memproses dokumen retur fleksibel ini senilai Rp ' + this.formatRupiah(this.calculateTotal()) + '?')) {
                e.preventDefault();
                return;
            }

            this.isSubmitting = true;
        }
    }
}
</script>
@endpush
@endsection
