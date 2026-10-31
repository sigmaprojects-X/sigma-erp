@extends('layouts.app')

@section('title', 'Detail Permintaan Transfer ' . $transferRequest->request_number)
@section('tab_title', 'Detail Permintaan')

@section('content')
<div class="space-y-4 p-4 max-w-6xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.requests.index') }}" class="hover:text-blue-600 font-semibold">Permintaan Cabang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $transferRequest->request_number }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    📋 Permintaan: {{ $transferRequest->request_number }}
                </h1>
                @if($transferRequest->status === 'REQUESTED')
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200">
                        ⏳ MENUNGGU PERSETUJUAN
                    </span>
                @elseif($transferRequest->status === 'APPROVED')
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200">
                        ✅ DISETUJUI PENUH
                    </span>
                @elseif($transferRequest->status === 'PARTIAL')
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200">
                        ⚡ DISETUJUI SEBAGIAN
                    </span>
                @elseif($transferRequest->status === 'FULFILLED')
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200">
                        📦 TERPENUHI (SELESAI DITERIMA)
                    </span>
                @elseif($transferRequest->status === 'REJECTED')
                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-200">
                        ❌ DITOLAK
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.requests.index') }}"
               class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                Kembali ke Daftar
            </a>
            @if($transferRequest->stockTransfer)
                <a href="{{ route('warehouse.transfers.show', $transferRequest->stockTransfer->id) }}"
                   class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                    <span>🚚 Lihat Surat Jalan Pengiriman</span>
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-700 dark:text-emerald-400 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-3 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-700 dark:text-amber-400 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('warning') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-rose-700 dark:text-rose-400 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>❌</span>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($transferRequest->status === 'REJECTED' && $transferRequest->rejection_reason)
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-2xl text-rose-700 dark:text-rose-300 text-xs font-medium">
            <div class="font-bold mb-1">Alasan Penolakan Permintaan:</div>
            <div>"{{ $transferRequest->rejection_reason }}"</div>
            <div class="text-[10.5px] text-rose-500 mt-1">Ditolak oleh: {{ $transferRequest->approver?->name ?? 'Pusat' }} pada {{ $transferRequest->approved_at ? $transferRequest->approved_at->format('d/m/Y H:i') : '-' }}</div>
        </div>
    @endif

    <!-- INFORMASI DOKUMEN -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-slate-400 uppercase text-[10px] font-bold">Cabang Peminta</div>
            <div class="text-sm font-extrabold text-slate-900 dark:text-white mt-0.5">
                🏬 {{ $transferRequest->requestingStore->name }}
            </div>
            <div class="text-[10px] text-slate-500 font-mono mt-0.5">Kode: {{ $transferRequest->requestingStore->code }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-slate-400 uppercase text-[10px] font-bold">Tujuan Permintaan (Pusat)</div>
            <div class="text-sm font-extrabold text-slate-900 dark:text-white mt-0.5">
                🏢 {{ $transferRequest->destinationStore->name }}
            </div>
            <div class="text-[10px] text-slate-500 font-mono mt-0.5">Kode: {{ $transferRequest->destinationStore->code }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-slate-400 uppercase text-[10px] font-bold">Tanggal &amp; Pemohon</div>
            <div class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">
                {{ date('d/m/Y', strtotime($transferRequest->request_date)) }}
            </div>
            <div class="text-[10.5px] text-slate-500 mt-0.5">Petugas: {{ $transferRequest->creator?->name ?? 'Sistem' }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-slate-400 uppercase text-[10px] font-bold">Total Kuantitas</div>
            <div class="text-sm font-black text-blue-600 dark:text-blue-400 mt-0.5">
                {{ number_format($transferRequest->total_requested_quantity, 0, ',', '.') }} Unit Dasar
            </div>
            <div class="text-[10.5px] text-slate-500 mt-0.5">Sebanyak {{ $transferRequest->total_items }} SKU Barang</div>
        </div>
    </div>

    @if($transferRequest->notes)
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-600 dark:text-slate-300">
            <strong>Catatan Permintaan:</strong> {{ $transferRequest->notes }}
        </div>
    @endif

    <!-- TABEL RINCIAN BARANG -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center justify-between">
            <span>📦 Daftar Barang Permintaan Pasokan</span>
            <span class="text-[11px] font-medium text-slate-400">Total: {{ $transferRequest->items->count() }} Baris Item</span>
        </h2>

        <form action="{{ route('warehouse.requests.approve', $transferRequest->id) }}" method="POST" id="approvalForm">
            @csrf

            <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-2.5 min-w-[260px]">Produk / SKU</th>
                            <th class="p-2.5 w-28 text-center">Kemasan</th>
                            <th class="p-2.5 w-32 text-right">Stok Pusat Saat Ini</th>
                            <th class="p-2.5 w-32 text-right font-black text-blue-700 dark:text-blue-400">Qty Diminta</th>
                            @if(in_array($transferRequest->status, ['REQUESTED', 'PARTIAL']))
                                <th class="p-2.5 w-36 text-right font-black text-emerald-700 dark:text-emerald-400">Qty Disetujui</th>
                            @else
                                <th class="p-2.5 w-32 text-right font-black text-emerald-700 dark:text-emerald-400">Qty Disetujui</th>
                                <th class="p-2.5 w-32 text-right font-black text-purple-700 dark:text-purple-400">Qty Diterima</th>
                            @endif
                            <th class="p-2.5 min-w-[140px]">Catatan Baris</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @foreach($transferRequest->items as $idx => $item)
                            @php
                                $destStock = $item->product->stocks->firstWhere('store_id', $transferRequest->destination_store_id);
                                $destAvail = $destStock ? max(0, (float)$destStock->physical_quantity - (float)$destStock->reserved_quantity - (float)$destStock->quarantined_quantity) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-2.5">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $item->product->name }}</div>
                                    <div class="text-[10px] font-mono text-slate-400">SKU: {{ $item->product->sku }} | Kategori: {{ $item->product->category?->name ?? 'Umum' }}</div>
                                </td>
                                <td class="p-2.5 text-center font-bold text-slate-600 dark:text-slate-300">
                                    {{ $item->unit->unit_code }} (x{{ (float)$item->unit->conversion_factor }})
                                </td>
                                <td class="p-2.5 text-right font-mono">
                                    <div class="font-black {{ $destAvail <= 0 ? 'text-rose-600' : 'text-slate-800 dark:text-slate-200' }}">
                                        {{ number_format($destAvail, 0, ',', '.') }} Unit
                                    </div>
                                    <div class="text-[9.5px] text-slate-400">Di Pusat</div>
                                </td>
                                <td class="p-2.5 text-right font-mono font-black text-blue-600 dark:text-blue-400 text-sm">
                                    {{ number_format($item->requested_quantity, 0, ',', '.') }}
                                </td>
                                @if(in_array($transferRequest->status, ['REQUESTED', 'PARTIAL']))
                                    <td class="p-2.5 text-right">
                                        <input type="hidden" name="approved_items[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                        <input type="number" step="0.01" min="0" name="approved_items[{{ $idx }}][approved_quantity]"
                                               value="{{ $item->approved_quantity > 0 ? $item->approved_quantity : $item->requested_quantity }}"
                                               class="w-full px-2 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-xs font-bold text-right font-mono text-emerald-600 focus:ring-2 focus:ring-emerald-500">
                                    </td>
                                @else
                                    <td class="p-2.5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($item->approved_quantity, 0, ',', '.') }}
                                    </td>
                                    <td class="p-2.5 text-right font-mono font-bold text-purple-600 dark:text-purple-400">
                                        {{ number_format($item->fulfilled_quantity, 0, ',', '.') }}
                                    </td>
                                @endif
                                <td class="p-2.5 text-slate-500 text-[11px]">
                                    {{ $item->notes ?: '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ACTION PERSETUJUAN OLEH PUSAT / BACK-OFFICE -->
            @if(in_array($transferRequest->status, ['REQUESTED', 'PARTIAL']))
                <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" name="generate_stock_transfer" value="1" checked class="rounded text-blue-600 w-4 h-4">
                            <span>Otomatis buat Surat Jalan Pengiriman (StockTransfer) sekarang</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="openRejectModal()"
                                class="px-4 py-2 border border-rose-300 dark:border-rose-800 hover:bg-rose-50 text-rose-600 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                            Tolak Permintaan
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-emerald-500/25 cursor-pointer flex items-center gap-1.5">
                            <span>✅</span>
                            <span>Setujui Permintaan Cabang</span>
                        </button>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- MODAL TOLAK PERMINTAAN -->
<div id="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl shadow-2xl max-w-md w-full p-5 animate-in zoom-in-95 duration-150">
        <h3 class="text-sm font-black text-rose-600 flex items-center gap-1.5 mb-2">
            <span>❌</span> Tolak Permintaan Transfer Cabang
        </h3>
        <p class="text-xs text-slate-500 mb-3">
            Berikan alasan penolakan agar kepala toko cabang dapat memahami kendala pasokan barang.
        </p>

        <form action="{{ route('warehouse.requests.reject', $transferRequest->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block font-bold text-slate-700 dark:text-slate-300 text-xs mb-1">
                    Alasan Penolakan: <span class="text-rose-500">*</span>
                </label>
                <textarea name="rejection_reason" rows="3" required placeholder="Contoh: Stok di gudang pusat sedang kosong dari pabrik..."
                          class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-xs font-medium focus:ring-2 focus:ring-rose-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeRejectModal()" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-xs cursor-pointer">
                    Konfirmasi Tolak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
@endsection
