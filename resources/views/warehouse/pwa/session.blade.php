@extends('layouts.pwa_opname')

@section('title', 'Hitung Fisik: ' . $stockOpname->opname_number)
@section('header_subtitle', $stockOpname->opname_number . ' • ' . $stockOpname->store->name)

@section('header_back')
<a href="{{ route('warehouse.opname-pwa.index') }}" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-bold flex items-center gap-1 transition-colors">
    <span>←</span>
    <span class="hidden sm:inline">Daftar Sesi</span>
</a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-4 pb-28">

    <!-- PROGRESS HITUNGAN BAR (STICKY HEADER) -->
    <div class="bg-white/95 dark:bg-slate-800/95 border border-slate-200 dark:border-slate-700 rounded-2xl p-3.5 shadow-xs sticky top-16 z-30 backdrop-blur-md transition-colors">
        <div class="flex items-center justify-between text-xs mb-2">
            <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-1.5">
                <span class="text-blue-600 dark:text-blue-400">📊</span>
                <span>Progres Hitung Fisik</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                    Sistem Blind Opname 🔒
                </span>
            </span>
            <span class="font-mono font-extrabold text-blue-600 dark:text-blue-400 text-xs sm:text-sm" id="progressText">
                0 / {{ count($items) }} SKU
            </span>
        </div>
        <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
            <div id="progressBar" class="bg-gradient-to-r from-blue-600 to-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
        </div>
    </div>

    <!-- SEARCH & CAMERA SCANNER BAR -->
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-xs space-y-3 transition-colors">
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">🔍</span>
                <input type="text"
                       id="searchInput"
                       placeholder="Scan barcode scanner atau ketik nama produk / SKU..."
                       class="w-full pl-8 pr-3 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-semibold text-slate-900 dark:text-white placeholder-slate-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- TOMBOL BUKA KAMERA BARCODE HP -->
            <button type="button" onclick="openCameraScanner()"
                    class="px-3.5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer shrink-0 transition-all hover:scale-[1.02]">
                <span class="text-base">📷</span>
                <span class="hidden sm:inline">Kamera Scan</span>
            </button>
        </div>

        <!-- FILTER RAK & KATEGORI -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            <div>
                <select id="rackFilter" onchange="filterItems()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    <option value="">📍 Semua Lokasi Rak</option>
                    @foreach($racks as $rack)
                        <option value="{{ $rack }}">{{ $rack }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select id="categoryFilter" onchange="filterItems()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs text-slate-700 dark:text-slate-200 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    <option value="">📂 Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- TABS STATUS HITUNG: SEMUA / BELUM / SUDAH -->
        <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-900/60 p-1 rounded-xl border border-slate-200 dark:border-slate-700/60 text-xs font-bold">
            <button type="button" onclick="setStatusTab('all')" id="tab-all" class="flex-1 py-1.5 rounded-lg text-center bg-blue-600 text-white shadow-xs transition-all cursor-pointer">
                Semua (<span id="countAll">{{ count($items) }}</span>)
            </button>
            <button type="button" onclick="setStatusTab('uncounted')" id="tab-uncounted" class="flex-1 py-1.5 rounded-lg text-center text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer">
                Belum (<span id="countUncounted">0</span>)
            </button>
            <button type="button" onclick="setStatusTab('counted')" id="tab-counted" class="flex-1 py-1.5 rounded-lg text-center text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer">
                Sudah (<span id="countCounted">0</span>)
            </button>
        </div>
    </div>

    <!-- LIST KARTU PRODUK (GRID RESPONSIVE: 1 COL MOBILE, 2 COLS TABLET/DESKTOP) -->
    <div id="itemsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($items as $idx => $it)
            <div class="product-card bg-white dark:bg-slate-800 border {{ $it['actual_quantity'] > 0 ? 'border-emerald-500/80 bg-emerald-50/40 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-700/80' }} rounded-2xl p-4 shadow-xs transition-all relative flex flex-col justify-between"
                 id="card-{{ $it['product_id'] }}"
                 data-product-id="{{ $it['product_id'] }}"
                 data-barcode="{{ implode(',', $it['barcodes']) }}"
                 data-sku="{{ $it['sku'] }}"
                 data-name="{{ strtolower($it['name']) }}"
                 data-rack="{{ $it['rack_location'] }}"
                 data-category="{{ $it['category_name'] }}"
                 data-counted="{{ $it['actual_quantity'] > 0 ? '1' : '0' }}">

                <!-- CARD HEADER: NAMA, SKU, RAK -->
                <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex-1">
                            <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white leading-snug line-clamp-2">
                                {{ $it['name'] }}
                            </h4>
                            <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                                    {{ $it['sku'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                    📍 {{ $it['rack_location'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 truncate max-w-[120px]">
                                    {{ $it['category_name'] }}
                                </span>
                            </div>

                            <!-- BADGE RINCIAN MULTI-SATUAN (JIKA ADA DUS/PACK/BKS) -->
                            <div id="notes-badge-{{ $it['product_id'] }}" class="{{ empty($it['notes']) ? 'hidden ' : '' }}mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10.5px] font-bold bg-blue-50 dark:bg-blue-950/70 text-blue-700 dark:text-blue-300 border border-blue-200/90 dark:border-blue-800">
                                <span>📦 Rincian:</span>
                                <span id="notes-text-{{ $it['product_id'] }}" class="font-semibold">{{ $it['notes'] }}</span>
                            </div>
                        </div>

                        <!-- STATUS BADGE -->
                        <div class="shrink-0">
                            <span id="badge-{{ $it['product_id'] }}"
                                  class="status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold {{ $it['actual_quantity'] > 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700' : 'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:border-slate-600' }}">
                                {{ $it['actual_quantity'] > 0 ? '✓ Terhitung' : 'Belum' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- TOUCH NUMPAD & QUICK BUTTONS -->
                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-700/80 flex flex-wrap items-center justify-between gap-2">
                    <!-- Tombol Cepat Satuan & Tambah (+1, +5, +10) -->
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="openMultiUnitModal('{{ $it['product_id'] }}')"
                                class="px-2.5 py-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:from-blue-800 active:to-indigo-800 text-white text-xs font-bold shadow-xs flex items-center gap-1 cursor-pointer transition-all hover:scale-[1.02]">
                            <span>📦</span>
                            <span>Hitung Satuan</span>
                        </button>
                        <button type="button" onclick="quickAdd('{{ $it['product_id'] }}', 1)"
                                class="px-2 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-600 transition-colors">
                            +1
                        </button>
                        <button type="button" onclick="quickAdd('{{ $it['product_id'] }}', 5)"
                                class="px-2 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-600 transition-colors">
                            +5
                        </button>
                    </div>

                    <!-- Input Kuantitas Fisik Riil -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" onclick="stepCount('{{ $it['product_id'] }}', -1)"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-100 hover:text-rose-600 active:bg-rose-200 dark:bg-slate-700 dark:hover:bg-rose-900/40 dark:hover:text-rose-300 text-slate-700 dark:text-slate-200 font-extrabold text-sm flex items-center justify-center border border-slate-200 dark:border-slate-600 transition-colors cursor-pointer">
                            -
                        </button>

                        <button type="button" onclick="openMultiUnitModal('{{ $it['product_id'] }}')"
                                id="display-{{ $it['product_id'] }}"
                                class="px-2.5 h-8 min-w-[4.5rem] rounded-xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-600 font-mono font-extrabold text-xs sm:text-sm text-center text-slate-900 dark:text-white flex items-center justify-center gap-1 shadow-inner hover:border-blue-500 cursor-pointer transition-colors"
                                title="Klik untuk membuka kalkulator / numpad">
                            <span id="qty-val-{{ $it['product_id'] }}">{{ (float)$it['actual_quantity'] }}</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase">{{ $it['base_unit'] }}</span>
                        </button>

                        <button type="button" onclick="stepCount('{{ $it['product_id'] }}', 1)"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-emerald-100 hover:text-emerald-600 active:bg-emerald-200 dark:bg-slate-700 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300 text-slate-700 dark:text-slate-200 font-extrabold text-sm flex items-center justify-center border border-slate-200 dark:border-slate-600 transition-colors cursor-pointer">
                            +
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- EMPTY FILTER NOTICE -->
    <div id="noResultsNotice" class="hidden text-center py-12 text-slate-400 dark:text-slate-500 text-xs">
        <div class="text-4xl mb-2">🔍</div>
        <div class="font-bold text-slate-700 dark:text-slate-300 text-sm">Tidak ada produk yang cocok</div>
        <div class="text-xs mt-1">Periksa kata kunci pencarian atau ganti filter rak/kategori.</div>
    </div>
</div>

<!-- ============================================================== -->
<!-- BOTTOM STICKY ACTION BAR -->
<!-- ============================================================== -->
<div class="fixed bottom-0 inset-x-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-4 py-3 shadow-xl transition-colors">
    <div class="max-w-4xl mx-auto flex items-center justify-between">
        <div>
            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Progres Hitung Fisik</div>
            <div class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white" id="bottomSummaryText">
                0 Terhitung • 0 Belum
            </div>
        </div>

        <button type="button" onclick="confirmSubmitOpname()"
                class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-md shadow-emerald-600/20 flex items-center gap-1.5 cursor-pointer transition-all hover:scale-[1.02]">
            <span>✅</span>
            <span>Selesaikan Opname</span>
        </button>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL KAMERA SCANNER BARCODE HP (HTML5 QR CODE SCANNER) -->
<!-- ============================================================== -->
<div id="cameraScannerModal" class="fixed inset-0 z-50 flex flex-col bg-slate-950/95 backdrop-blur-md transition-opacity duration-200" style="display: none;">
    <!-- Scanner Header -->
    <div class="p-4 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800 z-10">
        <div class="flex items-center gap-2">
            <span class="text-lg">📷</span>
            <div>
                <span class="text-xs font-bold block">Scan Barcode Produk di Rak</span>
                <span class="text-[10px] text-slate-400">Scanner otomatis mendeteksi barcode EAN/UPC/Code128</span>
            </div>
        </div>
        <button type="button" onclick="closeCameraScanner()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl cursor-pointer">
            ✕ Tutup
        </button>
    </div>

    <!-- Scanner Camera Viewport -->
    <div class="flex-1 flex flex-col items-center justify-center relative bg-black overflow-hidden p-4">
        <div id="interactiveScanner" class="w-full max-w-sm aspect-square bg-slate-950 rounded-2xl overflow-hidden border border-slate-800 relative"></div>
        <div class="mt-4 px-4 py-2 rounded-full bg-slate-900/90 border border-slate-700 text-xs text-slate-300 pointer-events-none">
            Arahkan kamera HP ke barcode rak/kemasan barang
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL HYBRID KALKULATOR MULTI-SATUAN & TOUCH NUMPAD -->
<!-- ============================================================== -->
<div id="multiUnitModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-950/75 backdrop-blur-xs" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-t-3xl sm:rounded-3xl w-full max-w-lg p-5 sm:p-6 shadow-2xl animate-in slide-in-from-bottom duration-150 transition-colors max-h-[92vh] flex flex-col">
        
        <!-- MODAL HEADER -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3 shrink-0">
            <div>
                <div class="flex items-center gap-1.5 text-[10px] uppercase font-bold tracking-wider text-blue-600 dark:text-blue-400">
                    <span>📦 Kalkulator Hitungan Fisik</span>
                </div>
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white line-clamp-1 mt-0.5" id="calcProductName">
                    Nama Produk
                </h3>
                <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <span id="calcProductSku" class="font-mono font-bold bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">SKU</span>
                    <span>•</span>
                    <span id="calcProductRack">Rak: -</span>
                </div>
            </div>
            <button type="button" onclick="closeMultiUnitModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:white flex items-center justify-center font-bold text-lg cursor-pointer transition-colors">&times;</button>
        </div>

        <!-- TABS MODE: KALKULATOR KEMASAN vs NUMPAD LANGSUNG -->
        <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800/80 p-1 rounded-2xl mb-3 shrink-0 text-xs font-bold">
            <button type="button" onclick="setCalcTab('units')" id="btnTabUnits" class="flex-1 py-2 rounded-xl text-center bg-blue-600 text-white shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5">
                <span>📦</span>
                <span>Satuan Kemasan (Dus, Pack, Pcs)</span>
            </button>
            <button type="button" onclick="setCalcTab('numpad')" id="btnTabNumpad" class="flex-1 py-2 rounded-xl text-center text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer flex items-center justify-center gap-1.5">
                <span>🔢</span>
                <span>Numpad Langsung</span>
            </button>
        </div>

        <!-- TAB CONTENT 1: KALKULATOR MULTI-SATUAN (SCROLLABLE JIKA KEMASAN BANYAK) -->
        <div id="contentTabUnits" class="flex-1 overflow-y-auto space-y-3 pr-1">
            <div class="p-2.5 bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-900/60 rounded-xl text-[11px] text-blue-700 dark:text-blue-300 leading-snug">
                💡 Masukkan jumlah kemasan fisik yang ditemukan di rak/gudang. Sistem otomatis mengalikan faktor konversi ke satuan dasar.
            </div>

            <!-- CONTAINER LIST SATUAN DINAMIS -->
            <div id="unitsInputsContainer" class="space-y-2.5">
                <!-- Diisi via JS: baris per satuan (Dus, Bal, Renteng, Pcs) -->
            </div>
        </div>

        <!-- TAB CONTENT 2: NUMPAD VIRTUAL LANGSUNG -->
        <div id="contentTabNumpad" class="flex-1 overflow-y-auto space-y-3" style="display: none;">
            <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 text-center">
                <div class="text-xs text-slate-400 font-semibold mb-1">Total Satuan Dasar Langsung:</div>
                <div class="text-3xl font-mono font-black text-slate-900 dark:text-white" id="numpadDirectDisplay">0</div>
                <div class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase mt-0.5" id="numpadBaseUnitLabel">PCS</div>
            </div>

            <!-- Numpad Grid -->
            <div class="grid grid-cols-3 gap-2 font-mono font-bold text-base">
                <button type="button" onclick="calcNumpadPress('1')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">1</button>
                <button type="button" onclick="calcNumpadPress('2')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">2</button>
                <button type="button" onclick="calcNumpadPress('3')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">3</button>
                <button type="button" onclick="calcNumpadPress('4')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">4</button>
                <button type="button" onclick="calcNumpadPress('5')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">5</button>
                <button type="button" onclick="calcNumpadPress('6')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">6</button>
                <button type="button" onclick="calcNumpadPress('7')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">7</button>
                <button type="button" onclick="calcNumpadPress('8')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">8</button>
                <button type="button" onclick="calcNumpadPress('9')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">9</button>
                <button type="button" onclick="calcNumpadClear()" class="py-2.5 bg-rose-50 hover:bg-rose-100 active:bg-rose-200 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-600 dark:text-rose-400 rounded-2xl text-sm font-black transition-colors cursor-pointer">C</button>
                <button type="button" onclick="calcNumpadPress('0')" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">0</button>
                <button type="button" onclick="calcNumpadBackspace()" class="py-2.5 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-600 dark:text-slate-300 transition-colors cursor-pointer">⌫</button>
            </div>
        </div>

        <!-- TOTAL CALCULATION FOOTER CARD (STICKY BOTTOM IN MODAL) -->
        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 shrink-0 space-y-3">
            <div class="bg-gradient-to-r from-blue-900/90 to-indigo-900/90 border border-blue-500/30 rounded-2xl p-3 text-white flex items-center justify-between">
                <div>
                    <div class="text-[10px] text-blue-200 font-bold uppercase tracking-wider">Total Hitungan Fisik Riil</div>
                    <div class="text-xs text-blue-100 line-clamp-1 mt-0.5" id="calcFormulaBreakdown">0 Satuan</div>
                </div>
                <div class="text-right">
                    <span class="text-2xl sm:text-3xl font-mono font-black text-cyan-300" id="calcTotalDisplay">0</span>
                    <span class="text-xs font-bold text-blue-200 ml-1 uppercase" id="calcBaseUnitLabel">PCS</span>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeMultiUnitModal()"
                        class="px-4 py-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl cursor-pointer transition-colors">
                    Batal
                </button>
                <button type="button" onclick="saveMultiUnitCalculation()"
                        class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 active:from-blue-800 active:to-indigo-800 text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-md shadow-blue-600/25 cursor-pointer transition-all flex items-center justify-center gap-2">
                    <span>💾</span>
                    <span id="btnSaveCalcText">Simpan Hitungan Fisik</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL KONFIRMASI SUBMIT SESI -->
<!-- ============================================================== -->
<div id="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-sm p-6 shadow-2xl animate-in zoom-in-95 duration-150 transition-colors">
        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white flex items-center gap-2 mb-2">
            <span>✅</span> Selesaikan Hitungan Fisik?
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-5 leading-relaxed" id="confirmSummaryDesc">
            Apakah Anda yakin ingin menyelesaikan sesi hitung fisik ini? Seluruh hitungan akan dikirim ke Manajer untuk rekonsiliasi selisih.
        </p>

        <form action="{{ route('warehouse.opname-pwa.submit', $stockOpname->id) }}" method="POST">
            @csrf
            <div class="flex items-center justify-end gap-2.5">
                <button type="button" onclick="document.getElementById('confirmModal').style.display='none'"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl cursor-pointer transition-colors">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md cursor-pointer transition-colors">
                    Ya, Selesaikan
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const OPNAME_ID = '{{ $stockOpname->id }}';
const PWA_ITEMS = @json($items->keyBy('product_id'));

let activeTab = 'all'; // all, uncounted, counted
let html5QrCode = null;

// Multi-Unit Calculator State
let activeCalcProductId = null;
let calcActiveTab = 'units'; // 'units' or 'numpad'
let calcUnitCounts = {};
let calcNumpadBuffer = '0';
let currentCalcTotal = 0;
let currentCalcNotes = '';

// Audio Beep
function playBeep() {
    if (window.AudioEngine && typeof AudioEngine.success === 'function') {
        AudioEngine.success();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Pulihkan hitungan offline dari localStorage jika ada
    try {
        const storageKey = 'sigma_opname_{{ $stockOpname->id }}';
        const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        Object.keys(saved).forEach(pId => {
            const entry = saved[pId];
            const qty = typeof entry === 'object' && entry !== null ? parseFloat(entry.qty || 0) : parseFloat(entry || 0);
            const notes = typeof entry === 'object' && entry !== null ? (entry.notes || '') : '';

            const card = document.getElementById(`card-${pId}`);
            const qtyVal = document.getElementById(`qty-val-${pId}`);
            const badge = document.getElementById(`badge-${pId}`);
            const notesBadge = document.getElementById(`notes-badge-${pId}`);
            const notesText = document.getElementById(`notes-text-${pId}`);

            if (card && qtyVal && parseFloat(qtyVal.innerText || 0) === 0 && qty > 0) {
                qtyVal.innerText = qty;
                card.dataset.counted = '1';
                card.classList.add('border-emerald-500/80', 'bg-emerald-50/40', 'dark:bg-emerald-950/20');
                card.classList.remove('border-slate-200', 'dark:border-slate-700/80');
                if (badge) {
                    badge.className = 'status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700';
                    badge.innerText = '✓ Terhitung';
                }
                if (notes && notesBadge && notesText) {
                    notesText.innerText = notes;
                    notesBadge.classList.remove('hidden');
                }
                if (PWA_ITEMS[pId]) {
                    PWA_ITEMS[pId].actual_quantity = qty;
                    PWA_ITEMS[pId].notes = notes;
                }
            }
        });
    } catch(e) {}

    updateProgressCounters();

    // Live search input
    document.getElementById('searchInput')?.addEventListener('input', () => {
        filterItems();
    });
});

function filterItems() {
    const q = document.getElementById('searchInput')?.value.trim().toLowerCase() || '';
    const selectedRack = document.getElementById('rackFilter')?.value || '';
    const selectedCat = document.getElementById('categoryFilter')?.value || '';

    const cards = document.querySelectorAll('.product-card');
    let visibleCount = 0;

    cards.forEach(card => {
        const name = card.dataset.name || '';
        const sku = (card.dataset.sku || '').toLowerCase();
        const barcode = (card.dataset.barcode || '').toLowerCase();
        const rack = card.dataset.rack || '';
        const cat = card.dataset.category || '';
        const isCounted = card.dataset.counted === '1';

        // Filter text
        const matchText = !q || name.includes(q) || sku.includes(q) || barcode.includes(q);
        // Filter rack
        const matchRack = !selectedRack || rack === selectedRack;
        // Filter category
        const matchCat = !selectedCat || cat === selectedCat;
        // Filter tab
        let matchTab = true;
        if (activeTab === 'uncounted') matchTab = !isCounted;
        if (activeTab === 'counted') matchTab = isCounted;

        if (matchText && matchRack && matchCat && matchTab) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    const notice = document.getElementById('noResultsNotice');
    if (notice) {
        notice.style.display = visibleCount === 0 ? 'block' : 'none';
    }
}

function setStatusTab(tab) {
    activeTab = tab;
    ['all', 'uncounted', 'counted'].forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        if (t === tab) {
            btn.className = 'flex-1 py-1.5 rounded-lg text-center bg-blue-600 text-white shadow-xs transition-all cursor-pointer';
        } else {
            btn.className = 'flex-1 py-1.5 rounded-lg text-center text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer';
        }
    });
    filterItems();
}

function quickAdd(productId, delta) {
    const item = PWA_ITEMS[productId];
    let current = parseFloat(item?.actual_quantity || 0);
    current = Math.max(0, current + delta);
    saveItemCount(productId, current, item?.notes || null);
}

function stepCount(productId, step) {
    quickAdd(productId, step);
}

function saveItemCount(productId, count, notes = null) {
    const card = document.getElementById(`card-${productId}`);
    const qtyVal = document.getElementById(`qty-val-${productId}`);
    const badge = document.getElementById(`badge-${productId}`);
    const notesBadge = document.getElementById(`notes-badge-${productId}`);
    const notesText = document.getElementById(`notes-text-${productId}`);

    if (qtyVal) qtyVal.innerText = count;
    if (card) card.dataset.counted = count > 0 ? '1' : '0';

    if (count > 0) {
        if (card) {
            card.classList.add('border-emerald-500/80', 'bg-emerald-50/40', 'dark:bg-emerald-950/20');
            card.classList.remove('border-slate-200', 'dark:border-slate-700/80');
        }
        if (badge) {
            badge.className = 'status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700';
            badge.innerText = '✓ Terhitung';
        }
    } else {
        if (card) {
            card.classList.remove('border-emerald-500/80', 'bg-emerald-50/40', 'dark:bg-emerald-950/20');
            card.classList.add('border-slate-200', 'dark:border-slate-700/80');
        }
        if (badge) {
            badge.className = 'status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:border-slate-600';
            badge.innerText = 'Belum';
        }
    }

    if (notes && notes.trim() !== '') {
        if (notesBadge) notesBadge.classList.remove('hidden');
        if (notesText) notesText.innerText = notes;
    } else {
        if (notesBadge) notesBadge.classList.add('hidden');
    }

    if (PWA_ITEMS[productId]) {
        PWA_ITEMS[productId].actual_quantity = count;
        PWA_ITEMS[productId].notes = notes;
    }

    playBeep();
    updateProgressCounters();

    // Simpan ke localStorage sebagai proteksi offline jika sinyal Wi-Fi gudang terputus
    try {
        const storageKey = 'sigma_opname_{{ $stockOpname->id }}';
        const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        saved[productId] = { qty: count, notes: notes };
        localStorage.setItem(storageKey, JSON.stringify(saved));
    } catch(e) {}

    // Kirim secara atomik ke backend API
    const url = `{{ route('warehouse.opname-pwa.update-item', $stockOpname->id) }}`;
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            product_id: productId,
            actual_quantity: count,
            notes: notes
        })
    }).catch(err => {
        console.error('Save offline fallback', err);
    });
}

function updateProgressCounters() {
    const cards = document.querySelectorAll('.product-card');
    let counted = 0;
    cards.forEach(c => {
        if (c.dataset.counted === '1') counted++;
    });

    const total = cards.length;
    const uncounted = total - counted;
    const pct = total > 0 ? Math.round((counted / total) * 100) : 0;

    document.getElementById('progressText').innerText = `${counted} / ${total} SKU (${pct}%)`;
    document.getElementById('progressBar').style.width = `${pct}%`;
    document.getElementById('countAll').innerText = total;
    document.getElementById('countUncounted').innerText = uncounted;
    document.getElementById('countCounted').innerText = counted;
    document.getElementById('bottomSummaryText').innerText = `${counted} Terhitung • ${uncounted} Belum`;
}

// ==========================================
// HYBRID MULTI-UNIT & NUMPAD MODAL
// ==========================================
function openMultiUnitModal(productId) {
    activeCalcProductId = productId;
    const prod = PWA_ITEMS[productId];
    if (!prod) return;

    document.getElementById('calcProductName').innerText = prod.name;
    document.getElementById('calcProductSku').innerText = prod.sku;
    document.getElementById('calcProductRack').innerText = `Rak: ${prod.rack_location}`;
    document.getElementById('calcBaseUnitLabel').innerText = prod.base_unit;
    document.getElementById('numpadBaseUnitLabel').innerText = prod.base_unit;

    // Reset buffer numpad
    const curQty = parseFloat(prod.actual_quantity || 0);
    calcNumpadBuffer = curQty > 0 ? curQty.toString() : '0';
    document.getElementById('numpadDirectDisplay').innerText = calcNumpadBuffer;

    // Render multi-unit container
    const container = document.getElementById('unitsInputsContainer');
    container.innerHTML = '';
    calcUnitCounts = {};

    prod.units.forEach(u => {
        calcUnitCounts[u.unit_code] = 0;

        const row = document.createElement('div');
        row.className = 'bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 flex items-center justify-between gap-3';
        
        const isBase = u.is_base_unit || u.conversion_factor === 1;
        const convLabel = isBase 
            ? `<span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase">Satuan Dasar</span>`
            : `<span class="text-[10px] text-slate-400">1 ${u.unit_code} = ${u.conversion_factor} ${prod.base_unit}</span>`;

        row.innerHTML = `
            <div class="flex-1">
                <div class="font-extrabold text-xs text-slate-900 dark:text-white">${u.unit_name} (${u.unit_code})</div>
                <div>${convLabel}</div>
                <div class="text-[11px] font-mono font-bold text-slate-500 dark:text-slate-400 mt-0.5" id="subtotal_${u.unit_code}">
                    = 0 ${prod.base_unit}
                </div>
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button" onclick="stepUnitCalc('${u.unit_code}', -1)"
                        class="w-9 h-9 rounded-xl bg-slate-200 hover:bg-rose-100 hover:text-rose-600 dark:bg-slate-700 dark:hover:bg-rose-950 font-black text-sm flex items-center justify-center cursor-pointer transition-colors">-</button>
                <input type="number" step="any" min="0" id="unit_input_${u.unit_code}"
                       value="0" oninput="onUnitCalcInput('${u.unit_code}')"
                       class="w-16 h-9 px-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 rounded-xl text-center font-mono font-black text-sm focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="stepUnitCalc('${u.unit_code}', 1)"
                        class="w-9 h-9 rounded-xl bg-slate-200 hover:bg-emerald-100 hover:text-emerald-600 dark:bg-slate-700 dark:hover:bg-emerald-950 font-black text-sm flex items-center justify-center cursor-pointer transition-colors">+</button>
            </div>
        `;
        container.appendChild(row);
    });

    // Coba parse rincian existing dari notes jika ada (misal: "2 DUS, 1 PCS")
    const existingNotes = (prod.notes || '').trim();
    if (existingNotes) {
        parseNotesIntoUnitCounts(existingNotes, prod);
    } else if (curQty > 0 && prod.units.length === 1) {
        const u = prod.units[0];
        calcUnitCounts[u.unit_code] = curQty;
        const el = document.getElementById('unit_input_' + u.unit_code);
        if (el) el.value = curQty;
    }

    // Default tab: jika produk punya multi-satuan (>1), buka tab kemasan; jika cuma 1 satuan (hanya PCS), buka numpad
    if (prod.units.length > 1) {
        setCalcTab('units');
    } else {
        setCalcTab('numpad');
    }

    recalcMultiUnits();
    document.getElementById('multiUnitModal').style.display = 'flex';
}

function parseNotesIntoUnitCounts(notes, prod) {
    const parts = notes.split(',');
    parts.forEach(p => {
        const trimmed = p.trim();
        const match = trimmed.match(/^([\d\.]+)\s*(.+)$/i);
        if (match) {
            const qty = parseFloat(match[1]);
            const code = match[2].trim().toUpperCase();
            if (calcUnitCounts.hasOwnProperty(code)) {
                calcUnitCounts[code] = qty;
                const el = document.getElementById('unit_input_' + code);
                if (el) el.value = qty;
            }
        }
    });
}

function setCalcTab(tab) {
    calcActiveTab = tab;
    const btnUnits = document.getElementById('btnTabUnits');
    const btnNumpad = document.getElementById('btnTabNumpad');
    const contentUnits = document.getElementById('contentTabUnits');
    const contentNumpad = document.getElementById('contentTabNumpad');

    if (tab === 'units') {
        btnUnits.className = 'flex-1 py-2 rounded-xl text-center bg-blue-600 text-white shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5';
        btnNumpad.className = 'flex-1 py-2 rounded-xl text-center text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer flex items-center justify-center gap-1.5';
        contentUnits.style.display = 'block';
        contentNumpad.style.display = 'none';
        recalcMultiUnits();
    } else {
        btnNumpad.className = 'flex-1 py-2 rounded-xl text-center bg-blue-600 text-white shadow-xs transition-all cursor-pointer flex items-center justify-center gap-1.5';
        btnUnits.className = 'flex-1 py-2 rounded-xl text-center text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer flex items-center justify-center gap-1.5';
        contentUnits.style.display = 'none';
        contentNumpad.style.display = 'block';
        recalcDirectNumpad();
    }
}

function stepUnitCalc(code, step) {
    const el = document.getElementById('unit_input_' + code);
    let val = parseFloat(el?.value || 0) + step;
    val = Math.max(0, val);
    if (el) el.value = val;
    calcUnitCounts[code] = val;
    recalcMultiUnits();
}

function onUnitCalcInput(code) {
    const el = document.getElementById('unit_input_' + code);
    let val = Math.max(0, parseFloat(el?.value || 0));
    calcUnitCounts[code] = val;
    recalcMultiUnits();
}

function recalcMultiUnits() {
    if (!activeCalcProductId) return;
    const prod = PWA_ITEMS[activeCalcProductId];
    let total = 0;
    let parts = [];

    prod.units.forEach(u => {
        const count = parseFloat(calcUnitCounts[u.unit_code] || 0);
        const sub = count * u.conversion_factor;
        total += sub;

        const subEl = document.getElementById(`subtotal_${u.unit_code}`);
        if (subEl) {
            subEl.innerText = `= ${sub} ${prod.base_unit}`;
        }

        if (count > 0) {
            parts.push(`${count} ${u.unit_code}`);
        }
    });

    currentCalcTotal = total;
    currentCalcNotes = parts.join(', ');

    document.getElementById('calcTotalDisplay').innerText = total;
    document.getElementById('calcFormulaBreakdown').innerText = parts.length > 0 ? parts.join(' + ') : '0 Kemasan';
    document.getElementById('btnSaveCalcText').innerText = `Simpan (${total} ${prod.base_unit})`;
}

// Numpad Direct logic
function calcNumpadPress(digit) {
    if (calcNumpadBuffer === '0') calcNumpadBuffer = '';
    calcNumpadBuffer += digit;
    recalcDirectNumpad();
}

function calcNumpadClear() {
    calcNumpadBuffer = '0';
    recalcDirectNumpad();
}

function calcNumpadBackspace() {
    calcNumpadBuffer = calcNumpadBuffer.slice(0, -1);
    if (!calcNumpadBuffer) calcNumpadBuffer = '0';
    recalcDirectNumpad();
}

function recalcDirectNumpad() {
    if (!activeCalcProductId) return;
    const prod = PWA_ITEMS[activeCalcProductId];
    const total = parseFloat(calcNumpadBuffer || 0);

    currentCalcTotal = total;
    currentCalcNotes = ''; // Numpad langsung tidak menyimpan breakdown satuan kemasan

    document.getElementById('numpadDirectDisplay').innerText = calcNumpadBuffer;
    document.getElementById('calcTotalDisplay').innerText = total;
    document.getElementById('calcFormulaBreakdown').innerText = `Input Langsung: ${total} ${prod.base_unit}`;
    document.getElementById('btnSaveCalcText').innerText = `Simpan (${total} ${prod.base_unit})`;
}

function saveMultiUnitCalculation() {
    if (!activeCalcProductId) return;
    saveItemCount(activeCalcProductId, currentCalcTotal, currentCalcNotes);
    closeMultiUnitModal();
}

function closeMultiUnitModal() {
    document.getElementById('multiUnitModal').style.display = 'none';
    activeCalcProductId = null;
}

// ==========================================
// KAMERA SCANNER BARCODE HP
// ==========================================
function openCameraScanner() {
    const modal = document.getElementById('cameraScannerModal');
    modal.style.display = 'flex';

    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("interactiveScanner");
    }

    const config = { fps: 15, qrbox: { width: 250, height: 180 } };

    html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
        handleBarcodeScanned(decodedText.trim());
    }).catch(err => {
        console.error('Camera access error', err);
        alert('Kamera tidak dapat diakses. Pastikan izin kamera telah diberikan di browser HP Anda.');
        closeCameraScanner();
    });
}

function closeCameraScanner() {
    const modal = document.getElementById('cameraScannerModal');
    modal.style.display = 'none';

    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
        }).catch(e => console.log(e));
    }
}

function handleBarcodeScanned(code) {
    if (navigator.vibrate) navigator.vibrate(100);
    playBeep();

    // Cari produk dengan barcode tersebut
    const targetCard = Array.from(document.querySelectorAll('.product-card')).find(c => {
        const barcodes = (c.dataset.barcode || '').split(',');
        return barcodes.includes(code) || c.dataset.sku === code;
    });

    closeCameraScanner();

    if (targetCard) {
        const pId = targetCard.dataset.productId;
        // Scroll ke card produk dan highlight
        targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetCard.classList.add('ring-4', 'ring-blue-500');
        setTimeout(() => targetCard.classList.remove('ring-4', 'ring-blue-500'), 1500);

        // Buka langsung modal hitung satuan untuk produk yang discan!
        openMultiUnitModal(pId);
    } else {
        alert(`Barcode "${code}" tidak ditemukan pada daftar barang sesi opname ini.`);
    }
}

function confirmSubmitOpname() {
    const cards = document.querySelectorAll('.product-card');
    let counted = 0;
    cards.forEach(c => {
        if (c.dataset.counted === '1') counted++;
    });

    const uncounted = cards.length - counted;
    document.getElementById('confirmSummaryDesc').innerText =
        `Dari ${cards.length} SKU barang, Anda telah menghitung ${counted} produk (${uncounted} belum dihitung / 0). Apakah Anda yakin ingin menyelesaikan dan mengirimkan ke Manajer?`;

    document.getElementById('confirmModal').style.display = 'flex';
}
</script>
@endpush
@endsection
