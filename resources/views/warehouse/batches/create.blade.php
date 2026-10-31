@extends('layouts.app')

@section('title', 'Tambah Nomor Batch / Lot Baru')
@section('tab_title', 'Tambah Batch')

@section('content')
<div class="space-y-4 p-4 max-w-3xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.batches.index') }}" class="font-semibold text-slate-500 hover:text-slate-700">Pelacakan Batch</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Tambah Batch Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>➕ Registrasi Nomor Batch / Lot Baru</span>
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.batches.index') }}"
               class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-all">
                ← Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold">
            ✕ {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('warehouse.batches.store') }}" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4 text-xs">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Toko / Gudang Penyimpanan *</label>
                <select name="store_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ old('store_id', Auth::user()->store_id) === $st->id ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Produk *</label>
                <select name="product_id" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ old('product_id') === $p->id ? 'selected' : '' }}>
                            {{ $p->sku }} - {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Nomor Batch / Lot *</label>
                <input type="text"
                       name="batch_number"
                       value="{{ old('batch_number') }}"
                       required
                       placeholder="Contoh: LOT202610A, BATCH-99"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-bold focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Kedaluwarsa (Expired) *</label>
                <input type="date"
                       name="expired_date"
                       value="{{ old('expired_date') }}"
                       required
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Produksi (Opsional)</label>
                <input type="date"
                       name="manufacture_date"
                       value="{{ old('manufacture_date') }}"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Kuantitas Batch *</label>
                <input type="number"
                       step="0.0001"
                       min="0.0001"
                       name="quantity"
                       value="{{ old('quantity', 1) }}"
                       required
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-black text-blue-600 dark:text-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Harga Modal Satuan (HPP Batch)</label>
                <input type="number"
                       step="0.01"
                       min="0"
                       name="unit_cost"
                       value="{{ old('unit_cost', 0) }}"
                       placeholder="Kosongkan jika ikuti HPP standar"
                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-medium focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan Tambahan</label>
            <input type="text"
                   name="notes"
                   value="{{ old('notes') }}"
                   placeholder="Keterangan nomor faktur supplier, kondisi kemasan, dsb..."
                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium focus:outline-none">
        </div>

        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
            <a href="{{ route('warehouse.batches.index') }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white font-black text-xs rounded-xl shadow-md shadow-blue-500/25 cursor-pointer">
                💾 Simpan Nomor Batch
            </button>
        </div>
    </form>

</div>
@endsection
