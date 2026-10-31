@extends('layouts.app')

@section('title', 'Form Penerimaan Fisik Barang (LPB Gudang)')
@section('tab_title', 'Input LPB Gudang')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full" x-data="goodsReceiptForm()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <a href="{{ route('purchases.receipts.index') }}" class="hover:text-blue-600 font-semibold">LPB Gudang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Terima Barang</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📥 Penerimaan Fisik Barang Gudang (LPB)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pencatatan fisik barang datang berdasarkan Surat Jalan Supplier untuk Purchase Order <span class="font-bold text-blue-600">{{ $purchaseOrder->po_number }}</span>.
            </p>
        </div>

        <div>
            <a href="{{ route('purchases.orders.show', $purchaseOrder->id) }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>&larr; Kembali ke PO</span>
            </a>
        </div>
    </div>

    <!-- FORM PENERIMAAN -->
    <form action="{{ route('purchases.receipts.store') }}" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

        <!-- INFORMASI PO & SURAT JALAN -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Box 1: PO & Supplier Info -->
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Referensi Purchase Order</div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Nomor PO:</span>
                    <span class="font-black text-blue-600 dark:text-blue-400 font-mono text-sm">{{ $purchaseOrder->po_number }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Pemasok / Vendor:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $purchaseOrder->supplier->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Lokasi Masuk:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchaseOrder->store->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Status PO:</span>
                    <span class="font-bold text-amber-600">{{ $purchaseOrder->status }}</span>
                </div>
            </div>

            <!-- Box 2: Dokumen Surat Jalan Supplier -->
            <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3 text-xs md:col-span-2">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Dokumen Fisik Surat Jalan & Tanggal Datang</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            No. Surat Jalan / Delivery Order (DO): <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="delivery_order_number"
                               required
                               placeholder="Contoh: SJ-2026-0988"
                               value="{{ old('delivery_order_number') }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-[10px] text-slate-400 mt-0.5">Nomor fisik surat jalan yang dibawa supir/kurir.</p>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Tanggal Fisik Diterima Gudang: <span class="text-rose-500">*</span>
                        </label>
                        <input type="date"
                               name="received_date"
                               required
                               value="{{ old('received_date', now()->toDateString()) }}"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Landed Cost / Biaya Pengiriman & Bongkar Muat -->
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Biaya Pengiriman (Ongkir):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-slate-400 font-bold">Rp</span>
                            <input type="number"
                                   step="any"
                                   name="shipping_fee"
                                   x-model.number="shippingFee"
                                   placeholder="0"
                                   class="w-full pl-9 pr-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Biaya Bongkar Muat / Handling:
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-slate-400 font-bold">Rp</span>
                            <input type="number"
                                   step="any"
                                   name="handling_fee"
                                   x-model.number="handlingFee"
                                   placeholder="0"
                                   class="w-full pl-9 pr-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DAFTAR ITEM DITERIMA -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <div class="font-black text-sm text-slate-900 dark:text-white">Rincian Barang yang Diterima</div>
                    <div class="text-[11px] text-slate-500">Periksa kuantitas fisik yang masuk dan masukkan nomor batch/lot jika ada.</div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-500 font-medium">Total Landed Cost: </span>
                    <span class="text-xs font-black text-amber-600" x-text="formatRupiah(totalLandedCost())"></span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-100/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-3 w-8">#</th>
                            <th class="p-3">Produk</th>
                            <th class="p-3 text-center">Satuan</th>
                            <th class="p-3 text-center">Dipesan</th>
                            <th class="p-3 text-center">Sdh Terima</th>
                            <th class="p-3 text-center">Sisa Pesanan</th>
                            <th class="p-3 text-center w-32">Qty Masuk Fisik <span class="text-rose-500">*</span></th>
                            <th class="p-3 text-center">No. Batch / Exp</th>
                            <th class="p-3 text-center">Kondisi Fisik</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                        @foreach($purchaseOrder->items as $idx => $item)
                            @php
                                $remaining = max(0, (float)$item->quantity_ordered - (float)$item->quantity_received);
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="p-3 text-slate-400">{{ $loop->iteration }}</td>
                                <td class="p-3">
                                    <input type="hidden" name="items[{{ $idx }}][purchase_order_item_id]" value="{{ $item->id }}">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item->product->name }}</div>
                                    <div class="text-[10.5px] font-mono text-slate-400">{{ $item->product->sku }}</div>
                                </td>
                                <td class="p-3 text-center font-semibold">
                                    {{ $item->unit_code }}
                                </td>
                                <td class="p-3 text-center font-bold text-slate-600 dark:text-slate-400">
                                    {{ number_format($item->quantity_ordered, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-center font-bold text-slate-500">
                                    {{ number_format($item->quantity_received, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-center font-black {{ $remaining > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                    {{ number_format($remaining, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-center">
                                    <input type="number"
                                           step="any"
                                           name="items[{{ $idx }}][received_quantity]"
                                           min="0"
                                           max="{{ $remaining }}"
                                           value="{{ old("items.{$idx}.received_quantity", $remaining) }}"
                                           required
                                           class="w-full px-2.5 py-1.5 text-center font-black text-xs rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-blue-500 text-blue-600 dark:text-blue-400">
                                </td>
                                <td class="p-3">
                                    <div class="space-y-1">
                                        <input type="text"
                                               name="items[{{ $idx }}][batch_number]"
                                               placeholder="No. Batch"
                                               class="w-full px-2 py-1 text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                        <input type="date"
                                               name="items[{{ $idx }}][expiry_date]"
                                               class="w-full px-2 py-1 text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                    </div>
                                </td>
                                <td class="p-3 text-center">
                                    <select name="items[{{ $idx }}][physical_condition]" class="px-2 py-1 text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                                        <option value="BAIK">✅ Baik / Segel Utuh</option>
                                        <option value="KEMASAN_PENYOK">⚠️ Kemasan Penyok</option>
                                        <option value="CACAT_DITERIMA">❌ Cacat / Perlu Retur</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CATATAN PETUGAS GUDANG -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-2">
            <label class="block font-bold text-slate-700 dark:text-slate-300">
                Catatan Petugas Gudang (Opsional):
            </label>
            <textarea name="notes"
                      rows="2"
                      placeholder="Contoh: Diterima oleh Supri di Gudang 1, supir PT Sumber Abadi, truk B 9123 XYZ..."
                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-white"></textarea>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="flex items-center justify-between pt-2">
            <div class="text-[11px] text-slate-400">
                ℹ️ Menyimpan formulir ini akan menambah stok fisik gudang secara instan dan mencatat akrual GRNI ke General Ledger.
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.orders.show', $purchaseOrder->id) }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold text-xs transition-colors">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-blue-500/25 cursor-pointer transition-all">
                    💾 Simpan LPB &amp; Tambah Stok Gudang
                </button>
            </div>
        </div>
    </form>

</div>

<script>
function goodsReceiptForm() {
    return {
        shippingFee: 0,
        handlingFee: 0,
        totalLandedCost() {
            return (parseFloat(this.shippingFee) || 0) + (parseFloat(this.handlingFee) || 0);
        },
        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        }
    }
}
</script>
@endsection
