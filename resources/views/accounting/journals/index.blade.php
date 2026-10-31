@extends('layouts.app')

@section('title', 'Jurnal Umum (General Journal)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Jurnal Umum</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📖 Daftar Entri Jurnal Umum</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Histori pembukuan jurnal double-entry seimbang dari transaksi otomatis POS/PO dan jurnal biaya manual.
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('accounting.journals.create') }}"
               class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Buat Jurnal Biaya Manual</span>
            </a>
            <a href="{{ route('reports.general-ledger') }}"
               class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span>Lihat Buku Besar (GL)</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <form method="GET" action="{{ route('accounting.journals.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
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
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Tipe Transaksi</label>
                <select name="reference_type"
                        class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Semua Sumber Jurnal --</option>
                    @foreach($refTypes as $code => $label)
                    <option value="{{ $code }}" {{ $selectedRefType === $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Toko / Cabang</label>
                <select name="store_id"
                        class="w-full px-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Semua Toko --</option>
                    @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $selectedStoreId === $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all">
                    Filter Jurnal
                </button>
                <a href="{{ route('accounting.journals.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table Jurnal -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">No. Jurnal</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Tipe Referensi</th>
                        <th class="py-3 px-4">No. Ref</th>
                        <th class="py-3 px-4">Keterangan / Memo</th>
                        <th class="py-3 px-4 text-right">Total Debit</th>
                        <th class="py-3 px-4 text-right">Total Kredit</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($journals as $jrn)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                            <a href="{{ route('accounting.journals.show', $jrn->id) }}" class="hover:underline">
                                {{ $jrn->entry_number }}
                            </a>
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            {{ $jrn->entry_date ? \Carbon\Carbon::parse($jrn->entry_date)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $jrn->reference_type === 'MANUAL' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ $refTypes[$jrn->reference_type] ?? $jrn->reference_type }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300">
                            {{ $jrn->reference_number ?: '-' }}
                        </td>
                        <td class="py-3 px-4 text-slate-900 dark:text-white max-w-xs truncate">
                            {{ $jrn->description }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($jrn->total_debit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                            Rp {{ number_format($jrn->total_credit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($jrn->is_balanced)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                                ✓ Seimbang
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400">
                                ⚠ Selisih
                            </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('accounting.journals.show', $jrn->id) }}"
                               class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-[11px] font-bold transition-all">
                                Voucher
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            Tidak ada entri jurnal dalam rentang tanggal ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($journals->hasPages())
        <div class="p-4 border-t border-slate-100 dark:border-slate-800">
            {{ $journals->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
