@extends('layouts.app')

@section('title', 'Mulai Sesi Stock Opname')
@section('tab_title', 'Mulai Stock Opname')

@section('content')
<div class="space-y-4 p-4 max-w-2xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.opnames.index') }}" class="hover:text-blue-600 font-semibold">Stock Opname</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Inisiasi Sesi</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>📋 Inisiasi Sesi Blind Stock Opname</span>
            </h1>
        </div>

        <a href="{{ route('warehouse.opnames.index') }}"
           class="px-3.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
            Kembali
        </a>
    </div>

    <!-- NOTICE BLIND OPNAME POLICY -->
    <div class="p-3.5 bg-blue-500/10 border border-blue-500/30 rounded-2xl text-blue-700 dark:text-blue-300 text-xs space-y-1">
        <div class="font-extrabold flex items-center gap-1.5">
            <span>🛡️ Kebijakan Blind Stock Opname (AGENTS.md Section 2)</span>
        </div>
        <p class="text-[11px] leading-relaxed text-blue-800/80 dark:text-blue-200/80">
            Saat sesi dimulai, sistem akan membekukan snapshot stok terkini seluruh produk di toko yang dipilih.
            Staf lapangan yang menghitung fisik <strong>TIDAK AKAN melihat angka ekspektasi sistem</strong>. Mereka hanya dapat menginput hasil hitungan riil di lembar fisik.
        </p>
    </div>

    @if ($errors->any())
        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs">
            <ul class="list-disc pl-4 space-y-0.5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('warehouse.opnames.store') }}" method="POST" class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <div class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Toko / Lokasi Opname <span class="text-rose-500">*</span></label>
                <select name="store_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Toko --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ (auth()->user()->store_id === $s->id || old('store_id') === $s->id) ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Pelaksanaan Opname <span class="text-rose-500">*</span></label>
                <input type="date" name="opname_date" value="{{ old('opname_date', date('Y-m-d')) }}" required
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Inisiasi Opname</label>
                <textarea name="notes" rows="3" placeholder="Contoh: Audit stok berkala akhir bulan unit sembako & snack"
                          class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('warehouse.opnames.index') }}"
               class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-blue-500/25 cursor-pointer">
                🚀 Mulai Sesi & Bekukan Snapshot Stok
            </button>
        </div>
    </form>
</div>
@endsection
