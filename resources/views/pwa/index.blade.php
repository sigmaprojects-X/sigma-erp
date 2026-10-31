<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $company->name ?? 'SIGMA Supermarket' }} - Marketplace Online</title>
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo-icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased selection:bg-blue-500 selection:text-white pb-24"
      x-data="pwaStoreApp()">

    <!-- TOP HEADER APP BAR -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 px-4 py-3 shadow-xs">
        <div class="max-w-2xl mx-auto flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-[#0B1F4D] p-1 flex items-center justify-center shadow-xs shrink-0 border border-[#132D6B]">
                    <img src="{{ asset('images/sigma-erp-logo.png') }}" alt="SIGMA" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-sm font-black text-slate-900 tracking-tight leading-tight">
                        {{ $company->name ?? 'SIGMA STORE' }}
                    </h1>
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ $store->name ?? 'Outlet Utama' }}</span>
                    </div>
                </div>
            </div>

            <button type="button" @click="openCartDrawer = true" class="relative p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer">
                <span class="text-lg">🛒</span>
                <span x-show="cartTotalCount > 0"
                      x-text="cartTotalCount"
                      class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full bg-rose-600 text-white font-black text-[10px] min-w-[18px] text-center shadow-xs">
                </span>
            </button>
        </div>

        <!-- SEARCH BAR INPUT -->
        <div class="max-w-2xl mx-auto mt-2.5">
            <form method="GET" action="{{ route('pwa.index') }}" class="relative">
                <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Cari sembako, minuman, snack..."
                       class="w-full pl-9 pr-4 py-2 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                @if(request('category_id'))
                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                @endif
            </form>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="max-w-2xl mx-auto px-4 pt-3 space-y-4">

        <!-- CATEGORIES HORIZONTAL CAROUSEL -->
        <div>
            <div class="flex items-center gap-2 overflow-x-auto hide-scrollbar py-1 text-xs font-bold">
                <a href="{{ route('pwa.index') }}"
                   class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all shadow-2xs {{ !request('category_id') ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
                    Semua Produk
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('pwa.index', ['category_id' => $cat->id]) }}"
                       class="px-3.5 py-1.5 rounded-full whitespace-nowrap transition-all shadow-2xs {{ request('category_id') == $cat->id ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- BANNER PROMO ALERT -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-4 text-white shadow-sm flex items-center justify-between">
            <div>
                <span class="px-2 py-0.5 rounded-full bg-white/20 text-[10px] font-black uppercase tracking-wider">
                    ⚡ Belanja Cepat dari HP
                </span>
                <h2 class="text-sm font-black mt-1">Pesan Online, Ambil / Diantar</h2>
                <p class="text-[11px] text-blue-100 mt-0.5">Stok terjamin & konfirmasi instan via WhatsApp.</p>
            </div>
            <div class="text-3xl">🛵</div>
        </div>

        <!-- SECTION PROMO & TEBUS MURAH SPESIAL -->
        @if(isset($activePromotions) && $activePromotions->isNotEmpty())
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <span class="text-sm">🔥</span>
                        <span>Promo &amp; Tebus Murah Spesial</span>
                    </h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">
                        {{ $activePromotions->count() }} Promo Aktif
                    </span>
                </div>

                <div class="flex gap-3 overflow-x-auto hide-scrollbar pb-1">
                    @foreach($activePromotions as $promo)
                        @php
                            $rwd = $promo->rewardProduct;
                            $rwdUnit = $rwd?->units?->first();
                            $normalPrice = $rwd?->prices?->first()?->price ?? 0;
                        @endphp
                        <div class="min-w-[240px] max-w-[260px] bg-gradient-to-br from-white to-amber-50/40 border border-amber-200/80 rounded-2xl p-3 shadow-xs flex flex-col justify-between shrink-0 relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-12 h-12 bg-amber-400/10 rounded-full"></div>
                            
                            <div>
                                <div class="flex items-center justify-between gap-1 mb-1.5">
                                    @if($promo->type === 'TEBUS_MURAH')
                                        <span class="px-2 py-0.5 rounded-md bg-rose-600 text-white text-[9.5px] font-black uppercase tracking-wider">
                                            🔥 Tebus Murah
                                        </span>
                                    @elseif($promo->type === 'BUY_X_GET_Y')
                                        <span class="px-2 py-0.5 rounded-md bg-blue-600 text-white text-[9.5px] font-black uppercase tracking-wider">
                                            🎁 Beli X Gratis Y
                                        </span>
                                    @elseif($promo->type === 'DISCOUNT_PERCENT')
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-600 text-white text-[9.5px] font-black uppercase tracking-wider">
                                            % Diskon {{ $promo->discount_percent }}%
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-indigo-600 text-white text-[9.5px] font-black uppercase tracking-wider">
                                            💵 Potongan Langsung
                                        </span>
                                    @endif

                                    <span class="text-[10px] font-bold text-slate-400">
                                        {{ $promo->code }}
                                    </span>
                                </div>

                                <h4 class="font-extrabold text-xs text-slate-900 leading-snug line-clamp-2">
                                    {{ $promo->name }}
                                </h4>

                                @if($promo->type === 'TEBUS_MURAH' && $rwd)
                                    <div class="mt-2 p-2 bg-white rounded-xl border border-slate-100 flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-lg shrink-0">
                                            🎁
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[11px] font-bold text-slate-800 truncate">{{ $rwd->name }}</div>
                                            <div class="flex items-center gap-1.5 text-[10px]">
                                                <span class="text-rose-600 font-extrabold text-xs">Rp {{ number_format($promo->special_price, 0, ',', '.') }}</span>
                                                @if($normalPrice > $promo->special_price)
                                                    <span class="text-slate-400 line-through text-[9px]">Rp {{ number_format($normalPrice, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-1.5 text-[10px] font-bold text-amber-700 flex items-center gap-1">
                                        <span>⚡</span>
                                        <span>Syarat: Min. Belanja Rp {{ number_format($promo->min_spend, 0, ',', '.') }}</span>
                                    </div>
                                @elseif($promo->type === 'BUY_X_GET_Y')
                                    <div class="mt-1.5 text-[10.5px] text-slate-600 font-medium">
                                        Beli <strong>{{ (int)$promo->buy_quantity }}x {{ $promo->buyProduct?->name }}</strong>, gratis <strong>{{ (int)$promo->reward_quantity }}x {{ $promo->rewardProduct?->name }}</strong>.
                                    </div>
                                @elseif($promo->min_spend > 0)
                                    <div class="mt-1.5 text-[10px] font-bold text-slate-500">
                                        Min. Belanja: Rp {{ number_format($promo->min_spend, 0, ',', '.') }}
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between">
                                <span class="text-[10px] text-slate-400 font-medium">Otomatis di keranjang</span>
                                <button type="button" 
                                        @click="openCartDrawer = true" 
                                        class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-[10px] shadow-2xs cursor-pointer">
                                    Cek Syarat
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- PRODUCT GRID (2 KOLOM RESPONSIVE) -->
        <div class="grid grid-cols-2 gap-3">
            @forelse($products as $prod)
                @php
                    $isAvailable = $prod->available_stock > 0;
                    $unit = $prod->units->first();
                    $unitId = $unit ? $unit->id : '';
                    $unitCode = $unit ? $unit->unit_code : 'Pcs';
                @endphp
                <div class="bg-white border border-slate-200/80 rounded-2xl p-3 flex flex-col justify-between shadow-2xs hover:shadow-md transition-all">
                    <div>
                        <!-- FOTO / PLACEHOLDER PRODUK -->
                        <div class="w-full aspect-square bg-slate-50 rounded-xl mb-2 flex items-center justify-center text-4xl border border-slate-100">
                            📦
                        </div>

                        <!-- BADGE STOK & SATUAN -->
                        <div class="flex items-center justify-between text-[10px] mb-1 font-bold">
                            <span class="text-slate-500 uppercase">{{ $unitCode }}</span>
                            @if($isAvailable)
                                <span class="text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded-md">
                                    Stok: {{ (int)$prod->available_stock }}
                                </span>
                            @else
                                <span class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded-md">
                                    Habis
                                </span>
                            @endif
                        </div>

                        <!-- NAMA PRODUK -->
                        <h3 class="text-xs font-black text-slate-900 line-clamp-2 leading-tight">
                            {{ $prod->name }}
                        </h3>
                    </div>

                    <!-- HARGA & TOMBOL TAMBAH -->
                    <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                        <div>
                            <span class="text-[9px] text-slate-400 block font-semibold leading-none">Harga</span>
                            <span class="text-xs font-black text-blue-600">
                                Rp {{ number_format($prod->display_price, 0, ',', '.') }}
                            </span>
                        </div>

                        @if($isAvailable)
                            <button type="button"
                                    @click="addToCart({
                                        id: '{{ $prod->id }}',
                                        name: '{{ addslashes($prod->name) }}',
                                        unit_id: '{{ $unitId }}',
                                        unit_code: '{{ $unitCode }}',
                                        price: {{ $prod->display_price }},
                                        max_qty: {{ $prod->available_stock }}
                                    })"
                                    class="w-8 h-8 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-sm flex items-center justify-center shadow-xs active:scale-90 transition-all cursor-pointer">
                                +
                            </button>
                        @else
                            <button disabled class="w-8 h-8 rounded-xl bg-slate-200 text-slate-400 font-bold text-xs flex items-center justify-center cursor-not-allowed">
                                ✕
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-2 py-12 text-center text-slate-400 text-xs">
                    Tidak ada produk yang cocok dengan pencarian.
                </div>
            @endforelse
        </div>

        <!-- PAGINATION -->
        @if($products->hasPages())
            <div class="pt-2">
                {{ $products->links() }}
            </div>
        @endif
    </main>

    <!-- FLOATING CART BAR DI BAWAH (STICKY MOBILE BAR) -->
    <div x-show="cartTotalCount > 0"
         x-cloak
         class="fixed bottom-4 left-4 right-4 max-w-2xl mx-auto z-40 animate-in fade-in slide-in-from-bottom-4 duration-200">
        <button type="button"
                @click="openCartDrawer = true"
                class="w-full bg-slate-900 text-white px-4 py-3.5 rounded-2xl shadow-xl flex items-center justify-between border border-slate-700 active:scale-98 transition-all cursor-pointer">
            <div class="flex items-center gap-3">
                <span class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center font-black text-xs" x-text="cartTotalCount"></span>
                <div class="text-left">
                    <div class="text-[11px] text-slate-300">Total Keranjang</div>
                    <div class="text-sm font-black text-white" x-text="'Rp ' + formatRupiah(cartGrandTotal)"></div>
                </div>
            </div>
            <div class="flex items-center gap-1.5 text-xs font-bold text-blue-400">
                <span>Lihat Pesanan</span>
                <span>&rarr;</span>
            </div>
        </button>
    </div>

    <!-- DRAWER / MODAL KERANJANG & CHECKOUT SLIDE-OVER -->
    <div x-show="openCartDrawer"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden">
        
        <!-- BACKDROP -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
             @click="openCartDrawer = false"></div>

        <div class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col justify-between">
            
            <!-- HEADER DRAWER -->
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🛒</span>
                    <h3 class="font-black text-sm text-slate-900">Keranjang Belanja</h3>
                    <span class="text-xs text-slate-500 font-bold" x-text="'(' + cartTotalCount + ' item)'"></span>
                </div>
                <button type="button" @click="openCartDrawer = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-700 text-lg">
                    ✕
                </button>
            </div>

            <!-- BODY: LIST ITEM KERANJANG & FORM PELANGGAN -->
            <div class="p-4 overflow-y-auto space-y-4 flex-1">
                
                <!-- NOTIFIKASI TEBUS MURAH TERSEDIA (SYARAT BELANJA TEMBUS) -->
                <template x-for="reward in eligibleRewards" :key="reward.promotion_id">
                    <div class="p-3 bg-gradient-to-r from-amber-500 via-rose-500 to-amber-600 text-white rounded-xl shadow-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded-full bg-white text-rose-700 font-black text-[9.5px] uppercase tracking-wider">
                                🔥 TEBUS MURAH TERSEDIA!
                            </span>
                            <span class="text-xs font-mono font-bold text-yellow-200" x-text="'Hanya Rp ' + formatRupiah(reward.special_price)"></span>
                        </div>
                        <p class="text-[11px] text-amber-50 leading-tight">
                            Belanja Anda sudah mencapai syarat! Anda berhak tebus <strong class="text-white underline" x-text="reward.reward_product_name"></strong> seharga Rp <span x-text="formatRupiah(reward.special_price)"></span>.
                        </p>
                        <button type="button"
                                @click="claimTebusMurah(reward)"
                                :disabled="isRewardAlreadyClaimed(reward)"
                                class="w-full py-2 rounded-lg font-black text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                :class="isRewardAlreadyClaimed(reward) ? 'bg-white/20 text-white cursor-not-allowed' : 'bg-white text-rose-700 hover:bg-amber-100 hover:scale-102 active:scale-98'">
                            <span x-text="isRewardAlreadyClaimed(reward) ? '✓ Sudah Masuk Keranjang' : '+ Ambil Tebus Murah Sekarang'"></span>
                        </button>
                    </div>
                </template>

                <!-- PROGRES MENUJU TEBUS MURAH (KURANG SEDIKIT LAGI) -->
                <template x-for="promo in almostEligiblePromos" :key="promo.id">
                    <div class="p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs">
                        <div class="flex items-center gap-1.5 font-bold">
                            <span>💡</span>
                            <span>Tambah belanja <strong class="text-rose-600" x-text="'Rp ' + formatRupiah(promo.remaining)"></strong> lagi</span>
                        </div>
                        <div class="text-[11px] text-slate-600 mt-0.5">
                            Untuk tebus murah <strong x-text="promo.reward_name"></strong> hanya seharga <strong class="text-rose-600 font-mono" x-text="'Rp ' + formatRupiah(promo.special_price)"></strong>!
                        </div>
                    </div>
                </template>

                <!-- LIST BARANG DI KERANJANG -->
                <div class="space-y-2">
                    <template x-for="(item, idx) in cartItems" :key="item.id">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3 text-xs">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <template x-if="item.is_tebus_murah">
                                        <span class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[9px] uppercase tracking-wider shrink-0">
                                            Tebus Murah
                                        </span>
                                    </template>
                                    <h4 class="font-bold text-slate-900 truncate" x-text="item.name"></h4>
                                </div>
                                <div class="text-slate-500 text-[11px] mt-0.5">
                                    Rp <span x-text="formatRupiah(item.price)"></span> / <span x-text="item.unit_code"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <template x-if="!item.is_tebus_murah">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="updateQty(idx, -1)" class="w-6 h-6 rounded-md bg-white border border-slate-300 font-bold flex items-center justify-center cursor-pointer">-</button>
                                        <span class="font-black text-slate-800 w-5 text-center" x-text="item.qty"></span>
                                        <button type="button" @click="updateQty(idx, 1)" class="w-6 h-6 rounded-md bg-white border border-slate-300 font-bold flex items-center justify-center cursor-pointer">+</button>
                                    </div>
                                </template>
                                <template x-if="item.is_tebus_murah">
                                    <span class="font-black text-slate-800 w-5 text-center" x-text="item.qty + 'x'"></span>
                                </template>
                                <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 ml-1 cursor-pointer">🗑️</button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- FORM IDENTITAS & PENGIRIMAN -->
                <div class="pt-2 border-t border-slate-200 space-y-3">
                    <h4 class="text-xs font-black uppercase text-slate-700">Data Pemesan</h4>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nama Lengkap *</label>
                        <input type="text" x-model="checkoutForm.name" placeholder="Contoh: Budi Santoso"
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-semibold">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Nomor WhatsApp Aktif *</label>
                        <input type="tel" x-model="checkoutForm.phone" placeholder="Contoh: 081234567890"
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-semibold">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Metode Pengambilan *</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer"
                                   :class="checkoutForm.delivery_type === 'PICKUP' ? 'border-blue-600 bg-blue-50 text-blue-900 font-bold' : 'border-slate-200'">
                                <input type="radio" value="PICKUP" x-model="checkoutForm.delivery_type" class="hidden">
                                <span>🏬 Ambil di Toko</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer"
                                   :class="checkoutForm.delivery_type === 'DELIVERY' ? 'border-blue-600 bg-blue-50 text-blue-900 font-bold' : 'border-slate-200'">
                                <input type="radio" value="DELIVERY" x-model="checkoutForm.delivery_type" class="hidden">
                                <span>🛵 Diantar Kurir</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="checkoutForm.delivery_type === 'DELIVERY'">
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Alamat Pengiriman *</label>
                        <textarea x-model="checkoutForm.address" rows="2" placeholder="Nama jalan, nomor rumah, patokan..."
                                  class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-semibold"></textarea>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Metode Pembayaran *</label>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer"
                                   :class="checkoutForm.payment_method === 'TRANSFER' ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200'">
                                <input type="radio" value="TRANSFER" x-model="checkoutForm.payment_method" class="hidden">
                                <span>🏦 Transfer / QRIS</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer"
                                   :class="checkoutForm.payment_method === 'COD' ? 'border-emerald-600 bg-emerald-50 text-emerald-900 font-bold' : 'border-slate-200'">
                                <input type="radio" value="COD" x-model="checkoutForm.payment_method" class="hidden">
                                <span>💵 Bayar di Tempat (COD)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Catatan Pesanan (Opsional)</label>
                        <input type="text" x-model="checkoutForm.notes" placeholder="Contoh: Tolong bungkus plastik dobel"
                               class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-semibold">
                    </div>
                </div>
            </div>

            <!-- FOOTER CHECKOUT -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">Total Pembayaran:</span>
                    <span class="text-base font-black text-blue-600" x-text="'Rp ' + formatRupiah(cartGrandTotal)"></span>
                </div>

                <button type="button"
                        @click="submitOrder()"
                        :disabled="submitting || cartItems.length === 0"
                        class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50">
                    <span x-show="!submitting">🚀 Kirim Pesanan Sekarang</span>
                    <span x-show="submitting">Memproses Pesanan...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT & ALPINE APP -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function pwaStoreApp() {
            return {
                openCartDrawer: false,
                submitting: false,
                cartItems: JSON.parse(localStorage.getItem('sigma_pwa_cart') || '[]'),
                activePromotions: @json($activePromotions ?? []),
                checkoutForm: {
                    name: localStorage.getItem('sigma_pwa_name') || '',
                    phone: localStorage.getItem('sigma_pwa_phone') || '',
                    delivery_type: 'PICKUP',
                    address: localStorage.getItem('sigma_pwa_address') || '',
                    payment_method: 'TRANSFER',
                    notes: ''
                },

                get eligibleRewards() {
                    const nonPromoSubtotal = this.cartItems
                        .filter(item => !item.is_tebus_murah)
                        .reduce((acc, curr) => acc + (parseFloat(curr.price) * parseInt(curr.qty)), 0);

                    const rewards = [];
                    for (const promo of this.activePromotions) {
                        if (promo.type === 'TEBUS_MURAH') {
                            const minSpend = parseFloat(promo.min_spend) || 0;
                            if (nonPromoSubtotal >= minSpend && promo.reward_product) {
                                rewards.push({
                                    promotion_id: promo.id,
                                    name: promo.name,
                                    reward_product_id: promo.reward_product_id,
                                    reward_product_name: promo.reward_product.name,
                                    special_price: parseFloat(promo.special_price) || 0,
                                    max_quantity: parseFloat(promo.reward_quantity) || 1,
                                    min_spend: minSpend,
                                    promo: promo
                                });
                            }
                        }
                    }
                    return rewards;
                },

                get almostEligiblePromos() {
                    const nonPromoSubtotal = this.cartItems
                        .filter(item => !item.is_tebus_murah)
                        .reduce((acc, curr) => acc + (parseFloat(curr.price) * parseInt(curr.qty)), 0);

                    const list = [];
                    for (const promo of this.activePromotions) {
                        if (promo.type === 'TEBUS_MURAH' && promo.reward_product) {
                            const minSpend = parseFloat(promo.min_spend) || 0;
                            if (nonPromoSubtotal > 0 && nonPromoSubtotal < minSpend) {
                                list.push({
                                    id: promo.id,
                                    name: promo.name,
                                    reward_name: promo.reward_product.name,
                                    remaining: minSpend - nonPromoSubtotal,
                                    special_price: parseFloat(promo.special_price) || 0
                                });
                            }
                        }
                    }
                    return list;
                },

                isRewardAlreadyClaimed(reward) {
                    return this.cartItems.some(i => i.id === reward.reward_product_id && i.is_tebus_murah);
                },

                claimTebusMurah(reward) {
                    if (this.isRewardAlreadyClaimed(reward)) return;
                    const prod = reward.promo.reward_product;
                    if (!prod) return;

                    const unit = prod.units && prod.units.length > 0 ? prod.units[0] : null;
                    const unitId = unit ? unit.id : '';
                    const unitCode = unit ? unit.unit_code : 'Pcs';

                    this.cartItems.push({
                        id: prod.id,
                        name: prod.name,
                        unit_id: unitId,
                        unit_code: unitCode,
                        price: reward.special_price,
                        qty: reward.max_quantity || 1,
                        is_tebus_murah: true,
                        promotion_id: reward.promotion_id
                    });
                    this.saveCart();
                },

                get cartTotalCount() {
                    return this.cartItems.reduce((acc, curr) => acc + (parseInt(curr.qty) || 0), 0);
                },

                get cartGrandTotal() {
                    return this.cartItems.reduce((acc, curr) => acc + (parseFloat(curr.price) * parseInt(curr.qty)), 0);
                },

                saveCart() {
                    localStorage.setItem('sigma_pwa_cart', JSON.stringify(this.cartItems));
                },

                addToCart(prod) {
                    const idx = this.cartItems.findIndex(i => i.id === prod.id);
                    if (idx > -1) {
                        this.cartItems[idx].qty += 1;
                    } else {
                        this.cartItems.push({
                            id: prod.id,
                            name: prod.name,
                            unit_id: prod.unit_id,
                            unit_code: prod.unit_code,
                            price: prod.price,
                            qty: 1
                        });
                    }
                    this.saveCart();
                },

                updateQty(idx, delta) {
                    this.cartItems[idx].qty += delta;
                    if (this.cartItems[idx].qty <= 0) {
                        this.removeItem(idx);
                    } else {
                        this.saveCart();
                    }
                },

                removeItem(idx) {
                    this.cartItems.splice(idx, 1);
                    this.saveCart();
                },

                formatRupiah(num) {
                    return new Intl.NumberFormat('id-ID').format(num || 0);
                },

                async submitOrder() {
                    if (!this.checkoutForm.name || !this.checkoutForm.phone) {
                        alert('Silakan isi Nama Lengkap dan Nomor WhatsApp Anda.');
                        return;
                    }

                    if (this.checkoutForm.delivery_type === 'DELIVERY' && !this.checkoutForm.address) {
                        alert('Silakan lengkapi Alamat Pengiriman.');
                        return;
                    }

                    this.submitting = true;

                    // Simpan kontak agar tidak perlu isi ulang
                    localStorage.setItem('sigma_pwa_name', this.checkoutForm.name);
                    localStorage.setItem('sigma_pwa_phone', this.checkoutForm.phone);
                    localStorage.setItem('sigma_pwa_address', this.checkoutForm.address);

                    try {
                        const payload = {
                            store_id: '{{ $store->id }}',
                            customer_name: this.checkoutForm.name,
                            customer_phone: this.checkoutForm.phone,
                            delivery_type: this.checkoutForm.delivery_type,
                            delivery_address: this.checkoutForm.address,
                            payment_method: this.checkoutForm.payment_method,
                            notes: this.checkoutForm.notes,
                            items: this.cartItems.map(i => ({
                                product_id: i.id,
                                product_unit_id: i.unit_id,
                                quantity: i.qty,
                                is_tebus_murah: i.is_tebus_murah || false,
                                promotion_id: i.promotion_id || null,
                                discount_amount: i.discount_amount || 0
                            }))
                        };

                        const response = await fetch('{{ route('pwa.checkout') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        const res = await response.json();

                        if (res.success) {
                            // Kosongkan keranjang
                            this.cartItems = [];
                            this.saveCart();

                            // Buka konfirmasi order
                            window.location.href = res.redirect_url;
                        } else {
                            alert(res.message || 'Gagal memproses pesanan.');
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan jaringan: ' + e.message);
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
