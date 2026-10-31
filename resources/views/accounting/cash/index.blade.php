@extends('layouts.app')

@section('title', 'Manajemen Kas & Bank')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Manajemen Kas &amp; Bank</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>💵 Kas Masuk, Kas Keluar &amp; Mutasi Bank</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Catat penerimaan non-penjualan, pengeluaran kas kecil operasional toko, dan transfer antar rekening dengan mudah.
            </p>
        </div>

        <!-- 3 Tombol Aksi Cepat Kasir / Admin Toko -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('accounting.cash.in') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm shadow-emerald-500/20 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Kas Masuk</span>
            </a>

            <a href="{{ route('accounting.cash.out') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-sm shadow-rose-500/20 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                <span>- Kas Keluar (Biaya)</span>
            </a>

            <a href="{{ route('accounting.cash.transfer') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm shadow-blue-500/20 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>⇄ Transfer / Setor Bank</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Cards Saldo Kas & Rekening Bank -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 rounded-2xl shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Kas &amp; Bank</span>
            <div class="text-base font-black mt-1">
                Rp {{ number_format($totalCash, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Seluruh likuiditas toko</span>
        </div>

        @foreach($cashAccounts as $acc)
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">{{ $acc->code }}</span>
            <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate mt-0.5">{{ $acc->name }}</div>
            <div class="text-sm font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($acc->current_balance ?? 0, 0, ',', '.') }}
            </div>
        </div>
        @endforeach
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('accounting.cash.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Jenis Mutasi</label>
                <select name="type"
                        class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Semua Jenis --</option>
                    <option value="CASH_IN" {{ $type === 'CASH_IN' ? 'selected' : '' }}>🟢 Kas Masuk</option>
                    <option value="CASH_OUT" {{ $type === 'CASH_OUT' ? 'selected' : '' }}>🔴 Kas Keluar</option>
                    <option value="CASH_TRANSFER" {{ $type === 'CASH_TRANSFER' ? 'selected' : '' }}>🔵 Transfer Kas</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Toko / Cabang</label>
                <select name="store_id"
                        class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Semua Toko --</option>
                    @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition-all">
                    Filter
                </button>
                <a href="{{ route('accounting.cash.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Mutasi Kas & Bank -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">No. Transaksi</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Jenis</th>
                        <th class="py-3 px-4">Rekening Kas / Bank</th>
                        <th class="py-3 px-4">Kategori / Keperluan</th>
                        <th class="py-3 px-4">Pihak Terkait</th>
                        <th class="py-3 px-4 text-right">Kas Masuk (Rp)</th>
                        <th class="py-3 px-4 text-right">Kas Keluar (Rp)</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($transactions as $tx)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                            <a href="{{ route('accounting.cash.show', $tx->id) }}" class="hover:underline">
                                {{ $tx->transaction_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            {{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4">
                            @if($tx->type === 'CASH_IN')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                🟢 Kas Masuk
                            </span>
                            @elseif($tx->type === 'CASH_OUT')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                🔴 Kas Keluar
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                🔵 Transfer Kas
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-800 dark:text-slate-200">
                            @if($tx->type === 'CASH_IN')
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">Ke:</span> {{ $tx->destinationAccount?->name ?? '-' }}
                            @elseif($tx->type === 'CASH_OUT')
                            <span class="font-bold text-rose-600 dark:text-rose-400">Dari:</span> {{ $tx->sourceAccount?->name ?? '-' }}
                            @else
                            <span class="text-[11px] font-semibold">
                                {{ $tx->sourceAccount?->name ?? '-' }} → <strong class="text-blue-600 dark:text-blue-400">{{ $tx->destinationAccount?->name ?? '-' }}</strong>
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-900 dark:text-white max-w-xs">
                            <div class="font-bold truncate">{{ $tx->categoryAccount?->name ?? ($tx->notes ?: '-') }}</div>
                            @if($tx->notes && $tx->categoryAccount)
                            <div class="text-[10px] text-slate-400 truncate">{{ $tx->notes }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            {{ $tx->contact_person ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $tx->type === 'CASH_IN' ? 'Rp ' . number_format($tx->amount, 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                            {{ $tx->type === 'CASH_OUT' ? 'Rp ' . number_format($tx->amount, 0, ',', '.') : ($tx->type === 'CASH_TRANSFER' ? 'Rp ' . number_format($tx->amount + $tx->admin_fee, 0, ',', '.') : '-') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('accounting.cash.show', $tx->id) }}"
                               class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition-all">
                                Bukti Slip
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            Belum ada riwayat transaksi kas pada filter tanggal ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $transactions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
