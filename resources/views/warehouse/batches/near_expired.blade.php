@extends('layouts.app')

@section('title', 'Peringatan Produk Mendekati Kedaluwarsa (Near-Expired Alert)')
@section('tab_title', 'Near-Expired Alert')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.batches.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Pelacakan Batch</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Near-Expired Alert</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⚠️ Peringatan Dini Produk Mendekati Kedaluwarsa</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.batches.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali ke Daftar Batch
            </a>
        </div>
    </div>

    <!-- FILTER PERIODE HARI & TOKO -->
    <form method="GET" action="{{ route('warehouse.batches.near-expired') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="w-56">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">Ambang Batas Peringatan:</label>
            <select name="days" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs">
                <option value="15" {{ $days == 15 ? 'selected' : '' }}>H-15 Hari Mendatang</option>
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>H-30 Hari Mendatang (1 Bulan)</option>
                <option value="60" {{ $days == 60 ? 'selected' : '' }}>H-60 Hari Mendatang (2 Bulan)</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>H-90 Hari Mendatang (3 Bulan)</option>
            </select>
        </div>

        <div class="w-56">
            <label class="block text-[11px] font-bold text-slate-500 mb-1">Pilih Toko / Cabang:</label>
            <select name="store_id" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs">
                <option value="">Semua Toko</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="pt-4">
            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
                Terapkan Filter
            </button>
        </div>
    </form>

    <!-- CARDS RINGKASAN RISIKO -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                        ⚡ Mendekati Expired (&lt;= {{ $days }} Hari)
                    </div>
                    <div class="text-2xl font-black text-amber-950 dark:text-amber-200 mt-1">
                        {{ $nearExpiredBatches->count() }} Batch Lot
                    </div>
                </div>
                <div class="p-3 bg-amber-200/50 dark:bg-amber-900/50 rounded-2xl text-2xl">
                    ⏳
                </div>
            </div>
            <p class="text-xs text-amber-800/80 dark:text-amber-300/80 mt-2">
                Rekomendasi: Lakukan diskon cuci gudang / promo bundling pada produk-produk ini agar stok cepat habis sebelum kedaluwarsa.
            </p>
        </div>

        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">
                        ⛔ Sudah Kedaluwarsa (Expired)
                    </div>
                    <div class="text-2xl font-black text-rose-950 dark:text-rose-200 mt-1">
                        {{ $alreadyExpiredBatches->count() }} Batch Lot
                    </div>
                </div>
                <div class="p-3 bg-rose-200/50 dark:bg-rose-900/50 rounded-2xl text-2xl">
                    🚫
                </div>
            </div>
            <p class="text-xs text-rose-800/80 dark:text-rose-300/80 mt-2">
                Peringatan: Batch ini telah otomatis dikunci dari kasir POS untuk melindungi konsumen. Lakukan proses retur supplier atau karantina pemusnahan.
            </p>
        </div>
    </div>

    <!-- TABEL 1: DAFTAR MENDEKATI KEDALUWARSA -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">
            Daftar Batch Mendekati Kedaluwarsa (Prioritas Obral / Clearance)
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-500 font-extrabold uppercase tracking-wider">
                    <tr>
                        <th class="p-2.5">No. Batch</th>
                        <th class="p-2.5">Produk</th>
                        <th class="p-2.5">Toko</th>
                        <th class="p-2.5">Tanggal Expired</th>
                        <th class="p-2.5 text-center">Sisa Hari</th>
                        <th class="p-2.5 text-right">Sisa Stok</th>
                        <th class="p-2.5 text-right">Potensi Nilai HPP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($nearExpiredBatches as $b)
                        @php
                            $daysRemaining = $b->days_remaining;
                            $totalVal = (float)$b->current_quantity * (float)$b->unit_cost;
                        @endphp
                        <tr class="hover:bg-amber-50/40 dark:hover:bg-amber-950/20">
                            <td class="p-2.5 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $b->batch_number }}
                            </td>
                            <td class="p-2.5">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $b->product->name ?? '-' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $b->product->sku ?? '' }}</div>
                            </td>
                            <td class="p-2.5 text-slate-700 dark:text-slate-300">
                                {{ $b->store->name ?? '-' }}
                            </td>
                            <td class="p-2.5 font-bold text-amber-700 dark:text-amber-400">
                                {{ \Carbon\Carbon::parse($b->expired_date)->format('d F Y') }}
                            </td>
                            <td class="p-2.5 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900">
                                    ⚡ H-{{ $daysRemaining }} hari
                                </span>
                            </td>
                            <td class="p-2.5 text-right font-mono font-black text-slate-900 dark:text-white">
                                {{ number_format($b->current_quantity, 2) }}
                            </td>
                            <td class="p-2.5 text-right font-mono text-slate-700 dark:text-slate-300">
                                @can('view-hpp')
                                    Rp {{ number_format($totalVal, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 italic">***</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400">
                                Tidak ada produk yang mendekati tanggal kedaluwarsa dalam rentang waktu ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 2: DAFTAR SUDAH KEDALUWARSA -->
    @if($alreadyExpiredBatches->count() > 0)
        <div class="bg-white dark:bg-slate-900 border border-rose-200 dark:border-rose-900/60 rounded-2xl p-4 shadow-xs space-y-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-rose-600">
                Daftar Batch Sudah Kedaluwarsa (Karantina / Retur Supplier)
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 font-extrabold uppercase tracking-wider">
                        <tr>
                            <th class="p-2.5">No. Batch</th>
                            <th class="p-2.5">Produk</th>
                            <th class="p-2.5">Toko</th>
                            <th class="p-2.5">Tanggal Expired</th>
                            <th class="p-2.5 text-right">Sisa Stok Kedaluwarsa</th>
                            <th class="p-2.5 text-right">Nilai Kerugian HPP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100 dark:divide-rose-900/40 font-medium">
                        @foreach($alreadyExpiredBatches as $b)
                            @php
                                $totalVal = (float)$b->current_quantity * (float)$b->unit_cost;
                            @endphp
                            <tr class="hover:bg-rose-50/50 dark:hover:bg-rose-950/30">
                                <td class="p-2.5 font-mono font-bold text-rose-700 dark:text-rose-400">
                                    {{ $b->batch_number }}
                                </td>
                                <td class="p-2.5">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $b->product->name ?? '-' }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ $b->product->sku ?? '' }}</div>
                                </td>
                                <td class="p-2.5 text-slate-700 dark:text-slate-300">
                                    {{ $b->store->name ?? '-' }}
                                </td>
                                <td class="p-2.5 font-bold text-rose-600">
                                    {{ \Carbon\Carbon::parse($b->expired_date)->format('d F Y') }}
                                </td>
                                <td class="p-2.5 text-right font-mono font-black text-rose-700 dark:text-rose-400">
                                    {{ number_format($b->current_quantity, 2) }}
                                </td>
                                <td class="p-2.5 text-right font-mono font-bold text-rose-600">
                                    @can('view-hpp')
                                        Rp {{ number_format($totalVal, 0, ',', '.') }}
                                    @else
                                        <span class="text-slate-400 italic">***</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
