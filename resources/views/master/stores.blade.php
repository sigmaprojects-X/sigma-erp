@extends('layouts.app')

@section('title', 'Master Data Toko, Cabang & Gudang')
@section('tab_title', 'Master Toko & Gudang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full"
     x-data="{
         createModal: false,
         editModal: false,
         editStore: {
             id: '',
             code: '',
             name: '',
             type: 'STORE',
             phone: '',
             address: ''
         }
     }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Toko & Gudang</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏪 Master Cabang, Outlet Toko & Gudang Pusat</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Cabang / Gudang Baru</span>
            </button>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('master.stores') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            <div class="sm:col-span-6">
                <label class="block font-bold text-slate-600 mb-1">Cari Toko / Cabang:</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ketik kode, nama cabang, atau alamat..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>
            </div>
            <div class="sm:col-span-3">
                <label class="block font-bold text-slate-600 mb-1">Tipe Lokasi:</label>
                <select name="type" class="w-full py-1.5 px-3 border border-slate-300 rounded-lg text-xs bg-white focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                    <option value="">Semua Tipe (Toko & Gudang)</option>
                    <option value="STORE" {{ request('type') === 'STORE' ? 'selected' : '' }}>🏪 Outlet / Toko Retail</option>
                    <option value="WAREHOUSE" {{ request('type') === 'WAREHOUSE' ? 'selected' : '' }}>🏭 Gudang / Warehouse</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-end gap-2">
                <button type="submit"
                        class="flex-1 py-1.5 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <span>Cari Data</span>
                </button>
                @if(request()->hasAny(['search', 'type']))
                    <a href="{{ route('master.stores') }}"
                       class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL TOKO & GUDANG -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3 w-28">Kode</th>
                        <th class="py-2.5 px-3">Nama Cabang / Toko</th>
                        <th class="py-2.5 px-3 text-center w-28">Tipe</th>
                        <th class="py-2.5 px-3">Telepon</th>
                        <th class="py-2.5 px-3">Alamat Lengkap</th>
                        <th class="py-2.5 px-3 w-20 text-center">Status</th>
                        <th class="py-2.5 px-3 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($stores as $index => $st)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                {{ $stores->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-700">
                                {{ $st->code }}
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $st->name }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                @if($st->type === 'STORE')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        🏪 TOKO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                                        🏭 GUDANG
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-3 font-mono text-slate-600">
                                {{ $st->phone ?: '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                {{ $st->address ?: '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold {{ $st->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $st->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="editStore = {
                                                id: '{{ $st->id }}',
                                                code: '{{ addslashes($st->code) }}',
                                                name: '{{ addslashes($st->name) }}',
                                                type: '{{ $st->type }}',
                                                phone: '{{ addslashes($st->phone ?? '') }}',
                                                address: '{{ addslashes($st->address ?? '') }}'
                                            }; editModal = true;"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-md font-bold text-[10.5px] transition-colors cursor-pointer"
                                            title="Edit Cabang">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('master.stores.toggle-status', $st->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status cabang/gudang ini?');" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 {{ $st->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }} rounded-md font-bold text-[10px] transition-colors cursor-pointer"
                                                title="{{ $st->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $st->is_active ? 'Off' : 'On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 text-xs">
                                🏪 Belum ada data cabang / gudang. Klik <strong>Tambah Cabang / Gudang Baru</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stores->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $stores->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH TOKO/GUDANG BARU -->
    <div x-show="createModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="createModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-blue-700 to-indigo-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏪</span>
                    <div>
                        <h3 class="text-sm font-black">Tambah Cabang / Gudang Baru</h3>
                        <p class="text-[11px] text-blue-200">Daftarkan outlet retail atau gudang distribusi fisik</p>
                    </div>
                </div>
                <button type="button" @click="createModal = false" class="text-blue-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('master.stores.store') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Cabang: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               placeholder="STR-002, WHS-001"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipe Lokasi: <span class="text-rose-500">*</span></label>
                        <select name="type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold bg-white focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                            <option value="STORE">🏪 Outlet / Toko Retail</option>
                            <option value="WAREHOUSE">🏭 Gudang / Warehouse</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Toko / Gudang: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           placeholder="Contoh: SIGMA MART CABANG KEBAYORAN"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-bold text-slate-900 focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor Telepon Toko:</label>
                    <input type="text"
                           name="phone"
                           placeholder="021-xxxxxxx / 08xxxxxxxx"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono focus:ring-2 focus:ring-blue-200 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap:</label>
                    <textarea name="address"
                              rows="3"
                              placeholder="Alamat fisik toko / gudang..."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-blue-200 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Cabang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT TOKO/GUDANG -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="editModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="text-sm font-black">Edit Data Cabang / Gudang</h3>
                        <p class="text-[11px] text-amber-200">Perbarui identitas, tipe, dan alamat toko</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-amber-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('master/stores') }}/' + editStore.id" method="POST" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Cabang: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               x-model="editStore.code"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipe Lokasi: <span class="text-rose-500">*</span></label>
                        <select name="type" x-model="editStore.type" required class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold bg-white focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                            <option value="STORE">🏪 Outlet / Toko Retail</option>
                            <option value="WAREHOUSE">🏭 Gudang / Warehouse</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Toko / Gudang: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           x-model="editStore.name"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-bold text-slate-900 focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nomor Telepon Toko:</label>
                    <input type="text"
                           name="phone"
                           x-model="editStore.phone"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap:</label>
                    <textarea name="address"
                              rows="3"
                              x-model="editStore.address"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-amber-200 focus:outline-hidden"></textarea>
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
