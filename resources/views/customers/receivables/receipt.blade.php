@extends('layouts.app')

@section('title', 'Kwitansi ' . $payment->payment_number)
@section('tab_title', 'Kwitansi Pembayaran')

@section('content')
<div class="space-y-4 p-4 max-w-3xl mx-auto w-full">

    <!-- TOP ACTIONS (HIDDEN WHEN PRINTING) -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.receivables.index') }}" class="hover:text-blue-600 font-semibold">Buku Piutang</a>
                <span>/</span>
                <a href="{{ route('customers.receivables.ledger', $payment->customer_id) }}" class="hover:text-blue-600 font-semibold">Kartu Piutang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold font-mono">{{ $payment->payment_number }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                Bukti Kwitansi Pembayaran Piutang
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customers.receivables.ledger', $payment->customer_id) }}"
               class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                ← Kembali
            </a>
            <button onclick="window.print()"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all cursor-pointer">
                <span>🖨️ Cetak Kwitansi</span>
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400 text-xs font-bold print:hidden">
            {{ session('success') }}
        </div>
    @endif

    <!-- KWITANSI PRINTABLE SHEET -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm text-slate-800 font-sans print:shadow-none print:border-none print:p-0">
        <!-- HEADER KWITANSI -->
        <div class="flex items-start justify-between border-b-2 border-slate-900 pb-4 mb-6">
            <div>
                <div class="text-2xl font-black tracking-tight text-slate-900">SIGMA ERP</div>
                <div class="text-xs font-bold text-slate-600 uppercase">{{ $payment->store->name ?? 'SIGMA RETAIL & WHOLESALE' }}</div>
                <div class="text-[11px] text-slate-500">{{ $payment->store->address ?? 'Pusat Distribusi Grosir' }}</div>
            </div>

            <div class="text-right">
                <div class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black rounded-lg tracking-wider uppercase mb-1">
                    KWITANSI PELUNASAN PIUTANG
                </div>
                <div class="font-mono font-bold text-xs text-blue-700">{{ $payment->payment_number }}</div>
                <div class="text-[11px] text-slate-500">Tanggal: {{ $payment->payment_date->format('d F Y') }}</div>
            </div>
        </div>

        <!-- INFO DITERIMA DARI -->
        <div class="space-y-3 text-xs mb-6">
            <div class="flex py-1.5 border-b border-slate-100">
                <span class="w-36 text-slate-500 font-semibold">Telah Diterima Dari</span>
                <span class="font-black text-slate-900">: {{ $payment->customer->name }} ({{ $payment->customer->code }})</span>
            </div>

            <div class="flex py-1.5 border-b border-slate-100">
                <span class="w-36 text-slate-500 font-semibold">Uang Sejumlah</span>
                <span class="font-mono font-black text-base text-emerald-700">: Rp {{ number_format((float)$payment->total_amount, 0, ',', '.') }}</span>
            </div>

            <div class="flex py-1.5 border-b border-slate-100">
                <span class="w-36 text-slate-500 font-semibold">Metode Pembayaran</span>
                <span class="font-bold text-slate-900">:
                    {{ $payment->payment_method }}
                    @if($payment->payment_method === 'TRANSFER')
                        ({{ $payment->bank_name }} - {{ $payment->bank_account_number }})
                        @if($payment->reference_number) • Ref: {{ $payment->reference_number }} @endif
                    @endif
                </span>
            </div>

            <div class="flex py-1.5 border-b border-slate-100">
                <span class="w-36 text-slate-500 font-semibold">Untuk Keperluan</span>
                <span class="text-slate-800">: {{ $payment->notes ?: 'Pelunasan piutang belanja grosir (Tempo POS)' }}</span>
            </div>
        </div>

        <!-- TABEL ALOKASI FAKTUR -->
        @if($payment->items->isNotEmpty())
            <div class="mb-6">
                <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Rincian Faktur yang Dilunasi:</div>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-slate-50 text-slate-600 font-bold text-[10px] uppercase border-b border-slate-200">
                            <tr>
                                <th class="p-2.5">No. Faktur POS</th>
                                <th class="p-2.5">Tanggal Faktur</th>
                                <th class="p-2.5 text-right">Nilai Faktur</th>
                                <th class="p-2.5 text-right">Alokasi Pembayaran</th>
                                <th class="p-2.5 text-right">Sisa Faktur Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-[11px]">
                            @foreach($payment->items as $item)
                                <tr>
                                    <td class="p-2.5 font-bold text-blue-700">{{ $item->transaction->invoice_number }}</td>
                                    <td class="p-2.5 font-sans">{{ $item->transaction->created_at->format('d/m/Y') }}</td>
                                    <td class="p-2.5 text-right">Rp {{ number_format((float)$item->transaction->grand_total, 0, ',', '.') }}</td>
                                    <td class="p-2.5 text-right font-black text-emerald-700">Rp {{ number_format((float)$item->amount, 0, ',', '.') }}</td>
                                    <td class="p-2.5 text-right text-slate-600">Rp {{ number_format((float)$item->transaction->remaining_receivable, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- SISA SALDO HUTANG PELANGGAN -->
        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 mb-8 flex items-center justify-between text-xs font-mono">
            <div>
                <span class="text-slate-500 font-sans">Sisa Saldo Piutang Berjalan Pelanggan:</span>
                <div class="text-lg font-black text-rose-600 mt-0.5">
                    Rp {{ number_format((float)$payment->customer->current_credit_balance, 0, ',', '.') }}
                </div>
            </div>
            <div class="text-right">
                <span class="text-slate-500 font-sans">Sisa Plafon Kredit Aman:</span>
                <div class="text-lg font-black text-emerald-600 mt-0.5">
                    Rp {{ number_format(max(0, (float)$payment->customer->credit_limit - (float)$payment->customer->current_credit_balance), 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- TANDA TANGAN -->
        <div class="grid grid-cols-2 gap-8 text-center text-xs pt-4 border-t border-slate-200">
            <div>
                <div class="text-slate-400 mb-12">Penyetor (Pelanggan),</div>
                <div class="font-bold underline text-slate-900">{{ $payment->customer->name }}</div>
            </div>

            <div>
                <div class="text-slate-400 mb-12">Penerima Kas / Kasir,</div>
                <div class="font-bold underline text-slate-900">{{ $payment->receiver->name ?? 'Kasir SIGMA ERP' }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
