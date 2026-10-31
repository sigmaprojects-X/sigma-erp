@extends('layouts.app')

@section('title', 'Pengaturan Program Loyalitas & Poin Pelanggan')
@section('tab_title', 'Aturan Poin')

@section('content')
<div class="space-y-4 p-4 max-w-3xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.loyalty.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Loyalty Points</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Konfigurasi Aturan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⚙️ Konfigurasi Aturan Poin Reward Pelanggan</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customers.loyalty.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            ✕ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('customers.loyalty.settings.update') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 text-xs">
        @csrf

        <div class="space-y-1">
            <label class="block font-bold text-slate-700 dark:text-slate-300">Nama Program Loyalitas *</label>
            <input type="text"
                   name="name"
                   value="{{ old('name', $program->name) }}"
                   required
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Kelipatan Belanja per 1 Poin (Rp) *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold">Rp</span>
                    <input type="number"
                           step="100"
                           min="1"
                           name="spend_amount_per_point"
                           value="{{ old('spend_amount_per_point', (float)$program->spend_amount_per_point) }}"
                           required
                           class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-black text-purple-600 dark:text-purple-400 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Contoh: 10000 artinya tiap belanja kelipatan Rp 10.000 pelanggan mendapatkan 1 poin reward.</p>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Nilai Tukar Rupiah per 1 Poin (Rp) *
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 font-bold">Rp</span>
                    <input type="number"
                           step="1"
                           min="0.01"
                           name="point_redemption_value"
                           value="{{ old('point_redemption_value', (float)$program->point_redemption_value) }}"
                           required
                           class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-black text-emerald-600 dark:text-emerald-400 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Contoh: 100 artinya setiap 1 poin dapat ditukarkan menjadi potongan diskon belanja senilai Rp 100.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Minimal Poin untuk Penukaran (Threshold) *
                </label>
                <input type="number"
                       step="1"
                       min="1"
                       name="min_points_to_redeem"
                       value="{{ old('min_points_to_redeem', $program->min_points_to_redeem) }}"
                       required
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-bold focus:outline-none focus:ring-2 focus:ring-purple-500">
                <p class="text-[11px] text-slate-400 mt-1">Batas minimal poin sebelum member boleh menukarkannya.</p>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Masa Berlaku Poin (Hari Kedaluwarsa)
                </label>
                <input type="number"
                       step="1"
                       min="1"
                       name="points_expiry_days"
                       value="{{ old('points_expiry_days', $program->points_expiry_days ?? 365) }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-bold focus:outline-none focus:ring-2 focus:ring-purple-500">
                <p class="text-[11px] text-slate-400 mt-1">Contoh: 365 hari (1 tahun). Poin yang tidak digunakan akan hangus.</p>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 space-y-2.5">
            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox"
                       name="only_registered_customers"
                       value="1"
                       {{ old('only_registered_customers', $program->only_registered_customers ?? true) ? 'checked' : '' }}
                       class="h-4 w-4 mt-0.5 text-purple-600 focus:ring-purple-500 border-slate-300 rounded">
                <div>
                    <span class="font-bold text-slate-800 dark:text-slate-200">Hanya Pelanggan Terdaftar (Member) yang Berhak Mendapatkan Poin</span>
                    <p class="text-[11px] text-slate-400">Jika dicentang, transaksi atas nama Pelanggan Umum / Guest Walk-in tidak akan memperoleh poin reward.</p>
                </div>
            </label>

            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox"
                       name="allow_merchandise_redemption"
                       value="1"
                       {{ old('allow_merchandise_redemption', $program->allow_merchandise_redemption) ? 'checked' : '' }}
                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-slate-300 rounded">
                <span class="font-bold text-slate-800 dark:text-slate-200">Izinkan Penukaran Poin dengan Hadiah Fisik (Merchandise Rewards)</span>
            </label>

            <label class="flex items-center gap-2.5 cursor-pointer">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       {{ old('is_active', $program->is_active) ? 'checked' : '' }}
                       class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-slate-300 rounded">
                <span class="font-bold text-slate-800 dark:text-slate-200">Aktifkan Program Poin Belanja di Kasir POS</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('customers.loyalty.index') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-purple-600 hover:bg-purple-500 text-white font-black text-xs rounded-xl shadow-md shadow-purple-500/25 cursor-pointer">
                💾 Simpan Pengaturan Program
            </button>
        </div>
    </form>

</div>
@endsection
