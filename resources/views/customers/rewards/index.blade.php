@extends('layouts.app')

@section('title', 'Katalog Hadiah Poin Pelanggan')
@section('tab_title', 'Tukar Hadiah Member')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- HEADER & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.loyalty.index') }}" class="hover:underline">Poin Reward</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Katalog Hadiah</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🎁 Katalog Hadiah Poin &amp; Tukar Merchandise</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customers.rewards.create') }}"
               class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                <span>➕ Tambah Hadiah Baru</span>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- DAFTAR KATALOG HADIAH (2 KOLOM) -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500">Pilihan Hadiah Poin Tersedia</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse($rewards as $reward)
                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="font-mono text-[10px] text-purple-600 dark:text-purple-400 font-extrabold">{{ $reward->code }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black {{ $reward->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $reward->is_active ? 'AKTIF' : 'NONAKTIF' }}
                                </span>
                            </div>
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $reward->name }}</h3>
                            @if($reward->description)
                                <p class="text-[11px] text-slate-500 mt-1">{{ $reward->description }}</p>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Butuh Poin</div>
                                <div class="text-base font-black text-purple-600 dark:text-purple-400">{{ number_format($reward->points_required, 0, ',', '.') }} <span class="text-xs">PTS</span></div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-slate-400 font-bold uppercase">Sisa Stok</div>
                                <div class="text-sm font-extrabold {{ $reward->stock_quantity > 0 ? 'text-slate-800 dark:text-slate-200' : 'text-rose-500' }}">
                                    {{ (float)$reward->stock_quantity }} unit
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 py-8 text-center text-slate-400 text-xs font-semibold bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                        Belum ada hadiah di katalog. Tambahkan hadiah menarik untuk member Anda!
                    </div>
                @endforelse
            </div>

            @if($rewards->hasPages())
                <div>{{ $rewards->links() }}</div>
            @endif
        </div>

        <!-- FORM TUKAR HADIAH MEMBER (1 KOLOM) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs h-fit space-y-3">
            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>🔄 Form Klaim / Tukar Hadiah</span>
            </h2>
            <p class="text-[11px] text-slate-500">Tukarkan poin member yang terkumpul dengan hadiah fisik langsung di meja kasir/customer service.</p>

            <form action="{{ route('customers.rewards.redeem') }}" method="POST" class="space-y-3 pt-2">
                @csrf

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Pelanggan / Member *</label>
                    <select name="customer_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                        <option value="">-- Pilih Member --</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}">{{ $cust->name }} (Poin: {{ number_format($cust->loyalty_points, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Hadiah *</label>
                    <select name="loyalty_reward_id" required class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                        <option value="">-- Pilih Hadiah --</option>
                        @foreach($rewards as $r)
                            @if($r->is_active && $r->stock_quantity > 0)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ number_format($r->points_required, 0, ',', '.') }} Pts - Stok: {{ (float)$r->stock_quantity }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Jumlah Hadiah (Unit) *</label>
                    <input type="number" name="quantity" value="1" min="1" step="1" required
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan</label>
                    <input type="text" name="notes" placeholder="Opsional (misal: warna/ukuran)"
                           class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                </div>

                <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                    🎁 Proses Penukaran Poin
                </button>
            </form>
        </div>

    </div>

    <!-- RIWAYAT KLAIM TERAKHIR -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs mt-4">
        <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Riwayat Klaim Hadiah Terakhir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/50 dark:bg-slate-800/30 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-2.5 px-4">No. Klaim</th>
                        <th class="py-2.5 px-4">Tanggal</th>
                        <th class="py-2.5 px-4">Pelanggan</th>
                        <th class="py-2.5 px-4">Hadiah</th>
                        <th class="py-2.5 px-4 text-center">Qty</th>
                        <th class="py-2.5 px-4 text-right">Poin Digunakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($recentRedemptions as $redemption)
                        <tr>
                            <td class="py-2.5 px-4 font-mono font-bold text-purple-600 dark:text-purple-400">{{ $redemption->redemption_number }}</td>
                            <td class="py-2.5 px-4 text-slate-500">{{ $redemption->redemption_date->format('d/m/Y H:i') }}</td>
                            <td class="py-2.5 px-4 font-bold">{{ $redemption->customer?->name }}</td>
                            <td class="py-2.5 px-4">{{ $redemption->loyaltyReward?->name }}</td>
                            <td class="py-2.5 px-4 text-center font-bold">{{ (float)$redemption->quantity }}</td>
                            <td class="py-2.5 px-4 text-right font-black text-rose-600">-{{ number_format($redemption->points_used, 0, ',', '.') }} PTS</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-400 text-xs">Belum ada riwayat penukaran hadiah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
