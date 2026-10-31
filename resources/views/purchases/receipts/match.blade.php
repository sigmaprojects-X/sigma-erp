@extends('layouts.app')

@section('title', "Three-Way Matching - {$receipt->receipt_number}")
@section('tab_title', 'Three-Way Matching')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full" x-data="threeWayMatchForm()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <a href="{{ route('purchases.receipts.index') }}" class="hover:text-blue-600 font-semibold">LPB Gudang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Three-Way Matching</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⚡ Three-Way Matching (Verifikasi Faktur Supplier)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pencocokan antara Pesanan (PO), Penerimaan Fisik Gudang (LPB), dan Faktur Tagihan Asli Pemasok.
            </p>
        </div>

        <div>
            <a href="{{ route('purchases.receipts.show', $receipt->id) }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-colors">
                &larr; Kembali ke LPB
            </a>
        </div>
    </div>

    <!-- 3-PILLAR REFERENCE CARDS (PO vs LPB vs INVOICE) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Pilar 1: Purchase Order (PO) -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-black text-blue-600 uppercase tracking-wider">1. Dokumen Purchase Order</span>
                <span class="text-xs">📑</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Nomor PO:</span>
                <span class="font-bold text-slate-900 dark:text-white font-mono">{{ $receipt->purchaseOrder?->po_number ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Tanggal PO:</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $receipt->purchaseOrder?->order_date?->format('d/m/Y') ?? '-' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Pemasok:</span>
                <span class="font-bold text-slate-900 dark:text-white truncate max-w-[140px]">{{ $receipt->supplier->name }}</span>
            </div>
        </div>

        <!-- Pilar 2: Laporan Penerimaan Barang (LPB Gudang) -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-black text-emerald-600 uppercase tracking-wider">2. Fisik Barang (LPB)</span>
                <span class="text-xs">📦</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Nomor LPB:</span>
                <span class="font-bold text-slate-900 dark:text-white font-mono">{{ $receipt->receipt_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Surat Jalan (DO):</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ $receipt->delivery_order_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Tgl Masuk Fisik:</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $receipt->receipt_date?->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Pilar 3: Akrual GRNI Aktif -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="flex items-center justify-between pb-1 border-b border-slate-100 dark:border-slate-800">
                <span class="text-[11px] font-black text-amber-600 uppercase tracking-wider">3. Status Akuntansi GRNI</span>
                <span class="text-xs">⚖️</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Akun Akrual:</span>
                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">2004-001 (GRNI)</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Nilai Akrual GRNI:</span>
                <span class="font-black text-amber-600 text-sm">Rp {{ number_format($receipt->total_estimated_amount, 0, ',', '.') }}</span>
            </div>
            <div class="text-[10.5px] text-slate-400">
                Akan otomatis dikliring saat faktur tagihan di-submit.
            </div>
        </div>
    </div>

    <!-- FORM THREE-WAY MATCHING -->
    <form action="{{ route('purchases.receipts.process-match', $receipt->id) }}" method="POST" class="space-y-4">
        @csrf

        <!-- BOX INPUT FAKTUR SUPPLIER -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 text-xs">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <div class="font-black text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🧾 Data Faktur Tagihan Asli Supplier (Vendor Invoice)</span>
                </div>
                <div class="text-[11px] text-slate-400">Isi sesuai lembar faktur resmi yang ditagihkan</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Nomor Faktur Tagihan: <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="invoice_number"
                           required
                           placeholder="Contoh: INV-2026/10/0981"
                           value="{{ old('invoice_number') }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Tanggal Faktur: <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           name="purchase_date"
                           required
                           value="{{ old('purchase_date', now()->toDateString()) }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Syarat Pembayaran (Terms): <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_type"
                            x-model="paymentType"
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="TEMPO">⏳ TEMPO / KREDIT (Hutang Dagang AP)</option>
                        <option value="TRANSFER">🏦 TRANSFER BANK (Lunas Langsung)</option>
                        <option value="CASH">💵 TUNAI / KAS BESAR (Lunas Langsung)</option>
                    </select>
                </div>

                <div x-show="paymentType === 'TEMPO'">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Tanggal Jatuh Tempo: <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           name="due_date"
                           :required="paymentType === 'TEMPO'"
                           value="{{ old('due_date', now()->addDays(30)->toDateString()) }}"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div x-show="paymentType !== 'TEMPO'" x-cloak>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Rekening Sumber Kas / Bank:
                    </label>
                    <select name="bank_account_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs dark:text-white">
                        <option value="">Pilih Rekening Kas/Bank</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->bank_name }} - {{ $bank->account_number }} ({{ $bank->account_holder }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- TABEL PENCOCOKAN HARGA & ITEM -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <div class="font-black text-sm text-slate-900 dark:text-white">Pencocokan Kuantitas &amp; Harga Satuan Faktur</div>
                    <div class="text-[11px] text-slate-500">Periksa harga satuan dari faktur supplier. Bila terdapat selisih dengan PO, sistem akan menandai Discrepancy Approval secara otomatis.</div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-100/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3 w-8">#</th>
                            <th class="p-3">Produk</th>
                            <th class="p-3 text-center">Satuan</th>
                            <th class="p-3 text-center">Qty Diterima (LPB)</th>
                            <th class="p-3 text-right">Harga Satuan PO</th>
                            <th class="p-3 text-right w-44">Harga Satuan Faktur <span class="text-rose-500">*</span></th>
                            <th class="p-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                        @foreach($receipt->items as $idx => $item)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="p-3 text-slate-400">{{ $loop->iteration }}</td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item->product->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $item->product->sku }}</div>
                                </td>
                                <td class="p-3 text-center font-semibold">
                                    {{ $item->unit_code }}
                                </td>
                                <td class="p-3 text-center font-black text-blue-600 dark:text-blue-400 text-sm">
                                    {{ number_format($item->quantity_received, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right font-medium text-slate-500">
                                    Rp {{ number_format($item->estimated_unit_price, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right">
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1.5 text-slate-400 font-bold text-xs">Rp</span>
                                        <input type="number"
                                               step="any"
                                               name="item_prices[{{ $item->id }}]"
                                               x-model.number="itemPrices['{{ $item->id }}']"
                                               @input="calculateTotals()"
                                               required
                                               class="w-full pl-8 pr-2 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-right font-bold text-xs dark:text-white focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <template x-if="hasPriceDiff('{{ $item->id }}', {{ $item->estimated_unit_price }})">
                                        <div class="text-[10px] text-amber-600 font-bold mt-0.5 text-right">
                                            ⚠️ Ada selisih harga dari PO
                                        </div>
                                    </template>
                                </td>
                                <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                    <span x-text="formatRupiah(getItemSubtotal('{{ $item->id }}', {{ $item->quantity_received }}))"></span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- TOTAL SUMMARY & PAJAK / DISKON -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-800 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Diskon Tambahan Faktur Supplier:
                        </label>
                        <div class="relative w-64">
                            <span class="absolute left-3 top-2 text-slate-400 font-bold">Rp</span>
                            <input type="number"
                                   step="any"
                                   name="discount_amount"
                                   x-model.number="discountAmount"
                                   @input="calculateTotals()"
                                   placeholder="0"
                                   class="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl font-bold text-xs dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Pajak PPN Masukan:
                        </label>
                        <div class="flex items-center gap-2">
                            <div class="relative w-64">
                                <span class="absolute left-3 top-2 text-slate-400 font-bold">Rp</span>
                                <input type="number"
                                       step="any"
                                       name="tax_amount"
                                       x-model.number="taxAmount"
                                       @input="calculateTotals()"
                                       placeholder="0"
                                       class="w-full pl-9 pr-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-xl font-bold text-xs dark:text-white">
                            </div>
                            <button type="button"
                                    @click="applyPpn(0.11)"
                                    class="px-2.5 py-1.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold cursor-pointer">
                                +11% PPN
                            </button>
                            <button type="button"
                                    @click="applyPpn(0.12)"
                                    class="px-2.5 py-1.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold cursor-pointer">
                                +12% PPN
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KALKULASI GRAND TOTAL -->
                <div class="space-y-2 text-xs flex flex-col justify-end">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Subtotal Barang:</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200" x-text="formatRupiah(subtotal)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400" x-show="discountAmount > 0">
                        <span>Diskon Faktur:</span>
                        <span class="font-bold text-rose-600" x-text="'- ' + formatRupiah(discountAmount)"></span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400" x-show="taxAmount > 0">
                        <span>PPN Masukan (Klaim Pajak):</span>
                        <span class="font-bold text-blue-600" x-text="'+ ' + formatRupiah(taxAmount)"></span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between items-baseline">
                        <span class="font-black text-sm text-slate-900 dark:text-white">Total Tagihan Definitif (AP):</span>
                        <span class="font-black text-lg text-emerald-600 dark:text-emerald-400" x-text="formatRupiah(grandTotal)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CATATAN MATCHING -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-2">
            <label class="block font-bold text-slate-700 dark:text-slate-300">
                Catatan Verifikasi Finance (Opsional):
            </label>
            <textarea name="notes"
                      rows="2"
                      placeholder="Contoh: Faktur tagihan sesuai DO dan PO, telah diperiksa finance..."
                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-white"></textarea>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center justify-between pt-2">
            <div class="text-[11px] text-slate-400">
                ⚖️ Tindakan ini akan mengkliring akun GRNI ke Hutang Usaha (AP), mencatat PPN Masukan, dan menyelesaikan Three-Way Matching.
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.receipts.show', $receipt->id) }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-emerald-500/25 cursor-pointer transition-all">
                    ⚡ Selesaikan Three-Way Matching &amp; Terbitkan Faktur AP
                </button>
            </div>
        </div>
    </form>

</div>

<script>
function threeWayMatchForm() {
    return {
        paymentType: 'TEMPO',
        discountAmount: 0,
        taxAmount: 0,
        subtotal: 0,
        grandTotal: 0,
        itemPrices: {
            @foreach($receipt->items as $item)
                '{{ $item->id }}': {{ (float)$item->estimated_unit_price }},
            @endforeach
        },
        init() {
            this.calculateTotals();
        },
        getItemSubtotal(itemId, qty) {
            const price = parseFloat(this.itemPrices[itemId]) || 0;
            return Math.round(price * qty);
        },
        hasPriceDiff(itemId, originalPrice) {
            const current = parseFloat(this.itemPrices[itemId]) || 0;
            return Math.abs(current - originalPrice) > 0.01;
        },
        calculateTotals() {
            let sub = 0;
            @foreach($receipt->items as $item)
                const price_{{ $loop->index }} = parseFloat(this.itemPrices['{{ $item->id }}']) || 0;
                sub += Math.round(price_{{ $loop->index }} * {{ (float)$item->quantity_received }});
            @endforeach
            this.subtotal = sub;
            const disc = parseFloat(this.discountAmount) || 0;
            const tax = parseFloat(this.taxAmount) || 0;
            this.grandTotal = Math.max(0, sub - disc + tax);
        },
        applyPpn(rate) {
            this.taxAmount = Math.round(this.subtotal * rate);
            this.calculateTotals();
        },
        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        }
    }
}
</script>
@endsection
