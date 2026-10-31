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
                <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-700/80 flex items-center justify-between gap-2">
                    <!-- Tombol Cepat Tambah (+1, +5, +10) -->
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="quickAdd('{{ $it['product_id'] }}', 1)"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-600 transition-colors">
                            +1
                        </button>
                        <button type="button" onclick="quickAdd('{{ $it['product_id'] }}', 5)"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-600 transition-colors">
                            +5
                        </button>
                        <button type="button" onclick="quickAdd('{{ $it['product_id'] }}', 10)"
                                class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold border border-slate-200 dark:border-slate-600 transition-colors">
                            +10
                        </button>
                    </div>

                    <!-- Input Kuantitas Fisik Riil -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" onclick="stepCount('{{ $it['product_id'] }}', -1)"
                                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-100 hover:text-rose-600 active:bg-rose-200 dark:bg-slate-700 dark:hover:bg-rose-900/40 dark:hover:text-rose-300 text-slate-700 dark:text-slate-200 font-extrabold text-sm flex items-center justify-center border border-slate-200 dark:border-slate-600 transition-colors cursor-pointer">
                            -
                        </button>

                        <button type="button" onclick="openNumpadModal('{{ $it['product_id'] }}', '{{ addslashes($it['name']) }}')"
                                id="display-{{ $it['product_id'] }}"
                                class="w-16 h-8 rounded-xl bg-slate-50 dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-600 font-mono font-extrabold text-sm text-center text-slate-900 dark:text-white flex items-center justify-center shadow-inner hover:border-blue-500 cursor-pointer transition-colors">
                            {{ $it['actual_quantity'] }}
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
<!-- MODAL TOUCH NUMPAD VIRTUAL UNTUK INPUT ANGKA BESAR -->
<!-- ============================================================== -->
<div id="numpadModal" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-950/70 backdrop-blur-xs" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-t-3xl sm:rounded-3xl w-full max-w-sm p-5 shadow-2xl animate-in slide-in-from-bottom duration-150 transition-colors">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
            <div>
                <h3 class="text-xs font-bold text-slate-900 dark:text-white line-clamp-1" id="numpadProductName">Nama Produk</h3>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Input Kuantitas Fisik Riil</span>
            </div>
            <button type="button" onclick="closeNumpadModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white font-bold text-lg cursor-pointer">&times;</button>
        </div>

        <!-- Input Display -->
        <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 text-center mb-4">
            <div class="text-3xl font-mono font-black text-slate-900 dark:text-white" id="numpadValueDisplay">0</div>
        </div>

        <!-- Numpad Grid -->
        <div class="grid grid-cols-3 gap-2 font-mono font-bold text-base">
            <button type="button" onclick="numpadPress('1')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">1</button>
            <button type="button" onclick="numpadPress('2')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">2</button>
            <button type="button" onclick="numpadPress('3')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">3</button>
            <button type="button" onclick="numpadPress('4')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">4</button>
            <button type="button" onclick="numpadPress('5')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">5</button>
            <button type="button" onclick="numpadPress('6')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">6</button>
            <button type="button" onclick="numpadPress('7')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">7</button>
            <button type="button" onclick="numpadPress('8')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">8</button>
            <button type="button" onclick="numpadPress('9')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">9</button>
            <button type="button" onclick="numpadClear()" class="py-3 bg-rose-50 hover:bg-rose-100 active:bg-rose-200 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-600 dark:text-rose-400 rounded-2xl text-sm font-black transition-colors cursor-pointer">C</button>
            <button type="button" onclick="numpadPress('0')" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-900 dark:text-white transition-colors cursor-pointer">0</button>
            <button type="button" onclick="numpadBackspace()" class="py-3 bg-slate-100 hover:bg-slate-200 active:bg-blue-600 active:text-white dark:bg-slate-800 dark:hover:bg-slate-700 rounded-2xl text-slate-600 dark:text-slate-300 transition-colors cursor-pointer">⌫</button>
        </div>

        <!-- Tombol Simpan Numpad -->
        <div class="mt-4">
            <button type="button" onclick="saveNumpadValue()" class="w-full py-3 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 font-extrabold text-xs sm:text-sm text-white rounded-xl shadow-md transition-colors cursor-pointer">
                Simpan Hitungan
            </button>
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
let activeTab = 'all'; // all, uncounted, counted
let activeNumpadProductId = null;
let numpadBuffer = '0';
let html5QrCode = null;

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
            const card = document.getElementById(`card-${pId}`);
            const display = document.getElementById(`display-${pId}`);
            const badge = document.getElementById(`badge-${pId}`);
            if (card && display && parseFloat(display.innerText || 0) === 0 && saved[pId] > 0) {
                display.innerText = saved[pId];
                card.dataset.counted = '1';
                card.classList.add('border-emerald-500/80', 'bg-emerald-50/40', 'dark:bg-emerald-950/20');
                card.classList.remove('border-slate-200', 'dark:border-slate-700/80');
                if (badge) {
                    badge.className = 'status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700';
                    badge.innerText = '✓ Terhitung';
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
    const display = document.getElementById(`display-${productId}`);
    let current = parseFloat(display?.innerText || 0);
    current = Math.max(0, current + delta);
    saveItemCount(productId, current);
}

function stepCount(productId, step) {
    quickAdd(productId, step);
}

function saveItemCount(productId, count) {
    const display = document.getElementById(`display-${productId}`);
    const card = document.getElementById(`card-${productId}`);
    const badge = document.getElementById(`badge-${productId}`);

    display.innerText = count;
    card.dataset.counted = count > 0 ? '1' : '0';

    if (count > 0) {
        card.classList.add('border-emerald-500/80', 'bg-emerald-50/40', 'dark:bg-emerald-950/20');
        card.classList.remove('border-slate-200', 'dark:border-slate-700/80');
        badge.className = 'status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700';
        badge.innerText = '✓ Terhitung';
    } else {
        card.classList.remove('border-emerald-500/80', 'bg-emerald-50/40', 'dark:bg-emerald-950/20');
        card.classList.add('border-slate-200', 'dark:border-slate-700/80');
        badge.className = 'status-indicator px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-700 dark:text-slate-400 dark:border-slate-600';
        badge.innerText = 'Belum';
    }

    playBeep();
    updateProgressCounters();

    // Simpan ke localStorage sebagai proteksi offline jika sinyal Wi-Fi gudang terputus
    try {
        const storageKey = 'sigma_opname_{{ $stockOpname->id }}';
        const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
        saved[productId] = count;
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
            actual_quantity: count
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
// NUMPAD VIRTUAL TOUCH
// ==========================================
function openNumpadModal(productId, productName) {
    activeNumpadProductId = productId;
    const display = document.getElementById(`display-${productId}`);
    numpadBuffer = display ? display.innerText.trim() : '0';
    if (numpadBuffer === '0') numpadBuffer = '';

    document.getElementById('numpadProductName').innerText = productName;
    document.getElementById('numpadValueDisplay').innerText = numpadBuffer || '0';
    document.getElementById('numpadModal').style.display = 'flex';
}

function closeNumpadModal() {
    document.getElementById('numpadModal').style.display = 'none';
}

function numpadPress(digit) {
    if (numpadBuffer === '0') numpadBuffer = '';
    numpadBuffer += digit;
    document.getElementById('numpadValueDisplay').innerText = numpadBuffer;
}

function numpadClear() {
    numpadBuffer = '';
    document.getElementById('numpadValueDisplay').innerText = '0';
}

function numpadBackspace() {
    numpadBuffer = numpadBuffer.slice(0, -1);
    document.getElementById('numpadValueDisplay').innerText = numpadBuffer || '0';
}

function saveNumpadValue() {
    const val = parseFloat(numpadBuffer || 0);
    if (activeNumpadProductId) {
        saveItemCount(activeNumpadProductId, val);
    }
    closeNumpadModal();
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

        // Auto increment count by +1
        quickAdd(pId, 1);
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
