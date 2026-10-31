@extends('layouts.app')

@section('title', 'Cek & Manajemen Poin Member Pelanggan')
@section('tab_title', 'Poin Member')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="customerPointsApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('sales.index') }}" class="hover:text-blue-700 font-semibold">Penjualan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Cek &amp; Manajemen Poin Member</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>⭐ Cek &amp; Manajemen Poin Member (Loyalty Rewards)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pantau akumulasi poin reward member belanja, konversi nilai rupiah, serta audit mutasi penukaran di kasir POS.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('sales.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>&larr; Hub Penjualan</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
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

    @if(session('error'))
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- SUMMARY METRICS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-lg shrink-0">
                👥
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Member Aktif</span>
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ number_format($totalRegisteredMembers) }} Orang</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-950/50 border border-yellow-200 dark:border-yellow-800 flex items-center justify-center text-lg shrink-0">
                ⭐
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Poin Beredar</span>
                <span class="text-lg font-black text-amber-600 dark:text-amber-400">{{ number_format($totalPointsCirculating) }} Pts</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-lg shrink-0">
                💰
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Nilai Rupiah Poin</span>
                <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalPointsValueRupiah, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-lg shrink-0">
                🔄
            </div>
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">Ditukar Bulan Ini</span>
                <span class="text-lg font-black text-blue-600 dark:text-blue-400">{{ number_format($pointsRedeemedThisMonth) }} Pts</span>
            </div>
        </div>
    </div>

    <!-- PANDUAN PROGRAM LOYALITAS -->
    <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-300 dark:border-amber-700/60 rounded-2xl p-3.5 text-xs text-amber-900 dark:text-amber-300 flex items-center justify-between flex-wrap gap-2 shadow-xs">
        <div class="flex items-center gap-2.5">
            <span class="text-xl">💡</span>
            <div>
                <strong class="font-black text-slate-900 dark:text-white">{{ $program->name }}</strong>
                <span class="text-slate-600 dark:text-slate-400 block text-[11px]">
                    Belanja <strong>Rp {{ number_format($program->spend_amount_per_point, 0, ',', '.') }}</strong> = 1 Poin • 
                    1 Poin = Potongan <strong>Rp {{ number_format($program->point_redemption_value, 0, ',', '.') }}</strong> • 
                    Minimal Penukaran: <strong>{{ $program->min_points_to_redeem }} Poin</strong>
                </span>
            </div>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[10.5px] font-extrabold bg-amber-500 text-white">
            Program Aktif
        </span>
    </div>

    <!-- TABEL UTAMA: MEMBER PELANGGAN & SALDO POIN -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Member &amp; Saldo Poin Aktif</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pencarian data member berdasarkan nama, nomor telepon, atau kode pelanggan</p>
            </div>

            <!-- SEARCH BAR -->
            <form method="GET" action="{{ route('sales.points.index') }}" class="flex items-center gap-2">
                <div class="relative w-full sm:w-64">
                    <input type="text"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Cari Nama / No HP / Kode..."
                           class="w-full text-xs py-2 pl-8 pr-3 rounded-xl border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 text-slate-900 dark:text-white">
                    <span class="absolute left-2.5 top-2.5 text-xs text-slate-400">🔍</span>
                </div>
                <button type="submit"
                        class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs">
                    Cari
                </button>
                @if(!empty($search))
                    <a href="{{ route('sales.points.index') }}"
                       class="px-3 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4">Member / Pelanggan</th>
                        <th class="py-3 px-4">Kontak / No HP</th>
                        <th class="py-3 px-4 text-center">Tier Harga</th>
                        <th class="py-3 px-4 text-center">Saldo Poin ⭐</th>
                        <th class="py-3 px-4 text-right">Setara Nilai Belanja</th>
                        <th class="py-3 px-4 text-center">Poin Lifetime</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors {{ $selectedCustomer?->id === $c->id ? 'bg-amber-50/60 dark:bg-amber-950/30' : '' }}">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-black text-slate-900 dark:text-white text-sm">{{ $c->name }}</div>
                                <span class="font-mono text-[10px] text-slate-400">{{ $c->code }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap font-mono">
                                {{ $c->phone ?: '-' }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                                    {{ $c->tier?->name ?? 'Retail' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                    <span>⭐</span>
                                    <span>{{ number_format($c->loyalty_points ?? 0) }} Pts</span>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format(($c->loyalty_points ?? 0) * $pointValueRupiah, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap font-mono text-slate-500 dark:text-slate-400">
                                {{ number_format($c->lifetime_points ?? 0) }} Pts
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click="openHistoryModal('{{ $c->id }}')"
                                            class="px-2.5 py-1 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg font-bold text-[11px] transition-colors cursor-pointer"
                                            title="Lihat Riwayat Mutasi Poin">
                                        📜 Riwayat
                                    </button>
                                    <button type="button"
                                            @click="openAdjustModal('{{ $c->id }}', '{{ addslashes($c->name) }}', {{ (int)$c->loyalty_points }})"
                                            class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-bold text-[11px] shadow-2xs transition-colors cursor-pointer"
                                            title="Atur / Sesuaikan Poin Member">
                                        ⚙️ Atur Poin
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                <span class="text-3xl block mb-2">⭐</span>
                                <p class="font-bold text-slate-600 dark:text-slate-400">Tidak ada data member yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-700">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    <!-- LOG MUTASI TERBARU SELURUH MEMBER (AUDIT TRAIL) -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-700">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">Audit Trail Mutasi Poin Terakhir (Seluruh Toko)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">Catatan setiap perolehan (Earn), penukaran di kasir (Redeem), dan penyesuaian manual</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-extrabold uppercase tracking-wider text-[11px]">
                        <th class="py-2.5 px-4">Waktu</th>
                        <th class="py-2.5 px-4">Pelanggan</th>
                        <th class="py-2.5 px-4 text-center">Jenis Mutasi</th>
                        <th class="py-2.5 px-4 text-center">Perubahan Poin</th>
                        <th class="py-2.5 px-4 text-center">Saldo Akhir</th>
                        <th class="py-2.5 px-4">No. Faktur / Referensi</th>
                        <th class="py-2.5 px-4">Keterangan / Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300 font-medium">
                    @forelse($recentMutations as $m)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/20">
                            <td class="py-2.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $m->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap font-bold text-slate-900 dark:text-white">
                                {{ $m->customer?->name ?? 'Member Dihapus' }}
                            </td>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap">
                                @if($m->type === 'EARN')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        + BELANJA (EARN)
                                    </span>
                                @elseif($m->type === 'REDEEM')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                        - TUKAR DISKON (REDEEM)
                                    </span>
                                @elseif($m->type === 'ADJUSTMENT')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300">
                                        ⚙️ PENYESUAIAN
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black bg-slate-100 text-slate-800">
                                        {{ $m->type }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap font-mono font-black text-sm {{ $m->points >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $m->points >= 0 ? '+'.number_format($m->points) : number_format($m->points) }} Pts
                            </td>
                            <td class="py-2.5 px-4 text-center whitespace-nowrap font-mono font-bold">
                                {{ number_format($m->balance_after ?? 0) }} Pts
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap font-mono text-blue-600 dark:text-blue-400">
                                {{ $m->transaction?->invoice_number ?: ($m->pos_transaction_id ? 'Faktur POS' : '-') }}
                            </td>
                            <td class="py-2.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $m->description ?: '-' }}
                                <span class="text-[10px] block text-slate-400">oleh: {{ $m->creator?->name ?? 'Kasir POS' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">
                                Belum ada riwayat mutasi poin yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentMutations->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-700">
                {{ $recentMutations->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL PENYESUAIAN / ADJUSTMENT POIN -->
    <div x-show="adjustModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="adjustModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                    <span>⚙️ Atur Poin Member</span>
                </h3>
                <button type="button" @click="adjustModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form action="{{ route('sales.points.adjust') }}" method="POST" class="space-y-3.5">
                @csrf
                <input type="hidden" name="customer_id" :value="targetCustomerId">

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 mb-1">Nama Member:</label>
                    <div class="text-sm font-black text-slate-900 dark:text-white" x-text="targetCustomerName"></div>
                    <div class="text-[11px] text-amber-600 dark:text-amber-400 font-bold mt-0.5">
                        Saldo Saat Ini: <span x-text="targetCustomerPoints"></span> Poin
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Jumlah Perubahan Poin (+ Tambah / - Kurang):
                    </label>
                    <input type="number" name="points_delta" required
                           placeholder="Contoh: 50 atau -20"
                           class="w-full text-center font-mono text-base font-black py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <span class="text-[10px] text-slate-400 block mt-1">Gunakan angka positif untuk menambah bonus, angka negatif (-) untuk memotong.</span>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan Penyesuaian:</label>
                    <input type="text" name="reason" required maxlength="255"
                           placeholder="Contoh: Bonus Ulang Tahun / Kompensasi Transaksi"
                           class="w-full text-xs py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="adjustModalOpen = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Simpan Penyesuaian
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DETAIL RIWAYAT INDIVIDU -->
    <div x-show="historyModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-2xl w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="historyModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <div>
                    <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                        <span>📜 Riwayat Poin Member: <span x-text="activeDetailCustomer?.name"></span></span>
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Saldo: <strong class="text-amber-600" x-text="activeDetailCustomer?.loyalty_points"></strong> Pts (Rp <span x-text="formatRupiah(activeDetailCustomer?.point_rupiah_value)"></span>)
                    </p>
                </div>
                <button type="button" @click="historyModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">&times;</button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700 border border-slate-200 dark:border-slate-700 rounded-xl">
                <template x-if="activeDetailMutations.length === 0">
                    <div class="p-6 text-center text-slate-400 text-xs">Belum ada catatan mutasi poin.</div>
                </template>
                <template x-for="m in activeDetailMutations" :key="m.id">
                    <div class="p-3 flex items-center justify-between hover:bg-slate-50/80 dark:hover:bg-slate-700/30 text-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold font-mono text-slate-900 dark:text-white" x-text="m.invoice_number || 'Penyesuaian'"></span>
                                <span class="text-[10px] px-1.5 py-0.2 rounded font-bold"
                                      :class="m.type === 'EARN' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                      x-text="m.type"></span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-0.5" x-text="m.created_at + ' • ' + (m.description || '-')"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-black text-sm"
                                 :class="m.points >= 0 ? 'text-emerald-600' : 'text-rose-600'"
                                 x-text="(m.points >= 0 ? '+' : '') + m.points + ' Pts'"></div>
                            <div class="text-[10px] text-slate-400 font-mono" x-text="'Saldo: ' + m.balance_after + ' Pts'"></div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-2">
                <button type="button" @click="historyModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<!-- SCRIPT LOGIC -->
<script>
function customerPointsApp() {
    return {
        adjustModalOpen: false,
        historyModalOpen: false,
        targetCustomerId: '',
        targetCustomerName: '',
        targetCustomerPoints: 0,
        activeDetailCustomer: null,
        activeDetailMutations: [],

        openAdjustModal(id, name, points) {
            this.targetCustomerId = id;
            this.targetCustomerName = name;
            this.targetCustomerPoints = points;
            this.adjustModalOpen = true;
        },

        async openHistoryModal(id) {
            try {
                const res = await fetch('{{ url("sales/points/lookup") }}/' + id);
                const data = await res.json();
                if (data.success) {
                    this.activeDetailCustomer = data.customer;
                    this.activeDetailMutations = data.mutations;
                    this.historyModalOpen = true;
                }
            } catch (e) {
                alert('Gagal mengambil data riwayat poin.');
            }
        },

        formatRupiah(num) {
            return (num || 0).toLocaleString('id-ID');
        }
    };
}
</script>
@endsection
