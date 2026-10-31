@extends('layouts.app')

@section('title', 'Deposito & Uang Muka Supplier — SIGMA ERP')

@section('content')
<div class="space-y-6" x-data="{
    showTopUpModal: false,
    showRefundModal: false,
    selectedSupplier: null,
    supplierId: '',
    supplierName: '',
    availableBalance: 0,
    openTopUp(s) {
        this.selectedSupplier = s;
        this.supplierId = s ? s.id : '';
        this.supplierName = s ? s.name : '';
        this.showTopUpModal = true;
    },
    openRefund(s) {
        this.selectedSupplier = s;
        this.supplierId = s ? s.id : '';
        this.supplierName = s ? s.name : '';
        this.availableBalance = s ? s.deposit_balance : 0;
        this.showRefundModal = true;
    }
}">

    <!-- Header & Subheader -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="p-2.5 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Deposito &amp; Uang Muka Supplier</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pengelolaan uang muka pembelian (DP), titipan dana ke pabrik / distributor (Fitur iPos 5 / Ketoko).</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <button @click="openTopUp(null)" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 text-white text-sm font-semibold shadow-md hover:from-indigo-700 hover:to-blue-700 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Bayar Uang Muka (DP)</span>
            </button>
            <a href="{{ route('accounting.deposits.customer.index') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 text-sm font-semibold transition">
                <span>&larr; Deposito Pelanggan</span>
            </a>
        </div>
    </div>

    <!-- KPI / Highlights Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Uang Muka Supplier</p>
                <h3 class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">Rp {{ number_format($totalDepositBalance, 0, ',', '.') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Aset lancar uang muka pembelian (Akun 1105-001)</p>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Supplier dengan Uang Muka</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $suppliers->filter(fn($s) => $s->deposit_balance > 0)->count() }}
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Dari {{ $suppliers->total() }} supplier aktif</p>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/60 rounded-xl text-emerald-600 dark:text-emerald-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Keseimbangan Akuntansi</p>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mt-1">Debit Uang Muka &amp; Kredit Kas</h3>
                <p class="text-xs text-slate-400 mt-0.5">Siap digunakan memotong Faktur Pembelian (AP)</p>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-950/60 rounded-xl text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Alert / Messages -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $errors->first('error') }}</span>
        </div>
    @endif

    <!-- Search Bar -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('accounting.deposits.supplier.index') }}" class="flex-1 flex items-center space-x-2">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama supplier, kontak person, kode..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">Cari</button>
            @if($search)
                <a href="{{ route('accounting.deposits.supplier.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>
    </div>

    <!-- Supplier Balances Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Supplier</th>
                        <th class="px-4 py-3.5">Kontak Person</th>
                        <th class="px-4 py-3.5">No. Telepon</th>
                        <th class="px-4 py-3.5 text-right">Saldo Uang Muka (DP)</th>
                        <th class="px-5 py-3.5 text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($suppliers as $s)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $s->name }}</span>
                                    <span class="text-xs font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $s->code }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs">{{ $s->contact_person ?? '-' }}</td>
                            <td class="px-4 py-4 text-xs">{{ $s->phone ?? '-' }}</td>
                            <td class="px-4 py-4 text-right font-black {{ $s->deposit_balance > 0 ? 'text-indigo-600 dark:text-indigo-400 text-base' : 'text-slate-400' }}">
                                Rp {{ number_format($s->deposit_balance, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openTopUp({{ json_encode($s) }})" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-400 transition cursor-pointer">
                                        + Bayar DP
                                    </button>
                                    @if($s->deposit_balance > 0)
                                        <button @click="openRefund({{ json_encode($s) }})" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-400 transition cursor-pointer">
                                            Refund DP
                                        </button>
                                    @endif
                                    <a href="{{ route('accounting.deposits.supplier.ledger', $s->id) }}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 transition">
                                        Mutasi &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                Belum ada data supplier yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: BAYAR UANG MUKA (DP) KE SUPPLIER -->
    <div x-show="showTopUpModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showTopUpModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Bayar Uang Muka (DP) ke Supplier</h3>
                </div>
                <button @click="showTopUpModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('accounting.deposits.supplier.top-up') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Supplier</label>
                    <select name="supplier_id" x-model="supplierId" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($allSuppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }}) — Sisa DP: Rp {{ number_format($s->deposit_balance, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Nominal DP (Rp)</label>
                        <input type="number" step="0.01" min="1000" name="amount" required placeholder="Contoh: 1000000" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Metode Bayar</label>
                        <select name="payment_method" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                            <option value="BANK_TRANSFER">Transfer Bank</option>
                            <option value="CASH">Tunai / Kas Toko</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Sumber Kas / Bank</label>
                        <select name="account_id" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Keterangan / No. PO</label>
                    <input type="text" name="notes" placeholder="Contoh: DP Pembelian Bahan Baku PO-202610-001" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl text-xs text-indigo-700 dark:text-indigo-300">
                    💡 Sistem otomatis membukukan jurnal: <strong>Debit Uang Muka Pembelian (1105-001)</strong> dan <strong>Kredit Kas/Bank</strong>.
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTopUpModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-md transition cursor-pointer">Simpan Uang Muka</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: REFUND UANG MUKA DARI SUPPLIER -->
    <div x-show="showRefundModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showRefundModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Pengembalian / Refund Uang Muka</h3>
                </div>
                <button @click="showRefundModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('accounting.deposits.supplier.refund') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Supplier</label>
                    <select name="supplier_id" x-model="supplierId" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($allSuppliers as $s)
                            @if($s->deposit_balance > 0)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }}) — Sisa DP: Rp {{ number_format($s->deposit_balance, 0, ',', '.') }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between">
                    <span>Sisa DP di Supplier:</span>
                    <span class="font-bold text-sm" x-text="'Rp ' + Number(availableBalance).toLocaleString('id-ID')"></span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Nominal Refund (Rp)</label>
                        <input type="number" step="0.01" min="1000" :max="availableBalance" name="amount" required placeholder="Contoh: 500000" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Metode Penerimaan</label>
                        <select name="payment_method" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                            <option value="BANK_TRANSFER">Transfer Bank</option>
                            <option value="CASH">Tunai / Kas Toko</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Kas / Bank Penerima</label>
                        <select name="account_id" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-xs">
                            @foreach($cashAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Keterangan / Alasan</label>
                    <input type="text" name="notes" placeholder="Contoh: Refund sisa DP pesanan dibatalkan supplier" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showRefundModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-md transition cursor-pointer">Proses Refund</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
