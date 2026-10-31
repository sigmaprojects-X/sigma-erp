@extends('layouts.app')

@section('title', 'Buku Pembantu Hutang Supplier (Accounts Payable)')
@section('tab_title', 'Hutang Supplier')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="debtLedgerApp()">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('purchases.index') }}" class="hover:text-blue-600 font-semibold">Purchasing</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Buku Pembantu Hutang Supplier</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>💰 Buku Pembantu Hutang Supplier (Accounts Payable)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Pencatatan faktur pembelian tempo, monitoring umur hutang (Aging), dan eksekusi pelunasan hutang dagang.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.debt-ledger.export') }}"
               class="px-3.5 py-2 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>📊 Export Rekap Hutang (CSV)</span>
            </a>
            <a href="{{ route('purchases.orders.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>📑 Daftar PO</span>
            </a>
            <a href="{{ route('purchases.recommendations') }}"
               class="px-3.5 py-2 bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/30 hover:bg-indigo-500/20 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors">
                <span>⚡ Smart Restock</span>
            </a>
        </div>
    </div>

    <!-- STATISTIK KARTU TOTAL HUTANG -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Hutang Berjalan</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($debts->sum('remaining_debt'), 0, ',', '.') }}
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Faktur Belum Lunas</div>
            <div class="text-2xl font-black text-amber-600 mt-1">
                {{ $debts->count() }} <span class="text-xs font-semibold text-slate-400">Faktur</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Hutang Jatuh Tempo (Overdue)</div>
            <div class="text-2xl font-black text-rose-600 mt-1">
                {{ $debts->where('is_overdue', true)->count() }} <span class="text-xs font-semibold text-slate-400">Faktur Lewat Tempo</span>
            </div>
        </div>
    </div>

    @if(session('payment_id'))
    <div class="bg-emerald-50 border border-emerald-300 rounded-2xl p-4 flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🎉</span>
            <div>
                <div class="font-extrabold text-emerald-900 text-sm">Pembayaran Hutang Berhasil Dicatat!</div>
                <div class="text-xs text-emerald-700">Voucher kas keluar telah selesai dibukukan ke General Ledger.</div>
            </div>
        </div>
        <a href="{{ route('purchases.payments.print', session('payment_id')) }}" target="_blank"
           class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs inline-flex items-center gap-1.5 transition-all">
            <span>🖨️</span>
            <span>Cetak Bukti Pembayaran (Payment Voucher)</span>
        </a>
    </div>
    @endif

    <!-- TAB NAVIGASI -->
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button"
                @click="activeTab = 'unpaid'"
                :class="activeTab === 'unpaid' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50'"
                class="px-4 py-2 rounded-xl text-xs transition-all cursor-pointer flex items-center gap-1.5">
            <span>📋 Daftar Tagihan Hutang Belum Lunas</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'unpaid' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                {{ $debts->count() }}
            </span>
        </button>

        <button type="button"
                @click="activeTab = 'history'"
                :class="activeTab === 'history' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50'"
                class="px-4 py-2 rounded-xl text-xs transition-all cursor-pointer flex items-center gap-1.5">
            <span>🧾 Riwayat Pembayaran (Payment Vouchers)</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                  :class="activeTab === 'history' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300'">
                {{ $payments->count() }}
            </span>
        </button>
    </div>

    <!-- TAB 1: TABEL BUKU PEMBANTU HUTANG -->
    <div x-show="activeTab === 'unpaid'" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 font-extrabold text-sm text-slate-800 dark:text-white flex items-center justify-between">
            <span>📋 Daftar Tagihan Hutang Supplier Belum Lunas</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">No. Faktur</th>
                        <th class="p-3">Pemasok</th>
                        <th class="p-3 text-center">Tgl Beli</th>
                        <th class="p-3 text-center">Jatuh Tempo</th>
                        <th class="p-3 text-right">Nilai Faktur</th>
                        <th class="p-3 text-right">Telah Dibayar</th>
                        <th class="p-3 text-right">Sisa Hutang</th>
                        <th class="p-3 text-center">Status Tempo</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($debts as $d)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-blue-600 dark:text-blue-400">
                                <a href="{{ route('purchases.show', $d['purchase_id']) }}" class="hover:underline">
                                    {{ $d['invoice_number'] }}
                                </a>
                                <div class="text-[10px] text-slate-400">{{ $d['reference_number'] }}</div>
                            </td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $d['supplier_name'] }}
                            </td>
                            <td class="p-3 text-center text-slate-500">
                                {{ $d['purchase_date'] }}
                            </td>
                            <td class="p-3 text-center font-bold {{ $d['is_overdue'] ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300' }}">
                                {{ $d['due_date'] }}
                            </td>
                            <td class="p-3 text-right font-bold">
                                Rp {{ number_format($d['grand_total'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right text-emerald-600 font-semibold">
                                Rp {{ number_format($d['paid_amount'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-right font-black text-rose-600 dark:text-rose-400">
                                Rp {{ number_format($d['remaining_debt'], 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center">
                                @if($d['is_overdue'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                        Lewat {{ $d['days_overdue'] }} Hari
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                        Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <button type="button"
                                        @click="openPayModal('{{ $d['purchase_id'] }}', '{{ $d['supplier_id'] }}', '{{ $d['supplier_name'] }}', '{{ $d['invoice_number'] }}', {{ $d['remaining_debt'] }})"
                                        class="px-3 py-1 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-[11px] rounded-lg shadow-xs transition-all cursor-pointer">
                                    Bayar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-1">🎉</div>
                                <div class="font-bold text-slate-600 dark:text-slate-300">Tidak Ada Hutang Dagang!</div>
                                <div class="text-xs">Semua faktur pembelian tempo supplier telah lunas terbayar.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: TABEL RIWAYAT PELUNASAN HUTANG (PAYMENT VOUCHERS) -->
    <div x-show="activeTab === 'history'" x-cloak class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 font-extrabold text-sm text-slate-800 dark:text-white flex items-center justify-between">
            <span>🧾 Riwayat Bukti Pembayaran Hutang Supplier (Payment Vouchers)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10.5px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3">No. Voucher</th>
                        <th class="p-3">Tanggal Bayar</th>
                        <th class="p-3">Supplier</th>
                        <th class="p-3">Metode &amp; Akun Kas/Bank</th>
                        <th class="p-3 text-right">Jumlah Dibayar</th>
                        <th class="p-3">No. Referensi / Ref Bank</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @forelse($payments as $p)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $p->payment_number }}
                            </td>
                            <td class="p-3 whitespace-nowrap text-slate-600 dark:text-slate-300">
                                {{ $p->payment_date ? $p->payment_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="p-3 font-bold text-slate-900 dark:text-white">
                                {{ $p->supplier?->name ?? 'Supplier' }}
                            </td>
                            <td class="p-3">
                                @if($p->payment_method === 'CASH')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">💵 KAS TUNAI</span>
                                @else
                                    <div class="font-bold text-blue-700 dark:text-blue-400">🏦 {{ $p->bankAccount?->bank_name ?: 'TRANSFER' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $p->bankAccount?->account_number }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-black text-slate-900 dark:text-white text-xs">
                                Rp {{ number_format((float) $p->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="p-3 font-mono text-slate-600 dark:text-slate-400">
                                {{ $p->reference_number ?: '-' }}
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <a href="{{ route('purchases.payments.print', $p->id) }}"
                                   target="_blank"
                                   class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 rounded-xl font-bold text-[11px] border border-emerald-300 dark:border-emerald-800 transition-colors inline-flex items-center gap-1 shadow-2xs">
                                    <span>🖨️ Cetak Voucher</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">
                                <div class="text-3xl mb-1">🧾</div>
                                <div class="font-bold text-slate-600 dark:text-slate-300">Belum Ada Riwayat Pembayaran</div>
                                <div class="text-xs">Voucher kas keluar akan tercatat otomatis saat Anda memproses pelunasan hutang.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL PELUNASAN HUTANG SUPPLIER -->
    <div x-show="payModal" x-cloak class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-2xl p-6 max-w-lg w-full shadow-2xl text-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
                <div class="font-black text-sm text-slate-900 dark:text-white">💰 Form Pelunasan Hutang Supplier</div>
                <button type="button" @click="payModal = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('purchases.pay-supplier') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="supplier_id" :value="selectedSupplierId">
                <input type="hidden" name="invoices[0][purchase_id]" :value="selectedPurchaseId">

                <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="text-slate-400 font-bold text-[10px]">Pemasok:</div>
                    <div class="font-black text-slate-900 dark:text-white text-sm" x-text="selectedSupplierName"></div>
                    <div class="text-slate-500 text-[11px] mt-0.5">Faktur: <span x-text="selectedInvoiceNumber"></span></div>
                    <div class="text-rose-600 font-bold text-xs mt-1">Sisa Hutang: Rp <span x-text="formatRupiah(selectedRemainingDebt)"></span></div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nominal yang Dibayarkan: <span class="text-rose-500">*</span></label>
                    <input type="number"
                           name="invoices[0][amount]"
                           x-model="paymentAmount"
                           :max="selectedRemainingDebt"
                           step="100"
                           min="1"
                           required
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-black text-sm text-slate-900 dark:text-white text-right">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Metode Bayar:</label>
                        <select name="payment_method" x-model="paymentMethod" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                            <option value="TRANSFER">Transfer Bank</option>
                            <option value="CASH">Kas Tunai</option>
                        </select>
                    </div>

                    <div x-show="paymentMethod === 'TRANSFER'">
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Rekening Bank Pengirim:</label>
                        <select name="bank_account_id" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs">
                            @foreach($bankAccounts as $b)
                                <option value="{{ $b->id }}">{{ $b->bank_name }} ({{ $b->account_number }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">No. Bukti Transfer / Catatan:</label>
                    <input type="text" name="reference_number" placeholder="Contoh: TRF-BCA-20261023-01" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" @click="payModal = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md cursor-pointer">
                        ✓ Proses Pelunasan Hutang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function debtLedgerApp() {
        return {
            activeTab: 'unpaid',
            payModal: false,
            selectedPurchaseId: '',
            selectedSupplierId: '',
            selectedSupplierName: '',
            selectedInvoiceNumber: '',
            selectedRemainingDebt: 0,
            paymentAmount: 0,
            paymentMethod: 'TRANSFER',

            openPayModal(purchaseId, supplierId, supplierName, invoiceNumber, remainingDebt) {
                this.selectedPurchaseId = purchaseId;
                this.selectedSupplierId = supplierId;
                this.selectedSupplierName = supplierName;
                this.selectedInvoiceNumber = invoiceNumber;
                this.selectedRemainingDebt = remainingDebt;
                this.paymentAmount = remainingDebt;
                this.payModal = true;
            },

            formatRupiah(val) {
                return Math.round(val || 0).toLocaleString('id-ID');
            }
        };
    }
</script>
@endpush
@endsection
