@extends('layouts.app')

@section('title', 'Dashboard Penjualan')
@section('page_title', 'Dashboard Penjualan & Ringkasan Toko')

@section('content')
<div class="space-y-6 md:space-y-7 animate-in fade-in duration-200"
     x-data="salesDashboardAnalytics({{ json_encode($initialTrendData) }}, {{ json_encode($initialBranchData) }}, '{{ route('sales.analytics') }}')">

    <!-- ============================================================== -->
    <!-- 1. WELCOME BANNER (MODERN CORPORATE BLUE MINIMALIST HERO)      -->
    <!-- ============================================================== -->
    <div class="relative rounded-xl bg-white dark:bg-slate-800 p-5 md:p-6 border border-[#E2E8F0] dark:border-slate-700/80 shadow-xs transition-colors overflow-hidden" data-wallpaper="{{ $wallpaperUrl }}">
        @if(!empty($wallpaperUrl))
            <div class="absolute inset-0 opacity-10 dark:opacity-5 pointer-events-none bg-cover bg-center" style="background-image: url('{{ $wallpaperUrl }}');"></div>
        @endif
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-13 h-13 rounded-xl bg-white dark:bg-slate-700/60 p-1 border border-slate-200 dark:border-slate-600/60 flex items-center justify-center shrink-0 shadow-2xs">
                    <img src="{{ asset('images/sigma-erp-logo.png') }}" alt="SIGMA ERP" class="w-full h-full object-contain rounded-lg" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}'">
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-[#15803D] border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#15803D] animate-pulse"></span>
                            Sistem Kasir Aktif
                        </span>
                        <span class="text-xs text-[#64748B] dark:text-slate-400 font-mono">Cabang: {{ $store->code ?? 'STR-001' }}</span>
                    </div>
                    <h2 class="text-lg md:text-xl font-bold tracking-tight text-[#172033] dark:text-white">
                        {{ $company->name ?? 'PT SIGMA PROJECTS INDONESIA' }}
                    </h2>
                    <p class="text-xs text-[#64748B] dark:text-slate-400 mt-0.5">
                        {{ $store->name ?? 'Cabang Utama Retail & Grosir' }} &bull; {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>
            </div>

            <!-- Ringkasan Cepat Status -->
            <div class="flex items-center gap-2.5 self-start md:self-auto">
                <div class="text-right hidden sm:block">
                    <div class="text-[10px] uppercase font-bold tracking-wider text-[#64748B] dark:text-slate-400">Status Server ERP</div>
                    <div class="text-xs font-bold text-[#15803D] flex items-center justify-end gap-1">
                        <span class="w-2 h-2 rounded-full bg-[#15803D]"></span>
                        <span>Terhubung &amp; Sinkron</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. QUICK ACTION CARDS (MODERN CORPORATE BLUE STYLE)            -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        <!-- Action 1: Kasir POS -->
        <a href="{{ route('pos.index') }}"
           class="group relative overflow-hidden rounded-xl bg-white dark:bg-slate-800 p-5 border border-[#E2E8F0] dark:border-slate-700/80 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-emerald-400 dark:hover:border-emerald-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-[#15803D] border border-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/40 transition-transform duration-200 group-hover:scale-105">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div>
                        <span class="block font-bold text-[#172033] dark:text-white text-sm leading-snug">Penjualan<br>Kasir [POS]</span>
                        <span class="text-xs text-[#64748B] dark:text-slate-400 font-medium">Buka Kasir Cepat (F12)</span>
                    </div>
                </div>
                <svg class="h-4 w-4 text-slate-400 group-hover:text-[#15803D] dark:group-hover:text-emerald-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
            </div>
        </a>

        <!-- Action 2: Transaksi Penjualan -->
        <a href="{{ route('sales.transactions') }}"
           class="group relative overflow-hidden rounded-xl bg-white dark:bg-slate-800 p-5 border border-[#E2E8F0] dark:border-slate-700/80 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-[#125BCE] dark:hover:border-blue-500">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#125BCE] border border-blue-100 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/40 transition-transform duration-200 group-hover:scale-105">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <span class="block font-bold text-[#172033] dark:text-white text-sm leading-snug">Daftar<br>Penjualan</span>
                        <span class="text-xs text-[#64748B] dark:text-slate-400 font-medium">Audit Faktur &amp; Struk</span>
                    </div>
                </div>
                <svg class="h-4 w-4 text-slate-400 group-hover:text-[#125BCE] dark:group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
            </div>
        </a>

        <!-- Action 3: Retur Penjualan -->
        <a href="{{ route('sales.returns.index') }}"
           class="group relative overflow-hidden rounded-xl bg-white dark:bg-slate-800 p-5 border border-[#E2E8F0] dark:border-slate-700/80 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-rose-400 dark:hover:border-rose-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-[#DC3545] border border-rose-100 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/40 transition-transform duration-200 group-hover:scale-105">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <div>
                        <span class="block font-bold text-[#172033] dark:text-white text-sm leading-snug">Retur Penjualan</span>
                        <span class="text-xs text-[#64748B] dark:text-slate-400 font-medium">Nota Retur &amp; Stok</span>
                    </div>
                </div>
                <svg class="h-4 w-4 text-slate-400 group-hover:text-[#DC3545] dark:group-hover:text-rose-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
            </div>
        </a>

        <!-- Action 4: Buat Faktur Back-Office -->
        <a href="{{ route('sales.invoices.create') }}"
           class="group relative overflow-hidden rounded-xl bg-white dark:bg-slate-800 p-5 border border-[#E2E8F0] dark:border-slate-700/80 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md hover:border-amber-400 dark:hover:border-amber-600">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-[#F59E0B] border border-amber-100 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40 transition-transform duration-200 group-hover:scale-105">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <span class="block font-bold text-[#172033] dark:text-white text-sm leading-snug">Faktur Back-Office</span>
                        <span class="text-xs text-[#64748B] dark:text-slate-400 font-medium">Cetak A4 &amp; DO</span>
                    </div>
                </div>
                <svg class="h-4 w-4 text-slate-400 group-hover:text-[#F59E0B] dark:group-hover:text-amber-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H7M17 7V17"/></svg>
            </div>
        </a>
    </div>

    <!-- ============================================================== -->
    <!-- 3. PRIMARY METRICS OVERVIEW (5 KEY PERFORMANCE METRICS)        -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <!-- KPI 1: Omset Penjualan Hari Ini -->
        <div class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-xs border border-[#E2E8F0] dark:border-slate-700/80 transition-all duration-200 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-[#15803D] dark:bg-emerald-950/40 dark:text-emerald-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-[#15803D] dark:bg-emerald-950/50 dark:text-emerald-300">
                    OMSET
                </span>
            </div>
            <div class="mt-3.5">
                <div class="text-xs font-semibold tracking-wider text-[#64748B] dark:text-slate-400 uppercase">
                    Omset Penjualan Hari Ini
                </div>
                <div class="mt-1 font-mono text-xl font-bold text-[#172033] dark:text-white truncate">
                    Rp {{ number_format($todayRevenue, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-[#15803D] dark:text-emerald-400 font-medium truncate">
                    &bull; Total Transaksi Berhasil
                </div>
            </div>
        </div>

        <!-- KPI 2: Total Faktur Terbit -->
        <div class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-xs border border-[#E2E8F0] dark:border-slate-700/80 transition-all duration-200 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#125BCE] dark:bg-blue-950/40 dark:text-blue-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-[#125BCE] dark:bg-blue-950/50 dark:text-blue-300">
                    KASIR
                </span>
            </div>
            <div class="mt-3.5">
                <div class="text-xs font-semibold tracking-wider text-[#64748B] dark:text-slate-400 uppercase">
                    Faktur Kasir
                </div>
                <div class="mt-1 font-mono text-xl font-bold text-[#172033] dark:text-white">
                    {{ number_format($todayCount, 0, ',', '.') }} <span class="text-xs font-sans font-medium text-[#64748B]">Struk</span>
                </div>
                <div class="mt-1 text-xs text-[#125BCE] dark:text-blue-400 font-medium truncate">
                    &bull; Rata-rata {{ $todayCount > 0 ? 'Rp ' . number_format($todayRevenue / $todayCount, 0, ',', '.') : 'Rp 0' }}
                </div>
            </div>
        </div>

        <!-- KPI 3: Kas & Bank Masuk -->
        <div class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-xs border border-[#E2E8F0] dark:border-slate-700/80 transition-all duration-200 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-[#F59E0B] dark:bg-amber-950/40 dark:text-amber-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-[#F59E0B] dark:bg-amber-950/50 dark:text-amber-300">
                    LIKUID
                </span>
            </div>
            <div class="mt-3.5">
                <div class="text-xs font-semibold tracking-wider text-[#64748B] dark:text-slate-400 uppercase">
                    Uang Tunai / Bank
                </div>
                <div class="mt-1 font-mono text-xl font-bold text-[#172033] dark:text-white truncate">
                    Rp {{ number_format($todayCashCollected + $todayTransferCollected, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-[#F59E0B] dark:text-amber-400 font-medium truncate">
                    Tunai: Rp {{ number_format($todayCashCollected, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- KPI 4: Piutang / Tempo Hari Ini -->
        <div class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-xs border border-[#E2E8F0] dark:border-slate-700/80 transition-all duration-200 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-[#7C3AED] dark:bg-purple-950/40 dark:text-purple-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-[#7C3AED] dark:bg-purple-950/50 dark:text-purple-300">
                    PIUTANG
                </span>
            </div>
            <div class="mt-3.5">
                <div class="text-xs font-semibold tracking-wider text-[#64748B] dark:text-slate-400 uppercase">
                    Tagihan Tempo
                </div>
                <div class="mt-1 font-mono text-xl font-bold text-[#172033] dark:text-white truncate">
                    Rp {{ number_format($todayCredit, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-[#7C3AED] dark:text-purple-400 font-medium truncate">
                    &bull; Piutang Terverifikasi
                </div>
            </div>
        </div>

        <!-- KPI 5: Retur Penjualan Hari Ini -->
        <div class="rounded-xl bg-white dark:bg-slate-800 p-5 shadow-xs border border-[#E2E8F0] dark:border-slate-700/80 transition-all duration-200 hover:shadow-md">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-[#DC3545] dark:bg-rose-950/40 dark:text-rose-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-[#DC3545] dark:bg-rose-950/50 dark:text-rose-300">
                    RETUR
                </span>
            </div>
            <div class="mt-3.5">
                <div class="text-xs font-semibold tracking-wider text-[#64748B] dark:text-slate-400 uppercase">
                    Retur Hari Ini
                </div>
                <div class="mt-1 font-mono text-xl font-bold text-[#DC3545] dark:text-rose-400 truncate">
                    Rp {{ number_format($todayReturnsAmount, 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-[#DC3545] dark:text-rose-400 font-medium truncate">
                    &bull; {{ $todayReturnsCount }} Nota Retur
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 4. GRAFIK UTAMA: TREN PENJUALAN & PERBANDINGAN CABANG          -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- GRAFIK 1: TREN TOTAL PENJUALAN -->
        <div class="rounded-xl bg-white dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700/80 p-5 shadow-xs relative flex flex-col justify-between">
            <div>
                <!-- Header Grafik 1 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-[#125BCE]"></span>
                            <h3 class="text-sm font-bold text-[#172033] dark:text-white">
                                Tren Penjualan
                            </h3>
                        </div>
                        <p class="text-xs text-[#64748B] dark:text-slate-400 mt-0.5">
                            Total omzet harian transaksi sah (net of void)
                        </p>
                    </div>

                    <!-- Filter Periode Tren -->
                    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-700/70 rounded-lg text-xs font-semibold border border-slate-200/60 dark:border-slate-600/60 self-start sm:self-auto">
                        <button type="button"
                                @click="setTrendPeriod('7_days')"
                                :class="trendPeriod === '7_days' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer">
                            7 Hari
                        </button>
                        <button type="button"
                                @click="setTrendPeriod('30_days')"
                                :class="trendPeriod === '30_days' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer">
                            30 Hari
                        </button>
                        <button type="button"
                                @click="setTrendPeriod('this_month')"
                                :class="trendPeriod === 'this_month' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2.5 py-1 rounded-md transition-all cursor-pointer">
                            Bulan Ini
                        </button>
                    </div>
                </div>

                <!-- Summary Bar Grafik 1 -->
                <div class="flex items-center justify-between py-3">
                    <span class="text-xs text-[#64748B] dark:text-slate-400 font-medium">Akumulasi Periode:</span>
                    <span class="font-mono text-sm font-bold text-[#125BCE] dark:text-blue-400" x-text="trendData?.formatted_total || 'Rp 0'">
                        {{ $initialTrendData['formatted_total'] }}
                    </span>
                </div>
            </div>

            <!-- Canvas Container Grafik 1 -->
            <div class="relative w-full h-64 sm:h-72 mt-1">
                <!-- Loading State -->
                <div x-show="trendLoading"
                     x-cloak
                     class="absolute inset-0 bg-white/75 dark:bg-slate-800/75 backdrop-blur-xs flex items-center justify-center z-10 rounded-lg">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#125BCE]">
                        <svg class="animate-spin h-4 w-4 text-[#125BCE]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Memuat data tren...</span>
                    </div>
                </div>

                <!-- Error State -->
                <div x-show="trendError"
                     x-cloak
                     class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 bg-white dark:bg-slate-800 z-10">
                    <span class="text-2xl mb-1">⚠️</span>
                    <p class="text-xs font-bold text-[#DC3545]" x-text="trendError"></p>
                    <button type="button" @click="fetchAnalytics('trend')" class="mt-2 text-xs text-[#125BCE] underline font-bold cursor-pointer">Coba Lagi</button>
                </div>

                <canvas id="trendSalesChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- GRAFIK 2: PERBANDINGAN PENJUALAN CABANG -->
        <div class="rounded-xl bg-white dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700/80 p-5 shadow-xs relative flex flex-col justify-between">
            <div>
                <!-- Header Grafik 2 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-700/60">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-2.5 w-2.5 rounded-full bg-[#15803D]"></span>
                            <h3 class="text-sm font-bold text-[#172033] dark:text-white">
                                Perbandingan Penjualan Cabang
                            </h3>
                        </div>
                        <p class="text-xs text-[#64748B] dark:text-slate-400 mt-0.5">
                            Kontribusi omzet per cabang &amp; store outlet aktif
                        </p>
                    </div>

                    <!-- Filter Periode Cabang -->
                    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-700/70 rounded-lg text-xs font-semibold border border-slate-200/60 dark:border-slate-600/60 self-start sm:self-auto">
                        <button type="button"
                                @click="setBranchPeriod('today')"
                                :class="branchPeriod === 'today' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2 py-1 rounded-md transition-all cursor-pointer">
                            Hari Ini
                        </button>
                        <button type="button"
                                @click="setBranchPeriod('7_days')"
                                :class="branchPeriod === '7_days' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2 py-1 rounded-md transition-all cursor-pointer">
                            7 Hari
                        </button>
                        <button type="button"
                                @click="setBranchPeriod('30_days')"
                                :class="branchPeriod === '30_days' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2 py-1 rounded-md transition-all cursor-pointer">
                            30 Hari
                        </button>
                        <button type="button"
                                @click="setBranchPeriod('this_month')"
                                :class="branchPeriod === 'this_month' ? 'bg-[#125BCE] text-white shadow-xs' : 'text-[#64748B] hover:text-[#172033] dark:text-slate-300 dark:hover:text-white'"
                                class="px-2 py-1 rounded-md transition-all cursor-pointer">
                            Bulan Ini
                        </button>
                    </div>
                </div>

                <!-- Summary Bar Grafik 2 -->
                <div class="flex items-center justify-between py-3">
                    <span class="text-xs text-[#64748B] dark:text-slate-400 font-medium">Total Seluruh Cabang:</span>
                    <span class="font-mono text-sm font-bold text-[#15803D] dark:text-emerald-400" x-text="branchData?.formatted_total || 'Rp 0'">
                        {{ $initialBranchData['formatted_total'] }}
                    </span>
                </div>
            </div>

            <!-- Canvas Container Grafik 2 -->
            <div class="relative w-full h-64 sm:h-72 mt-1">
                <!-- Loading State -->
                <div x-show="branchLoading"
                     x-cloak
                     class="absolute inset-0 bg-white/75 dark:bg-slate-800/75 backdrop-blur-xs flex items-center justify-center z-10 rounded-lg">
                    <div class="flex items-center gap-2 text-xs font-bold text-[#125BCE]">
                        <svg class="animate-spin h-4 w-4 text-[#125BCE]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Memuat data cabang...</span>
                    </div>
                </div>

                <!-- Error State -->
                <div x-show="branchError"
                     x-cloak
                     class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 bg-white dark:bg-slate-800 z-10">
                    <span class="text-2xl mb-1">⚠️</span>
                    <p class="text-xs font-bold text-[#DC3545]" x-text="branchError"></p>
                    <button type="button" @click="fetchAnalytics('branch')" class="mt-2 text-xs text-[#125BCE] underline font-bold cursor-pointer">Coba Lagi</button>
                </div>

                <!-- Empty State -->
                <div x-show="!branchLoading && (!branchData?.details || branchData.details.length === 0)"
                     x-cloak
                     class="absolute inset-0 flex flex-col items-center justify-center text-center p-4 bg-white dark:bg-slate-800 z-10">
                    <span class="text-2xl mb-1">🏬</span>
                    <p class="text-xs font-bold text-[#64748B]">Tidak ada data cabang yang dapat ditampilkan</p>
                </div>

                <canvas id="branchSalesChart" class="w-full h-full"></canvas>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 5. ANALISIS PENJUALAN: TOP BEST-SELLERS & CASHIER PERFORMANCE  -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Card A: Top 5 Produk Terlaris (Best-Selling Products) -->
        <div class="rounded-xl bg-white dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700/80 overflow-hidden shadow-xs">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🏆</span>
                    <div>
                        <h3 class="text-sm font-bold text-[#172033] dark:text-white">
                            Top 5 Produk Terlaris
                        </h3>
                        <p class="text-xs text-[#64748B] dark:text-slate-400">
                            Barang paling cepat laku &amp; menyumbang omset tertinggi
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-[#F59E0B] dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/40">
                    Best Sellers
                </span>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-[#64748B] uppercase text-[9.5px] font-bold border-b border-slate-100 dark:border-slate-700/60">
                                <th class="pb-2 text-center w-8">#</th>
                                <th class="pb-2">Produk</th>
                                <th class="pb-2 text-center">Terjual</th>
                                <th class="pb-2 text-right">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @forelse($topProducts as $idx => $prod)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750 transition-colors">
                                <td class="py-2.5 text-center font-bold">
                                    @if($idx === 0)
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-400 text-white font-black text-[10px]">1</span>
                                    @elseif($idx === 1)
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-300 text-slate-700 font-black text-[10px]">2</span>
                                    @elseif($idx === 2)
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-600 text-white font-black text-[10px]">3</span>
                                    @else
                                        <span class="text-[#64748B] font-mono">{{ $idx + 1 }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <div class="font-bold text-[#172033] dark:text-white truncate max-w-[200px]">
                                        {{ $prod->product->name ?? 'Produk' }}
                                    </div>
                                    <div class="text-[10px] text-[#64748B] font-mono">
                                        {{ $prod->product->sku ?? '-' }}
                                    </div>
                                </td>
                                <td class="py-2.5 text-center font-mono font-bold text-[#125BCE] dark:text-blue-400">
                                    {{ number_format($prod->total_qty, 0, ',', '.') }}
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold text-[#172033] dark:text-white">
                                    Rp {{ number_format($prod->total_revenue, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-[#64748B] text-xs">
                                    Belum ada data akumulasi penjualan produk
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Card B: Kinerja Kasir & Operator POS -->
        <div class="rounded-xl bg-white dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700/80 overflow-hidden shadow-xs">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">👩‍💼</span>
                    <div>
                        <h3 class="text-sm font-bold text-[#172033] dark:text-white">
                            Kinerja Kasir &amp; Operator POS
                        </h3>
                        <p class="text-xs text-[#64748B] dark:text-slate-400">
                            Volume dan nilai transaksi per staf kasir
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-[#125BCE] dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/40">
                    Produktivitas
                </span>
            </div>
            <div class="p-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="text-[#64748B] uppercase text-[9.5px] font-bold border-b border-slate-100 dark:border-slate-700/60">
                                <th class="pb-2">Nama Kasir</th>
                                <th class="pb-2 text-center">Faktur Selesai</th>
                                <th class="pb-2 text-right">Total Transaksi Dilayani</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @forelse($salesByCashier as $cashierData)
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-750 transition-colors">
                                <td class="py-2.5">
                                    <div class="font-bold text-[#172033] dark:text-white">
                                        {{ $cashierData->cashier->name ?? 'Kasir #' . substr($cashierData->cashier_id, 0, 6) }}
                                    </div>
                                    <div class="text-[10px] text-[#64748B]">
                                        {{ $cashierData->cashier->email ?? 'Petugas Toko' }}
                                    </div>
                                </td>
                                <td class="py-2.5 text-center font-mono font-bold text-[#15803D] dark:text-emerald-400">
                                    {{ number_format($cashierData->tx_count, 0, ',', '.') }} Faktur
                                </td>
                                <td class="py-2.5 text-right font-mono font-bold text-[#172033] dark:text-white">
                                    Rp {{ number_format($cashierData->total_revenue, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-[#64748B] text-xs">
                                    Belum ada data kinerja kasir terdaftar
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 6. RECENT TRANSACTIONS TABLE (MODERN ROUNDED CARD)             -->
    <!-- ============================================================== -->
    <div class="rounded-xl bg-white dark:bg-slate-800 border border-[#E2E8F0] dark:border-slate-700/80 overflow-hidden shadow-xs">
        <div class="p-5 md:p-6 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-[#172033] dark:text-white">
                    Riwayat Transaksi Penjualan Terkini
                </h3>
                <p class="text-xs text-[#64748B] dark:text-slate-400 mt-0.5">
                    Daftar 10 faktur struk penjualan terakhir di cabang ini
                </p>
            </div>
            <a href="{{ route('sales.transactions') }}"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#125BCE] hover:text-[#1258C4] dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                <span>Lihat Semua Transaksi</span>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/60 text-[#64748B] dark:text-slate-400 font-bold uppercase text-[10px] tracking-wider border-b border-slate-100 dark:border-slate-700/60">
                        <th class="py-3 px-5">No. Faktur</th>
                        <th class="py-3 px-5">Waktu</th>
                        <th class="py-3 px-5">Pelanggan</th>
                        <th class="py-3 px-5">Tier Harga</th>
                        <th class="py-3 px-5">Metode Pembayaran</th>
                        <th class="py-3 px-5 text-right">Total Transaksi</th>
                        <th class="py-3 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 font-medium">
                    @forelse($recentTransactions as $tx)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-750 transition-colors">
                            <td class="py-3.5 px-5 font-mono font-bold text-[#125BCE] dark:text-blue-400">
                                {{ $tx->invoice_number }}
                            </td>
                            <td class="py-3.5 px-5 text-[#64748B] dark:text-slate-400 font-mono">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-3.5 px-5 font-bold text-[#172033] dark:text-white">
                                {{ $tx->customer->name ?? 'Pelanggan Umum' }}
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                    {{ $tx->customerTier->name ?? 'Retail' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5">
                                @if($tx->payment_method === 'CASH')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-[#15803D] dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#15803D]"></span>
                                        TUNAI
                                    </span>
                                @elseif($tx->payment_method === 'TRANSFER')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-[#125BCE] dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#125BCE]"></span>
                                        TRANSFER
                                    </span>
                                @elseif($tx->payment_method === 'TEMPO')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-[#7C3AED] dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-[#7C3AED]"></span>
                                        TEMPO / KREDIT
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                                        {{ $tx->payment_method }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-bold text-[#172033] dark:text-white">
                                Rp {{ number_format($tx->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-5 text-center">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('pos.print', ['id' => $tx->id, 'paper' => '58']) }}"
                                       target="_blank"
                                       title="Cetak Struk Kasir"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-200 transition-colors">
                                        <span>🖨️ Struk</span>
                                    </a>
                                    <a href="{{ route('sales.print-invoice', $tx->id) }}"
                                       target="_blank"
                                       title="Cetak Faktur A4"
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 hover:bg-blue-100 text-[#125BCE] dark:bg-blue-950/60 dark:hover:bg-blue-900 dark:text-blue-300 transition-colors">
                                        <span>📄 A4</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-[#64748B]">
                                <div class="text-3xl mb-2">🧾</div>
                                <div class="font-bold">Belum ada transaksi penjualan hari ini</div>
                                <div class="text-[11px] mt-0.5">Buka layar kasir POS untuk memulai melayani transaksi</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Penjualan Cabang',
                        data: dataValues,
                        backgroundColor: '#125BCE',
                        hoverBackgroundColor: '#1258C4',
                        borderRadius: 6,
                        maxBarThickness: 38
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
                            displayColors: false,
                            callbacks: {
                                title: (tooltipItems) => {
                                    const idx = tooltipItems[0].dataIndex;
                                    return fullLabels[idx] || tooltipItems[0].label;
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
                                font: { size: 10, family: 'Plus Jakarta Sans', weight: '600' }
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

        updateBranchChart() {
            if (!this.branchChart) {
                this.renderBranchChart();
                return;
            }
            this.branchChart.data.labels = this.branchData?.short_labels || this.branchData?.labels || [];
            this.branchChart.data.datasets[0].data = this.branchData?.totals || [];
            this.branchChart.options.plugins.tooltip.callbacks.title = (tooltipItems) => {
                const idx = tooltipItems[0].dataIndex;
                const fullLabels = this.branchData?.labels || [];
                return fullLabels[idx] || tooltipItems[0].label;
            };
            this.branchChart.update();
        }
    };
}
</script>
@endpush
@endsection
