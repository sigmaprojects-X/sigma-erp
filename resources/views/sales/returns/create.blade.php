@extends('layouts.app')

@section('title', 'Buat Retur Penjualan Baru')
@section('tab_title', 'Input Retur Penjualan')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.returns.index') }}" class="hover:underline">Retur Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🔄</span> Form Retur Penjualan &amp; Pengembalian Barang
            </h1>
        </div>
        <a href="{{ route('sales.returns.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            ← Kembali ke Daftar Retur
        </a>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- STEP 1: CARI NOMOR FAKTUR PENJUALAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
            1. Cari Faktur / Scan Barcode Struk
        </h3>
        <form method="GET" action="{{ route('sales.returns.create') }}" class="flex gap-2">
            <div class="relative flex-1">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                <input type="text"
                       name="invoice_number"
                       value="{{ request('invoice_number') }}"
                       required
                       placeholder="Scan barcode struk atau ketik no faktur (misal: POS-20261027-... atau INV-...)"
                       class="w-full pl-9 pr-3 py-2 border border-slate-300 dark:border-slate-700 rounded-xl font-mono text-xs font-bold bg-white dark:bg-slate-800 focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer">
                Cari Faktur
            </button>
        </form>
    </div>

    @if($selectedTransaction)
        <!-- STEP 2: FORM PILIH ITEM & PENGEMBALIAN DANA -->
        <form action="{{ route('sales.returns.store') }}" method="POST" id="returnForm" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
            @csrf
            <input type="hidden" name="pos_transaction_id" value="{{ $selectedTransaction->id }}">

            <!-- INFORMASI FAKTUR ASAL -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-3 bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                <div>
                    <span class="text-slate-400 block font-semibold">Nomor Faktur:</span>
                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ $selectedTransaction->invoice_number }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold">Pelanggan:</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $selectedTransaction->customer->name ?? 'Pelanggan Umum' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold">Tanggal Belanja:</span>
                    <span class="font-bold">{{ $selectedTransaction->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold">Metode Bayar Asli:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $selectedTransaction->payment_method }}</span>
                </div>
            </div>

            <!-- TABEL DAFTAR BARANG YANG BISA DIRETUR -->
            <div class="space-y-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    2. Pilih Barang &amp; Tentukan Kuantitas Retur
                </h3>

                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold text-[10px] uppercase">
                            <tr>
                                <th class="py-2.5 px-3">Produk</th>
                                <th class="py-2.5 px-3 text-center">Satuan</th>
                                <th class="py-2.5 px-3 text-right">Qty Beli</th>
                                <th class="py-2.5 px-3 text-right">Sudah Diretur</th>
                                <th class="py-2.5 px-3 text-right">Sisa Retur Max</th>
                                <th class="py-2.5 px-3 text-right">Harga Satuan</th>
                                <th class="py-2.5 px-3 w-28 text-center">Qty Retur</th>
                                <th class="py-2.5 px-3 w-40">Kondisi Fisik</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($selectedTransaction->items as $index => $item)
                                @php $max = $item->max_returnable; @endphp
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $item->product->name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $item->product->sku }}</div>
                                        <input type="hidden" name="items[{{ $index }}][pos_transaction_item_id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="py-2.5 px-3 text-center font-bold text-slate-600">
                                        {{ $item->productUnit->unit_code ?? 'Pcs' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-semibold">
                                        {{ (float) $item->quantity }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right text-rose-600 font-semibold">
                                        {{ (float) $item->already_returned_qty }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold text-emerald-600">
                                        {{ $max }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-bold">
                                        Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if($max > 0)
                                            <input type="number" step="any" min="0" max="{{ $max }}"
                                                   name="items[{{ $index }}][quantity]"
                                                   value="0"
                                                   class="w-20 px-2 py-1 border border-slate-300 dark:border-slate-700 rounded-lg text-center font-black text-xs text-rose-600 bg-white dark:bg-slate-800 focus:ring-2 focus:ring-rose-500">
                                        @else
                                            <span class="text-[11px] text-slate-400 font-bold">Lunas Retur</span>
                                            <input type="hidden" name="items[{{ $index }}][quantity]" value="0">
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <select name="items[{{ $index }}][condition]" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 py-1 px-1.5 font-semibold">
                                            <option value="GOOD">✅ Layak Jual (Restock Rak)</option>
                                            <option value="DAMAGED">⚠️ Rusak (Masuk Karantina)</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- STEP 3: METODE REFUND & ALASAN -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Pengembalian Dana (Refund) *</label>
                    <select name="refund_method" required class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                        <option value="CASH">💵 Tunai (Kas Laci Kasir)</option>
                        <option value="TRANSFER">🏦 Transfer Bank</option>
                        @if($selectedTransaction->customer_id)
                            <option value="CREDIT_REDUCTION" {{ $selectedTransaction->remaining_receivable > 0 ? 'selected' : '' }}>📝 Potong Piutang Tempo Pelanggan</option>
                        @endif
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan Retur Barang *</label>
                    <input type="text" name="reason" required placeholder="Misal: Kemasan bocor / Salah ambil varian"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                </div>

                @if(!in_array(auth()->user()?->role, ['owner', 'manager', 'store_head']))
                    <div>
                        <label class="block text-xs font-bold text-rose-700 dark:text-rose-400 mb-1">PIN Otorisasi Manajer *</label>
                        <input type="password" name="manager_pin" required placeholder="Masukkan PIN Manajer"
                               class="w-full text-xs rounded-xl border border-rose-300 dark:border-rose-700 bg-white dark:bg-slate-800 px-3 py-2">
                    </div>
                @endif
            </div>

            <!-- ACTION BUTTONS -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs rounded-xl shadow-md transition-all cursor-pointer flex items-center gap-1.5">
                    <span>🔄</span> Proses &amp; Cetak Nota Retur
                </button>
            </div>
        </form>
    @elseif(request('invoice_number'))
        <div class="p-6 text-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-500 text-xs">
            Faktur penjualan dengan nomor <strong>"{{ request('invoice_number') }}"</strong> tidak ditemukan. Pastikan nomor faktur sudah benar.
        </div>
    @endif

</div>
@endsection
