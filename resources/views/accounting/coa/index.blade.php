@extends('layouts.app')

@section('title', 'Bagan Akun Perkiraan (COA)')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Bagan Akun (Chart of Accounts)</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📑 Bagan Akun Perkiraan (COA)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Kelola struktur akun buku besar, saldo normal, dan pantau saldo berjalan riil secara terintegrasi.
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <button type="button" onclick="document.getElementById('modalAddAccount').classList.remove('hidden')"
                    class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Tambah Akun Baru</span>
            </button>
            <a href="{{ route('accounting.journals.create') }}"
               class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Input Jurnal Biaya</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    <!-- 5 Ringkasan Saldo Akun -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Total Aset (1xxx)</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($typeSummaries['ASSET'] ?? 0, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Kas, Bank, Piutang, Stok, Aset</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Total Kewajiban (2xxx)</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($typeSummaries['LIABILITY'] ?? 0, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Hutang Usaha & Pajak</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400">Total Ekuitas (3xxx)</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($typeSummaries['EQUITY'] ?? 0, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Modal Pemilik & Prive</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Pendapatan (4xxx)</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($typeSummaries['REVENUE'] ?? 0, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">Penjualan POS & Toko</span>
        </div>

        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Total Beban (5xxx-6xxx)</span>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">
                Rp {{ number_format($typeSummaries['EXPENSE'] ?? 0, 0, ',', '.') }}
            </div>
            <span class="text-[10px] text-slate-400">HPP, Operasional & Gaji</span>
        </div>
    </div>

    <!-- Filter & Toolbar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Filter Tabs -->
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ route('accounting.coa.index') }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ empty($currentType) ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                Semua ({{ count($accounts) }})
            </a>
            @foreach(['ASSET' => 'Aset', 'LIABILITY' => 'Kewajiban', 'EQUITY' => 'Ekuitas', 'REVENUE' => 'Pendapatan', 'EXPENSE' => 'Beban'] as $typeKey => $typeLabel)
            <a href="{{ route('accounting.coa.index', ['type' => $typeKey, 'search' => $search]) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $currentType === $typeKey ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                {{ $typeLabel }}
            </a>
            @endforeach
        </div>

        <!-- Search Bar -->
        <form method="GET" action="{{ route('accounting.coa.index') }}" class="flex items-center space-x-2">
            @if($currentType)
            <input type="hidden" name="type" value="{{ $currentType }}">
            @endif
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari kode / nama akun..."
                       class="w-64 pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-900">Cari</button>
            @if($search)
            <a href="{{ route('accounting.coa.index', ['type' => $currentType]) }}" class="text-xs text-rose-500 hover:underline">Reset</a>
            @endif
        </form>
    </div>

    <!-- Table Bagan Akun -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">Kode Akun</th>
                        <th class="py-3 px-4">Nama Akun Perkiraan</th>
                        <th class="py-3 px-4">Klasifikasi</th>
                        <th class="py-3 px-4">Saldo Normal</th>
                        <th class="py-3 px-4 text-right">Total Debit</th>
                        <th class="py-3 px-4 text-right">Total Kredit</th>
                        <th class="py-3 px-4 text-right">Saldo Berjalan</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($accounts as $acc)
                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $acc['code'] }}
                        </td>
                        <td class="py-3 px-4 text-slate-900 dark:text-white font-bold">
                            {{ $acc['name'] }}
                            @if(!empty($acc['description']))
                            <div class="text-[10px] text-slate-400 font-normal">{{ $acc['description'] }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $badgeColors = [
                                    'ASSET' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200',
                                    'LIABILITY' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border-rose-200',
                                    'EQUITY' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200',
                                    'REVENUE' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200',
                                    'EXPENSE' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeColors[$acc['type']] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $acc['type'] }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-semibold text-slate-600 dark:text-slate-400">{{ $acc['normal_balance'] }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                            Rp {{ number_format($acc['total_debit'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600 dark:text-slate-400">
                            Rp {{ number_format($acc['total_credit'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold {{ $acc['current_balance'] >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">
                            Rp {{ number_format($acc['current_balance'], 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($acc['is_active'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">Aktif</span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">Nonaktif</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <button type="button"
                                    onclick="openEditModal('{{ $acc['id'] }}', '{{ $acc['code'] }}', '{{ addslashes($acc['name']) }}', '{{ addslashes($acc['description'] ?? '') }}', {{ $acc['is_active'] ? 'true' : 'false' }})"
                                    class="px-2 py-1 text-[11px] font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            Tidak ada akun perkiraan yang sesuai dengan filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Akun Baru -->
<div id="modalAddAccount" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">+ Tambah Akun Perkiraan Baru</h3>
            <button type="button" onclick="document.getElementById('modalAddAccount').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form action="{{ route('accounting.coa.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Akun <span class="text-rose-500">*</span></label>
                <input type="text" name="code" required placeholder="Contoh: 6003-001 (Beban Listrik)"
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <span class="text-[10px] text-slate-400">Gunakan format baku 1xxx (Aset), 2xxx (Kewajiban), 3xxx (Modal), 4xxx (Pendapatan), 5xxx-6xxx (Beban).</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Akun <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="Contoh: Beban Listrik, Air & Internet"
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Klasifikasi Akun <span class="text-rose-500">*</span></label>
                    <select name="type" id="newAccountType" onchange="autoSetNormalBalance()" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="ASSET">ASSET (Aset / Harta)</option>
                        <option value="LIABILITY">LIABILITY (Kewajiban / Hutang)</option>
                        <option value="EQUITY">EQUITY (Modal / Ekuitas)</option>
                        <option value="REVENUE">REVENUE (Pendapatan)</option>
                        <option value="EXPENSE" selected>EXPENSE (Beban Operasional)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Saldo Normal <span class="text-rose-500">*</span></label>
                    <select name="normal_balance" id="newNormalBalance" required
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="DEBIT" selected>DEBIT</option>
                        <option value="CREDIT">CREDIT</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Memo (Opsional)</label>
                <textarea name="description" rows="2" placeholder="Catatan peruntukan akun..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('modalAddAccount').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Batal</button>
                <button type="submit"
                        class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20">Simpan Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Akun -->
<div id="modalEditAccount" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Akun Perkiraan</h3>
            <button type="button" onclick="document.getElementById('modalEditAccount').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form id="editAccountForm" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Akun</label>
                <input type="text" id="editAccountCode" disabled
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-500 font-mono">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Akun <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="editAccountName" required
                       class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Deskripsi</label>
                <textarea name="description" id="editAccountDescription" rows="2"
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <div class="flex items-center space-x-2">
                <input type="checkbox" name="is_active" id="editAccountActive" value="1"
                       class="w-4 h-4 text-blue-600 rounded-sm border-slate-300 focus:ring-blue-500">
                <label for="editAccountActive" class="text-xs font-bold text-slate-700 dark:text-slate-300">Status Akun Aktif</label>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('modalEditAccount').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Batal</button>
                <button type="submit"
                        class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20">Perbarui Akun</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoSetNormalBalance() {
    const type = document.getElementById('newAccountType').value;
    const balanceSelect = document.getElementById('newNormalBalance');
    if (type === 'ASSET' || type === 'EXPENSE') {
        balanceSelect.value = 'DEBIT';
    } else {
        balanceSelect.value = 'CREDIT';
    }
}

function openEditModal(id, code, name, description, isActive) {
    const form = document.getElementById('editAccountForm');
    form.action = `/accounting/coa/${id}`;
    document.getElementById('editAccountCode').value = code;
    document.getElementById('editAccountName').value = name;
    document.getElementById('editAccountDescription').value = description;
    document.getElementById('editAccountActive').checked = isActive;
    document.getElementById('modalEditAccount').classList.remove('hidden');
}
</script>
@endsection
