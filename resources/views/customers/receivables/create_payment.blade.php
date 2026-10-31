@extends('layouts.app')

@section('title', 'Catat Pembayaran Piutang - ' . $customer->name)
@section('tab_title', 'Bayar Piutang')

@section('content')
<div class="space-y-4 p-4 max-w-4xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.receivables.index') }}" class="hover:text-blue-600 font-semibold">Buku Piutang</a>
                <span>/</span>
                <a href="{{ route('customers.receivables.ledger', $customer->id) }}" class="hover:text-blue-600 font-semibold font-mono">{{ $customer->code }}</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Catat Pembayaran</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>💳 Catat Penerimaan Pembayaran Piutang Pelanggan</span>
            </h1>
        </div>

        <a href="{{ route('customers.receivables.ledger', $customer->id) }}"
           class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
            Kembali ke Kartu Piutang
        </a>
    </div>

    <!-- SUMMARY CUSTOMER CARD -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-950 border border-blue-800 rounded-2xl p-4 text-white shadow-md flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-blue-300">Pelanggan Tagihan</div>
            <div class="text-lg font-black mt-0.5">{{ $customer->name }} <span class="text-xs font-mono font-normal text-blue-200">({{ $customer->code }})</span></div>
            <div class="text-xs text-blue-200/80">{{ $customer->phone ?: 'Tidak ada nomor telepon' }} • {{ $customer->address ?: 'Alamat tidak terdata' }}</div>
        </div>
        <div class="text-right">
            <div class="text-[11px] font-bold uppercase tracking-wider text-rose-300">Total Saldo Piutang Berjalan</div>
            <div class="text-2xl font-black text-white font-mono mt-0.5">
                Rp {{ number_format((float)$customer->current_credit_balance, 0, ',', '.') }}
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs">
            <ul class="list-disc pl-4 space-y-0.5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('customers.receivables.pay.store', $customer->id) }}" method="POST" id="paymentForm" class="space-y-4">
        @csrf

        <!-- INFORMASI PEMBAYARAN -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Rincian Pembayaran</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Bayar <span class="text-rose-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Pembayaran <span class="text-rose-500">*</span></label>
                    <select name="payment_method" id="payMethod" required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                        <option value="CASH" {{ old('payment_method') === 'CASH' ? 'selected' : '' }}>KAS TUNAI (Masuk Kas Toko)</option>
                        <option value="TRANSFER" {{ old('payment_method') === 'TRANSFER' ? 'selected' : '' }}>TRANSFER BANK (Rekening Toko)</option>
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nominal Yang Dibayarkan (Rp) <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="1" max="{{ (float)$customer->current_credit_balance }}"
                           id="totalAmount" name="total_amount"
                           value="{{ old('total_amount', (float)$customer->current_credit_balance) }}" required
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-black text-sm text-blue-600 dark:text-blue-400 focus:ring-2 focus:ring-blue-500">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Klik untuk melunasi seluruhnya atau ketik jumlah cicilan.</span>
                </div>

                <div id="bankSection" class="hidden space-y-2">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Rekening Bank Tujuan</label>
                        <select name="bank_name" id="bankSelect" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                            <option value="BCA">BCA (Rek: 123-456-7890)</option>
                            <option value="MANDIRI">MANDIRI (Rek: 098-765-4321)</option>
                            <option value="BRI">BRI (Rek: 567-890-1234)</option>
                            <option value="BNI">BNI (Rek: 321-654-0987)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">No. Bukti / Ref Transfer</label>
                        <input type="text" name="reference_number" placeholder="Contoh: TRF-BCA-98765"
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan</label>
                    <input type="text" name="notes" placeholder="Contoh: Cicilan termin 1 faktur sembako minggu lalu"
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
                </div>
            </div>
        </div>

        <!-- DAFTAR FAKTUR OUTSTANDING UNTUK ALOKASI -->
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                    Alokasi Pembayaran Faktur (Opsional - Sistem otomatis FIFO jika dikosongkan)
                </h2>
                <button type="button" id="autoAllocateBtn" class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg text-xs font-bold hover:bg-blue-100">
                    Auto Alokasikan Sesuai Nominal
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-2.5">No. Faktur</th>
                            <th class="p-2.5">Tgl Transaksi</th>
                            <th class="p-2.5">Jatuh Tempo</th>
                            <th class="p-2.5 text-right">Sisa Piutang Faktur</th>
                            <th class="p-2.5 text-right w-44">Alokasi Bayar (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-xs">
                        @foreach($outstandingInvoices as $inv)
                            <tr>
                                <td class="p-2.5 font-bold text-blue-600 dark:text-blue-400">
                                    {{ $inv->invoice_number }}
                                </td>
                                <td class="p-2.5 font-sans">
                                    {{ $inv->created_at->format('d/m/Y') }}
                                </td>
                                <td class="p-2.5 font-sans">
                                    {{ $inv->due_date ? $inv->due_date->format('d/m/Y') : '-' }}
                                </td>
                                <td class="p-2.5 text-right font-bold text-rose-600 dark:text-rose-400">
                                    Rp {{ number_format((float)$inv->remaining_receivable, 0, ',', '.') }}
                                </td>
                                <td class="p-2.5 text-right">
                                    <input type="number" step="0.01" min="0" max="{{ (float)$inv->remaining_receivable }}"
                                           name="allocations[{{ $inv->id }}]"
                                           data-remaining="{{ (float)$inv->remaining_receivable }}"
                                           placeholder="0"
                                           class="allocation-input w-full px-2.5 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-right font-bold text-xs focus:ring-2 focus:ring-blue-500">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SUBMIT -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('customers.receivables.ledger', $customer->id) }}"
               class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-emerald-500/25 cursor-pointer">
                ✅ Konfirmasi Pembayaran & Terbitkan Kwitansi
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payMethod = document.getElementById('payMethod');
    const bankSection = document.getElementById('bankSection');
    const totalAmount = document.getElementById('totalAmount');
    const autoAllocateBtn = document.getElementById('autoAllocateBtn');
    const allocationInputs = document.querySelectorAll('.allocation-input');

    function toggleBank() {
        if (payMethod.value === 'TRANSFER') {
            bankSection.classList.remove('hidden');
        } else {
            bankSection.classList.add('hidden');
        }
    }

    payMethod.addEventListener('change', toggleBank);
    toggleBank();

    autoAllocateBtn.addEventListener('click', function () {
        let remaining = parseFloat(totalAmount.value) || 0;
        allocationInputs.forEach(input => {
            const invoiceRem = parseFloat(input.dataset.remaining) || 0;
            if (remaining > 0) {
                const applied = Math.min(remaining, invoiceRem);
                input.value = applied;
                remaining -= applied;
            } else {
                input.value = '';
            }
        });
    });
});
</script>
@endsection
