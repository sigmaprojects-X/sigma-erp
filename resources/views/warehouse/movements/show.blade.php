@extends('layouts.app')

@section('title', 'Rincian Transaksi ' . $movement->movement_number)
@section('tab_title', 'Detail ' . $movement->movement_number)

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ $movement->movement_type === 'IN' ? route('warehouse.movements.in.index') : route('warehouse.movements.out.index') }}" class="hover:text-blue-600 font-semibold">
                    {{ $movement->movement_type === 'IN' ? 'Item Masuk' : 'Item Keluar' }}
                </a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">{{ $movement->movement_number }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>{{ $movement->movement_type === 'IN' ? '📥 Rincian Item Masuk' : '📤 Rincian Item Keluar' }}</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full {{ $movement->movement_type === 'IN' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' }} font-bold font-mono">
                    {{ $movement->movement_number }}
                </span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.movements.print', $movement->id) }}" target="_blank"
               class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>🖨️ Cetak Bukti</span>
            </a>
            <a href="{{ $movement->movement_type === 'IN' ? route('warehouse.movements.in.index') : route('warehouse.movements.out.index') }}"
               class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                Kembali
            </a>
        </div>
    </div>

    <!-- METADATA CARD -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Tanggal Transaksi</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                {{ \Carbon\Carbon::parse($movement->movement_date)->format('d F Y') }}
            </div>
        </div>
        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Cabang / Gudang</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                {{ $movement->store?->name }} ({{ $movement->store?->code }})
            </div>
        </div>
        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Kategori Alasan</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                {{ $movement->category_reason }}
            </div>
        </div>
        <div>
            <div class="text-slate-400 font-bold uppercase text-[10px]">Petugas / Operator</div>
            <div class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">
                {{ $movement->creator?->name ?? 'Sistem' }} ({{ $movement->creator?->role ?? '-' }})
            </div>
        </div>
        @if($movement->recipient_or_source)
            <div class="sm:col-span-2">
                <div class="text-slate-400 font-bold uppercase text-[10px]">{{ $movement->movement_type === 'IN' ? 'Sumber / Dari' : 'Penerima / Untuk' }}</div>
                <div class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">{{ $movement->recipient_or_source }}</div>
            </div>
        @endif
        @if($movement->notes)
            <div class="sm:col-span-2">
                <div class="text-slate-400 font-bold uppercase text-[10px]">Catatan</div>
                <div class="font-medium text-slate-800 dark:text-slate-200 mt-0.5">{{ $movement->notes }}</div>
            </div>
        @endif
    </div>

    <!-- TABEL RINCIAN ITEM -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 font-bold text-xs uppercase text-slate-400">
            Daftar Barang (Total {{ $movement->items->count() }} Item)
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-12 text-center">No</th>
                        <th class="p-3 w-36">SKU</th>
                        <th class="p-3 min-w-[200px]">Nama Produk</th>
                        <th class="p-3 w-28 text-center">Satuan</th>
                        <th class="p-3 w-32 text-right">Kuantitas</th>
                        @if(auth()->user()->canViewHpp())
                            <th class="p-3 w-32 text-right">HPP Satuan</th>
                            <th class="p-3 w-36 text-right">Subtotal Nilai</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-700 dark:text-slate-300">
                    @foreach($movement->items as $idx => $it)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="p-3 text-center text-slate-400">{{ $idx + 1 }}</td>
                            <td class="p-3 font-mono font-bold">{{ $it->product?->sku }}</td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white">{{ $it->product?->name }}</td>
                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-bold text-[11px]">
                                    {{ $it->unit_code }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-mono font-black {{ $movement->movement_type === 'IN' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $movement->movement_type === 'IN' ? '+' : '-' }}{{ (float) $it->quantity }}
                            </td>
                            @if(auth()->user()->canViewHpp())
                                <td class="p-3 text-right font-mono">
                                    Rp {{ number_format((float) $it->unit_cost, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format((float) $it->total_cost, 0, ',', '.') }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                @if(auth()->user()->canViewHpp())
                    <tfoot class="bg-slate-50 dark:bg-slate-800/80 font-bold border-t border-slate-200 dark:border-slate-800 text-xs">
                        <tr>
                            <td colspan="6" class="p-3 text-right text-slate-500 uppercase">Total Nilai Persediaan:</td>
                            <td class="p-3 text-right font-mono font-black text-slate-900 dark:text-white text-sm">
                                Rp {{ number_format((float) $movement->total_cost_value, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
