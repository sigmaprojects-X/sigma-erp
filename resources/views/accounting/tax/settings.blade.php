@extends('layouts.app')

@section('title', 'Pengaturan Pajak (PKP & PPN) - SIGMA ERP')

@section('content')
<div class="space-y-6 max-w-5xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Perpajakan</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>⚙️ Pengaturan Pajak (PKP &amp; PPN)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Konfigurasi fleksibel mode PKP (Pengusaha Kena Pajak) vs Non-PKP, tarif PPN, metode inklusif/eksklusif, dan NPWP perusahaan.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('accounting.tax.report') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-all">
                <span>📑 Rekap SPT Masa PPN</span>
                <span>→</span>
            </a>
        </div>
    </div>

    @if(session('status'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('status') }}</span>
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

    <!-- Card Panduan Integrasi Pajak -->
    <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-900 to-indigo-900 text-white shadow-sm space-y-3">
        <div class="flex items-center space-x-2 text-blue-200 text-xs font-bold uppercase tracking-wider">
            <span>🛡️ Standar Kepatuhan Pajak (AGENTS.md Section 5)</span>
        </div>
        <p class="text-sm text-blue-100 leading-relaxed">
            SIGMA ERP mendukung pergantian fleksibel antara status <strong>Non-PKP (Bebas PPN)</strong> dan <strong>PKP (Pengusaha Kena Pajak)</strong> dengan penanggalan efektif. Ketika PKP aktif, sistem mencatat jurnal otomatis:
            <span class="font-mono bg-blue-950/60 px-1.5 py-0.5 rounded text-amber-300">2003-001 PPN Keluaran</span> pada penjualan kasir dan
            <span class="font-mono bg-blue-950/60 px-1.5 py-0.5 rounded text-emerald-300">1006-001 PPN Masukan</span> pada pembelian barang dagang.
        </p>
    </div>

    <!-- Form Pengaturan Pajak -->
    <form action="{{ route('accounting.tax.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-xs space-y-6">
            <div class="border-b border-slate-100 dark:border-slate-800 pb-4">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Identitas Pajak &amp; Status PKP</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pengaturan identitas perpajakan untuk faktur komersial dan e-Faktur.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nama Perusahaan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Nama Wajib Pajak / Perusahaan</label>
                    <input type="text" value="{{ $company->name }}" disabled
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs font-bold cursor-not-allowed">
                </div>

                <!-- NPWP Perusahaan -->
                <div>
                    <label for="tax_id" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Nomor Pokok Wajib Pajak (NPWP / NIK 16 Digit)
                    </label>
                    <input type="text" name="tax_id" id="tax_id"
                           value="{{ old('tax_id', $company->tax_id) }}"
                           placeholder="Contoh: 01.234.567.8-901.000 / 16 digit"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Digunakan sebagai header identitas pada laporan SPT Masa PPN dan cetak invoice.</p>
                </div>
            </div>

            <!-- Toggle Switch PKP -->
            <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/80 dark:border-slate-700/60 flex items-start space-x-3.5">
                <input type="hidden" name="is_tax_enabled" value="0">
                <input type="checkbox" name="is_tax_enabled" id="is_tax_enabled" value="1"
                       {{ old('is_tax_enabled', $taxConfig->is_tax_enabled ?? false) ? 'checked' : '' }}
                       class="mt-1 w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <div>
                    <label for="is_tax_enabled" class="text-sm font-bold text-slate-900 dark:text-white cursor-pointer select-none">
                        Aktifkan Mode Pengusaha Kena Pajak (PKP)
                    </label>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Bila dicentang, sistem kasir POS dan modul pembelian akan menghitung PPN secara otomatis dan membukukan akun PPN Masukan/Keluaran ke Buku Besar. Jika tidak dicentang, sistem berjalan dalam mode <strong>Non-PKP (Bebas PPN)</strong>.
                    </p>
                </div>
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                <h2 class="text-base font-bold text-slate-900 dark:text-white mb-4">Parameter Tarif &amp; Perhitungan PPN</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Nama Pajak -->
                    <div>
                        <label for="tax_name" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">Nama Pajak</label>
                        <input type="text" name="tax_name" id="tax_name"
                               value="{{ old('tax_name', $taxConfig->tax_name ?? 'PPN') }}"
                               required
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Tarif PPN (%) -->
                    <div>
                        <label for="tax_rate_percent" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Tarif Pajak (%)
                        </label>
                        <div class="relative">
                            <input type="number" step="0.01" min="0" max="100" name="tax_rate_percent" id="tax_rate_percent"
                                   value="{{ old('tax_rate_percent', $taxConfig->tax_rate_percent ?? 11.00) }}"
                                   required
                                   class="w-full pr-8 pl-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500">
                            <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400">%</span>
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            <button type="button" onclick="document.getElementById('tax_rate_percent').value = '11.00'"
                                    class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                11% (Tarif Saat Ini)
                            </button>
                            <button type="button" onclick="document.getElementById('tax_rate_percent').value = '12.00'"
                                    class="px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                12% (UU HPP Mendatang)
                            </button>
                        </div>
                    </div>

                    <!-- Metode Perhitungan Harga -->
                    <div>
                        <label for="is_tax_inclusive" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Metode Harga Jual di Kasir
                        </label>
                        <select name="is_tax_inclusive" id="is_tax_inclusive"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500">
                            <option value="0" {{ old('is_tax_inclusive', $taxConfig->is_tax_inclusive ?? false) ? '' : 'selected' }}>
                                Eksklusif (PPN Ditambahkan di Luar Subtotal)
                            </option>
                            <option value="1" {{ old('is_tax_inclusive', $taxConfig->is_tax_inclusive ?? false) ? 'selected' : '' }}>
                                Inklusif (Harga Jual Sudah Termasuk PPN)
                            </option>
                        </select>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Mode default sistem saat transaksi kasir baru dibuat.</p>
                    </div>
                </div>

                <!-- Masa Berlaku Efektif -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label for="effective_from" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Tanggal Berlaku Efektif (Mulai)
                        </label>
                        <input type="date" name="effective_from" id="effective_from"
                               value="{{ old('effective_from', optional($taxConfig->effective_from)->format('Y-m-d') ?: now()->startOfYear()->format('Y-m-d')) }}"
                               required
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="effective_to" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                            Tanggal Berakhir (Opsional)
                        </label>
                        <input type="date" name="effective_to" id="effective_to"
                               value="{{ old('effective_to', optional($taxConfig->effective_to)->format('Y-m-d')) }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-xs font-bold focus:ring-2 focus:ring-blue-500">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Kosongkan jika berlaku tanpa batas waktu hingga ada kebijakan pajak baru.</p>
                    </div>
                </div>
            </div>

            <!-- Pemetaan Akun Jurnal Otomatis -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-3">
                <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pemetaan Akun Buku Besar Terkait</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white">PPN Keluaran (Output VAT)</div>
                            <div class="text-[11px] text-slate-500">Faktur Penjualan Kasir POS</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300 font-mono text-xs font-black">2003-001</span>
                    </div>

                    <div class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-bold text-slate-900 dark:text-white">PPN Masukan (Input VAT)</div>
                            <div class="text-[11px] text-slate-500">Faktur Pembelian Supplier</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-mono text-xs font-black">1006-001</span>
                    </div>
                </div>
            </div>

            <!-- Tombol Submit -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-6 flex items-center justify-end space-x-3">
                <a href="{{ route('accounting.coa.index') }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs hover:shadow transition-all">
                    💾 Simpan Pengaturan Pajak
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
