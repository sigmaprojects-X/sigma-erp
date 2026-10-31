@extends('layouts.app')

@section('title', 'Rekap SPT Masa PPN (Pajak Pertambahan Nilai) - SIGMA ERP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <a href="{{ route('accounting.tax.settings') }}" class="hover:underline">Perpajakan</a>
                <span>/</span>
                <span>Rekap SPT Masa PPN</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📑 Rekapitulasi SPT Masa PPN (Formulir 1111)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Perhitungan otomatis Pajak Keluaran (Penjualan), Pajak Masukan (Pembelian), dan PPN Kurang/Lebih Bayar sesuai standar DJP.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('accounting.tax.settings') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>⚙️ Pengaturan Pajak</span>
            </a>
            <a href="{{ route('accounting.tax.report', ['start_date' => $startDate, 'end_date' => $endDate, 'export' => 'xlsx']) }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs hover:shadow transition-all">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Unduh Excel (.xlsx)</span>
            </a>
        </div>
    </div>

    <!-- Filter Periode -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs">
        <form action="{{ route('accounting.tax.report') }}" method="GET" class="flex flex-col md:flex-row md:items-end gap-4">
            <div>
                <label for="start_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mulai Tanggal</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="end_date" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}"
                       class="px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-semibold focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-all">
                    🔍 Filter Laporan
                </button>
                <a href="{{ route('accounting.tax.report', ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->endOfMonth()->toDateString()]) }}"
                   class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-semibold transition-all">
                    Bulan Ini
                </a>
            </div>

            <div class="md:ml-auto text-right">
                <span class="text-[11px] text-slate-400">Status Wajib Pajak:</span>
                <div class="flex items-center space-x-2 justify-end">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $company->is_tax_registered ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $company->is_tax_registered ? 'STATUS: PKP' : 'STATUS: NON-PKP' }}
                    </span>
                    <span class="font-mono text-xs text-slate-500">NPWP: {{ $company->tax_id ?: 'Belum diisi' }}</span>
                </div>
            </div>
        </form>
    </div>

    <!-- KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Pajak Keluaran (Penjualan) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="font-bold uppercase tracking-wider">PPN Keluaran</span>
                <span class="px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 font-mono text-[10px] font-bold">2003-001</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white">
                Rp {{ number_format($totalPpnKeluaran, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-slate-400 flex items-center justify-between">
                <span>DPP Penjualan PKP:</span>
                <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">Rp {{ number_format($totalDppSales, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- 2. Pajak Masukan (Pembelian) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
                <span class="font-bold uppercase tracking-wider">PPN Masukan</span>
                <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 font-mono text-[10px] font-bold">1006-001</span>
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white">
                Rp {{ number_format($totalPpnMasukan, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-slate-400 flex items-center justify-between">
                <span>DPP Pembelian PKP:</span>
                <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">Rp {{ number_format($totalDppPurchases, 2, ',', '.') }}</span>
            </div>
        </div>

        <!-- 3. Status PPN Kurang / Lebih Bayar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border {{ $statusPpn === 'KURANG_BAYAR' ? 'border-rose-200 dark:border-rose-900/50 bg-rose-50/20' : ($statusPpn === 'LEBIH_BAYAR' ? 'border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/20' : 'border-slate-200/80 dark:border-slate-800') }} p-5 shadow-xs">
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="font-bold uppercase tracking-wider {{ $statusPpn === 'KURANG_BAYAR' ? 'text-rose-600' : ($statusPpn === 'LEBIH_BAYAR' ? 'text-emerald-600' : 'text-slate-500') }}">
                    {{ $statusPpn === 'KURANG_BAYAR' ? 'PPN Kurang Bayar' : ($statusPpn === 'LEBIH_BAYAR' ? 'PPN Lebih Bayar' : 'PPN Nihil') }}
                </span>
                <span class="w-2 h-2 rounded-full {{ $statusPpn === 'KURANG_BAYAR' ? 'bg-rose-500' : ($statusPpn === 'LEBIH_BAYAR' ? 'bg-emerald-500' : 'bg-slate-400') }}"></span>
            </div>
            <div class="text-xl font-black {{ $statusPpn === 'KURANG_BAYAR' ? 'text-rose-700 dark:text-rose-400' : ($statusPpn === 'LEBIH_BAYAR' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">
                Rp {{ number_format(abs($netPpn), 2, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-slate-400">
                @if($statusPpn === 'KURANG_BAYAR')
                    ⚠️ Wajib disetor ke Kas Negara via SSE Pajak.
                @elseif($statusPpn === 'LEBIH_BAYAR')
                    ✓ Kompensasikan ke masa pajak berikutnya.
                @else
                    Pajak Keluaran sama dengan Pajak Masukan.
                @endif
            </div>
        </div>

        <!-- 4. Total Omzet Penyerahan -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs">
            <div class="text-xs text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider mb-1">
                Total Omzet Penjualan
            </div>
            <div class="text-xl font-black text-slate-900 dark:text-white">
                Rp {{ number_format($grandTotalSales, 2, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-slate-400 flex items-center justify-between">
                <span>Penyerahan Non-PKP:</span>
                <span class="font-mono font-semibold text-slate-600 dark:text-slate-300">Rp {{ number_format($totalNonTaxSales, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <!-- Ringkasan Formulir Induk SPT Masa 1111 -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center space-x-2">
                <span>📋 Ringkasan Komputasi SPT Masa PPN 1111</span>
            </h2>
            <span class="text-xs text-slate-400 font-semibold">Periode: {{ $startDate }} s/d {{ $endDate }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200/80 dark:border-slate-800">
                        <th class="py-2.5 px-4 font-bold w-12 text-center">No</th>
                        <th class="py-2.5 px-4 font-bold">Uraian Penghitungan Pajak (Formulir 1111)</th>
                        <th class="py-2.5 px-4 font-bold text-right">DPP (Dasar Pengenaan Pajak)</th>
                        <th class="py-2.5 px-4 font-bold text-right">PPN (Rupiah)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-700 dark:text-slate-300">
                    <!-- Bagian I -->
                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 font-bold">
                        <td class="py-2 px-4 text-center">I</td>
                        <td class="py-2 px-4" colspan="3">PENYERAHAN BKP / JKP (PAJAK KELUARAN)</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4 text-center text-slate-400">1.a</td>
                        <td class="py-2 px-4 pl-8">Penyerahan Terutang PPN (Faktur Pajak Keluaran Kasir)</td>
                        <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($totalDppSales, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 text-right font-mono font-bold text-blue-600">Rp {{ number_format($totalPpnKeluaran, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4 text-center text-slate-400">1.b</td>
                        <td class="py-2 px-4 pl-8">Penyerahan Tidak Terutang PPN / Dibebaskan</td>
                        <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($totalNonTaxSales, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 text-right font-mono text-slate-400">Rp 0,00</td>
                    </tr>
                    <tr class="font-bold bg-blue-50/40 dark:bg-blue-950/20">
                        <td class="py-2 px-4 text-center"></td>
                        <td class="py-2 px-4">TOTAL JUMLAH PAJAK KELUARAN (I.a)</td>
                        <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($totalDppSales + $totalNonTaxSales, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 text-right font-mono text-blue-700 dark:text-blue-400">Rp {{ number_format($totalPpnKeluaran, 2, ',', '.') }}</td>
                    </tr>

                    <!-- Bagian II -->
                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 font-bold">
                        <td class="py-2 px-4 text-center">II</td>
                        <td class="py-2 px-4" colspan="3">PEROLEHAN BKP / JKP (PAJAK MASUKAN)</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4 text-center text-slate-400">2.a</td>
                        <td class="py-2 px-4 pl-8">Perolehan Terutang PPN yang Dapat Dikreditkan (Faktur Pembelian)</td>
                        <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($totalDppPurchases, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 text-right font-mono font-bold text-emerald-600">Rp {{ number_format($totalPpnMasukan, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 px-4 text-center text-slate-400">2.b</td>
                        <td class="py-2 px-4 pl-8">Perolehan Tanpa PPN / Dari Rekanan Non-PKP</td>
                        <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($totalNonTaxPurchases, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 text-right font-mono text-slate-400">Rp 0,00</td>
                    </tr>
                    <tr class="font-bold bg-emerald-50/40 dark:bg-emerald-950/20">
                        <td class="py-2 px-4 text-center"></td>
                        <td class="py-2 px-4">TOTAL JUMLAH PAJAK MASUKAN DAPAT DIKREDITKAN (II.a)</td>
                        <td class="py-2 px-4 text-right font-mono">Rp {{ number_format($totalDppPurchases + $totalNonTaxPurchases, 2, ',', '.') }}</td>
                        <td class="py-2 px-4 text-right font-mono text-emerald-700 dark:text-emerald-400">Rp {{ number_format($totalPpnMasukan, 2, ',', '.') }}</td>
                    </tr>

                    <!-- Bagian III -->
                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 font-bold">
                        <td class="py-2 px-4 text-center">III</td>
                        <td class="py-2 px-4" colspan="3">PENGHITUNGAN PPN KURANG / (LEBIH) BAYAR</td>
                    </tr>
                    <tr class="font-black text-sm {{ $statusPpn === 'KURANG_BAYAR' ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300' : ($statusPpn === 'LEBIH_BAYAR' ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800') }}">
                        <td class="py-3 px-4 text-center"></td>
                        <td class="py-3 px-4">
                            @if($statusPpn === 'KURANG_BAYAR')
                                PPN KURANG BAYAR (Wajib Disetor ke Kas Negara)
                            @elseif($statusPpn === 'LEBIH_BAYAR')
                                PPN LEBIH BAYAR (Dapat Dikompensasi ke Masa Pajak Berikutnya)
                            @else
                                PPN NIHIL
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right text-xs font-normal text-slate-500">Selisih (I.a - II.a)</td>
                        <td class="py-3 px-4 text-right font-mono">Rp {{ number_format(abs($netPpn), 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Dual Table: Faktur Keluaran & Masukan -->
    <div x-data="{ activeTab: 'sales' }" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden">
        <!-- Tab Buttons -->
        <div class="flex border-b border-slate-200/80 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 px-6 pt-3 space-x-4">
            <button type="button" @click="activeTab = 'sales'"
                    :class="activeTab === 'sales' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all flex items-center space-x-2">
                <span>🛒 Faktur Pajak Keluaran (Penjualan)</span>
                <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 text-[10px] font-bold">
                    {{ $taxableSales->count() }} Transaksi
                </span>
            </button>

            <button type="button" @click="activeTab = 'purchases'"
                    :class="activeTab === 'purchases' ? 'border-emerald-600 text-emerald-600 dark:text-emerald-400 font-black' : 'border-transparent text-slate-500 hover:text-slate-800 font-semibold'"
                    class="py-2.5 px-3 border-b-2 text-xs transition-all flex items-center space-x-2">
                <span>📦 Faktur Pajak Masukan (Pembelian)</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 text-[10px] font-bold">
                    {{ $taxablePurchases->count() }} Transaksi
                </span>
            </button>
        </div>

        <!-- Tab 1: Penjualan -->
        <div x-show="activeTab === 'sales'" class="p-6 space-y-4">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200/80 dark:border-slate-800">
                            <th class="py-2.5 px-3 font-bold w-12 text-center">No</th>
                            <th class="py-2.5 px-3 font-bold">Tanggal</th>
                            <th class="py-2.5 px-3 font-bold">No. Invoice POS</th>
                            <th class="py-2.5 px-3 font-bold">Cabang Toko</th>
                            <th class="py-2.5 px-3 font-bold">Pelanggan</th>
                            <th class="py-2.5 px-3 font-bold text-center">Mode PPN</th>
                            <th class="py-2.5 px-3 font-bold text-right">DPP (Rp)</th>
                            <th class="py-2.5 px-3 font-bold text-right">PPN (Rp)</th>
                            <th class="py-2.5 px-3 font-bold text-right">Total Transaksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($taxableSales as $idx => $tx)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="py-2 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2 px-3 text-slate-600 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2 px-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ $tx->invoice_number }}
                            </td>
                            <td class="py-2 px-3">{{ $tx->store?->name ?? '-' }}</td>
                            <td class="py-2 px-3">{{ $tx->customer?->name ?? 'Pelanggan Umum' }}</td>
                            <td class="py-2 px-3 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $tx->tax_mode === 'INC' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ $tx->tax_mode ?? 'INC' }}
                                </span>
                            </td>
                            <td class="py-2 px-3 text-right font-mono">Rp {{ number_format($tx->subtotal, 2, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-blue-600">Rp {{ number_format($tx->tax_amount, 2, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format($tx->grand_total, 2, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-slate-400 italic">
                                Belum ada transaksi penjualan terutang PPN pada rentang tanggal ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($taxableSales->isNotEmpty())
                    <tfoot>
                        <tr class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200 dark:border-slate-700">
                            <td colspan="6" class="py-2.5 px-3 text-right">TOTAL PAJAK KELUARAN:</td>
                            <td class="py-2.5 px-3 text-right font-mono">Rp {{ number_format($totalDppSales, 2, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-blue-600 font-black">Rp {{ number_format($totalPpnKeluaran, 2, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">Rp {{ number_format($taxableSales->sum('grand_total'), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Tab 2: Pembelian -->
        <div x-show="activeTab === 'purchases'" class="p-6 space-y-4" style="display: none;">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-y border-slate-200/80 dark:border-slate-800">
                            <th class="py-2.5 px-3 font-bold w-12 text-center">No</th>
                            <th class="py-2.5 px-3 font-bold">Tanggal</th>
                            <th class="py-2.5 px-3 font-bold">No. Faktur Supplier</th>
                            <th class="py-2.5 px-3 font-bold">Ref. Internal</th>
                            <th class="py-2.5 px-3 font-bold">Supplier</th>
                            <th class="py-2.5 px-3 font-bold">NPWP Supplier</th>
                            <th class="py-2.5 px-3 font-bold text-right">DPP (Rp)</th>
                            <th class="py-2.5 px-3 font-bold text-right">PPN Masukan (Rp)</th>
                            <th class="py-2.5 px-3 font-bold text-right">Total Faktur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 font-medium text-slate-700 dark:text-slate-300">
                        @forelse($taxablePurchases as $idx => $p)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="py-2 px-3 text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="py-2 px-3 text-slate-600 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($p->purchase_date)->format('d/m/Y') }}
                            </td>
                            <td class="py-2 px-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $p->invoice_number }}
                            </td>
                            <td class="py-2 px-3 font-mono text-[11px] text-slate-500">{{ $p->reference_number ?? '-' }}</td>
                            <td class="py-2 px-3">{{ $p->supplier?->name ?? '-' }}</td>
                            <td class="py-2 px-3 font-mono text-[11px]">{{ $p->supplier?->npwp ?? '-' }}</td>
                            <td class="py-2 px-3 text-right font-mono">Rp {{ number_format($p->subtotal, 2, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format($p->tax_amount, 2, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right font-mono font-bold">Rp {{ number_format($p->grand_total, 2, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-slate-400 italic">
                                Belum ada faktur pembelian terutang PPN pada rentang tanggal ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($taxablePurchases->isNotEmpty())
                    <tfoot>
                        <tr class="bg-slate-50 dark:bg-slate-800 font-bold border-t border-slate-200 dark:border-slate-700">
                            <td colspan="6" class="py-2.5 px-3 text-right">TOTAL PAJAK MASUKAN:</td>
                            <td class="py-2.5 px-3 text-right font-mono">Rp {{ number_format($totalDppPurchases, 2, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-600 font-black">Rp {{ number_format($totalPpnMasukan, 2, ',', '.') }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">Rp {{ number_format($taxablePurchases->sum('grand_total'), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
