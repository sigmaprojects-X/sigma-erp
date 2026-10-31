@extends('layouts.app')

@section('title', 'Tutup Buku Tahunan - SIGMA ERP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Proses Tahunan</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📅 Tutup Buku Tahunan (Year-End Closing)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Otomasi penutupan saldo nominal pendapatan &amp; beban ke Laba Ditahan (Retained Earnings) serta penguncian tahun buku.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <!-- Filter Pilih Tahun -->
            <form action="{{ route('accounting.closing.index') }}" method="GET" class="flex items-center space-x-2">
                <label class="text-xs font-bold text-slate-600 dark:text-slate-400">Tahun Buku:</label>
                <select name="year" onchange="this.form.submit()"
                        class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    @for($y = (int) date('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                    @endfor
                </select>
            </form>

            <a href="{{ route('reports.income-statement', ['start_date' => "{$selectedYear}-01-01", 'end_date' => "{$selectedYear}-12-31"]) }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>📊 Laba Rugi {{ $selectedYear }}</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs">
        <div class="font-bold mb-1">⚠️ Terjadi Kesalahan:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($summary['is_already_closed'])
    <!-- Status Tahun Buku Sudah Ditutup -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-900 to-teal-900 text-white shadow-lg space-y-4">
        <div class="flex items-start justify-between">
            <div class="space-y-1">
                <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-xs font-bold">
                    <span>🔒 TAHUN BUKU {{ $selectedYear }} SUDAH DITUTUP &amp; DIKUNCI</span>
                </span>
                <h2 class="text-xl font-black mt-2">Periode {{ $selectedYear }} Telah Resmi Selesai</h2>
                <p class="text-xs text-emerald-200/80 max-w-2xl">
                    Seluruh saldo nominal pendapatan dan beban telah dialokasikan ke Laba Ditahan. Transaksi bertanggal tahun {{ $selectedYear }} dikunci dari modifikasi manual untuk menjaga keaslian data audit.
                </p>
            </div>

            @if(in_array(auth()->user()?->role, ['owner', 'manager']))
            <form action="{{ route('accounting.closing.reopen', $selectedYear) }}" method="POST"
                  onsubmit="return confirm('Apakah Anda yakin ingin membuka kembali tahun buku {{ $selectedYear }}? Jurnal penutup akan ditarik.')">
                @csrf
                <button type="submit"
                        class="px-3.5 py-2 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white text-xs font-bold transition-all shadow-md">
                    🔓 Buka Kembali Periode
                </button>
            </form>
            @endif
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-emerald-800/60">
            <div>
                <span class="text-[11px] uppercase tracking-wider text-emerald-300 font-medium">Laba Bersih Ditutup</span>
                <div class="text-lg font-black font-mono mt-0.5">
                    Rp {{ number_format($summary['closing_record']->net_profit, 0, ',', '.') }}
                </div>
            </div>
            <div>
                <span class="text-[11px] uppercase tracking-wider text-emerald-300 font-medium">Tanggal Penutupan</span>
                <div class="text-sm font-bold mt-1">
                    {{ $summary['closing_record']->closing_date ? \Carbon\Carbon::parse($summary['closing_record']->closing_date)->format('d F Y') : '-' }}
                </div>
            </div>
            <div>
                <span class="text-[11px] uppercase tracking-wider text-emerald-300 font-medium">Ditutup Oleh</span>
                <div class="text-sm font-bold mt-1">
                    {{ $summary['closing_record']->closer?->name ?? 'Sistem' }}
                </div>
            </div>
            <div>
                <span class="text-[11px] uppercase tracking-wider text-emerald-300 font-medium">Jurnal Penutup</span>
                <div class="text-sm font-bold mt-1">
                    @if($summary['closing_record']->closing_journal_entry_id)
                    <a href="{{ route('accounting.journals.show', $summary['closing_record']->closing_journal_entry_id) }}" class="underline text-emerald-300 hover:text-white">
                        {{ $summary['closing_record']->closingJournalEntry?->entry_number ?? 'Lihat Jurnal' }} →
                    </a>
                    @else
                    -
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <!-- Ringkasan Finansial Tahun Berjalan -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Pendapatan (Tahun {{ $selectedYear }})</span>
            <div class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">Dari {{ count($summary['revenue_accounts']) }} akun pendapatan penjualan</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Beban &amp; HPP (Tahun {{ $selectedYear }})</span>
            <div class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400 mt-1">
                Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}
            </div>
            <span class="text-[11px] text-slate-400 mt-1 block">Dari {{ count($summary['expense_accounts']) }} akun HPP &amp; beban operasional</span>
        </div>

        <div class="bg-gradient-to-r {{ $summary['net_profit'] >= 0 ? 'from-blue-900 to-indigo-900' : 'from-rose-950 to-red-900' }} text-white rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-blue-200">{{ $summary['net_profit'] >= 0 ? 'Laba Bersih Tahun Buku' : 'Rugi Bersih Tahun Buku' }}</span>
                <div class="text-2xl font-black font-mono mt-1">
                    Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}
                </div>
            </div>
            <span class="text-[11px] text-blue-300 mt-2 block">
                Akan dialokasikan ke: <strong>[{{ $summary['retained_earnings_account']->code }}] {{ $summary['retained_earnings_account']->name }}</strong>
            </span>
        </div>
    </div>

    <!-- Tombol Eksekusi Tutup Buku -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center space-x-2">
                <span>Konfirmasi Tutup Buku Tahun {{ $selectedYear }}</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 max-w-xl">
                Tindakan ini akan membuat 1 jurnal penutup resmi di tanggal 31 Desember {{ $selectedYear }}, mereset akun nominal untuk tahun depan, dan mengunci transaksi periode ini.
            </p>
        </div>

        <button type="button" onclick="document.getElementById('modalConfirmClosing').classList.remove('hidden')"
                class="inline-flex items-center space-x-2 px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition-all cursor-pointer">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>Eksekusi Tutup Buku Tahun {{ $selectedYear }}</span>
        </button>
    </div>

    <!-- Rincian Akun yang Akan Ditutup -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Akun Pendapatan -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                Akun Pendapatan yang Ditutup (Debit ke 0)
            </h4>
            <div class="overflow-x-auto max-h-64 divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($summary['revenue_accounts'] as $rev)
                <div class="py-2 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-slate-400 font-bold">[{{ $rev['code'] }}]</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 ml-1">{{ $rev['name'] }}</span>
                    </div>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($rev['balance'], 0, ',', '.') }}
                    </span>
                </div>
                @empty
                <div class="py-4 text-center text-slate-400 text-xs">Belum ada mutasi pendapatan pada tahun {{ $selectedYear }}.</div>
                @endforelse
            </div>
        </div>

        <!-- Akun Beban & HPP -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">
                Akun Beban &amp; HPP yang Ditutup (Kredit ke 0)
            </h4>
            <div class="overflow-x-auto max-h-64 divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                @forelse($summary['expense_accounts'] as $exp)
                <div class="py-2 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-slate-400 font-bold">[{{ $exp['code'] }}]</span>
                        <span class="font-bold text-slate-800 dark:text-slate-200 ml-1">{{ $exp['name'] }}</span>
                    </div>
                    <span class="font-mono font-bold text-rose-600 dark:text-rose-400">
                        Rp {{ number_format($exp['balance'], 0, ',', '.') }}
                    </span>
                </div>
                @empty
                <div class="py-4 text-center text-slate-400 text-xs">Belum ada mutasi beban pada tahun {{ $selectedYear }}.</div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <!-- Riwayat Tutup Buku Tahun Lalu -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white">
                📜 Riwayat Tutup Buku Tahunan (Audit Log)
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Tahun Buku</th>
                        <th class="py-3 px-4">Tanggal Cut-off</th>
                        <th class="py-3 px-4 text-right">Total Pendapatan</th>
                        <th class="py-3 px-4 text-right">Total Beban</th>
                        <th class="py-3 px-4 text-right">Laba/Rugi Bersih</th>
                        <th class="py-3 px-4">Status Periode</th>
                        <th class="py-3 px-4">Jurnal Penutup</th>
                        <th class="py-3 px-4">Dieksekusi Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($closings as $c)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-4 font-bold text-blue-600 dark:text-blue-400">
                            Tahun {{ $c->fiscal_year }}
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            {{ $c->closing_date ? \Carbon\Carbon::parse($c->closing_date)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($c->total_revenue, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-rose-600 dark:text-rose-400">
                            Rp {{ number_format($c->total_expense, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $c->net_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                            Rp {{ number_format($c->net_profit, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 font-bold text-[10px]">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono">
                            @if($c->closing_journal_entry_id)
                            <a href="{{ route('accounting.journals.show', $c->closing_journal_entry_id) }}" class="underline text-blue-600 dark:text-blue-400 hover:text-blue-700 font-bold">
                                {{ $c->closingJournalEntry?->entry_number ?? 'Lihat Jurnal' }}
                            </a>
                            @else
                            -
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $c->closer?->name ?? 'Sistem' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-400 text-xs">
                            Belum ada riwayat tutup buku tahunan yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Konfirmasi Tutup Buku -->
    <div id="modalConfirmClosing" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center space-x-3 text-amber-500">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/40 flex items-center justify-center text-xl">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white">Konfirmasi Tutup Buku Tahun {{ $selectedYear }}</h3>
                    <p class="text-[11px] text-slate-500">Tindakan ini memerlukan verifikasi akuntansi.</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                Anda akan menutup tahun buku <strong>{{ $selectedYear }}</strong> dengan rincian:
            </p>

            <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl space-y-1 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-500">Total Pendapatan:</span>
                    <span class="font-mono font-bold text-emerald-600">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Total Beban &amp; HPP:</span>
                    <span class="font-mono font-bold text-rose-600">Rp {{ number_format($summary['total_expense'], 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between pt-1 border-t border-slate-200 dark:border-slate-700">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Laba Bersih Dipindahkan:</span>
                    <span class="font-mono font-black text-blue-600 dark:text-blue-400">Rp {{ number_format($summary['net_profit'], 0, ',', '.') }}</span>
                </div>
            </div>

            <form action="{{ route('accounting.closing.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="fiscal_year" value="{{ $selectedYear }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Contoh: Tutup buku tahunan audited oleh Finance & Owner..."
                              class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="document.getElementById('modalConfirmClosing').classList.add('hidden')"
                            class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Batal</button>
                    <button type="submit"
                            class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20">
                        Ya, Tutup Buku Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
