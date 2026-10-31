@extends('layouts.app')

@section('title', 'Manajemen Promosi & Diskon')
@section('tab_title', 'Promosi POS')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Penjualan &amp; Kasir</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Promosi</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🏷️ Promo Diskon, Beli 1 Gratis 1 &amp; Tebus Murah (PWP)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('promotions.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>➕ Tambah Promo Baru</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif

    <!-- TABEL PROMOSI -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Kode &amp; Nama Promo</th>
                        <th class="py-3 px-4">Tipe Promo</th>
                        <th class="py-3 px-4">Mekanisme / Syarat</th>
                        <th class="py-3 px-4">Reward / Diskon</th>
                        <th class="py-3 px-4">Periode Berlaku</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($promotions as $promo)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-extrabold text-slate-900 dark:text-white">{{ $promo->name }}</div>
                                <div class="font-mono text-[11px] text-blue-600 dark:text-blue-400 font-bold tracking-wider">{{ $promo->code }}</div>
                                @if($promo->description)
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $promo->description }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($promo->type === 'BUY_X_GET_Y')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        BELI X GRATIS Y
                                    </span>
                                @elseif($promo->type === 'TEBUS_MURAH')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        TEBUS MURAH (PWP)
                                    </span>
                                @elseif($promo->type === 'DISCOUNT_PERCENT')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">
                                        DISKON PERSEN
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        DISKON NOMINAL
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($promo->type === 'BUY_X_GET_Y')
                                    <div>Beli: <b>{{ (float)$promo->buy_quantity }}x</b> {{ $promo->buyProduct?->name }}</div>
                                @elseif($promo->type === 'TEBUS_MURAH')
                                    <div>Min. Belanja: <b>Rp {{ number_format($promo->min_spend, 0, ',', '.') }}</b></div>
                                @else
                                    <div>Min. Belanja: <b>Rp {{ number_format($promo->min_spend, 0, ',', '.') }}</b></div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($promo->type === 'BUY_X_GET_Y')
                                    <div class="text-emerald-600 dark:text-emerald-400 font-bold">Gratis {{ (float)$promo->reward_quantity }}x {{ $promo->rewardProduct?->name }}</div>
                                @elseif($promo->type === 'TEBUS_MURAH')
                                    <div class="text-amber-600 dark:text-amber-400 font-bold">{{ $promo->rewardProduct?->name }} seharga Rp {{ number_format($promo->special_price, 0, ',', '.') }}</div>
                                @elseif($promo->type === 'DISCOUNT_PERCENT')
                                    <div class="text-purple-600 dark:text-purple-400 font-bold">Diskon {{ $promo->discount_percent }}%</div>
                                @else
                                    <div class="text-emerald-600 dark:text-emerald-400 font-bold">Potongan Rp {{ number_format($promo->discount_nominal, 0, ',', '.') }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="text-[11px]">
                                    @if($promo->start_date && $promo->end_date)
                                        {{ $promo->start_date->format('d/m/Y') }} s/d {{ $promo->end_date->format('d/m/Y') }}
                                    @elseif($promo->start_date)
                                        Mulai {{ $promo->start_date->format('d/m/Y') }}
                                    @else
                                        <span class="text-slate-400">Selalu Berlaku</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form action="{{ route('promotions.toggle', $promo->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-black cursor-pointer transition-all {{ $promo->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                        {{ $promo->is_active ? '● AKTIF' : '○ NONAKTIF' }}
                                    </button>
                                </form>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form action="{{ route('promotions.destroy', $promo->id) }}" method="POST" onsubmit="return confirm('Hapus promo ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-rose-600 hover:text-rose-700 font-bold hover:underline cursor-pointer">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 font-semibold">
                                Belum ada konfigurasi promosi. Klik "Tambah Promo Baru" di atas untuk memulai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($promotions->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
