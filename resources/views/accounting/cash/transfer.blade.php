@extends('layouts.app')

@section('title', 'Transfer Kas & Setor Bank')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.cash.index') }}" class="hover:underline">Kas &amp; Bank</a>
                <span>/</span>
                <span>Transfer Kas</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>⇄ Formulir Transfer Kas &amp; Setor Bank</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pindahkan saldo antar rekening (Kasir setor ke Brankas, Setor Tunai ke Bank BCA, atau Tarik Tunai).
            </p>
        </div>

        <a href="{{ route('accounting.cash.index') }}"
           class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
            <span>← Kembali</span>
        </a>
    </div>

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

    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
        <form action="{{ route('accounting.cash.store-transfer') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Transfer <span class="text-rose-500">*</span></label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cabang / Toko</label>
                    <select name="store_id"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Kantor Pusat (Holding) --</option>
                        @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Rekening Sumber (Dari) & Rekening Tujuan (Ke) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-blue-50/40 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dari Rekening (Kas Keluar) <span class="text-rose-500">*</span></label>
                    <select name="source_account_id" id="sourceAccountSelect" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-bold">
                        <option value="">-- Pilih Rekening Sumber --</option>
                        @foreach($cashAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('source_account_id') == $acc->id ? 'selected' : '' }}>
                            [{{ $acc->code }}] {{ $acc->name }} (Rp {{ number_format($acc->current_balance ?? 0, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Ke Rekening (Kas Masuk) <span class="text-rose-500">*</span></label>
                    <select name="destination_account_id" id="destAccountSelect" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-bold">
                        <option value="">-- Pilih Rekening Tujuan --</option>
                        @foreach($cashAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('destination_account_id') == $acc->id ? 'selected' : '' }}>
                            [{{ $acc->code }}] {{ $acc->name }} (Rp {{ number_format($acc->current_balance ?? 0, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Nominal & Biaya Admin -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nominal Transfer (Rp) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">Rp</span>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="0"
                               class="w-full pl-9 pr-3 py-2 text-sm font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Biaya Admin Bank (Jika Ada)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">Rp</span>
                        <input type="number" step="0.01" min="0" name="admin_fee" value="{{ old('admin_fee', 0) }}" placeholder="0"
                               class="w-full pl-9 pr-3 py-2 text-sm font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <span class="text-[10px] text-slate-400">Contoh: BI-Fast Rp 2.500 atau Kliring Rp 6.500.</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Akun Beban Admin Bank (Opsional)</label>
                    <select name="admin_fee_account_id"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">-- Beban Operasional Default --</option>
                        @foreach($adminFeeAccounts as $fee)
                        <option value="{{ $fee->id }}" {{ old('admin_fee_account_id') == $fee->id ? 'selected' : '' }}>
                            [{{ $fee->code }}] {{ $fee->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Petugas / Penyetor</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', auth()->user()?->name) }}"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <!-- Keterangan / Memo -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan / Keterangan Transfer</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Setoran tunai kasir shift pagi ke Bank BCA..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('accounting.cash.index') }}" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                    💾 Simpan Transfer Kas
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
