@extends('layouts.app')

@section('title', 'Detail Transaksi Perakitan Paket')
@section('tab_title', 'Detail Perakitan')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.assemblies.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Perakitan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Detail {{ $assembly->assembly_number }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                @if($assembly->type === 'ASSEMBLY')
                    <span class="text-emerald-500">⚡</span>
                    <span>Transaksi Perakitan Paket (Assembly)</span>
                @else
                    <span class="text-amber-500">🔨</span>
                    <span>Transaksi Pembongkaran Paket (Disassembly)</span>
                @endif
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.assemblies.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali ke Daftar
            </a>

            @if($assembly->type === 'ASSEMBLY')
                <button type="button"
                        onclick="document.getElementById('disassembleModal').classList.remove('hidden')"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-amber-500/25 flex items-center gap-1.5 cursor-pointer transition-all">
                    <span>🔨 Bongkar Kembali Paket</span>
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            ✕ {{ $errors->first() }}
        </div>
    @endif

    <!-- RINGKASAN TRANSAKSI -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Nomor Transaksi</div>
            <div class="text-base font-black font-mono text-slate-900 dark:text-white mt-1">{{ $assembly->assembly_number }}</div>
            <div class="mt-2">
                @if($assembly->type === 'ASSEMBLY')
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                        ⚡ PERAKITAN BARANG JADI
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                        🔨 PEMBONGKARAN PAKET
                    </span>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Toko & Tanggal</div>
            <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">{{ $assembly->store->name ?? '-' }}</div>
            <div class="text-xs text-slate-500 mt-0.5">
                {{ \Carbon\Carbon::parse($assembly->assembly_date)->format('d F Y') }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Produk Paket (Parent)</div>
            <div class="text-sm font-bold text-slate-900 dark:text-white mt-1">{{ $assembly->parentProduct->name ?? '-' }}</div>
            <div class="text-xs font-black text-emerald-600 dark:text-emerald-400 mt-0.5">
                {{ number_format($assembly->quantity, 2) }} {{ $assembly->parentUnit->unit_name ?? '' }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Total Nilai HPP</div>
            <div class="text-base font-black font-mono text-slate-900 dark:text-white mt-1">
                @can('view-hpp')
                    Rp {{ number_format($assembly->total_cost, 0, ',', '.') }}
                @else
                    <span class="text-slate-400 italic text-xs">*** Tersembunyi ***</span>
                @endcan
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Operator: <b>{{ $assembly->creator->name ?? '-' }}</b>
            </div>
        </div>
    </div>

    @if($assembly->notes)
        <div class="p-3 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-700 dark:text-slate-300">
            <b>Catatan:</b> {{ $assembly->notes }}
        </div>
    @endif

    <!-- RINCIAN KOMPONEN BAHAN BAKU -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
        <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">
            Rincian Komponen Bahan Baku ({{ $assembly->items->count() }} Item)
        </h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-extrabold uppercase tracking-wider">
                    <tr>
                        <th class="p-2.5 w-8">#</th>
                        <th class="p-2.5">Produk Komponen</th>
                        <th class="p-2.5">Satuan</th>
                        <th class="p-2.5 text-right">Jumlah Qty</th>
                        <th class="p-2.5 text-right">Harga Modal Satuan</th>
                        <th class="p-2.5 text-right">Subtotal HPP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @foreach($assembly->items as $index => $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="p-2.5 text-slate-400 font-bold">{{ $index + 1 }}</td>
                            <td class="p-2.5">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $item->componentProduct->name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $item->componentProduct->sku ?? '' }}</div>
                            </td>
                            <td class="p-2.5 text-slate-700 dark:text-slate-300">
                                {{ $item->componentUnit->unit_name ?? '-' }}
                            </td>
                            <td class="p-2.5 text-right font-black text-slate-800 dark:text-slate-200">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="p-2.5 text-right font-mono text-slate-700 dark:text-slate-300">
                                @can('view-hpp')
                                    Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic">***</span>
                                @endcan
                            </td>
                            <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                @can('view-hpp')
                                    Rp {{ number_format($item->total_cost, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic">***</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                @can('view-hpp')
                    <tfoot class="border-t border-slate-200 dark:border-slate-800 font-black">
                        <tr>
                            <td colspan="5" class="p-2.5 text-right uppercase text-slate-500">Total HPP Perakitan:</td>
                            <td class="p-2.5 text-right font-mono text-emerald-600 dark:text-emerald-400 text-sm">
                                Rp {{ number_format($assembly->total_cost, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endcan
            </table>
        </div>
    </div>

</div>

<!-- MODAL KONFIRMASI BONGKAR (DISASSEMBLE) -->
@if($assembly->type === 'ASSEMBLY')
<div id="disassembleModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-amber-100 dark:bg-amber-950/60 text-amber-600 rounded-xl text-lg font-black">
                🔨
            </div>
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Bongkar Paket Ini?</h3>
                <p class="text-xs text-slate-500">Stok paket jadi akan dipotong dan komponen bahan baku akan dikembalikan ke gudang.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('warehouse.assemblies.disassemble', $assembly->id) }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan / Catatan Pembongkaran</label>
                <textarea name="notes" rows="2" required placeholder="Contoh: Pembatalan pesanan hampers, retur komponen..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium focus:outline-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button"
                        onclick="document.getElementById('disassembleModal').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-black text-xs rounded-xl shadow-md shadow-amber-500/25 cursor-pointer">
                    Ya, Bongkar Sekarang
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
