@extends('layouts.app')

@section('title', 'Setting Perkiraan - SIGMA ERP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Setting Perkiraan</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>⚙️ Setting Perkiraan (Default Linked Accounts)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Atur pemetaan akun buku besar yang digunakan secara otomatis saat transaksi kasir, penjualan, pembelian, persediaan, dan tutup buku.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('accounting.coa.index') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>← Bagan Akun (COA)</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs">
        <div class="font-bold mb-1">⚠️ Terjadi Kesalahan Validasi:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form Setting Perkiraan -->
    <form action="{{ route('accounting.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        @foreach($groups as $groupKey => $group)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-4">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center space-x-2">
                    <span>{{ $group['title'] }}</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ $group['description'] }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($group['items'] as $item)
                @php
                    $currentSetting = $settings->get($item['key']);
                    $selectedAccountId = $currentSetting?->account_id;

                    // Fallback to default code if not yet mapped
                    if (!$selectedAccountId) {
                        $fallback = $accounts->firstWhere('code', $item['default_code']);
                        $selectedAccountId = $fallback?->id;
                    }
                @endphp
                <div class="p-4 rounded-xl bg-slate-50/60 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-900 dark:text-white">
                            {{ $item['label'] }}
                        </label>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-md bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-bold">
                            Standar: {{ $item['default_code'] }}
                        </span>
                    </div>

                    <select name="settings[{{ $item['key'] }}]" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-blue-500">
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $selectedAccountId == $acc->id ? 'selected' : '' }}>
                            [{{ $acc->code }}] {{ $acc->name }} ({{ $acc->type }})
                        </option>
                        @endforeach
                    </select>

                    <p class="text-[11px] text-slate-500 dark:text-slate-400 italic">
                        💡 {{ $item['notes'] }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        <!-- Tombol Simpan -->
        <div class="sticky bottom-4 z-20 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-lg flex items-center justify-between">
            <div class="text-xs text-slate-600 dark:text-slate-400">
                <span class="font-bold text-slate-900 dark:text-white">Perubahan setting perkiraan</span> akan langsung diterapkan pada seluruh transaksi baru yang dibuat setelahnya.
            </div>
            <button type="submit"
                    class="inline-flex items-center space-x-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/25 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Simpan Pemetaan Akun</span>
            </button>
        </div>
    </form>
</div>
@endsection
