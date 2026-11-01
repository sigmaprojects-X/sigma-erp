@extends('layouts.pwa_opname')

@section('title', 'SIGMA Warehouse PWA - Dashboard Opname Fisik')
@section('header_subtitle', ($userStore?->name ?? 'Gudang Pusat') . ' • ' . $user->name)

@section('content')
<div class="space-y-5">

    <!-- ALERT NOTIFICATIONS -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-black">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 rounded-2xl text-rose-800 dark:text-rose-300 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <span class="text-base">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-black">&times;</button>
        </div>
    @endif

    <!-- 1. USER & STORE HERO CARD + TOMBOL BUAT SESI BARU -->
    <div class="bg-white dark:bg-slate-800 p-5 md:p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs transition-colors">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3.5">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center text-2xl shrink-0 shadow-xs">
                    🏬
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base md:text-lg font-bold text-slate-900 dark:text-white leading-tight">
                            {{ $userStore?->name ?? 'Cabang Utama' }}
                        </h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 uppercase">
                            {{ $user->role }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Kode: <span class="font-mono font-semibold">{{ $userStore?->code ?? 'STR-001' }}</span> &bull; Petugas: <strong class="text-slate-700 dark:text-slate-300">{{ $user->name }}</strong>
                    </p>
                    @if(isset($availableStores) && $availableStores->count() > 1 && in_array($user->role, ['owner', 'manager', 'store_head']))
                        <form method="GET" action="{{ route('warehouse.opname-pwa.index') }}" class="mt-2 flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Lokasi:</span>
                            <select name="cabang" onchange="this.form.submit()" class="text-xs font-bold bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg px-2 py-1 text-slate-800 dark:text-slate-200 focus:outline-hidden">
                                @foreach($availableStores as $st)
                                    <option value="{{ $st->code }}" {{ ($userStore?->id === $st->id) ? 'selected' : '' }}>
                                        🏬 {{ $st->name }} ({{ $st->code }})
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                </div>
            </div>

            <!-- TOMBOL UTAMA BUAT SESI BARU -->
            <button type="button"
                    onclick="openCreateModal()"
                    class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer shrink-0">
                <span class="text-sm">➕</span>
                <span>Buat Sesi Opname Baru</span>
            </button>
        </div>
    </div>

    <!-- 2. PANDUAN RINGKAS 3 LANGKAH (SOP BLIND OPNAME) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-sm shrink-0">
                1
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pindai / Cari Barang</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Gunakan kamera HP atau ketik nama/barcode barang di rak.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black text-sm shrink-0">
                2
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Input Hitungan Fisik</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Stok sistem dirahasiakan (*Blind Opname*) agar hasil audit jujur.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200/80 dark:border-slate-700/80 shadow-xs flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-sm shrink-0">
                3
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Kirim ke Manager</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Setelah selesai, kirim hasil hitungan untuk diverifikasi selisihnya.</p>
            </div>
        </div>
    </div>

    <!-- 3. SESI OPNAME AKTIF (SEDANG DIHITUNG) -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                    <span>📋</span> Sesi Opname Aktif (Sedang Dihitung)
                </h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                    {{ $activeSessions->count() }} Sesi
                </span>
            </div>

            @if($activeSessions->count() > 0)
                <button type="button" onclick="openCreateModal()" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                    + Tambah Sesi
                </button>
            @endif
        </div>

        @if($activeSessions->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($activeSessions as $opname)
                    @php
                        $counted = $opname->counted_items_count ?? 0;
                        $total = $opname->items_count ?: 1;
                        $pct = $opname->progress_percent ?? 0;
                    @endphp
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <!-- Header Sesi Card -->
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                                        {{ $opname->opname_number }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                                        ⏳ IN PROGRESS
                                    </span>
                                </div>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                    {{ date('d/m/Y', strtotime($opname->opname_date)) }}
                                </span>
                            </div>

                            <!-- Catatan / Nama Rak -->
                            <div class="mb-3">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ $opname->notes ?: 'Opname Seluruh Barang Toko' }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Dibuat oleh: <span class="font-medium text-slate-700 dark:text-slate-300">{{ $opname->creator->name ?? 'Petugas Toko' }}</span>
                                </div>
                            </div>

                            <!-- Visual Progress Bar -->
                            <div class="bg-slate-50 dark:bg-slate-900/60 rounded-xl p-3 border border-slate-100 dark:border-slate-750 mb-4">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">Progres Hitung Fisik</span>
                                    <span class="font-mono font-bold text-blue-600 dark:text-blue-400">
                                        {{ $counted }} / {{ $opname->items_count }} SKU ({{ $pct }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Aksi Bawah -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
                            <a href="{{ route('warehouse.opname-pwa.session', $opname->id) }}"
                               class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-xs hover:shadow-sm flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                                <span>📱</span>
                                <span>Mulai Hitung di Rak Sekarang</span>
                                <span>→</span>
                            </a>

                            <!-- Tombol Batalkan Sesi -->
                            <form action="{{ route('warehouse.opname-pwa.cancel-session', $opname->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Apakah Anda yakin ingin membatalkan sesi opname {{ $opname->opname_number }}? Seluruh hitungan sesi ini akan dihapus.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        title="Batalkan Sesi Ini"
                                        class="p-2.5 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 dark:bg-slate-700 dark:hover:bg-rose-950/60 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-600 transition-colors cursor-pointer">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- EMPTY STATE CARD -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 text-center shadow-xs">
                <div class="w-16 h-16 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-3xl mx-auto mb-3">
                    📋
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Tidak Ada Sesi Opname yang Aktif</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
                    Saat ini belum ada sesi hitung fisik barang di toko Anda. Silakan klik tombol di bawah untuk memulai sesi opname rak atau seluruh toko.
                </p>
                <div class="mt-4">
                    <button type="button"
                            onclick="openCreateModal()"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow-md transition-all inline-flex items-center gap-2 cursor-pointer">
                        <span>➕</span>
                        <span>Mulai Sesi Opname Sekarang</span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- 4. SESI TERBARU / RIWAYAT SESI SEBELUMNYA -->
    @if($recentSessions->count() > 0)
        <div class="space-y-3 pt-2">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                <span>🕒</span> Sesi Terbaru Lainnya
            </h3>

            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl divide-y divide-slate-100 dark:divide-slate-700 overflow-hidden shadow-xs">
                @foreach($recentSessions as $r)
                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $r->opname_number }}</span>
                                <span class="text-slate-400">&bull;</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ date('d/m/Y', strtotime($r->opname_date)) }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $r->notes ?: 'Opname Fisik' }} ({{ $r->items_count }} SKU) &bull; Oleh: {{ $r->creator->name ?? 'Kasir/Staf' }}
                            </div>
                        </div>

                        <div>
                            @if($r->status === 'PENDING_APPROVAL')
                                <span class="px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40">
                                    ⏳ Menunggu Review Manager
                                </span>
                            @elseif($r->status === 'COMPLETED')
                                <span class="px-2.5 py-1 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                                    ✅ Selesai Rekonsiliasi
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

<!-- ============================================================== -->
<!-- MODAL POPUP: BUAT SESI OPNAME BARU                             -->
<!-- ============================================================== -->
<div id="createOpnameModal" style="display: none;" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-xl animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">📋</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mulai Sesi Opname Baru</h3>
            </div>
            <button type="button" onclick="closeCreateModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold text-lg cursor-pointer">&times;</button>
        </div>

        <form action="{{ route('warehouse.opname-pwa.store-session') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Cabang Toko Info -->
            <div>
                <label for="modal_store_id" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Lokasi Toko / Cabang</label>
                @if(isset($availableStores) && $availableStores->count() > 1 && in_array($user->role, ['owner', 'manager', 'store_head']))
                    <select name="store_id" id="modal_store_id" class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-750 border border-slate-200 dark:border-slate-600 text-xs font-bold text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                        @foreach($availableStores as $st)
                            <option value="{{ $st->id }}" {{ ($userStore?->id === $st->id) ? 'selected' : '' }}>
                                🏬 {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="store_id" value="{{ $userStore?->id }}">
                    <div class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-700/60 border border-slate-200 dark:border-slate-600 text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                        <span>{{ $userStore?->name ?? 'Cabang Utama' }}</span>
                        <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ $userStore?->code ?? 'STR-001' }}</span>
                    </div>
                @endif
            </div>

            <!-- Tanggal Opname -->
            <div>
                <label for="opname_date" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Tanggal Hitung</label>
                <input type="date"
                       name="opname_date"
                       id="opname_date"
                       value="{{ now()->toDateString() }}"
                       class="w-full px-3.5 py-2 rounded-xl bg-white dark:bg-slate-750 border border-slate-200 dark:border-slate-600 text-xs font-bold text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Cakupan Rak (Cycle Counting Selection) -->
            <div>
                <label for="rack_location" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Cakupan Rak (Cycle Count)</label>
                <select name="rack_location"
                        id="rack_location"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-750 border border-slate-200 dark:border-slate-600 text-xs font-bold text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    <option value="">📍 Seluruh Toko (Semua Rak)</option>
                    @if(isset($availableRacks) && count($availableRacks) > 0)
                        @foreach($availableRacks as $rk)
                            <option value="{{ $rk }}">📦 Khusus Rak: {{ $rk }}</option>
                        @endforeach
                    @endif
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Pilih rak spesifik jika hanya ingin menghitung sebagian barang (*Cycle Counting*).</p>
            </div>

            <!-- Catatan Sesi / Lokasi Rak -->
            <div>
                <label for="notes" class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">Catatan / Detail Tambahan</label>
                <input type="text"
                       name="notes"
                       id="notes"
                       placeholder="Contoh: Audit Rutin Mingguan, Rak Sembako Depan"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-white dark:bg-slate-750 border border-slate-200 dark:border-slate-600 text-xs font-medium text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                <p class="text-[11px] text-slate-400 mt-1">Beri label agar staf lain tahu konteks penghitungan sesi ini.</p>
            </div>

            <!-- Notice Blind Opname -->
            <div class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/60 rounded-xl text-[11px] text-blue-900 dark:text-blue-300 flex items-start gap-2">
                <span class="text-sm">🔒</span>
                <span>Sistem akan membekukan data stok seluruh produk toko saat ini untuk diaudit secara buta (*Blind Count*).</span>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="pt-2 flex items-center gap-2">
                <button type="button"
                        onclick="closeCreateModal()"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 font-bold text-xs cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-sm hover:shadow-md transition-all cursor-pointer">
                    Mulai Sesi Sekarang →
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCreateModal() {
        document.getElementById('createOpnameModal').style.display = 'flex';
        setTimeout(() => document.getElementById('notes')?.focus(), 100);
    }

    function closeCreateModal() {
        document.getElementById('createOpnameModal').style.display = 'none';
    }

    // Close modal on Escape key
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCreateModal();
        }
    });
</script>
@endpush
@endsection
