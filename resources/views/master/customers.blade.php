@extends('layouts.app')

@section('title', 'Master Data Pelanggan & Plafon Kredit')
@section('tab_title', 'Master Pelanggan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full"
     x-data="{
         createModal: false,
         editModal: false,
         editCust: {
             id: '',
             code: '',
             name: '',
             customer_tier_id: '',
             phone: '',
             credit_term_days: 14,
             address: '',
             credit_limit: 0
         }
     }">

    <!-- HEADER & ACTION -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Pelanggan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>👥 Master Data Pelanggan & Kredit Tempo (Piutang Usaha)</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Pelanggan Baru</span>
            </button>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('master.customers') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            <div class="sm:col-span-8">
                <label class="block font-bold text-slate-600 mb-1">Cari Pelanggan / Telepon / Alamat:</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ketik kode, nama pelanggan, toko, atau nomor HP..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>
            </div>
            <div class="sm:col-span-4 flex items-end gap-2">
                <button type="submit"
                        class="flex-1 py-1.5 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <span>Cari Data</span>
                </button>
                @if(request('search'))
                    <a href="{{ route('master.customers') }}"
                       class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL PELANGGAN -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 divide-y divide-slate-200">
                <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10.5px]">
                    <tr>
                        <th class="px-3.5 py-3">Kode</th>
                        <th class="px-3 py-3">Nama Pelanggan</th>
                        <th class="px-3 py-3">Level Tier</th>
                        <th class="px-3 py-3">No. Telepon & Alamat</th>
                        <th class="px-3 py-3 text-right">Plafon Kredit (Limit)</th>
                        <th class="px-3 py-3 text-right">Saldo Piutang Berjalan</th>
                        <th class="px-3 py-3 text-right">Sisa Plafon Tersedia</th>
                        <th class="px-3 py-3 text-center">Tempo (Hari)</th>
                        <th class="px-3.5 py-3 text-center">Status</th>
                        <th class="px-3.5 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono text-[11.5px]">
                    @forelse($customers as $c)
                        @php
                            $avail = (float)$c->credit_limit - (float)$c->current_credit_balance;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3.5 py-2.5 font-bold text-slate-900">
                                {{ $c->code }}
                            </td>
                            <td class="px-3 py-2.5 font-sans font-bold text-slate-900">
                                {{ $c->name }}
                            </td>
                            <td class="px-3 py-2.5 font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $c->customerTier?->code === 'GROSIR' ? 'bg-purple-100 text-purple-900' : 'bg-blue-100 text-blue-900' }}">
                                    {{ $c->customerTier?->name }}
                                </span>
                            </td>
                            <td class="px-3 py-2.5 font-sans text-slate-600">
                                <div>{{ $c->phone }}</div>
                                <div class="text-[10px] text-slate-400 truncate max-w-xs">{{ $c->address }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-slate-900">
                                @if((float)$c->credit_limit > 0)
                                    Rp {{ number_format((float) $c->credit_limit, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 font-normal">Tanpa Kredit</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-rose-700">
                                @if((float)$c->current_credit_balance > 0)
                                    Rp {{ number_format((float) $c->current_credit_balance, 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 font-normal">Rp 0</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-right font-bold text-emerald-700">
                                @if((float)$c->credit_limit > 0)
                                    Rp {{ number_format(max(0, $avail), 0, ',', '.') }}
                                @else
                                    <span class="text-slate-400 font-normal">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center font-sans">
                                {{ $c->credit_term_days ? $c->credit_term_days . ' hari' : '-' }}
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                @if($c->is_active)
                                    <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">Aktif</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-500 font-bold text-[10px]">Non-Aktif</span>
                                @endif
                            </td>
                            <td class="px-3.5 py-2.5 text-center font-sans">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button"
                                            @click="editCust = {
                                                id: '{{ $c->id }}',
                                                code: '{{ addslashes($c->code) }}',
                                                name: '{{ addslashes($c->name) }}',
                                                customer_tier_id: '{{ $c->customer_tier_id }}',
                                                phone: '{{ addslashes($c->phone ?? '') }}',
                                                credit_term_days: {{ (int)$c->credit_term_days }},
                                                address: '{{ addslashes($c->address ?? '') }}',
                                                credit_limit: {{ (float)$c->credit_limit }}
                                            }; editModal = true;"
                                            class="px-2 py-0.5 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-[10px] cursor-pointer"
                                            title="Edit Pelanggan">
                                        ✏️ Edit
                                    </button>
                                    <a href="{{ route('customers.receivables.ledger', $c->id) }}"
                                       class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px]" title="Kartu Piutang">
                                        Kartu AR
                                    </a>
                                    @if((float)$c->current_credit_balance > 0)
                                        <a href="{{ route('customers.receivables.pay', $c->id) }}"
                                           class="px-2 py-0.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10px]" title="Bayar Piutang">
                                            Bayar
                                        </a>
                                    @endif
                                    <form action="{{ route('master.customers.toggle-status', $c->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status pelanggan ini?');" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-1.5 py-0.5 rounded {{ $c->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }} font-bold text-[9.5px] cursor-pointer"
                                                title="{{ $c->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $c->is_active ? 'Off' : 'On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-slate-400 font-sans text-xs">
                                👥 Belum ada data pelanggan yang tersimpan. Klik <strong>Tambah Pelanggan Baru</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-200">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH PELANGGAN BARU -->
    <div x-show="createModal"
         x-cloak
         @keydown.escape.window="createModal = false"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl max-w-lg w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs">
            
            <!-- HEADER MODAL -->
            <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-blue-950 text-white px-4 py-3 flex items-center justify-between border-b border-slate-700">
                <div class="flex items-center gap-2">
                    <span class="text-xl">➕</span>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">Input Pelanggan Baru</h3>
                        <p class="text-[10px] text-slate-400">Daftarkan pelanggan grosir / toko langganan</p>
                    </div>
                </div>
                <button type="button"
                        @click="createModal = false"
                        class="text-slate-400 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('master.customers.store') }}">
                @csrf
                <div class="p-4 space-y-3 max-h-[75vh] overflow-y-auto">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Pelanggan * :</label>
                            <input type="text" name="code" required placeholder="Contoh: CUST-003" class="w-full p-2 border border-slate-300 rounded uppercase font-mono font-bold focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Level Tier Pelanggan * :</label>
                            <select name="customer_tier_id" required class="w-full p-2 border border-slate-300 rounded font-semibold bg-white focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                                @foreach($customerTiers as $tier)
                                    <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Pelanggan / Toko * :</label>
                        <input type="text" name="name" required placeholder="Contoh: Toko Barokah Jaya" class="w-full p-2 border border-slate-300 rounded font-bold text-slate-900 focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Telepon / WhatsApp :</label>
                            <input type="text" name="phone" placeholder="08..." class="w-full p-2 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tempo Kredit (Hari) :</label>
                            <input type="number" name="credit_term_days" min="0" placeholder="14" value="14" class="w-full p-2 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Toko :</label>
                        <textarea name="address" rows="2" placeholder="Alamat pengiriman / domisili pelanggan..." class="w-full p-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-200 focus:outline-hidden"></textarea>
                    </div>

                    <div class="p-3 bg-purple-50/60 border border-purple-200 rounded-lg space-y-1">
                        <label class="block font-extrabold text-purple-950 text-xs">
                            Plafon Kredit / Limit Piutang Maksimal (Rp) :
                        </label>
                        <p class="text-[10.5px] text-purple-800">
                            Batas maksimal nominal belanja tempo / kasbon yang diizinkan untuk pelanggan ini.
                        </p>
                        <input type="number" name="credit_limit" min="0" placeholder="Contoh: 10000000" value="5000000" class="w-full p-2 border border-purple-300 rounded text-right font-mono font-black text-sm text-purple-900 focus:ring-2 focus:ring-purple-200 focus:outline-hidden bg-white">
                    </div>

                </div>

                <!-- FOOTER MODAL -->
                <div class="bg-slate-100 px-4 py-3 border-t border-slate-300 flex items-center justify-between">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-white border border-slate-300 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded shadow-md cursor-pointer">
                        💾 Simpan Pelanggan
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- MODAL EDIT PELANGGAN -->
    <div x-show="editModal"
         x-cloak
         @keydown.escape.window="editModal = false"
         class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl max-w-lg w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs">
            
            <!-- HEADER MODAL -->
            <div class="bg-gradient-to-r from-amber-600 via-amber-700 to-amber-900 text-white px-4 py-3 flex items-center justify-between border-b border-amber-800">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">Edit Master Pelanggan</h3>
                        <p class="text-[10px] text-amber-200">Perbarui profil, level tier, dan plafon kredit pelanggan</p>
                    </div>
                </div>
                <button type="button"
                        @click="editModal = false"
                        class="text-amber-200 hover:text-white rounded px-2 py-0.5 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <form method="POST" :action="'{{ url('master/customers') }}/' + editCust.id">
                @csrf
                @method('PUT')
                <div class="p-4 space-y-3 max-h-[75vh] overflow-y-auto">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kode Pelanggan * :</label>
                            <input type="text" name="code" x-model="editCust.code" required class="w-full p-2 border border-slate-300 rounded uppercase font-mono font-bold focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Level Tier Pelanggan * :</label>
                            <select name="customer_tier_id" x-model="editCust.customer_tier_id" required class="w-full p-2 border border-slate-300 rounded font-semibold bg-white focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                                @foreach($customerTiers as $tier)
                                    <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Pelanggan / Toko * :</label>
                        <input type="text" name="name" x-model="editCust.name" required class="w-full p-2 border border-slate-300 rounded font-bold text-slate-900 focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Telepon / WhatsApp :</label>
                            <input type="text" name="phone" x-model="editCust.phone" class="w-full p-2 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tempo Kredit (Hari) :</label>
                            <input type="number" name="credit_term_days" x-model="editCust.credit_term_days" min="0" class="w-full p-2 border border-slate-300 rounded font-mono focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Toko :</label>
                        <textarea name="address" rows="2" x-model="editCust.address" class="w-full p-2 border border-slate-300 rounded focus:ring-2 focus:ring-amber-200 focus:outline-hidden"></textarea>
                    </div>

                    <div class="p-3 bg-purple-50/60 border border-purple-200 rounded-lg space-y-1">
                        <label class="block font-extrabold text-purple-950 text-xs">
                            Plafon Kredit / Limit Piutang Maksimal (Rp) :
                        </label>
                        <p class="text-[10.5px] text-purple-800">
                            Batas maksimal nominal belanja tempo / kasbon yang diizinkan untuk pelanggan ini.
                        </p>
                        <input type="number" name="credit_limit" x-model="editCust.credit_limit" min="0" class="w-full p-2 border border-purple-300 rounded text-right font-mono font-black text-sm text-purple-900 focus:ring-2 focus:ring-purple-200 focus:outline-hidden bg-white">
                    </div>

                </div>

                <!-- FOOTER MODAL -->
                <div class="bg-slate-100 px-4 py-3 border-t border-slate-300 flex items-center justify-between">
                    <button type="button"
                            @click="editModal = false"
                            class="px-4 py-2 bg-white border border-slate-300 rounded font-bold text-xs hover:bg-slate-200 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded shadow-md cursor-pointer">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection
