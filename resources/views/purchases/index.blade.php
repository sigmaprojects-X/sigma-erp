@extends('layouts.app')

@section('title', 'Daftar Faktur Pembelian & Penerimaan Barang')
@section('tab_title', 'Pembelian & AP')

@section('content')
<div class="space-y-5">

    <!-- HEADER & ACTION BAR -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">📥</span>
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                    Faktur Pembelian &amp; Penerimaan Barang
                </h1>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Kelola faktur masuk dari supplier, pembaruan stok otomatis, HPP Moving Average, dan persetujuan kenaikan harga jual.
            </p>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('purchases.recommendations') }}"
               class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-extrabold text-xs rounded-xl border border-indigo-200 dark:border-indigo-800 transition-all flex items-center gap-1.5 shadow-2xs">
                <span>⚡ Smart Restock</span>
            </a>
            <a href="{{ route('purchases.orders.create') }}"
               class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-extrabold text-xs rounded-xl border border-blue-200 dark:border-blue-800 transition-all flex items-center gap-1.5 shadow-2xs">
                <span>📑 Buat PO Baru</span>
            </a>
            <a href="{{ route('purchases.debt-ledger') }}"
               class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-extrabold text-xs rounded-xl border border-amber-300 dark:border-amber-800 transition-all flex items-center gap-1.5 shadow-2xs">
                <span>💰 Hutang Supplier</span>
            </a>
            <a href="{{ route('purchases.export', request()->query()) }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-extrabold text-xs rounded-xl border border-slate-300 dark:border-slate-700 transition-all flex items-center gap-1.5 shadow-2xs">
                <span>📊 Export CSV/Excel</span>
            </a>
            <a href="{{ route('purchases.create') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 shadow-emerald-900/20 active:scale-95">
                <span>➕ Input Pembelian Baru</span>
            </a>
        </div>
    </div>

    <!-- 4 EXECUTIVE SUMMARY METRIC CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- KPI 1: Pembelian Bulan Ini -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-500/20 dark:bg-blue-950/50 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                    BULAN INI
                </span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Total Pembelian Bulan Ini
                </div>
                <div class="mt-1 font-mono text-xl font-black text-slate-900 dark:text-white truncate">
                    Rp {{ number_format($monthlyPurchasesTotal, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[10px] text-slate-400">
                    Per {{ now()->translatedFormat('F Y') }}
                </div>
            </div>
        </div>

        <!-- KPI 2: Total Hutang Supplier (Accounts Payable) -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-500/20 dark:bg-amber-950/50 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                    HUTANG (AP)
                </span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Total Hutang Supplier
                </div>
                <div class="mt-1 font-mono text-xl font-black text-amber-600 dark:text-amber-400 truncate">
                    Rp {{ number_format($totalAccountsPayable, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-[10px] text-slate-400">
                    Tagihan Tempo Berjalan
                </div>
            </div>
        </div>

        <!-- KPI 3: Hutang Lewat Jatuh Tempo (Overdue) -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 ring-1 ring-rose-500/20 dark:bg-rose-950/50 dark:text-rose-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                    OVERDUE
                </span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    Lewat Jatuh Tempo
                </div>
                <div class="mt-1 font-mono text-xl font-black text-rose-600 dark:text-rose-400 truncate">
                    {{ $overdueCount }} <span class="text-xs font-sans text-slate-500 font-bold">Faktur</span>
                </div>
                <div class="mt-1 text-[10px] text-rose-600 font-semibold">
                    ● Perlu Segera Dilunasi
                </div>
            </div>
        </div>

        <!-- KPI 4: PO Aktif Berjalan -->
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900 transition-all hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-emerald-500/20 dark:bg-emerald-950/50 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                    PO AKTIF
                </span>
            </div>
            <div class="mt-3">
                <div class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    PO Berjalan
                </div>
                <div class="mt-1 font-mono text-xl font-black text-slate-900 dark:text-white truncate">
                    {{ $activePoCount }} <span class="text-xs font-sans text-slate-500 font-bold">Pesanan</span>
                </div>
                <div class="mt-1 text-[10px] text-emerald-600 font-semibold">
                    ● Dalam Pengiriman / Approval
                </div>
            </div>
        </div>

    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
            <div class="sm:col-span-4">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Cari Faktur / Supplier:</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="No. Bukti, Faktur Supplier, Nama Supplier..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                </div>
            </div>
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Metode Bayar:</label>
                <select name="payment_type" class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="">Semua Metode</option>
                    <option value="CASH" {{ request('payment_type') === 'CASH' ? 'selected' : '' }}>TUNAI (Cash)</option>
                    <option value="TRANSFER" {{ request('payment_type') === 'TRANSFER' ? 'selected' : '' }}>TRANSFER</option>
                    <option value="TEMPO" {{ request('payment_type') === 'TEMPO' ? 'selected' : '' }}>TEMPO (Hutang)</option>
                </select>
            </div>
            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Status Pelunasan:</label>
                <select name="payment_status" class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden font-semibold">
                    <option value="">Semua Status Hutang</option>
                    <option value="PAID" {{ request('payment_status') === 'PAID' ? 'selected' : '' }}>🟢 Lunas (Paid)</option>
                    <option value="UNPAID_TEMPO" {{ request('payment_status') === 'UNPAID_TEMPO' ? 'selected' : '' }}>🟡 Belum Lunas (Tempo Aktif)</option>
                    <option value="OVERDUE" {{ request('payment_status') === 'OVERDUE' ? 'selected' : '' }}>🔴 Lewat Jatuh Tempo (Overdue)</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Status Faktur:</label>
                <select name="status" class="w-full py-2 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-hidden">
                    <option value="">Semua Status</option>
                    <option value="RECEIVED" {{ request('status') === 'RECEIVED' ? 'selected' : '' }}>RECEIVED</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                </select>
            </div>
            <div class="sm:col-span-1 flex items-end gap-1.5">
                <button type="submit" class="flex-1 py-2 px-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition-colors cursor-pointer text-center" title="Terapkan Filter">
                    Filter
                </button>
                @if(request()->hasAny(['q', 'payment_type', 'payment_status', 'status']))
                    <a href="{{ route('purchases.index') }}" class="py-2 px-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors text-center" title="Reset Filter">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABLE DAFTAR PEMBELIAN -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10.5px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-3.5 px-4">No. Bukti / Faktur</th>
                        <th class="py-3.5 px-4">Tanggal &amp; Jatuh Tempo</th>
                        <th class="py-3.5 px-4">Supplier</th>
                        <th class="py-3.5 px-4 text-center">Metode Bayar</th>
                        <th class="py-3.5 px-4 text-right">Total Pembelian</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($purchases as $p)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-bold text-blue-600 dark:text-blue-400 font-mono">
                                    <a href="{{ route('purchases.show', $p->id) }}" class="hover:underline">
                                        {{ $p->reference_number }}
                                    </a>
                                </div>
                                <div class="text-[10.5px] text-slate-500 font-mono">Faktur: {{ $p->invoice_number }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-medium text-slate-700 dark:text-slate-300">{{ $p->purchase_date->format('d/m/Y') }}</div>
                                @if($p->payment_type === 'TEMPO' && $p->due_date)
                                    <div class="text-[10.5px] font-semibold {{ $p->due_date->isPast() && ($p->paid_amount < $p->grand_total) ? 'text-rose-600' : 'text-amber-600' }}">
                                        Tempo: {{ $p->due_date->format('d/m/Y') }}
                                        @if($p->due_date->isPast() && ($p->paid_amount < $p->grand_total))
                                            (Lewat {{ $p->due_date->diffInDays(now()) }} hr)
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $p->supplier->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $p->supplier->phone ?: '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($p->payment_type === 'CASH')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                        💵 TUNAI
                                    </span>
                                @elseif($p->payment_type === 'TRANSFER')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300">
                                        🏦 TRANSFER
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                                        📝 TEMPO
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($p->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    @if($p->status === 'RECEIVED')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                            ✓ DITERIMA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9.5px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300">
                                            {{ $p->status }}
                                        </span>
                                    @endif

                                    @if($p->payment_type !== 'TEMPO' || $p->paid_amount >= $p->grand_total)
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[9px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                            🟢 LUNAS
                                        </span>
                                    @elseif($p->due_date && $p->due_date->isPast())
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[9px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300" title="Sisa Rp {{ number_format($p->grand_total - $p->paid_amount, 0, ',', '.') }}">
                                            🔴 OVERDUE
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[9px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300" title="Sisa Rp {{ number_format($p->grand_total - $p->paid_amount, 0, ',', '.') }}">
                                            🟡 HUTANG
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('purchases.show', $p->id) }}"
                                       class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-[11px] font-bold transition-colors">
                                        Detail
                                    </a>
                                    <a href="{{ route('purchases.print', $p->id) }}"
                                       target="_blank"
                                       title="Cetak Faktur Pembelian (A4)"
                                       class="px-2 py-1 bg-teal-50 hover:bg-teal-100 dark:bg-teal-950 dark:hover:bg-teal-900 text-teal-700 dark:text-teal-300 rounded-lg text-[11px] font-bold transition-colors">
                                        🖨️
                                    </a>
                                    <a href="{{ route('purchases.shelf-tags', $p->id) }}"
                                       target="_blank"
                                       title="Cetak Label Rak"
                                       class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-bold transition-colors">
                                        🏷️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="text-3xl mb-2">📥</div>
                                <div class="font-bold text-slate-600 dark:text-slate-400">Belum ada faktur pembelian tercatat.</div>
                                <div class="text-xs text-slate-400 mt-0.5">Mulai dengan mencatat penerimaan barang baru dari supplier.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
