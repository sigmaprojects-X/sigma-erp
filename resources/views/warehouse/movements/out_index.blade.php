@extends('layouts.app')

@section('title', 'Daftar Item Keluar (Stock Out)')
@section('tab_title', 'Item Keluar')

@section('content')
<div class="space-y-5 p-4 max-w-7xl mx-auto w-full">

    <!-- HEADER & BUTTONS -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Manajemen Gudang & Persediaan</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Item Keluar (Non-Jual)</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📤 Riwayat Item Keluar (Stock Out)</span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Link ke Item Masuk -->
            <a href="{{ route('warehouse.movements.in.index', ['store_id' => $selectedStoreId]) }}"
               class="px-3.5 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>📥 Ke Item Masuk (Stock In)</span>
            </a>

            <!-- Tombol Tambah Item Keluar Baru -->
            <a href="{{ route('warehouse.movements.out.create', ['store_id' => $selectedStoreId]) }}"
               class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>+ Catat Item Keluar Baru</span>
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
        <form method="GET" action="{{ route('warehouse.movements.out.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs">
            <!-- Filter Toko -->
            <div class="md:col-span-5">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Pilih Cabang / Gudang</label>
                <select name="store_id" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs focus:ring-2 focus:ring-rose-500">
                    <option value="">Semua Cabang / Gudang</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $selectedStoreId === $s->id ? 'selected' : '' }}>
                            🏬 {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Kategori / Alasan Keluar -->
            <div class="md:col-span-5">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Kategori Pengeluaran</label>
                <select name="category_reason" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-rose-500">
                    <option value="">Semua Kategori Pengeluaran</option>
                    <option value="INTERNAL_USE" {{ request('category_reason') === 'INTERNAL_USE' ? 'selected' : '' }}>🏢 Pemakaian Operasional Toko (ATK/Kresek/Pembersih)</option>
                    <option value="EMPLOYEE_CONSUMPTION" {{ request('category_reason') === 'EMPLOYEE_CONSUMPTION' ? 'selected' : '' }}>☕ Konsumsi Staf / Dapur Toko</option>
                    <option value="TESTER_SAMPLE" {{ request('category_reason') === 'TESTER_SAMPLE' ? 'selected' : '' }}>🔍 Tester / Sampling Konsumen</option>
                    <option value="PROMOTION_GIVEAWAY" {{ request('category_reason') === 'PROMOTION_GIVEAWAY' ? 'selected' : '' }}>🎉 Hadiah / Promosi Toko (Non-Kasir)</option>
                    <option value="SPOILAGE_WASTE" {{ request('category_reason') === 'SPOILAGE_WASTE' ? 'selected' : '' }}>🗑️ Basi / Pecah Dimusnahkan Langsung</option>
                    <option value="OTHER" {{ request('category_reason') === 'OTHER' ? 'selected' : '' }}>📝 Lain-lain</option>
                </select>
            </div>

            <!-- Reset Filter -->
            <div class="md:col-span-2 flex items-end">
                @if($selectedStoreId || request('category_reason'))
                    <a href="{{ route('warehouse.movements.out.index') }}"
                       class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-center rounded-xl transition-colors">
                        Reset Filter
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR ITEM KELUAR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-36">No. Transaksi</th>
                        <th class="p-3 w-28">Tanggal</th>
                        <th class="p-3 w-36">Toko / Lokasi</th>
                        <th class="p-3 w-44">Kategori Alasan</th>
                        <th class="p-3 w-40">Untuk / Penerima</th>
                        <th class="p-3 min-w-[220px]">Rincian Barang Keluar</th>
                        @if(auth()->user()->canViewHpp())
                            <th class="p-3 w-28 text-right">Total Nilai</th>
                        @endif
                        <th class="p-3 w-36">Petugas / Operator</th>
                        <th class="p-3 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-700 dark:text-slate-300">
                    @forelse($movements as $m)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <!-- Nomor -->
                            <td class="p-3 font-mono font-bold text-rose-600 dark:text-rose-400">
                                {{ $m->movement_number }}
                            </td>

                            <!-- Tanggal -->
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($m->movement_date)->format('d/m/Y') }}
                            </td>

                            <!-- Cabang -->
                            <td class="p-3 font-semibold text-slate-800 dark:text-slate-200">
                                {{ $m->store?->name ?? '-' }}
                            </td>

                            <!-- Kategori -->
                            <td class="p-3">
                                @if($m->category_reason === 'INTERNAL_USE')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 text-[10px] font-bold">
                                        🏢 Operasional Toko
                                    </span>
                                @elseif($m->category_reason === 'EMPLOYEE_CONSUMPTION')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 text-[10px] font-bold">
                                        ☕ Konsumsi Staf
                                    </span>
                                @elseif($m->category_reason === 'TESTER_SAMPLE')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 text-[10px] font-bold">
                                        🔍 Tester Konsumen
                                    </span>
                                @elseif($m->category_reason === 'PROMOTION_GIVEAWAY')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 text-[10px] font-bold">
                                        🎉 Hadiah Promosi
                                    </span>
                                @elseif($m->category_reason === 'SPOILAGE_WASTE')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 text-[10px] font-bold">
                                        🗑️ Pemusnahan Langsung
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 dark:bg-slate-800 text-[10px] font-bold">
                                        📝 {{ $m->category_reason }}
                                    </span>
                                @endif
                            </td>

                            <!-- Untuk / Penerima -->
                            <td class="p-3 text-slate-700 dark:text-slate-300">
                                {{ $m->recipient_or_source ?: '-' }}
                            </td>

                            <!-- Rincian Barang -->
                            <td class="p-3">
                                <div class="space-y-1">
                                    @foreach($m->items as $it)
                                        <div class="flex items-center justify-between text-[11px] gap-2 border-b border-dashed border-slate-200 dark:border-slate-800 pb-1">
                                            <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[220px]">
                                                {{ $it->product?->name ?? 'Produk' }}
                                            </span>
                                            <span class="font-mono font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                                -{{ (float) $it->quantity }} {{ $it->unit_code }}
                                            </span>
                                        </div>
                                    @endforeach
                                    @if($m->notes)
                                        <div class="text-[10px] text-slate-400 italic mt-0.5">
                                            Catatan: {{ $m->notes }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Total Nilai HPP -->
                            @if(auth()->user()->canViewHpp())
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format((float) $m->total_cost_value, 0, ',', '.') }}
                                </td>
                            @endif

                            <!-- Petugas / Operator -->
                            <td class="p-3">
                                @if($m->creator)
                                    <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                        <span>{{ $m->creator->name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 capitalize pl-3">{{ $m->creator->role }}</div>
                                @else
                                    <span class="text-slate-400 italic">Sistem</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('warehouse.movements.show', $m->id) }}"
                                       class="px-2 py-1 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-100 font-bold text-[11px] transition-colors"
                                       title="Lihat Rincian">
                                        Detail
                                    </a>
                                    <a href="{{ route('warehouse.movements.print', $m->id) }}" target="_blank"
                                       class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 font-bold text-[11px] transition-colors"
                                       title="Cetak Bukti Transaksi">
                                        🖨️ Cetak
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canViewHpp() ? 9 : 8 }}" class="p-8 text-center text-slate-400 font-semibold">
                                Belum ada transaksi item keluar yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
