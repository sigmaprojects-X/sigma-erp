@extends('layouts.app')

@section('title', 'Voucher Jurnal ' . $journal->entry_number)

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header Controls -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center space-x-2">
            <a href="{{ route('accounting.journals.index') }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>← Kembali</span>
            </a>
            <span class="text-xs text-slate-400">/</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Voucher Jurnal: {{ $journal->entry_number }}</span>
        </div>

        <div class="flex items-center space-x-2">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-xs transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Voucher</span>
            </button>
            <a href="{{ route('accounting.journals.create') }}"
               class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-all">
                <span>+ Buat Jurnal Baru</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5 no-print">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Voucher Card -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-8 shadow-sm printable-voucher">
        <!-- Voucher Header -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 dark:border-slate-100 pb-5 mb-6">
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider">
                    {{ $company->name ?? 'SIGMA RETAIL & ERP' }}
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Sistem Informasi Akuntansi Terpadu</p>
                <div class="text-[11px] text-slate-600 dark:text-slate-400 mt-1">
                    Lokasi: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $journal->store?->name ?? 'Semua Toko / Holding HO' }}</span>
                </div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black uppercase tracking-widest rounded-lg mb-1">
                    BUKTI JURNAL UMUM
                </div>
                <div class="text-sm font-mono font-bold text-blue-600 dark:text-blue-400">
                    {{ $journal->entry_number }}
                </div>
                <div class="text-xs text-slate-500">
                    Tanggal: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $journal->entry_date ? \Carbon\Carbon::parse($journal->entry_date)->format('d F Y') : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="grid grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-800/60 p-4 rounded-2xl mb-6 text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">No. Referensi / Bukti:</span>
                <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $journal->reference_number ?: '-' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Tipe Transaksi:</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $journal->reference_type }}</span>
            </div>
            <div class="col-span-2">
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Keterangan / Memo:</span>
                <span class="font-medium text-slate-900 dark:text-white">{{ $journal->description }}</span>
            </div>
        </div>

        <!-- Detail Baris Jurnal -->
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-2.5 px-3 w-10 text-center">No</th>
                        <th class="py-2.5 px-3 w-36">Kode Akun</th>
                        <th class="py-2.5 px-3">Nama Akun & Uraian</th>
                        <th class="py-2.5 px-3 w-36 text-right">Debit (Rp)</th>
                        <th class="py-2.5 px-3 w-36 text-right">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800 font-medium">
                    @foreach($journal->lines as $idx => $line)
                    <tr class="hover:bg-slate-50/50">
                        <td class="py-2.5 px-3 text-center text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $line->account?->code ?? '-' }}
                        </td>
                        <td class="py-2.5 px-3">
                            <div class="font-bold text-slate-900 dark:text-white">
                                {{ $line->account?->name ?? 'Akun Tidak Ditemukan' }}
                            </div>
                            @if($line->memo)
                            <div class="text-[11px] text-slate-500 italic">{{ $line->memo }}</div>
                            @endif
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $line->debit > 0 ? 'Rp ' . number_format($line->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $line->credit > 0 ? 'Rp ' . number_format($line->credit, 0, ',', '.') : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-slate-100 dark:bg-slate-800 font-bold border-t-2 border-slate-900 dark:border-slate-700">
                    <tr>
                        <td colspan="3" class="py-3 px-3 text-right uppercase tracking-wider text-[11px] text-slate-700 dark:text-slate-300">
                            Total Debit & Kredit (Seimbang):
                        </td>
                        <td class="py-3 px-3 text-right font-mono text-sm text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-3 text-right font-mono text-sm text-blue-600 dark:text-blue-400">
                            Rp {{ number_format($journal->total_credit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Kolom Otorisasi & Tanda Tangan -->
        <div class="grid grid-cols-3 gap-6 pt-6 border-t border-slate-200 dark:border-slate-800 text-center text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase mb-16">Dibuat Oleh:</span>
                <div class="border-t border-slate-400 mx-6 pt-1 font-bold text-slate-800 dark:text-slate-200">
                    ( Staf Akuntansi / Keuangan )
                </div>
            </div>

            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase mb-16">Diperiksa Oleh:</span>
                <div class="border-t border-slate-400 mx-6 pt-1 font-bold text-slate-800 dark:text-slate-200">
                    ( Kepala Toko / Supervisor )
                </div>
            </div>

            <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase mb-16">Disetujui Oleh:</span>
                <div class="border-t border-slate-400 mx-6 pt-1 font-bold text-slate-800 dark:text-slate-200">
                    ( Manajer Keuangan / Owner )
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
    .printable-voucher {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>
@endsection
