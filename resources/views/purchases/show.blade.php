@extends('layouts.app')

@section('title', 'Detail Faktur Pembelian - ' . $purchase->reference_number)

@section('content')
<div class="space-y-4" x-data="purchaseShowApp()">

    <!-- HEADER & ACTIONS -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">🧾</span>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Detail Faktur Pembelian / GRN</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    {{ $purchase->status }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                No. Bukti: <strong class="text-slate-800 font-mono">{{ $purchase->reference_number }}</strong> | Faktur Supplier: <strong class="text-slate-800 font-mono">{{ $purchase->invoice_number }}</strong>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()?->role, ['owner', 'manager', 'purchasing']))
                @php
                    $canReturnAny = $purchase->items->sum('max_returnable') > 0;
                @endphp
                @if($canReturnAny)
                    <button type="button"
                            @click="returnModal = true"
                            class="px-3.5 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow-sm transition-colors flex items-center gap-1.5 cursor-pointer">
                        <span>🔄</span>
                        <span>Proses Retur Pembelian</span>
                    </button>
                @else
                    <button type="button"
                            disabled
                            class="px-3.5 py-1.5 text-xs font-bold text-slate-400 bg-slate-100 rounded-lg border border-slate-200 flex items-center gap-1.5 cursor-not-allowed"
                            title="Semua barang pada faktur ini sudah diretur secara penuh">
                        <span>🔄</span>
                        <span>Retur Penuh (Habis)</span>
                    </button>
                @endif
            @endif
            <a href="{{ route('purchases.shelf-tags', $purchase->id) }}" target="_blank" class="px-3.5 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition-colors flex items-center gap-1.5">
                <span>🏷️</span>
                <span>Cetak Label Rak (Shelf Tag)</span>
            </a>
            <a href="{{ route('purchases.print', $purchase->id) }}" target="_blank" class="px-3.5 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg transition-colors flex items-center gap-1.5">
                <span>🖨️</span>
                <span>Cetak Faktur (A4)</span>
            </a>
            <a href="{{ route('purchases.index') }}" class="px-3.5 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg transition-colors">
                ← Daftar Faktur
            </a>
        </div>
    </div>

    <!-- DETAIL CARD -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Supplier Info -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs text-xs space-y-2">
            <div class="font-bold text-slate-800 uppercase tracking-wider pb-1.5 border-b border-slate-100">
                🏭 Data Supplier
            </div>
            <div class="font-bold text-slate-900 text-sm">{{ $purchase->supplier->name }}</div>
            <div class="text-slate-500">Kode: <span class="font-mono text-slate-700">{{ $purchase->supplier->code ?: '-' }}</span></div>
            <div class="text-slate-500">Telepon: <span class="text-slate-700">{{ $purchase->supplier->phone ?: '-' }}</span></div>
            <div class="text-slate-500">Alamat: <span class="text-slate-700">{{ $purchase->supplier->address ?: '-' }}</span></div>
        </div>

        <!-- Info Transaksi -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs text-xs space-y-2">
            <div class="font-bold text-slate-800 uppercase tracking-wider pb-1.5 border-b border-slate-100">
                📋 Rincian Transaksi
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Tanggal Faktur:</span>
                <strong class="text-slate-800">{{ $purchase->purchase_date->format('d M Y') }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Metode Pembayaran:</span>
                <span class="font-bold text-slate-800">{{ $purchase->payment_type }}</span>
            </div>
            @if($purchase->payment_type === 'TEMPO' && $purchase->due_date)
                <div class="flex justify-between text-amber-700 font-medium">
                    <span>Jatuh Tempo:</span>
                    <strong>{{ $purchase->due_date->format('d M Y') }}</strong>
                </div>
            @endif
            @if($purchase->bankAccount)
                <div class="flex justify-between">
                    <span class="text-slate-500">Bank:</span>
                    <span class="text-slate-800">{{ $purchase->bankAccount->bank_name }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-slate-500">Gudang Penerima:</span>
                <span class="text-slate-800">{{ $purchase->store->name }}</span>
            </div>
        </div>

        <!-- Ringkasan Biaya -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs text-xs space-y-2">
            <div class="font-bold text-slate-800 uppercase tracking-wider pb-1.5 border-b border-slate-100">
                💰 Ringkasan Nominal
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Subtotal Barang:</span>
                <span class="font-mono">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($purchase->discount_amount > 0)
                <div class="flex justify-between text-rose-600">
                    <span>Diskon Global:</span>
                    <span class="font-mono">- Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            @if((float) ($purchase->shipping_cost ?? 0) > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Ongkos Kirim (Landed Cost):</span>
                    <span class="font-mono">+ Rp {{ number_format($purchase->shipping_cost, 0, ',', '.') }}</span>
                </div>
            @endif
            @if($purchase->tax_amount > 0)
                <div class="flex justify-between text-slate-600">
                    <span>PPN Masukan:</span>
                    <span class="font-mono">+ Rp {{ number_format($purchase->tax_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="pt-2 border-t border-slate-200 flex justify-between text-sm font-extrabold text-emerald-800">
                <span>Grand Total:</span>
                <span class="font-mono text-base">Rp {{ number_format($purchase->grand_total, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- ITEM DETAIL TABLE -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        <div class="p-3 bg-slate-50 border-b border-slate-200">
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                📦 Rincian Barang yang Diterima & Audit HPP Moving Average
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-2.5 px-3 w-8 text-center">#</th>
                        <th class="py-2.5 px-3">Produk & SKU</th>
                        <th class="py-2.5 px-3 text-center">Satuan Kemasan</th>
                        <th class="py-2.5 px-3 text-center">Qty Beli</th>
                        <th class="py-2.5 px-3 text-right">Harga Beli Kemasan</th>
                        <th class="py-2.5 px-3 text-center">Konversi Dasar</th>
                        <th class="py-2.5 px-3 text-right">Modal Faktur / Pcs</th>
                        <th class="py-2.5 px-3 text-right">Audit HPP Moving Avg</th>
                        <th class="py-2.5 px-3 text-center">Status Harga Jual (Opsi B)</th>
                        <th class="py-2.5 px-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchase->items as $idx => $it)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-3 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-800">{{ $it->product->name }}</div>
                                <div class="text-[10.5px] text-slate-500 font-mono">SKU: {{ $it->product->sku }}</div>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <span class="font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                    {{ $it->unit_code }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-slate-800">
                                {{ number_format($it->quantity, 0) }}
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-medium text-slate-800">
                                Rp {{ number_format($it->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-center font-mono text-slate-600">
                                = {{ number_format($it->base_quantity, 0) }} pcs
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                Rp {{ number_format($it->base_unit_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="font-mono text-slate-600 text-[11px]">
                                    Lama: Rp {{ \App\Models\Product::formatDecimalCurrency($it->old_cost_price) }}
                                </div>
                                <div class="font-mono font-extrabold text-emerald-700">
                                    Baru: Rp {{ \App\Models\Product::formatDecimalCurrency($it->new_cost_price) }}
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($it->is_selling_price_updated)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        ✅ DI-UPDATE
                                    </span>
                                    @if($it->new_selling_price)
                                        <div class="text-[10px] text-emerald-700 font-mono font-bold mt-0.5">
                                            Rp {{ number_format($it->new_selling_price, 0, ',', '.') }}
                                        </div>
                                    @endif
                                @elseif($it->is_price_increased)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">
                                        ⏳ HARGA TETAP
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        ⚖️ NORMAL
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-extrabold text-slate-900">
                                Rp {{ number_format($it->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIWAYAT RETUR PEMBELIAN PADA FAKTUR INI -->
    @if($purchase->returns && $purchase->returns->isNotEmpty())
        <div class="bg-white rounded-xl border border-rose-200 shadow-2xs overflow-hidden">
            <div class="p-3 bg-rose-50/80 border-b border-rose-200 flex items-center justify-between">
                <span class="text-xs font-bold text-rose-900 uppercase tracking-wider flex items-center gap-1.5">
                    <span>🔄</span>
                    <span>Riwayat Retur Barang pada Faktur Ini</span>
                </span>
                <span class="text-[11px] font-bold text-rose-700 font-mono">
                    Total Retur: Rp {{ number_format($purchase->returns->where('status', 'COMPLETED')->sum('grand_total'), 0, ',', '.') }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2 px-3">No. Retur</th>
                            <th class="py-2 px-3">Tanggal</th>
                            <th class="py-2 px-3">Metode Penyelesaian</th>
                            <th class="py-2 px-3 text-right">Nilai Retur</th>
                            <th class="py-2 px-3">Alasan</th>
                            <th class="py-2 px-3 text-center">Status</th>
                            <th class="py-2 px-3">Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($purchase->returns as $ret)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 px-3 font-mono font-bold text-blue-700">{{ $ret->return_number }}</td>
                                <td class="py-2.5 px-3 text-slate-600">{{ $ret->return_date ? $ret->return_date->format('d M Y') : '-' }}</td>
                                <td class="py-2.5 px-3">
                                    @if($ret->refund_type === 'DEBT_REDUCTION')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-300">Potong Hutang (AP)</span>
                                    @elseif($ret->refund_type === 'CASH')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">Kas Tunai</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300">Transfer Bank</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-rose-700">Rp {{ number_format((float) $ret->grand_total, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-slate-600 italic">"{{ $ret->reason }}"</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">✅ {{ $ret->status }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-slate-600">{{ $ret->creator?->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MODAL FORM INPUT RETUR PEMBELIAN -->
    @if(in_array(auth()->user()?->role, ['owner', 'manager', 'purchasing']))
        <div x-show="returnModal"
             x-cloak
             @keydown.escape.window="returnModal = false"
             class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white border-2 border-slate-700 rounded-xl max-w-3xl w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100">
                <form action="{{ route('purchases.return', $purchase->id) }}" method="POST" @submit="handleSubmit">
                    @csrf

                    <!-- Header Modal -->
                    <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between border-b border-slate-700">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🔄</span>
                            <div>
                                <h3 class="font-bold text-sm tracking-wide">
                                    Formulir Retur Barang ke Pemasok: <span class="text-amber-300 font-mono">{{ $purchase->reference_number }}</span>
                                </h3>
                                <p class="text-[10px] text-slate-400">Supplier: {{ $purchase->supplier->name }} | Gudang: {{ $purchase->store->name }}</p>
                            </div>
                        </div>
                        <button type="button" @click="returnModal = false" class="text-slate-400 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- Body Modal -->
                    <div class="p-4 space-y-3.5 max-h-[75vh] overflow-y-auto">
                        <!-- Alert Info -->
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-[11px] space-y-1">
                            <div class="font-bold">⚠️ Perhatian Pengembalian Barang:</div>
                            <div>Barang yang diretur akan mengurangi kuantitas stok toko secara otomatis dan mengurangi nilai persediaan. Pastikan stok fisik mencukupi.</div>
                            @if($purchase->payment_type === 'TEMPO')
                                <div class="font-semibold text-amber-800 mt-1">
                                    📌 Sisa Hutang Faktur Saat Ini: <strong>Rp {{ number_format($remainingDebt ?? 0, 0, ',', '.') }}</strong>
                                </div>
                            @endif
                        </div>

                        <!-- Tabel Pilihan Item Retur -->
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <table class="w-full text-left text-xs divide-y divide-slate-200">
                                <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="px-3 py-2 w-8 text-center">Pilih</th>
                                        <th class="px-3 py-2">Nama Barang &amp; SKU</th>
                                        <th class="px-2 py-2 text-center">Satuan</th>
                                        <th class="px-2 py-2 text-center">Qty Beli</th>
                                        <th class="px-2 py-2 text-center">Sudah Retur</th>
                                        <th class="px-2 py-2 text-center">Sisa Retur</th>
                                        <th class="px-3 py-2 text-right">Harga Beli</th>
                                        <th class="px-3 py-2 text-center w-28">Qty Di-retur</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-sans text-xs">
                                    <template x-for="(item, idx) in returnableItems" :key="item.id">
                                        <tr :class="item.selected ? 'bg-blue-50/50' : ''">
                                            <td class="px-3 py-2 text-center">
                                                <input type="checkbox"
                                                       x-model="item.selected"
                                                       :disabled="item.max_returnable <= 0"
                                                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="font-bold text-slate-800" x-text="item.name"></div>
                                                <div class="text-[10px] text-slate-400 font-mono" x-text="item.sku"></div>
                                            </td>
                                            <td class="px-2 py-2 text-center font-bold text-slate-600" x-text="item.unit_code"></td>
                                            <td class="px-2 py-2 text-center font-mono" x-text="item.quantity"></td>
                                            <td class="px-2 py-2 text-center font-mono text-slate-500" x-text="item.already_returned_qty"></td>
                                            <td class="px-2 py-2 text-center font-mono font-bold"
                                                :class="item.max_returnable > 0 ? 'text-emerald-700' : 'text-slate-400'"
                                                x-text="item.max_returnable"></td>
                                            <td class="px-3 py-2 text-right font-mono" x-text="'Rp ' + formatRupiah(item.unit_price)"></td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number"
                                                       step="1"
                                                       min="1"
                                                       :max="item.max_returnable"
                                                       x-model.number="item.qty_to_return"
                                                       :disabled="!item.selected || item.max_returnable <= 0"
                                                       class="w-20 text-center font-mono font-bold py-1 border border-slate-300 rounded text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden disabled:bg-slate-100 disabled:text-slate-400">
                                                <input type="hidden" :name="'items[' + idx + '][purchase_item_id]'" :value="item.id" :disabled="!item.selected">
                                                <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="item.qty_to_return" :disabled="!item.selected">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Refund Type & Reason Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1 text-[11px]">Metode Penyelesaian Refund:</label>
                                <select name="refund_type" x-model="refundType" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs bg-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                                    @if($purchase->payment_type === 'TEMPO')
                                        <option value="DEBT_REDUCTION">📝 Potong Hutang Dagang (DEBT REDUCTION)</option>
                                    @endif
                                    <option value="CASH">💵 Kas Tunai (CASH REFUND)</option>
                                    <option value="TRANSFER">🏦 Transfer Bank</option>
                                </select>
                                <span class="text-[10px] text-slate-500 mt-1 block">
                                    @if($purchase->payment_type === 'TEMPO')
                                        * DEBT REDUCTION langsung memotong sisa hutang pada faktur ini dan buku pembantu AP.
                                    @else
                                        * Faktur Tunai/Transfer diselesaikan dengan penerimaan kas atau transfer dari vendor.
                                    @endif
                                </span>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1 text-[11px]">Alasan Pengembalian (Wajib):</label>
                                <textarea name="reason"
                                          x-model="reason"
                                          rows="2"
                                          required
                                          placeholder="Contoh: Barang rusak saat diterima / kemasan cacat dari supplier"
                                          class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
                            </div>
                        </div>

                        <!-- Ringkasan Total Retur -->
                        <div class="p-3 bg-slate-100 rounded-lg space-y-1 font-mono text-xs border border-slate-200">
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="font-sans">Subtotal Item yang Diretur:</span>
                                <span x-text="'Rp ' + formatRupiah(calculateSubtotal())"></span>
                            </div>
                            @if((float) $purchase->tax_rate > 0)
                                <div class="flex justify-between items-center text-blue-700">
                                    <span class="font-sans">PPN Masukan Disesuaikan ({{ $purchase->tax_rate }}%):</span>
                                    <span x-text="'+Rp ' + formatRupiah(calculateTax())"></span>
                                </div>
                            @endif
                            <div class="flex justify-between items-center text-sm font-extrabold text-slate-900 pt-1.5 border-t border-slate-300">
                                <span class="font-sans">Grand Total Nilai Retur:</span>
                                <span class="text-rose-700 font-mono text-base" x-text="'Rp ' + formatRupiah(calculateGrandTotal())"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="bg-slate-100 px-4 py-2.5 border-t border-slate-200 flex items-center justify-between">
                        <button type="button" @click="returnModal = false" class="px-3.5 py-1.5 bg-white border border-slate-300 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                :disabled="isSubmitting || calculateGrandTotal() <= 0"
                                class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-lg shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                            <span x-show="!isSubmitting">💾 Konfirmasi &amp; Simpan Retur</span>
                            <span x-show="isSubmitting">Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
function purchaseShowApp() {
    return {
        returnModal: false,
        isSubmitting: false,
        refundType: '{{ $purchase->payment_type === "TEMPO" ? "DEBT_REDUCTION" : "CASH" }}',
        reason: '',
        taxRate: {{ (float) ($purchase->tax_rate ?? 0) }},
        remainingDebt: {{ (float) ($remainingDebt ?? 0) }},
        returnableItems: [
            @foreach($purchase->items as $it)
            {
                id: '{{ $it->id }}',
                name: '{{ addslashes($it->product->name) }}',
                sku: '{{ $it->product->sku }}',
                unit_code: '{{ $it->unit_code }}',
                unit_price: {{ (float) $it->unit_price }},
                quantity: {{ (float) $it->quantity }},
                already_returned_qty: {{ (float) ($it->already_returned_qty ?? 0) }},
                max_returnable: {{ (float) ($it->max_returnable ?? $it->quantity) }},
                qty_to_return: {{ (float) ($it->max_returnable > 0 ? 1 : 0) }},
                selected: false
            },
            @endforeach
        ],

        calculateSubtotal() {
            return this.returnableItems.reduce((acc, item) => {
                if (item.selected && item.qty_to_return > 0) {
                    return acc + (item.qty_to_return * item.unit_price);
                }
                return acc;
            }, 0);
        },

        calculateTax() {
            if (this.taxRate <= 0) return 0;
            return Math.round(this.calculateSubtotal() * (this.taxRate / 100));
        },

        calculateGrandTotal() {
            return this.calculateSubtotal() + this.calculateTax();
        },

        formatRupiah(val) {
            let num = parseFloat(val) || 0;
            return Math.round(num).toLocaleString('id-ID');
        },

        handleSubmit(e) {
            const selected = this.returnableItems.filter(i => i.selected && i.qty_to_return > 0);
            if (selected.length === 0) {
                alert('Pilih minimal satu barang yang akan diretur.');
                e.preventDefault();
                return;
            }

            for (let it of selected) {
                if (it.qty_to_return > it.max_returnable) {
                    alert('Kuantitas retur untuk ' + it.name + ' (' + it.qty_to_return + ') melebihi batas sisa (' + it.max_returnable + ').');
                    e.preventDefault();
                    return;
                }
            }

            const total = this.calculateGrandTotal();
            if (this.refundType === 'DEBT_REDUCTION' && '{{ $purchase->payment_type }}' === 'TEMPO') {
                if (total > this.remainingDebt) {
                    alert('Nilai retur (Rp ' + this.formatRupiah(total) + ') melebihi sisa hutang faktur (Rp ' + this.formatRupiah(this.remainingDebt) + '). Harap kurangi kuantitas atau gunakan metode CASH / TRANSFER.');
                    e.preventDefault();
                    return;
                }
            }

            if (!confirm('Apakah Anda yakin ingin memproses retur pembelian senilai Rp ' + this.formatRupiah(total) + '? Stok barang akan berkurang secara otomatis.')) {
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
