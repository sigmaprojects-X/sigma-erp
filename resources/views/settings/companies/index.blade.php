@extends('layouts.app')

@section('title', 'Kelola Perusahaan (Multi-Company Holding)')
@section('tab_title', 'Kelola Perusahaan')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="companyManagementApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Multi-Company Holding</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🏢 Kelola Entitas Perusahaan (Multi-Company)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Kelola banyak badan usaha mandiri (PT, CV, Toko/Brand) dalam 1 server. Setiap perusahaan memiliki stok barang, laporan akuntansi, dan kasir terpisah 100%.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.general') }}"
               class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>⚙️ Pengaturan Umum</span>
            </a>
            <a href="{{ route('settings.stores.index') }}"
               class="px-3 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏪 Cabang &amp; Gudang</span>
            </a>
            <button type="button"
                    @click="openCreateModal()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-blue-900/20 active:scale-95 cursor-pointer">
                <span>➕</span>
                <span>TAMBAH PERUSAHAAN BARU</span>
            </button>
        </div>
    </div>

    <!-- STATS SUMMARY CARDS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-lg shrink-0">
                🏢
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Total Perusahaan</div>
                <div class="text-xl font-black text-slate-900 dark:text-white">{{ $companies->count() }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-lg shrink-0">
                ✅
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Perusahaan Aktif</div>
                <div class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                    {{ $companies->where('is_active', true)->count() }}
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3.5 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-lg shrink-0">
                🏪
            </div>
            <div>
                <div class="text-[11px] font-bold text-slate-500 uppercase">Total Cabang / Toko</div>
                <div class="text-xl font-black text-amber-600 dark:text-amber-400">
                    {{ $companies->sum(fn($c) => $c->stores->count()) }}
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-900 to-indigo-950 text-white rounded-xl p-3.5 shadow-xs flex items-center gap-3 border border-blue-800">
            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-lg shrink-0">
                👑
            </div>
            <div class="truncate">
                <div class="text-[10.5px] font-bold text-blue-200 uppercase tracking-wider">Perusahaan Aktif Saat Ini</div>
                <div class="text-sm font-black truncate text-white" title="{{ $activeCompany->name }}">
                    {{ $activeCompany->name }}
                </div>
            </div>
        </div>
    </div>

    <!-- DATA TABLE PERUSAHAAN -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50/60 dark:bg-slate-800/60">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <span>📋 Daftar Perusahaan &amp; Badan Usaha Terdaftar</span>
            </h2>
            <span class="text-xs text-slate-500">
                Gunakan tombol <strong>Beralih Perusahaan</strong> untuk mengganti konteks kerja sistem.
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-100/70 dark:bg-slate-900/40 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="py-3 px-4">Kode</th>
                        <th class="py-3 px-4">Nama Perusahaan</th>
                        <th class="py-3 px-4">Cabang / Toko</th>
                        <th class="py-3 px-4">Kontak &amp; Alamat</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Konteks Aktif</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                    @forelse($companies as $company)
                        @php
                            $isCurrent = $company->id === $activeCompany->id;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors {{ $isCurrent ? 'bg-blue-50/30 dark:bg-blue-950/20' : '' }}">
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-[11px]">
                                    {{ $company->code }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-extrabold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">🏢</span>
                                    <span>{{ $company->name }}</span>
                                </div>
                                @if($company->tax_id)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">NPWP: {{ $company->tax_id }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="space-y-1">
                                    @forelse($company->stores as $store)
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <span class="text-[10px] font-mono px-1 py-0.2 bg-slate-100 dark:bg-slate-700 rounded">{{ $store->code }}</span>
                                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $store->name }}</span>
                                            @if($store->is_main_store)
                                                <span class="text-[9px] font-black text-blue-600 bg-blue-50 border border-blue-200 px-1 py-0.2 rounded">UTAMA</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-slate-400 italic">Belum ada toko</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate">
                                <div>{{ $company->phone ?? '-' }}</div>
                                <div class="text-[11px] truncate text-slate-400">{{ $company->address ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($company->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($isCurrent)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10.5px] font-black bg-blue-600 text-white shadow-xs">
                                        <span>👑</span>
                                        SEDANG DIGUNAKAN
                                    </span>
                                @else
                                    <form action="{{ route('company.switch') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="company_id" value="{{ $company->id }}">
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-full text-[10.5px] font-extrabold bg-slate-100 hover:bg-blue-600 hover:text-white text-slate-700 border border-slate-300 hover:border-blue-600 shadow-2xs transition-all cursor-pointer">
                                            Beralih ke Sini ➔
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click="openEditModal({{ json_encode($company) }})"
                                            class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 rounded-lg transition-colors cursor-pointer"
                                            title="Edit Profil Perusahaan">
                                        ✏️
                                    </button>
                                    <form action="{{ route('settings.companies.toggle-status', $company->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif perusahaan ini?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-700 rounded-lg transition-colors cursor-pointer"
                                                title="{{ $company->is_active ? 'Nonaktifkan Perusahaan' : 'Aktifkan Perusahaan' }}">
                                            {{ $company->is_active ? '⏸️' : '▶️' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">
                                Belum ada data perusahaan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL TAMBAH PERUSAHAAN BARU -->
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700"
             @click.away="showCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase flex items-center gap-2">
                    <span>🏢 Tambah Entitas Perusahaan Baru</span>
                </h3>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <form action="{{ route('settings.companies.store') }}" method="POST" class="space-y-4 mt-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Nama Perusahaan / Badan Usaha <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: CV BERKAH ABADI / PT MAJU MANDIRI"
                           class="w-full text-xs font-semibold px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kode Perusahaan (Singkat) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="code" required placeholder="Contoh: BERKAH-ABADI"
                               class="w-full text-xs font-mono uppercase px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor NPWP / NIK (Opsional)
                        </label>
                        <input type="text" name="tax_id" placeholder="00.000.000.0-000.000"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nama Toko / Outlet Utama
                        </label>
                        <input type="text" name="store_name" placeholder="Contoh: Toko Utama Berkah"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kode Toko (Opsional)
                        </label>
                        <input type="text" name="store_code" placeholder="STR-001"
                               class="w-full text-xs font-mono uppercase px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" placeholder="0812xxxxxxxx"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email Perusahaan</label>
                        <input type="email" name="email" placeholder="contact@perusahaan.com"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alamat Kantor / Toko</label>
                    <textarea name="address" rows="2" placeholder="Alamat lengkap..."
                              class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl border border-blue-200 dark:border-blue-800 text-[11px] text-blue-800 dark:text-blue-300">
                    💡 <strong>Otomatis Dibuatkan:</strong> Toko Utama, Bagan Akun Akuntansi (COA), Level Harga (Retail/Grosir), dan Pola Nomor Nota otomatis disiapkan secara mandiri untuk perusahaan ini.
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-lg hover:bg-slate-50 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-md cursor-pointer">
                        SIMPAN &amp; AKTIFKAN
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT PERUSAHAAN -->
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-700"
             @click.away="showEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase flex items-center gap-2">
                    <span>✏️ Edit Profil Perusahaan</span>
                </h3>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">&times;</button>
            </div>

            <form :action="'{{ url('settings/companies') }}/' + editForm.id" method="POST" class="space-y-4 mt-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Nama Perusahaan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="editForm.name" required
                           class="w-full text-xs font-semibold px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Kode Perusahaan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="code" x-model="editForm.code" required
                               class="w-full text-xs font-mono uppercase px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Nomor NPWP / NIK
                        </label>
                        <input type="text" name="tax_id" x-model="editForm.tax_id"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">No. Telepon</label>
                        <input type="text" name="phone" x-model="editForm.phone"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input type="email" name="email" x-model="editForm.email"
                               class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Alamat Kantor</label>
                    <textarea name="address" x-model="editForm.address" rows="2"
                              class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pesan Footer Struk Kasir</label>
                    <textarea name="receipt_footer" x-model="editForm.receipt_footer" rows="2"
                              class="w-full text-xs px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-900 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-lg hover:bg-slate-50 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-md cursor-pointer">
                        SIMPAN PERUBAHAN
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function companyManagementApp() {
        return {
            showCreateModal: false,
            showEditModal: false,
            editForm: {
                id: '',
                name: '',
                code: '',
                tax_id: '',
                phone: '',
                email: '',
                address: '',
                receipt_footer: ''
            },
            openCreateModal() {
                this.showCreateModal = true;
            },
            openEditModal(company) {
                this.editForm = {
                    id: company.id,
                    name: company.name || '',
                    code: company.code || '',
                    tax_id: company.tax_id || '',
                    phone: company.phone || '',
                    email: company.email || '',
                    address: company.address || '',
                    receipt_footer: company.receipt_footer || ''
                };
                this.showEditModal = true;
            }
        };
    }
</script>
@endsection
