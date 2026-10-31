@extends('layouts.app')

@section('title', 'Buat Jurnal Umum / Biaya Manual')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.journals.index') }}" class="hover:underline">Jurnal Umum</a>
                <span>/</span>
                <span>Input Jurnal Biaya Manual</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📝 Formulir Jurnal Umum & Biaya Operasional</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Catat pengeluaran listrik, sewa, gaji, prive, atau penyesuaian non-kas dengan kepatuhan double-entry seimbang.
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('accounting.journals.index') }}"
               class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>← Kembali ke Daftar</span>
            </a>
        </div>
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

    <!-- Preset Cepat (1-Klik Template Biaya Toko) -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-800/80 dark:to-slate-900 p-4 rounded-2xl border border-blue-100 dark:border-slate-800 shadow-xs">
        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">⚡ Template Pengeluaran Cepat (1-Klik Isi Akun):</span>
        <div class="flex flex-wrap gap-2 mt-2">
            <button type="button" onclick="applyPreset('LISTRIK')"
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                💡 Bayar Listrik / Air / WiFi
            </button>
            <button type="button" onclick="applyPreset('GAJI')"
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                👥 Bayar Gaji & Upah Kasir
            </button>
            <button type="button" onclick="applyPreset('SEWA')"
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                🏢 Biaya Sewa Ruko / Toko
            </button>
            <button type="button" onclick="applyPreset('OPERASIONAL')"
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-blue-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                📦 Perlengkapan Toko / ATK
            </button>
            <button type="button" onclick="applyPreset('PRIVE')"
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-purple-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                👤 Prive Penarikan Pemilik
            </button>
            <button type="button" onclick="applyPreset('MODAL')"
                    class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-800 hover:bg-emerald-600 hover:text-white border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all shadow-2xs">
                💰 Tambahan Modal Pemilik
            </button>
        </div>
    </div>

    <!-- Form Utama -->
    <form id="journalForm" action="{{ route('accounting.journals.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Metadata Header -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Transaksi <span class="text-rose-500">*</span></label>
                <input type="date" name="entry_date" value="{{ old('entry_date', $todayDate) }}" required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">No. Bukti / Referensi <span class="text-rose-500">*</span></label>
                <input type="text" name="reference_number" id="referenceNumber" value="{{ old('reference_number', $suggestedRef) }}" required
                       class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Cabang</label>
                <select name="store_id"
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    <option value="">-- Kantor Pusat (Holding) --</option>
                    @foreach($stores as $st)
                    <option value="{{ $st->id }}" {{ old('store_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Memo <span class="text-rose-500">*</span></label>
                <input type="text" name="description" id="journalDescription" value="{{ old('description') }}" required placeholder="Contoh: Pembayaran listrik PLN toko bulan ini"
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>
        </div>

        <!-- Tabel Baris Jurnal Dinamis -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Rincian Baris Akun Debit & Kredit</h3>
                    <p class="text-[11px] text-slate-400">Minimal 2 baris akun. Nilai Total Debit harus sama persis dengan Total Kredit.</p>
                </div>
                <button type="button" onclick="addRow()"
                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 text-blue-700 dark:text-blue-300 text-xs font-bold border border-blue-200 dark:border-blue-800">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Tambah Baris</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="journalTable">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4 w-12 text-center">No</th>
                            <th class="py-3 px-4 w-72">Akun Perkiraan (COA)</th>
                            <th class="py-3 px-4">Deskripsi Baris (Memo)</th>
                            <th class="py-3 px-4 w-48 text-right">Debit (Rp)</th>
                            <th class="py-3 px-4 w-48 text-right">Kredit (Rp)</th>
                            <th class="py-3 px-4 w-12 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="journalLinesBody" class="divide-y divide-slate-100 dark:divide-slate-800/60">
                        <!-- Baris 1 (Default Debit) -->
                        <tr class="journal-row hover:bg-slate-50/50">
                            <td class="py-3 px-4 text-center font-bold text-slate-400 row-number">1</td>
                            <td class="py-2 px-3">
                                <select name="lines[0][account_id]" required onchange="calculateTotals()"
                                        class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white account-select">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" data-type="{{ $acc->type }}">
                                        [{{ $acc->code }}] {{ $acc->name }} ({{ $acc->type }})
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 px-3">
                                <input type="text" name="lines[0][memo]" placeholder="Memo baris..."
                                       class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-memo">
                            </td>
                            <td class="py-2 px-3">
                                <input type="number" step="0.01" min="0" name="lines[0][debit]" value="0" oninput="calculateTotals()"
                                       class="w-full px-2.5 py-1.5 text-xs font-mono text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-debit">
                            </td>
                            <td class="py-2 px-3">
                                <input type="number" step="0.01" min="0" name="lines[0][credit]" value="0" oninput="calculateTotals()"
                                       class="w-full px-2.5 py-1.5 text-xs font-mono text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-credit">
                            </td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" onclick="removeRow(this)" class="text-rose-400 hover:text-rose-600 font-bold text-sm">✕</button>
                            </td>
                        </tr>

                        <!-- Baris 2 (Default Kredit) -->
                        <tr class="journal-row hover:bg-slate-50/50">
                            <td class="py-3 px-4 text-center font-bold text-slate-400 row-number">2</td>
                            <td class="py-2 px-3">
                                <select name="lines[1][account_id]" required onchange="calculateTotals()"
                                        class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white account-select">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" data-type="{{ $acc->type }}">
                                        [{{ $acc->code }}] {{ $acc->name }} ({{ $acc->type }})
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 px-3">
                                <input type="text" name="lines[1][memo]" placeholder="Memo baris..."
                                       class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-memo">
                            </td>
                            <td class="py-2 px-3">
                                <input type="number" step="0.01" min="0" name="lines[1][debit]" value="0" oninput="calculateTotals()"
                                       class="w-full px-2.5 py-1.5 text-xs font-mono text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-debit">
                            </td>
                            <td class="py-2 px-3">
                                <input type="number" step="0.01" min="0" name="lines[1][credit]" value="0" oninput="calculateTotals()"
                                       class="w-full px-2.5 py-1.5 text-xs font-mono text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-credit">
                            </td>
                            <td class="py-2 px-3 text-center">
                                <button type="button" onclick="removeRow(this)" class="text-rose-400 hover:text-rose-600 font-bold text-sm">✕</button>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Total Balance Footer -->
                    <tfoot class="bg-slate-50 dark:bg-slate-800/80 font-bold border-t-2 border-slate-200 dark:border-slate-700">
                        <tr>
                            <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider text-[11px] text-slate-700 dark:text-slate-300">
                                Total Keseimbangan (Debit vs Kredit):
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-sm text-emerald-600 dark:text-emerald-400" id="totalDebitDisplay">
                                Rp 0
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-sm text-blue-600 dark:text-blue-400" id="totalCreditDisplay">
                                Rp 0
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span id="balanceIndicator" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">
                                    Rp 0
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Action Bar -->
            <div class="p-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-500" id="balanceText">
                    Silakan masukkan akun dan nominal debit & kredit.
                </div>

                <div class="flex items-center space-x-2.5">
                    <a href="{{ route('accounting.journals.index') }}" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">
                        Batal
                    </a>
                    <button type="submit" id="btnSubmitJournal"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                        💾 Simpan & Bukukan Jurnal
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="rowTemplate">
    <tr class="journal-row hover:bg-slate-50/50">
        <td class="py-3 px-4 text-center font-bold text-slate-400 row-number">__INDEX__</td>
        <td class="py-2 px-3">
            <select name="lines[__NAME_INDEX__][account_id]" required onchange="calculateTotals()"
                    class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white account-select">
                <option value="">-- Pilih Akun --</option>
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" data-type="{{ $acc->type }}">
                    [{{ $acc->code }}] {{ $acc->name }} ({{ $acc->type }})
                </option>
                @endforeach
            </select>
        </td>
        <td class="py-2 px-3">
            <input type="text" name="lines[__NAME_INDEX__][memo]" placeholder="Memo baris..."
                   class="w-full px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-memo">
        </td>
        <td class="py-2 px-3">
            <input type="number" step="0.01" min="0" name="lines[__NAME_INDEX__][debit]" value="0" oninput="calculateTotals()"
                   class="w-full px-2.5 py-1.5 text-xs font-mono text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-debit">
        </td>
        <td class="py-2 px-3">
            <input type="number" step="0.01" min="0" name="lines[__NAME_INDEX__][credit]" value="0" oninput="calculateTotals()"
                   class="w-full px-2.5 py-1.5 text-xs font-mono text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white row-credit">
        </td>
        <td class="py-2 px-3 text-center">
            <button type="button" onclick="removeRow(this)" class="text-rose-400 hover:text-rose-600 font-bold text-sm">✕</button>
        </td>
    </tr>
</template>

<script>
let rowIndex = 2;

function addRow() {
    const tbody = document.getElementById('journalLinesBody');
    const template = document.getElementById('rowTemplate').innerHTML;
    const currentRows = tbody.querySelectorAll('.journal-row').length + 1;
    const html = template.replace(/__INDEX__/g, currentRows).replace(/__NAME_INDEX__/g, rowIndex++);
    tbody.insertAdjacentHTML('beforeend', html);
    calculateTotals();
}

function removeRow(btn) {
    const tbody = document.getElementById('journalLinesBody');
    if (tbody.querySelectorAll('.journal-row').length <= 2) {
        alert('Minimal harus ada 2 baris akun!');
        return;
    }
    btn.closest('tr').remove();
    reindexRows();
    calculateTotals();
}

function reindexRows() {
    const rows = document.querySelectorAll('#journalLinesBody .journal-row');
    rows.forEach((row, idx) => {
        row.querySelector('.row-number').textContent = idx + 1;
    });
}

function calculateTotals() {
    let totalDebit = 0;
    let totalCredit = 0;

    document.querySelectorAll('.row-debit').forEach(input => {
        totalDebit += parseFloat(input.value) || 0;
    });

    document.querySelectorAll('.row-credit').forEach(input => {
        totalCredit += parseFloat(input.value) || 0;
    });

    document.getElementById('totalDebitDisplay').textContent = 'Rp ' + totalDebit.toLocaleString('id-ID');
    document.getElementById('totalCreditDisplay').textContent = 'Rp ' + totalCredit.toLocaleString('id-ID');

    const diff = Math.abs(totalDebit - totalCredit);
    const indicator = document.getElementById('balanceIndicator');
    const balanceText = document.getElementById('balanceText');
    const btnSubmit = document.getElementById('btnSubmitJournal');

    if (totalDebit > 0 && diff < 0.01) {
        indicator.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400';
        indicator.textContent = '✓ SEIMBANG';
        balanceText.innerHTML = '<span class="text-emerald-600 font-bold">✓ Jurnal seimbang dan siap dibukukan ke General Ledger.</span>';
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        indicator.className = 'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400';
        indicator.textContent = 'SELISIH: Rp ' + diff.toLocaleString('id-ID');
        balanceText.innerHTML = '<span class="text-rose-500 font-bold">⚠️ Debit dan Kredit belum seimbang! Selisih: Rp ' + diff.toLocaleString('id-ID') + '</span>';
    }
}

// Preset Quick Templates
function applyPreset(type) {
    const tbody = document.getElementById('journalLinesBody');
    const desc = document.getElementById('journalDescription');
    const selects = tbody.querySelectorAll('.account-select');

    function findOptionByCode(select, codePrefix) {
        for (let opt of select.options) {
            if (opt.getAttribute('data-code') && opt.getAttribute('data-code').startsWith(codePrefix)) {
                return opt.value;
            }
        }
        return '';
    }

    if (type === 'LISTRIK') {
        desc.value = 'Pembayaran Beban Listrik, Air & Internet Toko';
        // Baris 1: Beban (6001 / 6003 / 5001)
        selects[0].value = findOptionByCode(selects[0], '600') || findOptionByCode(selects[0], '500');
        // Baris 2: Kas Toko (1001-001)
        selects[1].value = findOptionByCode(selects[1], '1001-001');
    } else if (type === 'GAJI') {
        desc.value = 'Pembayaran Gaji & Upah Karyawan / Kasir Toko';
        selects[0].value = findOptionByCode(selects[0], '600');
        selects[1].value = findOptionByCode(selects[1], '1001-001');
    } else if (type === 'SEWA') {
        desc.value = 'Pembayaran Beban Sewa Tempat / Toko';
        selects[0].value = findOptionByCode(selects[0], '600');
        selects[1].value = findOptionByCode(selects[1], '1002-001') || findOptionByCode(selects[1], '1001'); // Bank BCA / Kas
    } else if (type === 'OPERASIONAL') {
        desc.value = 'Pembelian Perlengkapan & ATK Operasional Toko';
        selects[0].value = findOptionByCode(selects[0], '600');
        selects[1].value = findOptionByCode(selects[1], '1001-001');
    } else if (type === 'PRIVE') {
        desc.value = 'Penarikan Prive Pemilik untuk Keperluan Pribadi';
        selects[0].value = findOptionByCode(selects[0], '300') || findOptionByCode(selects[0], '1001');
        selects[1].value = findOptionByCode(selects[1], '1001-001');
    } else if (type === 'MODAL') {
        desc.value = 'Setoran Tambahan Modal Usaha Pemilik';
        selects[0].value = findOptionByCode(selects[0], '1001-001'); // Kas Toko Bertambah (Debit)
        selects[1].value = findOptionByCode(selects[1], '3001-001'); // Modal Pemilik (Kredit)
    }

    calculateTotals();
}

document.addEventListener('DOMContentLoaded', () => {
    calculateTotals();
});
</script>
@endsection
