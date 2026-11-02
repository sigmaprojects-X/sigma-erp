<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIGMA ERP') - Enterprise Retail &amp; POS</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- PWA Progressive Web App Meta & Manifest -->
    <meta name="theme-color" content="#03091e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIGMA ERP">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="{{ asset('sigma-icon-192.png') }}">

    <!-- 1. Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://googleapis.com">
    <link rel="preconnect" href="https://gstatic.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Init dark mode immediately in head to avoid FOUC
        (function() {
            try {
                const storedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {
                console.warn('Dark mode pre-check error:', e);
            }
        })();
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: #334155; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ============================================================== */
        /* SIGMA ERP - ENTERPRISE HIGH-PRECISION PRINT ENGINE STYLES       */
        /* ============================================================== */
        @media print {
            @page {
                size: A4 auto;
                margin: 10mm 10mm 12mm 10mm;
            }

            *, *::before, *::after {
                box-shadow: none !important;
                text-shadow: none !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html, body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 8pt !important;
                line-height: 1.3 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                min-height: 0 !important;
            }

            /* Hide all interactive web UI and navigation chrome */
            aside,
            header,
            footer,
            nav,
            .print\:hidden,
            .no-print,
            button,
            form,
            input,
            select,
            textarea,
            [x-cloak] {
                display: none !important;
            }

            /* Reset layout margins & paddings */
            .md\:pl-64,
            .pl-64 {
                padding-left: 0 !important;
                margin-left: 0 !important;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .max-w-7xl {
                max-width: 100% !important;
            }

            /* Explicit print display helpers */
            .print\:block {
                display: block !important;
            }
            .print\:flex {
                display: flex !important;
            }
            .print\:grid {
                display: grid !important;
            }
            .print\:table {
                display: table !important;
            }

            /* Professional Table Formatting for Paper Output */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
                font-size: 7.5pt !important;
                margin-top: 4px !important;
                margin-bottom: 8px !important;
            }

            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }

            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }

            th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 700 !important;
                border: 1px solid #94a3b8 !important;
                padding: 3.5px 5px !important;
                text-transform: uppercase !important;
                font-size: 7pt !important;
                letter-spacing: 0.025em;
            }

            td {
                border: 1px solid #cbd5e1 !important;
                padding: 3.5px 5px !important;
                color: #1e293b !important;
            }

            tbody tr:nth-child(even) td {
                background-color: #f8fafc !important;
            }

            /* Page-break control */
            .print-avoid-break,
            .break-inside-avoid {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .print-break-before {
                page-break-before: always !important;
            }
        }
    </style>
    @stack('styles')
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(e) {
                    console.log('PWA SW error:', e);
                });
            });
        }
    </script>
</head>
<body class="min-h-screen bg-[#F4F7FC] dark:bg-slate-900 font-sans text-[#172033] dark:text-slate-100 pb-20 md:pb-0 antialiased selection:bg-[#125BCE] selection:text-white transition-colors duration-200"
      x-data="sigmaErpLayout()"
      x-init="initLayout()"
      @keydown.window="handleGlobalKeys($event)">

    <!-- ============================================================== -->
    <!-- 1. DESKTOP SIDEBAR (FIXED w-64, OFFICIAL SIGMA NAVY IDENTITY)   -->
    <!-- ============================================================== -->
    <aside class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col border-r border-[#071536] dark:border-slate-800 bg-[#0B1F4D] dark:bg-[#071536] text-slate-200 shadow-xl z-30 transition-colors">
        
        <!-- BRAND & LOGO HEADER (h-18) -->
        <div class="flex h-18 shrink-0 items-center justify-between px-5 border-b border-white/10 dark:border-slate-800 bg-[#071536]/40">
            <a href="{{ route('sales.index') }}" class="flex items-center space-x-3 group">
                <img src="{{ asset('images/sigma-erp-logo.png') }}"
                     alt="SIGMA ERP"
                     class="h-10 w-auto object-contain rounded-xl shrink-0 select-none drop-shadow-md transition-transform group-hover:scale-105"
                     onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}'">
                <div>
                    <div class="font-black text-base tracking-tight text-white leading-tight flex items-center gap-1.5">
                        <span>SIGMA</span>
                        <span class="px-1.5 py-0.2 bg-[#FFB800] text-[#071536] border border-[#FFB800] text-[10px] rounded font-black">ERP</span>
                    </div>
                    <div class="text-[10px] text-[#18BDEB] font-semibold tracking-wider uppercase">
                        Enterprise System
                    </div>
                </div>
            </a>
        </div>

        <!-- NAVIGATION LINKS -->
        <div class="flex flex-1 flex-col overflow-y-auto px-4 py-5">
            @php
                $isMasterActive = request()->routeIs('master.products*') || request()->routeIs('master.barcodes*') || request()->routeIs('master.categories*') || request()->routeIs('master.units*') || request()->routeIs('master.suppliers*') || request()->routeIs('master.customers*') || request()->routeIs('master.bank-accounts*') || request()->routeIs('master.stores*') || request()->routeIs('master.customer-tiers*');
                $isSalesActive = request()->routeIs('sales.transactions*') || request()->routeIs('sales.returns*') || request()->routeIs('sales.invoices*') || request()->routeIs('sales.orders*') || request()->routeIs('customers.receivables*') || request()->routeIs('promotions*') || request()->routeIs('vouchers*') || request()->routeIs('customers.loyalty*') || request()->routeIs('customers.rewards*');
                $isPurchasesActive = request()->routeIs('purchases.*');
                $isInventoryActive = request()->routeIs('warehouse.*') || request()->routeIs('master.stock-card*');
                $isAccountingActive = request()->routeIs('accounting.*') || request()->routeIs('reports.general-ledger*') || request()->routeIs('reports.trial-balance*') || request()->routeIs('assets*') || request()->routeIs('purchases.debt-ledger*') || request()->routeIs('customers.receivables*');
                $isReportsActive = request()->routeIs('reports.*');
                $isSettingsActive = request()->routeIs('settings.*');

                $initialActiveMenu = $isMasterActive ? 'master' : (
                    $isSalesActive ? 'sales' : (
                        $isPurchasesActive ? 'purchases' : (
                            $isInventoryActive ? 'inventory' : (
                                $isAccountingActive ? 'accounting' : (
                                    $isReportsActive ? 'reports' : (
                                        $isSettingsActive ? 'settings' : ''
                                    )
                                )
                            )
                        )
                    )
                );
            @endphp
            <nav class="space-y-1.5"
                 x-data="{
                     activeMenu: '{{ $initialActiveMenu }}',
                     toggle(menu) {
                         this.activeMenu = this.activeMenu === menu ? '' : menu;
                     }
                 }">
                <!-- Group Title -->
                <div class="px-3 pb-1 text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Akses Cepat
                </div>

                <!-- 1. Dashboard Utama -->
                <a href="{{ route('sales.index') }}"
                   class="flex items-center space-x-3.5 rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 {{ request()->routeIs('sales.index') ? 'bg-[#125BCE] text-white font-bold shadow-sm shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10 hover:translate-x-1' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>Dashboard Utama</span>
                </a>

                <!-- 2. Kasir POS (Flagship Action Card) -->
                <a href="{{ route('pos.index') }}"
                   class="flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 bg-gradient-to-r from-emerald-600 via-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black shadow-md shadow-emerald-500/25 ring-1 ring-emerald-400/40 hover:scale-[1.02] cursor-pointer mb-2">
                    <div class="flex items-center space-x-3.5">
                        <svg class="h-5 w-5 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Buka Kasir POS</span>
                    </div>
                    <kbd class="px-1.5 py-0.5 rounded text-[10px] bg-black/25 font-mono">F12</kbd>
                </a>

                <div class="pt-2 pb-1 px-3 text-[10px] font-black uppercase tracking-wider text-slate-400">
                    Modul ERP &amp; Bisnis
                </div>

                <!-- ========================================== -->
                <!-- 1. MODUL MASTER DATA                       -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager', 'store_head', 'finance', 'purchasing', 'warehouse']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('master')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'master' || {{ $isMasterActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'master' || {{ $isMasterActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                                </svg>
                                <span class="tracking-tight">Master Data</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'master' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'master'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            <a href="{{ route('master.products') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.products*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.products*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Data Barang &amp; Harga</span>
                            </a>
                            <a href="{{ route('master.barcodes.print') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.barcodes*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.barcodes*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Cetak Barcode &amp; Label Rak</span>
                            </a>
                            <a href="{{ route('master.categories') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.categories*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.categories*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kategori Produk</span>
                            </a>
                            <a href="{{ route('master.units') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.units*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.units*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Satuan / Unit Kemasan</span>
                            </a>
                            <a href="{{ route('master.suppliers') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.suppliers*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.suppliers*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Pemasok / Supplier</span>
                            </a>
                            <a href="{{ route('master.customers') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.customers*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.customers*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Pelanggan / Customer</span>
                            </a>
                            <a href="{{ route('master.bank-accounts') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.bank-accounts*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.bank-accounts*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Rekening Kas &amp; Bank</span>
                            </a>
                            <a href="{{ route('master.stores') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.stores*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.stores*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Toko, Cabang &amp; Gudang</span>
                            </a>
                            <a href="{{ route('master.customer-tiers') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.customer-tiers*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.customer-tiers*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Level / Tier Pelanggan</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 2. MODUL PENJUALAN (SALES & POS)           -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager', 'store_head', 'cashier', 'sales', 'finance']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('sales')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'sales' || {{ $isSalesActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'sales' || {{ $isSalesActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                <span class="tracking-tight">Penjualan</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'sales' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'sales'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            <a href="{{ route('sales.transactions') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('sales.transactions*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('sales.transactions*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Riwayat Transaksi Kasir</span>
                            </a>
                            <a href="{{ route('sales.invoices.create') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('sales.invoices.create*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('sales.invoices.create*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Input Faktur Back-Office</span>
                            </a>
                            <a href="{{ route('sales.returns.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('sales.returns*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('sales.returns*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Retur Penjualan &amp; Refund</span>
                            </a>
                            <a href="{{ route('pwa.index') }}"
                               target="_blank"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 text-[#18BDEB] font-bold hover:bg-white/10">
                                <span>📱</span>
                                <span>Marketplace HP (PWA) ↗</span>
                            </a>
                            <a href="{{ route('sales.orders.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('sales.orders*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('sales.orders*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Pesanan Penjualan (SO)</span>
                            </a>
                            <a href="{{ route('customers.receivables.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('customers.receivables*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('customers.receivables*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Piutang Pelanggan (AR)</span>
                            </a>
                            <a href="{{ route('sales.points.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('sales.points*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('sales.points*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>⭐ Cek Poin &amp; Saldo Member</span>
                            </a>
                            <a href="{{ route('promotions.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('promotions*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('promotions*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Promo &amp; Diskon (PWP)</span>
                            </a>
                            <a href="{{ route('vouchers.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('vouchers*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('vouchers*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Voucher &amp; Gift Card</span>
                            </a>
                            <a href="{{ route('customers.loyalty.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('customers.loyalty*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('customers.loyalty*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Poin Reward &amp; Loyalty</span>
                            </a>
                            <a href="{{ route('customers.rewards.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('customers.rewards*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('customers.rewards*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Katalog Hadiah Poin</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 3. MODUL PEMBELIAN (PURCHASING & AP)       -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager', 'purchasing', 'warehouse', 'store_head', 'finance']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('purchases')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'purchases' || {{ $isPurchasesActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'purchases' || {{ $isPurchasesActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="tracking-tight">Pembelian</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'purchases' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'purchases'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            <a href="{{ route('purchases.orders.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.orders*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.orders*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Pesanan Pembelian (PO)</span>
                            </a>
                            <a href="{{ route('purchases.receipts.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.receipts*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.receipts*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Penerimaan Fisik (LPB)</span>
                            </a>
                            @php
                                $isPurchaseListActive = (request()->routeIs('purchases.index') || request()->routeIs('purchases.show')) && !request()->routeIs('purchases.orders*') && !request()->routeIs('purchases.receipts*') && !request()->routeIs('purchases.create*') && !request()->routeIs('purchases.recommendations*') && !request()->routeIs('purchases.debt-ledger*');
                            @endphp
                            <a href="{{ route('purchases.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ $isPurchaseListActive ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isPurchaseListActive ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Faktur Pembelian (AP)</span>
                            </a>
                            @if(in_array(auth()->user()?->role, ['owner', 'manager', 'purchasing']))
                                <a href="{{ route('purchases.create') }}"
                                   class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.create') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.create') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                    <span>Input Pembelian Cepat</span>
                                </a>
                            @endif
                            <a href="{{ route('purchases.debt-ledger') }}"
                               class="flex items-center justify-between rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.debt-ledger*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.debt-ledger*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                    <span>Hutang Supplier &amp; Bayar</span>
                                </div>
                                @php
                                    $sidebarOverdueDebt = \Illuminate\Support\Facades\Cache::remember('sidebar_overdue_debt_count', 30, function() {
                                        return \App\Models\Purchase::where('payment_type', 'TEMPO')
                                            ->where('status', 'RECEIVED')
                                            ->where('due_date', '<', today())
                                            ->whereColumn('paid_amount', '<', 'grand_total')
                                            ->count();
                                    });
                                @endphp
                                @if($sidebarOverdueDebt > 0)
                                    <span class="px-1.5 py-0.2 rounded-full text-[9.5px] font-black bg-rose-500 text-white shadow-2xs" title="{{ $sidebarOverdueDebt }} Faktur Lewat Jatuh Tempo">
                                        {{ $sidebarOverdueDebt }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('purchases.returns.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.returns*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.returns*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Retur Pembelian</span>
                            </a>
                            <a href="{{ route('purchases.recommendations') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.recommendations*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.recommendations*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Smart Restock Replenish</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 4. MODUL PERSEDIAAN (INVENTORY & GUDANG)   -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager', 'store_head', 'warehouse', 'purchasing']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('inventory')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'inventory' || {{ $isInventoryActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'inventory' || {{ $isInventoryActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <span class="tracking-tight">Persediaan</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'inventory' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'inventory'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            <a href="{{ route('warehouse.inventory.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.inventory*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.inventory*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Monitoring & Valuasi Stok</span>
                            </a>
                            <a href="{{ route('warehouse.opening-stock.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.opening-stock*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.opening-stock*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>⚖️ Saldo Awal Item</span>
                            </a>
                            <a href="{{ route('warehouse.minimum-stock.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.minimum-stock*') ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold shadow-xs shadow-amber-500/25' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.minimum-stock*') ? 'bg-white' : 'bg-amber-400' }}"></span>
                                <span>🚨 Stok Minimum &amp; Restock</span>
                            </a>
                            <a href="{{ route('warehouse.movements.in.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.movements.in*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-xs shadow-emerald-500/25' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.movements.in*') ? 'bg-white' : 'bg-emerald-400' }}"></span>
                                <span>📥 Item Masuk (Stock In)</span>
                            </a>
                            <a href="{{ route('warehouse.movements.out.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.movements.out*') ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white font-bold shadow-xs shadow-rose-500/25' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.movements.out*') ? 'bg-white' : 'bg-rose-400' }}"></span>
                                <span>📤 Item Keluar (Stock Out)</span>
                            </a>
                            <a href="{{ route('warehouse.adjustments.quarantine') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.adjustments.quarantine*') ? 'bg-gradient-to-r from-rose-600 to-pink-600 text-white font-bold shadow-xs shadow-rose-500/25' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.adjustments.quarantine*') ? 'bg-white' : 'bg-rose-400' }}"></span>
                                <span>Barang Rusak &amp; Karantina</span>
                            </a>
                            <a href="{{ route('warehouse.adjustments.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ (request()->routeIs('warehouse.adjustments*') && !request()->routeIs('warehouse.adjustments.quarantine*')) ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ (request()->routeIs('warehouse.adjustments*') && !request()->routeIs('warehouse.adjustments.quarantine*')) ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Penyesuaian Stok (Adjustment)</span>
                            </a>
                            <a href="{{ route('master.stock-card') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('master.stock-card*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('master.stock-card*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kartu Stok Mutasi</span>
                            </a>
                            <a href="{{ route('warehouse.requests.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.requests*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.requests*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Permintaan Cabang (TR)</span>
                            </a>
                            <a href="{{ route('warehouse.transfers.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.transfers*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.transfers*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Transfer Antar-Cabang (TO)</span>
                            </a>
                            <a href="{{ route('warehouse.opnames.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.opnames*') && !request()->routeIs('warehouse.opname-pwa*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.opnames*') && !request()->routeIs('warehouse.opname-pwa*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Blind Stock Opname</span>
                            </a>
                            <a href="{{ route('warehouse.opname-pwa.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.opname-pwa*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.opname-pwa*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>📱 PWA Opname (HP)</span>
                            </a>
                            <a href="{{ route('warehouse.assemblies.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.assemblies*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.assemblies*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Perakitan Paket (BOM)</span>
                            </a>
                            <a href="{{ route('warehouse.batches.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.batches*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.batches*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Batch Lot &amp; FEFO Expired</span>
                            </a>
                            <a href="{{ route('warehouse.consignments.receipts.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('warehouse.consignments*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('warehouse.consignments*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Konsinyasi Masuk</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 5. MODUL AKUNTANSI & KEUANGAN              -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager', 'finance']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('accounting')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'accounting' || {{ $isAccountingActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'accounting' || {{ $isAccountingActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <span class="tracking-tight">Akuntansi</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'accounting' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'accounting'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            <a href="{{ route('accounting.cash.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.cash*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.cash*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kas Masuk &amp; Keluar</span>
                            </a>
                            <a href="{{ route('accounting.coa.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.coa*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.coa*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Bagan Akun (COA)</span>
                            </a>
                            <a href="{{ route('accounting.journals.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.journals*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.journals*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Jurnal Umum &amp; Biaya</span>
                            </a>
                            <a href="{{ route('accounting.transaction-logs.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.transaction-logs*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.transaction-logs*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>📜 Catatan Transaksi</span>
                            </a>
                            <a href="{{ route('reports.general-ledger') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.general-ledger*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.general-ledger*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Buku Besar (GL)</span>
                            </a>
                            <a href="{{ route('reports.trial-balance') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.trial-balance*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.trial-balance*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Neraca Saldo (Trial Balance)</span>
                            </a>
                            <a href="{{ route('purchases.debt-ledger') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('purchases.debt-ledger*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('purchases.debt-ledger*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kartu Hutang Usaha (AP)</span>
                            </a>
                            <a href="{{ route('customers.receivables.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('customers.receivables*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('customers.receivables*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kartu Piutang Usaha (AR)</span>
                            </a>
                            <a href="{{ route('assets.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('assets*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('assets*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Aset Tetap & Depresiasi</span>
                            </a>
                            <a href="{{ route('accounting.opening.coa.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.opening*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.opening*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>⚖️ Saldo Awal Migrasi</span>
                            </a>
                            <a href="{{ route('accounting.settings.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.settings*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.settings*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>⚙️ Setting Perkiraan</span>
                            </a>
                            <a href="{{ route('accounting.closing.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.closing*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.closing*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>📅 Tutup Buku Tahunan</span>
                            </a>
                            <a href="{{ route('accounting.deposits.customer.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.deposits.customer*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.deposits.customer*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Deposito Pelanggan</span>
                            </a>
                            <a href="{{ route('accounting.deposits.supplier.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.deposits.supplier*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.deposits.supplier*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Deposito / DP Supplier</span>
                            </a>
                            <a href="{{ route('accounting.tax.settings') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.tax.settings*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.tax.settings*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>⚙️ Pengaturan Pajak (PKP)</span>
                            </a>
                            <a href="{{ route('accounting.tax.report') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('accounting.tax.report*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('accounting.tax.report*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>📑 Rekap PPN (SPT Masa)</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 6. MODUL LAPORAN (FINANCIAL & BI REPORTS)  -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager', 'finance']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('reports')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'reports' || {{ $isReportsActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'reports' || {{ $isReportsActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <span class="tracking-tight">Laporan</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'reports' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'reports'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            <a href="{{ route('reports.sales') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.sales*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.sales*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Laporan Penjualan (Ritel)</span>
                            </a>
                            <a href="{{ route('reports.purchases') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.purchases*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.purchases*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Laporan Pembelian</span>
                            </a>
                            <div class="pt-2 pb-1 px-3 text-[10px] font-black uppercase tracking-wider text-slate-400">Persediaan &amp; Stok</div>
                            <a href="{{ route('reports.inventory.valuation') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.inventory.valuation*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory.valuation*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Nilai Persediaan (Valuation)</span>
                            </a>
                            <a href="{{ route('reports.inventory.stock-card') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.inventory.stock-card*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory.stock-card*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kartu Stok Barang</span>
                            </a>
                            <a href="{{ route('reports.inventory.low-stock') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.inventory.low-stock*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory.low-stock*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Peringatan Stok Minimum</span>
                            </a>
                            <a href="{{ route('reports.inventory.turnover') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.inventory.turnover*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory.turnover*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Fast / Slow / Dead Stock</span>
                            </a>
                            <a href="{{ route('reports.inventory.expiry') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.inventory.expiry*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory.expiry*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Kedaluwarsa Barang</span>
                            </a>
                            <a href="{{ route('reports.inventory.opname-discrepancy') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.inventory.opname-discrepancy*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory.opname-discrepancy*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Selisih Stock Opname</span>
                            </a>
                            <div class="pt-2 pb-1 px-3 text-[10px] font-black uppercase tracking-wider text-slate-400">Keuangan &amp; Kasir</div>
                            <a href="{{ route('reports.aging-receivables') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.aging-receivables*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.aging-receivables*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Analisa Umur Piutang (AR)</span>
                            </a>
                            <a href="{{ route('reports.aging-payables') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.aging-payables*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.aging-payables*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Analisa Umur Hutang (AP)</span>
                            </a>
                            <div class="pt-2 pb-1 px-3 text-[10px] font-black uppercase tracking-wider text-slate-400">Operasional &amp; Kasir</div>
                            <a href="{{ route('reports.operational.peak-hours') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.operational.peak-hours*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.operational.peak-hours*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Jam Sibuk (Peak Hours)</span>
                            </a>
                            <a href="{{ route('reports.operational.void-returns') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.operational.void-returns*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.operational.void-returns*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Audit Void &amp; Retur</span>
                            </a>
                            <a href="{{ route('reports.operational.consignment') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.operational.consignment*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.operational.consignment*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Titip Jual Konsinyasi</span>
                            </a>
                            <a href="{{ route('reports.shifts') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.shifts*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.shifts*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Shift Kasir &amp; Z-Report</span>
                            </a>
                            <a href="{{ route('reports.income-statement') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.income-statement*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.income-statement*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Laba Rugi (P&amp;L)</span>
                            </a>
                            <a href="{{ route('reports.balance-sheet') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.balance-sheet*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.balance-sheet*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Neraca Keuangan</span>
                            </a>
                            <a href="{{ route('reports.cash-flow') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.cash-flow*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.cash-flow*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>Laporan Arus Kas</span>
                            </a>
                            <a href="{{ route('reports.promotions') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('reports.promotions*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.promotions*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🎁 Diskon, Voucher &amp; Poin</span>
                            </a>
                        </div>
                    </div>
                @endif

                <!-- ========================================== -->
                <!-- 7. MODUL PENGATURAN & SISTEM               -->
                <!-- ========================================== -->
                @if(in_array(auth()->user()?->role, ['owner', 'manager']))
                    <div class="space-y-1">
                        <button type="button"
                                @click="toggle('settings')"
                                class="w-full flex items-center justify-between rounded-2xl px-4 py-2.5 text-sm transition-all duration-200 cursor-pointer select-none"
                                :class="(activeMenu === 'settings' || {{ $isSettingsActive ? 'true' : 'false' }}) ? 'bg-[#132D6B] text-white font-bold border border-[#18BDEB]/30 shadow-xs' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10'">
                            <div class="flex items-center space-x-3.5">
                                <svg class="h-5 w-5 shrink-0"
                                     :class="(activeMenu === 'settings' || {{ $isSettingsActive ? 'true' : 'false' }}) ? 'text-[#18BDEB]' : 'text-slate-400'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <span class="tracking-tight">Pengaturan</span>
                            </div>
                            <svg class="h-4 w-4 shrink-0 transition-transform duration-200"
                                 :class="activeMenu === 'settings' ? 'rotate-180 text-[#18BDEB]' : 'text-slate-400'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="activeMenu === 'settings'"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="pl-3 py-1 space-y-1 border-l-2 border-[#132D6B] ml-5 my-1">
                            @if(auth()->user()?->role === 'owner')
                            <a href="{{ route('settings.companies.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.companies*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.companies*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🏢 Kelola Perusahaan (Holding)</span>
                            </a>
                            @endif
                            <a href="{{ route('settings.general') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.general*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.general*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🏢 Profil &amp; Faktur Pajak</span>
                            </a>
                            <a href="{{ route('settings.stores.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.stores*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.stores*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🏪 Cabang &amp; Gudang</span>
                            </a>
                            <a href="{{ route('settings.users.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.users*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.users*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>👥 Pengguna &amp; Hak Akses</span>
                            </a>
                            <a href="{{ route('settings.printer') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.printer*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.printer*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🖨️ Printer &amp; Struk Kasir</span>
                            </a>
                            <a href="{{ route('settings.numbering.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.numbering*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.numbering*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🔢 Penomoran Otomatis</span>
                            </a>
                            <a href="{{ route('settings.cashier') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.cashier*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.cashier*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>💼 Operasional Kasir</span>
                            </a>
                            <a href="{{ route('settings.maintenance.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.maintenance*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.maintenance*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>💾 Pemeliharaan &amp; Backup</span>
                            </a>
                            <a href="{{ route('settings.audit-logs.index') }}"
                               class="flex items-center space-x-2.5 rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.audit-logs*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.audit-logs*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                <span>🛡️ Audit Log Aktivitas</span>
                            </a>
                            <a href="{{ route('settings.update.index') }}"
                               class="flex items-center justify-between rounded-xl px-3 py-2 text-xs transition-all duration-200 {{ request()->routeIs('settings.update*') ? 'bg-[#125BCE] text-white font-bold shadow-xs shadow-[#125BCE]/40 ring-1 ring-[#18BDEB]/30' : 'text-slate-300 hover:text-white font-semibold hover:bg-white/10' }}">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('settings.update*') ? 'bg-[#18BDEB]' : 'bg-slate-500' }}"></span>
                                    <span>🔄 Pembaruan Sistem</span>
                                </div>
                                @if(config('version.update_available', false))
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9.5px] font-black bg-[#FFB800] text-slate-950 animate-pulse">
                                        v{{ config('version.latest_version') }}
                                    </span>
                                @endif
                            </a>
                        </div>
                    </div>
                @endif
            </nav>

            <!-- SIDEBAR BOTTOM CARDS (OFFICIAL SIGMA NAVY IDENTITY) -->
            <div class="mt-auto pt-5 space-y-2.5">
                <!-- User Profile Card in Sidebar -->
                <div class="rounded-2xl bg-[#071536]/80 p-3.5 border border-white/10 dark:bg-slate-900/60 dark:border-slate-700/60 shadow-xs">
                    <div class="flex items-center space-x-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-[#125BCE] to-[#18BDEB] font-black text-sm text-white shadow-md shadow-[#125BCE]/30">
                            {{ substr(auth()->user()?->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-xs text-white truncate">
                                {{ auth()->user()?->name ?? 'Administrator' }}
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-[#125BCE]/30 text-[#18BDEB] border border-[#18BDEB]/30">
                                    {{ strtoupper(auth()->user()?->role ?? 'USER') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2.5 pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                        <span class="text-[10px] text-slate-400 font-mono truncate max-w-[120px]">
                            {{ auth()->user()?->store?->name ?? 'Semua Toko' }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="flex items-center space-x-1 text-rose-400 hover:text-rose-300 font-bold text-[11px] hover:underline cursor-pointer">
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Database Status Badge -->
                <div class="rounded-2xl bg-[#071536]/60 p-2.5 border border-white/10 dark:bg-slate-900/40 dark:border-slate-800 text-xs font-semibold text-slate-400 flex items-center justify-between">
                    <span class="uppercase tracking-wider text-[10px]">ERP System</span>
                    <span class="inline-flex items-center gap-1 text-emerald-400 font-bold text-[11px]">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Online
                    </span>
                </div>
            </div>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- 2. MAIN AREA (TOPBAR + MAIN CONTENT CANVAS)                    -->
    <!-- ============================================================== -->
    <div class="md:pl-64 flex flex-col flex-1 min-h-screen">
        
        <!-- STICKY TOPBAR (h-18, BACKDROP BLUR) -->
        <header class="sticky top-0 z-20 flex h-18 shrink-0 items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-800/95 px-4 md:px-8 backdrop-blur-xl transition-colors">
            <!-- Mobile Brand -->
            <div class="flex items-center space-x-2.5 md:hidden">
                <img src="{{ asset('images/sigma-erp-logo.png') }}" alt="SIGMA ERP" class="h-8 w-auto object-contain rounded-lg shrink-0" onerror="this.onerror=null; this.src='{{ asset('logo.svg') }}'">
                <div>
                    <span class="font-black text-sm text-[#0B1F4D] dark:text-white">SIGMA ERP</span>
                </div>
            </div>

            <!-- Desktop Title & Breadcrumbs -->
            <div class="hidden md:flex items-center gap-3">
                <h1 class="text-xl font-black text-[#0B1F4D] dark:text-white tracking-tight">
                    @yield('page_title', 'Sistem Manajemen Toko & Kasir POS')
                </h1>

                <!-- SAKELAR MULTI-COMPANY (DROPDOWN HOLDING) -->
                @if(isset($currentCompany))
                <div class="relative" x-data="{ openCompanyDropdown: false }">
                    <button type="button"
                            @click="openCompanyDropdown = !openCompanyDropdown"
                            class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-700/60 dark:hover:bg-slate-700 border border-slate-300 dark:border-slate-600 text-xs font-black text-slate-800 dark:text-white transition-all shadow-2xs cursor-pointer"
                            title="Entitas Perusahaan Aktif: {{ $currentCompany->name }}">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white text-[10px]">🏢</span>
                        <span class="max-w-[170px] xl:max-w-[220px] truncate">{{ $currentCompany->name }}</span>
                        @if(isset($canSwitchCompany) && $canSwitchCompany && isset($availableCompanies) && $availableCompanies->count() > 1)
                            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': openCompanyDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        @endif
                    </button>

                    @if(isset($canSwitchCompany) && $canSwitchCompany && isset($availableCompanies))
                    <div x-show="openCompanyDropdown"
                         x-cloak
                         @click.away="openCompanyDropdown = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute left-0 mt-2 w-72 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-2 z-50 text-left">
                        <div class="px-3.5 py-1.5 border-b border-slate-100 dark:border-slate-700/60 text-[10.5px] font-black uppercase tracking-wider text-slate-400">
                            Beralih Entitas Perusahaan
                        </div>

                        <div class="max-h-60 overflow-y-auto py-1">
                            @foreach($availableCompanies as $comp)
                                <form action="{{ route('company.switch') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                    <button type="submit"
                                            class="w-full flex items-center justify-between px-3.5 py-2.5 text-xs hover:bg-slate-100 dark:hover:bg-slate-700/70 transition-colors cursor-pointer {{ $comp->id === $currentCompany->id ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-black' : 'text-slate-700 dark:text-slate-300 font-bold' }}">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="text-sm">🏢</span>
                                            <div class="truncate text-left">
                                                <div class="truncate">{{ $comp->name }}</div>
                                                <div class="text-[9.5px] font-mono text-slate-400">{{ $comp->code }}</div>
                                            </div>
                                        </div>
                                        @if($comp->id === $currentCompany->id)
                                            <span class="text-blue-600 dark:text-blue-400 font-black text-xs">✓</span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>

                        <div class="border-t border-slate-100 dark:border-slate-700/60 pt-1 mt-1 px-2">
                            <a href="{{ route('settings.companies.index') }}"
                               class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs font-extrabold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-slate-700/60 transition-colors">
                                <span>➕</span>
                                <span>Kelola / Tambah Perusahaan</span>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Right Actions: Quick Search, Buka Kasir POS, Dark Mode Toggle, Profile -->
            <div class="flex items-center space-x-3">
                <!-- Global Quick Search Trigger -->
                <a href="{{ route('master.products') }}"
                   class="hidden lg:flex items-center gap-2 bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-700/60 dark:hover:bg-slate-700 border border-[#E2E8F0] dark:border-slate-700 px-3.5 py-1.5 rounded-full text-slate-600 dark:text-slate-300 text-xs transition-colors">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Cari Produk / Barcode...</span>
                    <kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-800 rounded text-[9.5px] font-mono shadow-2xs">F1</kbd>
                </a>

                <!-- SYSTEM ENVIRONMENT MODE BADGE (DEV / PROD) -->
                @if(app()->isProduction())
                    <a href="{{ route('settings.maintenance.index') }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/80 shadow-2xs select-none transition-colors"
                       title="Mode Produksi Aktif: Kecepatan transaksi maksimal & cache aktif">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="hidden sm:inline">MODE</span> PROD
                    </a>
                @else
                    <a href="{{ route('settings.maintenance.index') }}"
                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-black bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/80 shadow-2xs select-none transition-colors"
                       title="Mode Development Aktif: Live reload kode & debug aktif">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="hidden sm:inline">MODE</span> DEV
                    </a>
                @endif

                @if(config('version.update_available', false))
                    <!-- UPDATE NOTIFICATION BADGE -->
                    <a href="{{ route('settings.update.index') }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-black bg-[#FFB800]/20 text-[#D97706] dark:text-[#FFB800] border border-[#FFB800]/50 hover:bg-[#FFB800]/30 shadow-xs transition-all animate-bounce"
                       title="Pembaruan Baru Tersedia!">
                        <span class="w-2 h-2 rounded-full bg-[#FFB800]"></span>
                        <span class="hidden sm:inline">Update v{{ config('version.latest_version') }}</span>
                        <span>🚀</span>
                    </a>
                @endif

                <!-- TOMBOL CEPAT BUKA KASIR POS [F12] (PROMINENT GREEN GLOW) -->
                <a href="{{ route('pos.index') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs px-4 py-2 shadow-sm hover:shadow-md hover:scale-102 active:scale-95 transition-all">
                    <span>🛒</span>
                    <span class="hidden sm:inline font-black tracking-wide">BUKA KASIR POS</span>
                    <kbd class="px-1.5 py-0.5 rounded text-[9.5px] bg-black/20 font-mono">F12</kbd>
                </a>

                <!-- DARK / LIGHT MODE SWITCHER (CIRCULAR HOVER BUTTON) -->
                <button type="button"
                        id="theme-toggle-btn"
                        @click="toggleTheme()"
                        class="group flex h-10 w-10 items-center justify-center rounded-full border border-[#E2E8F0] bg-white shadow-xs hover:bg-slate-100 hover:shadow-md hover:scale-105 active:scale-95 transition-all duration-200 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 cursor-pointer"
                        title="Ganti Mode Gelap / Terang">
                    <template x-if="isDark">
                        <svg class="h-5 w-5 text-amber-400 group-hover:rotate-45 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </template>
                    <template x-if="!isDark">
                        <svg class="h-5 w-5 text-slate-700 dark:text-slate-300 group-hover:-rotate-12 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </template>
                </button>

                <!-- User Profile Pill (Desktop) -->
                <div class="hidden sm:flex items-center space-x-2.5 pl-2 border-l border-slate-200/80 dark:border-slate-700/80">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-tr from-[#125BCE] to-[#0B1F4D] text-white font-black text-xs shadow-xs">
                        {{ substr(auth()->user()?->name ?? 'A', 0, 1) }}
                    </div>
                    <div class="text-left hidden xl:block">
                        <div class="text-xs font-bold text-slate-900 dark:text-white leading-tight">
                            {{ auth()->user()?->name ?? 'Administrator' }}
                        </div>
                        <div class="text-[10px] font-black uppercase tracking-wider text-[#125BCE] dark:text-[#18BDEB]">
                            {{ strtoupper(auth()->user()?->role ?? 'USER') }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="ml-1 inline">
                        @csrf
                        <button type="submit" title="Keluar dari sistem" class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- FLASH NOTIFICATIONS -->
        @if(session('success'))
            <div class="mx-4 md:mx-8 mt-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between shadow-xs animate-in fade-in duration-150">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold text-xs">✓</span>
                    <span class="font-bold">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-400 hover:text-emerald-950 font-bold text-sm cursor-pointer">&times;</button>
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 md:mx-8 mt-4 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 text-xs flex items-center justify-between shadow-xs animate-in fade-in duration-150">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center font-bold text-xs">✕</span>
                    <span class="font-bold">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 dark:text-rose-400 hover:text-rose-950 font-bold text-sm cursor-pointer">&times;</button>
            </div>
        @endif

        <!-- MAIN WORKSPACE CONTENT -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto space-y-6">
            @yield('content')
        </main>

        <!-- FOOTER -->
        <footer class="border-t border-[#E2E8F0] dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 py-3.5 px-6 md:px-8 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-bold text-[#0B1F4D] dark:text-slate-300">SIGMA ERP</span>
                <span>•</span>
                <span>Retail &amp; POS Enterprise Edition</span>
            </div>
            <div class="flex items-center gap-3 font-mono text-[11px]">
                <span class="hidden sm:inline">[F1] Cari Item</span>
                <span class="hidden sm:inline">[F12] Kasir POS</span>
                <span class="text-[#125BCE] dark:text-[#18BDEB] font-bold">PostgreSQL 16</span>
            </div>
        </footer>
    </div>

    <!-- ============================================================== -->
    <!-- 3. MOBILE BOTTOM NAVIGATION (TOUCH-FRIENDLY ALA WAREHOUSE)     -->
    <!-- ============================================================== -->
    <nav class="fixed bottom-0 left-0 right-0 z-40 flex h-16 items-center justify-around border-t border-[#E2E8F0] bg-white/95 px-2 backdrop-blur-md dark:border-slate-800 dark:bg-slate-800/95 md:hidden shadow-lg">
        <a href="{{ route('sales.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-colors {{ request()->routeIs('sales.index') ? 'text-[#125BCE] dark:text-[#18BDEB] font-bold' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400' }}">
            <svg class="h-5 w-5 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="mt-1">Dashboard</span>
        </a>
        <a href="{{ route('pos.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400">
            <svg class="h-5 w-5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="mt-1">Kasir</span>
        </a>
        <a href="{{ route('sales.transactions') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-colors {{ request()->routeIs('sales.transactions*') ? 'text-[#125BCE] dark:text-[#18BDEB] font-bold' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400' }}">
            <svg class="h-5 w-5 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="mt-1">Transaksi</span>
        </a>
        <a href="{{ route('purchases.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-colors {{ request()->routeIs('purchases*') ? 'text-[#125BCE] dark:text-[#18BDEB] font-bold' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400' }}">
            <svg class="h-5 w-5 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span class="mt-1">Beli / PO</span>
        </a>
        <a href="{{ route('master.products') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-colors {{ request()->routeIs('master*') ? 'text-[#125BCE] dark:text-[#18BDEB] font-bold' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400' }}">
            <svg class="h-5 w-5 stroke-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <span class="mt-1">Barang</span>
        </a>
    </nav>

    <!-- JAVASCRIPT STATE ENGINE -->
    <script>
        window.toggleSigmaTheme = function() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            return !isDark;
        };

        function sigmaErpLayout() {
            return {
                isDark: document.documentElement.classList.contains('dark'),

                initLayout() {
                    const stored = localStorage.getItem('theme');
                    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (stored === 'dark' || (!stored && prefersDark)) {
                        document.documentElement.classList.add('dark');
                        this.isDark = true;
                    } else {
                        document.documentElement.classList.remove('dark');
                        this.isDark = false;
                    }
                },

                toggleTheme() {
                    this.isDark = window.toggleSigmaTheme();
                },

                handleGlobalKeys(e) {
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                        return;
                    }
                    if (e.key === 'F12') {
                        e.preventDefault();
                        window.location.href = '{{ route("pos.index") }}';
                    } else if (e.key === 'F1') {
                        e.preventDefault();
                        window.location.href = '{{ route("master.products") }}';
                    }
                }
            };
        }
    </script>

    @stack('scripts')
    <!-- Floating PWA Install Banner for Mobile HP -->
    <div id="pwa-install-banner" class="fixed bottom-4 left-4 right-4 md:left-auto md:right-6 md:w-80 bg-slate-900/95 backdrop-blur-md border border-cyan-500/30 text-white p-3.5 rounded-2xl shadow-2xl flex items-center justify-between gap-3 z-50 transition-all duration-300" style="display: none;">
        <div class="flex items-center gap-3">
            <img src="{{ asset('sigma-icon-192.png') }}" alt="SIGMA" class="w-10 h-10 rounded-xl shadow-md border border-cyan-400/20">
            <div>
                <div class="font-bold text-xs text-cyan-300">Pasang Aplikasi SIGMA</div>
                <div class="text-[11px] text-slate-300">Layar penuh &amp; instan di HP</div>
            </div>
        </div>
        <div class="flex items-center gap-1.5">
            <button id="pwa-install-btn" class="px-3 py-1.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-semibold text-xs rounded-xl shadow-lg transition cursor-pointer">Pasang</button>
            <button onclick="document.getElementById('pwa-install-banner').style.display = 'none'" class="p-1.5 text-slate-400 hover:text-white rounded-lg text-xs cursor-pointer">&times;</button>
        </div>
    </div>

    <script>
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const banner = document.getElementById('pwa-install-banner');
            if (banner) banner.style.display = 'flex';
        });

        document.addEventListener('DOMContentLoaded', () => {
            const btn = document.getElementById('pwa-install-btn');
            if (btn) {
                btn.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        const { outcome } = await deferredPrompt.userChoice;
                        if (outcome === 'accepted') {
                            const banner = document.getElementById('pwa-install-banner');
                            if (banner) banner.style.display = 'none';
                        }
                        deferredPrompt = null;
                    }
                });
            }
        });
    </script>
</body>
</html>
