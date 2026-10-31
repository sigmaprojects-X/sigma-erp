@extends('layouts.app')

@section('title', 'Program Loyalitas & Poin Pelanggan')
@section('tab_title', 'Poin Reward')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span class="font-semibold">Pelanggan &amp; Member</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Loyalty Points</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🎁 Program Loyalitas &amp; Poin Reward Pelanggan</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customers.rewards.index') }}"
               class="px-3.5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>🎁 Katalog Hadiah</span>
            </a>
            <form action="{{ route('customers.loyalty.expire') }}" method="POST" onsubmit="return confirm('Jalankan pembersihan poin yang telah kedaluwarsa?')">
                @csrf
                <button type="submit" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold text-xs rounded-xl border border-slate-300 dark:border-slate-700 transition-all cursor-pointer">
                    ⌛ Cek Poin Kedaluwarsa
                </button>
            </form>
            <a href="{{ route('customers.loyalty.settings') }}"
               class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <span>⚙️ Aturan Nilai Poin</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-bold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- BANNER ATURAN POIN AKTIF -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 rounded-2xl p-4 text-white shadow-md flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-white/20 text-white uppercase tracking-wider">
                    {{ $program->is_active ? '● AKTIF' : '○ NONAKTIF' }}
                </span>
                <h2 class="text-base font-black">{{ $program->name }}</h2>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-semibold mt-2 text-purple-100">
                <div class="flex items-center gap-1.5">
                    <span>💳 Belanja Tiap:</span>
                    <b class="text-white">Rp {{ number_format($program->spend_amount_per_point, 0, ',', '.') }} = 1 Poin</b>
                </div>
                <div>•</div>
                <div class="flex items-center gap-1.5">
                    <span>💵 Nilai Tukar Diskon:</span>
                    <b class="text-white">1 Poin = Rp {{ number_format($program->point_redemption_value, 0, ',', '.') }}</b>
                </div>
                <div>•</div>
                <div class="flex items-center gap-1.5">
                    <span>🎯 Minimal Penukaran:</span>
                    <b class="text-white">{{ $program->min_points_to_redeem }} Poin</b>
                </div>
            </div>
        </div>

        <div class="text-right shrink-0">
            <a href="{{ route('customers.loyalty.settings') }}"
               class="px-3.5 py-1.5 bg-white text-purple-700 hover:bg-purple-50 font-black text-xs rounded-xl shadow-xs transition-colors">
                Ubah Aturan
            </a>
        </div>
    </div>

    <!-- FILTER & PENCARIAN -->
    <form method="GET" action="{{ route('customers.loyalty.index') }}" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 shadow-xs flex flex-wrap items-center gap-3 text-xs">
        <div class="flex-1 min-w-[240px]">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari nama member, nomor telepon, atau kode..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl shadow-xs cursor-pointer">
            Cari Member
        </button>
        @if(request()->filled('search'))
            <a href="{{ route('customers.loyalty.index') }}" class="px-3 py-2 text-slate-500 hover:text-slate-700 font-semibold text-xs">
                Reset
            </a>
        @endif
    </form>

    <!-- KONTEN: TABEL SALDO POIN PELANGGAN & MUTASI TERAKHIR -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- TABEL SALDO POIN PELANGGAN (2 KOLOM) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
            <div class="p-3 border-b border-slate-100 dark:border-slate-800 font-extrabold text-xs uppercase tracking-wider text-slate-400">
                Peringkat &amp; Saldo Poin Pelanggan (Member)
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold">
                        <tr>
                            <th class="p-3">Pelanggan</th>
                            <th class="p-3">Tier</th>
                            <th class="p-3 text-right">Saldo Poin Aktif</th>
                            <th class="p-3 text-right">Potensi Diskon</th>
                            <th class="p-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                        @forelse($customers as $c)
                            @php
                                $points = (int) $c->loyalty_points;
                                $discountVal = $points * (float) $program->point_redemption_value;
                            @endphp
                            <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $c->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $c->code }} • {{ $c->phone ?: '-' }}</div>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                        {{ $c->tier->name ?? 'RETAIL' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right font-mono font-black text-purple-600 dark:text-purple-400 text-sm">
                                    {{ number_format($points) }} Pts
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    Rp {{ number_format($discountVal, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-center">
                                    <a href="{{ route('customers.loyalty.ledger', $c->id) }}"
                                       class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 font-bold rounded-lg text-xs transition-colors">
                                        Buku Besar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-400">
                                    Belum ada data pelanggan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($customers->hasPages())
                <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

        <!-- RIWAYAT MUTASI TERAKHIR (1 KOLOM) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs space-y-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">
                10 Mutasi Poin Terakhir
            </h3>

            <div class="space-y-2.5 overflow-y-auto max-h-[500px] pr-1">
                @forelse($recentMutations as $mut)
                    <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/30 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900 dark:text-white truncate max-w-[140px]">
                                {{ $mut->customer->name ?? '-' }}
                            </span>
                            @if($mut->type === 'EARN')
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-emerald-100 text-emerald-800">
                                    +{{ number_format($mut->points) }} Pts
                                </span>
                            @elseif($mut->type === 'REDEEM')
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-rose-100 text-rose-800">
                                    {{ number_format($mut->points) }} Pts
                                </span>
                            @else
                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-amber-100 text-amber-800">
                                    {{ $mut->points > 0 ? '+'.$mut->points : $mut->points }} Pts
                                </span>
                            @endif
                        </div>
                        <div class="text-[11px] text-slate-500">
                            {{ $mut->description }}
                        </div>
                        <div class="text-[10px] text-slate-400 flex items-center justify-between pt-0.5">
                            <span>{{ $mut->created_at->format('d/m/Y H:i') }}</span>
                            <span class="font-mono">Sisa: {{ number_format($mut->balance_after) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-slate-400 text-xs">
                        Belum ada aktivitas mutasi poin.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
