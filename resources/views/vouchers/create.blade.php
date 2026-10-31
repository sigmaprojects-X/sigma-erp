@extends('layouts.app')

@section('title', 'Generate Voucher Baru')
@section('tab_title', 'Generate Voucher')

@section('content')
<div class="space-y-4 p-4 max-w-3xl mx-auto w-full">

    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('vouchers.index') }}" class="hover:underline">Voucher</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Generate</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                🎟️ Generate Voucher Belanja &amp; Ekor Struk
            </h1>
        </div>
        <a href="{{ route('vouchers.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('vouchers.store') }}" method="POST" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Judul / Nama Voucher *</label>
                <input type="text" name="title" value="{{ old('title', 'Voucher Belanja Hemat') }}" required
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tipe Voucher *</label>
                <select name="type" required class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    <option value="VALUE" {{ old('type') === 'VALUE' ? 'selected' : '' }}>💵 Nilai Nominal Rupiah (Value Voucher)</option>
                    <option value="DISCOUNT_PERCENT" {{ old('type') === 'DISCOUNT_PERCENT' ? 'selected' : '' }}>% Diskon Persentase</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nilai Nominal / Persen *</label>
                <input type="number" name="amount" value="{{ old('amount', 10000) }}" min="1" step="any" required
                       class="w-full text-xs font-bold text-emerald-600 dark:text-emerald-400 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Syarat Min. Belanja untuk Pakai Voucher (Rp)</label>
                <input type="number" name="min_spend" value="{{ old('min_spend', 50000) }}" min="0" step="any"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
        </div>

        <!-- FOOT VOUCHER TRIGGER (EKOR STRUK KASIR) -->
        <div class="p-3 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 rounded-xl space-y-2">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-blue-900 dark:text-blue-300">
                <input type="checkbox" name="is_printed_on_receipt" value="1" {{ old('is_printed_on_receipt') ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600">
                <span>🖨️ Cetak Otomatis di Ekor Struk Kasir (Foot Voucher Belanja)</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Min. Belanja Pelanggan untuk Mendapatkan Voucher (Rp)</label>
                    <input type="number" name="trigger_min_spend" value="{{ old('trigger_min_spend', 100000) }}" min="0" step="any"
                           class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                </div>
                <div class="flex items-center pt-4">
                    <label class="flex items-center gap-2 cursor-pointer text-[11px] font-bold text-purple-700 dark:text-purple-300">
                        <input type="checkbox" name="is_template" value="1" {{ old('is_template') ? 'checked' : '' }} class="rounded border-purple-400 text-purple-600">
                        <span>Jadikan Aturan Template Cetak Struk</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Masa Berlaku (Mulai)</label>
                <input type="date" name="valid_from" value="{{ date('Y-m-d') }}"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Masa Berlaku (Kedaluwarsa)</label>
                <input type="date" name="valid_until" value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jumlah Voucher yang Ingin Digenerate Sekaligus</label>
            <input type="number" name="quantity_to_generate" value="1" min="1" max="50" step="1"
                   class="w-32 text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            <span class="text-[11px] text-slate-400 ml-2">Setiap voucher akan memiliki kode alfanumerik unik anti-fraud.</span>
        </div>

        <div class="pt-2 flex items-center justify-end border-t border-slate-100 dark:border-slate-800">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                Generate Voucher Sekarang
            </button>
        </div>
    </form>

</div>
@endsection
