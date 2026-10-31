@extends('layouts.app')

@section('title', 'Daftar Faktur Penjualan')
@section('tab_title', 'Daftar Penjualan')

@section('content')
<div class="space-y-4" x-data="salesTransactionsApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Daftar Faktur Penjualan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>📑 Daftar Faktur Penjualan</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.invoices.create') }}"
               class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-sm flex items-center gap-1.5 transition-all active:scale-95">
                <span>➕ Input Faktur Back-Office</span>
            </a>
            <a href="{{ route('sales.index') }}"
               class="px-3.5 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors">
                <span>&larr; Hub Penjualan</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
                <span>⚡ BUKA KASIR [F12]</span>
            </a>
        </div>
    </div>

    <!-- QUICK FILTER PILLS & STATUS TAGIHAN -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('sales.transactions') }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ !request('payment_status') ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' }}">
            📋 Semua Faktur
        </a>
        <a href="{{ route('sales.transactions', array_merge(request()->except(['payment_status', 'page']), ['payment_status' => 'UNPAID_TEMPO'])) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('payment_status') === 'UNPAID_TEMPO' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white border border-amber-300 text-amber-800 hover:bg-amber-50' }}">
            ⏳ Piutang Tempo Belum Lunas
        </a>
        <a href="{{ route('sales.transactions', array_merge(request()->except(['payment_status', 'page']), ['payment_status' => 'OVERDUE'])) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ request('payment_status') === 'OVERDUE' ? 'bg-rose-600 text-white shadow-xs' : 'bg-white border border-rose-300 text-rose-800 hover:bg-rose-50' }}">
            ⚠️ Melewati Jatuh Tempo (Overdue)
        </a>
        <a href="{{ route('sales.returns.index') }}"
           class="ml-auto px-3 py-1.5 rounded-lg text-xs font-bold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition-colors">
            <span>🔄</span> Riwayat Retur Penjualan
        </a>
    </div>

    <!-- FILTER BAR (SEARCH, STATUS, PAYMENT METHOD, DATE RANGE) -->
    <div class="bg-white border border-slate-200/90 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('sales.transactions') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            
            <!-- SEARCH KEYWORD -->
            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-600 mb-1">Cari Faktur / Pelanggan:</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ketik INV-..., nama toko..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>
            </div>

            <!-- STATUS TAGIHAN / PIUTANG -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 mb-1">Status Piutang:</label>
                <select name="payment_status" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden bg-white">
                    <option value="">Semua Status</option>
                    <option value="UNPAID_TEMPO" {{ request('payment_status') === 'UNPAID_TEMPO' ? 'selected' : '' }}>⏳ Piutang Belum Lunas</option>
                    <option value="OVERDUE" {{ request('payment_status') === 'OVERDUE' ? 'selected' : '' }}>⚠️ Overdue (Jatuh Tempo)</option>
                </select>
            </div>

            <!-- PAYMENT METHOD -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 mb-1">Metode Bayar:</label>
                <select name="payment_method" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden bg-white">
                    <option value="">Semua Metode</option>
                    <option value="CASH" {{ request('payment_method') === 'CASH' ? 'selected' : '' }}>💵 Tunai (CASH)</option>
                    <option value="TRANSFER" {{ request('payment_method') === 'TRANSFER' ? 'selected' : '' }}>🏦 Transfer Bank</option>
                    <option value="QRIS" {{ request('payment_method') === 'QRIS' ? 'selected' : '' }}>📱 QRIS</option>
                    <option value="SPLIT" {{ request('payment_method') === 'SPLIT' ? 'selected' : '' }}>⚖️ Split Payment</option>
                    <option value="TEMPO" {{ request('payment_method') === 'TEMPO' ? 'selected' : '' }}>📝 Piutang (TEMPO)</option>
                    <option value="VOUCHER" {{ request('payment_method') === 'VOUCHER' ? 'selected' : '' }}>🎟️ Voucher</option>
                </select>
            </div>

            <!-- TANGGAL DARI -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 mb-1">Tanggal Mulai:</label>
                <input type="date"
                       name="start_date"
                       value="{{ request('start_date') }}"
                       class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
            </div>

            <!-- TANGGAL SAMPAI -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 mb-1">Tanggal Sampai:</label>
                <input type="date"
                       name="end_date"
                       value="{{ request('end_date') }}"
                       class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
            </div>

            <!-- TOMBOL FILTER & RESET -->
            <div class="sm:col-span-1 flex items-end gap-1">
                <button type="submit"
                        class="w-full py-1.5 px-3 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-lg transition-colors text-center cursor-pointer">
                    Cari
                </button>
                @if(request()->anyFilled(['search', 'payment_status', 'payment_method', 'start_date', 'end_date']))
                    <a href="{{ route('sales.transactions') }}"
                       class="py-1.5 px-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-lg transition-colors text-center cursor-pointer"
                       title="Reset Filter">
                        ✕
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- DATAGRID TRANSAKSI PENJUALAN -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10.5px]">
                    <tr>
                        <th class="px-3.5 py-3">No. Faktur</th>
                        <th class="px-3 py-3">Waktu</th>
                        <th class="px-3 py-3">Kasir / Sales</th>
                        <th class="px-3 py-3">Pelanggan</th>
                        <th class="px-3 py-3">Metode Bayar</th>
                        <th class="px-3 py-3 text-right">Total Belanja</th>
                        <th class="px-3 py-3 text-right">Dibayar</th>
                        <th class="px-3 py-3 text-right">Piutang (Tempo)</th>
                        <th class="px-3.5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11.5px]">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3.5 py-2.5 font-bold text-blue-900 cursor-pointer hover:underline"
                                @click="openDetailModal('{{ $tx->id }}')">
                                {{ $tx->invoice_number }}
                            </td>
                            <td class="px-3 py-2.5 text-slate-500 font-sans text-xs">
                                {{ $tx->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-3 py-2.5 font-sans text-slate-700">
                                <div>{{ $tx->salesPerson?->name ?? 'Kasir Utama' }}</div>
                            </td>
                            <td class="px-3 py-2.5 font-sans font-semibold text-slate-800">
                                <div>{{ $tx->customer?->name ?? 'UMUM (Eceran)' }}</div>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $tx->customerTier?->name }}</span>
                            </td>
                            <td class="px-3 py-2.5">
                                @if($tx->payment_method === 'CASH')
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">💵 TUNAI</span>
                                @elseif($tx->payment_method === 'TRANSFER')
                                    <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]" title="{{ $tx->bank_name }}">🏦 {{ $tx->bank_name ?: 'TRANSFER' }}</span>
                                @elseif($tx->payment_method === 'SPLIT')
                                    <span class="px-2 py-0.5 rounded bg-purple-100 text-purple-900 font-bold text-[10px]">⚖️ SPLIT ({{ $tx->split_payment_method }})</span>
                                @elseif($tx->payment_method === 'TEMPO')
                                    <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold text-[10px]">📝 TEMPO</span>
                                @elseif($tx->payment_method === 'QRIS')
                                    <span class="px-2 py-0.5 rounded bg-cyan-100 text-cyan-800 font-bold text-[10px]">📱 QRIS</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 font-bold text-[10px]">🎟️ VOUCHER</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-slate-900">
                                Rp {{ number_format((float) $tx->grand_total, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2.5 text-right text-emerald-700 font-bold">
                                Rp {{ number_format((float) $tx->paid_amount, 0, ',', '.') }}
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-rose-700">
                                @if((float) $tx->credit_amount > 0)
                                    Rp {{ number_format((float) $tx->credit_amount, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 font-normal">-</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <div class="inline-flex items-center gap-1 font-sans">
                                    <button type="button"
                                            @click="openDetailModal('{{ $tx->id }}')"
                                            class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded text-[11px] font-semibold border border-blue-200 shadow-2xs cursor-pointer"
                                            title="Rincian Item Faktur">
                                        🔍 Detail
                                    </button>
                                    @if($tx->status !== 'VOID')
                                        @can('access-pos')
                                            <button type="button"
                                                    @click="openReturnModal('{{ $tx->id }}')"
                                                    class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded text-[11px] font-semibold border border-rose-200 shadow-2xs cursor-pointer flex items-center gap-1"
                                                    title="Retur Barang Faktur Ini">
                                                <span>🔄</span> Retur
                                            </button>
                                        @endcan
                                    @endif
                                    <a href="{{ route('sales.print-invoice', $tx->id) }}"
                                       target="_blank"
                                       class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded text-[11px] font-semibold border border-blue-200 shadow-2xs"
                                       title="Cetak Faktur (A4 / Dot Matrix)">
                                        📄 Faktur
                                    </a>
                                    <a href="{{ route('sales.print-delivery-order', $tx->id) }}"
                                       target="_blank"
                                       class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded text-[11px] font-semibold border border-emerald-200 shadow-2xs"
                                       title="Cetak Surat Jalan (DO)">
                                        🚚 DO
                                    </a>
                                    @if((float) $tx->credit_amount > 0 || (float) $tx->remaining_receivable > 0)
                                        <a href="{{ route('sales.print-reminder', $tx->id) }}"
                                           target="_blank"
                                           class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded text-[11px] font-semibold border border-amber-300 shadow-2xs flex items-center gap-1"
                                           title="Cetak Surat Pengingat Tagihan (Statement of Account)">
                                            <span>✉️</span> Tagihan
                                        </a>
                                    @endif
                                    <a href="{{ route('pos.print', $tx->id) }}"
                                       target="_blank"
                                       class="px-2 py-1 bg-white hover:bg-slate-100 text-slate-700 rounded text-[11px] font-semibold border border-slate-300 shadow-2xs"
                                       title="Cetak Ulang Struk Thermal">
                                        🖨️ Struk
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-slate-400 font-sans text-xs">
                                Tidak ada transaksi penjualan yang cocok dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if($transactions->hasPages())
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL DETAIL TRANSAKSI FAKTUR -->
    <div x-show="detailModal"
         x-cloak
         @keydown.escape.window="detailModal = false"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl max-w-2xl w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100">
            
            <!-- HEADER MODAL -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 text-white px-4 py-3 flex items-center justify-between border-b border-slate-700">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🧾</span>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">
                            Rincian Faktur: <span class="font-mono text-amber-300" x-text="currentTx?.invoice_number"></span>
                        </h3>
                        <p class="text-[10px] text-slate-400" x-text="'Waktu Transaksi: ' + formatDateTime(currentTx?.created_at)"></p>
                    </div>
                </div>
                <button type="button"
                        @click="detailModal = false"
                        class="text-slate-400 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- BODY MODAL -->
            <div class="p-4 space-y-3.5 max-h-[75vh] overflow-y-auto">
                
                <!-- INFO PELANGGAN & KASIR -->
                <div class="grid grid-cols-2 gap-2 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-[11.5px]">
                    <div>
                        <span class="text-slate-500 block text-[10px]">Pelanggan:</span>
                        <strong class="text-slate-900" x-text="currentTx?.customer?.name || 'UMUM (Pelanggan Eceran)'"></strong>
                        <div class="text-[10px] text-slate-400" x-text="currentTx?.customer_tier?.name"></div>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-[10px]">Kasir / Sales:</span>
                        <strong class="text-slate-900" x-text="currentTx?.sales_person?.name || 'Kasir Toko'"></strong>
                        <div class="text-[10px] text-slate-400" x-text="'Metode: ' + currentTx?.payment_method"></div>
                    </div>
                </div>

                <!-- TABEL ITEM BELANJA -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2">Nama Barang</th>
                                <th class="px-2 py-2 text-center">Satuan</th>
                                <th class="px-2 py-2 text-right">Qty</th>
                                <th class="px-2 py-2 text-right">Harga</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono text-[11px]">
                            <template x-for="item in currentTx?.items" :key="item.id">
                                <tr>
                                    <td class="px-3 py-2 font-sans font-semibold text-slate-900" x-text="item.product?.name"></td>
                                    <td class="px-2 py-2 text-center text-slate-500 font-sans" x-text="item.product_unit?.unit_code"></td>
                                    <td class="px-2 py-2 text-right font-bold" x-text="parseFloat(item.quantity)"></td>
                                    <td class="px-2 py-2 text-right" x-text="'Rp ' + formatRupiah(item.unit_price)"></td>
                                    <td class="px-3 py-2 text-right font-bold text-slate-900" x-text="'Rp ' + formatRupiah(item.total_price || item.subtotal)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- RINGKASAN TOTAL & PEMBAYARAN -->
                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 space-y-1.5 font-mono text-xs">
                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-sans">Subtotal Belanja:</span>
                        <span x-text="'Rp ' + formatRupiah(currentTx?.subtotal)"></span>
                    </div>
                    <template x-if="parseFloat(currentTx?.voucher_discount) > 0">
                        <div class="flex justify-between items-center text-emerald-700 font-bold">
                            <span class="font-sans" x-text="'Potongan Voucher (' + (currentTx?.voucher?.code || '') + '):'"></span>
                            <span x-text="'-Rp ' + formatRupiah(currentTx?.voucher_discount)"></span>
                        </div>
                    </template>
                    <template x-if="parseFloat(currentTx?.tax_amount) > 0">
                        <div class="flex justify-between items-center text-blue-700">
                            <span class="font-sans" x-text="'Pajak (' + currentTx?.tax_mode + '):'"></span>
                            <span x-text="'+Rp ' + formatRupiah(currentTx?.tax_amount)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between items-center text-sm font-black text-slate-900 pt-1.5 border-t border-slate-200">
                        <span class="font-sans">Grand Total:</span>
                        <span class="text-blue-900" x-text="'Rp ' + formatRupiah(currentTx?.grand_total)"></span>
                    </div>

                    <!-- RINCIAN METODE PEMBAYARAN -->
                    <div class="pt-2 border-t border-dashed border-slate-300 space-y-1 text-[11.5px]">
                        <div class="flex justify-between items-center text-slate-700 font-bold">
                            <span class="font-sans">Metode Bayar:</span>
                            <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-900 text-[10px]" x-text="currentTx?.payment_method"></span>
                        </div>
                        <template x-if="currentTx?.payment_method === 'TRANSFER'">
                            <div class="flex justify-between items-center text-slate-600">
                                <span class="font-sans">Bank Tujuan:</span>
                                <span x-text="currentTx?.bank_name + ' (' + (currentTx?.bank_account_number || '') + ')'"></span>
                            </div>
                        </template>
                        <div class="flex justify-between items-center text-emerald-700 font-bold">
                            <span class="font-sans">Uang Dibayar Kasir:</span>
                            <span x-text="'Rp ' + formatRupiah(currentTx?.paid_amount)"></span>
                        </div>
                        <template x-if="parseFloat(currentTx?.credit_amount) > 0">
                            <div class="flex justify-between items-center text-rose-700 font-black">
                                <span class="font-sans">Sisa Masuk Piutang (Kredit):</span>
                                <span x-text="'Rp ' + formatRupiah(currentTx?.credit_amount)"></span>
                            </div>
                        </template>
                        <template x-if="parseFloat(currentTx?.change_amount) > 0">
                            <div class="flex justify-between items-center text-emerald-800 font-bold">
                                <span class="font-sans">Kembalian Kasir:</span>
                                <span x-text="'Rp ' + formatRupiah(currentTx?.change_amount)"></span>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <!-- FOOTER MODAL DETAIL -->
            <div class="bg-slate-100 px-4 py-2.5 border-t border-slate-200 flex items-center justify-between">
                <button type="button"
                        @click="detailModal = false"
                        class="px-3.5 py-1.5 bg-white border border-slate-300 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                    Tutup (ESC)
                </button>
                <div class="flex items-center gap-2">
                    <template x-if="currentTx?.status !== 'VOID'">
                        <button type="button"
                                @click="detailModal = false; openReturnModal(currentTx.id)"
                                class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                            <span>🔄 Retur Faktur Ini</span>
                        </button>
                    </template>
                    <a :href="'/pos/receipt/' + currentTx?.id"
                       target="_blank"
                       class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-extrabold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <span>🖨️ Cetak Struk Thermal</span>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL FORM PROSES RETUR PENJUALAN (SALES RETURN) -->
    <div x-show="returnModal"
         x-cloak
         @keydown.escape.window="if (!isSubmittingReturn) returnModal = false"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl max-w-3xl w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs animate-in fade-in zoom-in-95 duration-100">

            <!-- Header Modal Retur -->
            <div class="bg-gradient-to-r from-rose-900 via-rose-800 to-slate-900 text-white px-4 py-3 flex items-center justify-between border-b border-rose-700">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🔄</span>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">
                            Retur Penjualan POS: <span class="font-mono text-amber-300" x-text="currentReturnTx?.invoice_number"></span>
                        </h3>
                        <p class="text-[10px] text-rose-200" x-text="'Pelanggan: ' + (currentReturnTx?.customer?.name || 'Pelanggan Umum') + ' | Toko: ' + (currentReturnTx?.store?.name || '-')"></p>
                    </div>
                </div>
                <button type="button"
                        :disabled="isSubmittingReturn"
                        @click="returnModal = false"
                        class="text-rose-300 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- Body Modal Retur -->
            <div class="p-4 space-y-3.5 max-h-[75vh] overflow-y-auto">

                <!-- Alert Petunjuk -->
                <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-950 text-[11px] space-y-1">
                    <div class="font-bold flex items-center gap-1">
                        <span>🛡️</span>
                        <span>Aturan Keamanan Retur Penjualan (AGENTS.md Section 2 &amp; 3):</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-900 text-[10.5px]">
                        <li>Kuantitas retur dibatasi maksimal sisa barang yang belum pernah diretur.</li>
                        <li><strong>Kondisi Layak Jual:</strong> Fisik barang masuk kembali ke stok toko dan langsung siap dijual.</li>
                        <li><strong>Kondisi Rusak:</strong> Barang langsung dialokasikan ke <em>Karantina Stok</em>, <strong>TIDAK</strong> menambah stok layak jual.</li>
                        <template x-if="currentReturnTx?.payment_method === 'TEMPO'">
                            <li><strong>Faktur Tempo:</strong> Opsi Potong Piutang akan mengurangi saldo hutang pelanggan dan sisa tagihan faktur.</li>
                        </template>
                    </ul>
                </div>

                <!-- Tabel Pilihan Item yang Diretur -->
                <div class="border border-slate-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2 w-8 text-center">Pilih</th>
                                <th class="px-3 py-2">Nama Barang</th>
                                <th class="px-2 py-2 text-center">Satuan</th>
                                <th class="px-2 py-2 text-center">Terjual</th>
                                <th class="px-2 py-2 text-center">Sisa Retur</th>
                                <th class="px-2 py-2 text-center w-24">Qty Retur</th>
                                <th class="px-3 py-2 text-center w-36">Kondisi Barang</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-sans text-xs">
                            <template x-for="(item, idx) in returnItems" :key="item.id">
                                <tr :class="item.selected ? 'bg-rose-50/40' : ''">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox"
                                               x-model="item.selected"
                                               :disabled="item.max_returnable <= 0"
                                               class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="font-bold text-slate-800" x-text="item.name"></div>
                                        <div class="text-[10px] text-slate-400 font-mono" x-text="'SKU: ' + item.sku"></div>
                                    </td>
                                    <td class="px-2 py-2 text-center font-bold text-slate-600" x-text="item.unit_code"></td>
                                    <td class="px-2 py-2 text-center font-mono" x-text="item.quantity"></td>
                                    <td class="px-2 py-2 text-center font-mono font-bold"
                                        :class="item.max_returnable > 0 ? 'text-emerald-700' : 'text-slate-400'"
                                        x-text="item.max_returnable"></td>
                                    <td class="px-2 py-2 text-center">
                                        <input type="number"
                                               step="1"
                                               min="1"
                                               :max="item.max_returnable"
                                               x-model.number="item.qty_to_return"
                                               :disabled="!item.selected || item.max_returnable <= 0"
                                               class="w-20 text-center font-mono font-bold py-1 border border-slate-300 rounded text-xs focus:ring-2 focus:ring-rose-500 focus:outline-hidden disabled:bg-slate-100 disabled:text-slate-400">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <select x-model="item.condition"
                                                :disabled="!item.selected"
                                                class="w-full py-1 px-1.5 text-[11px] font-semibold border rounded bg-white focus:ring-2 focus:ring-rose-500 focus:outline-hidden"
                                                :class="item.condition === 'DAMAGED' ? 'border-amber-400 text-amber-800 bg-amber-50' : 'border-emerald-400 text-emerald-800 bg-emerald-50'">
                                            <option value="GOOD">✅ Layak Jual</option>
                                            <option value="DAMAGED">⚠️ Rusak (Karantina)</option>
                                        </select>
                                        <div class="text-[9px] mt-0.5"
                                             :class="item.condition === 'DAMAGED' ? 'text-amber-700 font-semibold' : 'text-emerald-700 font-semibold'"
                                             x-text="item.condition === 'DAMAGED' ? '🛡️ Masuk Karantina' : '📦 Kembali Siap Jual'"></div>
                                    </td>
                                    <td class="px-3 py-2 text-right font-mono font-bold text-slate-900"
                                        x-text="'Rp ' + formatRupiah(item.selected ? (item.qty_to_return * item.unit_price) : 0)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Metode Pengembalian & Alasan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-[11px]">Metode Pengembalian Dana / Penyelesaian:</label>
                        <select x-model="returnRefundMethod" class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg font-medium text-xs bg-white focus:ring-2 focus:ring-rose-500 focus:outline-hidden">
                            <template x-if="currentReturnTx?.payment_method === 'TEMPO' || (currentReturnTx?.customer && parseFloat(currentReturnTx?.customer?.current_credit_balance || 0) > 0)">
                                <option value="CREDIT_REDUCTION">📝 Potong Saldo Piutang (CREDIT REDUCTION)</option>
                            </template>
                            <option value="CASH">💵 Uang Kas Tunai (CASH)</option>
                            <option value="TRANSFER">🏦 Transfer Bank</option>
                        </select>
                        <span class="text-[10px] text-slate-500 mt-1 block">
                            <template x-if="returnRefundMethod === 'CREDIT_REDUCTION'">
                                <span>* Mengurangi sisa piutang faktur &amp; kartu piutang pelanggan di buku besar.</span>
                            </template>
                            <template x-if="returnRefundMethod === 'CASH'">
                                <span>* Mengeluarkan uang tunai dari kasir aktif (tercatat di rekonsiliasi shift kasir).</span>
                            </template>
                            <template x-if="returnRefundMethod === 'TRANSFER'">
                                <span>* Pengembalian dana melalui transfer bank toko ke rekening pelanggan.</span>
                            </template>
                        </span>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1 text-[11px]">Alasan Retur Penjualan (Wajib):</label>
                        <textarea x-model="returnReason"
                                  rows="2"
                                  placeholder="Contoh: Kemasan rusak / salah beli varian / cacat produksi"
                                  class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-rose-500 focus:outline-hidden"></textarea>
                    </div>
                </div>

                <!-- Input PIN Manajer (Jika Pengguna Bukan Owner/Manager/Store Head) -->
                <div x-show="!isPrivilegedUser" class="p-3 bg-amber-50 border border-amber-300 rounded-lg space-y-1">
                    <label class="block font-bold text-amber-900 text-[11px] flex items-center gap-1">
                        <span>🔐</span>
                        <span>Otorisasi PIN Manajer Diperlukan:</span>
                    </label>
                    <p class="text-[10.5px] text-amber-800">
                        Sesuai AGENTS.md Section 4B, pemrosesan retur oleh kasir/sales harus disahkan oleh PIN Manajer.
                    </p>
                    <input type="password"
                           x-model="managerPin"
                           maxlength="10"
                           placeholder="Ketik 4-6 digit PIN Manajer..."
                           class="w-48 px-3 py-1.5 bg-white border border-amber-400 rounded-lg text-center font-mono font-bold tracking-widest text-sm focus:ring-2 focus:ring-amber-500 focus:outline-hidden">
                </div>

                <!-- Ringkasan Refund Akuntansi -->
                <div class="p-3 bg-slate-100 rounded-lg space-y-1 font-mono text-xs border border-slate-200">
                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-sans">Subtotal Nilai Barang:</span>
                        <span x-text="'Rp ' + formatRupiah(calculateReturnSubtotal())"></span>
                    </div>
                    <template x-if="calculateReturnTax() > 0">
                        <div class="flex justify-between items-center text-blue-700">
                            <span class="font-sans">Proporsi PPN Dikembalikan:</span>
                            <span x-text="'+Rp ' + formatRupiah(calculateReturnTax())"></span>
                        </div>
                    </template>
                    <div class="flex justify-between items-center text-sm font-extrabold text-slate-900 pt-1.5 border-t border-slate-300">
                        <span class="font-sans">Total Nilai Pengembalian:</span>
                        <span class="text-rose-700 font-mono text-base" x-text="'Rp ' + formatRupiah(calculateReturnGrandTotal())"></span>
                    </div>
                </div>

            </div>

            <!-- Footer Modal Retur -->
            <div class="bg-slate-100 px-4 py-2.5 border-t border-slate-200 flex items-center justify-between">
                <button type="button"
                        :disabled="isSubmittingReturn"
                        @click="returnModal = false"
                        class="px-3.5 py-1.5 bg-white border border-slate-300 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                    Batal
                </button>
                <button type="button"
                        @click="submitSalesReturn()"
                        :disabled="isSubmittingReturn || calculateReturnGrandTotal() <= 0"
                        class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-lg shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                    <span x-show="!isSubmittingReturn">💾 Proses &amp; Simpan Retur Penjualan</span>
                    <span x-show="isSubmittingReturn">Memproses Transaksi...</span>
                </button>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function salesTransactionsApp() {
    return {
        detailModal: false,
        returnModal: false,
        currentTx: null,
        currentReturnTx: null,
        isPrivilegedUser: {{ in_array(auth()->user()?->role, ['owner', 'manager', 'store_head']) ? 'true' : 'false' }},
        isSubmittingReturn: false,
        returnRefundMethod: 'CASH',
        returnReason: '',
        managerPin: '',
        returnItems: [],

        async openDetailModal(id) {
            try {
                const res = await fetch('/sales/transactions/' + id);
                const data = await res.json();
                if (data.success) {
                    this.currentTx = data.transaction;
                    this.detailModal = true;
                } else {
                    alert('Gagal memuat detail transaksi.');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memuat data transaksi.');
            }
        },

        async openReturnModal(id) {
            try {
                const res = await fetch('/sales/transactions/' + id);
                const data = await res.json();
                if (!data.success) {
                    alert('Gagal memuat data transaksi untuk retur.');
                    return;
                }

                const tx = data.transaction;
                if (tx.status === 'VOID') {
                    alert('Transaksi ini sudah berstatus VOID / Dibatalkan.');
                    return;
                }

                this.currentReturnTx = tx;
                this.returnReason = '';
                this.managerPin = '';
                this.returnRefundMethod = (tx.payment_method === 'TEMPO') ? 'CREDIT_REDUCTION' : 'CASH';

                // Map items
                this.returnItems = (tx.items || []).map(it => {
                    const maxRet = parseFloat(it.max_returnable !== undefined ? it.max_returnable : it.quantity) || 0;
                    return {
                        id: it.id,
                        name: it.product ? it.product.name : 'Unknown Product',
                        sku: it.product ? it.product.sku : '-',
                        unit_code: it.product_unit ? it.product_unit.unit_code : 'PCS',
                        unit_price: parseFloat(it.unit_price) || 0,
                        quantity: parseFloat(it.quantity) || 0,
                        already_returned_qty: parseFloat(it.already_returned_qty) || 0,
                        max_returnable: maxRet,
                        qty_to_return: maxRet > 0 ? 1 : 0,
                        condition: 'GOOD',
                        selected: maxRet > 0
                    };
                });

                this.returnModal = true;
            } catch (e) {
                alert('Terjadi kesalahan koneksi saat memuat data transaksi retur.');
            }
        },

        calculateReturnSubtotal() {
            return this.returnItems.reduce((acc, item) => {
                if (item.selected && item.qty_to_return > 0) {
                    return acc + (item.qty_to_return * item.unit_price);
                }
                return acc;
            }, 0);
        },

        calculateReturnTax() {
            if (!this.currentReturnTx) return 0;
            const subtotalTx = parseFloat(this.currentReturnTx.subtotal) || 0;
            const taxTx = parseFloat(this.currentReturnTx.tax_amount) || 0;
            if (subtotalTx <= 0 || taxTx <= 0) return 0;

            const subtotalRet = this.calculateReturnSubtotal();
            return Math.round((subtotalRet / subtotalTx) * taxTx);
        },

        calculateReturnGrandTotal() {
            return this.calculateReturnSubtotal() + this.calculateReturnTax();
        },

        async submitSalesReturn() {
            const selected = this.returnItems.filter(i => i.selected && i.qty_to_return > 0);
            if (selected.length === 0) {
                alert('Pilih minimal satu barang yang akan diretur.');
                return;
            }

            for (let it of selected) {
                if (it.qty_to_return > it.max_returnable) {
                    alert('Kuantitas retur untuk [' + it.name + '] melebihi sisa yang dapat diretur (' + it.max_returnable + ').');
                    return;
                }
            }

            if (!this.returnReason.trim()) {
                alert('Harap isi alasan pengembalian barang.');
                return;
            }

            if (!this.isPrivilegedUser && !this.managerPin.trim()) {
                alert('PIN Manajer wajib diisi untuk mengesahkan retur transaksi.');
                return;
            }

            const total = this.calculateReturnGrandTotal();
            if (!confirm('Apakah Anda yakin ingin memproses retur penjualan senilai Rp ' + this.formatRupiah(total) + '?')) {
                return;
            }

            this.isSubmittingReturn = true;

            const payload = {
                refund_method: this.returnRefundMethod,
                reason: this.returnReason.trim(),
                manager_pin: this.managerPin || null,
                items: selected.map(i => ({
                    pos_transaction_item_id: i.id,
                    quantity: i.qty_to_return,
                    condition: i.condition
                }))
            };

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/pos/transactions/' + this.currentReturnTx.id + '/return', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ ' + data.message);
                    this.returnModal = false;
                    window.location.reload();
                } else {
                    alert('❌ Gagal: ' + (data.message || 'Terjadi kesalahan pada sistem.'));
                    this.isSubmittingReturn = false;
                }
            } catch (e) {
                alert('❌ Terjadi kesalahan jaringan saat memproses retur.');
                this.isSubmittingReturn = false;
            }
        },

        formatRupiah(val) {
            let num = parseFloat(val) || 0;
            return Math.round(num).toLocaleString('id-ID');
        },

        formatDateTime(str) {
            if (!str) return '-';
            const d = new Date(str);
            return d.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        }
    }
}
</script>
@endpush
@endsection
