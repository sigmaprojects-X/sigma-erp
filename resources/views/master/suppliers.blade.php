@extends('layouts.app')

@section('title', 'Master Data Supplier')
@section('tab_title', 'Daftar Supplier')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full"
     x-data="{
         createModal: false,
         editModal: false,
         editSup: {
             id: '',
             code: '',
             name: '',
             contact_person: '',
             phone: '',
             email: '',
             term_of_payment_days: 0,
             bank_name: '',
             bank_account_no: '',
             bank_account_holder: '',
             address: ''
         }
     }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Supplier / Pemasok</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏭 Master Data Supplier & Rekening Pemasok</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Supplier Baru</span>
            </button>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('master.suppliers') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            <div class="sm:col-span-8">
                <label class="block font-bold text-slate-600 mb-1">Cari Supplier / Kontak / Kota:</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ketik nama PT / Distributor, nama sales, atau telepon..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                </div>
            </div>
            <div class="sm:col-span-4 flex items-end gap-2">
                <button type="submit"
                        class="flex-1 py-1.5 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <span>Cari Data</span>
                </button>
                @if(request('search'))
                    <a href="{{ route('master.suppliers') }}"
                       class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL SUPPLIER -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3 w-28">Kode</th>
                        <th class="py-2.5 px-3">Nama Supplier / PT</th>
                        <th class="py-2.5 px-3">Kontak Person & Telepon</th>
                        <th class="py-2.5 px-3">Termin (TOP)</th>
                        <th class="py-2.5 px-3">Rekening Bank Pemasok</th>
                        <th class="py-2.5 px-3 w-20 text-center">Status</th>
                        <th class="py-2.5 px-3 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($suppliers as $index => $sup)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                {{ $suppliers->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-indigo-700">
                                {{ $sup->code }}
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ $sup->name }}</span>
                                    @if($sup->is_pkp)
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-black bg-blue-100 text-blue-800 border border-blue-200" title="Pengusaha Kena Pajak">
                                            PKP
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[9.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200" title="Bukan Pengusaha Kena Pajak">
                                            Non-PKP
                                        </span>
                                    @endif
                                </div>
                                @if($sup->npwp)
                                    <div class="text-[10px] text-slate-500 font-mono">NPWP: {{ $sup->npwp }}</div>
                                @endif
                                <div class="text-[10.5px] font-normal text-slate-500 truncate max-w-xs">{{ $sup->address ?: '-' }}</div>
                            </td>
                            <td class="py-2.5 px-3">
                                <div class="font-bold text-slate-800">{{ $sup->contact_person ?: '-' }}</div>
                                <div class="text-slate-500 font-mono text-[11px]">{{ $sup->phone ?: '-' }}</div>
                                @if($sup->email)
                                    <div class="text-[10.5px] text-indigo-600">{{ $sup->email }}</div>
                                @endif
                            </td>
                            <td class="py-2.5 px-3">
                                @if($sup->term_of_payment_days > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ $sup->term_of_payment_days }} Hari
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Tunai (Cash)
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-[11px]">
                                @if($sup->bank_account_no)
                                    <div class="font-bold text-slate-800 font-mono">
                                        <span class="text-blue-700">{{ $sup->bank_name ?: 'BANK' }}</span>: {{ $sup->bank_account_no }}
                                    </div>
                                    <div class="text-slate-500 text-[10.5px] truncate max-w-xs">a.n {{ $sup->bank_account_holder ?: '-' }}</div>
                                @else
                                    <span class="text-slate-400 italic">Belum disetel</span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold {{ $sup->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $sup->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="editSup = {
                                                id: '{{ $sup->id }}',
                                                code: '{{ addslashes($sup->code) }}',
                                                name: '{{ addslashes($sup->name) }}',
                                                is_pkp: {{ $sup->is_pkp ? 'true' : 'false' }},
                                                npwp: '{{ addslashes($sup->npwp ?? '') }}',
                                                tax_name: '{{ addslashes($sup->tax_name ?? '') }}',
                                                contact_person: '{{ addslashes($sup->contact_person ?? '') }}',
                                                phone: '{{ addslashes($sup->phone ?? '') }}',
                                                email: '{{ addslashes($sup->email ?? '') }}',
                                                term_of_payment_days: {{ (int)$sup->term_of_payment_days }},
                                                bank_name: '{{ addslashes($sup->bank_name ?? '') }}',
                                                bank_account_no: '{{ addslashes($sup->bank_account_no ?? '') }}',
                                                bank_account_holder: '{{ addslashes($sup->bank_account_holder ?? '') }}',
                                                address: '{{ addslashes($sup->address ?? '') }}'
                                            }; editModal = true;"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-md font-bold text-[10.5px] transition-colors cursor-pointer"
                                            title="Edit Supplier">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('master.suppliers.toggle-status', $sup->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status supplier ini?');" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 {{ $sup->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }} rounded-md font-bold text-[10px] transition-colors cursor-pointer"
                                                title="{{ $sup->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $sup->is_active ? 'Off' : 'On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                🏭 Belum ada data supplier yang tersimpan. Klik <strong>Tambah Supplier Baru</strong> untuk memasukkan data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH SUPPLIER BARU -->
    <div x-show="createModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden"
             @click.away="createModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-indigo-700 to-indigo-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏭</span>
                    <div>
                        <h3 class="text-sm font-black">Tambah Master Supplier Baru</h3>
                        <p class="text-[11px] text-indigo-200">Daftarkan rekanan distributor / pabrikan pemasok barang</p>
                    </div>
                </div>
                <button type="button" @click="createModal = false" class="text-indigo-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('master.suppliers.store') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Kode Supplier: <span class="text-xs text-indigo-600 font-normal">(Otomatis / Bisa Diedit)</span> <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="code"
                               value="{{ $nextSupplierCode ?? '' }}"
                               placeholder="Contoh: SUP-0001"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Supplier / PT: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="name"
                               placeholder="PT / CV / Toko Distributor"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kontak Person / Salesman:</label>
                        <input type="text"
                               name="contact_person"
                               placeholder="Nama PIC Sales Distributor"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">No. Telepon / WhatsApp:</label>
                        <input type="text"
                               name="phone"
                               placeholder="08xxxxxxxxxx / 021-xxxx"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Supplier:</label>
                        <input type="email"
                               name="email"
                               placeholder="order@distributor.com"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Termin Pembayaran (Hari Tempo / TOP):</label>
                        <div class="relative">
                            <input type="number"
                                   name="term_of_payment_days"
                                   value="0"
                                   min="0"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg font-bold font-mono focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                            <span class="absolute right-3 top-2 text-slate-400 font-semibold">Hari (0 = Cash)</span>
                        </div>
                    </div>
                </div>

                <!-- STATUS PERPAJAKAN SUPPLIER (PKP / NON-PKP) -->
                <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl space-y-2.5" x-data="{ isPkp: false }">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <h4 class="font-bold text-blue-950 text-[11px] flex items-center gap-1.5">
                            <span>🏛️</span>
                            <span>Status Perpajakan Pemasok (PKP / Non-PKP)</span>
                        </h4>
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer font-bold text-xs text-slate-700">
                                <input type="radio" name="is_pkp" value="0" :checked="!isPkp" @change="isPkp = false" class="text-blue-600">
                                <span>Non-PKP (Tanpa Pajak)</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer font-bold text-xs text-blue-700">
                                <input type="radio" name="is_pkp" value="1" :checked="isPkp" @change="isPkp = true" class="text-blue-600">
                                <span>PKP (Kena PPN)</span>
                            </label>
                        </div>
                    </div>
                    <div x-show="isPkp" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1.5 border-t border-blue-100">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-0.5 text-[10.5px]">Nomor Pokok Wajib Pajak (NPWP / NIK):</label>
                            <input type="text"
                                   name="npwp"
                                   placeholder="Contoh: 01.234.567.8-123.000"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-0.5 text-[10.5px]">Nama Resmi di Faktur Pajak:</label>
                            <input type="text"
                                   name="tax_name"
                                   placeholder="Nama PT / CV sesuai dokumen pajak"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-medium focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                    </div>
                </div>

                <!-- REKENING PEMBAYARAN SUPPLIER -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                    <h4 class="font-bold text-slate-800 text-[11px] flex items-center gap-1.5">
                        <span>💳</span>
                        <span>Informasi Rekening Bank Tujuan Transfer Supplier</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5 text-[10.5px]">Nama Bank:</label>
                            <input type="text"
                                   name="bank_name"
                                   placeholder="BCA, Mandiri, BRI"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5 text-[10.5px]">Nomor Rekening:</label>
                            <input type="text"
                                   name="bank_account_no"
                                   placeholder="Nomor rekening transfer"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5 text-[10.5px]">Atas Nama (A.N):</label>
                            <input type="text"
                                   name="bank_account_holder"
                                   placeholder="Nama pemilik rekening"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Kantor / Gudang Supplier:</label>
                    <textarea name="address"
                              rows="2"
                              placeholder="Alamat lengkap distributor / pabrik..."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Data Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT SUPPLIER -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl overflow-hidden"
             @click.away="editModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="text-sm font-black">Edit Master Supplier</h3>
                        <p class="text-[11px] text-amber-200">Perbarui kontak, termin hutang, dan nomor rekening distributor</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-amber-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('master/suppliers') }}/' + editSup.id" method="POST" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Supplier: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               x-model="editSup.code"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Supplier / PT: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="name"
                               x-model="editSup.name"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kontak Person / Salesman:</label>
                        <input type="text"
                               name="contact_person"
                               x-model="editSup.contact_person"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">No. Telepon / WhatsApp:</label>
                        <input type="text"
                               name="phone"
                               x-model="editSup.phone"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email Supplier:</label>
                        <input type="email"
                               name="email"
                               x-model="editSup.email"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Termin Pembayaran (Hari Tempo / TOP):</label>
                        <div class="relative">
                            <input type="number"
                                   name="term_of_payment_days"
                                   x-model="editSup.term_of_payment_days"
                                   min="0"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg font-bold font-mono focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                            <span class="absolute right-3 top-2 text-slate-400 font-semibold">Hari (0 = Cash)</span>
                        </div>
                    </div>
                </div>

                <!-- STATUS PERPAJAKAN SUPPLIER (PKP / NON-PKP) EDIT -->
                <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl space-y-2.5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <h4 class="font-bold text-blue-950 text-[11px] flex items-center gap-1.5">
                            <span>🏛️</span>
                            <span>Status Perpajakan Pemasok (PKP / Non-PKP)</span>
                        </h4>
                        <div class="flex items-center gap-4">
                            <label class="inline-flex items-center gap-1.5 cursor-pointer font-bold text-xs text-slate-700">
                                <input type="radio" name="is_pkp" value="0" :checked="!editSup.is_pkp" @change="editSup.is_pkp = false" class="text-blue-600">
                                <span>Non-PKP (Tanpa Pajak)</span>
                            </label>
                            <label class="inline-flex items-center gap-1.5 cursor-pointer font-bold text-xs text-blue-700">
                                <input type="radio" name="is_pkp" value="1" :checked="editSup.is_pkp" @change="editSup.is_pkp = true" class="text-blue-600">
                                <span>PKP (Kena PPN)</span>
                            </label>
                        </div>
                    </div>
                    <div x-show="editSup.is_pkp" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1.5 border-t border-blue-100">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-0.5 text-[10.5px]">Nomor Pokok Wajib Pajak (NPWP / NIK):</label>
                            <input type="text"
                                   name="npwp"
                                   x-model="editSup.npwp"
                                   placeholder="Contoh: 01.234.567.8-123.000"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-0.5 text-[10.5px]">Nama Resmi di Faktur Pajak:</label>
                            <input type="text"
                                   name="tax_name"
                                   x-model="editSup.tax_name"
                                   placeholder="Nama PT / CV sesuai dokumen pajak"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-medium focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                    </div>
                </div>

                <!-- REKENING PEMBAYARAN SUPPLIER -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2.5">
                    <h4 class="font-bold text-slate-800 text-[11px] flex items-center gap-1.5">
                        <span>💳</span>
                        <span>Informasi Rekening Bank Tujuan Transfer Supplier</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5 text-[10.5px]">Nama Bank:</label>
                            <input type="text"
                                   name="bank_name"
                                   x-model="editSup.bank_name"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5 text-[10.5px]">Nomor Rekening:</label>
                            <input type="text"
                                   name="bank_account_no"
                                   x-model="editSup.bank_account_no"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-600 mb-0.5 text-[10.5px]">Atas Nama (A.N):</label>
                            <input type="text"
                                   name="bank_account_holder"
                                   x-model="editSup.bank_account_holder"
                                   class="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Kantor / Gudang Supplier:</label>
                    <textarea name="address"
                              rows="2"
                              x-model="editSup.address"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="editModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
