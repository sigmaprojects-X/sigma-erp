@extends('layouts.app')

@section('title', 'Manajemen Cabang Toko & Gudang')
@section('tab_title', 'Cabang & Gudang')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="storeManagementApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 font-bold">Multi-Store &amp; Gudang</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>🏪 Cabang Toko &amp; Gudang Multi-Store</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.general') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏢 Profil Perusahaan</span>
            </a>
            <a href="{{ route('settings.users.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>👥 Pengguna &amp; Hak Akses</span>
            </a>
            <a href="{{ route('settings.printer') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️ Printer &amp; Struk</span>
            </a>
            <a href="{{ route('settings.numbering.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🔢 Penomoran</span>
            </a>
            <a href="{{ route('settings.cashier') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>💼 Kasir</span>
            </a>
            <a href="{{ route('settings.maintenance.index') }}"
               class="px-3 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🛡️ Pemeliharaan &amp; Backup</span>
            </a>
            <button type="button"
                    @click="openCreateModal()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-blue-900/20 active:scale-95 cursor-pointer">
                <span>➕</span>
                <span>TAMBAH CABANG / GUDANG</span>
            </button>
        </div>
    </div>

    <!-- STATS SUMMARY CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-200 flex items-center justify-center text-lg shrink-0">
                🏪
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Total Cabang</span>
                <span class="text-lg font-black text-slate-900">{{ $stores->count() }} Cabang</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 border border-emerald-200 flex items-center justify-center text-lg shrink-0">
                🛒
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Toko Retail (POS)</span>
                <span class="text-lg font-black text-emerald-700">{{ $stores->where('type', 'STORE')->where('is_active', true)->count() }} Aktif</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-200 flex items-center justify-center text-lg shrink-0">
                📦
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Gudang Distribusi</span>
                <span class="text-lg font-black text-amber-700">{{ $stores->where('type', 'WAREHOUSE')->where('is_active', true)->count() }} Aktif</span>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-purple-50 border border-purple-200 flex items-center justify-center text-lg shrink-0">
                ⭐
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Toko Utama</span>
                <span class="text-xs font-black text-purple-900 truncate block max-w-[140px]" title="{{ optional($stores->where('is_main_store', true)->first())->name }}">
                    {{ optional($stores->where('is_main_store', true)->first())->name ?? 'Belum Ditentukan' }}
                </span>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE CARD -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-900">Daftar Cabang Toko &amp; Gudang Penyimpanan</h2>
                <p class="text-xs text-slate-500">Cabang dan gudang aktif yang terdaftar dalam satu ekosistem perusahaan {{ $company->name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari kode, nama, kota..."
                       class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:outline-hidden focus:bg-white focus:border-blue-500 w-48 sm:w-64">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Status &amp; Kode</th>
                        <th class="py-3 px-4">Nama Cabang / Gudang</th>
                        <th class="py-3 px-4">Tipe</th>
                        <th class="py-3 px-4">PIC / Penanggung Jawab</th>
                        <th class="py-3 px-4">Kontak &amp; Wilayah</th>
                        <th class="py-3 px-4">Alamat Lengkap</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($stores as $st)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $st->is_main_store ? 'bg-amber-50/20' : '' }}"
                            x-show="matchesSearch('{{ strtolower($st->code . ' ' . $st->name . ' ' . $st->city . ' ' . $st->pic_name) }}')">
                            
                            <!-- KODE & BADGE UTAMA -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-mono font-black text-slate-900 text-xs">{{ $st->code }}</span>
                                    @if($st->is_main_store)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-0.5" title="Kantor Pusat / Toko Utama">
                                            <span>⭐</span>
                                            <span>PUSAT</span>
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- NAMA TOKO -->
                            <td class="py-3 px-4">
                                <div class="font-black text-slate-900 text-xs">{{ $st->name }}</div>
                                <span class="text-[10px] text-slate-400 font-mono">Dibuat {{ $st->created_at ? $st->created_at->format('d/m/Y') : '-' }}</span>
                            </td>

                            <!-- TIPE CABANG -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($st->type === 'STORE')
                                    <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1 w-max">
                                        <span>🛒</span>
                                        <span>Toko Retail (POS)</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-purple-50 text-purple-700 border border-purple-200 flex items-center gap-1 w-max">
                                        <span>📦</span>
                                        <span>Gudang Distribusi</span>
                                    </span>
                                @endif
                            </td>

                            <!-- PIC -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-semibold text-slate-800">{{ $st->pic_name ?: '-' }}</span>
                            </td>

                            <!-- KONTAK & KOTA -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="text-slate-800 font-medium">{{ $st->phone ?: '-' }}</div>
                                <div class="text-[10.5px] text-slate-500 font-semibold">{{ $st->city ?: 'Kota belum diset' }}</div>
                            </td>

                            <!-- ALAMAT LENGKAP -->
                            <td class="py-3 px-4 max-w-xs">
                                <p class="line-clamp-2 text-slate-600 text-[11px] leading-relaxed">
                                    {{ $st->address ?: '-' }}
                                    @if($st->postal_code)
                                        <span class="text-slate-400 font-mono">({{ $st->postal_code }})</span>
                                    @endif
                                </p>
                            </td>

                            <!-- STATUS OPERASIONAL -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($st->is_active)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        ● Aktif
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300">
                                        ○ Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- AKSI -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    
                                    <!-- Tombol Bagikan Link Cabang -->
                                    <button type="button"
                                            @click="openShareModal(@js($st))"
                                            class="px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-extrabold border border-blue-200 rounded-lg transition-colors cursor-pointer flex items-center gap-1 shadow-2xs"
                                            title="Lihat & Bagikan Tautan Cabang (Marketplace, Gudang, Kasir)">
                                        <span>🔗</span>
                                        <span class="text-[11px]">Tautan Cabang</span>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button"
                                            @click="openEditModal(@js($st))"
                                            class="p-1.5 bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-700 border border-slate-200 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Data Cabang">
                                        ✏️
                                    </button>

                                    <!-- Tombol Jadikan Toko Utama (jika bukan toko utama dan aktif) -->
                                    @if(!$st->is_main_store && $st->is_active)
                                        <form action="{{ route('settings.stores.set-main', $st->id) }}" method="POST" class="inline" onsubmit="return confirm('Jadikan [{{ $st->name }}] sebagai Toko Utama / Kantor Pusat?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="p-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 rounded-lg transition-colors cursor-pointer"
                                                    title="Jadikan Toko Utama / Kantor Pusat">
                                                ⭐
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Tombol Toggle Status (jika bukan toko utama) -->
                                    @if(!$st->is_main_store)
                                        <form action="{{ route('settings.stores.toggle-status', $st->id) }}" method="POST" class="inline" onsubmit="return confirm('Ubah status operasional cabang [{{ $st->name }}]?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="p-1.5 {{ $st->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200' }} border rounded-lg transition-colors cursor-pointer"
                                                    title="{{ $st->is_active ? 'Nonaktifkan Cabang' : 'Aktifkan Cabang' }}">
                                                {{ $st->is_active ? '⏸️' : '▶️' }}
                                            </button>
                                        </form>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                Belum ada data cabang toko atau gudang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL TAMBAH CABANG TOKO / GUDANG BARU                         -->
    <!-- ============================================================== -->
    <div x-show="showCreateModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-4" @click.outside="showCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🏪</span>
                    <h3 class="text-sm font-black text-slate-900">Tambah Cabang Toko / Gudang Baru</h3>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('settings.stores.store') }}" method="POST" class="space-y-3.5 text-xs">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Cabang: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               required
                               placeholder="Contoh: STR-002 / GUD-002"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipe Lokasi: <span class="text-rose-500">*</span></label>
                        <select name="type" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            <option value="STORE">Toko Retail (POS Kasir)</option>
                            <option value="WAREHOUSE">Gudang Penyimpanan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Cabang / Gudang: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           required
                           placeholder="Contoh: SIGMA MART CABANG CIPETE"
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama PIC / Penanggung Jawab:</label>
                        <input type="text"
                               name="pic_name"
                               placeholder="Kepala Toko / Supervisor"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">No. Telepon Toko:</label>
                        <input type="text"
                               name="phone"
                               placeholder="021-xxxxxx / 0812xxxx"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kota / Kabupaten:</label>
                        <input type="text"
                               name="city"
                               placeholder="Jakarta Selatan"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Pos:</label>
                        <input type="text"
                               name="postal_code"
                               placeholder="12410"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap:</label>
                    <textarea name="address"
                              rows="2"
                              placeholder="Nama jalan, nomor bangunan, patokan..."
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_main_store" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700">Jadikan sebagai Toko Utama / Kantor Pusat</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-lg shadow-sm transition-colors cursor-pointer">
                        Simpan Cabang Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL EDIT CABANG TOKO / GUDANG                                -->
    <!-- ============================================================== -->
    <div x-show="showEditModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 space-y-4" @click.outside="showEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-xl">✏️</span>
                    <h3 class="text-sm font-black text-slate-900">Edit Data Cabang / Gudang</h3>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <form :action="'{{ url('settings/stores') }}/' + currentStore.id" method="POST" class="space-y-3.5 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Cabang: <span class="text-rose-500">*</span></label>
                        <input type="text"
                               name="code"
                               x-model="currentStore.code"
                               required
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden uppercase">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tipe Lokasi: <span class="text-rose-500">*</span></label>
                        <select name="type" x-model="currentStore.type" required class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-semibold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                            <option value="STORE">Toko Retail (POS Kasir)</option>
                            <option value="WAREHOUSE">Gudang Penyimpanan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Cabang / Gudang: <span class="text-rose-500">*</span></label>
                    <input type="text"
                           name="name"
                           x-model="currentStore.name"
                           required
                           class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-bold text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama PIC / Penanggung Jawab:</label>
                        <input type="text"
                               name="pic_name"
                               x-model="currentStore.pic_name"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">No. Telepon Toko:</label>
                        <input type="text"
                               name="phone"
                               x-model="currentStore.phone"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kota / Kabupaten:</label>
                        <input type="text"
                               name="city"
                               x-model="currentStore.city"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kode Pos:</label>
                        <input type="text"
                               name="postal_code"
                               x-model="currentStore.postal_code"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap:</label>
                    <textarea name="address"
                              rows="2"
                              x-model="currentStore.address"
                              class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-medium text-slate-800 text-xs focus:ring-2 focus:ring-blue-200 focus:border-blue-600 focus:outline-hidden"></textarea>
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_main_store" value="1" x-model="currentStore.is_main_store" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-slate-700">Toko Utama / Kantor Pusat</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-lg shadow-sm transition-colors cursor-pointer">
                        Perbarui Data Cabang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL BAGIKAN TAUTAN CABANG (MARKETPLACE, GUDANG & KASIR)     -->
    <!-- ============================================================== -->
    <div x-show="showShareModal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-xl w-full p-6 space-y-4" @click.outside="showShareModal = false">
            <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center justify-center text-xl shrink-0">
                        🔗
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 leading-tight">Tautan Akses &amp; Berbagi Cabang</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <span class="font-bold text-slate-800" x-text="currentStore.name"></span>
                            <span class="font-mono text-slate-400"> • Kode: <strong x-text="currentStore.code"></strong></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="showShareModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold cursor-pointer">&times;</button>
            </div>

            <div class="space-y-4 text-xs">

                <!-- 1. TAUTAN MARKETPLACE KONSUMEN -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 font-black text-slate-900">
                            <span>🛒</span>
                            <span>Marketplace Online Konsumen (PWA HP)</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            Publik / Pelanggan
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Bagikan ke pelanggan untuk belanja online. Pesanan &amp; reservasi stok otomatis masuk ke cabang ini.
                    </p>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="text"
                               readonly
                               :value="getMarketplaceUrl()"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono text-[11px] font-bold text-blue-700 select-all focus:outline-hidden">
                        <button type="button"
                                @click="copyToClipboard(getMarketplaceUrl(), 'market')"
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors whitespace-nowrap cursor-pointer shrink-0">
                            <span x-show="copiedKey !== 'market'">📋 Salin</span>
                            <span x-show="copiedKey === 'market'" class="text-emerald-200">✅ Tersalin!</span>
                        </button>
                        <a :href="getMarketplaceUrl()" target="_blank"
                           class="p-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg transition-colors cursor-pointer shrink-0"
                           title="Buka Halaman di Tab Baru">
                            ↗️
                        </a>
                        <a :href="getWaShareUrl()" target="_blank"
                           class="p-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition-colors cursor-pointer shrink-0"
                           title="Kirim ke WhatsApp">
                            💬
                        </a>
                    </div>
                </div>

                <!-- 2. TAUTAN STOCK OPNAME GUDANG -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 font-black text-slate-900">
                            <span>📦</span>
                            <span>PWA Stock Opname Gudang (Audit HP Staf)</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                            Staf Gudang / Kasir
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Kirimkan ke staf untuk scan barcode rak fisik &amp; audit buta (*Blind Opname*) di cabang ini.
                    </p>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="text"
                               readonly
                               :value="getOpnameUrl()"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono text-[11px] font-bold text-purple-700 select-all focus:outline-hidden">
                        <button type="button"
                                @click="copyToClipboard(getOpnameUrl(), 'opname')"
                                class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-colors whitespace-nowrap cursor-pointer shrink-0">
                            <span x-show="copiedKey !== 'opname'">📋 Salin</span>
                            <span x-show="copiedKey === 'opname'" class="text-emerald-200">✅ Tersalin!</span>
                        </button>
                        <a :href="getOpnameUrl()" target="_blank"
                           class="p-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg transition-colors cursor-pointer shrink-0"
                           title="Buka PWA Gudang di Tab Baru">
                            ↗️
                        </a>
                    </div>
                </div>

                <!-- 3. TAUTAN KASIR POS -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 font-black text-slate-900">
                            <span>💻</span>
                            <span>Layar Kasir POS Cabang (Komputer / Tablet Kasir)</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            Kasir Toko
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Buka di browser PC kasir toko atau tablet kasir untuk langsung memulai transaksi kasir cabang ini.
                    </p>
                    <div class="flex items-center gap-2 pt-1">
                        <input type="text"
                               readonly
                               :value="getPosUrl()"
                               class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg font-mono text-[11px] font-bold text-amber-800 select-all focus:outline-hidden">
                        <button type="button"
                                @click="copyToClipboard(getPosUrl(), 'pos')"
                                class="px-3 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-lg transition-colors whitespace-nowrap cursor-pointer shrink-0">
                            <span x-show="copiedKey !== 'pos'">📋 Salin</span>
                            <span x-show="copiedKey === 'pos'" class="text-emerald-200">✅ Tersalin!</span>
                        </button>
                        <a :href="getPosUrl()" target="_blank"
                           class="p-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg transition-colors cursor-pointer shrink-0"
                           title="Buka Layar Kasir POS di Tab Baru">
                            ↗️
                        </a>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                <button type="button" @click="showShareModal = false" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-colors cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function storeManagementApp() {
        return {
            showCreateModal: false,
            showEditModal: false,
            showShareModal: false,
            searchQuery: '',
            currentStore: {},
            copiedKey: null,

            openCreateModal() {
                this.showCreateModal = true;
            },

            openEditModal(store) {
                this.currentStore = Object.assign({}, store);
                this.showEditModal = true;
            },

            openShareModal(store) {
                this.currentStore = Object.assign({}, store);
                this.showShareModal = true;
                this.copiedKey = null;
            },

            getMarketplaceUrl() {
                const code = (this.currentStore.code || '').toLowerCase();
                return window.location.origin + '/cabang/' + code;
            },

            getOpnameUrl() {
                const code = (this.currentStore.code || '').toLowerCase();
                return window.location.origin + '/warehouse/opname-pwa?cabang=' + code;
            },

            getPosUrl() {
                const code = (this.currentStore.code || '').toLowerCase();
                return window.location.origin + '/pos?cabang=' + code;
            },

            getWaShareUrl() {
                const storeName = this.currentStore.name || 'Toko Kami';
                const link = this.getMarketplaceUrl();
                const text = encodeURIComponent(`Halo! Kini Anda bisa belanja online langsung dari ${storeName} melalui link resmi berikut:\n${link}\n\nBelanja praktis, cepat, dan terpercaya!`);
                return 'https://api.whatsapp.com/send?text=' + text;
            },

            copyToClipboard(text, key) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copiedKey = key;
                    setTimeout(() => {
                        if (this.copiedKey === key) this.copiedKey = null;
                    }, 2000);
                }).catch(() => {
                    prompt('Salin tautan ini:', text);
                });
            },

            matchesSearch(text) {
                if (!this.searchQuery.trim()) return true;
                return text.includes(this.searchQuery.toLowerCase().trim());
            }
        }
    }
</script>
@endpush
@endsection
