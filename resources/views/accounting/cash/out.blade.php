@extends('layouts.app')

@section('title', 'Input Kas Keluar (Biaya Toko)')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.cash.index') }}" class="hover:underline">Kas &amp; Bank</a>
                <span>/</span>
                <span>Kas Keluar</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>🔴 Formulir Pengeluaran Kas (Biaya Toko)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pengeluaran kas kecil operasional toko (beli galon, kebersihan, konsumsi, bensin kurir, ATK, perbaikan).
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

    <!-- Preset Cepat Keperluan Toko -->
    <div class="bg-rose-50/50 dark:bg-rose-950/20 p-4 rounded-2xl border border-rose-100 dark:border-rose-900/40 shadow-xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">⚡ Contoh Cepat Pengeluaran Kas Kecil:</span>
        <div class="flex flex-wrap gap-2 mt-2">
            <button type="button" onclick="setQuickOut('Beli Air Galon & Kebersihan Toko', '50000')"
                    class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-rose-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                💧 Air Galon &amp; Kebersihan (Rp 50rb)
            </button>
            <button type="button" onclick="setQuickOut('Bensin Kurir Kirim Barang', '35000')"
                    class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-rose-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                🛵 Bensin Kurir Toko (Rp 35rb)
            </button>
            <button type="button" onclick="setQuickOut('Konsumsi Lembur Kasir & Toko', '75000')"
                    class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-rose-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                🍱 Konsumsi / Makan Lembur (Rp 75rb)
            </button>
            <button type="button" onclick="setQuickOut('Beli Lakban & Perlengkapan Packing', '40000')"
                    class="px-3 py-1 rounded-xl bg-white dark:bg-slate-800 hover:bg-rose-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                📦 ATK &amp; Lakban Packing (Rp 40rb)
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-sm">
        <form action="{{ route('accounting.cash.store-out') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                    <input type="date" name="transaction_date" value="{{ old('transaction_date', now()->toDateString()) }}" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500">
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

            <!-- Rekening Kas Sumber & Kategori Beban -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Diambil dari Kas / Bank <span class="text-rose-500">*</span></label>
                    <select name="source_account_id" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500 font-bold">
                        <option value="">-- Pilih Kas Sumber Pembayaran --</option>
                        @foreach($cashAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ old('source_account_id') == $acc->id ? 'selected' : '' }}>
                            [{{ $acc->code }}] {{ $acc->name }} (Saldo: Rp {{ number_format($acc->current_balance ?? 0, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kategori Beban Operasional <span class="text-rose-500">*</span></label>
                    <select name="category_account_id" id="categoryAccountSelect" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500">
                        <option value="">-- Pilih Kategori Biaya --</option>
                        @foreach($categoryAccounts as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_account_id') == $cat->id ? 'selected' : '' }}>
                            [{{ $cat->code }}] {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Nominal & Dibayarkan Kepada -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jumlah Uang (Rp) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">Rp</span>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" value="{{ old('amount') }}" required placeholder="0"
                               class="w-full pl-9 pr-3 py-2 text-sm font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-rose-600 dark:text-rose-400 focus:ring-2 focus:ring-rose-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dibayarkan Kepada (Penerima Uang)</label>
                    <input type="text" name="contact_person" id="contactPersonInput" value="{{ old('contact_person') }}" placeholder="Contoh: Depot Air Tirta / Petugas Sampah"
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500">
                </div>
            </div>

            <!-- Keterangan / Memo -->
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Keperluan Pengeluaran</label>
                <textarea name="notes" id="notesInput" rows="3" placeholder="Tuliskan alasan / rincian biaya pengeluaran kas..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                <a href="{{ route('accounting.cash.index') }}" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-500/20 transition-all cursor-pointer">
                    💾 Simpan Kas Keluar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function setQuickOut(note, amount) {
    document.getElementById('notesInput').value = note;
    document.getElementById('amountInput').value = amount;
    const select = document.getElementById('categoryAccountSelect');
    if (select.options.length > 1 && !select.value) {
        select.selectedIndex = 1; // default first category
    }
}
</script>
@endsection
