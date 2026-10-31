@extends('layouts.app')

@section('title', 'Bukti Transaksi Kas ' . $transaction->transaction_number)

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Header Controls -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center space-x-2">
            <a href="{{ route('accounting.cash.index') }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>← Kembali ke Kas &amp; Bank</span>
            </a>
            <span class="text-xs text-slate-400">/</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ $transaction->transaction_number }}</span>
        </div>

        <div class="flex items-center space-x-2">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Bukti Slip</span>
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5 no-print">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Voucher Slip Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 shadow-sm printable-slip">
        <!-- Header Slip -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 dark:border-slate-100 pb-5 mb-6">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider">
                    {{ $company->name ?? 'SIGMA RETAIL & ERP' }}
                </h2>
                <div class="text-xs text-slate-500 mt-0.5">
                    Cabang: <strong class="text-slate-800 dark:text-slate-200">{{ $transaction->store?->name ?? 'Kantor Pusat / Holding' }}</strong>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-1 text-xs font-black uppercase tracking-widest rounded-lg mb-1 {{ $transaction->type === 'CASH_IN' ? 'bg-emerald-600 text-white' : ($transaction->type === 'CASH_OUT' ? 'bg-rose-600 text-white' : 'bg-blue-600 text-white') }}">
                    @if($transaction->type === 'CASH_IN')
                    BUKTI KAS MASUK (BKM)
                    @elseif($transaction->type === 'CASH_OUT')
                    BUKTI KAS KELUAR (BKK)
                    @else
                    BUKTI TRANSFER KAS (BTK)
                    @endif
                </div>
                <div class="text-sm font-mono font-bold text-slate-900 dark:text-white">
                    {{ $transaction->transaction_number }}
                </div>
                <div class="text-xs text-slate-500">
                    Tanggal: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $transaction->transaction_date ? \Carbon\Carbon::parse($transaction->transaction_date)->format('d F Y') : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Meta Detail Grid -->
        <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/60 p-5 rounded-2xl mb-6 text-xs">
            @if($transaction->type === 'CASH_IN')
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Masuk ke Kas / Bank:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->destinationAccount?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Kategori Pendapatan:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->categoryAccount?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Diterima Dari:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->contact_person ?: '-' }}</span>
            </div>
            @elseif($transaction->type === 'CASH_OUT')
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Sumber Kas / Bank:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->sourceAccount?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Kategori Beban:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->categoryAccount?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Dibayarkan Kepada:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->contact_person ?: '-' }}</span>
            </div>
            @else
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Dari Rekening:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->sourceAccount?->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Ke Rekening:</span>
                <span class="font-bold text-slate-900 dark:text-white">{{ $transaction->destinationAccount?->name }}</span>
            </div>
            @if($transaction->admin_fee > 0)
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Biaya Admin Bank:</span>
                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">Rp {{ number_format($transaction->admin_fee, 0, ',', '.') }}</span>
            </div>
            @endif
            @endif

            <div class="col-span-2">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Catatan / Keterangan:</span>
                <span class="font-medium text-slate-900 dark:text-white">{{ $transaction->notes ?: '-' }}</span>
            </div>
        </div>

        <!-- Nominal Kotak Besar -->
        <div class="p-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-center mb-6">
            <span class="text-xs uppercase tracking-wider font-bold text-slate-500">Jumlah Uang:</span>
            <div class="text-3xl font-black font-mono mt-1 {{ $transaction->type === 'CASH_IN' ? 'text-emerald-600 dark:text-emerald-400' : ($transaction->type === 'CASH_OUT' ? 'text-rose-600 dark:text-rose-400' : 'text-blue-600 dark:text-blue-400') }}">
                Rp {{ number_format($transaction->amount, 0, ',', '.') }}
            </div>
        </div>

        <!-- Kolom Tanda Tangan -->
        <div class="grid grid-cols-3 gap-6 pt-6 border-t border-slate-200 dark:border-slate-800 text-center text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase mb-16">Penyetor / Penerima:</span>
                <div class="border-t border-slate-400 mx-4 pt-1 font-bold text-slate-800 dark:text-slate-200">
                    ( {{ $transaction->contact_person ?: '............................' }} )
                </div>
            </div>

            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase mb-16">Kasir / Admin:</span>
                <div class="border-t border-slate-400 mx-4 pt-1 font-bold text-slate-800 dark:text-slate-200">
                    ( {{ $transaction->creator?->name ?? 'Kasir Toko' }} )
                </div>
            </div>

            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase mb-16">Disetujui Oleh:</span>
                <div class="border-t border-slate-400 mx-4 pt-1 font-bold text-slate-800 dark:text-slate-200">
                    ( Kepala Toko / Manajer )
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print, header, aside, nav {
        display: none !important;
    }
    body {
        background: white !important;
        color: black !important;
    }
    .printable-slip {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
@endsection
