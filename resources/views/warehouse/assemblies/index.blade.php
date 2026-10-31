@extends('layouts.app')

@section('title', 'Perakitan & Paket Bundling (Assembly)')
@section('tab_title', 'Perakitan Paket')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Logistik & Gudang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Perakitan & Paket Bundling</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📦 Perakitan & Paket Bundling (BOM)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.assemblies.boms') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <span>📋 Kelola Resep BOM</span>
            </a>
            <a href="{{ route('warehouse.assemblies.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-500/25 cursor-pointer">
                <span>➕ Rakit Paket Baru</span>
            </a>
        </div>
    </div>

    <!-- FILTER & SEARCH -->
    <form method="GET" action="{{ route('warehouse.assemblies.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari No. Perakitan, nama produk paket, atau SKU..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500">
        </div>

        <div class="w-40">
            <select name="type" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Tipe</option>
                <option value="ASSEMBLY" {{ request('type') === 'ASSEMBLY' ? 'selected' : '' }}>ASSEMBLY (Perakitan)</option>
                <option value="DISASSEMBLY" {{ request('type') === 'DISASSEMBLY' ? 'selected' : '' }}>DISASSEMBLY (Pembongkaran)</option>
            </select>
        </div>

        <div class="w-48">
            <select name="store_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                <option value="">Semua Toko / Gudang</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ request('store_id') === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>
        @if(request()->anyFilled(['search', 'type', 'store_id']))
            <a href="{{ route('warehouse.assemblies.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 font-semibold text-xs">
                Reset
            </a>
        @endif
    </form>

    <!-- NOTIFIKASI -->
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

    <!-- TABLE RIWAYAT PERAKITAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold">
                    <tr>
                        <th class="p-3">No. Transaksi</th>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">Tipe</th>
                        <th class="p-3">Toko / Gudang</th>
                        <th class="p-3">Produk Paket (Parent)</th>
                        <th class="p-3 text-right">Jumlah</th>
                        <th class="p-3 text-right">Total Nilai HPP</th>
                        <th class="p-3">Operator</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($assemblies as $assembly)
                        <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('warehouse.assemblies.show', $assembly->id) }}" class="text-blue-600 hover:underline">
                                    {{ $assembly->assembly_number }}
                                </a>
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($assembly->assembly_date)->format('d/m/Y') }}
                            </td>
                            <td class="p-3">
                                @if($assembly->type === 'ASSEMBLY')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        ⚡ RAKIT (ASSEMBLY)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                        🔨 BONGKAR (DISASSEMBLY)
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                {{ $assembly->store->name ?? '-' }}
                            </td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $assembly->parentProduct->name ?? '-' }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $assembly->parentProduct->sku ?? '' }}</div>
                            </td>
                            <td class="p-3 text-right font-black text-slate-800 dark:text-slate-200">
                                {{ number_format($assembly->quantity, 2) }} {{ $assembly->parentUnit->unit_name ?? '' }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                @can('view-hpp')
                                    Rp {{ number_format($assembly->total_cost, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic">*** Tersembunyi ***</span>
                                @endcan
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-300">
                                {{ $assembly->creator->name ?? '-' }}
                            </td>
                            <td class="p-3 text-center">
                                <a href="{{ route('warehouse.assemblies.show', $assembly->id) }}"
                                   class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold rounded-lg text-xs transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                Belum ada transaksi perakitan atau pembongkaran paket.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assemblies->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $assemblies->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
