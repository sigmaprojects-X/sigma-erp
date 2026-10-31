@extends('layouts.app')

@section('title', 'Buku Piutang Pelanggan (AR)')
@section('tab_title', 'Piutang Pelanggan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('master.customers') }}" class="hover:text-blue-600 font-semibold">Pelanggan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Piutang Dagang (Accounts Receivable)</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📑 Buku Pembantu Piutang Pelanggan (Tempo / Kasbon)</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('master.customers') }}"
               class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                👥 Master Data Pelanggan
            </a>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div class="bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-transparent border border-rose-500/20 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Total Piutang Berjalan</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                Rp {{ number_format($totalReceivable, 0, ',', '.') }}
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                Saldo tagihan tempo/kasbon belum terlunasi
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-transparent border border-blue-500/20 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Pelanggan Memiliki Tagihan</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                {{ number_format($totalCustomerInDebt) }} <span class="text-xs font-semibold text-slate-500">orang/toko</span>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                Pelanggan grosir dengan saldo piutang aktif
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 rounded-2xl p-4 shadow-xs sm:col-span-2 lg:col-span-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Sistem Kredit Aman</div>
            <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">
                Terkontrol Plafon Otomatis
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                Transaksi POS Tempo otomatis dibatasi oleh limit kredit per pelanggan.
            </div>
        </div>
    </div>

    <!-- SEARCH & FILTER -->
    <form method="GET" action="{{ route('customers.receivables.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[220px]">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Cari kode, nama pelanggan, atau no HP..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Filter
        </button>

        @if(request('q'))
            <a href="{{ route('customers.receivables.index') }}" class="text-xs text-slate-400 hover:text-slate-600 underline">Reset</a>
        @endif
    </form>

    <!-- TABEL DAFTAR PIUTANG -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">Kode & Pelanggan</th>
                        <th class="p-3">Tier Harga</th>
                        <th class="p-3">Kontak / Toko</th>
                        <th class="p-3 text-right">Plafon Kredit</th>
                        <th class="p-3 text-right">Saldo Piutang Berjalan</th>
                        <th class="p-3 text-right">Sisa Plafon Aman</th>
                        <th class="p-3 text-center">Tempo</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($customers as $c)
                        @php
                            $available = max(0.0, (float)$c->credit_limit - (float)$c->current_credit_balance);
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $c->name }}</div>
                                <div class="text-[10.5px] font-mono text-slate-400">{{ $c->code }}</div>
                            </td>
                            <td class="p-3 font-semibold">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black {{ $c->customerTier?->code === 'GROSIR' ? 'bg-purple-100 text-purple-900 dark:bg-purple-950 dark:text-purple-300' : 'bg-blue-100 text-blue-900 dark:bg-blue-950 dark:text-blue-300' }}">
                                    {{ $c->customerTier?->name ?? 'Standard' }}
                                </span>
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                <div>{{ $c->phone ?: '-' }}</div>
                                <div class="text-[10px] text-slate-400 truncate max-w-[200px]">{{ $c->address ?: '-' }}</div>
                            </td>
                            <td class="p-3 text-right font-mono font-bold">
                                Rp {{ number_format((float)$c->credit_limit, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-rose-600 dark:text-rose-400">
                                Rp {{ number_format((float)$c->current_credit_balance, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($available, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center text-slate-600 dark:text-slate-400">
                                {{ $c->credit_term_days ? $c->credit_term_days . ' hari' : '-' }}
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('customers.receivables.ledger', $c->id) }}"
                                       class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold rounded-lg text-xs transition-colors">
                                        Kartu Piutang
                                    </a>
                                    <a href="{{ route('customers.receivables.pay', $c->id) }}"
                                       class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-black rounded-lg text-xs shadow-xs transition-all">
                                        Bayar Piutang
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-400">
                                Tidak ada pelanggan dengan saldo piutang berjalan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
