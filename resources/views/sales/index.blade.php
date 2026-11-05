@extends('layouts.app')

@section('title', 'Dashboard Penjualan')
@section('page_title', 'Dashboard Penjualan & Ringkasan Bisnis')

@section('content')
<div class="space-y-5 md:space-y-6 animate-in fade-in duration-200"
     x-data="salesDashboardAnalytics({{ json_encode($initialTrendData) }}, {{ json_encode($initialBranchData) }}, '{{ route('sales.analytics') }}')">

    <section class="flex flex-col gap-4 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/90 md:flex-row md:items-center md:justify-between md:p-5">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[.16em] text-blue-700 dark:text-blue-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Ringkasan operasional
            </div>
            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white md:text-2xl">
                {{ $company->name ?? 'SIGMA ERP & POS' }}
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ $store->name ?? 'Semua cabang' }} <span class="px-1.5 text-slate-300">•</span> {{ now()->translatedFormat('l, d F Y') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Sistem aktif
            </span>
            <a href="{{ route('sales.transactions') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                Daftar Penjualan
            </a>
            <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-emerald-900/15 transition hover:-translate-y-0.5">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.2 11.1a2 2 0 002 1.6h8.9a2 2 0 001.9-1.4L22 8H6M10 20a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                Buka Kasir POS <kbd class="rounded bg-white/20 px-1.5 py-0.5 text-[10px]">F12</kbd>
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sky-500 via-blue-600 to-blue-800 p-4 text-white shadow-lg shadow-blue-900/10">
            <div class="absolute -right-5 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-blue-100">Omzet Penjualan</p><p class="mt-0.5 text-[11px] text-blue-100/80">Hari ini</p></div><span class="rounded-xl bg-white/15 p-2.5"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.2 11.1a2 2 0 002 1.6h8.9a2 2 0 001.9-1.4L22 8H6M10 20a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg></span></div>
            <p class="relative mt-4 truncate text-2xl font-extrabold tracking-tight">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            <p class="relative mt-1 text-[11px] font-medium text-blue-100">Total penjualan berhasil</p>
        </article>
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-400 via-teal-600 to-emerald-800 p-4 text-white shadow-lg shadow-teal-900/10">
            <div class="absolute -right-5 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-teal-50">Total Transaksi</p><p class="mt-0.5 text-[11px] text-teal-50/80">Hari ini</p></div><span class="rounded-xl bg-white/15 p-2.5"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span></div>
            <p class="relative mt-4 text-3xl font-extrabold tracking-tight">{{ number_format($todayCount, 0, ',', '.') }}</p>
            <p class="relative mt-1 text-[11px] font-medium text-teal-50">Struk penjualan berhasil</p>
        </article>
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-indigo-800 p-4 text-white shadow-lg shadow-violet-900/10">
            <div class="absolute -right-5 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-violet-50">Kas Masuk</p><p class="mt-0.5 text-[11px] text-violet-50/80">Tunai + transfer hari ini</p></div><span class="rounded-xl bg-white/15 p-2.5"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.2 0-4 .9-4 2s1.8 2 4 2 4 .9 4 2-1.8 2-4 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span></div>
            <p class="relative mt-4 truncate text-2xl font-extrabold tracking-tight">Rp {{ number_format($todayCashCollected + $todayTransferCollected, 0, ',', '.') }}</p>
            <p class="relative mt-1 truncate text-[11px] font-medium text-violet-50">Tunai Rp {{ number_format($todayCashCollected, 0, ',', '.') }}</p>
        </article>
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-400 via-orange-500 to-rose-600 p-4 text-white shadow-lg shadow-orange-900/10">
            <div class="absolute -right-5 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-orange-50">Retur Penjualan</p><p class="mt-0.5 text-[11px] text-orange-50/80">Hari ini</p></div><span class="rounded-xl bg-white/15 p-2.5"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M5 9a7 7 0 0112-3l3 3M19 15a7 7 0 01-12 3l-3-3"/></svg></span></div>
            <p class="relative mt-4 truncate text-2xl font-extrabold tracking-tight">Rp {{ number_format($todayReturnsAmount, 0, ',', '.') }}</p>
            <p class="relative mt-1 text-[11px] font-medium text-orange-50">{{ number_format($todayReturnsCount, 0, ',', '.') }} nota retur</p>
        </article>
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 via-cyan-700 to-sky-900 p-4 text-white shadow-lg shadow-cyan-900/10">
            <div class="absolute -right-5 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-cyan-50">Penjualan Tempo</p><p class="mt-0.5 text-[11px] text-cyan-50/80">Kredit hari ini</p></div><span class="rounded-xl bg-white/15 p-2.5"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13H4V6a1 1 0 011-1z"/></svg></span></div>
            <p class="relative mt-4 truncate text-2xl font-extrabold tracking-tight">Rp {{ number_format($todayCredit, 0, ',', '.') }}</p>
            <p class="relative mt-1 text-[11px] font-medium text-cyan-50">Nilai transaksi kredit hari ini</p>
        </article>
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-900 p-4 text-white shadow-lg shadow-indigo-900/10">
            <div class="absolute -right-5 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
            <div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold text-indigo-100">Rata-rata Transaksi</p><p class="mt-0.5 text-[11px] text-indigo-100/80">Hari ini</p></div><span class="rounded-xl bg-white/15 p-2.5"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-9M15 6h6v6"/></svg></span></div>
            <p class="relative mt-4 truncate text-2xl font-extrabold tracking-tight">Rp {{ number_format($todayCount > 0 ? $todayRevenue / $todayCount : 0, 0, ',', '.') }}</p>
            <p class="relative mt-1 text-[11px] font-medium text-indigo-100">Omzet dibagi jumlah struk</p>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:p-5 xl:col-span-7">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-bold text-slate-900 dark:text-white">Tren Penjualan</h3><p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Omzet harian transaksi sah (tidak termasuk void)</p></div>
                <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    <button type="button" @click="setTrendPeriod('7_days')" :class="trendPeriod === '7_days' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-3 py-1.5 text-xs font-bold">7 Hari</button>
                    <button type="button" @click="setTrendPeriod('30_days')" :class="trendPeriod === '30_days' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-3 py-1.5 text-xs font-bold">30 Hari</button>
                    <button type="button" @click="setTrendPeriod('this_month')" :class="trendPeriod === 'this_month' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-3 py-1.5 text-xs font-bold">Bulan ini</button>
                </div>
            </div>
            <div class="flex items-center justify-between py-3 text-xs"><span class="text-slate-500 dark:text-slate-400">Akumulasi periode</span><strong class="font-mono text-blue-700 dark:text-blue-300" x-text="trendData?.formatted_total || 'Rp 0'">{{ $initialTrendData['formatted_total'] }}</strong></div>
            <div class="relative h-64 md:h-72">
                <div x-show="trendLoading" x-cloak class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/80 text-sm font-semibold text-blue-700 backdrop-blur-sm dark:bg-slate-900/80 dark:text-blue-300">Memuat data tren…</div>
                <div x-show="trendError" x-cloak class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-xl bg-white text-center dark:bg-slate-900"><p class="text-sm font-semibold text-rose-600" x-text="trendError"></p><button type="button" @click="fetchAnalytics('trend')" class="mt-2 text-xs font-bold text-blue-700 underline">Coba lagi</button></div>
                <canvas id="trendSalesChart" class="h-full w-full"></canvas>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:p-5 xl:col-span-5">
            <div class="flex flex-col gap-3 border-b border-slate-100 pb-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-bold text-slate-900 dark:text-white">Perbandingan Cabang</h3><p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Kontribusi omzet outlet aktif</p></div>
                <div class="inline-flex flex-wrap rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                    <button type="button" @click="setBranchPeriod('today')" :class="branchPeriod === 'today' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold">Hari ini</button>
                    <button type="button" @click="setBranchPeriod('7_days')" :class="branchPeriod === '7_days' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold">7 Hari</button>
                    <button type="button" @click="setBranchPeriod('30_days')" :class="branchPeriod === '30_days' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold">30 Hari</button>
                    <button type="button" @click="setBranchPeriod('this_month')" :class="branchPeriod === 'this_month' ? 'bg-blue-600 text-white shadow' : 'text-slate-500 dark:text-slate-300'" class="rounded-lg px-2.5 py-1.5 text-xs font-bold">Bulan ini</button>
                </div>
            </div>
            <div class="flex items-center justify-between py-3 text-xs"><span class="text-slate-500 dark:text-slate-400">Total seluruh cabang</span><strong class="font-mono text-emerald-700 dark:text-emerald-300" x-text="branchData?.formatted_total || 'Rp 0'">{{ $initialBranchData['formatted_total'] }}</strong></div>
            <div class="relative grid min-h-[250px] grid-cols-1 items-center gap-3 md:grid-cols-2">
                <div class="relative mx-auto h-56 w-full max-w-[250px]">
                    <div x-show="branchLoading" x-cloak class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/80 text-sm font-semibold text-blue-700 dark:bg-slate-900/80 dark:text-blue-300">Memuat cabang…</div>
                    <div x-show="branchError" x-cloak class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-xl bg-white text-center dark:bg-slate-900"><p class="text-xs font-semibold text-rose-600" x-text="branchError"></p><button type="button" @click="fetchAnalytics('branch')" class="mt-2 text-xs font-bold text-blue-700 underline">Coba lagi</button></div>
                    <div x-show="!branchLoading && (!branchData?.details || branchData.details.length === 0)" x-cloak class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white px-4 text-center text-xs text-slate-500 dark:bg-slate-900">Belum ada data cabang</div>
                    <canvas id="branchSalesChart" class="h-full w-full"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Total Omzet</span>
                        <strong class="mt-1 max-w-[130px] truncate text-center text-sm font-extrabold text-slate-900 dark:text-white" x-text="branchData?.formatted_total || 'Rp 0'">{{ $initialBranchData['formatted_total'] }}</strong>
                    </div>
                </div>
                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                    <template x-for="(branch, index) in (branchData?.details || [])" :key="branch.store_id">
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/70">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :style="'background-color:' + branchColors[index % branchColors.length]"></span>
                            <div class="min-w-0 flex-1"><p class="truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="branch.store_name"></p><p class="mt-0.5 text-[11px] text-slate-500" x-text="branch.formatted_sales"></p></div>
                            <span class="shrink-0 text-[10px] text-slate-400" x-text="branch.tx_count + ' trx'"></span>
                        </div>
                    </template>
                </div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:col-span-4">
            <header class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Top 5 Produk Terlaris</h3><p class="mt-0.5 text-xs text-slate-500">Jumlah terjual dan nilai penjualan</p></div><span class="rounded-lg bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">PRODUK</span></header>
            <div class="overflow-x-auto p-3"><table class="w-full text-left text-xs"><thead class="text-[10px] uppercase tracking-wide text-slate-400"><tr><th class="px-2 py-2">Produk</th><th class="px-2 py-2 text-right">Qty</th><th class="px-2 py-2 text-right">Omzet</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($topProducts as $prod)
                <tr><td class="max-w-[180px] px-2 py-3"><p class="truncate font-bold text-slate-800 dark:text-slate-100">{{ $prod->product->name ?? 'Produk' }}</p><p class="mt-0.5 truncate font-mono text-[10px] text-slate-400">{{ $prod->product->sku ?? '-' }}</p></td><td class="px-2 py-3 text-right font-mono font-semibold text-blue-700 dark:text-blue-300">{{ number_format($prod->total_qty, 0, ',', '.') }}</td><td class="whitespace-nowrap px-2 py-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100">Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}</td></tr>
                @empty <tr><td colspan="3" class="px-2 py-8 text-center text-slate-500">Belum ada data produk</td></tr> @endforelse
            </tbody></table></div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:col-span-3">
            <header class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Kinerja Kasir</h3><p class="mt-0.5 text-xs text-slate-500">Transaksi per operator</p></div><span class="rounded-lg bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">KASIR</span></header>
            <div class="max-h-[330px] space-y-2 overflow-y-auto p-3">
                @forelse($salesByCashier as $cashierData)
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-xs font-black text-white">{{ strtoupper(substr($cashierData->cashier->name ?? 'K', 0, 1)) }}</span><div class="min-w-0 flex-1"><p class="truncate text-xs font-bold text-slate-800 dark:text-slate-100">{{ $cashierData->cashier->name ?? 'Kasir #' . substr($cashierData->cashier_id, 0, 6) }}</p><p class="mt-0.5 text-[10px] text-slate-500">{{ number_format($cashierData->tx_count, 0, ',', '.') }} transaksi</p></div><p class="shrink-0 text-right text-[11px] font-bold text-slate-700 dark:text-slate-200">Rp {{ number_format($cashierData->total_revenue, 0, ',', '.') }}</p></div>
                @empty <div class="py-10 text-center text-xs text-slate-500">Belum ada transaksi kasir</div> @endforelse
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:col-span-5">
            <header class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Transaksi Terbaru</h3><p class="mt-0.5 text-xs text-slate-500">10 transaksi terakhir</p></div><a href="{{ route('sales.transactions') }}" class="text-xs font-bold text-blue-700 hover:text-blue-900 dark:text-blue-300">Lihat semua →</a></header>
            <div class="max-h-[330px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($recentTransactions as $tx)
                <div class="flex items-center gap-3 px-4 py-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $tx->payment_method === 'TEMPO' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h4"/></svg></span>
                    <div class="min-w-0 flex-1"><p class="truncate font-mono text-xs font-bold text-slate-800 dark:text-slate-100">{{ $tx->invoice_number }}</p><p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $tx->customer->name ?? 'Pelanggan Umum' }} · {{ $tx->created_at->format('d/m H:i') }}</p></div>
                    <div class="shrink-0 text-right"><p class="text-xs font-bold text-slate-900 dark:text-white">Rp {{ number_format($tx->grand_total, 0, ',', '.') }}</p><p class="mt-0.5 text-[10px] text-slate-500">{{ $tx->payment_method === 'TEMPO' ? 'Tempo' : $tx->payment_method }}</p><div class="mt-1 flex justify-end gap-1"><a href="{{ route('pos.print', ['id' => $tx->id, 'paper' => '58']) }}" target="_blank" title="Cetak struk" class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">58mm</a><a href="{{ route('sales.print-invoice', $tx->id) }}" target="_blank" title="Cetak faktur A4" class="rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300">A4</a></div></div>
                </div>
                @empty <div class="py-12 text-center text-xs text-slate-500">Belum ada transaksi hari ini</div> @endforelse
            </div>
        </article>
    </section>
</div>
@push('scripts')
<script>
function salesDashboardAnalytics(initialTrend, initialBranch, analyticsUrl) {
    return {
        trendPeriod: '7_days',
        branchPeriod: 'this_month',
        trendLoading: false,
        branchLoading: false,
        trendError: null,
        branchError: null,
        trendChart: null,
        branchChart: null,
        branchColors: ['#0878F9', '#08B9E8', '#08B88A', '#8B5CF6', '#F59E0B', '#EF5577', '#6366F1', '#14B8A6'],
        trendData: initialTrend,
        branchData: initialBranch,

        init() {
            this.$nextTick(() => {
                this.renderTrendChart();
                this.renderBranchChart();
            });
        },

        setTrendPeriod(period) {
            if (this.trendPeriod === period || this.trendLoading) return;
            this.trendPeriod = period;
            this.fetchAnalytics('trend');
        },

        setBranchPeriod(period) {
            if (this.branchPeriod === period || this.branchLoading) return;
            this.branchPeriod = period;
            this.fetchAnalytics('branch');
        },

        async fetchAnalytics(type) {
            if (type === 'trend') this.trendLoading = true;
            if (type === 'branch') this.branchLoading = true;

            try {
                const url = `${analyticsUrl}?trend_period=${this.trendPeriod}&branch_period=${this.branchPeriod}`;
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Gagal memuat data analitik dari server');
                const json = await res.json();

                if (type === 'trend' || type === 'all') {
                    this.trendData = json.trend;
                    this.updateTrendChart();
                    this.trendError = null;
                }
                if (type === 'branch' || type === 'all') {
                    this.branchData = json.branch;
                    this.updateBranchChart();
                    this.branchError = null;
                }
            } catch (err) {
                console.error('Analytics Error:', err);
                if (type === 'trend') this.trendError = 'Terjadi kesalahan saat memuat data tren';
                if (type === 'branch') this.branchError = 'Terjadi kesalahan saat memuat data cabang';
            } finally {
                this.trendLoading = false;
                this.branchLoading = false;
            }
        },

        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 0
            }).format(num || 0);
        },

        formatCompact(num) {
            if (num >= 1000000000) return 'Rp ' + (num / 1000000000).toFixed(1) + 'M';
            if (num >= 1000000) return 'Rp ' + (num / 1000000).toFixed(1) + 'jt';
            if (num >= 1000) return 'Rp ' + (num / 1000).toFixed(0) + 'rb';
            return 'Rp ' + num;
        },

        renderTrendChart() {
            const ctx = document.getElementById('trendSalesChart');
            if (!ctx || !window.Chart) return;

            const labels = this.trendData?.labels || [];
            const dataValues = this.trendData?.totals || [];
            const fullDates = this.trendData?.full_dates || [];

            this.trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Penjualan',
                        data: dataValues,
                        borderColor: '#125BCE',
                        backgroundColor: (context) => {
                            const chart = context.chart;
                            const { ctx, chartArea } = chart;
                            if (!chartArea) return 'rgba(23, 105, 232, 0.12)';
                            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                            gradient.addColorStop(0, 'rgba(23, 105, 232, 0.22)');
                            gradient.addColorStop(1, 'rgba(23, 105, 232, 0.00)');
                            return gradient;
                        },
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.32,
                        pointBackgroundColor: '#125BCE',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 3.5,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: '#1258C4',
                        pointHoverBorderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#172033',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#E2E8F0',
                            borderWidth: 0,
                            padding: 10,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: (tooltipItems) => {
                                    const idx = tooltipItems[0].dataIndex;
                                    return fullDates[idx] || tooltipItems[0].label;
                                },
                                label: (tooltipItem) => {
                                    return 'Total: ' + this.formatRupiah(tooltipItem.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#64748B',
                                font: { size: 10, family: 'Plus Jakarta Sans', weight: '500' },
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.06)' : 'rgba(226, 232, 240, 0.8)',
                                borderDash: [4, 4]
                            },
                            ticks: {
                                color: '#64748B',
                                font: { size: 10, family: 'Plus Jakarta Sans', weight: '500' },
                                callback: (val) => this.formatCompact(val)
                            }
                        }
                    }
                }
            });
        },

        updateTrendChart() {
            if (!this.trendChart) {
                this.renderTrendChart();
                return;
            }
            this.trendChart.data.labels = this.trendData?.labels || [];
            this.trendChart.data.datasets[0].data = this.trendData?.totals || [];
            this.trendChart.options.plugins.tooltip.callbacks.title = (tooltipItems) => {
                const idx = tooltipItems[0].dataIndex;
                const fullDates = this.trendData?.full_dates || [];
                return fullDates[idx] || tooltipItems[0].label;
            };
            this.trendChart.update();
        },

        renderBranchChart() {
            const ctx = document.getElementById('branchSalesChart');
            if (!ctx || !window.Chart) return;

            const labels = this.branchData?.short_labels || this.branchData?.labels || [];
            const dataValues = this.branchData?.totals || [];
            const fullLabels = this.branchData?.labels || [];

            this.branchChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Kontribusi Omzet',
                        data: dataValues,
                        backgroundColor: labels.map((_, index) => this.branchColors[index % this.branchColors.length]),
                        hoverOffset: 7,
                        borderColor: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        borderWidth: 3,
                        cutout: '68%',
                        borderRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#172033',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            padding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                title: (tooltipItems) => fullLabels[tooltipItems[0].dataIndex] || tooltipItems[0].label,
                                label: (tooltipItem) => {
                                    const total = dataValues.reduce((sum, value) => sum + Number(value || 0), 0);
                                    const amount = Number(tooltipItem.raw || 0);
                                    const share = total > 0 ? ((amount / total) * 100).toFixed(1) : '0.0';
                                    return this.formatRupiah(amount) + ' (' + share + '%)';
                                }
                            }
                        }
                    }
                }
            });
        },

        updateBranchChart() {
            if (!this.branchChart) {
                this.renderBranchChart();
                return;
            }
            const labels = this.branchData?.short_labels || this.branchData?.labels || [];
            const dataValues = this.branchData?.totals || [];
            this.branchChart.data.labels = labels;
            this.branchChart.data.datasets[0].data = dataValues;
            this.branchChart.data.datasets[0].backgroundColor = labels.map((_, index) => this.branchColors[index % this.branchColors.length]);
            this.branchChart.options.plugins.tooltip.callbacks.title = (tooltipItems) => {
                const fullLabels = this.branchData?.labels || [];
                return fullLabels[tooltipItems[0].dataIndex] || tooltipItems[0].label;
            };
            this.branchChart.options.plugins.tooltip.callbacks.label = (tooltipItem) => {
                const total = dataValues.reduce((sum, value) => sum + Number(value || 0), 0);
                const amount = Number(tooltipItem.raw || 0);
                const share = total > 0 ? ((amount / total) * 100).toFixed(1) : '0.0';
                return this.formatRupiah(amount) + ' (' + share + '%)';
            };
            this.branchChart.update();
        }
    };
}
</script>
@endpush
@endsection

