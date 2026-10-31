@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Kelompok Hak Akses')
@section('tab_title', 'Pengguna & Hak Akses')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="userManagementApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('settings.general') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Pengguna &amp; Kelompok Hak Akses</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>👥 Manajemen Pengguna &amp; Matriks Hak Akses (RBAC)</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.general') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏢 Profil &amp; Pajak</span>
            </a>
            <a href="{{ route('settings.stores.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏪 Cabang &amp; Gudang</span>
            </a>
            <a href="{{ route('settings.printer') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️ Printer &amp; Struk</span>
            </a>
            <a href="{{ route('settings.numbering.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🔢 Penomoran</span>
            </a>
            <a href="{{ route('settings.cashier') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>💼 Kasir</span>
            </a>
            <a href="{{ route('settings.maintenance.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>💾 Pemeliharaan</span>
            </a>
            <a href="{{ route('settings.audit-logs.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🛡️ Audit Log</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
                <span>⚡ BUKA KASIR [F12]</span>
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- NAVIGATION TABS -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2">
        <button type="button"
                @click="activeTab = 'groups'"
                :class="activeTab === 'groups' ? 'bg-blue-600 text-white shadow-sm font-black' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 font-bold border border-slate-200 dark:border-slate-700'"
                class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer">
            <span>🔐 Kelompok Hak Akses (Matriks Ceklist)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-black/20 text-white">{{ $userGroups->count() }} Kelompok</span>
        </button>

        <button type="button"
                @click="activeTab = 'users'"
                :class="activeTab === 'users' ? 'bg-blue-600 text-white shadow-sm font-black' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 font-bold border border-slate-200 dark:border-slate-700'"
                class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition-all cursor-pointer">
            <span>👤 Daftar Pengguna &amp; Kasir</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] bg-black/20 text-white">{{ $users->count() }} Akun</span>
        </button>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 1: KELOMPOK HAK AKSES (SISTEM CEKLIST MATRIKS STANDAR KETOKO.ID)  -->
    <!-- ===================================================================== -->
    <div x-show="activeTab === 'groups'" class="space-y-4" x-cloak>
        
        <!-- TOP TOOLBAR: KELOMPOK SELECTOR & ACTIONS -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-xs space-y-3">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                
                <!-- SELECT ACTIVE GROUP -->
                <div class="flex items-center gap-3">
                    <label class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider shrink-0 flex items-center gap-1.5">
                        <span>🛡️ Kelompok User:</span>
                    </label>
                    <select class="form-select text-xs font-bold rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white py-1.5 px-3 min-w-[220px] focus:ring-blue-500 focus:border-blue-500"
                            onchange="window.location.href = '{{ route('settings.users.index') }}?group_id=' + this.value + '&tab=groups'">
                        @foreach($userGroups as $group)
                            <option value="{{ $group->id }}" {{ $selectedGroup && $selectedGroup->id === $group->id ? 'selected' : '' }}>
                                {{ $group->name }} ({{ $group->code }}) - [{{ $group->users_count }} user]
                            </option>
                        @endforeach
                    </select>

                    @if($selectedGroup)
                        @if($selectedGroup->is_system)
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-900/60 dark:text-blue-300 dark:border-blue-700">
                                🔒 KELOMPOK SISTEM
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-900/60 dark:text-purple-300 dark:border-purple-700">
                                ⚙️ KELOMPOK KUSTOM
                            </span>
                        @endif
                    @endif
                </div>

                <!-- GROUP ACTION BUTTONS -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button"
                            @click="openNewGroupModal = true"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-xs flex items-center gap-1 transition-all cursor-pointer">
                        <span>+ Tambah Kelompok Baru</span>
                    </button>

                    @if($selectedGroup && !$selectedGroup->is_system)
                        <button type="button"
                                @click="openEditGroupModal = true"
                                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg border border-slate-300 dark:border-slate-600 flex items-center gap-1 transition-all cursor-pointer">
                            <span>✏️ Edit Nama</span>
                        </button>

                        <form action="{{ route('settings.user-groups.destroy', $selectedGroup->id) }}" method="POST"
                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelompok hak akses {{ $selectedGroup->name }}?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-lg border border-rose-200 flex items-center gap-1 transition-all cursor-pointer">
                                <span>🗑️ Hapus Kelompok</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- PRESET QUICK APPLY ROW & SEARCH -->
            @if($selectedGroup)
                <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                    <div class="flex items-center flex-wrap gap-1.5">
                        <span class="text-[11px] font-black text-slate-500 uppercase tracking-wider mr-1">⚡ Terapkan Template Preset:</span>
                        @foreach($presets as $pKey => $presetData)
                            <form action="{{ route('settings.user-groups.preset', $selectedGroup->id) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Terapkan preset {{ $presetData['name'] }} ke kelompok ini? Ceklist saat ini akan ditimpa.');">
                                @csrf
                                <input type="hidden" name="preset" value="{{ $pKey }}">
                                <button type="submit"
                                        class="px-2 py-1 bg-slate-100 hover:bg-blue-50 dark:bg-slate-700 dark:hover:bg-slate-600 hover:text-blue-700 dark:hover:text-blue-300 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600 rounded text-[11px] font-bold transition-colors cursor-pointer">
                                    {{ $presetData['name'] }}
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <!-- SEARCH PERMISSION -->
                    <div class="relative min-w-[240px]">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="🔍 Cari menu / modul izin..."
                               class="w-full text-xs py-1.5 pl-8 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            @endif
        </div>

        @if($selectedGroup)
            <!-- CHECKLIST MATRIX FORM -->
            <form action="{{ route('settings.user-groups.update', $selectedGroup->id) }}" method="POST" id="permissionsMatrixForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="save_permissions_matrix" value="1">

                @if($selectedGroup->code === 'ADMINISTRATOR')
                    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800 rounded-xl p-3.5 text-xs text-amber-900 dark:text-amber-200 flex items-center gap-3">
                        <span class="text-2xl">👑</span>
                        <div>
                            <span class="font-black text-amber-800 dark:text-amber-300 uppercase tracking-wide">Kelompok Super Administrator (Full Access):</span>
                            <p class="mt-0.5 text-amber-700 dark:text-amber-400">Kelompok ini memiliki hak akses penuh tanpa batasan untuk seluruh modul dan menu sistem SIGMA ERP secara otomatis.</p>
                        </div>
                    </div>
                @endif

                <!-- CATEGORY NAVIGATION PILLS & GLOBAL TOGGLES -->
                <div class="bg-slate-100 dark:bg-slate-900/60 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center flex-wrap gap-1">
                        <button type="button"
                                @click="activeCategory = 'all'"
                                :class="activeCategory === 'all' ? 'bg-blue-600 text-white font-black' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-bold border border-slate-300 dark:border-slate-700'"
                                class="px-2.5 py-1 text-xs rounded-lg transition-colors cursor-pointer">
                            Semua Modul ({{ count($catalog) }})
                        </button>
                        @foreach($catalog as $catKey => $catData)
                            <button type="button"
                                    @click="activeCategory = '{{ $catKey }}'"
                                    :class="activeCategory === '{{ $catKey }}' ? 'bg-blue-600 text-white font-black' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-bold border border-slate-300 dark:border-slate-700'"
                                    class="px-2.5 py-1 text-xs rounded-lg transition-colors cursor-pointer flex items-center gap-1">
                                <span>{{ $catData['icon'] }}</span>
                                <span>{{ $catData['name'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button"
                                @click="checkAllVisible()"
                                class="px-2.5 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 text-[11px] font-bold rounded-lg border border-emerald-300 dark:border-emerald-700 cursor-pointer transition-colors">
                            ✓ Centang Semua Tampil
                        </button>
                        <button type="button"
                                @click="uncheckAllVisible()"
                                class="px-2.5 py-1 bg-rose-100 hover:bg-rose-200 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300 text-[11px] font-bold rounded-lg border border-rose-300 dark:border-rose-700 cursor-pointer transition-colors">
                            ✗ Batalkan Semua Tampil
                        </button>
                    </div>
                </div>

                <!-- MATRIX TABLE -->
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-xs mt-3">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 dark:bg-slate-700/80 border-b border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-xs font-black uppercase tracking-wider">
                                    <th class="py-3 px-4 w-5/12">Modul / Menu / Fitur</th>
                                    <th class="py-3 px-3 text-center w-1/12">Buka</th>
                                    <th class="py-3 px-3 text-center w-1/12">Baru</th>
                                    <th class="py-3 px-3 text-center w-1/12">Ubah</th>
                                    <th class="py-3 px-3 text-center w-1/12">Hapus</th>
                                    <th class="py-3 px-3 text-center w-2/12">Kunci / Khusus</th>
                                    <th class="py-3 px-3 text-center w-1/12">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                                @foreach($catalog as $catKey => $category)
                                    <!-- CATEGORY HEADER ROW -->
                                    <tr x-show="(activeCategory === 'all' || activeCategory === '{{ $catKey }}') && categoryMatchesSearch('{{ $catKey }}')"
                                        class="bg-blue-50/70 dark:bg-slate-900/80 font-black text-slate-900 dark:text-white border-y border-blue-200 dark:border-slate-700">
                                        <td colspan="6" class="py-2.5 px-4">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-base">{{ $category['icon'] }}</span>
                                                    <span class="text-sm font-black text-blue-900 dark:text-blue-300">{{ $category['name'] }}</span>
                                                    <span class="text-[11px] font-normal text-slate-500">({{ $category['description'] }})</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button"
                                                        @click="toggleCategory('{{ $catKey }}', true)"
                                                        class="px-1.5 py-0.5 text-[10px] bg-blue-100 hover:bg-blue-200 text-blue-800 font-bold rounded cursor-pointer"
                                                        title="Centang semua di kategori ini">
                                                    ✓ Semua
                                                </button>
                                                <button type="button"
                                                        @click="toggleCategory('{{ $catKey }}', false)"
                                                        class="px-1.5 py-0.5 text-[10px] bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded cursor-pointer"
                                                        title="Hapus semua di kategori ini">
                                                    ✗ Batal
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- ITEMS UNDER CATEGORY -->
                                    @foreach($category['items'] as $item)
                                        @php
                                            $itemKey = $item['key'];
                                            $actions = $item['actions'];
                                        @endphp
                                        <tr x-show="(activeCategory === 'all' || activeCategory === '{{ $catKey }}') && itemMatchesSearch('{{ strtolower($item['label']) }}', '{{ strtolower($itemKey) }}')"
                                            class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                                            
                                            <!-- FITUR & KETERANGAN -->
                                            <td class="py-2.5 px-4">
                                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                                    <span>{{ $item['label'] }}</span>
                                                </div>
                                                <div class="text-[10.5px] text-slate-500 dark:text-slate-400 pl-3">
                                                    Kode: <code class="text-slate-600 dark:text-slate-300 font-mono">{{ $itemKey }}</code>
                                                </div>
                                            </td>

                                            <!-- ACTION: BUKA -->
                                            <td class="py-2.5 px-3 text-center">
                                                @if($actions['buka'] ?? false)
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $itemKey }}.buka"
                                                           data-category="{{ $catKey }}"
                                                           data-item="{{ $itemKey }}"
                                                           data-action="buka"
                                                           :checked="hasPermission('{{ $itemKey }}.buka')"
                                                           @change="togglePermission('{{ $itemKey }}.buka', $event.target.checked)"
                                                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                                @else
                                                    <span class="text-slate-300 dark:text-slate-600 font-bold">-</span>
                                                @endif
                                            </td>

                                            <!-- ACTION: BARU -->
                                            <td class="py-2.5 px-3 text-center">
                                                @if($actions['baru'] ?? false)
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $itemKey }}.baru"
                                                           data-category="{{ $catKey }}"
                                                           data-item="{{ $itemKey }}"
                                                           data-action="baru"
                                                           :checked="hasPermission('{{ $itemKey }}.baru')"
                                                           @change="togglePermission('{{ $itemKey }}.baru', $event.target.checked)"
                                                           class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                                @else
                                                    <span class="text-slate-300 dark:text-slate-600 font-bold">-</span>
                                                @endif
                                            </td>

                                            <!-- ACTION: UBAH -->
                                            <td class="py-2.5 px-3 text-center">
                                                @if($actions['ubah'] ?? false)
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $itemKey }}.ubah"
                                                           data-category="{{ $catKey }}"
                                                           data-item="{{ $itemKey }}"
                                                           data-action="ubah"
                                                           :checked="hasPermission('{{ $itemKey }}.ubah')"
                                                           @change="togglePermission('{{ $itemKey }}.ubah', $event.target.checked)"
                                                           class="rounded border-slate-300 text-amber-600 focus:ring-amber-500 w-4 h-4 cursor-pointer">
                                                @else
                                                    <span class="text-slate-300 dark:text-slate-600 font-bold">-</span>
                                                @endif
                                            </td>

                                            <!-- ACTION: HAPUS -->
                                            <td class="py-2.5 px-3 text-center">
                                                @if($actions['hapus'] ?? false)
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $itemKey }}.hapus"
                                                           data-category="{{ $catKey }}"
                                                           data-item="{{ $itemKey }}"
                                                           data-action="hapus"
                                                           :checked="hasPermission('{{ $itemKey }}.hapus')"
                                                           @change="togglePermission('{{ $itemKey }}.hapus', $event.target.checked)"
                                                           class="rounded border-slate-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                                                @else
                                                    <span class="text-slate-300 dark:text-slate-600 font-bold">-</span>
                                                @endif
                                            </td>

                                            <!-- ACTION: KUNCI / KHUSUS -->
                                            <td class="py-2.5 px-3 text-center">
                                                @if($actions['kunci'] ?? false)
                                                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                        <input type="checkbox"
                                                               name="permissions[]"
                                                               value="{{ $itemKey }}.kunci"
                                                               data-category="{{ $catKey }}"
                                                               data-item="{{ $itemKey }}"
                                                               data-action="kunci"
                                                               :checked="hasPermission('{{ $itemKey }}.kunci')"
                                                               @change="togglePermission('{{ $itemKey }}.kunci', $event.target.checked)"
                                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer">
                                                        <span class="text-[10px] font-bold text-slate-600 dark:text-slate-300">Kunci No</span>
                                                    </label>
                                                @elseif(isset($actions['special']) && $actions['special'])
                                                    <input type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $itemKey }}"
                                                           data-category="{{ $catKey }}"
                                                           data-item="{{ $itemKey }}"
                                                           data-action="special"
                                                           :checked="hasPermission('{{ $itemKey }}')"
                                                           @change="togglePermission('{{ $itemKey }}', $event.target.checked)"
                                                           class="rounded border-slate-300 text-purple-600 focus:ring-purple-500 w-4 h-4 cursor-pointer">
                                                @else
                                                    <span class="text-slate-300 dark:text-slate-600 font-bold">-</span>
                                                @endif
                                            </td>

                                            <!-- ROW QUICK SELECT -->
                                            <td class="py-2.5 px-3 text-center">
                                                <button type="button"
                                                        @click="toggleRow('{{ $itemKey }}')"
                                                        class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600 transition-colors cursor-pointer">
                                                    Baris
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- STICKY BOTTOM SAVE BAR -->
                <div class="sticky bottom-4 mt-6 z-20">
                    <div class="bg-slate-900/95 backdrop-blur-md text-white p-4 rounded-2xl shadow-2xl border border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🛡️</span>
                            <div>
                                <div class="text-xs font-bold text-slate-300">
                                    Kelompok: <span class="font-black text-white text-sm">{{ $selectedGroup->name }}</span> ({{ $selectedGroup->code }})
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    Total Hak Akses Dipilih: <span class="font-extrabold text-emerald-400" x-text="permissionsCount()">0</span> Izin Operasional
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="reset"
                                    @click="resetToInitial()"
                                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-slate-600 transition-all cursor-pointer">
                                ↺ Reset Semula
                            </button>
                            <button type="submit"
                                    class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs rounded-xl shadow-lg shadow-emerald-900/30 flex items-center gap-2 transition-all active:scale-95 cursor-pointer">
                                <span>💾 SIMPAN HAK AKSES KELOMPOK [CTRL+S]</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: DAFTAR PENGGUNA & STAF KASIR                                   -->
    <!-- ===================================================================== -->
    <div x-show="activeTab === 'users'" class="space-y-4" x-cloak>
        
        <!-- TOOLBAR: SEARCH & ADD USER -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 flex-1">
                <!-- Search user -->
                <div class="relative min-w-[240px]">
                    <input type="text"
                           x-model="userSearchQuery"
                           placeholder="🔍 Cari nama atau email user..."
                           class="w-full text-xs py-2 pl-8 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter by role -->
                <select x-model="roleFilter"
                        class="text-xs font-semibold rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Peran (Role)</option>
                    @foreach($roles as $rVal => $rLabel)
                        <option value="{{ $rVal }}">{{ $rLabel }}</option>
                    @endforeach
                </select>

                <!-- Filter by user group -->
                <select x-model="groupFilter"
                        class="text-xs font-semibold rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-white py-2 px-3 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Kelompok Hak Akses</option>
                    @foreach($userGroups as $grp)
                        <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- BUTTON TAMBAH PENGGUNA -->
            <button type="button"
                    @click="openNewUserModal = true"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-sm flex items-center gap-1.5 transition-all cursor-pointer">
                <span>+ Tambah Pengguna / Kasir Baru</span>
            </button>
        </div>

        <!-- USERS TABLE -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-700/80 border-b border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-200 text-xs font-black uppercase tracking-wider">
                            <th class="py-3 px-4">Nama &amp; Akun</th>
                            <th class="py-3 px-3">Peran (Role)</th>
                            <th class="py-3 px-3">Kelompok Hak Akses (RBAC)</th>
                            <th class="py-3 px-3">Cabang Toko</th>
                            <th class="py-3 px-3 text-center">PIN Otorisasi</th>
                            <th class="py-3 px-3 text-center">Status Akun</th>
                            <th class="py-3 px-4 text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                        @forelse($users as $user)
                            <tr x-show="userMatchesSearch('{{ strtolower($user->name) }}', '{{ strtolower($user->email) }}', '{{ $user->role }}', '{{ $user->user_group_id }}')"
                                class="hover:bg-slate-50/80 dark:hover:bg-slate-700/40 transition-colors">
                                
                                <!-- NAMA & EMAIL -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0 shadow-sm">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ $user->name }}</span>
                                                @if($user->id === auth()->id())
                                                    <span class="px-1.5 py-0.2 bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 rounded text-[9.5px] font-extrabold">Anda</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                                                {{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- PERAN (ROLE) -->
                                <td class="py-3 px-3">
                                    <span class="px-2.5 py-1 rounded-md text-[10.5px] font-bold uppercase tracking-wider
                                        {{ $user->role === 'owner' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300' : '' }}
                                        {{ in_array($user->role, ['manager', 'store_head']) ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300' : '' }}
                                        {{ $user->role === 'cashier' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : '' }}
                                        {{ $user->role === 'warehouse' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/60 dark:text-sky-300' : '' }}
                                        {{ in_array($user->role, ['purchasing', 'finance', 'sales']) ? 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' : '' }}
                                    ">
                                        {{ $roles[$user->role] ?? $user->role }}
                                    </span>
                                </td>

                                <!-- KELOMPOK HAK AKSES -->
                                <td class="py-3 px-3">
                                    @if($user->userGroup)
                                        <a href="{{ route('settings.users.index', ['group_id' => $user->userGroup->id, 'tab' => 'groups']) }}"
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10.5px] font-extrabold bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-slate-700 dark:text-blue-300 transition-colors">
                                            <span>🛡️ {{ $user->userGroup->name }}</span>
                                        </a>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">(Default Role)</span>
                                    @endif
                                </td>

                                <!-- CABANG TOKO -->
                                <td class="py-3 px-3">
                                    @if($user->store)
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300">
                                            🏪 {{ $user->store->name }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-50 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                            🌐 Semua Cabang
                                        </span>
                                    @endif
                                </td>

                                <!-- PIN OTORISASI -->
                                <td class="py-3 px-3 text-center">
                                    @if($user->manager_pin)
                                        <span class="px-2 py-0.5 text-[10.5px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-900/60 dark:text-emerald-300">
                                            🟢 PIN Aktif
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-[10.5px] font-bold rounded-full bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                            ⚪ Belum Ada
                                        </span>
                                    @endif
                                </td>

                                <!-- STATUS AKUN -->
                                <td class="py-3 px-3 text-center">
                                    <form action="{{ route('settings.users.toggle-status', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-full text-[10.5px] font-extrabold transition-all cursor-pointer {{ $user->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 hover:bg-rose-200 dark:bg-rose-950/60 dark:text-rose-300' }}"
                                                title="Klik untuk ubah status aktif/nonaktif">
                                            {{ $user->is_active ? '🟢 Aktif' : '🔴 Nonaktif' }}
                                        </button>
                                    </form>
                                </td>

                                <!-- AKSI CEPAT -->
                                <td class="py-3 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit button -->
                                        <button type="button"
                                                @click="editUserById('{{ $user->id }}')"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-slate-700 rounded-lg transition-colors cursor-pointer"
                                                title="Edit Profil & Hak Akses">
                                            <span class="text-sm">✏️</span>
                                        </button>

                                        <!-- Set PIN modal -->
                                        <button type="button"
                                                @click="openSetPinModal('{{ $user->id }}', '{{ $user->name }}')"
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-700 rounded-lg transition-colors cursor-pointer"
                                                title="Set / Ganti PIN Otorisasi">
                                            <span class="text-sm">🔢</span>
                                        </button>

                                        <!-- Reset Password modal -->
                                        <button type="button"
                                                @click="openResetPasswordModal('{{ $user->id }}', '{{ $user->name }}')"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-slate-700 rounded-lg transition-colors cursor-pointer"
                                                title="Reset Password">
                                            <span class="text-sm">🔑</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    Belum ada data pengguna yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 1: BUAT KELOMPOK HAK AKSES BARU                                 -->
    <!-- ===================================================================== -->
    <div x-show="openNewGroupModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="openNewGroupModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🛡️ Buat Kelompok Hak Akses Baru</span>
                </h3>
                <button type="button" @click="openNewGroupModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form action="{{ route('settings.user-groups.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Kelompok (Unik):</label>
                    <input type="text" name="code" required placeholder="CONTOH: KASIR_CABANG"
                           class="w-full text-xs font-mono uppercase py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Kelompok:</label>
                    <input type="text" name="name" required placeholder="Kasir Cabang Solo"
                           class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi:</label>
                    <textarea name="description" rows="2" placeholder="Hak akses kasir transaksi harian..."
                              class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Salin Izin Awal Dari Template Preset (Opsional):</label>
                    <select name="preset" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">-- Mulai Kosong --</option>
                        @foreach($presets as $pKey => $presetData)
                            <option value="{{ $pKey }}">{{ $presetData['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="openNewGroupModal = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Simpan Kelompok
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 2: EDIT KELOMPOK HAK AKSES                                      -->
    <!-- ===================================================================== -->
    @if($selectedGroup)
        <div x-show="openEditGroupModal"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
                 @click.away="openEditGroupModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>✏️ Edit Kelompok: {{ $selectedGroup->name }}</span>
                    </h3>
                    <button type="button" @click="openEditGroupModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                </div>

                <form action="{{ route('settings.user-groups.update', $selectedGroup->id) }}" method="POST" class="space-y-3.5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Kelompok:</label>
                        <input type="text" name="name" value="{{ $selectedGroup->name }}" required
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi:</label>
                        <textarea name="description" rows="2"
                                  class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">{{ $selectedGroup->description }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" @click="openEditGroupModal = false"
                                class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                            Perbarui Nama
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ===================================================================== -->
    <!-- MODAL 3: TAMBAH PENGGUNA BARU                                         -->
    <!-- ===================================================================== -->
    <div x-show="openNewUserModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="openNewUserModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>👤 Tambah Pengguna / Kasir Baru</span>
                </h3>
                <button type="button" @click="openNewUserModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form action="{{ route('settings.users.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap:</label>
                        <input type="text" name="name" required placeholder="Ahmad Fadli"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email / Login ID:</label>
                        <input type="email" name="email" required placeholder="kasir2@sigma.test"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Password Baru:</label>
                        <input type="password" name="password" required minlength="6" placeholder="Minimal 6 karakter"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">PIN Otorisasi Manajer (Opsional):</label>
                        <input type="password" name="manager_pin" maxlength="6" placeholder="4-6 Digit Angka"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Peran Sistem (Role):</label>
                        <select name="role" required class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            @foreach($roles as $rVal => $rLabel)
                                <option value="{{ $rVal }}">{{ $rLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kelompok Hak Akses Ceklist:</label>
                        <select name="user_group_id" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="">-- Ikuti Default Role --</option>
                            @foreach($userGroups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }} ({{ $grp->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Cabang Penugasan:</label>
                    <select name="store_id" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">-- Semua Cabang (Pusat) --</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mulai Berlaku (Opsional):</label>
                        <input type="date" name="valid_from"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Berakhir Pada (Opsional):</label>
                        <input type="date" name="valid_until"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Status Akun Langsung Aktif</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="openNewUserModal = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Simpan Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 4: EDIT DATA PENGGUNA                                           -->
    <!-- ===================================================================== -->
    <div x-show="openEditUserModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-lg w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="openEditUserModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>✏️ Edit Pengguna: <span x-text="editingUser.name"></span></span>
                </h3>
                <button type="button" @click="openEditUserModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form :action="'{{ url('settings/users') }}/' + editingUser.id" method="POST" class="space-y-3.5">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap:</label>
                        <input type="text" name="name" x-model="editingUser.name" required
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email / Login ID:</label>
                        <input type="email" name="email" x-model="editingUser.email" required
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Peran Sistem (Role):</label>
                        <select name="role" x-model="editingUser.role" required class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            @foreach($roles as $rVal => $rLabel)
                                <option value="{{ $rVal }}">{{ $rLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kelompok Hak Akses Ceklist:</label>
                        <select name="user_group_id" x-model="editingUser.user_group_id" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <option value="">-- Ikuti Default Role --</option>
                            @foreach($userGroups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->name }} ({{ $grp->code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Cabang Penugasan:</label>
                    <select name="store_id" x-model="editingUser.store_id" class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="">-- Semua Cabang (Pusat) --</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Mulai Berlaku (Opsional):</label>
                        <input type="date" name="valid_from" x-model="editingUser.valid_from"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Berakhir Pada (Opsional):</label>
                        <input type="date" name="valid_until" x-model="editingUser.valid_until"
                               class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editingUser.is_active"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 cursor-pointer">
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Status Akun Aktif</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="openEditUserModal = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 5: RESET PASSWORD USER                                          -->
    <!-- ===================================================================== -->
    <div x-show="openResetPasswordModalState"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="openResetPasswordModalState = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🔑 Reset Password</span>
                </h3>
                <button type="button" @click="openResetPasswordModalState = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form :action="'{{ url('settings/users') }}/' + targetUserId + '/reset-password'" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mb-2">
                        Reset password untuk akun <strong class="text-slate-900 dark:text-white" x-text="targetUserName"></strong>:
                    </p>
                    <input type="password" name="new_password" required minlength="6" placeholder="Ketik password baru (min 6 digit)"
                           class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="openResetPasswordModalState = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 6: SET PIN OTORISASI MANAJER                                    -->
    <!-- ===================================================================== -->
    <div x-show="openSetPinModalState"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="openSetPinModalState = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🔢 Set PIN Otorisasi Manajer</span>
                </h3>
                <button type="button" @click="openSetPinModalState = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form :action="'{{ url('settings/users') }}/' + targetUserId + '/set-pin'" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mb-2">
                        Atur PIN otorisasi untuk <strong class="text-slate-900 dark:text-white" x-text="targetUserName"></strong>:
                    </p>
                    <input type="password" name="manager_pin" maxlength="6" placeholder="4-6 Digit Angka (Kosongkan jika hapus PIN)"
                           class="w-full text-xs text-center tracking-widest font-mono text-base py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <p class="text-[10.5px] text-slate-400 mt-1">Digunakan untuk otorisasi cetak ulang kasir, diskon darurat, retur &amp; selisih kas.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="openSetPinModalState = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Simpan PIN
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- ALPINE COMPONENT SCRIPT -->
<script>
const SIGMA_USERS_DATA = @json($users);
const SIGMA_USER_MGMT_CONFIG = {
    initialTab: @json(request()->query('tab', 'groups')),
    selectedGroupId: @json($selectedGroup?->id),
    groupPermissions: @json($selectedGroup?->permissions ?? []),
    isAdministratorGroup: @json($selectedGroup?->code === 'ADMINISTRATOR' || in_array('*', $selectedGroup?->permissions ?? [])),
};

function userManagementApp() {
    return {
        activeTab: SIGMA_USER_MGMT_CONFIG.initialTab || 'groups',
        selectedGroupId: SIGMA_USER_MGMT_CONFIG.selectedGroupId,
        activeCategory: 'all',
        searchQuery: '',
        userSearchQuery: '',
        roleFilter: '',
        groupFilter: '',
        isAdministrator: SIGMA_USER_MGMT_CONFIG.isAdministratorGroup,

        // Modal states
        openNewGroupModal: false,
        openEditGroupModal: false,
        openNewUserModal: false,
        openEditUserModal: false,
        openResetPasswordModalState: false,
        openSetPinModalState: false,

        // Editing states
        targetUserId: '',
        targetUserName: '',
        editingUser: {
            id: '',
            name: '',
            email: '',
            role: 'cashier',
            user_group_id: '',
            store_id: '',
            valid_from: '',
            valid_until: '',
            is_active: 1
        },

        // Active Group Permissions Dictionary
        permissions: new Set(SIGMA_USER_MGMT_CONFIG.groupPermissions || []),

        init() {
            // Listen for CTRL+S shortcut
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    if (this.activeTab === 'groups') {
                        e.preventDefault();
                        const form = document.getElementById('permissionsMatrixForm');
                        if (form) form.submit();
                    }
                }
            });
        },

        hasPermission(key) {
            if (this.isAdministrator) return true;
            return this.permissions.has(key);
        },

        togglePermission(key, checked) {
            if (checked) {
                this.permissions.add(key);
            } else {
                this.permissions.delete(key);
            }
        },

        permissionsCount() {
            if (this.isAdministrator) return 'Semua (Full)';
            return this.permissions.size;
        },

        toggleRow(itemKey) {
            const checkboxes = document.querySelectorAll(`input[data-item="${itemKey}"]`);
            if (!checkboxes.length) return;

            let allChecked = true;
            checkboxes.forEach(cb => {
                if (!cb.checked) allChecked = false;
            });

            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
                this.togglePermission(cb.value, !allChecked);
            });
        },

        toggleCategory(catKey, enable) {
            const checkboxes = document.querySelectorAll(`input[data-category="${catKey}"]`);
            checkboxes.forEach(cb => {
                cb.checked = enable;
                this.togglePermission(cb.value, enable);
            });
        },

        checkAllVisible() {
            const visibleCheckboxes = document.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
            visibleCheckboxes.forEach(cb => {
                // Check if row is visible
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = true;
                    this.togglePermission(cb.value, true);
                }
            });
        },

        uncheckAllVisible() {
            const visibleCheckboxes = document.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
            visibleCheckboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row && row.style.display !== 'none') {
                    cb.checked = false;
                    this.togglePermission(cb.value, false);
                }
            });
        },

        resetToInitial() {
            window.location.reload();
        },

        itemMatchesSearch(label, key) {
            if (!this.searchQuery.trim()) return true;
            const query = this.searchQuery.toLowerCase();
            return label.includes(query) || key.includes(query);
        },

        categoryMatchesSearch(catKey) {
            if (!this.searchQuery.trim()) return true;
            // Always show header if query has matches
            return true;
        },

        userMatchesSearch(name, email, role, groupId) {
            if (this.roleFilter && role !== this.roleFilter) return false;
            if (this.groupFilter && groupId !== this.groupFilter) return false;
            if (!this.userSearchQuery.trim()) return true;

            const q = this.userSearchQuery.toLowerCase();
            return name.includes(q) || email.includes(q);
        },

        editUserById(id) {
            const u = (SIGMA_USERS_DATA || []).find(x => x.id === id);
            if (!u) return;
            this.editingUser = {
                id: u.id,
                name: u.name,
                email: u.email,
                role: u.role,
                user_group_id: u.user_group_id || '',
                store_id: u.store_id || '',
                valid_from: u.valid_from ? u.valid_from.substring(0, 10) : '',
                valid_until: u.valid_until ? u.valid_until.substring(0, 10) : '',
                is_active: Boolean(u.is_active)
            };
            this.openEditUserModal = true;
        },

        openResetPasswordModal(id, name) {
            this.targetUserId = id;
            this.targetUserName = name;
            this.openResetPasswordModalState = true;
        },

        openSetPinModal(id, name) {
            this.targetUserId = id;
            this.targetUserName = name;
            this.openSetPinModalState = true;
        }
    };
}
</script>
@endsection
