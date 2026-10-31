@extends('layouts.app')

@section('title', 'Catatan Transaksi - SIGMA ERP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Catatan Transaksi</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📜 Catatan Transaksi (Unified Audit Trail)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Histori komprehensif seluruh aktivitas transaksi kasir, pembelian, beban, persediaan, aset, dan jurnal umum secara real-time.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('accounting.journals.index') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>← Jurnal Umum</span>
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('accounting.transaction-logs.index') }}"
          class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 text-xs">
        <div>
            <label class="block text-[10px] font-bold text-slate-400 mb-1">Dari Tanggal</label>
            <input type="date" name="start_date" value="{{ $startDate }}"
                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-400 mb-1">Sampai Tanggal</label>
            <input type="date" name="end_date" value="{{ $endDate }}"
                   class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-400 mb-1">Tipe Transaksi</label>
            <select name="type"
                    class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                <option value="">Semua Tipe Transaksi</option>
                @foreach($transactionTypes as $k => $conf)
                <option value="{{ $k }}" {{ $typeFilter === $k ? 'selected' : '' }}>{{ $conf['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-400 mb-1">Cabang Toko</label>
            <select name="store_id"
                    class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                <option value="">Semua Cabang Toko</option>
                @foreach($stores as $st)
                <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[10px] font-bold text-slate-400 mb-1">Pengguna / Staf</label>
            <select name="user_id"
                    class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                <option value="">Semua Staf</option>
                @foreach($users as $u)
                <option value="{{ $u->id }}" {{ $userId === $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ strtoupper($u->role) }})</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end space-x-1.5">
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-400 mb-1">Pencarian</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="No. Nota / Ket..."
                       class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
            </div>
            <button type="submit"
                    class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs cursor-pointer">
                Filter
            </button>
        </div>
    </form>

    <!-- Tabel Catatan Transaksi -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Waktu &amp; Tanggal</th>
                        <th class="py-3 px-4">Tipe Transaksi</th>
                        <th class="py-3 px-4">No. Bukti / Referensi</th>
                        <th class="py-3 px-4">Cabang</th>
                        <th class="py-3 px-4">Eksekutor</th>
                        <th class="py-3 px-4 text-right">Nilai Transaksi (Rp)</th>
                        <th class="py-3 px-4">Keterangan Aktivitas</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($logs as $log)
                    @php
                        $typeMeta = $transactionTypes[$log->reference_type] ?? ['label' => $log->reference_type, 'color' => 'slate'];
                    @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $log->entry_date ? \Carbon\Carbon::parse($log->entry_date)->format('d/m/Y') : '-' }}</span>
                            <span class="text-[10px] text-slate-400">{{ $log->created_at ? $log->created_at->format('H:i:s') : '' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 font-bold text-[10px] whitespace-nowrap">
                                {{ $typeMeta['label'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono">
                            <a href="{{ route('accounting.journals.show', $log->id) }}" class="font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ $log->reference_number ?: $log->entry_number }}
                            </a>
                            <span class="text-[10px] text-slate-400 block font-normal">{{ $log->entry_number }}</span>
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400 whitespace-nowrap">
                            {{ $log->store?->name ?? 'Head Office' }}
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 dark:text-white block">{{ $log->user?->name ?? 'System' }}</span>
                            <span class="text-[10px] text-slate-400">{{ $log->user?->role ? strtoupper($log->user->role) : 'AUTO' }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                            Rp {{ number_format($log->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-300 max-w-xs truncate" title="{{ $log->description }}">
                            {{ $log->description }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($log->is_balanced)
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-[10px]">
                                SEIMBANG
                            </span>
                            @else
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-bold text-[10px]">
                                SELISIH
                            </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                            Tidak ada catatan transaksi yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
