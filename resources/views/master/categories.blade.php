@extends('layouts.app')

@section('title', 'Master Jenis & Kategori Produk')
@section('tab_title', 'Master Kategori')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="{ createModal: false, editModal: false, editCat: { id: '', code: '', name: '', description: '' } }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Jenis / Kategori Produk</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏷️ Master Data Jenis & Kategori Produk</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Kategori Baru</span>
            </button>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs">
        <form method="GET" action="{{ route('master.categories') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 text-xs">
            <div class="sm:col-span-8">
                <label class="block font-bold text-slate-600 mb-1">Cari Kategori:</label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-slate-400">🔍</span>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Ketik kode atau nama jenis kategori..."
                           class="w-full pl-8 pr-3 py-1.5 border border-slate-300 rounded-lg font-medium text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>
            </div>
            <div class="sm:col-span-4 flex items-end gap-2">
                <button type="submit"
                        class="flex-1 py-1.5 px-3 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-lg shadow-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                    <span>Cari Data</span>
                </button>
                @if(request('search'))
                    <a href="{{ route('master.categories') }}"
                       class="py-1.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-lg border border-slate-300 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- TABEL KATEGORI -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3 w-32">Kode Kategori</th>
                        <th class="py-2.5 px-3">Nama Jenis / Kategori</th>
                        <th class="py-2.5 px-3">Deskripsi / Keterangan</th>
                        <th class="py-2.5 px-3 w-32 text-center">Jumlah Produk</th>
                        <th class="py-2.5 px-3 w-20 text-center">Status</th>
                        <th class="py-2.5 px-3 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($categories as $index => $cat)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                {{ $categories->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold text-blue-700">
                                {{ $cat->code }}
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $cat->name }}
                            </td>
                            <td class="py-2.5 px-3 text-slate-600 text-[11px]">
                                {{ $cat->description ?: '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200 font-mono">
                                    {{ $cat->products_count }} Item
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold {{ $cat->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $cat->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="editCat = { id: '{{ $cat->id }}', code: '{{ addslashes($cat->code) }}', name: '{{ addslashes($cat->name) }}', description: '{{ addslashes($cat->description ?? '') }}' }; editModal = true;"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-md font-bold text-[10.5px] transition-colors cursor-pointer"
                                            title="Edit Kategori">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('master.categories.toggle-status', $cat->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status kategori ini?');" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 {{ $cat->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }} rounded-md font-bold text-[10px] transition-colors cursor-pointer"
                                                title="{{ $cat->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $cat->is_active ? 'Off' : 'On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-xs">
                                🏷️ Belum ada data kategori. Klik <strong>Tambah Kategori Baru</strong> untuk memasukkan data.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH KATEGORI BARU -->
    <div x-show="createModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="createModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-blue-700 to-blue-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏷️</span>
                    <div>
                        <h3 class="text-sm font-black">Tambah Master Kategori Produk</h3>
                        <p class="text-[11px] text-blue-200">Klasifikasi kelompok barang dagang toko</p>
                    </div>
                </div>
                <button type="button" @click="createModal = false" class="text-blue-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('master.categories.store') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Kategori: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="code"
                           placeholder="Contoh: SEMBAKO, MINUMAN, BUMBU"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Kategori / Jenis: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           placeholder="Contoh: Sembako & Bahan Pokok"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Cakupan Barang:</label>
                    <textarea name="description"
                              rows="3"
                              placeholder="Keterangan jenis barang yang masuk dalam kategori ini..."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT KATEGORI -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="editModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="text-sm font-black">Edit Master Kategori Produk</h3>
                        <p class="text-[11px] text-amber-200">Perbarui data kelompok barang dagang toko</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-amber-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('master/categories') }}/' + editCat.id" method="POST" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kode Kategori: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="code"
                           x-model="editCat.code"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Kategori / Jenis: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           x-model="editCat.name"
                           required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg font-semibold focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Cakupan Barang:</label>
                    <textarea name="description"
                              rows="3"
                              x-model="editCat.description"
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
