<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Masuk — SIGMA ERP & POS Enterprise</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-gradient-to-br from-[#071536] via-[#0B1F4D] to-[#0A1633] text-slate-100 min-h-screen flex items-center justify-center p-4 relative overflow-hidden selection:bg-[#125BCE] selection:text-white">
    <!-- Ambient Background Glow -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-[#125BCE]/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-[#18BDEB]/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#0B1F4D]/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 bg-[radial-gradient(#1e293b_1px,transparent_1px)] [background-size:24px_24px] opacity-30 pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Header -->
        <div class="text-center mb-7">
            <div class="inline-flex items-center justify-center p-3.5 bg-[#0B1F4D]/90 border border-[#125BCE]/30 rounded-2xl shadow-2xl shadow-black/60 mb-3 backdrop-blur-md">
                <img src="{{ asset('images/sigma-erp-logo.png') }}" alt="SIGMA ERP" class="h-20 w-auto object-contain drop-shadow-md">
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white flex items-center justify-center gap-2">
                <span>SIGMA</span>
                <span class="text-[#FFB800] font-black text-sm px-2.5 py-0.5 rounded-md bg-[#071536]/80 border border-[#FFB800]/40 shadow-xs">ERP &amp; POS</span>
            </h1>
            <p class="text-xs text-slate-300 mt-1.5 font-medium">Sistem Terintegrasi Manajemen Toko, Kasir &amp; Logistik</p>
        </div>

        <!-- Login Card -->
        <div class="bg-[#0B1F4D]/80 border border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-2xl shadow-black/70 backdrop-blur-xl">
            @if(session('status'))
                <div class="mb-5 p-3.5 bg-emerald-950/60 border border-emerald-500/40 rounded-xl text-emerald-300 text-xs flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3.5 bg-rose-950/60 border border-rose-500/40 rounded-xl text-rose-300 text-xs flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="space-y-0.5">
                        @foreach($errors->all() as $err)
                            <div>{{ $err }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Alamat Email Pengguna
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="nama@sigmamart.co.id"
                            class="w-full pl-10 pr-4 py-2.5 bg-[#071536]/90 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#18BDEB] focus:border-[#18BDEB] transition-all font-medium">
                    </div>
                </div>

                <!-- Password Input -->
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required
                            placeholder="••••••••"
                            class="w-full pl-10 pr-4 py-2.5 bg-[#071536]/90 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#18BDEB] focus:border-[#18BDEB] transition-all font-medium">
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-400 hover:text-slate-200 transition">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-[#071536] border-slate-700 text-[#125BCE] focus:ring-0 focus:ring-offset-0 cursor-pointer">
                        <span>Ingat sesi saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                    class="w-full py-3 px-4 bg-[#125BCE] hover:bg-[#0E4DB3] active:bg-[#0B3D8F] text-white font-bold rounded-xl text-sm shadow-lg shadow-blue-600/30 hover:shadow-blue-600/40 transition-all duration-200 flex items-center justify-center gap-2 group cursor-pointer">
                    <span>Masuk ke Sistem</span>
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-0.5 text-[#18BDEB]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </form>

            @if(app()->environment('local', 'testing'))
                <!-- Quick Account Reference for Internal Testing (Local Only) -->
                <div class="mt-6 pt-5 border-t border-slate-800 text-[11px] text-slate-500">
                    <div class="flex items-center justify-between text-slate-400 font-semibold mb-2">
                        <span>Role Akun Uji Internal (Environment Lokal):</span>
                        <span class="text-slate-500 text-[10px] font-mono">Dev Sandbox</span>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 font-mono text-[10px]">
                        <div class="bg-slate-950/60 px-2 py-1 rounded border border-slate-800/80 text-slate-300">
                            <span class="text-amber-400 font-bold">Owner:</span> owner@sigma.test
                        </div>
                        <div class="bg-slate-950/60 px-2 py-1 rounded border border-slate-800/80 text-slate-300">
                            <span class="text-blue-400 font-bold">Manager:</span> manager@sigma.test
                        </div>
                        <div class="bg-slate-950/60 px-2 py-1 rounded border border-slate-800/80 text-slate-300">
                            <span class="text-emerald-400 font-bold">Kasir:</span> kasir1@sigma.test
                        </div>
                        <div class="bg-slate-950/60 px-2 py-1 rounded border border-slate-800/80 text-slate-300">
                            <span class="text-purple-400 font-bold">Sales:</span> sales1@sigma.test
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <p class="text-center text-[11px] text-slate-600 mt-6">
            &copy; {{ date('Y') }} PT SIGMA RETAIL INDONESIA &bull; Dilindungi Keamanan Enkripsi Ketat
        </p>
    </div>
</body>
</html>
