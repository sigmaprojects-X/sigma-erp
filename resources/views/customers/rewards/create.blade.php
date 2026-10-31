@extends('layouts.app')

@section('title', 'Tambah Hadiah Poin')
@section('tab_title', 'Tambah Hadiah')

@section('content')
<div class="space-y-4 p-4 max-w-2xl mx-auto w-full">

    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('customers.rewards.index') }}" class="hover:underline">Katalog Hadiah</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Tambah</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                ➕ Tambah Hadiah Baru
            </h1>
        </div>
        <a href="{{ route('customers.rewards.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('customers.rewards.store') }}" method="POST" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Hadiah *</label>
                <input type="text" name="code" value="{{ old('code', 'RWD-' . strtoupper(Str::random(4))) }}" required
                       class="w-full text-xs font-mono font-bold uppercase rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Poin yang Dibutuhkan *</label>
                <input type="number" name="points_required" value="{{ old('points_required', 100) }}" min="1" required
                       class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-purple-600">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Hadiah *</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Payung Eksklusif SIGMA / Tumbler Stainless"
                   class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Stok Hadiah Tersedia *</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 10) }}" min="0" step="1" required
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tautkan ke Master Produk (Opsional)</label>
                <select name="product_id" class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    <option value="">-- Non-Stok / Merchandise Promosi --</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}">{{ $prod->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Deskripsi / Spesifikasi</label>
            <textarea name="description" rows="2" placeholder="Keterangan tambahan hadiah..."
                      class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">{{ old('description') }}</textarea>
        </div>

        <div class="pt-2 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-purple-600">
                <span>Aktif di Katalog</span>
            </label>

            <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                Simpan Hadiah
            </button>
        </div>
    </form>

</div>
@endsection
