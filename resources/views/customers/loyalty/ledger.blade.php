@extends('layouts.app')

@section('title', 'Buku Besar Poin Member - ' . $customer->name)
@section('tab_title', 'Buku Besar Poin')

@section('content')
<div class="space-y-4 p-4 max-w-5xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.loyalty.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Loyalty Points</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Point Ledger</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📜 Riwayat Mutasi Poin Pelanggan</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('customers.loyalty.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali
            </a>
            <button type="button"
                    onclick="document.getElementById('adjustModal').classList.remove('hidden')"
                    class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-purple-500/25 flex items-center gap-1.5 transition-all cursor-pointer">
                <span>⚡ Sesuaikan Poin</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-semibold">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            ✕ {{ $errors->first() }}
        </div>
    @endif

    <!-- RINGKASAN DATA MEMBER -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Identitas Pelanggan</div>
            <div class="text-base font-black text-slate-900 dark:text-white mt-1">{{ $customer->name }}</div>
            <div class="text-xs text-slate-500 mt-0.5 font-mono">{{ $customer->code }} • {{ $customer->phone ?: 'No Phone' }}</div>
            <div class="mt-2">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    Tier: {{ $customer->tier->name ?? 'RETAIL' }}
                </span>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Saldo Poin Saat Ini</div>
            <div class="text-2xl font-black font-mono text-purple-600 dark:text-purple-400 mt-1">
                {{ number_format($customer->loyalty_points) }} Pts
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
                Total Perolehan Historis: <b>{{ number_format($customer->lifetime_points) }} Pts</b>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-[11px] font-bold uppercase text-slate-400">Status Belanja &amp; Kredit</div>
            <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-2 space-y-1">
                <div>Plafon Kredit: <b>Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}</b></div>
                <div>Saldo Piutang: <b class="text-rose-600">Rp {{ number_format($customer->current_credit_balance, 0, ',', '.') }}</b></div>
            </div>
        </div>
    </div>

    <!-- TABEL MUTASI BUKU BESAR POIN -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-xs">
        <div class="p-3 border-b border-slate-100 dark:border-slate-800 font-extrabold text-xs uppercase tracking-wider text-slate-400">
            Riwayat Mutasi Poin (Point Ledger)
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 uppercase tracking-wider font-extrabold">
                    <tr>
                        <th class="p-3">Waktu &amp; Tanggal</th>
                        <th class="p-3">Tipe</th>
                        <th class="p-3">Keterangan / Faktur</th>
                        <th class="p-3 text-right">Poin Masuk / Keluar</th>
                        <th class="p-3 text-right">Saldo Sesudah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($mutations as $m)
                        <tr class="hover:bg-slate-50/75 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono text-slate-600 dark:text-slate-400">
                                {{ $m->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="p-3">
                                @if($m->type === 'EARN')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        BELANJA (EARN)
                                    </span>
                                @elseif($m->type === 'REDEEM')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                        PENUKARAN (REDEEM)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                        PENYESUAIAN
                                    </span>
                                @endif
                            </td>
                            <td class="p-3 text-slate-800 dark:text-slate-200">
                                {{ $m->description }}
                            </td>
                            <td class="p-3 text-right font-mono font-black text-sm">
                                @if($m->points > 0)
                                    <span class="text-emerald-600 dark:text-emerald-400">+{{ number_format($m->points) }}</span>
                                @else
                                    <span class="text-rose-600 dark:text-rose-400">{{ number_format($m->points) }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ number_format($m->balance_after) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">
                                Belum ada riwayat mutasi poin untuk pelanggan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutations->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $mutations->links() }}
            </div>
        @endif
    </div>

</div>

<!-- MODAL PENYESUAIAN POIN -->
<div id="adjustModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-xs">
    <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-5 border border-slate-200 dark:border-slate-800 shadow-xl space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-black text-slate-900 dark:text-white">⚡ Penyesuaian Saldo Poin</h3>
            <button type="button" onclick="document.getElementById('adjustModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <form method="POST" action="{{ route('customers.loyalty.adjust', $customer->id) }}" class="space-y-3 text-xs">
            @csrf

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                    Jumlah Perubahan Poin (+ untuk Menambah, - untuk Mengurangi) *
                </label>
                <input type="number"
                       name="points_delta"
                       required
                       placeholder="Contoh: 50 atau -20"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-bold focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Alasan Penyesuaian *</label>
                <input type="text"
                       name="reason"
                       required
                       placeholder="Contoh: Kompensasi keluhan layanan, bonus ulang tahun..."
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                <button type="button"
                        onclick="document.getElementById('adjustModal').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold rounded-xl cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold rounded-xl shadow-md shadow-purple-500/25 cursor-pointer">
                    Simpan Penyesuaian
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
