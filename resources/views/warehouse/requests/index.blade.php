@extends('layouts.app')

@section('title', 'Permintaan Transfer Cabang')
@section('tab_title', 'Permintaan Cabang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.transfers.index') }}" class="hover:text-blue-600 font-semibold">Persediaan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Permintaan Transfer Cabang</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 Permintaan Transfer Antar Cabang (Indent)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Pengajuan pasokan stok dari toko cabang ke gudang pusat dengan dukungan Smart Restock otomatis.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.transfers.index') }}"
               class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                🚚 Surat Jalan Transfer
            </a>
            <a href="{{ route('warehouse.requests.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-blue-500/25 flex items-center gap-1.5 cursor-pointer">
                <span>+ Buat Permintaan [F2]</span>
            </a>
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

    <!-- FILTER BAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
        <form action="{{ route('warehouse.requests.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Status Permintaan:</label>
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs">
                    <option value="">Semua Status</option>
                    <option value="REQUESTED" {{ request('status') === 'REQUESTED' ? 'selected' : '' }}>⏳ Menunggu Persetujuan</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>✅ Disetujui Penuh</option>
                    <option value="PARTIAL" {{ request('status') === 'PARTIAL' ? 'selected' : '' }}>⚡ Disetujui Sebagian</option>
                    <option value="FULFILLED" {{ request('status') === 'FULFILLED' ? 'selected' : '' }}>📦 Selesai Diterima</option>
                    <option value="REJECTED" {{ request('status') === 'REJECTED' ? 'selected' : '' }}>❌ Ditolak</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Cabang Peminta:</label>
                <select name="requesting_store_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs">
                    <option value="">Semua Cabang</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ request('requesting_store_id') === $s->id ? 'selected' : '' }}>
                            🏬 {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tujuan Permintaan:</label>
                <select name="destination_store_id" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs">
                    <option value="">Semua Tujuan</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ request('destination_store_id') === $s->id ? 'selected' : '' }}>
                            🏢 {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <a href="{{ route('warehouse.requests.index') }}" class="w-full py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl text-center transition-colors">
                    Reset Filter
                </a>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR PERMINTAAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-36">No. Permintaan</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Cabang Peminta</th>
                        <th class="p-3">Tujuan (Pusat)</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center">SKU / Item</th>
                        <th class="p-3 text-right">Qty Diminta</th>
                        <th class="p-3 text-right">Qty Disetujui</th>
                        <th class="p-3">Surat Jalan Transfer</th>
                        <th class="p-3 text-center w-20">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($requests as $req)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('warehouse.requests.show', $req->id) }}" class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $req->request_number }}
                                </a>
                                <div class="text-[10px] text-slate-400">Oleh: {{ $req->creator?->name ?? 'Sistem' }}</div>
                            </td>
                            <td class="p-3 whitespace-nowrap text-slate-700 dark:text-slate-300">
                                {{ date('d/m/Y', strtotime($req->request_date)) }}
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">🏬 {{ $req->requestingStore->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $req->requestingStore->code }}</div>
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-700 dark:text-slate-300">🏢 {{ $req->destinationStore->name }}</div>
                            </td>
                            <td class="p-3 text-center">
                                @if($req->status === 'REQUESTED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200">
                                        ⏳ MENUNGGU
                                    </span>
                                @elseif($req->status === 'APPROVED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200">
                                        ✅ DISETUJUI
                                    </span>
                                @elseif($req->status === 'PARTIAL')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 border border-blue-200">
                                        ⚡ SEBAGIAN
                                    </span>
                                @elseif($req->status === 'FULFILLED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 border border-purple-200">
                                        📦 SELESAI
                                    </span>
                                @elseif($req->status === 'REJECTED')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 border border-rose-200">
                                        ❌ DITOLAK
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $req->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center font-bold">
                                {{ $req->total_items }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ number_format($req->total_requested_quantity, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold {{ $req->total_approved_quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                {{ number_format($req->total_approved_quantity, 0, ',', '.') }}
                            </td>
                            <td class="p-3">
                                @if($req->stockTransfer)
                                    <a href="{{ route('warehouse.transfers.show', $req->stockTransfer->id) }}" class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-indigo-600 hover:underline">
                                        <span>🚚 {{ $req->stockTransfer->transfer_number }}</span>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-[11px]">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <a href="{{ route('warehouse.requests.show', $req->id) }}" class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 hover:bg-blue-600 hover:text-white text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-1">📋</div>
                                <div class="font-bold text-slate-600 dark:text-slate-300">Belum Ada Permintaan Transfer</div>
                                <div class="text-xs">Klik tombol <strong>+ Buat Permintaan</strong> untuk mengajukan pasokan barang.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
