@extends('layouts.app')

@section('title', 'Master Tingkatan / Tier Pelanggan')
@section('tab_title', 'Master Tier Pelanggan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full"
     x-data="{
         createModal: false,
         editModal: false,
         editTier: {
             id: '',
             code: '',
             name: '',
             default_margin_percentage: 10,
             point_multiplier: 1,
             description: ''
         }
     }">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Master Data</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Tingkatan Pelanggan</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🎖️ Master Tingkatan Pelanggan (Customer Tiers)</span>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <button type="button"
                    @click="createModal = true"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all active:scale-95 cursor-pointer">
                <span>➕ Tambah Tier Baru</span>
            </button>
        </div>
    </div>

    <!-- INFO BOX TIER & HARGA OTOMATIS -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3.5 text-xs text-indigo-950 flex items-start gap-2.5">
        <span class="text-lg">💡</span>
        <div class="leading-relaxed">
            <strong>Penetapan Harga 3 Dimensi Otomatis:</strong> Tingkatan tier di bawah ini terhubung langsung ke <strong>Mesin Harga POS Kasir</strong> dan form master produk. Setiap pelanggan yang terdaftar pada tier tertentu akan otomatis memperoleh harga khusus (Retail, Grosir, Agen, Distributor, Member) tanpa kasir dapat mengubah harga secara manual.
        </div>
    </div>

    <!-- TABEL TIER PELANGGAN -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                    <tr>
                        <th class="py-2.5 px-3 w-12 text-center">No</th>
                        <th class="py-2.5 px-3 w-28">Kode Tier</th>
                        <th class="py-2.5 px-3">Nama Tingkatan Pelanggan</th>
                        <th class="py-2.5 px-3 text-right">Margin Baku (%)</th>
                        <th class="py-2.5 px-3 text-center">Multi Poin Loyalty</th>
                        <th class="py-2.5 px-3 text-center">Jumlah Pelanggan</th>
                        <th class="py-2.5 px-3">Deskripsi / Peruntukan</th>
                        <th class="py-2.5 px-3 w-20 text-center">Status</th>
                        <th class="py-2.5 px-3 w-32 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($tiers as $index => $t)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-2.5 px-3 text-center text-slate-400 font-mono text-[11px]">
                                {{ $tiers->firstItem() + $index }}
                            </td>
                            <td class="py-2.5 px-3 font-mono font-bold">
                                <span class="px-2 py-0.5 rounded text-[10.5px] font-black {{ $t->code === 'RETAIL' ? 'bg-indigo-100 text-indigo-900' : ($t->code === 'GROSIR' ? 'bg-purple-100 text-purple-900' : ($t->code === 'DISTRIBUTOR' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-800')) }}">
                                    {{ $t->code }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 font-bold text-slate-900">
                                {{ $t->name }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-emerald-700">
                                {{ number_format((float)$t->default_margin_percentage, 1) }}%
                            </td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-indigo-700">
                                {{ (float)$t->point_multiplier }}x
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold font-mono text-[11px]">
                                    {{ $t->customers_count }} Pelanggan
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                {{ $t->description ?: '-' }}
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold {{ $t->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                                    {{ $t->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button"
                                            @click="editTier = {
                                                id: '{{ $t->id }}',
                                                code: '{{ addslashes($t->code) }}',
                                                name: '{{ addslashes($t->name) }}',
                                                default_margin_percentage: {{ (float)$t->default_margin_percentage }},
                                                point_multiplier: {{ (float)$t->point_multiplier }},
                                                description: '{{ addslashes($t->description ?? '') }}'
                                            }; editModal = true;"
                                            class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-md font-bold text-[10.5px] transition-colors cursor-pointer"
                                            title="Edit Tier">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('master.customer-tiers.toggle-status', $t->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status tier pelanggan ini?');" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 {{ $t->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200' }} rounded-md font-bold text-[10px] transition-colors cursor-pointer"
                                                title="{{ $t->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            {{ $t->is_active ? 'Off' : 'On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 text-xs">
                                🎖️ Belum ada data tier pelanggan. Klik <strong>Tambah Tier Baru</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tiers->hasPages())
            <div class="p-3 border-t border-slate-200 bg-slate-50">
                {{ $tiers->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL TAMBAH TIER BARU -->
    <div x-show="createModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="createModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-indigo-700 to-purple-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🎖️</span>
                    <div>
                        <h3 class="text-sm font-black">Tambah Tier Pelanggan Baru</h3>
                        <p class="text-[11px] text-indigo-200">Definisikan klasifikasi level harga dan loyalty pelanggan</p>
                    </div>
                </div>
                <button type="button" @click="createModal = false" class="text-indigo-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('master.customer-tiers.store') }}" method="POST" class="p-5 space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Tier: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               placeholder="VIP, KOPERASI, PARTNER"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Tingkatan: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="name"
                               placeholder="Contoh: Pelanggan VIP Khusus"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-bold text-slate-900 focus:ring-2 focus:ring-indigo-200 focus:border-indigo-600 focus:outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Margin Baku Default (%): <span class="text-rose-500">*</span></label>
                        <input type="number"
                               name="default_margin_percentage"
                               step="0.1"
                               min="0"
                               value="10"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-indigo-200 focus:outline-hidden">
                        <span class="text-[10.5px] text-slate-400 mt-0.5 block">Persentase markup dari modal HPP</span>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pengali Poin Loyalty:</label>
                        <input type="number"
                               name="point_multiplier"
                               step="0.1"
                               min="0.1"
                               value="1.0"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-200 focus:outline-hidden">
                        <span class="text-[10.5px] text-slate-400 mt-0.5 block">1x = normal, 2x = dobel poin</span>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Ketentuan Tier:</label>
                    <textarea name="description"
                              rows="2"
                              placeholder="Keterangan kriteria pelanggan untuk masuk level ini..."
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg font-medium focus:ring-2 focus:ring-indigo-200 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                    <button type="button"
                            @click="createModal = false"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold rounded-lg shadow-md transition-all active:scale-95 cursor-pointer">
                        💾 Simpan Tier Pelanggan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT TIER -->
    <div x-show="editModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/60 backdrop-blur-2xs p-4 animate-in fade-in duration-150">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="editModal = false">
            
            <div class="px-5 py-3.5 bg-gradient-to-r from-amber-600 to-amber-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <div>
                        <h3 class="text-sm font-black">Edit Tier Pelanggan</h3>
                        <p class="text-[11px] text-amber-200">Perbarui margin keuntungan baku dan pengali poin</p>
                    </div>
                </div>
                <button type="button" @click="editModal = false" class="text-amber-200 hover:text-white font-bold text-lg cursor-pointer">✕</button>
            </div>

            <form :action="'{{ url('master/customer-tiers') }}/' + editTier.id" method="POST" class="p-5 space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Tier: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               x-model="editTier.code"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold uppercase focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Tingkatan: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="name"
                               x-model="editTier.name"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-bold text-slate-900 focus:ring-2 focus:ring-amber-200 focus:border-amber-600 focus:outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Margin Baku Default (%): <span class="text-rose-500">*</span></label>
                        <input type="number"
                               name="default_margin_percentage"
                               step="0.1"
                               min="0"
                               x-model="editTier.default_margin_percentage"
                               required
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-emerald-700 focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pengali Poin Loyalty:</label>
                        <input type="number"
                               name="point_multiplier"
                               step="0.1"
                               min="0.1"
                               x-model="editTier.point_multiplier"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg font-mono font-bold text-indigo-700 focus:ring-2 focus:ring-amber-200 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Ketentuan Tier:</label>
                    <textarea name="description"
                              rows="2"
                              x-model="editTier.description"
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
