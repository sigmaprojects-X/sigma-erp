@extends('layouts.app')

@section('title', 'Mutasi Uang Muka: ' . $supplier->name . ' — SIGMA ERP')

@section('content')
<div class="space-y-6">

    <!-- Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center space-x-3">
            <a href="{{ route('accounting.deposits.supplier.index') }}" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Buku Mutasi Uang Muka Supplier</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Riwayat pembayaran DP, pemotongan faktur pembelian, dan pengembalian dana.</p>
            </div>
        </div>
    </div>

    <!-- Supplier Profile Card -->
    <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
            <div class="flex items-center space-x-3">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ $supplier->name }}</h2>
                <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ $supplier->code }}</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Kontak: {{ $supplier->contact_person ?? '-' }} | Telepon: {{ $supplier->phone ?? '-' }} | Rek: {{ $supplier->bank_name ?? '-' }} {{ $supplier->bank_account_no ?? '' }}
            </p>
        </div>

        <div class="flex items-center space-x-6">
            <div class="text-right">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Saldo Uang Muka (DP) Saat Ini</p>
                <h3 class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">Rp {{ number_format($supplier->deposit_balance, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Tanggal</th>
                        <th class="px-4 py-3.5">No. Bukti / Jurnal</th>
                        <th class="px-4 py-3.5">Jenis Mutasi</th>
                        <th class="px-4 py-3.5">Akun Kas / Bank</th>
                        <th class="px-4 py-3.5 text-right">Debit (Bayar DP)</th>
                        <th class="px-4 py-3.5 text-right">Kredit (Potong Faktur / Refund)</th>
                        <th class="px-4 py-3.5 text-right">Saldo Berjalan</th>
                        <th class="px-5 py-3.5">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($ledger as $row)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3.5 text-xs whitespace-nowrap">{{ $row->transaction_date->format('d M Y') }}</td>
                            <td class="px-4 py-3.5 font-mono text-xs font-bold text-slate-800 dark:text-slate-200">
                                <div>{{ $row->deposit_number }}</div>
                                @if($row->journalEntry)
                                    <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-mono">Jrn: {{ $row->journalEntry->entry_number }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs">
                                @if($row->type === 'TOP_UP')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">BAYAR DP</span>
                                @elseif($row->type === 'USAGE')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">POTONG FAKTUR</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">REFUND DANA</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs">{{ $row->account?->name ?? 'Kas / Bank' }}</td>
                            <td class="px-4 py-3.5 text-right font-semibold text-indigo-600 dark:text-indigo-400 text-xs">
                                {{ $row->type === 'TOP_UP' ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-semibold text-rose-600 dark:text-rose-400 text-xs">
                                {{ in_array($row->type, ['WITHDRAWAL', 'USAGE', 'REFUND']) ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-900 dark:text-white text-xs">
                                Rp {{ number_format($row->balance_after, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 max-w-xs truncate">
                                {{ $row->notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                Belum ada riwayat mutasi transaksi uang muka untuk supplier ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
