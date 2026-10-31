@extends('layouts.app')

@section('title', 'Daftar Retur Pembelian ke Supplier')

@section('content')
<div class="space-y-4" x-data="purchaseReturnsApp()">

    <!-- HEADER TITLE & ACTIONS -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-2xl">🔄</span>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Daftar Retur Pembelian</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                    Dua Jalur: Faktur &amp; Fleksibel
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Kelola pengembalian barang ke supplier, baik berbasis faktur pembelian maupun retur fleksibel langsung ke vendor.
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()?->role, ['owner', 'manager', 'purchasing']))
                <div class="relative" x-data="{ openDropdown: false }" @click.outside="openDropdown = false">
                    <button type="button"
                            @click="openDropdown = !openDropdown"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-extrabold rounded-lg shadow-sm text-xs transition-all active:scale-95 cursor-pointer">
                        <span>➕ Buat Retur Pembelian</span>
                        <svg class="w-3.5 h-3.5 transition-transform" :class="openDropdown ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <!-- Dropdown Dua Pilihan Retur -->
                    <div x-show="openDropdown"
                         x-cloak
                         x-transition
                         class="absolute right-0 mt-2 w-64 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-xl z-30 p-1.5 space-y-1 text-xs">
                        <a href="{{ route('purchases.index') }}"
                           class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/60 transition-colors">
                            <span class="text-base">🧾</span>
                            <div>
                                <div class="font-bold text-slate-800 dark:text-slate-100">1. Dari Faktur Pembelian</div>
                                <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Pilih faktur penerimaan barang yang sudah tercatat.</div>
                            </div>
                        </a>
                        <a href="{{ route('purchases.returns.create-flexible') }}"
                           class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/60 transition-colors">
                            <span class="text-base">📦</span>
                            <div>
                                <div class="font-bold text-amber-700 dark:text-amber-400">2. Retur Fleksibel ke Supplier</div>
                                <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Retur langsung ke vendor tanpa referensi faktur asal.</div>
                            </div>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white dark:bg-slate-900 p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs">
        <form method="GET" action="{{ route('purchases.returns.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            <!-- Search Keyword -->
            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Cari Kata Kunci:</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="No. Retur, Faktur, Alasan..."
                           class="w-full pl-8 pr-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>
            </div>

            <!-- Jenis Retur -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Jenis Retur:</label>
                <select name="return_type" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="ALL">Semua Jenis Retur</option>
                    <option value="INVOICE_BASED" {{ request('return_type') === 'INVOICE_BASED' ? 'selected' : '' }}>🧾 Dari Faktur</option>
                    <option value="FLEXIBLE" {{ request('return_type') === 'FLEXIBLE' ? 'selected' : '' }}>📦 Fleksibel (Manual)</option>
                </select>
            </div>

            <!-- Supplier -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Supplier Tujuan:</label>
                <select name="supplier_id" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Supplier</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') === $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Status Dokumen:</label>
                <select name="status" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="ALL">Semua Status</option>
                    <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>✅ COMPLETED</option>
                    <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>⏳ DRAFT (Menunggu)</option>
                    <option value="CANCELLED" {{ request('status') === 'CANCELLED' ? 'selected' : '' }}>❌ CANCELLED</option>
                </select>
            </div>

            <!-- Refund Type -->
            <div class="sm:col-span-2">
                <label class="block font-bold text-slate-600 dark:text-slate-400 mb-1">Penyelesaian:</label>
                <select name="refund_type" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="ALL">Semua Metode</option>
                    <option value="DEBT_REDUCTION" {{ request('refund_type') === 'DEBT_REDUCTION' ? 'selected' : '' }}>Potong Hutang</option>
                    <option value="CASH" {{ request('refund_type') === 'CASH' ? 'selected' : '' }}>Kas Tunai</option>
                    <option value="TRANSFER" {{ request('refund_type') === 'TRANSFER' ? 'selected' : '' }}>Transfer Bank</option>
                    <option value="SUPPLIER_CLAIM" {{ request('refund_type') === 'SUPPLIER_CLAIM' ? 'selected' : '' }}>Klaim Supplier</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="sm:col-span-1 flex items-end gap-1">
                <button type="submit"
                        class="w-full py-1.5 px-3 bg-blue-700 hover:bg-blue-800 text-white font-bold rounded-lg text-xs transition-colors cursor-pointer text-center">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'return_type', 'supplier_id', 'status', 'refund_type', 'start_date', 'end_date']))
                    <a href="{{ route('purchases.returns.index') }}"
                       class="py-1.5 px-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 text-slate-700 dark:text-slate-200 font-bold rounded-lg text-xs transition-colors text-center"
                       title="Reset Filter">✕</a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABLE DAFTAR RETUR PEMBELIAN -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <th class="py-2.5 px-3 w-8 text-center">#</th>
                        <th class="py-2.5 px-3">No. Dokumen &amp; Tanggal</th>
                        <th class="py-2.5 px-3">Jenis Retur</th>
                        <th class="py-2.5 px-3">Faktur Asal</th>
                        <th class="py-2.5 px-3">Supplier &amp; Gudang</th>
                        <th class="py-2.5 px-3 text-right">Nilai Retur</th>
                        <th class="py-2.5 px-3 text-center">Penyelesaian</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-3">Audit Trail</th>
                        <th class="py-2.5 px-3 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                    @forelse($returns as $idx => $ret)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-3 text-center text-slate-400 font-mono">
                                {{ $returns->firstItem() + $idx }}
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-blue-700 dark:text-blue-400 font-mono">{{ $ret->return_number }}</div>
                                <div class="text-[10.5px] text-slate-500 dark:text-slate-400">
                                    {{ $ret->return_date ? $ret->return_date->format('d M Y') : '-' }}
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                @if($ret->return_type === 'FLEXIBLE')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                        <span>📦</span>
                                        <span>Fleksibel</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                        <span>🧾</span>
                                        <span>Dari Faktur</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @if($ret->purchase)
                                    <a href="{{ route('purchases.show', $ret->purchase_id) }}"
                                       class="font-mono font-bold text-slate-800 dark:text-slate-200 hover:text-blue-600 hover:underline flex items-center gap-1"
                                       title="Lihat Faktur Pembelian Asal">
                                        <span>🧾</span>
                                        <span>{{ $ret->purchase->reference_number }}</span>
                                    </a>
                                    <div class="text-[10.5px] text-slate-500 font-mono">
                                        No. Faktur: {{ $ret->purchase->invoice_number ?: '-' }}
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Non-Faktur (Manual)</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900 dark:text-slate-100">{{ $ret->supplier?->name ?? 'Unknown Supplier' }}</div>
                                <div class="text-[10.5px] text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <span>🏪</span>
                                    <span>{{ $ret->store?->name ?? 'Gudang Pusat' }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="font-mono font-extrabold text-slate-900 dark:text-slate-100 text-xs">
                                    Rp {{ number_format((float) $ret->grand_total, 0, ',', '.') }}
                                </div>
                                @if((float) $ret->tax_amount > 0)
                                    <div class="text-[10px] text-slate-500 font-mono">
                                        (PPN: Rp {{ number_format((float) $ret->tax_amount, 0, ',', '.') }})
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($ret->refund_type === 'DEBT_REDUCTION')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                        <span>📝</span>
                                        <span>Potong Hutang</span>
                                    </span>
                                @elseif($ret->refund_type === 'CASH')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                        <span>💵</span>
                                        <span>Kas Tunai</span>
                                    </span>
                                @elseif($ret->refund_type === 'SUPPLIER_CLAIM')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-300 dark:border-purple-700">
                                        <span>🛡️</span>
                                        <span>Klaim Supplier</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300 dark:border-blue-700">
                                        <span>🏦</span>
                                        <span>Transfer</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($ret->status === 'COMPLETED')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                        ✅ COMPLETED
                                    </span>
                                @elseif($ret->status === 'DRAFT')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 animate-pulse">
                                        ⏳ DRAFT
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300">
                                        ❌ {{ $ret->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <div class="text-[11px] text-slate-700 dark:text-slate-300">
                                    Dibuat: <span class="font-medium">{{ $ret->creator?->name ?? 'System' }}</span>
                                </div>
                                @if($ret->approver)
                                    <div class="text-[10px] text-emerald-700 dark:text-emerald-400 font-semibold">
                                        Disetujui: {{ $ret->approver->name }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button"
                                            @click="openDetail('{{ $ret->id }}')"
                                            class="px-2 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold rounded text-[11px] border border-slate-300 dark:border-slate-700 shadow-2xs transition-all cursor-pointer">
                                        🔍 Detail
                                    </button>

                                    <!-- Tombol Cetak Nota Retur -->
                                    <a href="{{ route('purchases.returns.print', $ret->id) }}"
                                       target="_blank"
                                       class="px-2 py-1 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/40 dark:hover:bg-blue-800/60 text-blue-700 dark:text-blue-300 font-bold rounded text-[11px] border border-blue-200 dark:border-blue-700 shadow-2xs transition-all flex items-center gap-1"
                                       title="Cetak Dokumen Nota Retur / Surat Jalan">
                                        <span>🖨️</span>
                                        <span>Cetak</span>
                                    </a>

                                    <!-- Tombol Otorisasi jika berstatus DRAFT -->
                                    @if($ret->status === 'DRAFT')
                                        @if(in_array(auth()->user()?->role, ['owner', 'manager']))
                                            <form action="{{ route('purchases.returns.approve', $ret->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dokumen retur ini? Stok fisik akan langsung dipotong.')" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-[11px] shadow-2xs cursor-pointer" title="Setujui &amp; Potong Stok">
                                                    ✓
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('purchases.returns.cancel', $ret->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan dokumen retur DRAFT ini?')" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2 py-1 bg-rose-100 hover:bg-rose-200 text-rose-700 font-bold rounded text-[11px] border border-rose-300 shadow-2xs cursor-pointer" title="Batalkan Dokumen">
                                                ✕
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                                Belum ada data retur pembelian yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returns->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40">
                {{ $returns->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL DETAIL RETUR PEMBELIAN -->
    <div x-show="detailModal"
         x-cloak
         @keydown.escape.window="detailModal = false"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl max-w-2xl w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 dark:text-slate-200 text-xs animate-in fade-in zoom-in-95 duration-100">

            <!-- Header Modal -->
            <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🔄</span>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">
                            Detail Retur: <span class="font-mono text-amber-300" x-text="currentReturn?.return_number"></span>
                        </h3>
                        <p class="text-[10px] text-slate-400" x-text="'Tanggal: ' + (currentReturn?.return_date || '-') + ' | Status: ' + (currentReturn?.status || '-')"></p>
                    </div>
                </div>
                <button type="button"
                        @click="detailModal = false"
                        class="text-slate-400 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- Body Modal -->
            <div class="p-4 space-y-3.5 max-h-[75vh] overflow-y-auto">
                <!-- Info Supplier & Jalur Retur -->
                <div class="grid grid-cols-2 gap-2 p-2.5 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 rounded-lg text-[11.5px]">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Supplier Tujuan:</span>
                        <strong class="text-slate-900 dark:text-slate-100 text-sm" x-text="currentReturn?.supplier?.name"></strong>
                        <div class="text-[10px] text-slate-500" x-text="'Gudang Asal: ' + (currentReturn?.store?.name || '-')"></div>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 block text-[10px]">Jalur &amp; Referensi Dokumen:</span>
                        <template x-if="currentReturn?.purchase">
                            <div>
                                <a :href="'/purchases/' + currentReturn?.purchase_id"
                                   class="font-mono font-bold text-blue-600 dark:text-blue-400 hover:underline block"
                                   x-text="'Faktur: ' + currentReturn?.purchase?.reference_number"></a>
                                <span class="text-[10px] text-slate-500" x-text="'No. Inv: ' + (currentReturn?.purchase?.invoice_number || '-')"></span>
                            </div>
                        </template>
                        <template x-if="!currentReturn?.purchase">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                📦 Retur Fleksibel ke Supplier (Non-Faktur)
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Info Penyelesaian & Audit Trail -->
                <div class="p-2.5 bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40 rounded-lg text-[11.5px] space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Metode Penyelesaian:</span>
                        <strong class="font-bold text-slate-800 dark:text-slate-200" x-text="currentReturn?.refund_type"></strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600 dark:text-slate-400">Alasan Pengembalian:</span>
                        <span class="italic text-slate-700 dark:text-slate-300" x-text="currentReturn?.reason || '-'"></span>
                    </div>
                    <template x-if="currentReturn?.notes">
                        <div class="flex justify-between">
                            <span class="text-slate-600 dark:text-slate-400">Catatan Tambahan:</span>
                            <span class="text-slate-700 dark:text-slate-300" x-text="currentReturn?.notes"></span>
                        </div>
                    </template>
                    <div class="flex justify-between pt-1 border-t border-blue-200/60 dark:border-blue-900/30">
                        <span class="text-slate-600 dark:text-slate-400">Dibuat Oleh:</span>
                        <span class="text-slate-700 dark:text-slate-300 font-medium" x-text="currentReturn?.creator?.name || 'System'"></span>
                    </div>
                    <template x-if="currentReturn?.approver">
                        <div class="flex justify-between">
                            <span class="text-emerald-700 dark:text-emerald-400 font-semibold">Disetujui Oleh:</span>
                            <span class="text-emerald-800 dark:text-emerald-300 font-bold" x-text="currentReturn?.approver?.name + ' (' + (currentReturn?.approved_at || '') + ')'"></span>
                        </div>
                    </template>
                </div>

                <!-- Tabel Item Retur -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs divide-y divide-slate-200 dark:divide-slate-700">
                        <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2">Nama Barang &amp; SKU</th>
                                <th class="px-2 py-2 text-center">Satuan</th>
                                <th class="px-2 py-2 text-center">Kondisi</th>
                                <th class="px-2 py-2 text-right">Qty</th>
                                <th class="px-3 py-2 text-right">Nilai Satuan</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-[11px]">
                            <template x-for="item in currentReturn?.items" :key="item.id">
                                <tr>
                                    <td class="px-3 py-2 font-sans font-semibold text-slate-900 dark:text-slate-100" x-text="item.product?.name"></td>
                                    <td class="px-2 py-2 text-center text-slate-500 font-sans" x-text="item.unit?.unit_code"></td>
                                    <td class="px-2 py-2 text-center">
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                              :class="item.condition === 'DAMAGED' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'"
                                              x-text="item.condition === 'DAMAGED' ? '⚠️ Rusak' : '✅ Layak'"></span>
                                    </td>
                                    <td class="px-2 py-2 text-right font-bold text-rose-600 dark:text-rose-400" x-text="parseFloat(item.quantity)"></td>
                                    <td class="px-3 py-2 text-right" x-text="'Rp ' + formatRupiah(item.unit_price)"></td>
                                    <td class="px-3 py-2 text-right font-bold text-slate-900 dark:text-slate-100" x-text="'Rp ' + formatRupiah(item.subtotal)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Total Summary -->
                <div class="p-3 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg space-y-1 font-mono text-xs">
                    <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
                        <span class="font-sans">Subtotal Nilai Barang:</span>
                        <span x-text="'Rp ' + formatRupiah(currentReturn?.subtotal)"></span>
                    </div>
                    <template x-if="parseFloat(currentReturn?.tax_amount) > 0">
                        <div class="flex justify-between items-center text-blue-700 dark:text-blue-400">
                            <span class="font-sans">PPN Masukan Disesuaikan:</span>
                            <span x-text="'+Rp ' + formatRupiah(currentReturn?.tax_amount)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between items-center text-sm font-extrabold text-slate-900 dark:text-slate-100 pt-1.5 border-t border-slate-200 dark:border-slate-700">
                        <span class="font-sans">Total Nilai Retur:</span>
                        <span class="text-emerald-700 dark:text-emerald-400 font-mono text-base" x-text="'Rp ' + formatRupiah(currentReturn?.grand_total)"></span>
                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="bg-slate-100 dark:bg-slate-800/80 px-4 py-2.5 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <button type="button"
                        @click="detailModal = false"
                        class="px-3.5 py-1.5 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                    Tutup (ESC)
                </button>
                <a :href="'/purchases/returns/' + currentReturn?.id + '/print'"
                   target="_blank"
                   class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <span>🖨️ Cetak Nota Retur</span>
                </a>
                <template x-if="currentReturn?.purchase">
                    <a :href="'/purchases/' + currentReturn?.purchase_id"
                       class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded font-bold text-xs flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <span>🧾 Buka Faktur Pembelian Asal</span>
                    </a>
                </template>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function purchaseReturnsApp() {
    return {
        detailModal: false,
        currentReturn: null,

        async openDetail(id) {
            try {
                const res = await fetch('/purchases/returns/' + id);
                const data = await res.json();
                if (data.success) {
                    this.currentReturn = data.return;
                    this.detailModal = true;
                } else {
                    alert('Gagal memuat detail data retur pembelian.');
                }
            } catch (e) {
                alert('Terjadi kesalahan saat memuat rincian retur pembelian.');
            }
        },

        formatRupiah(val) {
            let num = parseFloat(val) || 0;
            return Math.round(num).toLocaleString('id-ID');
        }
    }
}
</script>
@endpush
@endsection
