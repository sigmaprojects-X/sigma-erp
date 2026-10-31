@extends('layouts.app')

@section('title', 'Kartu Piutang - ' . $customer->name)
@section('tab_title', 'Kartu Piutang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.receivables.index') }}" class="hover:text-blue-600 font-semibold">Buku Piutang</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold font-mono">{{ $customer->code }}</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>👤 Kartu Piutang Pelanggan: {{ $customer->name }}</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customers.receivables.index') }}"
               class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                ← Kembali ke Buku Piutang
            </a>
            <a href="{{ route('customers.receivables.pay', $customer->id) }}"
               class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-500/25">
                <span>💳 Catat Pembayaran Piutang</span>
            </a>
        </div>
    </div>

    <!-- CUSTOMER PROFILE & CREDIT STATUS BANNER -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Data Pelanggan</span>
            <div class="font-black text-base text-slate-900 dark:text-white mt-1">{{ $customer->name }}</div>
            <div class="text-[11px] font-mono text-blue-600 dark:text-blue-400 mt-0.5">{{ $customer->code }} ({{ $customer->customerTier?->name ?? 'Standard' }})</div>
            <div class="text-slate-500 dark:text-slate-400 mt-1">{{ $customer->phone ?: 'Tidak ada no telepon' }}</div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <span class="text-slate-400 font-bold uppercase text-[10px]">Plafon Kredit (Limit)</span>
            <div class="font-black text-base text-slate-900 dark:text-white mt-1 font-mono">
                Rp {{ number_format((float)$customer->credit_limit, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Batas maksimal hutang tempo: <span class="font-bold">{{ $customer->credit_term_days ? $customer->credit_term_days . ' hari' : 'Standar' }}</span>
            </div>
        </div>

        <div class="bg-rose-500/10 border border-rose-500/20 rounded-2xl p-4 shadow-xs">
            <span class="text-rose-600 dark:text-rose-400 font-bold uppercase text-[10px]">Saldo Piutang Berjalan</span>
            <div class="font-black text-xl text-rose-600 dark:text-rose-400 mt-1 font-mono">
                Rp {{ number_format((float)$customer->current_credit_balance, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-rose-700/80 dark:text-rose-300/80 mt-0.5">
                Total kewajiban tempo belum disetor
            </div>
        </div>

        @php
            $avail = max(0.0, (float)$customer->credit_limit - (float)$customer->current_credit_balance);
        @endphp
        <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-4 shadow-xs">
            <span class="text-emerald-600 dark:text-emerald-400 font-bold uppercase text-[10px]">Sisa Plafon Aman</span>
            <div class="font-black text-xl text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                Rp {{ number_format($avail, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-emerald-700/80 dark:text-emerald-300/80 mt-0.5">
                Sisa limit belanja tempo yang diizinkan
            </div>
        </div>
    </div>

    <!-- TABEL FAKTUR BELUM LUNAS (OUTSTANDING INVOICES) -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <span>⚠️ Faktur Penjualan Belum Lunas (Outstanding)</span>
            </h2>
            <span class="text-xs text-slate-400 font-bold">Total: {{ $outstandingInvoices->count() }} faktur</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">No. Faktur POS</th>
                        <th class="p-3">Tanggal Transaksi</th>
                        <th class="p-3">Jatuh Tempo</th>
                        <th class="p-3 text-right">Nilai Belanja</th>
                        <th class="p-3 text-right">Nilai Kredit/Tempo</th>
                        <th class="p-3 text-right">Sisa Piutang</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200 font-mono text-[11px]">
                    @forelse($outstandingInvoices as $inv)
                        @php
                            $isOverdue = $inv->due_date && $inv->due_date->isPast();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-bold text-blue-600 dark:text-blue-400 font-mono">
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="p-3 font-sans">
                                {{ $inv->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="p-3 font-sans">
                                @if($inv->due_date)
                                    <span class="{{ $isOverdue ? 'text-rose-600 font-extrabold animate-pulse' : 'text-slate-700 dark:text-slate-300' }}">
                                        {{ $inv->due_date->format('d/m/Y') }}
                                        {{ $isOverdue ? '(LEWAT TEMPO)' : '' }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-bold text-slate-700 dark:text-slate-300">
                                Rp {{ number_format((float)$inv->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-bold text-slate-700 dark:text-slate-300">
                                Rp {{ number_format((float)($inv->credit_amount > 0 ? $inv->credit_amount : $inv->grand_total), 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-black text-rose-600 dark:text-rose-400">
                                Rp {{ number_format((float)$inv->remaining_receivable, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center font-sans">
                                @if($inv->payment_status === 'UNPAID')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">BELUM LUNAS</span>
                                @elseif($inv->payment_status === 'PARTIALLY_PAID')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">SEBAGIAN</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">LUNAS</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-400 font-sans text-xs">
                                Seluruh faktur tempo pelanggan ini telah lunas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL BUKU PEMBANTU PIUTANG (KRONOLOGI LENGKAP DEBET & KREDIT) -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <span>📖 Kronologi Kartu Piutang (Debet Penjualan vs Kredit Pelunasan)</span>
            </h2>
            <span class="text-xs text-slate-400 font-bold">Total Mutasi: {{ count($ledgerEntries) }} entri</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Tanggal</th>
                        <th class="p-3">No. Referensi</th>
                        <th class="p-3">Keterangan Transaksi</th>
                        <th class="p-3 text-right">Debet (Tambah Piutang)</th>
                        <th class="p-3 text-right">Kredit (Pelunasan)</th>
                        <th class="p-3 text-right">Saldo Piutang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200 font-mono text-[11px]">
                    @forelse($ledgerEntries as $entry)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-sans">
                                {{ date('d/m/Y H:i', strtotime($entry['date'])) }}
                            </td>
                            <td class="p-3 font-bold text-blue-600 dark:text-blue-400">
                                @if($entry['type'] === 'PAYMENT')
                                    <a href="{{ route('customers.receivables.receipt', $entry['model']->id) }}" class="hover:underline">
                                        {{ $entry['reference_number'] }}
                                    </a>
                                @else
                                    {{ $entry['reference_number'] }}
                                @endif
                            </td>
                            <td class="p-3 font-sans">
                                {{ $entry['description'] }}
                            </td>
                            <td class="p-3 text-right font-bold text-rose-600 dark:text-rose-400">
                                {{ $entry['debit'] > 0 ? 'Rp ' . number_format($entry['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $entry['credit'] > 0 ? 'Rp ' . number_format($entry['credit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($entry['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400 font-sans text-xs">
                                Belum ada riwayat transaksi kartu piutang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
