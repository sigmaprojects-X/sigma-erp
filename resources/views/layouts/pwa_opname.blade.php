<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0B1F4D">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SIGMA Opname">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'SIGMA Warehouse PWA - Opname Fisik')</title>

    <link rel="manifest" href="{{ route('warehouse.opname-pwa.manifest') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Vite / Fallback) -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['Plus Jakarta Sans', 'sans-serif'],
                        }
                    }
                }
            }
        </script>
    @endif

    <script>
        // Init dark mode immediately to avoid FOUC
        (function() {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (stored === 'dark' || (!stored && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch(e) {}
        })();
    </script>

    <!-- HTML5 QR / Barcode Scanner CDN -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        [x-cloak] { display: none !important; }
        /* Optimasi Touch Mobile */
        * {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }
        body {
            overscroll-behavior-y: none;
            user-select: none;
        }
        input, textarea {
            user-select: text;
        }
        /* Custom Scrollbar tipis */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.3);
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100 min-h-screen flex flex-col antialiased font-sans pb-16 selection:bg-blue-600 selection:text-white transition-colors duration-200">

    <!-- TOP HEADER BAR (RESPONSIVE DESKTOP & MOBILE) -->
    <header class="sticky top-0 z-40 bg-white/95 dark:bg-slate-850/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-4 md:px-6 py-3 flex items-center justify-between shadow-xs transition-colors">
        <div class="flex items-center gap-3">
            @yield('header_back')
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-[#0B1F4D] p-1 flex items-center justify-center shadow-xs shrink-0 border border-[#132D6B]">
                    <img src="{{ asset('images/sigma-erp-logo.png') }}" alt="SIGMA" class="w-full h-full object-contain">
                </div>
                <div>
                    <div class="flex items-center gap-1.5 leading-tight">
                        <span class="text-xs md:text-sm font-extrabold tracking-tight text-slate-900 dark:text-white uppercase">SIGMA WAREHOUSE</span>
                        <span class="px-1.5 py-0.2 rounded text-[9.5px] font-black bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 uppercase tracking-wider">PWA</span>
                    </div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium truncate max-w-[220px] md:max-w-md">
                        @yield('header_subtitle', 'Stock Opname Fisik & Barcode Scanner')
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Online / Offline Status Indicator -->
            <div id="onlineBadge" class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/60">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-mono">ONLINE</span>
            </div>

            <!-- Tombol Kembali ke Menu ERP Utama -->
            <a href="{{ route('sales.index') }}"
               class="px-3 py-1.5 rounded-xl bg-[#125BCE] hover:bg-[#0E4DB3] active:bg-[#0B3D8F] text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-xs transition-all hover:scale-102 cursor-pointer"
               title="Kembali ke Dashboard ERP">
                <span>🏠</span>
                <span class="hidden sm:inline">Menu ERP</span>
            </a>

            <!-- Tombol Rekonsiliasi PC Back-Office -->
            <a href="{{ route('warehouse.opnames.index') }}"
               class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-colors hidden md:inline-flex items-center gap-1.5 text-xs font-bold"
               title="Buka Rekonsiliasi Opname di PC Back-Office">
                <span>🖥️</span>
                <span>PC Opname</span>
            </a>

            <!-- Dark / Light Mode Switcher -->
            <button type="button"
                    onclick="toggleTheme()"
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
                    title="Ganti Mode Gelap / Terang">
                <span id="themeIcon">🌙</span>
            </button>
        </div>
    </header>

    <!-- CONTENT WRAPPER (RESPONSIVE GRID DESKTOP & MOBILE) -->
    <main class="flex-1 p-4 md:p-6 max-w-5xl mx-auto w-full">
        @yield('content')
    </main>

    <!-- SOUND EFFECTS ENGINE (Web Audio API - Bekerja Offline Tanpa Download File Audio) -->
    <script>
        const AudioEngine = {
            ctx: null,
            init() {
                if (!this.ctx) {
                    this.ctx = new (window.AudioContext || window.webkitAudioContext)();
                }
            },
            beep(freq = 880, duration = 0.1, type = 'sine') {
                try {
                    this.init();
                    if (this.ctx.state === 'suspended') {
                        this.ctx.resume();
                    }
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = type;
                    osc.frequency.setValueAtTime(freq, this.ctx.currentTime);
                    gain.gain.setValueAtTime(0.15, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + duration);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + duration);
                } catch (e) {}
            },
            success() {
                this.beep(880, 0.08);
                setTimeout(() => this.beep(1320, 0.12), 90);
            },
            warning() {
                this.beep(400, 0.15, 'triangle');
                setTimeout(() => this.beep(300, 0.2, 'triangle'), 160);
            }
        };

        // Dark Mode Toggle
        function updateThemeIcon() {
            const isDark = document.documentElement.classList.contains('dark');
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.innerText = isDark ? '☀️' : '🌙';
            }
        }
        function toggleTheme() {
            const isDark = document.documentElement.classList.contains('dark');
            if (isDark) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
            updateThemeIcon();
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);

        // Deteksi Online / Offline
        window.addEventListener('online', () => {
            const b = document.getElementById('onlineBadge');
            if (b) {
                b.className = 'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/60';
                b.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span><span class="font-mono">ONLINE</span>';
            }
        });
        window.addEventListener('offline', () => {
            const b = document.getElementById('onlineBadge');
            if (b) {
                b.className = 'flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/60';
                b.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span><span class="font-mono">OFFLINE</span>';
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
