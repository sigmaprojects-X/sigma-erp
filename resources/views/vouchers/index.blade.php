@extends('layouts.app')

@section('title', 'Voucher & Gift Cards')
@section('tab_title', 'Voucher Belanja')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Penjualan &amp; Kasir</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Voucher</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🎟️ Voucher Belanja &amp; Diskon (Kode Unik Anti-Fraud)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('vouchers.print-coupons') }}"
               target="_blank"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>🖨️ Cetak Lembaran Kupon</span>
            </a>
            <a href="{{ route('vouchers.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>➕ Generate Voucher Baru</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif

    <!-- TABEL VOUCHER -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider font-extrabold text-[10px] border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Kode Unik Voucher</th>
                        <th class="py-3 px-4">Judul Voucher</th>
                        <th class="py-3 px-4">Nilai Potongan</th>
                        <th class="py-3 px-4">Syarat Min. Belanja</th>
                        <th class="py-3 px-4">Berlaku s/d</th>
                        <th class="py-3 px-4 text-center">Tipe Cetak</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($vouchers as $vch)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-mono font-black text-blue-600 dark:text-blue-400 text-xs tracking-wider">{{ $vch->code }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $vch->title ?: 'Voucher Belanja' }}</div>
                            </td>
                            <td class="py-3 px-4 font-black">
                                @if($vch->type === 'VALUE')
                                    <span class="text-emerald-600 dark:text-emerald-400">Rp {{ number_format($vch->amount, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-purple-600 dark:text-purple-400">{{ (float)$vch->amount }}%</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($vch->min_spend > 0)
                                    Rp {{ number_format($vch->min_spend, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400">Tanpa Syarat</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[11px] text-slate-500">
                                {{ $vch->valid_until ? $vch->valid_until->format('d/m/Y') : 'Selamanya' }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($vch->is_printed_on_receipt)
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        🖨️ EKOR STRUK
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                        STANDALONE
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($vch->status === 'ISSUED')
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        ● AKTIF (ISSUED)
                                    </span>
                                @elseif($vch->status === 'REDEEMED')
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        ✓ SUDAH KLAIM
                                    </span>
                                @elseif($vch->status === 'TEMPLATE')
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                        📄 TEMPLATE STRUK
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-slate-100 text-slate-500">
                                        KADALUWARSA
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form action="{{ route('vouchers.destroy', $vch->id) }}" method="POST" onsubmit="return confirm('Hapus voucher ini?')">
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
                            <td colspan="8" class="py-8 text-center text-slate-400 font-semibold">
                                Belum ada voucher belanja. Klik "Generate Voucher Baru" di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
