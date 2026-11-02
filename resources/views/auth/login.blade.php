<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIGMA ERP &amp; POS Enterprise</title>
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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
    </style>
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
<body x-data="{
        showPassword: false,
        showForgotModal: false,
        showSupportModal: false,
        isOnline: navigator.onLine,
        fillAccount(email) {
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = 'password123';
                emailInput.dispatchEvent(new Event('input'));
                passInput.dispatchEvent(new Event('input'));
            }
        }
    }"
    x-init="window.addEventListener('online', () => isOnline = true); window.addEventListener('offline', () => isOnline = false)"
    class="bg-[#03091e] text-slate-100 min-h-screen flex flex-col justify-between relative overflow-x-hidden selection:bg-[#00d2ff] selection:text-slate-900">

    <!-- ============================================================== -->
    <!-- 1. LATAR BELAKANG ENTERPRISE: AMBIENT GLOW, GEOMETRI & DIGITAL -->
    <!-- ============================================================== -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <!-- Radial Glow Lighting Top-Center -->
        <div class="absolute -top-40 left-1/4 w-[850px] h-[850px] bg-gradient-to-br from-[#00d2ff]/20 via-[#1d4ed8]/25 to-transparent rounded-full blur-[140px] opacity-90"></div>
        
        <!-- Ambient Royal Blue Glow Behind Login Card -->
        <div class="absolute top-1/4 -right-28 w-[650px] h-[650px] bg-gradient-to-bl from-[#1e40af]/30 via-[#0284c7]/20 to-transparent rounded-full blur-[140px] opacity-80"></div>
        
        <!-- Deep Navy Glow Bottom-Left -->
        <div class="absolute -bottom-48 -left-36 w-[750px] h-[750px] bg-[#0c2b64]/35 rounded-full blur-[150px] opacity-90"></div>

        <!-- Diagonal Dynamic Lighting Rays -->
        <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(0,210,255,0.06)_0%,rgba(18,91,206,0.12)_35%,transparent_65%)]"></div>

        <!-- Top Right Digital Matrix Dots Grid -->
        <div class="absolute top-6 right-8 w-64 h-48 opacity-35">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="matrix-dots" width="16" height="16" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1.5" fill="#00d2ff" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#matrix-dots)" />
            </svg>
        </div>

        <!-- Subtle Retail Supermarket Silhouette Pattern on Left Side -->
        <div class="absolute inset-y-0 left-0 w-full lg:w-3/5 opacity-[0.07] mix-blend-screen pointer-events-none">
            <svg class="w-full h-full object-cover" viewBox="0 0 1000 800" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Aisle Perspective Lines -->
                <path d="M0 800 L450 420 L470 420 L600 800" stroke="#00d2ff" stroke-width="1.5" opacity="0.4" />
                <path d="M100 800 L460 420" stroke="#ffffff" stroke-width="0.8" opacity="0.2" />
                <path d="M250 800 L465 420" stroke="#ffffff" stroke-width="0.8" opacity="0.2" />
                <!-- Shelves Left -->
                <rect x="0" y="280" width="320" height="40" fill="#1e40af" opacity="0.25" rx="4" />
                <rect x="0" y="340" width="350" height="40" fill="#1e40af" opacity="0.2" rx="4" />
                <rect x="0" y="400" width="380" height="45" fill="#1e40af" opacity="0.18" rx="4" />
                <rect x="0" y="470" width="410" height="50" fill="#1e40af" opacity="0.15" rx="4" />
                <!-- Product Silhouettes on Shelves -->
                <circle cx="60" cy="300" r="10" fill="#00d2ff" opacity="0.4" />
                <circle cx="100" cy="300" r="10" fill="#ffffff" opacity="0.3" />
                <circle cx="140" cy="300" r="10" fill="#00d2ff" opacity="0.4" />
                <circle cx="180" cy="300" r="10" fill="#ffffff" opacity="0.3" />
                <rect x="50" y="350" width="22" height="26" rx="3" fill="#ffffff" opacity="0.25" />
                <rect x="85" y="350" width="22" height="26" rx="3" fill="#00d2ff" opacity="0.3" />
                <rect x="120" y="350" width="22" height="26" rx="3" fill="#ffffff" opacity="0.25" />
                <rect x="155" y="350" width="22" height="26" rx="3" fill="#00d2ff" opacity="0.3" />
                <!-- Subtle Floor Grid -->
                <path d="M0 650 H550" stroke="#00d2ff" stroke-width="1" opacity="0.15" />
                <path d="M0 720 H700" stroke="#00d2ff" stroke-width="1" opacity="0.2" />
            </svg>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- 2. KONTEN UTAMA: SPLIT SCREEN (55% KIRI BRANDING | 45% KANAN) -->
    <!-- ============================================================== -->
    <main class="flex-1 w-full max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between px-6 sm:px-10 lg:px-14 py-8 lg:py-12 relative z-10 gap-10 lg:gap-12">

        <!-- ========================================== -->
        <!-- AREA KIRI: BRANDING SIGMA ERP & POS (~55%) -->
        <!-- ========================================== -->
        <div class="w-full lg:w-[55%] flex flex-col justify-center space-y-8 lg:space-y-9 text-left">
            
            <!-- 1. Logo Resmi SIGMA ERP (Diperbesar 20% & Lebih Proporsional) -->
            <div class="flex items-center gap-4 sm:gap-5">
                <div class="p-3 sm:p-3.5 rounded-2xl bg-white/[0.05] border border-white/15 backdrop-blur-md shadow-2xl flex items-center justify-center">
                    <img src="{{ asset('logo-icon.png') }}" alt="SIGMA ERP Logo" class="h-16 sm:h-20 w-auto object-contain drop-shadow-xl">
                </div>
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="text-3xl sm:text-4xl font-black text-white tracking-wider">SIGMA</span>
                        <span class="text-xs sm:text-sm font-black text-[#00d2ff] bg-cyan-950/70 border border-cyan-400/40 px-2.5 py-0.5 rounded-lg tracking-widest uppercase shadow-sm">ERP &amp; POS</span>
                    </div>
                    <span class="text-xs text-slate-300 font-medium tracking-wide mt-0.5 block">Enterprise Retail Management System</span>
                </div>
            </div>

            <!-- 2. Headline & Slogan (Diperbesar & Lebih Jelas) -->
            <div class="space-y-3.5">
                <h1 class="text-4xl sm:text-5xl lg:text-[56px] font-black tracking-tight text-white leading-[1.1]">
                    SIGMA <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00d2ff] via-[#38bdf8] to-[#00b4d8]">ERP &amp; POS</span>
                </h1>
                <p class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#00d2ff] shadow-md shadow-[#00d2ff]"></span>
                    <span>One Integrated System. Smarter Business.</span>
                </p>
                <p class="text-sm sm:text-base text-slate-200/90 max-w-xl font-medium leading-relaxed pt-1">
                    Sistem terintegrasi untuk manajemen toko, kasir, persediaan dan logistik.
                </p>
            </div>

            <!-- 3. Empat Kartu Fitur (Lebih Besar, Ikon & Teks Jelas, Hover Halus) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 sm:gap-4 max-w-2xl">
                
                <!-- Kartu 1: Manajemen Toko -->
                <div class="bg-white/[0.05] hover:bg-white/[0.09] border border-white/12 hover:border-cyan-400/40 backdrop-blur-md rounded-2xl p-4 sm:p-5 flex flex-col items-center justify-center text-center gap-3 shadow-xl shadow-black/20 hover:scale-[1.03] transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#125bce]/40 to-[#00d2ff]/25 border border-cyan-400/35 flex items-center justify-center text-cyan-300 group-hover:scale-110 transition-transform shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-100 group-hover:text-white leading-tight">Manajemen Toko</span>
                </div>

                <!-- Kartu 2: Kasir Modern -->
                <div class="bg-white/[0.05] hover:bg-white/[0.09] border border-white/12 hover:border-cyan-400/40 backdrop-blur-md rounded-2xl p-4 sm:p-5 flex flex-col items-center justify-center text-center gap-3 shadow-xl shadow-black/20 hover:scale-[1.03] transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#125bce]/40 to-[#00d2ff]/25 border border-cyan-400/35 flex items-center justify-center text-cyan-300 group-hover:scale-110 transition-transform shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-100 group-hover:text-white leading-tight">Kasir Modern</span>
                </div>

                <!-- Kartu 3: Inventori Real-Time -->
                <div class="bg-white/[0.05] hover:bg-white/[0.09] border border-white/12 hover:border-cyan-400/40 backdrop-blur-md rounded-2xl p-4 sm:p-5 flex flex-col items-center justify-center text-center gap-3 shadow-xl shadow-black/20 hover:scale-[1.03] transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#125bce]/40 to-[#00d2ff]/25 border border-cyan-400/35 flex items-center justify-center text-cyan-300 group-hover:scale-110 transition-transform shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-100 group-hover:text-white leading-tight">Inventori Real-Time</span>
                </div>

                <!-- Kartu 4: Logistik Terintegrasi -->
                <div class="bg-white/[0.05] hover:bg-white/[0.09] border border-white/12 hover:border-cyan-400/40 backdrop-blur-md rounded-2xl p-4 sm:p-5 flex flex-col items-center justify-center text-center gap-3 shadow-xl shadow-black/20 hover:scale-[1.03] transition-all duration-300 group">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-[#125bce]/40 to-[#00d2ff]/25 border border-cyan-400/35 flex items-center justify-center text-cyan-300 group-hover:scale-110 transition-transform shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                    </div>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-100 group-hover:text-white leading-tight">Logistik Terintegrasi</span>
                </div>
            </div>

            <!-- 4. Bagian Identitas & Kepercayaan (Pill Trust Badge) -->
            <div class="pt-1">
                <div class="inline-flex items-center gap-3.5 px-5 py-3.5 rounded-2xl bg-gradient-to-r from-blue-950/80 via-blue-900/40 to-blue-950/20 border border-blue-500/25 backdrop-blur-md shadow-xl max-w-md">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-[#125bce] to-[#00d2ff] flex items-center justify-center text-white shadow-md shadow-cyan-500/20 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-extrabold text-sm sm:text-base text-white flex items-center gap-1.5">
                            <span>Aman</span>
                            <span class="text-cyan-400">•</span>
                            <span>Andal</span>
                            <span class="text-cyan-400">•</span>
                            <span>Profesional</span>
                        </div>
                        <p class="text-xs text-slate-300 font-normal mt-0.5">Solusi bisnis terbaik untuk masa depan Anda</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- AREA KANAN: FORM LOGIN ENTERPRISE (~45%)   -->
        <!-- ========================================== -->
        <div class="w-full lg:w-[45%] flex items-center justify-center">
            <div class="bg-white rounded-[32px] p-8 sm:p-10 shadow-2xl shadow-blue-950/60 border border-slate-100 w-full max-w-[450px] text-slate-900 relative">
                
                <!-- Icon Avatar Header -->
                <div class="flex justify-center mb-3.5">
                    <div class="w-16 h-16 rounded-full bg-blue-50/90 border-4 border-blue-100 flex items-center justify-center shadow-sm">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#125bce] to-[#00d2ff] flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Judul & Subjudul Form Login -->
                <div class="text-center mb-6">
                    <h2 class="text-2xl sm:text-[26px] font-black text-slate-900 tracking-tight">Selamat Datang Kembali</h2>
                    <p class="text-xs sm:text-sm text-slate-500 font-normal mt-1">Masuk untuk melanjutkan aktivitas bisnis Anda.</p>
                </div>

                <!-- Notifikasi Status / Pesan Validasi -->
                @if(session('status'))
                    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start gap-2">
                        <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="space-y-0.5">
                            @foreach($errors->all() as $err)
                                <div>{{ $err }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Form Login Resmi (Backend Authentikasi Tetap Utuh) -->
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4.5">
                    @csrf

                    <!-- 1. Input Alamat Email -->
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                placeholder="Alamat Email"
                                class="w-full pl-11 pr-4 py-3.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 focus:border-[#125bce] focus:ring-4 focus:ring-blue-500/10 rounded-2xl text-sm font-semibold text-slate-900 transition-all placeholder:text-slate-400 placeholder:font-normal">
                        </div>
                    </div>

                    <!-- 2. Input Kata Sandi dengan Show/Hide Password -->
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                placeholder="Password"
                                class="w-full pl-11 pr-11 py-3.5 bg-slate-50/80 hover:bg-white focus:bg-white border border-slate-200 focus:border-[#125bce] focus:ring-4 focus:ring-blue-500/10 rounded-2xl text-sm font-semibold text-slate-900 transition-all placeholder:text-slate-400 placeholder:font-normal">
                            
                            <!-- Toggle Show / Hide Password -->
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer focus:outline-none"
                                title="Lihat / Sembunyikan Password">
                                <template x-if="!showPassword">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </template>
                                <template x-if="showPassword">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Checkbox Ingat Sesi & Link Lupa Password -->
                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900 font-medium">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-[#125bce] border-slate-300 focus:ring-0 cursor-pointer">
                            <span>Ingat sesi saya di perangkat ini</span>
                        </label>
                        <button type="button" @click="showForgotModal = true" class="text-[#125bce] hover:text-blue-700 font-bold hover:underline cursor-pointer">
                            Lupa Password?
                        </button>
                    </div>

                    <!-- 4. Tombol Utama Masuk ke Sistem -->
                    <button type="submit"
                        class="w-full py-3.5 px-6 bg-gradient-to-r from-[#0066ff] via-[#125bce] to-[#00b4d8] hover:from-[#0052cc] hover:to-[#0096c7] active:scale-[0.99] text-white font-extrabold text-sm sm:text-base rounded-2xl shadow-lg shadow-blue-600/30 hover:shadow-blue-600/40 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5 text-cyan-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>Masuk ke Sistem</span>
                    </button>
                </form>

                <!-- 5. Identitas Versi Aplikasi -->
                <div class="relative my-6 text-center">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                    <span class="relative px-3 bg-white text-[10.5px] font-bold text-slate-400 font-mono tracking-widest uppercase">
                        SIGMA ERP &amp; POS v2.0
                    </span>
                </div>

                <!-- 6. Akun Pengujian Internal (RINGKAS & TIDAK MENDOMINASI FORM - HANYA PADA ENV LOKAL) -->
                @if(app()->environment('local', 'testing'))
                    <div class="pt-1">
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-2 flex items-center justify-between">
                            <span class="flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                <span>Akun Sandbox (1-Klik Isi):</span>
                            </span>
                            <span class="text-blue-600 font-mono text-[9.5px]">Local Dev</span>
                        </div>
                        <div class="grid grid-cols-4 gap-1.5 font-mono text-[11px]">
                            <button type="button" @click="fillAccount('owner@sigma.test')" title="owner@sigma.test" class="px-2 py-1.5 rounded-lg bg-slate-50 hover:bg-amber-50 border border-slate-200 hover:border-amber-300 text-center font-bold text-amber-700 hover:text-amber-800 transition-colors cursor-pointer text-xs">
                                👑 Owner
                            </button>
                            <button type="button" @click="fillAccount('manager@sigma.test')" title="manager@sigma.test" class="px-2 py-1.5 rounded-lg bg-slate-50 hover:bg-blue-50 border border-slate-200 hover:border-blue-300 text-center font-bold text-blue-700 hover:text-blue-800 transition-colors cursor-pointer text-xs">
                                👔 Manager
                            </button>
                            <button type="button" @click="fillAccount('kasir1@sigma.test')" title="kasir1@sigma.test" class="px-2 py-1.5 rounded-lg bg-slate-50 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-center font-bold text-emerald-700 hover:text-emerald-800 transition-colors cursor-pointer text-xs">
                                🛒 Kasir
                            </button>
                            <button type="button" @click="fillAccount('sales1@sigma.test')" title="sales1@sigma.test" class="px-2 py-1.5 rounded-lg bg-slate-50 hover:bg-purple-50 border border-slate-200 hover:border-purple-300 text-center font-bold text-purple-700 hover:text-purple-800 transition-colors cursor-pointer text-xs">
                                💼 Sales
                            </button>
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </main>

    <!-- ============================================================== -->
    <!-- 3. BAGIAN FOOTER RESMI & STATUS KONEKSI                        -->
    <!-- ============================================================== -->
    <footer class="w-full bg-[#020718]/90 border-t border-blue-900/40 backdrop-blur-md px-6 sm:px-12 lg:px-16 py-3 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs text-slate-400 relative z-20">
        
        <!-- Hak Cipta & Info Keamanan (Identitas Resmi SIGMA PROJECTS) -->
        <div class="flex items-center gap-2 text-center sm:text-left">
            <span>&copy; {{ date('Y') }} SIGMA PROJECTS</span>
            <span class="text-slate-600 hidden sm:inline">&bull;</span>
            <span class="text-slate-500 hidden sm:inline">Didukung Keamanan Enkripsi Tingkat Tinggi</span>
        </div>

        <!-- Status Koneksi & Support -->
        <div class="flex items-center gap-3.5 font-medium">
            <!-- Server Status -->
            <div class="flex items-center gap-1.5 text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="font-semibold text-[11.5px]">Server Online</span>
            </div>

            <span class="text-slate-700">|</span>

            <!-- Jaringan Klien -->
            <div class="flex items-center gap-1.5 text-slate-400">
                <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                </svg>
                <span class="text-[11.5px]" x-text="isOnline ? 'Terhubung' : 'Offline'">Terhubung</span>
            </div>

            <span class="text-slate-700">|</span>

            <!-- Dukungan IT / Support -->
            <button type="button" @click="showSupportModal = true" class="flex items-center gap-1.5 text-slate-400 hover:text-white transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span class="text-[11.5px]">Support</span>
            </button>
        </div>
    </footer>

    <!-- ============================================================== -->
    <!-- 4. MODAL DIALOG PENDUKUNG (LUPA PASSWORD & SUPPORT)           -->
    <!-- ============================================================== -->
    
    <!-- Modal Lupa Password (Enterprise Security Compliance) -->
    <div x-show="showForgotModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-sm w-full shadow-2xl text-slate-800 space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>🔐</span>
                    <span>Pemulihan Kata Sandi</span>
                </h3>
                <button type="button" @click="showForgotModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed">
                Untuk standar keamanan enterprise SIGMA ERP, pemulihan atau reset kata sandi wajib melalui otorisasi <strong>Administrator Sistem</strong> atau <strong>Manajer Toko</strong> Anda.
            </p>
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs">
                Hubungi PIC IT internal perusahaan atau gunakan panel <strong>Kelola Pengguna</strong> oleh Manajer.
            </div>
            <button type="button" @click="showForgotModal = false" class="w-full py-2.5 bg-[#125bce] hover:bg-[#0e4db3] text-white rounded-xl font-bold text-xs shadow-md cursor-pointer transition-all">
                Saya Mengerti
            </button>
        </div>
    </div>

    <!-- Modal Support Bantuan Teknis -->
    <div x-show="showSupportModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-sm w-full shadow-2xl text-slate-800 space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>🎧</span>
                    <span>Bantuan &amp; Dukungan Teknis</span>
                </h3>
                <button type="button" @click="showSupportModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed">
                Layanan bantuan teknis SIGMA ERP &amp; POS beroperasi mendampingi kelancaran operasional ritel dan pergudangan Anda.
            </p>
            <div class="space-y-2 text-xs">
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Helpdesk Internal:</span>
                    <span class="font-bold text-slate-800">Ext. 101 / IT Support</span>
                </div>
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                    <span class="text-slate-500 font-medium">Status Layanan:</span>
                    <span class="font-bold text-emerald-600 flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Normal Aktif
                    </span>
                </div>
            </div>
            <button type="button" @click="showSupportModal = false" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs cursor-pointer transition-all">
                Tutup
            </button>
        </div>
    </div>

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
