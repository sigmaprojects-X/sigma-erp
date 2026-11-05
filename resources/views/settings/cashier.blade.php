@extends('layouts.app')

@section('title', 'Pengaturan Operasional Kasir & Modal Awal')
@section('tab_title', 'Operasional Kasir')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="cashierSettingsApp({
    defaultStartingCash: {{ (float) ($company->cashier_default_starting_cash ?? 200000) }},
    maxStartingCash: {{ (float) ($company->cashier_max_starting_cash ?? 2000000) }},
    requireStartingCash: {{ ($company->cashier_require_starting_cash ?? true) ? 'true' : 'false' }},
    maxChangeLimit: {{ (float) ($company->cashier_max_change_limit ?? 500000) }},
    cashDropThreshold: {{ (float) ($company->cashier_cash_drop_threshold ?? 2000000) }},
    cashDropWarning: {{ ($company->cashier_cash_drop_warning_enabled ?? true) ? 'true' : 'false' }},
    maxDiscrepancy: {{ (float) ($company->cashier_max_discrepancy_tolerance ?? 2000) }},
    allowPending: {{ ($company->cashier_allow_pending_transaction ?? true) ? 'true' : 'false' }},
    maxPending: {{ (int) ($company->cashier_max_pending_transactions ?? 5) }},
    minProfitPercentage: {{ (float) ($company->min_profit_percentage ?? 0.00) }},
    quickCashNominal: '{{ $company->cashier_quick_cash_nominal ?? '10000,20000,50000,100000,200000' }}'
})">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('settings.general') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Operasional Kasir &amp; Keamanan Kas</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>💼 Pengaturan Operasional Kasir, Modal Awal &amp; Limit Kas Drop</span>
            </h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('settings.general') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏢 Profil &amp; Pajak</span>
            </a>
            <a href="{{ route('settings.stores.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🏪 Cabang &amp; Gudang</span>
            </a>
            <a href="{{ route('settings.users.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>👥 Pengguna &amp; RBAC</span>
            </a>
            <a href="{{ route('settings.printer') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🖨️ Printer &amp; Struk</span>
            </a>
            <a href="{{ route('settings.numbering.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🔢 Penomoran</span>
            </a>
            <a href="{{ route('settings.maintenance.index') }}"
               class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-lg shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                <span>🛡️ Pemeliharaan &amp; Backup</span>
            </a>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-lg shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95 cursor-pointer">
                <span>⚡ BUKA KASIR [F12]</span>
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-xl text-xs font-semibold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span>{{ $errors->first() }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-600 hover:text-rose-900 font-bold">&times;</button>
        </div>
    @endif

    <!-- SUB-NAVIGATION TABS -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2 flex-wrap gap-y-1">
        <a href="{{ route('settings.general') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>⚙️ Umum &amp; Wallpaper</span>
        </a>
        <a href="{{ route('settings.users.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>👥 Pengguna &amp; Hak Akses (RBAC)</span>
        </a>
        <a href="{{ route('settings.printer') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>🖨️ Printer &amp; Struk Kasir</span>
        </a>
        <a href="{{ route('settings.numbering.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
            <span>🔢 Penomoran Otomatis</span>
        </a>
        <a href="{{ route('settings.cashier') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-black transition-all bg-blue-600 text-white shadow-sm flex items-center gap-1.5">
            <span>💼 Operasional Kasir</span>
        </a>
    </div>

    <!-- MAIN FORM -->
    <form action="{{ route('settings.cashier.update') }}" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- 1. MODAL AWAL KASIR (STARTING CASH) -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">💼</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Modal Awal Kasir (Starting Cash / Float)</h2>
                        <p class="text-xs text-slate-500">Standar uang receh awal saat kasir membuka shift baru</p>
                    </div>
                </div>

                <div class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Modal Awal Standar Kasir (Rp):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" name="cashier_default_starting_cash" x-model.number="defaultStartingCash" required min="0" step="1000"
                                   class="w-full text-xs font-mono font-bold py-2 pl-9 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <p class="text-[10.5px] text-slate-400 mt-1">Nilai ini otomatis terisi di dialog Buka Shift kasir untuk mempercepat proses buka toko.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Batas Maksimal Modal Awal yang Diizinkan (Rp):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" name="cashier_max_starting_cash" x-model.number="maxStartingCash" required min="0" step="1000"
                                   class="w-full text-xs font-mono font-bold py-2 pl-9 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <p class="text-[10.5px] text-slate-400 mt-1">Mencegah kasir salah ketik kelebihan digit nol saat menginput modal awal.</p>
                    </div>

                    <div class="pt-1">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="cashier_require_starting_cash" value="1" x-model="requireStartingCash"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Wajibkan Input Modal Awal Kasir</span>
                                <p class="text-[10.5px] text-slate-500 dark:text-slate-400">Mewajibkan kasir memiliki saldo modal awal saat memulai transaksi shift.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 2. AMBANG BATAS & PERINGATAN KAS DROP -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">🚨</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Ambang Batas Saldo Laci Kas &amp; Peringatan Kas Drop</h2>
                        <p class="text-xs text-slate-500">Mencegah uang tunai menumpuk di laci kasir demi keamanan toko</p>
                    </div>
                </div>

                <div class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Ambang Batas Maksimal Saldo Laci Kas (Rp):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" name="cashier_cash_drop_threshold" x-model.number="cashDropThreshold" required min="100000" step="50000"
                                   class="w-full text-xs font-mono font-bold py-2 pl-9 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <p class="text-[10.5px] text-slate-400 mt-1">Contoh: Rp 2.000.000. Bila uang tunai di laci mencapai batas ini, sistem akan memperingatkan kasir.</p>
                    </div>

                    <div class="pt-2">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="cashier_cash_drop_warning_enabled" value="1" x-model="cashDropWarning"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Aktifkan Banner Peringatan Kas Drop di Layar Kasir</span>
                                <p class="text-[10.5px] text-slate-500 dark:text-slate-400">Menampilkan banner peringatan berwarna oranye/merah mencolok di atas layar POS begitu saldo laci melampaui ambang batas.</p>
                            </div>
                        </label>
                    </div>

                    <!-- PREVIEW WARNING BANNER -->
                    <div x-show="cashDropWarning" class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 rounded-xl text-xs space-y-1">
                        <div class="font-bold text-amber-900 dark:text-amber-200 flex items-center gap-1.5">
                            <span>⚠️ Contoh Tampilan Peringatan di Layar Kasir:</span>
                        </div>
                        <p class="text-[11px] text-amber-800 dark:text-amber-300">
                            "Peringatan Keamanan Kas: Estimasi uang tunai di laci kasir saat ini mencapai Rp <span x-text="cashDropThreshold.toLocaleString('id-ID')"></span>. Harap segera lakukan Cash Drop ke brankas!"
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. LIMIT KEMBALIAN & TRANSAKSI PENDING (HOLD BILL) -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">🪙</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Limit Kembalian Tunai &amp; Antrean Transaksi Pending</h2>
                        <p class="text-xs text-slate-500">Pengendalian arus uang kembalian dan fitur hold bill kasir</p>
                    </div>
                </div>

                <div class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Batas Maksimum Kembalian Tunai per Transaksi (Rp):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" name="cashier_max_change_limit" x-model.number="maxChangeLimit" required min="0" step="50000"
                                   class="w-full text-xs font-mono font-bold py-2 pl-9 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <p class="text-[10.5px] text-slate-400 mt-1">Jika nilai kembalian melebihi batas ini, layar kasir akan memunculkan peringatan verifikasi uang.</p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-700 space-y-2">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="cashier_allow_pending_transaction" value="1" x-model="allowPending"
                                   class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4 mt-0.5 cursor-pointer">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white">Izinkan Kasir Menahan / Pending Transaksi (Hold Bill)</span>
                                <p class="text-[10.5px] text-slate-500 dark:text-slate-400">Kasir dapat menunda keranjang belanja pembeli yang sedang mengambil barang tambahan tanpa menghapus antrean.</p>
                            </div>
                        </label>

                        <div x-show="allowPending" class="pl-6 pt-1">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                                Maksimal Transaksi Tertahan Bersamaan:
                            </label>
                            <input type="number" name="cashier_max_pending_transactions" x-model.number="maxPending" min="1" max="20"
                                   class="w-32 text-xs font-mono font-bold py-1.5 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. TOLERANSI SELISIH KAS & BLIND CLOSING -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">⚖️</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Toleransi Selisih Kas Receh (Blind Closing)</h2>
                        <p class="text-xs text-slate-500">Kebijakan selisih uang fisik vs sistem saat kasir tutup shift</p>
                    </div>
                </div>

                <div class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Batas Toleransi Selisih Receh Kasir (Rp):
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-xs font-bold text-slate-400">Rp</span>
                            <input type="number" name="cashier_max_discrepancy_tolerance" x-model.number="maxDiscrepancy" required min="0" step="500"
                                   class="w-full text-xs font-mono font-bold py-2 pl-9 pr-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        </div>
                        <p class="text-[10.5px] text-slate-400 mt-1">
                            Sesuai aturan <strong>Blind Closing AGENTS.md</strong>: Jika selisih uang fisik kasir vs hitungan sistem masih berada di bawah angka toleransi ini (misal Rp 2.000), selisih otomatis dianggap wajar (toleransi receh/pembulatan). Bila melampaui, status shift akan meminta resolusi PIN Manajer.
                        </p>
                    </div>

                    <div class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl text-xs space-y-1">
                        <div class="font-bold text-blue-900 dark:text-blue-300">
                            🛡️ Kebijakan Blind Closing Terintegrasi:
                        </div>
                        <p class="text-[11px] text-blue-800 dark:text-blue-400">
                            Kasir wajib menghitung uang fisik tanpa melihat ekspektasi sistem di layar, menjamin integritas uang kas toko 100% dari potensi kecurangan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 5. BATAS MINIMUM KEUNTUNGAN & PROTEKSI HARGA MODAL -->
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
                <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-700">
                    <span class="text-xl">🛡️</span>
                    <div>
                        <h2 class="text-sm font-black text-slate-900 dark:text-white">Batas Minimum Keuntungan Penjualan (Min Profit Margin)</h2>
                        <p class="text-xs text-slate-500">Mencegah harga jual di bawah harga pokok modal (HPP) di seluruh sistem</p>
                    </div>
                </div>

                <div class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Batas Minimum Laba Penjualan (%):
                        </label>
                        <div class="relative">
                            <input type="number" name="min_profit_percentage" x-model.number="minProfitPercentage" required min="0" max="1000" step="0.5"
                                   class="w-full text-xs font-mono font-bold py-2 pl-3 pr-9 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                            <span class="absolute right-3 top-2.5 text-xs font-bold text-slate-400">%</span>
                        </div>
                        <p class="text-[10.5px] text-slate-400 mt-1">
                            Jika diisi <strong>0%</strong>, harga jual minimal sama dengan modal (HPP). Jika diisi misalnya <strong>5%</strong>, sistem akan menolak segala input harga jual atau diskon yang menghasilkan laba di bawah 5%.
                        </p>
                    </div>

                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-xl text-xs space-y-1">
                        <div class="font-bold text-emerald-900 dark:text-emerald-300 flex items-center gap-1.5">
                            <span>✅</span> Proteksi Rahasia Dagang &amp; Anti-Jual Rugi:
                        </div>
                        <p class="text-[11px] text-emerald-800 dark:text-emerald-400">
                            Validasi ini berlaku mutlak saat input/edit Master Barang, Impor CSV, Promo/Diskon, dan Layar Kasir POS. Jika harga di bawah batas, form langsung ditolak tanpa membocorkan angka modal kepada operator.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <!-- 5. TOMBOL CEPAT PECAHAN UANG TUNAI (QUICK CASH PRESETS) -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-xs space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-700">
                <span class="text-xl">⚡</span>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Tombol Cepat Pecahan Uang Kasir (Quick Cash Tender Presets)</h2>
                    <p class="text-xs text-slate-500">Konfigurasi nominal pecahan uang yang muncul di modal pembayaran kasir POS</p>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Daftar Pecahan Cepat (Pisahkan dengan tanda koma):
                    </label>
                    <input type="text" name="cashier_quick_cash_nominal" x-model="quickCashNominal" required
                           placeholder="10000,20000,50000,100000,200000"
                           class="w-full text-xs font-mono font-bold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <p class="text-[10.5px] text-slate-400 mt-1">Daftar nominal rupiah yang akan ditampilkan sebagai tombol cepat saat kasir memproses bayar tunai.</p>
                </div>

                <!-- PREVIEW QUICK CASH BUTTONS -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Preview Tampilan Tombol di Kasir:
                    </label>
                    <div class="flex items-center gap-2 flex-wrap bg-slate-100 dark:bg-slate-900/60 p-3 rounded-xl border border-slate-200 dark:border-slate-700 font-mono text-xs font-bold">
                        <span class="px-3 py-1.5 bg-blue-100 text-blue-900 border border-blue-300 rounded-lg shadow-2xs font-sans">Uang Pas</span>
                        <template x-for="nom in getQuickCashArray()" :key="nom">
                            <span class="px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-600 rounded-lg shadow-2xs"
                                  x-text="nom >= 1000 ? (nom / 1000) + 'k' : nom"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- SAVE BUTTON -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-black text-xs rounded-xl shadow-lg shadow-blue-900/20 flex items-center gap-2 transition-all active:scale-95 cursor-pointer">
                <span>💾 SIMPAN PENGATURAN OPERASIONAL KASIR</span>
            </button>
        </div>

    </form>

</div>

<script>
function cashierSettingsApp(config) {
    return {
        defaultStartingCash: config.defaultStartingCash,
        maxStartingCash: config.maxStartingCash,
        requireStartingCash: config.requireStartingCash,
        maxChangeLimit: config.maxChangeLimit,
        cashDropThreshold: config.cashDropThreshold,
        cashDropWarning: config.cashDropWarning,
        maxDiscrepancy: config.maxDiscrepancy,
        allowPending: config.allowPending,
        maxPending: config.maxPending,
        minProfitPercentage: config.minProfitPercentage,
        quickCashNominal: config.quickCashNominal,

        getQuickCashArray() {
            if (!this.quickCashNominal.trim()) return [];
            return this.quickCashNominal
                .split(',')
                .map(s => parseInt(s.trim()))
                .filter(n => !isNaN(n) && n > 0);
        }
    };
}
</script>
@endsection
