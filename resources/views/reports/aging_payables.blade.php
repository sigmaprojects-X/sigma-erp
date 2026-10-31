@extends('layouts.app')

@section('title', 'Analisa Umur Hutang (Aging AP)')
@section('tab_title', 'Aging Hutang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    @php
        $selectedStoreName = $storeId ? ($stores->firstWhere('id', $storeId)?->name ?? 'Cabang Terpilih') : 'Semua Cabang Toko (Konsolidasi Pusat)';
    @endphp

    <!-- OFFICIAL CORPORATE PRINT LETTERHEAD (KOP SURAT RESMI) -->
    @include('reports.partials.print-header', [
        'title' => 'LAPORAN ANALISA UMUR HUTANG USAHA (AGING ACCOUNTS PAYABLE)',
        'subtitle' => 'Audit Jadwal Jatuh Tempo Kewajiban Pembelian Barang Dagang ke Supplier',
        'docCode' => 'SIGMA/RPT-AGP/' . now()->format('Ymd') . '/' . strtoupper(substr(md5($report['as_of_date'] . ($storeId ?? 'ALL')), 0, 4)),
        'storeName' => $selectedStoreName,
        'periodLabel' => 'Per Tanggal: ' . \Carbon\Carbon::parse($report['as_of_date'])->format('d F Y'),
        'extraFilters' => [
            'Total Hutang' => 'Rp ' . number_format($report['grand_total'] ?? 0, 0, ',', '.'),
            'Status AP' => 'Jatuh Tempo Berjalan',
        ]
    ])

    <!-- BREADCRUMB & HEADER (WEB VIEW ONLY) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Laporan Keuangan &amp; Hutang</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Aging Accounts Payable</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⏱️ Laporan Analisa Umur Hutang Dagang (Aging AP)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pemantauan jadwal jatuh tempo tagihan pembelian barang ke supplier untuk menjaga arus kas dan reputasi bisnis.
            </p>
        </div>

        <div class="flex items-center gap-2 print:hidden">
            <button onclick="window.print()"
                    class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors cursor-pointer">
                🖨️ Cetak Analisa
            </button>
            <a href="{{ route('purchases.debt-ledger') }}"
               class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <span>📑 Buku Pembantu Hutang</span>
            </a>
        </div>
    </div>

    <!-- FILTER CABANG & PERIODE AS-OF -->
    <form method="GET" action="{{ route('reports.aging-payables') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex items-center gap-3 text-xs print:hidden">
        <div class="w-48">
            <label class="block text-[10px] font-bold text-slate-400 mb-0.5">Filter Cabang Toko</label>
            <select name="store_id" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg font-semibold text-xs">
                <option value="">Semua Cabang Toko</option>
                @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ $storeId === $st->id ? 'selected' : '' }}>
                        {{ $st->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="pt-3.5 flex items-center gap-1.5">
            <button type="submit" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs cursor-pointer">
                Terapkan
            </button>
            <span class="text-xs text-slate-400 ml-2">Per tanggal: <strong class="text-slate-700 dark:text-slate-200">{{ \Carbon\Carbon::parse($report['as_of_date'])->format('d F Y') }}</strong></span>
        </div>
    </form>

    <!-- BUCKETS AGING 5 KARTU (WEB VIEW ONLY) -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 print:hidden">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Belum Jatuh Tempo</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($report['aging_buckets']['current'], 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Aman / Berjalan</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">1 - 30 Hari</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($report['aging_buckets']['days_1_30'], 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Jatuh tempo dekat</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">31 - 60 Hari</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($report['aging_buckets']['days_31_60'], 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Prioritas pelunasan</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">61 - 90 Hari</span>
            <div class="text-base font-black text-rose-600 dark:text-rose-400 mt-1">
                Rp {{ number_format($report['aging_buckets']['days_61_90'], 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Tertunggak lama</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-rose-200 dark:border-rose-900/60 shadow-xs bg-rose-50/20">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">&gt; 90 Hari</span>
            <div class="text-base font-black text-rose-700 dark:text-rose-300 mt-1">
                Rp {{ number_format($report['aging_buckets']['days_over_90'], 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-rose-500 font-bold">Harus segera dilunasi!</span>
        </div>
    </div>

    <!-- PRINT-ONLY EXECUTIVE AGING SUMMARY STRIP -->
    <div class="hidden print:block print-avoid-break mb-3">
        <div class="text-[7.5pt] font-black uppercase tracking-wider text-slate-800 mb-1 flex items-center justify-between border-b border-slate-300 pb-0.5">
            <span>I. REKAPITULASI BUCKET UMUR HUTANG USAHA (AGING AP)</span>
            <span class="text-slate-500 font-mono font-normal">Mata Uang: IDR (Rupiah)</span>
        </div>
        <table class="w-full text-center text-[7.5pt] border-collapse">
            <thead>
                <tr class="bg-slate-100 font-bold text-slate-800">
                    <th class="py-1 px-2 border border-slate-400">Belum Jatuh Tempo</th>
                    <th class="py-1 px-2 border border-slate-400">1 - 30 Hari</th>
                    <th class="py-1 px-2 border border-slate-400">31 - 60 Hari</th>
                    <th class="py-1 px-2 border border-slate-400">61 - 90 Hari</th>
                    <th class="py-1 px-2 border border-slate-400">&gt; 90 Hari (Macet)</th>
                    <th class="py-1 px-2 border border-slate-400 text-right">Total Kewajiban (AP)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="font-mono font-bold">
                    <td class="py-1 px-2 border border-slate-300 text-emerald-700">
                        Rp {{ number_format($report['aging_buckets']['current'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-amber-700">
                        Rp {{ number_format($report['aging_buckets']['days_1_30'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-orange-700">
                        Rp {{ number_format($report['aging_buckets']['days_31_60'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-rose-600">
                        Rp {{ number_format($report['aging_buckets']['days_61_90'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-rose-800">
                        Rp {{ number_format($report['aging_buckets']['days_over_90'], 0, ',', '.') }}
                    </td>
                    <td class="py-1 px-2 border border-slate-300 text-slate-900 text-right">
                        Rp {{ number_format($report['grand_total'], 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABEL AGING HUTANG PER SUPPLIER -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Rincian Saldo Kewajiban Hutang per Supplier</h3>
                <p class="text-[11px] text-slate-400">Total Keseluruhan Hutang: <strong class="text-slate-800 dark:text-slate-200 font-mono">Rp {{ number_format($report['grand_total'], 0, ',', '.') }}</strong></p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Nama Supplier</th>
                        <th class="py-3 px-4">No. Kontak</th>
                        <th class="py-3 px-4 text-right">Lancar (Current)</th>
                        <th class="py-3 px-4 text-right text-amber-600">1 - 30 Hari</th>
                        <th class="py-3 px-4 text-right text-orange-600">31 - 60 Hari</th>
                        <th class="py-3 px-4 text-right text-rose-500">61 - 90 Hari</th>
                        <th class="py-3 px-4 text-right text-rose-700 font-black">&gt; 90 Hari</th>
                        <th class="py-3 px-4 text-right font-black">Total Hutang (AP)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($report['suppliers'] as $sup)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">{{ $sup['supplier_name'] }}</td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $sup['supplier_phone'] }}</td>
                        <td class="py-3 px-4 text-right font-mono {{ $sup['current'] > 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-300 dark:text-slate-700' }}">
                            {{ $sup['current'] > 0 ? 'Rp ' . number_format($sup['current'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $sup['days_1_30'] > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-300 dark:text-slate-700' }}">
                            {{ $sup['days_1_30'] > 0 ? 'Rp ' . number_format($sup['days_1_30'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $sup['days_31_60'] > 0 ? 'text-orange-600 dark:text-orange-400 font-bold' : 'text-slate-300 dark:text-slate-700' }}">
                            {{ $sup['days_31_60'] > 0 ? 'Rp ' . number_format($sup['days_31_60'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $sup['days_61_90'] > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-300 dark:text-slate-700' }}">
                            {{ $sup['days_61_90'] > 0 ? 'Rp ' . number_format($sup['days_61_90'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono {{ $sup['days_over_90'] > 0 ? 'text-rose-700 dark:text-rose-300 font-black bg-rose-50 dark:bg-rose-950/40' : 'text-slate-300 dark:text-slate-700' }}">
                            {{ $sup['days_over_90'] > 0 ? 'Rp ' . number_format($sup['days_over_90'], 0, ',', '.') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-black text-slate-900 dark:text-white">
                            Rp {{ number_format($sup['total_payable'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            🎉 Bersih! Tidak ada kewajiban hutang supplier yang outstanding saat ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- OFFICIAL CORPORATE SIGNATURE & APPROVAL BLOCK -->
    @include('reports.partials.print-signatures')
</div>
@endsection
