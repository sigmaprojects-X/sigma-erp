<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIGMA ERP &amp; POS Enterprise</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- PWA Progressive Web App Meta & Manifest -->
    <meta name="theme-color" content="#020b24">
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
        
        /* Smooth backdrop filters */
        .glass-card {
            background: rgba(4, 24, 68, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 229, 255, 0.25);
        }
        .glass-card:hover {
            border-color: rgba(0, 229, 255, 0.55);
            background: rgba(7, 34, 94, 0.65);
        }
        .glass-pill {
            background: linear-gradient(90deg, rgba(3, 29, 77, 0.85) 0%, rgba(7, 43, 107, 0.6) 60%, rgba(3, 29, 77, 0.4) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 229, 255, 0.3);
            border-bottom: 2px solid rgba(245, 158, 11, 0.65);
        }
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
    class="bg-[#020b24] text-slate-100 min-h-screen flex flex-col justify-between relative overflow-x-hidden selection:bg-[#00e5ff] selection:text-slate-950">

    <!-- ============================================================== -->
    <!-- 1. LATAR BELAKANG: GRADASI NAVY, LASER GOLD & CYAN, TECH DOTS   -->
    <!-- ============================================================== -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <!-- Base Radial Gradient Dark Navy Background -->
        <div class="absolute inset-0 bg-[#020b24] bg-[radial-gradient(ellipse_at_18%_28%,#0d439d_0%,#041e5e_38%,#020b24_72%,#010617_100%)]"></div>

        <!-- Glowing Royal Blue Ambient Orbs -->
        <div class="absolute -top-36 left-6 w-[750px] h-[750px] bg-blue-600/22 rounded-full blur-[140px]"></div>
        <div class="absolute top-1/4 -right-24 w-[650px] h-[650px] bg-cyan-500/18 rounded-full blur-[150px]"></div>
        <div class="absolute -bottom-48 left-1/3 w-[850px] h-[850px] bg-blue-900/35 rounded-full blur-[160px]"></div>

        <!-- SVG Diagonal Laser Lines, Facets & Tech Matrix -->
        <svg class="absolute inset-0 w-full h-full" preserveAspectRatio="none" viewBox="0 0 1920 1080" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Gold Glowing Beam Gradient -->
                <linearGradient id="goldBeam" x1="0" y1="0" x2="1920" y2="1080" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.95" />
                    <stop offset="30%" stop-color="#f59e0b" stop-opacity="1" />
                    <stop offset="70%" stop-color="#d97706" stop-opacity="0.75" />
                    <stop offset="100%" stop-color="#b45309" stop-opacity="0.1" />
                </linearGradient>

                <!-- Cyan Glowing Beam Gradient -->
                <linearGradient id="cyanBeam" x1="0" y1="0" x2="1920" y2="1080" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#00e5ff" stop-opacity="0.95" />
                    <stop offset="40%" stop-color="#00b4d8" stop-opacity="1" />
                    <stop offset="80%" stop-color="#0077b6" stop-opacity="0.4" />
                    <stop offset="100%" stop-color="#03045e" stop-opacity="0" />
                </linearGradient>

                <!-- Facet Gradient -->
                <linearGradient id="facetPoly" x1="0" y1="0" x2="1200" y2="1080" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#0066ff" stop-opacity="0.16" />
                    <stop offset="50%" stop-color="#00e5ff" stop-opacity="0.06" />
                    <stop offset="100%" stop-color="#001845" stop-opacity="0" />
                </linearGradient>

                <filter id="goldGlow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="7" result="blur" />
                    <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>

                <filter id="cyanGlow" x="-30%" y="-30%" width="160%" height="160%">
                    <feGaussianBlur stdDeviation="8" result="blur" />
                    <feMerge>
                        <feMergeNode in="blur" />
                        <feMergeNode in="SourceGraphic" />
                    </feMerge>
                </filter>

                <!-- Dot Grid Pattern -->
                <pattern id="dotGrid" width="18" height="18" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.4" fill="#00e5ff" fill-opacity="0.35" />
                </pattern>
            </defs>

            <!-- Translucent Geometric Facets -->
            <polygon points="0,0 880,0 360,1080 0,1080" fill="url(#facetPoly)" />
            <polygon points="420,0 1380,0 880,1080 0,1080" fill="url(#facetPoly)" opacity="0.35" />

            <!-- Top Right Tech Dot Matrix -->
            <rect x="1420" y="30" width="460" height="320" fill="url(#dotGrid)" opacity="0.75" />
            <!-- Bottom Left Tech Dot Matrix -->
            <rect x="30" y="720" width="320" height="320" fill="url(#dotGrid)" opacity="0.4" />

            <!-- Diagonal Light Rays cutting across from top-left to bottom-right -->
            <!-- Main Gold Ray -->
            <line x1="-80" y1="260" x2="1250" y2="-90" stroke="url(#goldBeam)" stroke-width="3" filter="url(#goldGlow)" />
            <line x1="80" y1="-60" x2="1880" y2="1140" stroke="url(#goldBeam)" stroke-width="2.5" filter="url(#goldGlow)" />
            
            <!-- Main Cyan Ray -->
            <line x1="-60" y1="110" x2="1650" y2="1140" stroke="url(#cyanBeam)" stroke-width="3.2" filter="url(#cyanGlow)" />
            <line x1="280" y1="-90" x2="1960" y2="1040" stroke="url(#cyanBeam)" stroke-width="2.2" filter="url(#cyanGlow)" />
            
            <!-- Subtle Secondary Cyan Ray -->
            <line x1="-90" y1="460" x2="1380" y2="1160" stroke="#00b4d8" stroke-width="1.8" stroke-opacity="0.35" />

            <!-- Glowing Star / Spark Flares on the Lines -->
            <circle cx="260" cy="165" r="4.5" fill="#ffffff" filter="url(#cyanGlow)" />
            <circle cx="260" cy="165" r="1.5" fill="#ffffff" />
            
            <circle cx="110" cy="210" r="5" fill="#fef08a" filter="url(#goldGlow)" />
            <circle cx="110" cy="210" r="2" fill="#ffffff" />

            <circle cx="860" cy="510" r="4.5" fill="#00e5ff" filter="url(#cyanGlow)" />
            <circle cx="860" cy="510" r="1.5" fill="#ffffff" />
        </svg>
    </div>

    <!-- ============================================================== -->
    <!-- 2. KONTEN UTAMA: 2 KOLOM (KIRI BRANDING | KANAN FORM LOGIN)    -->
    <!-- ============================================================== -->
    <main class="flex-1 w-full max-w-7xl mx-auto flex flex-col lg:flex-row items-center justify-between px-6 sm:px-10 lg:px-14 py-8 lg:py-10 relative z-10 gap-10 lg:gap-14">

        <!-- ========================================== -->
        <!-- KOLOM KIRI: IDENTITAS BRAND & FITUR        -->
        <!-- ========================================== -->
        <div class="w-full lg:w-[54%] flex flex-col justify-center space-y-6 sm:space-y-7 text-left">
            
            <!-- A. Logo Resmi SIGMA PROJECTS Asli -->
            <div class="flex items-center">
                <img src="{{ asset('images/sigma-projects-logo-alpha.png') }}" 
                     alt="SIGMA PROJECTS" 
                     class="h-16 sm:h-20 lg:h-22 w-auto object-contain drop-shadow-[0_10px_25px_rgba(0,180,255,0.35)]"
                     onerror="this.onerror=null; this.src='{{ asset('logo-icon.png') }}'">
            </div>

            <!-- B & C. Judul Utama & Slogan -->
            <div class="space-y-2.5">
                <h1 class="text-3xl sm:text-5xl lg:text-[54px] font-black tracking-tight text-white leading-[1.1]">
                    SIGMA <span class="text-[#00e5ff] drop-shadow-[0_0_20px_rgba(0,229,255,0.45)]">ERP &amp; POS</span>
                </h1>
                
                <p class="text-lg sm:text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>One Integrated System. Smarter Business.</span>
                </p>

                <!-- D. Garis Aksen Horizontal Cyan dengan Highlight Emas -->
                <div class="pt-1">
                    <div class="h-1.5 w-44 rounded-full bg-gradient-to-r from-[#00e5ff] via-[#38bdf8] to-[#f59e0b] shadow-[0_0_12px_rgba(0,229,255,0.7)]"></div>
                </div>

                <!-- E. Deskripsi -->
                <p class="text-sm sm:text-base text-slate-200/90 max-w-xl font-medium leading-relaxed pt-2">
                    Sistem terintegrasi untuk manajemen toko, kasir, persediaan dan logistik.
                </p>
            </div>

            <!-- F. Empat Kartu Fitur (Navy Transparan, Border Cyan Tipis, Ikon 3D) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-3.5 max-w-2xl pt-1">
                
                <!-- Kartu 1: Manajemen Toko -->
                <div class="glass-card rounded-2xl p-4 flex flex-col items-center justify-center text-center gap-2.5 shadow-xl shadow-black/25 hover:scale-[1.04] transition-all duration-300 group cursor-default">
                    <img src="{{ asset('images/icon-store.png') }}" alt="Manajemen Toko" class="h-11 w-auto object-contain drop-shadow-[0_6px_12px_rgba(0,229,255,0.4)] group-hover:scale-110 transition-transform">
                    <span class="text-xs sm:text-[13px] font-extrabold text-white leading-tight">Manajemen<br>Toko</span>
                </div>

                <!-- Kartu 2: Kasir Modern -->
                <div class="glass-card rounded-2xl p-4 flex flex-col items-center justify-center text-center gap-2.5 shadow-xl shadow-black/25 hover:scale-[1.04] transition-all duration-300 group cursor-default">
                    <img src="{{ asset('images/icon-pos.png') }}" alt="Kasir Modern" class="h-11 w-auto object-contain drop-shadow-[0_6px_12px_rgba(0,229,255,0.4)] group-hover:scale-110 transition-transform">
                    <span class="text-xs sm:text-[13px] font-extrabold text-white leading-tight">Kasir<br>Modern</span>
                </div>

                <!-- Kartu 3: Inventori Real-Time -->
                <div class="glass-card rounded-2xl p-4 flex flex-col items-center justify-center text-center gap-2.5 shadow-xl shadow-black/25 hover:scale-[1.04] transition-all duration-300 group cursor-default">
                    <img src="{{ asset('images/icon-box.png') }}" alt="Inventori Real-Time" class="h-11 w-auto object-contain drop-shadow-[0_6px_12px_rgba(0,229,255,0.4)] group-hover:scale-110 transition-transform">
                    <span class="text-xs sm:text-[13px] font-extrabold text-white leading-tight">Inventori<br>Real-Time</span>
                </div>

                <!-- Kartu 4: Logistik Terintegrasi -->
                <div class="glass-card rounded-2xl p-4 flex flex-col items-center justify-center text-center gap-2.5 shadow-xl shadow-black/25 hover:scale-[1.04] transition-all duration-300 group cursor-default">
                    <img src="{{ asset('images/icon-truck.png') }}" alt="Logistik Terintegrasi" class="h-11 w-auto object-contain drop-shadow-[0_6px_12px_rgba(0,229,255,0.4)] group-hover:scale-110 transition-transform">
                    <span class="text-xs sm:text-[13px] font-extrabold text-white leading-tight">Logistik<br>Terintegrasi</span>
                </div>
            </div>

            <!-- G. Banner Identitas Keamanan (Trust Pill Badge) -->
            <div class="pt-1">
                <div class="glass-pill inline-flex items-center gap-3.5 px-5 py-3 rounded-2xl max-w-md shadow-2xl">
                    <img src="{{ asset('images/icon-shield.png') }}" alt="Keamanan" class="w-10 h-10 object-contain drop-shadow-[0_4px_10px_rgba(0,229,255,0.5)] shrink-0">
                    <div>
                        <div class="font-black text-sm sm:text-base text-white flex items-center gap-1.5 tracking-wide">
                            <span>Aman</span>
                            <span class="text-[#00e5ff] font-bold">•</span>
                            <span>Andal</span>
                            <span class="text-[#00e5ff] font-bold">•</span>
                            <span>Profesional</span>
                        </div>
                        <p class="text-xs text-blue-200/90 font-normal mt-0.5">Solusi bisnis terbaik untuk masa depan Anda</p>
                    </div>
                </div>
            </div>

        </div>

        <!-- ========================================== -->
        <!-- KOLOM KANAN: KARTU LOGIN PUTIH PREMIUM     -->
        <!-- ========================================== -->
        <div class="w-full lg:w-[46%] flex items-center justify-center">
            <div class="bg-white rounded-[32px] p-7 sm:p-9 lg:p-10 shadow-[0_25px_65px_rgba(0,18,60,0.55)] border border-slate-100 ring-8 ring-blue-500/10 w-full max-w-[460px] text-slate-900 relative">
                
                <!-- A. Ikon Pengguna Dalam Lingkaran Biru -->
                <div class="flex justify-center mb-3">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-b from-[#e0f2fe] to-[#bae6fd] p-1.5 shadow-md shadow-blue-500/20 flex items-center justify-center">
                        <div class="w-full h-full rounded-full bg-gradient-to-tr from-[#0066ff] to-[#00d2ff] flex items-center justify-center text-white shadow-inner">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- B & C. Judul & Subjudul Form Login -->
                <div class="text-center mb-6">
                    <h2 class="text-2xl sm:text-[26px] font-black text-slate-900 tracking-tight">Selamat Datang Kembali</h2>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">Masuk untuk melanjutkan aktivitas bisnis Anda.</p>
                </div>

                <!-- Notifikasi Status / Pesan Validasi -->
                @if(session('status'))
                    <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="font-semibold">{{ session('status') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="space-y-0.5 font-semibold">
                            @foreach($errors->all() as $err)
                                <div>{{ $err }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Form Login Resmi -->
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- D. Input Alamat Email -->
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                                placeholder="Alamat Email"
                                class="w-full pl-11 pr-4 py-3.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 focus:border-[#0066ff] focus:ring-4 focus:ring-blue-500/15 rounded-xl text-sm font-semibold text-slate-900 transition-all placeholder:text-slate-400 placeholder:font-normal">
                        </div>
                    </div>

                    <!-- E. Input Kata Sandi dengan Show/Hide Password -->
                    <div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                placeholder="Password"
                                class="w-full pl-11 pr-11 py-3.5 bg-slate-50/70 hover:bg-white focus:bg-white border border-slate-200 focus:border-[#0066ff] focus:ring-4 focus:ring-blue-500/15 rounded-xl text-sm font-semibold text-slate-900 transition-all placeholder:text-slate-400 placeholder:font-normal">
                            
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

                    <!-- F. Checkbox Ingat Sesi & Link Lupa Password -->
                    <div class="flex items-center justify-between text-xs pt-0.5">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-700 hover:text-slate-900 font-semibold select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-[#0066ff] border-slate-300 focus:ring-blue-500 cursor-pointer" checked>
                            <span>Ingat sesi saya di perangkat ini</span>
                        </label>
                        <button type="button" @click="showForgotModal = true" class="text-[#0066ff] hover:text-blue-800 font-bold hover:underline cursor-pointer">
                            Lupa Password?
                        </button>
                    </div>

                    <!-- G. Tombol Utama Masuk ke Sistem (Biru Cerah dengan Gradasi Cyan) -->
                    <button type="submit"
                        class="w-full py-3.5 px-6 bg-gradient-to-r from-[#0066ff] via-[#0080ff] to-[#00d2ff] hover:from-[#0052cc] hover:to-[#00b4d8] active:scale-[0.99] text-white font-extrabold text-sm sm:text-base rounded-2xl shadow-xl shadow-blue-600/35 hover:shadow-blue-600/50 transition-all duration-200 flex items-center justify-center gap-2.5 cursor-pointer">
                        <svg class="w-5 h-5 text-cyan-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span>Masuk ke Sistem</span>
                    </button>
                </form>

                <!-- H. Identitas Versi Aplikasi -->
                <div class="relative my-6 text-center">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                    <span class="relative px-3.5 bg-white text-[10.5px] font-bold text-slate-400 font-mono tracking-widest uppercase">
                        SIGMA ERP &amp; POS v2.0
                    </span>
                </div>

                <!-- Akun Sandbox Cepat (HANYA PADA ENV LOKAL) -->
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
    <!-- 3. FOOTER RESMI & STATUS KONEKSI                               -->
    <!-- ============================================================== -->
    <footer class="w-full bg-[#01081f]/90 border-t border-blue-900/40 backdrop-blur-md px-6 sm:px-12 lg:px-16 py-3 flex flex-col sm:flex-row items-center justify-between gap-2.5 text-xs text-slate-400 relative z-20">
        
        <!-- Hak Cipta & Info Keamanan (Identitas Resmi SIGMA PROJECTION) -->
        <div class="flex items-center gap-2 text-center sm:text-left">
            <span>&copy; {{ date('Y') }} SIGMA PROJECTION</span>
            <span class="text-slate-600 hidden sm:inline">&bull;</span>
            <span class="text-slate-400 hidden sm:inline">Didukung Keamanan Enkripsi Tingkat Tinggi</span>
        </div>

        <!-- Status Koneksi & Support -->
        <div class="flex items-center gap-3.5 font-medium">
            <!-- Server Status (Aktual) -->
            <div class="flex items-center gap-1.5 text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                <span class="font-bold text-[11.5px]">Server Online</span>
            </div>

            <span class="text-slate-700">|</span>

            <!-- Jaringan Klien (Aktual via isOnline) -->
            <div class="flex items-center gap-1.5 text-slate-300">
                <svg class="w-3.5 h-3.5 text-[#00e5ff]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                </svg>
                <span class="text-[11.5px] font-semibold" x-text="isOnline ? 'Terhubung' : 'Offline'">Terhubung</span>
            </div>

            <span class="text-slate-700">|</span>

            <!-- Dukungan IT / Support -->
            <button type="button" @click="showSupportModal = true" class="flex items-center gap-1.5 text-slate-300 hover:text-white transition-colors cursor-pointer font-semibold">
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
    
    <!-- Modal Lupa Password -->
    <div x-show="showForgotModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-sm w-full shadow-2xl text-slate-800 space-y-4 animate-in fade-in zoom-in duration-150">
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
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs font-medium">
                Hubungi PIC IT internal perusahaan atau gunakan panel <strong>Kelola Pengguna</strong> oleh Manajer.
            </div>
            <button type="button" @click="showForgotModal = false" class="w-full py-2.5 bg-[#0066ff] hover:bg-[#0055ee] text-white rounded-xl font-bold text-xs shadow-md cursor-pointer transition-all">
                Saya Mengerti
            </button>
        </div>
    </div>

    <!-- Modal Support Bantuan Teknis -->
    <div x-show="showSupportModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 max-w-sm w-full shadow-2xl text-slate-800 space-y-4 animate-in fade-in zoom-in duration-150">
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
