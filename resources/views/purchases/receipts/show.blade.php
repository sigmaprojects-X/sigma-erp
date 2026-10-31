@extends('layouts.app')

@section('title', "LPB {$receipt->receipt_number}")
@section('tab_title', $receipt->receipt_number)

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <a href="{{ route('purchases.receipts.index') }}" class="hover:text-blue-600 font-semibold">LPB Gudang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold font-mono">{{ $receipt->receipt_number }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📦 Laporan Penerimaan Barang (LPB)</span>
                <span class="font-mono text-blue-600 dark:text-blue-400">{{ $receipt->receipt_number }}</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Dokumen bukti fisik penerimaan barang di gudang dari Surat Jalan Supplier.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.receipts.print', $receipt->id) }}"
               target="_blank"
               class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️ Cetak Surat LPB</span>
            </a>

            @if($receipt->status === 'RECEIVED' && in_array(auth()->user()?->role, ['owner', 'manager', 'finance']))
                <a href="{{ route('purchases.receipts.match', $receipt->id) }}"
                   class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-500/25 cursor-pointer">
                    <span>⚡ Three-Way Matching (Input Faktur)</span>
                </a>
            @endif

            <a href="{{ route('purchases.receipts.index') }}"
               class="px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-colors">
                &larr; Daftar LPB
            </a>
        </div>
    </div>

    <!-- STATUS BANNER -->
    @if($receipt->status === 'MATCHED')
        <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between text-xs text-emerald-900 dark:text-emerald-300">
            <div class="flex items-center gap-2">
                <span class="text-base">✅</span>
                <div>
                    <span class="font-bold">Three-Way Matching Selesai:</span>
                    <span>Penerimaan ini telah dicocokkan dengan Faktur Pembelian Supplier resmi. Akrual GRNI telah dikliring ke Hutang Usaha (AP).</span>
                </div>
            </div>
            @if($receipt->purchase)
                <a href="{{ route('purchases.show', $receipt->purchase->id) }}" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs whitespace-nowrap shadow-xs">
                    Buka Faktur {{ $receipt->purchase->invoice_number }} &rarr;
                </a>
            @endif
        </div>
    @elseif($receipt->status === 'CANCELLED')
        <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 flex items-center gap-2 text-xs text-rose-800 dark:text-rose-300">
            <span class="text-base">❌</span>
            <div>
                <span class="font-bold">Dokumen Dibatalkan:</span>
                <span>Penerimaan fisik ini telah dibatalkan dan stok dikembalikan.</span>
            </div>
        </div>
    @else
        <div class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between text-xs text-amber-900 dark:text-amber-300">
            <div class="flex items-center gap-2">
                <span class="text-base">⏳</span>
                <div>
                    <span class="font-bold">Menunggu Faktur Tagihan Supplier (Akrual GRNI Aktif):</span>
                    <span>Fisik barang telah masuk ke stok gudang dan nilai sementara dicatat pada akun <strong class="font-mono">2004-001 Hutang Belum Difakturkan (GRNI)</strong>. Silakan lakukan Three-Way Matching saat faktur tagihan tiba.</span>
                </div>
            </div>
            @if(in_array(auth()->user()?->role, ['owner', 'manager', 'finance']))
                <a href="{{ route('purchases.receipts.match', $receipt->id) }}" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-xs whitespace-nowrap shadow-xs">
                    Match Faktur Sekarang &rarr;
                </a>
            @endif
        </div>
    @endif

    <!-- GRID INFORMASI DOKUMEN -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Box 1: No Dokumen & Tanggal -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Identitas LPB</div>
            <div>
                <div class="text-slate-500 text-[11px]">No. LPB:</div>
                <div class="font-black text-slate-900 dark:text-white font-mono text-sm">{{ $receipt->receipt_number }}</div>
            </div>
            <div>
                <div class="text-slate-500 text-[11px]">Tanggal Terima Fisik:</div>
                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $receipt->receipt_date?->format('d F Y') }}</div>
            </div>
            <div>
                <div class="text-slate-500 text-[11px]">Petugas Penerima:</div>
                <div class="font-semibold text-slate-700 dark:text-slate-300">{{ $receipt->receiver?->name ?? 'Gudang' }}</div>
            </div>
        </div>

        <!-- Box 2: Surat Jalan & Supplier -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pemasok & Pengiriman</div>
            <div>
                <div class="text-slate-500 text-[11px]">Pemasok:</div>
                <div class="font-bold text-slate-900 dark:text-white">{{ $receipt->supplier->name }}</div>
            </div>
            <div>
                <div class="text-slate-500 text-[11px]">No. Surat Jalan / DO:</div>
                <div class="font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ $receipt->delivery_order_number }}</div>
            </div>
            <div>
                <div class="text-slate-500 text-[11px]">Gudang Tujuan:</div>
                <div class="font-semibold text-slate-700 dark:text-slate-300">{{ $receipt->store->name }}</div>
            </div>
        </div>

        <!-- Box 3: PO Terkait -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Purchase Order Terkait</div>
            @if($receipt->purchaseOrder)
                <div>
                    <div class="text-slate-500 text-[11px]">Nomor PO:</div>
                    <a href="{{ route('purchases.orders.show', $receipt->purchaseOrder->id) }}" class="font-black text-blue-600 dark:text-blue-400 font-mono hover:underline">
                        {{ $receipt->purchaseOrder->po_number }}
                    </a>
                </div>
                <div>
                    <div class="text-slate-500 text-[11px]">Tanggal PO:</div>
                    <div class="font-semibold text-slate-700 dark:text-slate-300">{{ $receipt->purchaseOrder->order_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-slate-500 text-[11px]">Status PO Saat Ini:</div>
                    <span class="font-bold text-amber-600">{{ $receipt->purchaseOrder->status }}</span>
                </div>
            @else
                <div class="text-slate-400 italic">Penerimaan Langsung (Non-PO)</div>
            @endif
        </div>

        <!-- Box 4: Landed Cost & Total Nilai -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-2 text-xs">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Landed Cost & Nilai Masuk</div>
            <div class="flex justify-between">
                <span class="text-slate-500">Biaya Ongkir:</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($receipt->shipping_fee, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Bongkar Muat:</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Rp {{ number_format($receipt->handling_fee, 0, ',', '.') }}</span>
            </div>
            <div class="pt-1 border-t border-slate-100 dark:border-slate-800 flex justify-between">
                <span class="text-slate-700 dark:text-slate-300 font-bold">Total Nilai GRNI:</span>
                <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">Rp {{ number_format($receipt->total_estimated_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- TABEL RINCIAN ITEM & ALOKASI BIAYA HPP -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-3.5 bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="font-black text-sm text-slate-900 dark:text-white">Rincian Fisik Barang &amp; Perhitungan HPP Moving Average</div>
            <div class="text-xs text-slate-500">Biaya ongkir &amp; handling dialokasikan proporsional per unit ke harga pokok.</div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-100/70 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="p-3 w-8">#</th>
                        <th class="p-3">Nama Produk &amp; SKU</th>
                        <th class="p-3 text-center">Satuan</th>
                        <th class="p-3 text-center">Qty Diterima</th>
                        <th class="p-3 text-right">Harga PO</th>
                        <th class="p-3 text-right">Alokasi Ongkir/Unit</th>
                        <th class="p-3 text-right">HPP Efektif Unit</th>
                        <th class="p-3 text-right">Subtotal</th>
                        <th class="p-3 text-center">Batch / Exp</th>
                        <th class="p-3 text-center">Kondisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @foreach($receipt->items as $item)
                        @php
                            $allocatedPerUnit = (float)$item->quantity_received > 0 ? ((float)$item->allocated_landed_cost / (float)$item->quantity_received) : 0;
                        @endphp
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
                            <td class="p-3 text-right font-medium text-slate-600 dark:text-slate-400">
                                Rp {{ number_format($item->estimated_unit_price, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-semibold text-amber-600">
                                + Rp {{ number_format($allocatedPerUnit, 2, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($item->effective_cost_price, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center text-[11px]">
                                @if($item->batch_number)
                                    <div class="font-mono font-bold">{{ $item->batch_number }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $item->expiry_date?->format('d/m/Y') ?? '-' }}</div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $item->physical_condition === 'BAIK' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $item->physical_condition ?? 'BAIK' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-slate-800/80 font-bold text-slate-800 dark:text-slate-200 border-t border-slate-200 dark:border-slate-800">
                    <tr>
                        <td colspan="7" class="p-3 text-right text-xs uppercase">Total Nilai Persediaan Fisik Diterima (GRNI):</td>
                        <td class="p-3 text-right text-sm font-black text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($receipt->total_estimated_amount, 0, ',', '.') }}
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- CATATAN DOKUMEN -->
    @if($receipt->notes)
        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300">
            <span class="font-bold">Catatan Gudang:</span> {{ $receipt->notes }}
        </div>
    @endif

</div>
@endsection
