@extends('layouts.app')

@section('title', 'Deposito & Titipan Pelanggan — SIGMA ERP')

@section('content')
<div class="space-y-6" x-data="{
    showTopUpModal: false,
    showWithdrawModal: false,
    selectedCustomer: null,
    customerId: '',
    customerName: '',
    availableBalance: 0,
    openTopUp(c) {
        this.selectedCustomer = c;
        this.customerId = c ? c.id : '';
        this.customerName = c ? c.name : '';
        this.showTopUpModal = true;
    },
    openWithdraw(c) {
        this.selectedCustomer = c;
        this.customerId = c ? c.id : '';
        this.customerName = c ? c.name : '';
        this.availableBalance = c ? c.deposit_balance : 0;
        this.showWithdrawModal = true;
    }
}">

    <!-- Header & Subheader -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="p-2.5 bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-2xl">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Deposito &amp; Titipan Pelanggan</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pengelolaan saldo simpanan, uang muka pesanan &amp; titipan belanja pelanggan (Fitur iPos 5 / Ketoko).</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <button @click="openTopUp(null)" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold shadow-md hover:from-blue-700 hover:to-indigo-700 transition cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Terima Deposito Baru</span>
            </button>
            <a href="{{ route('accounting.deposits.supplier.index') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 text-sm font-semibold transition">
                <span>Deposito Supplier &rarr;</span>
            </a>
        </div>
    </div>

    <!-- KPI / Highlights Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Saldo Deposito Aktif</p>
                <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">Rp {{ number_format($totalDepositBalance, 0, ',', '.') }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Kewajiban titipan dana pelanggan (Akun 2005-001)</p>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-950/60 rounded-xl text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pelanggan Pemilik Saldo</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">
                    {{ $customers->filter(fn($c) => $c->deposit_balance > 0)->count() }}
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Dari {{ $customers->total() }} pelanggan terdaftar</p>
            </div>
            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/60 rounded-xl text-emerald-600 dark:text-emerald-400">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Keseimbangan Akuntansi</p>
                <h3 class="text-base font-bold text-slate-800 dark:text-slate-200 mt-1">Otomatis Terbit Jurnal</h3>
                <p class="text-xs text-slate-400 mt-0.5">Top-up &amp; penarikan membukukan Debit/Credit otomatis</p>
            </div>
            <div class="p-3 bg-indigo-50 dark:bg-indigo-950/60 rounded-xl text-indigo-600 dark:text-indigo-400">
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

    <!-- Search & Filter Bar -->
    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('accounting.deposits.customer.index') }}" class="flex-1 flex items-center space-x-2">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama pelanggan, nomor kontak, kode..." class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition">Cari</button>
            @if($search)
                <a href="{{ route('accounting.deposits.customer.index') }}" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </form>
    </div>

    <!-- Customer Balances Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50 dark:bg-slate-800/60 text-xs uppercase font-bold text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-5 py-3.5">Pelanggan</th>
                        <th class="px-4 py-3.5">Kontak / HP</th>
                        <th class="px-4 py-3.5">Tier / Grup</th>
                        <th class="px-4 py-3.5 text-right">Saldo Deposito (Titipan)</th>
                        <th class="px-4 py-3.5 text-right">Piutang Berjalan</th>
                        <th class="px-5 py-3.5 text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center space-x-2">
                                    <span>{{ $c->name }}</span>
                                    <span class="text-xs font-mono text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ $c->code }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-xs">{{ $c->phone ?? '-' }}</td>
                            <td class="px-4 py-4 text-xs">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    {{ $c->tier?->name ?? 'Umum' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right font-black {{ $c->deposit_balance > 0 ? 'text-blue-600 dark:text-blue-400 text-base' : 'text-slate-400' }}">
                                Rp {{ number_format($c->deposit_balance, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-4 text-right text-xs font-semibold {{ $c->current_credit_balance > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400' }}">
                                Rp {{ number_format($c->current_credit_balance, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button @click="openTopUp({{ json_encode($c) }})" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-400 transition cursor-pointer">
                                        + Top Up
                                    </button>
                                    @if($c->deposit_balance > 0)
                                        <button @click="openWithdraw({{ json_encode($c) }})" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-400 transition cursor-pointer">
                                            - Tarik
                                        </button>
                                    @endif
                                    <a href="{{ route('accounting.deposits.customer.ledger', $c->id) }}" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 transition">
                                        Mutasi &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                Belum ada data pelanggan yang sesuai dengan pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-200 dark:border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: TOP UP DEPOSITO -->
    <div x-show="showTopUpModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showTopUpModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-blue-100 dark:bg-blue-900/50 text-blue-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Terima / Top Up Deposito</h3>
                </div>
                <button @click="showTopUpModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('accounting.deposits.customer.top-up') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Pelanggan</label>
                    <select name="customer_id" x-model="customerId" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($allCustomers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }}) — Saldo: Rp {{ number_format($c->deposit_balance, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Nominal Top Up (Rp)</label>
                        <input type="number" step="0.01" min="1000" name="amount" required placeholder="Contoh: 500000" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Metode Bayar</label>
                        <select name="payment_method" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                            <option value="CASH">Tunai / Cash</option>
                            <option value="BANK_TRANSFER">Transfer Bank</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Kas / Bank Penampung</label>
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
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Keterangan / Memo</label>
                    <input type="text" name="notes" placeholder="Contoh: DP Pesanan Catering / Titipan Belanja" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl text-xs text-blue-700 dark:text-blue-300">
                    💡 Sistem otomatis membukukan jurnal: <strong>Debit Kas/Bank</strong> dan <strong>Kredit Hutang Deposito Pelanggan (2005-001)</strong>.
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showTopUpModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-blue-600 hover:bg-blue-700 text-white shadow-md transition cursor-pointer">Simpan Top Up</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: TARIK DEPOSITO -->
    <div x-show="showWithdrawModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div @click.away="showWithdrawModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 bg-amber-100 dark:bg-amber-900/50 text-amber-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Penarikan / Refund Deposito</h3>
                </div>
                <button @click="showWithdrawModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>

            <form action="{{ route('accounting.deposits.customer.withdraw') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Pelanggan</label>
                    <select name="customer_id" x-model="customerId" required class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                        <option value="">-- Pilih Pelanggan --</option>
                        @foreach($allCustomers as $c)
                            @if($c->deposit_balance > 0)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }}) — Saldo: Rp {{ number_format($c->deposit_balance, 0, ',', '.') }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-center justify-between">
                    <span>Saldo Tersedia:</span>
                    <span class="font-bold text-sm" x-text="'Rp ' + Number(availableBalance).toLocaleString('id-ID')"></span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Nominal Ditarik (Rp)</label>
                        <input type="number" step="0.01" min="1000" :max="availableBalance" name="amount" required placeholder="Contoh: 100000" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Metode Penarikan</label>
                        <select name="payment_method" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white font-medium">
                            <option value="CASH">Tunai / Cash</option>
                            <option value="BANK_TRANSFER">Transfer Bank</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Dikeluarkan Dari Akun</label>
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
                    <input type="text" name="notes" placeholder="Contoh: Pengembalian sisa deposit pelanggan" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showWithdrawModal = false" class="px-4 py-2 text-sm font-semibold rounded-xl text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Batal</button>
                    <button type="submit" class="px-5 py-2 text-sm font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-md transition cursor-pointer">Proses Penarikan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
