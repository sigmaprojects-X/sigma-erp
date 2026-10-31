@extends('layouts.app')

@section('title', 'Master Satuan Barang (UOM)')
@section('tab_title', 'Master Satuan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="{ createModal: false, editModal: false, editUnit: { id: '', code: '', name: '', description: '' } }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Satuan Barang</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>📏 Master Satuan Barang (Unit of Measure)</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Satuan Baru</span>
            </button>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('master.units') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            <div class="sm:col-span-8">
                <label class="block font-bold text-slate-600 mb-1">Cari Satuan:</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ketik kode satuan (PCS, SAK, DUS, dll)..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-emerald-200 focus:border-emerald-600 focus:outline-hidden">
                </div>
            </div>
            <div class="sm:col-span-4 flex items-end gap-2">
                <button type="submit"
                        class="flex-1 py-1.5 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <span>Cari Data</span>
                </button>
                @if(request('search'))
                    <a href="{{ route('master.units') }}"
                       class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL SATUAN -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3 w-28">Kode Satuan</th>
                        <th class="py-2.5 px-3">Nama Satuan Lengkap</th>
                        <th class="py-2.5 px-3">Keterangan</th>
                        <th class="py-2.5 px-3 w-24 text-center">Status</th>
                        <th class="py-2.5 px-3 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($units as $index => $u)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                {{ $units->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2.5 py-0.5 rounded-md font-mono font-black text-xs bg-slate-100 text-slate-900 border border-slate-300">
                                    {{ $u->code }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-800">
                                {{ $u->name }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                {{ $u->description ?: '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold {{ $u->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $u->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="editUnit = { id: '{{ $u->id }}', code: '{{ addslashes($u->code) }}', name: '{{ addslashes($u->name) }}', description: '{{ addslashes($u->description ?? '') }}' }; editModal = true;"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-md font-bold text-[10.5px] transition-colors cursor-pointer"
                                            title="Edit Satuan">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('master.units.toggle-status', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status satuan ini?');" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 {{ $u->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }} rounded-md font-bold text-[10px] transition-colors cursor-pointer"
                                                title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $u->is_active ? 'Off' : 'On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                📏 Belum ada data satuan. Klik <strong>Tambah Satuan Baru</strong> untuk memasukkan data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($units->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $units->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH SATUAN BARU -->
    <div x-show="createModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="createModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-emerald-700 to-teal-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">📏</span>
                    <div>
                        <h3 class="text-sm font-black">Tambah Satuan Baku Baru</h3>
                        <p class="text-[11px] text-emerald-200">Standarisasi satuan kemasan barang di toko</p>
                    </div>
                </div>
                <button type="button" @click="createModal = false" class="text-emerald-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('master.units.store') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Satuan (Singkatan): <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="code"
                           placeholder="Contoh: PCS, DUS, SAK, KG, BAL"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-black uppercase focus:ring-2 focus:ring-emerald-200 focus:border-emerald-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Satuan Lengkap: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           placeholder="Contoh: Pieces / Eceran Satuan"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-emerald-200 focus:border-emerald-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Keterangan:</label>
                    <textarea name="description"
                              rows="2"
                              placeholder="Keterangan peruntukan satuan..."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-emerald-200 focus:border-emerald-600 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Satuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT SATUAN -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="editModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="text-sm font-black">Edit Master Satuan</h3>
                        <p class="text-[11px] text-amber-200">Perbarui nama dan keterangan satuan baku</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-amber-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('master/units') }}/' + editUnit.id" method="POST" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Satuan (Singkatan): <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="code"
                           x-model="editUnit.code"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-black uppercase focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Satuan Lengkap: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           x-model="editUnit.name"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Keterangan:</label>
                    <textarea name="description"
                              rows="2"
                              x-model="editUnit.description"
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
