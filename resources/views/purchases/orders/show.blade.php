@extends('layouts.app')

@section('title', 'Detail Purchase Order ' . $purchaseOrder->po_number)
@section('tab_title', 'Detail PO')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full" x-data="{ receiveModal: false }">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.orders.index') }}" class="hover:text-blue-600 font-semibold">Purchase Orders</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $purchaseOrder->po_number }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📑 {{ $purchaseOrder->po_number }}</span>
                @if($purchaseOrder->status === 'DRAFT')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">DRAFT</span>
                @elseif($purchaseOrder->status === 'ORDERED')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300">ORDERED</span>
                @elseif($purchaseOrder->status === 'PARTIALLY_RECEIVED')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">PARTIAL (SEBAGIAN)</span>
                @elseif($purchaseOrder->status === 'COMPLETED')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">COMPLETED</span>
                @elseif($purchaseOrder->status === 'CANCELLED')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">DIBATALKAN</span>
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-2">
            @if($purchaseOrder->status === 'DRAFT' && in_array(auth()->user()->role, ['owner', 'manager']))
                <form action="{{ route('purchases.orders.approve', $purchaseOrder->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all cursor-pointer">
                        ✓ Setujui PO (Approve)
                    </button>
                </form>
            @endif

            @if(in_array($purchaseOrder->status, ['ORDERED', 'PARTIALLY_RECEIVED']))
                <a href="{{ route('purchases.receipts.create', $purchaseOrder->id) }}" class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-1.5 shadow-blue-500/25">
                    <span>📥 Terima Fisik Barang (LPB Gudang)</span>
                </a>
            @endif

            @if(in_array($purchaseOrder->status, ['DRAFT', 'ORDERED']) && in_array(auth()->user()->role, ['owner', 'manager', 'purchasing']))
                <form action="{{ route('purchases.orders.cancel', $purchaseOrder->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan Purchase Order ini? Alokasi stok terkait akan dilepaskan.')">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 font-extrabold text-xs rounded-xl border border-rose-200 dark:border-rose-800 transition-all cursor-pointer">
                        ✕ Batalkan PO
                    </button>
                </form>
            @endif

            <a href="{{ route('purchases.orders.print', $purchaseOrder->id) }}" target="_blank" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                <span>🖨️ Cetak Surat Pesanan (PO)</span>
            </a>

            <a href="{{ route('purchases.orders.index') }}" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl hover:bg-slate-200 transition-colors">
                &larr; Kembali
            </a>
        </div>
    </div>

    <!-- INFO RINGKASAN PO -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Pemasok:</div>
            <div class="font-black text-slate-900 dark:text-white text-sm mt-0.5">{{ $purchaseOrder->supplier->name }}</div>
            <div class="text-slate-500">{{ $purchaseOrder->supplier->phone }}</div>
        </div>

        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Tanggal Pemesanan:</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 text-sm mt-0.5">{{ $purchaseOrder->order_date->format('d/m/Y') }}</div>
            <div class="text-slate-500">Tiba: {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('d/m/Y') : '-' }}</div>
        </div>

        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Dibuat &amp; Disetujui:</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">Oleh: {{ $purchaseOrder->creator?->name ?? 'System' }}</div>
            <div class="text-slate-500">Approval: {{ $purchaseOrder->approver?->name ?? 'Menunggu Persetujuan' }}</div>
        </div>

        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Total Nilai PO:</div>
            <div class="text-lg font-black text-blue-600 dark:text-blue-400 mt-0.5">
                Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- TABEL RINCIAN ITEM & THREE-WAY MATCHING -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 font-extrabold text-sm text-slate-800 dark:text-white">
            📦 Rincian Barang &amp; Realisasi Penerimaan (Three-Way Matching)
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Nama Produk</th>
                        <th class="p-3 text-center">Satuan</th>
                        <th class="p-3 text-center">Qty Dipesan</th>
                        <th class="p-3 text-center">Qty Diterima</th>
                        <th class="p-3 text-center">Sisa Pesanan</th>
                        <th class="p-3 text-right">Harga Satuan</th>
                        <th class="p-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @foreach($purchaseOrder->items as $item)
                        @php
                            $remaining = max(0, (float)$item->quantity_ordered - (float)$item->quantity_received);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $item->product->name }}</div>
                                <div class="text-[10px] font-mono text-slate-400">{{ $item->product->sku }}</div>
                            </td>
                            <td class="p-3 text-center font-semibold">
                                {{ $item->unit_code }}
                            </td>
                            <td class="p-3 text-center font-bold">
                                {{ number_format($item->quantity_ordered, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center font-black {{ (float)$item->quantity_received >= (float)$item->quantity_ordered ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600' }}">
                                {{ number_format($item->quantity_received, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center font-bold {{ $remaining > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ number_format($remaining, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-bold">
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-black">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIWAYAT LAPORAN PENERIMAAN BARANG (LPB GUDANG) -->
    @if($purchaseOrder->goodsReceipts->isNotEmpty())
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs p-5 space-y-3">
            <div class="flex items-center justify-between">
                <div class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📦 Riwayat Laporan Penerimaan Barang (LPB Gudang)</span>
                </div>
                <div class="text-xs text-slate-400">Bukti fisik surat jalan & alokasi landed cost</div>
            </div>
            <div class="space-y-2 text-xs">
                @foreach($purchaseOrder->goodsReceipts as $gr)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">LPB: {{ $gr->receipt_number }} (Surat Jalan: {{ $gr->delivery_order_number }})</div>
                            <div class="text-slate-500 text-[11px]">Tanggal Terima: {{ $gr->received_date->format('d/m/Y') }} • Penerima: {{ $gr->receiver?->name ?? 'Gudang' }} • {{ $gr->items->count() }} item</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="font-black text-slate-900 dark:text-white">Rp {{ number_format($gr->total_estimated_amount, 0, ',', '.') }}</div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $gr->status === 'MATCHED' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $gr->status }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('purchases.receipts.show', $gr->id) }}" class="px-2.5 py-1 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-lg text-slate-800 dark:text-slate-200 font-bold text-[11px]">
                                    Detail LPB
                                </a>
                                @if($gr->status === 'PENDING_MATCH' && in_array(auth()->user()?->role, ['owner', 'manager', 'finance']))
                                    <a href="{{ route('purchases.receipts.match', $gr->id) }}" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-[11px]">
                                        Match Faktur
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- RIWAYAT FAKTUR PEMBELIAN (AP) TERKAIT -->
    @if($purchaseOrder->receipts->isNotEmpty())
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs p-5 space-y-3">
            <div class="font-bold text-sm text-slate-900 dark:text-white">Riwayat Faktur Pembelian (AP) Terkait</div>
            <div class="space-y-2 text-xs">
                @foreach($purchaseOrder->receipts as $receipt)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white">Faktur: {{ $receipt->invoice_number }} (Ref: {{ $receipt->reference_number }})</div>
                            <div class="text-slate-500 text-[11px]">Tanggal Faktur: {{ $receipt->purchase_date->format('d/m/Y') }} • Metode: {{ $receipt->payment_type }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($receipt->grand_total, 0, ',', '.') }}</div>
                            <a href="{{ route('purchases.show', $receipt->id) }}" class="text-[11px] text-blue-600 hover:underline font-bold">Lihat Faktur &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- MODAL THREE-WAY MATCHING PENERIMAAN BARANG -->
    <div x-show="receiveModal" x-cloak class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl p-6 max-w-2xl w-full shadow-2xl text-xs space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="font-black text-sm text-slate-900 dark:text-white">📥 Form Penerimaan Barang dari PO: {{ $purchaseOrder->po_number }}</div>
                <button type="button" @click="receiveModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('purchases.orders.receive', $purchaseOrder->id) }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">No. Surat Jalan / Faktur Supplier: <span class="text-rose-500">*</span></label>
                        <input type="text" name="invoice_number" required placeholder="Contoh: DO-2026-XXXX" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Jenis Pembayaran: <span class="text-rose-500">*</span></label>
                        <select name="payment_type" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                            <option value="CASH">CASH (Tunai)</option>
                            <option value="TRANSFER">TRANSFER (Bank)</option>
                            <option value="TEMPO" selected>TEMPO (Kredit / Hutang)</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <label class="block font-bold text-slate-800 dark:text-slate-200">Input Jumlah Fisik Barang yang Diterima:</label>
                    @foreach($purchaseOrder->items as $idx => $it)
                        @php
                            $rem = max(0, (float)$it->quantity_ordered - (float)$it->quantity_received);
                        @endphp
                        @if($rem > 0)
                            <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $it->product->name }}</div>
                                    <div class="text-[10px] text-slate-400">Sisa belum diterima: {{ $rem }} {{ $it->unit_code }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="items[{{ $idx }}][purchase_order_item_id]" value="{{ $it->id }}">
                                    <input type="number"
                                           name="items[{{ $idx }}][quantity]"
                                           value="{{ $rem }}"
                                           max="{{ $rem }}"
                                           step="0.01"
                                           min="0.01"
                                           class="w-24 px-2.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg text-center font-bold text-xs">
                                    <span class="font-bold text-slate-600 dark:text-slate-400">{{ $it->unit_code }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="receiveModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md cursor-pointer">
                        ✓ Konfirmasi Terima Barang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
