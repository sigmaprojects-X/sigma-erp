@extends('layouts.app')

@section('title', 'Riwayat Penyesuaian Stok')
@section('tab_title', 'Penyesuaian Stok')

@section('content')
<div class="space-y-5 p-4 max-w-7xl mx-auto w-full">

    <!-- HEADER & BUTTONS -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Manajemen Gudang & Persediaan</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Penyesuaian Stok (Adjustment)</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 Riwayat Penyesuaian Stok & Barang Rusak</span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Link ke Dashboard Karantina Barang Rusak -->
            <a href="{{ route('warehouse.adjustments.quarantine', ['store_id' => $selectedStoreId]) }}"
               class="px-3.5 py-2 bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>🛡️ Stok Karantina (Barang Rusak)</span>
            </a>

            <!-- Tombol Tambah Penyesuaian Baru -->
            <a href="{{ route('warehouse.adjustments.create', ['store_id' => $selectedStoreId]) }}"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>+ Buat Penyesuaian Baru</span>
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-black">✕</button>
        </div>
    @endif

    <!-- FILTER FORM -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('warehouse.adjustments.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
            <!-- Filter Toko -->
            <div class="md:col-span-5">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Pilih Cabang / Gudang</label>
                <select name="store_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Cabang / Gudang</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $selectedStoreId === $s->id ? 'selected' : '' }}>
                            🏬 {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Jenis Penyesuaian -->
            <div class="md:col-span-5">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Tipe / Alasan Penyesuaian</label>
                <select name="type" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Tipe Penyesuaian</option>
                    <option value="DAMAGE_WASTE" {{ request('type') === 'DAMAGE_WASTE' ? 'selected' : '' }}>🛡️ Barang Rusak / Kadaluarsa (Masuk Karantina)</option>
                    <option value="INTERNAL_USE" {{ request('type') === 'INTERNAL_USE' ? 'selected' : '' }}>🏢 Pemakaian Sendiri / Operasional Toko</option>
                    <option value="STOCK_IN_CORRECTION" {{ request('type') === 'STOCK_IN_CORRECTION' ? 'selected' : '' }}>➕ Koreksi Stok Masuk (Penambahan)</option>
                    <option value="STOCK_OUT_CORRECTION" {{ request('type') === 'STOCK_OUT_CORRECTION' ? 'selected' : '' }}>➖ Koreksi Stok Keluar (Pengurangan)</option>
                </select>
            </div>

            <!-- Reset Filter -->
            <div class="md:col-span-2 flex items-end">
                @if($selectedStoreId || request('type'))
                    <a href="{{ route('warehouse.adjustments.index') }}"
                       class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-center rounded-xl transition-colors">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL RIWAYAT PENYESUAIAN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-36">No. Penyesuaian</th>
                        <th class="p-3 w-28">Tanggal</th>
                        <th class="p-3 w-36">Toko / Lokasi</th>
                        <th class="p-3 w-48">Tipe Transaksi</th>
                        <th class="p-3 min-w-[220px]">Rincian Barang & Alasan</th>
                        <th class="p-3 w-28 text-right">Total Nilai</th>
                        <th class="p-3 w-32">Petugas / Operator</th>
                        <th class="p-3 w-24 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-700 dark:text-slate-300">
                    @forelse($adjustments as $adj)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- Nomor -->
                            <td class="p-3 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $adj->adjustment_number }}
                            </td>

                            <!-- Tanggal -->
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($adj->adjustment_date)->format('d/m/Y') }}
                            </td>

                            <!-- Cabang -->
                            <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $adj->store?->name ?? '-' }}
                            </td>

                            <!-- Tipe -->
                            <td class="p-3">
                                @if($adj->adjustment_type === 'DAMAGE_WASTE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 text-[10px] font-bold">
                                        🛡️ Rusak (Karantina)
                                    </span>
                                @elseif($adj->adjustment_type === 'INTERNAL_USE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 text-[10px] font-bold">
                                        🏢 Pemakaian Sendiri
                                    </span>
                                @elseif($adj->adjustment_type === 'STOCK_IN_CORRECTION')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[10px] font-bold">
                                        ➕ Koreksi Masuk
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 text-[10px] font-bold">
                                        ➖ Koreksi Keluar
                                    </span>
                                @endif
                            </td>

                            <!-- Rincian Barang -->
                            <td class="p-3">
                                <div class="space-y-1">
                                    @foreach($adj->items as $it)
                                        <div class="flex items-center justify-between text-[11px] gap-2 border-b border-dashed border-slate-200 dark:border-slate-800 pb-1">
                                            <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[240px]">
                                                {{ $it->product?->name ?? 'Produk' }}
                                            </span>
                                            <span class="font-mono font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                                {{ (float) $it->quantity }} {{ $it->unit_code }}
                                            </span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 italic">
                                            Alasan: {{ $it->reason }}
                                        </div>
                                    @endforeach
                                    @if($adj->notes)
                                        <div class="text-[10px] text-slate-500 font-medium mt-1">
                                            Catatan: {{ $adj->notes }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Total Nilai -->
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                @if(auth()->user()->canViewHpp())
                                    Rp {{ number_format((float) $adj->total_cost_value, 0, ',', '.') }}
                                @else
                                    ***
                                @endif
                            </td>

                            <!-- Operator / Petugas -->
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $adj->creator?->name ?? 'Sistem' }}</div>
                                <div class="text-[10px] text-slate-400 capitalize">{{ $adj->creator?->role ?? '-' }}</div>
                            </td>

                            <!-- Status -->
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[10px] font-bold">
                                    {{ $adj->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400 font-semibold">
                                Belum ada transaksi penyesuaian stok yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($adjustments->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
