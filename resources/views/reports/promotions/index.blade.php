@extends('layouts.app')

@section('title', 'Laporan Poin, Voucher, Promo & Diskon Penjualan - SIGMA ERP')
@section('tab_title', 'Laporan Promosi & Poin')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800 print:hidden">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Laporan Bisnis &amp; Ritel</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Laporan Diskon, Voucher &amp; Poin</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>🎁 Laporan Poin, Voucher, Promo &amp; Diskon Penjualan</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Audit komprehensif seluruh penghematan konsumen dari diskon produk, voucher belanja, dan penukaran poin reward member.
            </p>
        </div>

        <div class="flex items-center gap-2 print:hidden">
            <a href="{{ route('reports.promotions.print', request()->query()) }}" target="_blank"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <span>🖨️ Cetak / Print A4</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
                <span>⚡ BUKA KASIR [F12]</span>
            </a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs print:hidden">
        <form method="GET" action="{{ route('reports.promotions') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <!-- TANGGAL AWAL -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Dari Tanggal:</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                       class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <!-- TANGGAL AKHIR -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                       class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white">
            </div>

            <!-- CABANG / TOKO -->
            <div class="lg:col-span-3">
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Cabang Toko:</label>
                <select name="store_id"
                        class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white">
                    <option value="">Semua Cabang Toko</option>
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>
                            {{ $st->code }} - {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- TOMBOL AKSI FILTER -->
            <div class="lg:col-span-3 flex items-center gap-2">
                <button type="submit"
                        class="flex-1 py-2 px-4 bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-700 text-white font-black text-xs rounded-lg shadow-sm transition-colors cursor-pointer">
                    Filter Laporan
                </button>
                <a href="{{ route('reports.promotions') }}"
                   class="py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-lg transition-colors">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- METRIK CARD RINGKASAN EFISIENSI PROMOSI -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <!-- 1. TOTAL PENGHEMATAN KONSUMEN -->
        <div class="bg-gradient-to-br from-indigo-500/10 via-white to-white dark:from-indigo-950/40 dark:via-slate-800 dark:to-slate-800 border border-indigo-200 dark:border-indigo-800/80 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 border-b border-indigo-100 dark:border-slate-700">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Potongan (Semua)</span>
                <span class="text-lg">💰</span>
            </div>
            <div class="pt-2">
                <span class="text-xl font-black text-indigo-700 dark:text-indigo-400 block font-mono">
                    Rp {{ number_format($totalCustomerSavings, 0, ',', '.') }}
                </span>
                <span class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5 block">
                    dari <strong>{{ number_format($promoTxCount) }}</strong> transaksi promo
                </span>
            </div>
        </div>

        <!-- 2. TOTAL DISKON BARANG / TIER -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Diskon Produk/Tier</span>
                <span class="text-lg">🏷️</span>
            </div>
            <div class="pt-2">
                <span class="text-xl font-black text-rose-600 dark:text-rose-400 block font-mono">
                    Rp {{ number_format($totalItemDiscounts, 0, ',', '.') }}
                </span>
                <span class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5 block">
                    Diskon langsung produk di kasir
                </span>
            </div>
        </div>

        <!-- 3. TOTAL POTONGAN VOUCHER -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Potongan Voucher</span>
                <span class="text-lg">🎟️</span>
            </div>
            <div class="pt-2">
                <span class="text-xl font-black text-blue-600 dark:text-blue-400 block font-mono">
                    Rp {{ number_format($totalVoucherDiscounts, 0, ',', '.') }}
                </span>
                <span class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5 block">
                    <strong>{{ number_format($vouchersUsedCount) }}</strong> kali voucher ditukar
                </span>
            </div>
        </div>

        <!-- 4. TOTAL POTONGAN POIN MEMBER -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-700">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Potongan Poin Member</span>
                <span class="text-lg">⭐</span>
            </div>
            <div class="pt-2">
                <span class="text-xl font-black text-amber-600 dark:text-amber-400 block font-mono">
                    Rp {{ number_format($totalPointsDiscounts, 0, ',', '.') }}
                </span>
                <span class="text-[10.5px] text-slate-500 dark:text-slate-400 mt-0.5 block">
                    <strong>{{ number_format($totalPointsRedeemed) }} Pts</strong> ditukar (+{{ number_format($totalPointsEarned) }} Pts baru)
                </span>
            </div>
        </div>
    </div>

    <!-- REKAPITULASI HARIAN PROMOSI -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Rekapitulasi Promosi &amp; Diskon Harian</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Distribusi nilai diskon, voucher, dan penukaran poin per hari transaksi</p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                {{ $dailyBreakdown->count() }} Hari Transaksi
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Tanggal Transaksi</th>
                        <th class="py-3 px-4 text-center">Jml Transaksi</th>
                        <th class="py-3 px-4 text-right">Diskon Produk</th>
                        <th class="py-3 px-4 text-right">Potongan Voucher</th>
                        <th class="py-3 px-4 text-right">Potongan Poin ⭐</th>
                        <th class="py-3 px-4 text-center">Poin Ditukar / Didapat</th>
                        <th class="py-3 px-4 text-right">Total Penghematan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($dailyBreakdown as $d)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($d->tx_date)->isoFormat('dddd, D MMMM Y') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono font-bold">
                                {{ number_format($d->total_tx) }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($d->daily_discount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-blue-600 dark:text-blue-400">
                                Rp {{ number_format($d->daily_voucher, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono text-amber-600 dark:text-amber-400">
                                Rp {{ number_format($d->daily_points_discount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-[11px]">
                                <span class="text-rose-600">-{{ number_format($d->daily_points_redeemed) }}</span>
                                <span class="text-slate-400">/</span>
                                <span class="text-emerald-600">+{{ number_format($d->daily_points_earned) }}</span> Pts
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-black text-indigo-700 dark:text-indigo-400">
                                Rp {{ number_format($d->total_savings, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Tidak ada data promosi harian pada rentang tanggal terpilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB NAVIGASI RINCIAN TRANSAKSI -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto print:hidden">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $tab === 'all' ? 'bg-slate-900 text-white dark:bg-blue-600' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700' }} flex items-center gap-1.5 whitespace-nowrap">
            <span>Semua Transaksi Promosi</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'discounts']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $tab === 'discounts' ? 'bg-rose-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700' }} flex items-center gap-1.5 whitespace-nowrap">
            <span>🏷️ Diskon Produk</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'vouchers']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $tab === 'vouchers' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700' }} flex items-center gap-1.5 whitespace-nowrap">
            <span>🎟️ Voucher Belanja</span>
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'points']) }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all {{ $tab === 'points' ? 'bg-amber-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700' }} flex items-center gap-1.5 whitespace-nowrap">
            <span>⭐ Poin Member</span>
        </a>
    </div>

    <!-- TABEL RINCIAN TRANSAKSI PROMOSI -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Rincian Faktur Transaksi Kasir POS</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Daftar transaksi kasir yang menerapkan diskon item, voucher, atau penukaran poin member</p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300">
                {{ $transactions->total() }} Faktur Ditemukan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">No. Faktur POS</th>
                        <th class="py-3 px-4">Waktu Transaksi</th>
                        <th class="py-3 px-4">Pelanggan / Member</th>
                        <th class="py-3 px-4 text-right">Diskon Produk</th>
                        <th class="py-3 px-4 text-right">Potongan Voucher</th>
                        <th class="py-3 px-4 text-right">Potongan Poin ⭐</th>
                        <th class="py-3 px-4 text-right">Grand Total</th>
                        <th class="py-3 px-4 text-center">Poin Didapat</th>
                        <th class="py-3 px-4 text-right">Kasir Bertugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($transactions as $t)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <a href="{{ route('sales.transactions.show', $t->id) }}"
                                   class="font-mono font-black text-blue-600 dark:text-blue-400 hover:underline">
                                    {{ $t->invoice_number }}
                                </a>
                                <span class="block text-[10px] text-slate-400">{{ $t->store?->name }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $t->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $t->customer?->name ?? 'Pelanggan Umum' }}</div>
                                <span class="text-[10px] text-slate-400">{{ $t->customerTier?->name ?? 'Retail' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono {{ $t->discount_amount > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-400' }}">
                                {{ $t->discount_amount > 0 ? '-Rp '.number_format($t->discount_amount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono {{ $t->voucher_discount > 0 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-400' }}">
                                {{ $t->voucher_discount > 0 ? '-Rp '.number_format($t->voucher_discount, 0, ',', '.') : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono {{ $t->points_discount_amount > 0 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-slate-400' }}">
                                @if($t->points_discount_amount > 0)
                                    <div>-Rp {{ number_format($t->points_discount_amount, 0, ',', '.') }}</div>
                                    <span class="text-[10px] text-amber-600 font-normal">({{ $t->points_redeemed }} Pts)</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-black text-slate-900 dark:text-white">
                                Rp {{ number_format($t->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono {{ $t->points_earned > 0 ? 'text-emerald-600 font-bold' : 'text-slate-400' }}">
                                {{ $t->points_earned > 0 ? '+'.$t->points_earned.' Pts' : '-' }}
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $t->cashier?->name ?? 'Kasir POS' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                Tidak ada transaksi promosi atau diskon pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
