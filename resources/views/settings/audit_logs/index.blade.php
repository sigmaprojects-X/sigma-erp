@extends('layouts.app')

@section('title', 'Audit Log & Jejak Aktivitas - SIGMA ERP')
@section('tab_title', 'Audit Log')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('settings.general') }}" class="hover:text-blue-600 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Keamanan &amp; Audit</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🛡️ Audit Log &amp; Rekam Jejak Aktivitas</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pencatatan real-time seluruh aktivitas sensitif pengguna, pembatalan transaksi kasir, perubahan harga, dan audit internal anti-fraud.
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.users.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>👥 Pengguna &amp; Hak Akses</span>
            </a>
            <a href="{{ route('settings.general') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏢 Pengaturan Umum</span>
            </a>
            <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/60 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Audit Logger Aktif</span>
            </span>
        </div>
    </div>

    <!-- METRICS SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Aktivitas Hari Ini</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ number_format($todayCount) }}</div>
                <span class="text-[10px] text-slate-400">Total event tercatat</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 flex items-center justify-center text-xl">
                📋
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Void / Pembatalan Kasir</span>
                <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ number_format($voidCount) }}</div>
                <span class="text-[10px] text-slate-400">Transaksi dibatalkan hari ini</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 flex items-center justify-center text-xl">
                ⚠️
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Perubahan Harga Barang</span>
                <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($priceChangeCount) }}</div>
                <span class="text-[10px] text-slate-400">Modifikasi harga hari ini</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400 flex items-center justify-center text-xl">
                🏷️
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <form method="GET" action="{{ route('settings.audit-logs.index') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3.5 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-2.5 text-xs">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Cari Keterangan / IP</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Ketik kata kunci..."
                       class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Modul</label>
                <select name="module" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
                    <option value="">-- Semua Modul --</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Pengguna / Kasir</label>
                <select name="user_id" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
                    <option value="">-- Semua Pengguna --</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') === $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ strtoupper($u->role) }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                       class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                <div class="flex gap-2">
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs">
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold rounded-xl shadow-xs transition-colors cursor-pointer shrink-0">
                        Filter
                    </button>
                    @if(request()->anyFilled(['q', 'module', 'user_id', 'start_date', 'end_date']))
                        <a href="{{ route('settings.audit-logs.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl flex items-center justify-center font-bold" title="Reset Filter">
                            ✕
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <!-- TABEL DATA AUDIT LOG -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-extrabold uppercase text-[10px] tracking-wider">
                        <th class="py-3 px-4 w-36">Waktu &amp; Tanggal</th>
                        <th class="py-3 px-4 w-44">Pengguna / Aktor</th>
                        <th class="py-3 px-4 w-28">Modul</th>
                        <th class="py-3 px-4 w-40">Aksi / Event</th>
                        <th class="py-3 px-4">Deskripsi Aktivitas</th>
                        <th class="py-3 px-4 w-32 text-right">IP &amp; Perangkat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($logs as $log)
                        @php
                            $actionUpper = strtoupper($log->action);
                            $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300';
                            if (str_contains($actionUpper, 'VOID') || str_contains($actionUpper, 'DELETE') || str_contains($actionUpper, 'CANCEL')) {
                                $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800';
                            } elseif (str_contains($actionUpper, 'PRICE') || str_contains($actionUpper, 'UPDATE') || str_contains($actionUpper, 'EDIT')) {
                                $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800';
                            } elseif (str_contains($actionUpper, 'APPROVE') || str_contains($actionUpper, 'COMPLETE') || str_contains($actionUpper, 'SUCCESS')) {
                                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800';
                            } elseif (str_contains($actionUpper, 'LOGIN') || str_contains($actionUpper, 'AUTH')) {
                                $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-mono text-slate-900 dark:text-white font-bold text-[11px]">
                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900 dark:text-white leading-tight">
                                    {{ $log->user_name ?? ($log->user?->name ?? 'Sistem') }}
                                </div>
                                <span class="px-1.5 py-0.2 rounded text-[9.5px] font-black uppercase tracking-wider bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 mt-0.5 inline-block">
                                    {{ $log->user_role ?? ($log->user?->role ?? 'system') }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $log->module }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[10.5px] font-black uppercase tracking-wider border {{ $badgeClass }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="py-3 px-4">
                                <div class="text-slate-800 dark:text-slate-200 font-medium leading-relaxed">
                                    {{ $log->description }}
                                </div>
                                @if($log->old_values || $log->new_values)
                                    <details class="mt-1 text-[10.5px] text-slate-500 cursor-pointer">
                                        <summary class="hover:text-blue-600 font-bold">Lihat Detail Nilai (JSON)</summary>
                                        <div class="p-2 mt-1 bg-slate-50 dark:bg-slate-800 rounded-lg font-mono text-[10px] overflow-x-auto space-y-1">
                                            @if($log->old_values)
                                                <div><strong class="text-rose-600">Sebelum:</strong> {{ json_encode($log->old_values) }}</div>
                                            @endif
                                            @if($log->new_values)
                                                <div><strong class="text-emerald-600">Sesudah:</strong> {{ json_encode($log->new_values) }}</div>
                                            @endif
                                        </div>
                                    </details>
                                @endif
                            </td>

                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <span class="font-mono text-[10.5px] font-bold text-slate-600 dark:text-slate-400 block">
                                    {{ $log->ip_address ?? '127.0.0.1' }}
                                </span>
                                <span class="text-[9.5px] text-slate-400 truncate max-w-[120px] inline-block" title="{{ $log->user_agent }}">
                                    {{ Str::limit($log->user_agent, 20) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <span class="text-3xl block mb-2">🛡️</span>
                                <span class="font-bold text-slate-700 dark:text-slate-300">Belum Ada Rekam Jejak Aktivitas</span>
                                <p class="text-xs text-slate-400 mt-1">Aktivitas sensitif kasir, login, dan perubahan data akan otomatis tercatat di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
