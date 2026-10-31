@extends('layouts.app')

@section('title', 'Buat Promo Baru')
@section('tab_title', 'Tambah Promo')

@section('content')
<div class="space-y-4 p-4 max-w-4xl mx-auto w-full">

    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('promotions.index') }}" class="hover:underline">Promosi</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Baru</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                ➕ Buat Promo Baru
            </h1>
        </div>
        <a href="{{ route('promotions.index') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
            Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs font-bold">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('promotions.store') }}" method="POST" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-xs space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Kode Promo *</label>
                <input type="text" name="code" value="{{ old('code', 'PRM-' . strtoupper(Str::random(5))) }}" required
                       class="w-full text-xs font-mono font-bold uppercase rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-3 py-2">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tipe Promo *</label>
                <select name="type" id="promo_type" required onchange="handleTypeChange()"
                        class="w-full text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
                    <option value="BUY_X_GET_Y" {{ old('type') === 'BUY_X_GET_Y' ? 'selected' : '' }}>🎁 Beli X Gratis Y (Buy 1 Get 1)</option>
                    <option value="TEBUS_MURAH" {{ old('type') === 'TEBUS_MURAH' ? 'selected' : '' }}>🔥 Tebus Murah (Purchase With Purchase)</option>
                    <option value="DISCOUNT_PERCENT" {{ old('type') === 'DISCOUNT_PERCENT' ? 'selected' : '' }}>% Diskon Persentase</option>
                    <option value="DISCOUNT_NOMINAL" {{ old('type') === 'DISCOUNT_NOMINAL' ? 'selected' : '' }}>💵 Potongan Nominal Langsung</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nama Promo *</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Beli 1 Teh Botol Gratis 1 Wafer"
                   class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
        </div>

        <!-- BUY X GET Y SECTION -->
        <div id="section_buy_x" class="p-3 bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-900 rounded-xl space-y-3">
            <h3 class="text-xs font-extrabold text-blue-900 dark:text-blue-300">Syarat Pembelian (Produk X)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Produk Syarat</label>
                    <select name="buy_product_id" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Jumlah Beli Minimal</label>
                    <input type="number" name="buy_quantity" value="1" min="1" step="1"
                           class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                </div>
            </div>
        </div>

        <!-- REWARD PRODUCT SECTION -->
        <div id="section_reward" class="p-3 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-xl space-y-3">
            <h3 class="text-xs font-extrabold text-amber-900 dark:text-amber-300">Hadiah / Produk Tebus Murah (Produk Y)</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-1">
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Produk Hadiah</label>
                    <select name="reward_product_id" class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                        <option value="">-- Pilih Produk --</option>
                        @foreach($products as $prod)
                            <option value="{{ $prod->id }}">{{ $prod->name }} ({{ $prod->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Jumlah Hadiah</label>
                    <input type="number" name="reward_quantity" value="1" min="1" step="1"
                           class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Harga Tebus Murah (Rp)</label>
                    <input type="number" name="special_price" value="0" min="0" step="100" placeholder="0 untuk gratis"
                           class="w-full text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1.5">
                </div>
            </div>
        </div>

        <!-- DISCOUNT & MIN SPEND -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Diskon Persen (%)</label>
                <input type="number" name="discount_percent" value="0" min="0" max="100" step="0.5"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Potongan Nominal (Rp)</label>
                <input type="number" name="discount_nominal" value="0" min="0" step="500"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Min. Belanja Syarat (Rp)</label>
                <input type="number" name="min_spend" value="0" min="0" step="1000"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
        </div>

        <!-- PERIODE & STATUS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Mulai</label>
                <input type="date" name="start_date" value="{{ date('Y-m-d') }}"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Berakhir</label>
                <input type="date" name="end_date"
                       class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2">
            </div>
        </div>

        <div class="pt-2 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-slate-700 dark:text-slate-300">
                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-blue-600">
                <span>Aktifkan Promo Langsung</span>
            </label>

            <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer">
                Simpan &amp; Aktifkan Promo
            </button>
        </div>
    </form>

</div>

<script>
function handleTypeChange() {
    const val = document.getElementById('promo_type').value;
    const buySec = document.getElementById('section_buy_x');
    const rwdSec = document.getElementById('section_reward');

    if (val === 'BUY_X_GET_Y') {
        buySec.style.display = 'block';
        rwdSec.style.display = 'block';
    } else if (val === 'TEBUS_MURAH') {
        buySec.style.display = 'none';
        rwdSec.style.display = 'block';
    } else {
        buySec.style.display = 'none';
        rwdSec.style.display = 'none';
    }
}
handleTypeChange();
</script>
@endsection
