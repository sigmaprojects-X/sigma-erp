@extends('layouts.app')

@section('title', 'Karantina Barang Rusak & Resolusi')
@section('tab_title', 'Barang Rusak (Karantina)')

@section('content')
<div class="space-y-5 p-4 max-w-7xl mx-auto w-full"
     x-data="{
        showResolveModal: false,
        productId: '',
        productName: '',
        productSku: '',
        quarantinedQty: 0,
        resolveQty: 0,
        unitCode: 'PCS',
        resolutionType: 'REPLACEMENT',
        openResolve(data) {
            this.productId = data.id;
            this.productName = data.name;
            this.productSku = 'SKU: ' + data.sku;
            this.quarantinedQty = data.quarantined_qty;
            this.resolveQty = data.quarantined_qty;
            this.unitCode = data.unit_code || 'PCS';
            this.resolutionType = 'REPLACEMENT';
            this.showResolveModal = true;
        }
     }">

    <!-- HEADER & STORE SWITCHER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <span>Manajemen Gudang &amp; Persediaan</span>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Barang Rusak &amp; Karantina</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🛡️ Dashboard Karantina Barang Rusak / BS</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 font-bold">
                    {{ $selectedStore->name }} ({{ $selectedStore->code }})
                </span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Form Switch Toko -->
            <form method="GET" action="{{ route('warehouse.adjustments.quarantine') }}" class="flex items-center gap-2">
                <select name="store_id" onchange="this.form.submit()"
                        class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-blue-500 shadow-xs cursor-pointer">
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}" {{ $selectedStore->id === $s->id ? 'selected' : '' }}>
                            🏬 {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </form>

            <!-- Quick Action: Input Barang Rusak Baru -->
            <a href="{{ route('warehouse.adjustments.create', ['store_id' => $selectedStore->id]) }}"
               class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                <span>+ Catat Barang Rusak Baru</span>
            </a>

            <!-- Quick Action: Transfer ke Cabang/Gudang BS -->
            <a href="{{ route('warehouse.transfers.create', ['source_store_id' => $selectedStore->id]) }}"
               class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer">
                <span>🚚 Kirim ke Gudang BS Pusat</span>
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGE -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✅</span>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-black cursor-pointer">✕</button>
        </div>
    @endif

    <!-- PENJELASAN ALUR RETUR/KARANTINA -->
    <div class="p-4 bg-rose-50/70 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/40 rounded-2xl text-xs text-rose-800 dark:text-rose-300">
        <div class="flex items-start gap-2.5">
            <span class="text-xl">🛡️</span>
            <div class="space-y-1">
                <div class="font-bold text-sm">Prinsip Pemisahan Stok Rusak &amp; Terkunci Otomatis (Zero Leakage)</div>
                <p class="leading-relaxed">
                    Barang yang masuk dalam tabel karantina ini <strong>tidak dapat dijual oleh kasir POS maupun dipesan melalui katalog PWA</strong>.
                    Pilih tombol <strong>"⚡ Proses Resolusi Retur"</strong> di samping barang untuk menyelesaikan fisik barang:
                    <span class="underline font-bold">1. Tukar Guling Produk Baru (1-to-1)</span>,
                    <span class="underline font-bold">2. Potong Hutang Faktur</span>,
                    <span class="underline font-bold">3. Terima Uang Tunai/Transfer</span>, atau
                    <span class="underline font-bold">4. Pemusnahan / Kerugian (Write-Off)</span>.
                </p>
            </div>
        </div>
    </div>

    <!-- 3 KPI SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Varian Barang Rusak</div>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                {{ $quarantinedStocks->count() }} <span class="text-sm font-semibold text-slate-500">SKU</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1">Menunggu penyelesaian dengan supplier</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Kuantitas Fisik Rusak</div>
            <div class="text-2xl font-black text-slate-900 dark:text-white font-mono tracking-tight">
                {{ number_format($quarantinedStocks->sum('quarantined_quantity'), 2, ',', '.') }} <span class="text-sm font-semibold text-slate-500">Unit</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1">Tersimpan di rak karantina cabang</div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Estimasi Nilai Klaim / Aset</div>
            <div class="text-2xl font-black text-blue-600 dark:text-blue-400 font-mono tracking-tight">
                @if(auth()->user()->canViewHpp())
                    Rp {{ number_format($totalQuarantinedValuation, 0, ',', '.') }}
                @else
                    <span class="text-sm italic font-normal text-slate-400">[HPP Dirahasiakan]</span>
                @endif
            </div>
            <div class="text-[11px] text-slate-500 font-medium mt-1">Total modal mengendap di karantina</div>
        </div>
    </div>

    <!-- TABEL DATA STOK KARANTINA -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">Daftar Barang dalam Karantina Cabang</h2>
            </div>
            <div class="text-xs font-medium text-slate-400 font-mono">
                Menampilkan {{ $quarantinedStocks->count() }} item barang
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                        <th class="p-3">SKU &amp; Barcode</th>
                        <th class="p-3">Nama Produk</th>
                        <th class="p-3">Kategori</th>
                        <th class="p-3 text-center">Satuan</th>
                        <th class="p-3 text-right">Qty Karantina</th>
                        @if(auth()->user()->canViewHpp())
                            <th class="p-3 text-right">HPP Satuan</th>
                            <th class="p-3 text-right">Total Nilai</th>
                        @endif
                        <th class="p-3 text-center">Aksi Penyelesaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($quarantinedStocks as $qs)
                        @php
                            $prod = $qs->product;
                            $qQty = (float) $qs->quarantined_quantity;
                            $cost = (float) $prod->cost_price;
                            $val = $qQty * $cost;
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="p-3 font-mono">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $prod->sku }}</div>
                                <div class="text-[11px] text-slate-400">{{ $prod->barcodes->first()?->barcode ?? '-' }}</div>
                            </td>

                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $prod->name }}</div>
                                <div class="text-[10px] text-slate-400">Rak: {{ $prod->rack_location ?: '-' }}</div>
                            </td>

                            <td class="p-3 font-semibold text-slate-700 dark:text-slate-300">
                                {{ $prod->category?->name ?? 'Umum' }}
                            </td>

                            <td class="p-3 text-center">
                                <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-bold">
                                    {{ $prod->baseUnit?->unit_code ?? 'PCS' }}
                                </span>
                            </td>

                            <td class="p-3 text-right font-mono font-black text-rose-600 dark:text-rose-400 text-sm">
                                {{ number_format($qQty, 2, ',', '.') }}
                            </td>

                            @if(auth()->user()->canViewHpp())
                                <td class="p-3 text-right font-mono text-slate-600 dark:text-slate-400">
                                    Rp {{ number_format($cost, 0, ',', '.') }}
                                </td>

                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                    Rp {{ number_format($val, 0, ',', '.') }}
                                </td>
                            @endif

                            <td class="p-3 text-center">
                                <button type="button"
                                        @click="openResolve({
                                            id: '{{ $prod->id }}',
                                            name: '{{ addslashes($prod->name) }}',
                                            sku: '{{ $prod->sku }}',
                                            quarantined_qty: {{ $qQty }},
                                            unit_code: '{{ $prod->baseUnit?->unit_code ?? 'PCS' }}'
                                        })"
                                        class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 mx-auto cursor-pointer">
                                    <span>⚡ Proses Resolusi Retur</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->canViewHpp() ? 8 : 6 }}" class="p-10 text-center text-slate-400 font-semibold">
                                🎉 Luar biasa! Tidak ada barang rusak dalam karantina di cabang ini. Semua stok dalam kondisi siap jual.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL RESOLUSI BARANG KARANTINA (ALPINE POWERED) -->
    <div x-show="showResolveModal"
         x-cloak
         @keydown.escape.window="showResolveModal = false"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-150"
             @click.outside="showResolveModal = false">
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400">⚡</span>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Eksekusi Penyelesaian Barang Rusak / Tukar Guling</h3>
                </div>
                <button type="button" @click="showResolveModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-sm cursor-pointer">✕ ESC</button>
            </div>

            <form action="{{ route('warehouse.adjustments.quarantine.resolve') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <input type="hidden" name="store_id" value="{{ $selectedStore->id }}">
                <input type="hidden" name="product_id" :value="productId">

                <!-- Ringkasan Produk Terpilih -->
                <div class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700/60 text-xs">
                    <div class="font-bold text-slate-900 dark:text-white text-sm" x-text="productName">-</div>
                    <div class="flex items-center justify-between mt-1 text-slate-500">
                        <span x-text="productSku">SKU: -</span>
                        <span class="font-mono font-bold text-rose-600" x-text="'Stok Rusak: ' + quarantinedQty + ' ' + unitCode">Karantina: 0 Unit</span>
                    </div>
                </div>

                <!-- Input Jumlah Qty yang Diselesaikan -->
                <div>
                    <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                        Jumlah Qty yang Diselesaikan / Ditukar <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" step="0.01" min="0.01" name="quantity" x-model="resolveQty" :max="quarantinedQty" required
                               class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-mono font-bold text-xs focus:ring-2 focus:ring-blue-500">
                        <span class="px-3 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl font-bold text-xs text-slate-600 dark:text-slate-300" x-text="unitCode">
                            PCS
                        </span>
                    </div>
                </div>

                <!-- Pilihan Metode Resolusi (4 Saluran) -->
                <div>
                    <label class="block font-bold text-xs text-slate-700 dark:text-slate-300 mb-1">
                        Metode Penyelesaian dengan Supplier / Toko <span class="text-rose-500">*</span>
                    </label>
                    <select name="resolution_type" x-model="resolutionType" required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-bold text-xs focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="REPLACEMENT">1. 🔄 Tukar Guling Barang Baru (1-to-1 Replacement dari Supplier)</option>
                        <option value="DEBT_DEDUCTION">2. ✂️ Potong Hutang Faktur Supplier (Credit Note)</option>
                        <option value="CASH_REFUND">3. 💵 Terima Kas / Refund Tunai / Transfer Bank</option>
                        <option value="WRITE_OFF">4. 🗑️ Pemusnahan / Dianggap Kerugian Toko (Write-Off)</option>
                    </select>
                </div>

                <!-- Field Supplier (Muncul jika Tukar Guling atau Potong Hutang) -->
                <div x-show="resolutionType === 'REPLACEMENT' || resolutionType === 'DEBT_DEDUCTION'" class="text-xs">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Pilih Mitra Supplier / Distributor <span class="text-rose-500">*</span>
                    </label>
                    <select name="supplier_id"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="">-- Pilih Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1" x-show="resolutionType === 'REPLACEMENT'">
                        Barang cacat diserahkan ke supplier dan diganti 1-to-1 dengan produk baru layak jual (Stok aktif pulih, selisih finansial Rp 0).
                    </p>
                    <p class="text-[10px] text-slate-400 mt-1" x-show="resolutionType === 'DEBT_DEDUCTION'">
                        Sistem akan otomatis memotong saldo Hutang Dagang (AP) pada faktur pembelian supplier terkait.
                    </p>
                </div>

                <!-- Field Rekening Kas/Bank (Hanya jika CASH_REFUND) -->
                <div x-show="resolutionType === 'CASH_REFUND'" class="text-xs">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                        Pilih Rekening Kas / Bank Penerima Refund <span class="text-rose-500">*</span>
                    </label>
                    <select name="bank_account_id"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-semibold text-xs focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <option value="">-- Kas Tunai Toko (1001-001) --</option>
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}">{{ $ba->account_name }} ({{ $ba->account_number }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Catatan Tambahan -->
                <div class="text-xs">
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Catatan / No. Tanda Terima / Surat Jalan Retur</label>
                    <input type="text" name="notes" placeholder="Misal: Retur diserahkan ke Salesman Bpk. Budi via Surat Jalan retur No. SJ-019..."
                           class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl font-medium text-xs focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Modal Footer -->
                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2">
                    <button type="button" @click="showResolveModal = false"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <span>⚡ Konfirmasi &amp; Eksekusi Jurnal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
