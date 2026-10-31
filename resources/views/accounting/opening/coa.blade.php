@extends('layouts.app')

@section('title', 'Saldo Awal Perkiraan (COA)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Saldo Awal Pembukuan</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>⚖️ Saldo Awal Perkiraan (COA Opening Balance)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Masukkan posisi keuangan awal (Kas, Bank, Persediaan, Aset, Modal) saat pertama kali toko migrasi ke SIGMA ERP.
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('accounting.opening.receivables.index') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>Saldo Awal Piutang →</span>
            </a>
            <a href="{{ route('accounting.opening.payables.index') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>Saldo Awal Hutang →</span>
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
        <div class="font-bold mb-1">⚠️ Terjadi Kesalahan:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form Saldo Awal -->
    <form id="coaOpeningForm" action="{{ route('accounting.opening.coa.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Cutoff Date Bar -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Tanggal Cut-off Saldo Awal:</span>
                <input type="date" name="cutoff_date" value="{{ old('cutoff_date', $cutoffDate) }}" required
                       class="px-3 py-1.5 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            </div>

            <button type="button" onclick="autoBalanceEquity()"
                    class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 hover:bg-purple-100 text-purple-700 dark:text-purple-300 text-xs font-bold border border-purple-200 dark:border-purple-800 transition-all cursor-pointer">
                <span>⚡ Seimbangkan Selisih ke Modal Pemilik (3001-001)</span>
            </button>
        </div>

        <!-- Tabel Akun COA -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4 w-32">Kode Akun</th>
                            <th class="py-3 px-4">Nama Akun Perkiraan</th>
                            <th class="py-3 px-4 w-32">Klasifikasi</th>
                            <th class="py-3 px-4 w-28 text-center">Saldo Normal</th>
                            <th class="py-3 px-4 w-48 text-right">Saldo Awal Debit (Rp)</th>
                            <th class="py-3 px-4 w-48 text-right">Saldo Awal Kredit (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                        @foreach($accounts as $acc)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 account-row" data-code="{{ $acc->code }}" data-type="{{ $acc->type }}">
                            <td class="py-2.5 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                                {{ $acc->code }}
                            </td>
                            <td class="py-2.5 px-4 text-slate-900 dark:text-white font-bold">
                                {{ $acc->name }}
                            </td>
                            <td class="py-2.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $acc->type === 'ASSET' ? 'bg-blue-50 text-blue-700 border-blue-200' : ($acc->type === 'LIABILITY' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-purple-50 text-purple-700 border-purple-200') }}">
                                    {{ $acc->type }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold text-slate-500">
                                {{ $acc->normal_balance }}
                            </td>
                            <td class="py-2 px-3">
                                <input type="number" step="0.01" min="0"
                                       name="balances[{{ $acc->id }}][debit]"
                                       value="{{ old('balances.'.$acc->id.'.debit', $acc->opening_debit > 0 ? $acc->opening_debit : 0) }}"
                                       oninput="recalculateCoaTotals()"
                                       class="w-full px-2.5 py-1.5 text-xs font-mono font-bold text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 debit-input">
                            </td>
                            <td class="py-2 px-3">
                                <input type="number" step="0.01" min="0"
                                       name="balances[{{ $acc->id }}][credit]"
                                       value="{{ old('balances.'.$acc->id.'.credit', $acc->opening_credit > 0 ? $acc->opening_credit : 0) }}"
                                       oninput="recalculateCoaTotals()"
                                       class="w-full px-2.5 py-1.5 text-xs font-mono font-bold text-right rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 credit-input">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <!-- Balance Summary Footer -->
                    <tfoot class="bg-slate-100 dark:bg-slate-800/80 font-bold border-t-2 border-slate-200 dark:border-slate-700">
                        <tr>
                            <td colspan="4" class="py-3 px-4 text-right uppercase tracking-wider text-[11px] text-slate-700 dark:text-slate-300">
                                Total Keseimbangan Saldo Awal:
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-sm text-emerald-600 dark:text-emerald-400" id="totalDebitCoa">
                                Rp {{ number_format($totalDebit, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right font-mono text-sm text-blue-600 dark:text-blue-400" id="totalCreditCoa">
                                Rp {{ number_format($totalCredit, 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="py-2.5 px-4 text-right text-xs font-bold text-slate-500">
                                Selisih Debit vs Kredit:
                            </td>
                            <td colspan="2" class="py-2.5 px-4 text-right" id="diffContainer">
                                <span id="diffBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $difference < 0.01 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ $difference < 0.01 ? '✓ SEIMBANG (BALANCE)' : 'SELISIH: Rp ' . number_format($difference, 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Action Bar -->
            <div class="p-4 bg-slate-50/50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="text-xs text-slate-500">
                    Pastikan seluruh saldo kas fisik, bank, nilai persediaan, dan modal telah terinput secara akurat.
                </div>

                <button type="submit" id="btnSubmitCoa"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                    💾 Simpan &amp; Bukukan Saldo Awal
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function recalculateCoaTotals() {
    let totalDebit = 0;
    let totalCredit = 0;

    document.querySelectorAll('.debit-input').forEach(inp => {
        totalDebit += parseFloat(inp.value) || 0;
    });

    document.querySelectorAll('.credit-input').forEach(inp => {
        totalCredit += parseFloat(inp.value) || 0;
    });

    document.getElementById('totalDebitCoa').textContent = 'Rp ' + totalDebit.toLocaleString('id-ID');
    document.getElementById('totalCreditCoa').textContent = 'Rp ' + totalCredit.toLocaleString('id-ID');

    const diff = Math.abs(totalDebit - totalCredit);
    const badge = document.getElementById('diffBadge');
    const btnSubmit = document.getElementById('btnSubmitCoa');

    if (totalDebit > 0 && diff < 0.01) {
        badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400';
        badge.textContent = '✓ SEIMBANG (BALANCE)';
        btnSubmit.disabled = false;
        btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400';
        badge.textContent = 'SELISIH: Rp ' + diff.toLocaleString('id-ID');
    }
}

function autoBalanceEquity() {
    let totalDebit = 0;
    let totalCredit = 0;

    document.querySelectorAll('.account-row').forEach(row => {
        const code = row.getAttribute('data-code');
        if (code !== '3001-001') {
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const credit = parseFloat(row.querySelector('.credit-input').value) || 0;
            totalDebit += debit;
            totalCredit += credit;
        }
    });

    const diff = totalDebit - totalCredit;
    const modalRow = document.querySelector('.account-row[data-code="3001-001"]');

    if (modalRow) {
        const debitInp = modalRow.querySelector('.debit-input');
        const creditInp = modalRow.querySelector('.credit-input');

        if (diff >= 0) {
            creditInp.value = diff;
            debitInp.value = 0;
        } else {
            debitInp.value = Math.abs(diff);
            creditInp.value = 0;
        }
        recalculateCoaTotals();
    } else {
        alert('Akun Modal Pemilik [3001-001] tidak ditemukan!');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    recalculateCoaTotals();
});
</script>
@endsection
