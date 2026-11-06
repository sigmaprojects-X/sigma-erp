@extends('layouts.app')

@section('title', 'Dashboard Penjualan')
@section('page_title', 'Dashboard Penjualan')

@section('content')
<div class="space-y-5 pb-2 md:space-y-6"
     x-data="salesDashboardAnalytics({{ json_encode($initialTrendData) }}, {{ json_encode($initialBranchData) }}, {{ json_encode($initialTopProducts) }}, {{ json_encode($initialCashierPerformance) }}, '{{ route('sales.analytics') }}')">

    <section class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:flex-row md:items-center md:justify-between md:px-5">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[.16em] text-blue-700 dark:text-blue-300">Ringkasan operasional</p>
            <h2 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900 dark:text-white md:text-2xl">Pantau performa bisnis Anda</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $store->name ?? 'Semua cabang' }} <span class="mx-1.5 text-slate-300">•</span> {{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <span class="inline-flex items-center gap-2 self-start rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Data transaksi sah
            </span>
            <label for="dashboard-period" class="sr-only">Periode analitik dashboard</label>
            <select id="dashboard-period" x-model="selectedPeriod" @change="setDashboardPeriod(selectedPeriod)" class="rounded-xl border-slate-200 bg-slate-50 py-2 pl-3 pr-9 text-sm font-semibold text-slate-700 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <option value="7_days">7 hari terakhir</option>
                <option value="30_days">30 hari terakhir</option>
                <option value="this_month">Bulan ini</option>
            </select>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-4">
        <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-900 p-5 text-white shadow-lg shadow-blue-900/10">
            <div class="absolute -right-8 -top-10 h-40 w-40 rounded-full border-[18px] border-white/[.07]"></div>
            <div class="relative flex items-center justify-between">
                <div><p class="text-sm font-semibold text-blue-100">Omzet penjualan</p><p class="mt-1 text-xs text-blue-100/75">Hari ini</p></div>
                <span class="rounded-xl bg-white/15 p-3"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l2.2 11.1a2 2 0 002 1.6h8.9a2 2 0 001.9-1.4L22 8H6M10 20a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg></span>
            </div>
            <p class="relative mt-5 truncate text-3xl font-extrabold tracking-tight">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            <p class="relative mt-1 text-xs text-blue-100/80">{{ number_format($todayCount, 0, ',', '.') }} transaksi berhasil</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between"><div><p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Total transaksi</p><p class="mt-1 text-xs text-slate-400">Hari ini</p></div><span class="rounded-xl bg-sky-50 p-3 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span></div>
            <p class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ number_format($todayCount, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Struk penjualan berhasil</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between"><div><p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Kas terkumpul</p><p class="mt-1 text-xs text-slate-400">Tunai + transfer hari ini</p></div><span class="rounded-xl bg-emerald-50 p-3 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.2 0-4 .9-4 2s1.8 2 4 2 4 .9 4 2-1.8 2-4 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span></div>
            <p class="mt-5 truncate text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Rp {{ number_format($todayCashCollected + $todayTransferCollected, 0, ',', '.') }}</p>
            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">Tunai Rp {{ number_format($todayCashCollected, 0, ',', '.') }} <span class="mx-1 text-slate-300">•</span> Transfer Rp {{ number_format($todayTransferCollected, 0, ',', '.') }}</p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center justify-between"><div><p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Rata-rata transaksi</p><p class="mt-1 text-xs text-slate-400">Hari ini</p></div><span class="rounded-xl bg-violet-50 p-3 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 17l6-6 4 4 8-9M15 6h6v6"/></svg></span></div>
            <p class="mt-5 truncate text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">Rp {{ number_format($todayCount > 0 ? $todayRevenue / $todayCount : 0, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Omzet dibagi jumlah struk</p>
        </article>
    </section>

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <article class="rounded-xl border border-slate-200/80 bg-white/70 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70"><p class="text-xs font-medium text-slate-500 dark:text-slate-400">Retur hari ini</p><p class="mt-1 text-base font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($todayReturnsAmount, 0, ',', '.') }}</p><p class="mt-0.5 text-[11px] text-slate-400">{{ number_format($todayReturnsCount, 0, ',', '.') }} nota retur</p></article>
        <article class="rounded-xl border border-slate-200/80 bg-white/70 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/70"><p class="text-xs font-medium text-slate-500 dark:text-slate-400">Penjualan tempo</p><p class="mt-1 text-base font-bold text-slate-900 dark:text-white">Rp {{ number_format($todayCredit, 0, ',', '.') }}</p><p class="mt-0.5 text-[11px] text-slate-400">Kredit tercatat hari ini</p></article>
    </section>

    <div x-show="analyticsError" x-cloak role="alert" class="flex items-center justify-between gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300">
        <span x-text="analyticsError"></span><button type="button" @click="fetchAnalytics('all')" class="shrink-0 font-bold underline">Coba lagi</button>
    </div>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:p-5 xl:col-span-7">
            <div class="flex flex-col gap-1 border-b border-slate-100 pb-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="font-bold text-slate-900 dark:text-white">Tren penjualan</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Omzet harian transaksi sah, tanpa transaksi void</p></div>
                <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400"><span class="h-2 w-2 rounded-full bg-blue-600"></span>Total periode <strong class="font-mono text-blue-700 dark:text-blue-300" x-text="trendData?.formatted_total || 'Rp 0'"></strong></div>
            </div>
            <div class="relative mt-3 h-64 md:h-72">
                <div x-show="trendLoading" x-cloak class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/75 text-sm font-semibold text-blue-700 backdrop-blur-sm dark:bg-slate-900/75 dark:text-blue-300">Memuat data…</div>
                <div x-show="trendError" x-cloak class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-xl bg-white/95 text-center dark:bg-slate-900/95"><p class="text-sm font-semibold text-rose-600" x-text="trendError"></p><button type="button" @click="fetchAnalytics('all')" class="mt-2 text-xs font-bold text-blue-700 underline">Coba lagi</button></div>
                <div x-show="!trendLoading && (!trendData?.totals || trendData.totals.every(value => Number(value) === 0))" x-cloak class="pointer-events-none absolute inset-0 z-[1] flex items-center justify-center text-xs text-slate-400">Belum ada transaksi pada periode ini</div>
                <canvas id="trendSalesChart" class="h-full w-full"></canvas>
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 md:p-5 xl:col-span-5">
            <div class="border-b border-slate-100 pb-4 dark:border-slate-800"><h3 class="font-bold text-slate-900 dark:text-white">Penjualan per cabang</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Kontribusi outlet aktif • mengikuti periode pilihan</p></div>
            <div class="relative grid min-h-[250px] grid-cols-1 items-center gap-3 pt-3 md:grid-cols-2">
                <div class="relative mx-auto h-56 w-full max-w-[250px]">
                    <div x-show="branchLoading" x-cloak class="absolute inset-0 z-10 flex items-center justify-center rounded-xl bg-white/75 text-sm font-semibold text-blue-700 dark:bg-slate-900/75 dark:text-blue-300">Memuat cabang…</div>
                    <div x-show="!branchLoading && (!branchData?.details || branchData.details.length === 0 || branchData.totals.every(value => Number(value) === 0))" x-cloak class="pointer-events-none absolute inset-0 z-[1] flex items-center justify-center text-center text-xs text-slate-400">Belum ada data cabang</div>
                    <canvas id="branchSalesChart" class="h-full w-full"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center"><span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Total omzet</span><strong class="mt-1 max-w-[140px] truncate text-center text-sm font-extrabold text-slate-900 dark:text-white" x-text="branchData?.formatted_total || 'Rp 0'"></strong></div>
                </div>
                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                    <template x-for="(branch, index) in (branchData?.details || [])" :key="branch.store_id">
                        <div class="flex items-start gap-2.5 rounded-xl bg-slate-50 p-2.5 dark:bg-slate-800/70"><span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" :style="'background-color:' + branchColors[index % branchColors.length]"></span><div class="min-w-0 flex-1"><p class="truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="branch.store_name"></p><p class="mt-0.5 text-[11px] text-slate-500" x-text="branch.formatted_sales"></p></div><span class="shrink-0 text-[10px] text-slate-400" x-text="branch.tx_count + ' trx'"></span></div>
                    </template>
                </div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:col-span-4">
            <header class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Produk terlaris</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">5 produk teratas • <span x-text="selectedPeriod === '7_days' ? '7 hari terakhir' : (selectedPeriod === '30_days' ? '30 hari terakhir' : 'bulan ini')"></span></p></div><span class="rounded-lg bg-amber-50 px-2 py-1 text-[10px] font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">PRODUK</span></header>
            <div class="overflow-x-auto p-3"><table class="w-full text-left text-xs"><thead class="text-[10px] uppercase tracking-wide text-slate-400"><tr><th class="px-2 py-2">Produk</th><th class="px-2 py-2 text-right">Qty</th><th class="px-2 py-2 text-right">Omzet</th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <template x-for="(product, index) in productRows" :key="product.product_id"><tr><td class="max-w-[180px] px-2 py-3"><div class="flex items-center gap-2"><span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-[10px] font-bold text-amber-700" x-text="index + 1"></span><div class="min-w-0"><p class="truncate font-bold text-slate-800 dark:text-slate-100" x-text="product.name"></p><p class="mt-0.5 truncate font-mono text-[10px] text-slate-400" x-text="product.sku"></p></div></div></td><td class="whitespace-nowrap px-2 py-3 text-right font-mono font-semibold text-blue-700 dark:text-blue-300" x-text="product.formatted_qty"></td><td class="whitespace-nowrap px-2 py-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100" x-text="product.formatted_revenue"></td></tr></template>
                <tr x-show="!productRows.length"><td colspan="3" class="px-2 py-8 text-center text-slate-500">Belum ada penjualan produk pada periode ini</td></tr>
            </tbody></table></div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:col-span-3">
            <header class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Kinerja kasir</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Performa operator • <span x-text="selectedPeriod === '7_days' ? '7 hari terakhir' : (selectedPeriod === '30_days' ? '30 hari terakhir' : 'bulan ini')"></span></p></div><span class="rounded-lg bg-blue-50 px-2 py-1 text-[10px] font-bold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">KASIR</span></header>
            <div class="max-h-[330px] space-y-2 overflow-y-auto p-3">
                <template x-for="cashier in cashierRows" :key="cashier.cashier_id"><div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-cyan-500 text-xs font-black text-white" x-text="cashier.initials"></span><div class="min-w-0 flex-1"><p class="truncate text-xs font-bold text-slate-800 dark:text-slate-100" x-text="cashier.name"></p><p class="mt-0.5 text-[10px] text-slate-500" x-text="cashier.tx_count + ' transaksi'"></p></div><p class="shrink-0 text-right text-[11px] font-bold text-slate-700 dark:text-slate-200" x-text="cashier.formatted_revenue"></p></div></template>
                <div x-show="!cashierRows.length" class="py-10 text-center text-xs text-slate-500">Belum ada transaksi kasir</div>
            </div>
        </article>

        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900 xl:col-span-5">
            <header class="flex items-center justify-between border-b border-slate-100 p-4 dark:border-slate-800"><div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Transaksi terbaru</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">10 transaksi terakhir • transaksi void tidak ditampilkan</p></div><a href="{{ route('sales.transactions') }}" class="text-xs font-bold text-blue-700 hover:text-blue-900 dark:text-blue-300">Lihat semua →</a></header>
            <div class="max-h-[330px] divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @forelse($recentTransactions as $tx)
                <div class="flex items-center gap-3 px-4 py-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $tx->payment_method === 'TEMPO' ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h4"/></svg></span><div class="min-w-0 flex-1"><p class="truncate font-mono text-xs font-bold text-slate-800 dark:text-slate-100">{{ $tx->invoice_number }}</p><p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $tx->customer->name ?? 'Pelanggan Umum' }} · {{ $tx->created_at->format('d/m H:i') }}</p></div><div class="shrink-0 text-right"><p class="text-xs font-bold text-slate-900 dark:text-white">Rp {{ number_format($tx->grand_total, 0, ',', '.') }}</p><p class="mt-0.5 text-[10px] text-slate-500">{{ $tx->payment_method === 'TEMPO' ? 'Tempo' : $tx->payment_method }}</p><div class="mt-1 flex justify-end gap-1"><a href="{{ route('pos.print', ['id' => $tx->id, 'paper' => '58']) }}" target="_blank" rel="noopener noreferrer" title="Cetak struk" class="rounded bg-slate-100 px-1.5 py-0.5 text-[9px] font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">58mm</a><a href="{{ route('sales.print-invoice', $tx->id) }}" target="_blank" rel="noopener noreferrer" title="Cetak faktur A4" class="rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-950/60 dark:text-blue-300">A4</a></div></div></div>
                @empty <div class="py-12 text-center text-xs text-slate-500">Belum ada transaksi</div> @endforelse
            </div>
        </article>
    </section>
</div>
@push('scripts')
<script>
function salesDashboardAnalytics(initialTrend, initialBranch, initialProducts, initialCashiers, analyticsUrl) {
    return {
        trendPeriod: '7_days',
        branchPeriod: '7_days',
        selectedPeriod: '7_days',
        productRows: initialProducts || [],
        cashierRows: initialCashiers || [],
        analyticsError: null,
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

        setDashboardPeriod(period) {
            if (this.trendPeriod === period && this.branchPeriod === period) return;
            this.trendPeriod = period;
            this.branchPeriod = period;
            this.selectedPeriod = period;
            this.fetchAnalytics('all');
        },

        async fetchAnalytics(type) {
            if (type === 'trend' || type === 'all') this.trendLoading = true;
            if (type === 'branch' || type === 'all') this.branchLoading = true;

            try {
                const url = analyticsUrl + '?period=' + encodeURIComponent(this.selectedPeriod);
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Gagal memuat data analitik dari server');
                const json = await res.json();

                if (type === 'trend' || type === 'all') {
                    this.trendData = json.trend;
                    this.productRows = json.products || [];
                    this.cashierRows = json.cashiers || [];
                    this.updateTrendChart();
                    this.trendError = null;
                }
                if (type === 'branch' || type === 'all') {
                    this.branchData = json.branch;
                    this.updateBranchChart();
                    this.branchError = null;
                }
                this.analyticsError = null;
            } catch (err) {
                console.error('Analytics Error:', err);
                if (type === 'trend') this.trendError = 'Terjadi kesalahan saat memuat data tren';
                if (type === 'branch') this.branchError = 'Terjadi kesalahan saat memuat data cabang';
                this.analyticsError = 'Data dashboard belum berhasil dimuat. Periksa koneksi lalu coba lagi.';
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
