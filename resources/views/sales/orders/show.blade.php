@extends('layouts.app')

@section('title', 'Detail Pesanan Penjualan ' . $order->order_number)
@section('tab_title', 'SO: ' . $order->order_number)

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.orders.index') }}" class="hover:underline">Pesanan Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $order->order_number }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 {{ $order->order_number }}</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sales.orders.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-all">
                ← Kembali
            </a>
            <button onclick="window.print()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                🖨️ Cetak Proforma
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- STATUS & INFO CARD -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
        <div>
            <div class="text-[10px] text-slate-400 font-bold uppercase">Status Pesanan</div>
            <div class="mt-1">
                @if($order->status === 'PENDING')
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                        ● MENUNGGU PERSETUJUAN
                    </span>
                @elseif($order->status === 'APPROVED')
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                        ✓ DISETUJUI (SIAP PROSES)
                    </span>
                @elseif($order->status === 'PROCESSED')
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                        ★ FAKTUR KASIR TERBIT
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-500">
                        ✕ DIBATALKAN
                    </span>
                @endif
            </div>
        </div>

        <div>
            <div class="text-[10px] text-slate-400 font-bold uppercase">Pelanggan / Member</div>
            <div class="text-sm font-extrabold text-slate-900 dark:text-white mt-0.5">
                {{ $order->customer?->name ?? 'Pelanggan Umum' }}
            </div>
            @if($order->customer?->phone)
                <div class="text-slate-500 text-[11px]">{{ $order->customer->phone }}</div>
            @endif
        </div>

        <div>
            <div class="text-[10px] text-slate-400 font-bold uppercase">Toko &amp; Tanggal</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $order->store?->name }}</div>
            <div class="text-slate-500 text-[11px]">{{ $order->order_date ? $order->order_date->format('d F Y') : '-' }}</div>
        </div>

        <div>
            <div class="text-[10px] text-slate-400 font-bold uppercase">Dibuat Oleh</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">{{ $order->creator?->name ?? 'Kasir / Sales' }}</div>
            <div class="text-slate-500 text-[11px]">{{ $order->created_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <!-- TABEL ITEM PESANAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Rincian Barang yang Dipesan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/50 dark:bg-slate-800/30 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-2.5 px-4">Nama Produk</th>
                        <th class="py-2.5 px-4">Satuan</th>
                        <th class="py-2.5 px-4 text-center">Jumlah</th>
                        <th class="py-2.5 px-4 text-right">Harga Satuan</th>
                        <th class="py-2.5 px-4 text-right">Potongan</th>
                        <th class="py-2.5 px-4 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $item->product?->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $item->product?->sku }}</div>
                            </td>
                            <td class="py-3 px-4 font-semibold">{{ $item->unit_code }}</td>
                            <td class="py-3 px-4 text-center font-black">{{ (float)$item->quantity }}</td>
                            <td class="py-3 px-4 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right text-rose-600">-Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 font-bold">
                    <tr>
                        <td colspan="5" class="py-2.5 px-4 text-right uppercase text-[11px] text-slate-500">Subtotal:</td>
                        <td class="py-2.5 px-4 text-right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($order->discount_amount > 0)
                        <tr>
                            <td colspan="5" class="py-1 px-4 text-right uppercase text-[11px] text-rose-500">Total Diskon:</td>
                            <td class="py-1 px-4 text-right text-rose-600">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="text-sm font-black border-t border-slate-200 dark:border-slate-800">
                        <td colspan="5" class="py-3 px-4 text-right text-slate-900 dark:text-white">TOTAL PESANAN:</td>
                        <td class="py-3 px-4 text-right text-blue-600 dark:text-blue-400">
                            Rp {{ number_format($order->grand_total, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <div>
            @if($order->notes)
                <div class="text-[11px] text-slate-500">
                    <b>Catatan:</b> {{ $order->notes }}
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            @if($order->status === 'PENDING')
                <form action="{{ route('sales.orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Batalkan pesanan ini?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-rose-600 font-bold text-xs rounded-xl cursor-pointer">
                        Batalkan Pesanan
                    </button>
                </form>

                <form action="{{ route('sales.orders.approve', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs cursor-pointer">
                        ✓ Setujui Pesanan (Approve)
                    </button>
                </form>
            @endif

            @if($order->status === 'APPROVED')
                <form action="{{ route('sales.orders.process-to-sale', $order->id) }}" method="POST" onsubmit="return confirm('Proses pesanan ini langsung menjadi faktur kasir POS?')">
                    @csrf
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs rounded-xl shadow-md shadow-emerald-500/25 cursor-pointer">
                        🚀 Proses ke Faktur Kasir POS (Checkout)
                    </button>
                </form>

                <form action="{{ route('sales.orders.complete', $order->id) }}" method="POST"
                      onsubmit="return confirm('Tandai pesanan ini sebagai Selesai? Stok barang akan otomatis dikurangi dari inventaris.')">
                    @csrf
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-black text-xs rounded-xl shadow-md cursor-pointer">
                        ✓ Tandai Selesai (Potong Stok Otomatis)
                    </button>
                </form>
            @endif

            @if($order->status === 'COMPLETED')
                <div class="flex flex-col gap-1">
                    <span class="px-4 py-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 font-black text-xs rounded-xl border border-emerald-200 dark:border-emerald-700">
                        ✓ PESANAN SELESAI
                    </span>
                    @if($order->stock_deducted_at)
                        <span class="text-[10px] text-slate-500 text-center">Stok dikurangi: {{ $order->stock_deducted_at->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
            @endif

            @if($order->status === 'PROCESSED' && $order->pos_transaction_id)
                <a href="{{ route('pos.receipt', $order->pos_transaction_id) }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs">
                    ★ Lihat Struk Penjualan Kasir
                </a>
            @endif
        </div>
    </div>

</div>
@endsection
