@extends('layouts.app')

@section('title', 'Detail Transfer Cabang ' . $transfer->transfer_number)
@section('tab_title', 'Detail Transfer')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.transfers.index') }}" class="hover:text-blue-600 font-semibold">Transfer Cabang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold font-mono">{{ $transfer->transfer_number }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    {{ $transfer->transfer_number }}
                </h1>
                @if($transfer->status === 'DRAFT')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">DRAFT</span>
                @elseif($transfer->status === 'IN_TRANSIT')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 animate-pulse">🚚 IN_TRANSIT (Dalam Perjalanan)</span>
                @elseif($transfer->status === 'RECEIVED')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✅ RECEIVED (Selesai Diterima)</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ $transfer->status }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.transfers.print', $transfer->id) }}" target="_blank"
               class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️</span>
                <span>Cetak Surat Jalan</span>
            </a>
            <a href="{{ route('warehouse.transfers.index') }}"
               class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs">
            <ul class="list-disc pl-4 space-y-0.5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- GRID INFORMASI PENGIRIMAN & STATUS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- CABANG ASAL & TUJUAN -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-3">
            <h2 class="font-bold uppercase tracking-wider text-slate-400 text-[10.5px]">Rute Pengiriman</h2>
            <div class="space-y-2">
                <div class="flex items-start gap-2">
                    <span class="text-slate-400 font-medium w-16">Dari:</span>
                    <div>
                        <div class="font-black text-slate-900 dark:text-white">{{ $transfer->sourceStore->name }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $transfer->sourceStore->code }}</div>
                    </div>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-slate-400 font-medium w-16">Tujuan:</span>
                    <div>
                        <div class="font-black text-indigo-600 dark:text-indigo-400">{{ $transfer->destinationStore->name }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ $transfer->destinationStore->code }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOGISTIK & ARMADA -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-3">
            <h2 class="font-bold uppercase tracking-wider text-slate-400 text-[10.5px]">Logistik & Armada</h2>
            <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between">
                    <span class="text-slate-400">Tanggal Kirim:</span>
                    <span class="font-bold">{{ $transfer->transfer_date->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Driver / Kurir:</span>
                    <span class="font-bold">{{ $transfer->driver_name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Nomor Kendaraan:</span>
                    <span class="font-mono font-bold">{{ $transfer->vehicle_number ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- PETUGAS & AUDIT TRAIL -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-3">
            <h2 class="font-bold uppercase tracking-wider text-slate-400 text-[10.5px]">Audit Trail</h2>
            <div class="space-y-1.5 text-slate-700 dark:text-slate-300">
                <div class="flex justify-between">
                    <span class="text-slate-400">Pengirim (Dispatch):</span>
                    <span class="font-bold">{{ $transfer->sender ? $transfer->sender->name : '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Penerima (Receipt):</span>
                    <span class="font-bold">{{ $transfer->receiver ? $transfer->receiver->name : '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Waktu Diterima:</span>
                    <span class="font-bold">{{ $transfer->received_at ? $transfer->received_at->format('d/m/Y H:i') : '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RINCIAN BARANG -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Rincian Barang Transfer
            </h2>
            <span class="text-xs text-slate-400 font-bold">Total Dikirim: {{ number_format($transfer->total_sent_quantity, 2) }}</span>
        </div>

        <form action="{{ route('warehouse.transfers.receive', $transfer->id) }}" method="POST" id="receiveForm">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-3">Produk</th>
                            <th class="p-3">Satuan</th>
                            <th class="p-3 text-right">Qty Dikirim</th>
                            <th class="p-3 text-right">Konversi Satuan Dasar</th>
                            <th class="p-3 text-right">Qty Diterima</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                        @foreach($transfer->items as $item)
                            <tr>
                                <td class="p-3">
                                    <div class="font-bold">{{ $item->product->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">{{ $item->product->sku }}</div>
                                </td>
                                <td class="p-3 font-semibold">
                                    {{ $item->productUnit ? $item->productUnit->unit_code : 'BASE' }}
                                </td>
                                <td class="p-3 text-right font-mono font-bold">
                                    {{ number_format($item->quantity_sent, 2) }}
                                </td>
                                <td class="p-3 text-right font-mono text-slate-500">
                                    {{ number_format($item->base_quantity_sent, 2) }} {{ $item->product->base_unit }}
                                </td>
                                <td class="p-3 text-right font-mono">
                                    @if($transfer->status === 'IN_TRANSIT')
                                        <input type="number" step="0.01" min="0"
                                               name="received_quantities[{{ $item->id }}]"
                                               value="{{ $item->quantity_sent }}"
                                               class="w-24 px-2 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-right font-bold text-xs focus:ring-2 focus:ring-blue-500">
                                    @else
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ number_format($item->quantity_received, 2) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- FORM ACTIONS DEPENDING ON STATUS -->
            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <div class="text-xs text-slate-500">
                    @if($transfer->notes)
                        <span class="font-bold">Catatan:</span> {{ $transfer->notes }}
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @if($transfer->status === 'DRAFT')
                        <form action="{{ route('warehouse.transfers.cancel', $transfer->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengiriman transfer ini? Alokasi kebutuhan stok cabang tujuan akan dilepaskan.')">
                            @csrf
                            <button type="submit"
                                    class="px-3.5 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-400 font-extrabold text-xs rounded-xl border border-rose-200 dark:border-rose-800 transition-all cursor-pointer">
                                ✕ Batalkan Transfer
                            </button>
                        </form>
                        <form action="{{ route('warehouse.transfers.dispatch', $transfer->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memberangkatkan armada transfer ini? Stok toko asal akan dipotong langsung.')">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-amber-500/25 cursor-pointer">
                                🚀 Berangkatkan Pengiriman (Dispatch In-Transit)
                            </button>
                        </form>
                    @elseif($transfer->status === 'IN_TRANSIT')
                        <button type="submit"
                                onclick="return confirm('Konfirmasi penerimaan barang di toko tujuan? Stok fisik toko tujuan akan otomatis bertambah.')"
                                class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-emerald-500/25 cursor-pointer">
                            ✅ Konfirmasi Terima Barang (Store Receipt)
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
