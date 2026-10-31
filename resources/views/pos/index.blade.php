<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIGMA POS Pro - Kasir Penjualan Modern • {{ $store->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* SIGMA Modern Enterprise POS Styling */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f4f8;
            color: #1e293b;
        }
        .pos-table th {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            font-weight: 800;
            color: #334155;
            padding: 9px 12px;
            user-select: none;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .pos-table td {
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
            padding: 8px 12px;
            white-space: nowrap;
        }
        .pos-input {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            border-radius: 8px;
            font-size: 12px;
            padding: 6px 10px;
            transition: all 130ms ease;
            color: #1e293b;
        }
        .pos-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background-color: #ffffff;
        }
        .excel-input {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            border-radius: 7px;
            font-size: 11.5px;
            padding: 4px 8px;
            transition: all 120ms ease;
            color: #1e293b;
        }
        .excel-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15);
        }
        .excel-input:read-only {
            background-color: #f8fafc;
            color: #475569;
        }
        .btn-pos-cmd {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            padding: 5px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 120ms ease;
        }
        .btn-pos-cmd:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-pos-cmd:active {
            transform: scale(0.98);
        }
        .btn-win {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            padding: 5px 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 130ms ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .btn-win:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .btn-win:active {
            transform: scale(0.98);
        }
        .btn-action-primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: 1px solid #059669;
            color: #ffffff;
            box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3);
            transition: all 140ms ease;
        }
        .btn-action-primary:hover {
            background: linear-gradient(135deg, #34d399 0%, #059669 100%);
            box-shadow: 0 5px 12px rgba(16, 185, 129, 0.4);
            transform: translateY(-1px);
        }
        .btn-action-primary:active {
            transform: scale(0.98);
        }
        /* Custom scrollbar for modern dense view */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f8fafc;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-screen w-screen overflow-hidden select-none flex flex-col justify-between text-slate-800 bg-[#F4F7FC]"
      x-data="posDesktopApp()"
      x-init="initApp()"
      @keydown.window="handleGlobalKeys($event)">

    <!-- MODERN ENTERPRISE HEADER TOP BAR -->
    <div class="bg-gradient-to-r from-[#03153b] via-[#082b6b] to-[#041945] text-white text-xs px-3.5 py-2 flex items-center justify-between border-b border-blue-900/60 shadow-md gap-2 shrink-0">
        
        <!-- LEFT: BRAND & SYSTEM PILLS -->
        <div class="flex items-center gap-2.5 font-semibold tracking-wide flex-wrap">
            
            <!-- BRAND TITLE WITH CYAN CART BADGE -->
            <div class="flex items-center gap-2 mr-1">
                <div class="w-7 h-7 rounded-lg bg-[#00F2FE]/20 text-[#00F2FE] border border-[#00F2FE]/40 flex items-center justify-center shadow-xs">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="font-black text-sm tracking-tight text-white">SIGMA <span class="text-[#18BDEB]">POS Pro</span></span>
                    <span class="text-slate-500 font-light text-sm mx-1 hidden sm:inline">|</span>
                    <span class="text-slate-300 font-normal text-xs hidden lg:inline">Kasir Penjualan Profesional</span>
                </div>
            </div>

            <!-- MENU ERP DROPDOWN PILL -->
            <a href="{{ route('sales.index') }}"
               class="px-3 py-1 bg-[#0A3D8C] hover:bg-[#0E4DB3] border border-blue-400/30 rounded-full text-white text-xs font-semibold flex items-center gap-1.5 transition-all shadow-xs cursor-pointer"
               title="Kembali ke Dashboard & Menu ERP Utama">
                <span>🏠 Menu ERP</span>
                <span class="text-[10px] text-blue-200">▾</span>
            </a>

            <!-- STORE BADGE PILL -->
            <div class="px-3 py-1 bg-[#06214C] border border-blue-400/30 rounded-full text-white text-xs font-medium flex items-center gap-2 shadow-xs hidden sm:flex">
                <span>🏪</span>
                <span class="font-bold">SIGMA POS PRO</span>
                <span class="bg-[#FFB800] text-slate-950 font-black text-[9px] px-1.5 py-0.2 rounded">PRO</span>
            </div>

            <!-- KASIR BERTUGAS CHIP -->
            <div class="bg-[#06214C] border border-blue-400/30 hover:border-blue-400/60 rounded-full pl-1.5 pr-3 py-0.5 flex items-center gap-2 cursor-pointer shadow-xs transition-all"
                 @click="openSwitchCashierModal()"
                 title="Klik untuk Ganti Kasir yang Bertugas">
                <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-[#125BCE] to-[#18BDEB] text-white font-black text-[11px] flex items-center justify-center shadow-xs">
                    {{ strtoupper(substr($cashier->name, 0, 1)) }}
                </div>
                <div class="flex flex-col text-left">
                    <span class="text-[8.5px] uppercase font-bold text-slate-400 leading-tight">KASIR BERTUGAS</span>
                    <span class="text-xs font-bold text-white leading-tight">{{ $cashier->name }}</span>
                </div>
            </div>

            <!-- ACTIVE SHIFT PILL -->
            @if($activeShift)
                <div class="px-3 py-1 bg-emerald-950/50 border border-emerald-500/60 rounded-full text-emerald-400 text-xs font-mono font-bold flex items-center gap-1.5 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Shift : {{ $activeShift->shift_number }}</span>
                </div>
            @else
                <div class="px-3 py-1 bg-rose-950/50 border border-rose-500/60 rounded-full text-rose-300 text-xs font-mono font-bold flex items-center gap-1.5 shadow-xs animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    <span>Shift Belum Buka</span>
                </div>
            @endif
        </div>

        <!-- RIGHT: ACTIONS & CONTROLS -->
        <div class="flex items-center gap-2 text-xs text-slate-300 flex-wrap">
            
            <!-- TOMBOL CEK HARGA [F9] PILL -->
            <button type="button"
                    @click="openLookupModal()"
                    class="px-3 py-1 bg-[#1877F2] hover:bg-[#1565C0] text-white rounded-full text-xs font-bold flex items-center gap-1.5 shadow-sm transition-all cursor-pointer"
                    title="Cek Harga & Ketersediaan Stok Real-Time [F9]">
                <span>🔍</span>
                <span>Cek Harga</span>
                <kbd class="px-1 py-0.2 bg-black/20 rounded text-[9.5px] font-mono text-cyan-200">F9</kbd>
            </button>

            <!-- PRINTER STATUS PILL -->
            <button type="button"
                    @click="openPrinterSettings()"
                    class="px-3 py-1 bg-[#06214C] border border-blue-400/30 hover:border-blue-400 text-slate-200 text-xs font-medium rounded-full flex items-center gap-1.5 shadow-xs cursor-pointer transition-all"
                    title="Pengaturan Kertas & Printer Thermal Kasir">
                <span>🖨️</span>
                <span>Printer</span>
                <span class="font-bold text-blue-300 font-mono" x-text="printerPaperSize + 'mm'"></span>
            </button>

            <!-- LIVE DIGITAL CLOCK -->
            <div class="px-3 py-1 bg-[#06214C] border border-blue-400/30 text-cyan-300 font-mono text-xs font-bold rounded-full flex items-center gap-1.5 shadow-xs hidden xl:flex">
                <span>📅</span>
                <span x-text="liveClock"></span>
            </div>

            <!-- CASH DROP & SHIFT BUTTONS -->
            <div class="flex items-center gap-1.5">
                @if($activeShift)
                    <button type="button"
                            @click="openCashDropModal()"
                            class="px-3 py-1 bg-[#D97706] hover:bg-[#B45309] text-white text-xs font-bold rounded-lg shadow-sm transition-all cursor-pointer">
                        Cash Drop
                    </button>
                    <button type="button"
                            @click="openBlindCloseModal()"
                            class="px-3 py-1 bg-[#DC2626] hover:bg-[#B91C1C] text-white text-xs font-bold rounded-lg shadow-sm transition-all cursor-pointer">
                        Tutup Shift
                    </button>
                @else
                    <button type="button"
                            @click="openShiftModal = true"
                            class="px-3 py-1 bg-[#059669] hover:bg-[#047857] text-white text-xs font-bold rounded-lg shadow-sm transition-all cursor-pointer">
                        Buka Shift
                    </button>
                @endif
                <button type="button"
                        @click="openReprintModal()"
                        class="px-3 py-1 bg-[#2563EB] hover:bg-[#1D4ED8] text-white text-xs font-bold rounded-lg shadow-sm transition-all cursor-pointer flex items-center gap-1">
                    <span>🖨️</span>
                    <span>Cetak Ulang</span>
                </button>
            </div>

            <!-- WINDOW CONTROLS SIMULATION -->
            <div class="flex items-center gap-2.5 text-slate-400 text-xs ml-1 select-none hidden 2xl:flex">
                <span class="hover:text-white cursor-pointer transition-colors" title="Minimize">⎯</span>
                <span class="hover:text-white cursor-pointer transition-colors" title="Maximize">▢</span>
                <span class="hover:text-rose-400 cursor-pointer transition-colors" title="Close">✕</span>
            </div>
        </div>
    </div>

    @if($cashDropWarning)
        <!-- CASH DROP WARNING BANNER -->
        <div class="bg-amber-500 text-slate-950 px-4 py-2 font-bold flex items-center justify-between text-xs border-b border-amber-600 shadow-sm animate-pulse z-40">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <span><strong>Peringatan Keamanan Kas:</strong> Uang tunai di laci kasir saat ini telah mencapai Rp {{ number_format($currentDrawerCash, 0, ',', '.') }} (Batas aman: Rp {{ number_format($cashDropThreshold, 0, ',', '.') }}). Melebihi ambang batas aman!</span>
            </div>
            <button type="button" @click="openCashDropModal()" class="bg-slate-900 text-white px-3 py-1 rounded text-xs font-black hover:bg-slate-800 transition-colors cursor-pointer shadow">
                Lakukan Cash Drop Sekarang &rarr;
            </button>
        </div>
    @endif

    <!-- MAIN APPLICATION CONTAINER -->
    <div class="flex-1 flex flex-col justify-between p-1.5 gap-1.5 min-h-0 bg-[#f1f5f9]">

        <!-- ========================================== -->
        <!-- 1. FORM HEADER KIRI ATAS & LED GRAND TOTAL -->
        <!-- ========================================== -->
        <div class="grid grid-cols-12 gap-2.5 shrink-0">

            <!-- KIRI ATAS: FIELD FORM TRANSAKSI & SCAN BARIS UTAMA -->
            <div class="col-span-8 bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex flex-col justify-between gap-2.5">
                
                <!-- Baris 1: No Transaksi, Tanggal, Pelanggan -->
                <div class="flex items-center justify-between gap-3 text-[11.5px] border-b border-slate-100 pb-2">
                    <!-- No Transaksi -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-semibold">No. Transaksi :</span>
                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 font-mono font-bold text-[11px]">AUTO</span>
                        <span class="font-bold text-slate-800 text-xs font-mono">{{ $store->code }}-001</span>
                    </div>

                    <!-- Tanggal -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-semibold">Tanggal :</span>
                        <div class="flex items-center gap-1.5 font-mono text-xs text-slate-700 bg-slate-50 border border-slate-200 px-2.5 py-0.5 rounded-md">
                            <span>📅</span>
                            <span x-text="transactionDate"></span>
                        </div>
                    </div>

                    <!-- Pelanggan Interaktif [F3] -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-semibold">Pelanggan [F3] :</span>
                        <div class="flex items-center gap-1">
                            <select x-ref="customerSelect"
                                    x-model="selectedCustomerId"
                                    @change="onCustomerChange()"
                                    class="excel-input font-bold text-slate-800 cursor-pointer bg-white border-slate-300 hover:border-slate-400 focus:border-blue-600 shadow-2xs text-xs py-0.5 max-w-[260px] truncate">
                                @foreach($customers as $cust)
                                    <option value="{{ $cust->id }}" {{ $cust->code === 'CUST-UMUM' ? 'selected' : '' }}>
                                        {{ $cust->name }} {{ $cust->code === 'CUST-UMUM' ? '(Pelanggan Umum / Eceran)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="cycleCustomer()" class="p-1 rounded hover:bg-slate-100 text-slate-500 hover:text-blue-600 cursor-pointer" title="Ganti Pelanggan Cepat (F3)">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Sales Kasir & Plafon Kredit -->
                <div class="flex items-center justify-between gap-3 text-[11.5px]">
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-semibold">Sales :</span>
                        <div class="flex items-center gap-1">
                            <select x-ref="salesSelect"
                                    x-model="selectedSalesId"
                                    class="excel-input bg-white font-medium text-slate-800 border-slate-300 hover:border-slate-400 py-0.5 text-xs">
                                @foreach($salesList as $s)
                                    <option value="{{ $s->id }}" {{ $s->id === $cashier->id ? 'selected' : '' }}>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="cycleSales()" class="p-1 rounded hover:bg-slate-100 text-slate-500 hover:text-blue-600 cursor-pointer" title="Ganti Sales Pegawai">
                                ✏️
                            </button>
                        </div>
                    </div>

                    <!-- INDIKATOR PLAFON KREDIT & TEMPO -->
                    <div class="flex items-center gap-2">
                        <!-- Pelanggan UMUM (Retail Non-Kredit) -->
                        <div x-show="isWalkInCustomer" class="flex items-center gap-2 text-[11px] text-slate-500 font-medium">
                            <span class="bg-blue-100 text-blue-800 border border-blue-200 font-bold px-2 py-0.5 rounded text-[10.5px]">UMUM / CASH</span>
                            <span>Pelanggan retail eceran standar.</span>
                            <template x-if="selectedCustomer && (selectedCustomer.loyalty_points || 0) > 0">
                                <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300 font-bold text-[10.5px]">
                                    <span>⭐ Poin:</span>
                                    <strong x-text="formatRupiah(selectedCustomer.loyalty_points)"></strong>
                                </span>
                            </template>
                        </div>

                        <!-- Pelanggan Terdaftar (Dengan Fasilitas Plafon Kredit & Poin) -->
                        <div x-show="!isWalkInCustomer" x-cloak class="flex items-center gap-2 font-mono text-[11px] flex-wrap">
                            <span class="bg-indigo-50 text-indigo-800 border border-indigo-200 px-2 py-0.5 rounded text-[10px] font-bold">
                                KREDIT / TEMPO
                            </span>
                            <span class="text-slate-600">
                                Plafon: <strong class="text-slate-900" x-text="'Rp ' + formatRupiah(selectedCustomer?.credit_limit || 0)"></strong>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-600">
                                Terpakai: <span class="text-rose-600 font-semibold" x-text="'Rp ' + formatRupiah(selectedCustomer?.current_credit_balance || 0)"></span>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="px-2 py-0.5 rounded text-[10.5px] font-bold"
                                  :class="availableCredit >= grandTotal ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200 animate-pulse'">
                                Sisa Plafon: <strong x-text="'Rp ' + formatRupiah(availableCredit)"></strong>
                            </span>
                            <template x-if="selectedCustomer && selectedCustomer.credit_term_days > 0">
                                <span class="text-slate-500 text-[10px]" x-text="'(Tempo ' + selectedCustomer.credit_term_days + ' Hari)'"></span>
                            </template>
                            <span class="text-slate-300">|</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-300 font-bold text-[10.5px]">
                                <span>⭐ Poin:</span>
                                <strong x-text="formatRupiah(selectedCustomer?.loyalty_points || 0)"></strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- BARIS SCAN UTAMA DENGAN OPSI "JUMLAH SEBELUM SCAN"       -->
                <!-- ======================================================== -->
                <div class="bg-slate-50/80 border border-slate-200 rounded-xl p-1.5 flex items-center gap-2.5">
                    
                    <!-- BARCODE SCANNER ICON -->
                    <div class="text-slate-500 flex items-center justify-center pl-1 shrink-0" title="Barcode Scanner Ready">
                        <svg class="w-5 h-5 text-slate-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 5v14M8 5v14M12 5v14M17 5v14M21 5v14"/>
                        </svg>
                    </div>

                    <!-- OPSI JUMLAH (QUANTITY SEBELUM SCAN) -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <label for="inputJumlah" class="text-xs font-black text-slate-700 uppercase tracking-tight">JUMLAH :</label>
                        <input type="number"
                               id="inputJumlah"
                               x-ref="inputJumlah"
                               x-model.number="inputScanQty"
                               @keydown.enter.prevent="focusBarcodeInput()"
                               @keydown.tab.prevent="focusBarcodeInput()"
                               @focus="$event.target.select()"
                               min="1"
                               step="1"
                               class="w-14 h-9 text-center font-mono font-black text-sm text-blue-700 border-2 border-blue-400 rounded-lg bg-blue-50/40 focus:bg-amber-50 focus:border-blue-600 focus:outline-none transition-all">
                    </div>

                    <!-- KODE ITEM / SCAN BARCODE INPUT (F1) -->
                    <div class="flex-1 flex items-center gap-2 relative">
                        <label for="inputBarcode" class="text-xs font-black text-slate-700 uppercase tracking-tight whitespace-nowrap hidden sm:inline">
                            KODE ITEM / BARCODE (F1) :
                        </label>
                        <div class="relative flex-1">
                            <input type="text"
                                   id="inputBarcode"
                                   x-ref="inputBarcode"
                                   x-model="searchQuery"
                                   @input.debounce.200ms="onSearchInput()"
                                   @keydown.enter.prevent="handleBarcodeEnter()"
                                   @keydown.shift.tab.prevent="focusQuantityInput()"
                                   @keydown.arrow-down.prevent="navigateDropdown(1)"
                                   @keydown.arrow-up.prevent="navigateDropdown(-1)"
                                   @keydown.escape.prevent="clearSearch()"
                                   @focus="$event.target.select()"
                                   placeholder="SCAN BARCODE SCANNER ATAU KETIK NAMA SEMBAKO (F1)...."
                                   class="w-full h-9 px-3 border border-blue-400/80 rounded-lg text-xs font-mono font-bold text-slate-900 bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase transition-all"
                                   autocomplete="off">

                            <!-- Trigram Instant Dropdown Match dengan Stok & Harga Jual Kasir -->
                            <div x-show="searchResults.length > 0"
                                 x-cloak
                                 class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-lg shadow-xl z-50 max-h-64 overflow-y-auto divide-y divide-slate-100">
                                <template x-for="(res, idx) in searchResults" :key="res.id">
                                    <div @click="selectProductFromSearch(res)"
                                         :class="idx === selectedSearchIdx ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-blue-50/80 text-slate-800'"
                                         class="p-2.5 px-3 cursor-pointer flex items-center justify-between text-xs transition-colors duration-100">
                                        <div class="min-w-0 pr-2">
                                            <div class="font-bold text-xs truncate" x-text="res.name"></div>
                                            <div class="text-[10px] opacity-80 font-mono flex items-center gap-1.5 mt-0.5">
                                                <span x-text="'SKU: ' + res.sku"></span>
                                                <span>•</span>
                                                <span x-text="'Barcode: ' + res.barcode"></span>
                                            </div>
                                        </div>
                                        <div class="text-right flex items-center gap-2 shrink-0">
                                            <!-- Badge Stok Kasir -->
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full font-mono flex items-center gap-1 shadow-2xs"
                                                  :class="res.stock > 0 
                                                      ? (idx === selectedSearchIdx ? 'bg-emerald-500 text-white' : 'bg-emerald-100 text-emerald-800 border border-emerald-300')
                                                      : (idx === selectedSearchIdx ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-800 border border-rose-300')">
                                                <span class="w-1.5 h-1.5 rounded-full" :class="res.stock > 0 ? (idx === selectedSearchIdx ? 'bg-white' : 'bg-emerald-600') : (idx === selectedSearchIdx ? 'bg-white' : 'bg-rose-600')"></span>
                                                <span x-text="'Stok: ' + res.stock_formatted"></span>
                                            </span>

                                            <!-- Harga Jual Satuan -->
                                            <span class="min-w-[75px] text-right font-mono font-black text-xs"
                                                  :class="idx === selectedSearchIdx ? 'text-amber-300' : 'text-blue-700'"
                                                  x-text="res.selling_price_formatted">
                                            </span>

                                            <!-- Satuan Kemasan -->
                                            <span class="font-bold px-2 py-0.5 rounded text-[10px] border"
                                                  :class="idx === selectedSearchIdx ? 'bg-blue-700/80 border-blue-400 text-white' : 'bg-slate-100 border-slate-200 text-slate-700'"
                                                  x-text="res.matched_unit_code">
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- TOMBOL MASUK -->
                        <button type="button"
                                @click="handleBarcodeEnter()"
                                class="h-9 px-5 bg-gradient-to-r from-[#1877F2] to-[#0D62D9] hover:from-[#1565C0] hover:to-[#0B52B8] text-white font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-sm cursor-pointer transition-all active:scale-98 shrink-0">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <span>Masuk</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- KANAN ATAS: MODERN TOTAL TAGIHAN NEON DISPLAY -->
            <div class="col-span-4 bg-gradient-to-br from-[#072152] via-[#09357a] to-[#08489b] rounded-xl shadow-md border border-cyan-400/40 p-3.5 flex flex-col justify-between text-white relative overflow-hidden">
                
                <!-- TOP ROW: TITLE & ITEM BADGE -->
                <div class="flex items-center justify-between text-[11px] font-extrabold uppercase tracking-wider">
                    <span class="flex items-center gap-1.5 text-cyan-200">
                        <svg class="w-3.5 h-3.5 text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/>
                            <path d="M7 7h.01"/>
                        </svg>
                        <span>TOTAL TAGIHAN</span>
                    </span>
                    <span class="bg-[#00529B] border border-cyan-400/50 text-cyan-200 text-[10.5px] font-bold px-2.5 py-0.5 rounded-full font-mono"
                          x-text="cart.length + ' ITEM'"></span>
                </div>

                <!-- CENTER ROW: FAST MOVING CART GRAPHIC & GLOWING CYAN AMOUNT -->
                <div class="flex items-center justify-between my-auto py-1">
                    <!-- GLOWING CART GRAPHIC -->
                    <div class="flex items-center gap-2 pl-1">
                        <div class="relative text-[#00F2FE] drop-shadow-[0_0_10px_rgba(0,242,254,0.6)]">
                            <svg class="w-11 h-11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="8" cy="21" r="1.5"/><circle cx="19" cy="21" r="1.5"/>
                                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                            </svg>
                            <!-- Speed Lines -->
                            <div class="absolute -left-3 top-2.5 flex flex-col gap-1 opacity-80">
                                <div class="w-2.5 h-0.5 bg-[#00F2FE] rounded-full"></div>
                                <div class="w-4 h-0.5 bg-[#00F2FE] rounded-full"></div>
                                <div class="w-3 h-0.5 bg-[#00F2FE] rounded-full"></div>
                            </div>
                        </div>
                    </div>

                    <!-- HUGE GLOWING AMOUNT -->
                    <div class="text-right font-mono font-black text-3xl sm:text-4xl lg:text-[42px] leading-none text-[#00F2FE] tracking-tight drop-shadow-[0_0_14px_rgba(0,242,254,0.45)]">
                        <span x-text="formatRupiah(grandTotal) + ',00'"></span>
                    </div>
                </div>

                <!-- BOTTOM ROW: SUBTOTAL BREAKDOWN -->
                <div class="flex items-center justify-between text-xs font-mono text-cyan-100/90 border-t border-cyan-500/20 pt-1.5">
                    <span>Subtotal : Rp <strong class="text-white" x-text="formatRupiah(subtotal)"></strong></span>
                    <template x-if="taxAmount > 0">
                        <span class="text-cyan-200 font-bold" x-text="(selectedTaxMode === 'PPN_INCLUDE' ? 'PPN Inc: Rp ' : 'PPN Exc: +Rp ') + formatRupiah(taxAmount)"></span>
                    </template>
                    <template x-if="voucherDiscount > 0">
                        <span class="text-rose-300 font-bold">Voucher: -Rp <strong x-text="formatRupiah(voucherDiscount)"></strong></span>
                    </template>
                    <template x-if="pointsDiscount > 0">
                        <span class="text-amber-300 font-bold">Poin: -Rp <strong x-text="formatRupiah(pointsDiscount)"></strong></span>
                    </template>
                    <template x-if="roundingAmount !== 0">
                        <span :class="roundingAmount > 0 ? 'text-amber-300 font-bold' : 'text-emerald-300 font-bold'"
                              x-text="(roundingAmount > 0 ? 'Bulat (+): Rp ' : 'Bulat (-): Rp ') + formatRupiah(Math.abs(roundingAmount))"></span>
                    </template>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 2. MODERN TRANSACTION DATAGRID (KEYBOARD NAVIGABLE)            -->
        <!-- ============================================================== -->
        <div class="flex-1 bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden flex flex-col min-h-0"
             @keydown.arrow-up.prevent="navigateCartRow(-1)"
             @keydown.arrow-down.prevent="navigateCartRow(1)"
             @keydown.delete.prevent="removeSelectedCartRow()">

            <!-- NOTIFIKASI TEBUS MURAH & REWARD PROMO ENGINE -->
            <template x-for="reward in eligibleRewards" :key="reward.promotion_id">
                <div class="px-4 py-2 bg-gradient-to-r from-amber-500 via-rose-500 to-amber-600 text-white flex items-center justify-between border-b border-amber-300 shadow-xs shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg animate-bounce">🔥</span>
                        <div>
                            <div class="flex items-center gap-1.5 leading-tight">
                                <span class="px-1.5 py-0.2 rounded-full bg-white text-rose-700 font-black text-[9.5px] uppercase tracking-wider">TEBUS MURAH TERSEDIA</span>
                                <strong class="text-xs font-bold" x-text="reward.name"></strong>
                            </div>
                            <div class="text-[11px] text-amber-100 mt-0.5">
                                Belanja terpenuhi! Konsumen berhak tebus <strong class="text-white underline" x-text="reward.reward_product_name"></strong> hanya <strong class="text-yellow-200 font-mono text-xs" x-text="'Rp ' + formatRupiah(reward.special_price)"></strong> (Maks: <span x-text="reward.max_quantity"></span>)!
                            </div>
                        </div>
                    </div>
                    <button type="button"
                            @click="claimTebusMurah(reward)"
                            :disabled="isRewardAlreadyClaimed(reward)"
                            class="px-3 py-1.5 rounded-lg font-black text-xs shadow-xs transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
                            :class="isRewardAlreadyClaimed(reward) ? 'bg-white/20 text-white cursor-not-allowed' : 'bg-white text-rose-700 hover:bg-amber-100 hover:scale-102 active:scale-98'">
                        <span x-text="isRewardAlreadyClaimed(reward) ? '✓ Sudah Ditebus' : '+ Klaim Tebus Murah'"></span>
                    </button>
                </div>
            </template>

            <!-- TABEL DATA PRODUK -->
            <div class="flex-1 overflow-y-auto overflow-x-auto">
                <table class="pos-table w-full border-collapse">
                    <thead class="sticky top-0 z-10 shadow-2xs">
                        <tr>
                            <th class="w-12 text-center">NO</th>
                            <th class="w-40 text-left">KODE / BARCODE</th>
                            <th class="text-left">NAMA PRODUK</th>
                            <th class="w-24 text-right">KUANTITAS</th>
                            <th class="w-28 text-center">SATUAN KEMASAN</th>
                            <th class="w-32 text-right">HARGA SATUAN</th>
                            <th class="w-24 text-right">DISKON</th>
                            <th class="w-32 text-right">SUBTOTAL</th>
                            <th class="w-20 text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- BARIS DATA KERANJANG AKTIF -->
                        <template x-for="(item, idx) in cart" :key="idx">
                            <tr :class="selectedCartIdx === idx ? 'bg-blue-50/80 font-semibold border-l-4 border-[#1877F2]' : (idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white')"
                                @click="selectedCartIdx = idx"
                                class="cursor-pointer hover:bg-blue-50/40 transition-colors duration-100 border-b border-slate-100 text-slate-800">
                                
                                <!-- NO -->
                                <td class="text-center font-bold text-xs sm:text-sm text-blue-600" x-text="idx + 1"></td>
                                
                                <!-- KODE / BARCODE -->
                                <td class="font-mono text-xs font-semibold text-slate-600" x-text="item.barcode || item.sku"></td>
                                
                                <!-- NAMA PRODUK & STOK TOKO -->
                                <td class="font-bold text-sm text-slate-900">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="item.is_tebus_murah">
                                            <span class="px-1.5 py-0.2 rounded bg-rose-600 text-white text-[9px] font-black uppercase tracking-wider shadow-2xs">Tebus Murah</span>
                                        </template>
                                        <span x-text="item.product_name"></span>
                                    </div>
                                    <template x-if="item.stock_formatted">
                                        <div class="text-[11px] font-mono mt-0.5 flex items-center gap-1 font-semibold"
                                             :class="item.available_stock > 0 ? 'text-slate-600' : 'text-rose-600'">
                                            <span class="w-2 h-2 rounded-full" :class="item.available_stock > 0 ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                            <span>Stok Toko : <strong class="text-slate-700" x-text="item.stock_formatted"></strong></span>
                                        </div>
                                    </template>
                                </td>

                                <!-- KUANTITAS -->
                                <td class="text-right font-mono font-black text-sm text-slate-900">
                                    <span x-text="formatDec(item.quantity)"></span>
                                </td>

                                <!-- SATUAN KEMASAN (DROPDOWN PILL IN-ROW) -->
                                <td class="text-center">
                                    <select x-model="item.product_unit_id"
                                            @change="onItemUnitChange(item)"
                                            class="text-xs bg-slate-50 border border-slate-300 rounded px-2.5 py-1 cursor-pointer shadow-2xs text-slate-800 font-bold hover:border-slate-400 focus:ring-1 focus:ring-blue-500">
                                        <template x-for="u in item.available_units" :key="u.id">
                                            <option :value="u.id" x-text="u.code" :selected="u.id === item.product_unit_id"></option>
                                        </template>
                                    </select>
                                </td>

                                <!-- HARGA SATUAN -->
                                <td class="text-right font-mono font-bold text-slate-800 text-sm"
                                    x-text="formatRupiah(item.unit_price) + ',00'"></td>

                                <!-- POTONGAN DISKON BARIS -->
                                <td class="text-right font-mono text-sm"
                                    :class="item.discount_amount > 0 ? 'text-rose-600 font-bold' : 'text-blue-600 font-bold'">
                                    <span x-text="item.discount_amount > 0 ? ('-Rp ' + formatRupiah(item.discount_amount)) : '0,00'"></span>
                                </td>

                                <!-- SUBTOTAL -->
                                <td class="text-right font-mono font-black text-sm text-blue-600"
                                    x-text="formatRupiah(item.subtotal) + ',00'"></td>

                                <!-- AKSI CEPAT PER BARIS -->
                                <td class="text-center">
                                    <div class="flex items-center justify-center gap-1.5 text-slate-600">
                                        <button type="button"
                                                @click.stop="openEditItemModal(item)"
                                                class="p-1 rounded text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition-colors cursor-pointer"
                                                title="Edit Qty / Diskon">✏️</button>
                                        <button type="button"
                                                @click.stop="removeCartRow(idx)"
                                                class="p-1 rounded text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-colors cursor-pointer"
                                                title="Hapus Baris (Delete)">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- TAMPILAN KETIKA KERANJANG KOSONG -->
                        <template x-if="cart.length === 0">
                            <tr>
                                <td colspan="9" class="py-16 text-center text-slate-400">
                                    <div class="w-14 h-14 mx-auto mb-3 text-slate-300 flex items-center justify-center rounded-2xl bg-slate-100 border border-slate-200">
                                        <svg class="w-8 h-8 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <circle cx="8" cy="21" r="1.5"/><circle cx="19" cy="21" r="1.5"/>
                                            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                                        </svg>
                                    </div>
                                    <span class="font-bold text-slate-700 text-sm">Keranjang Transaksi Masih Kosong</span>
                                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                        Scan barcode barang, atau tekan <kbd class="px-1.5 py-0.5 bg-slate-200 text-slate-700 font-mono rounded text-xs">[F1]</kbd> untuk pencarian cepat nama produk.
                                    </p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 3. PANEL RINCIAN, KEUANGAN, SUB TOTAL & VOUCHER (3 KOLOM)      -->
        <!-- ============================================================== -->
        <div class="grid grid-cols-12 gap-2.5 shrink-0 text-xs">

            <!-- KARTU 1 (KIRI - COL 6): TAB RINCIAN & TOMBOL TOOLBAR CEPAT -->
            <div class="col-span-6 bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex flex-col justify-between gap-2.5">
                
                <!-- Tab Header Rincian / Potongan -->
                <div class="flex items-center gap-1.5">
                    <button type="button" class="px-3.5 py-1 bg-[#1877F2] text-white font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-xs">
                        <span>📄</span>
                        <span>Rincian</span>
                    </button>
                    <button type="button" class="px-3 py-1 text-slate-500 hover:text-slate-800 font-medium text-xs rounded-lg flex items-center gap-1.5 cursor-pointer">
                        <span>✂️</span>
                        <span>Potongan</span>
                    </button>
                </div>

                <!-- Tool Buttons Quick Action -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="removeSelectedCartRow()" class="px-2.5 py-1 rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 font-bold text-[11px] flex items-center gap-1 cursor-pointer transition-all shadow-2xs" title="Hapus Item Terpilih (Del)">
                        <span>🗑️</span>
                        <span>Hapus Detail [Del]</span>
                    </button>
                    <button type="button" @click="openEditModal()" class="px-2.5 py-1 rounded-lg border border-blue-200 text-blue-700 hover:bg-blue-50 font-bold text-[11px] flex items-center gap-1 cursor-pointer transition-all shadow-2xs" title="Ubah Qty / Satuan Item">
                        <span>✏️</span>
                        <span>Detail Item</span>
                    </button>
                    <button type="button" @click="triggerCashDrawer()" class="px-2.5 py-1 rounded-lg border border-amber-300 text-amber-800 hover:bg-amber-50 font-bold text-[11px] flex items-center gap-1 cursor-pointer transition-all shadow-2xs" title="Buka Laci Kasir (Cash Drawer Kick)">
                        <span>🔒</span>
                        <span>Buka Laci [F2]</span>
                    </button>
                </div>

                <!-- Pajak, Keluar Dari, Keterangan -->
                <div class="grid grid-cols-12 gap-2 items-center text-[11px] pt-0.5">
                    <div class="col-span-5 flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600">Pajak :</label>
                        <select x-model="selectedTaxMode"
                                @change="onTaxModeChange()"
                                class="excel-input flex-1 border-slate-300 bg-white font-semibold text-slate-800 cursor-pointer text-xs py-1 rounded-lg">
                            <option value="NON">Non-PKP (0%)</option>
                            <option value="PPN_EXCLUDE">PKP (PPN 11% Exclude)</option>
                            <option value="PPN_INCLUDE">PKP (PPN 11% Include)</option>
                        </select>
                    </div>
                    <div class="col-span-7 flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600 whitespace-nowrap">Keluar Dari :</label>
                        <input type="text" value="UTM - {{ $store->name }}" readonly class="excel-input flex-1 bg-slate-50 text-slate-700 border-slate-200 font-mono text-xs py-1 rounded-lg">
                    </div>
                    <div class="col-span-12 flex items-center gap-1.5">
                        <label class="font-semibold text-slate-600 whitespace-nowrap">Keterangan :</label>
                        <input type="text" x-model="transactionNotes" placeholder="Catatan transaksi kasir..." class="excel-input flex-1 border-slate-300 text-xs py-1 rounded-lg">
                    </div>
                </div>
            </div>

            <!-- KARTU 2 (TENGAH - COL 3): BREAKDOWN BIAYA & PEMBULATAN -->
            <div class="col-span-3 bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex flex-col justify-between text-xs space-y-1 font-medium">
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <span class="text-slate-600">Potongan :</span>
                    <span class="font-mono text-slate-800 font-bold">0,00</span>
                </div>
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <span class="text-slate-600">Pajak (PPN) :</span>
                    <span class="font-mono font-bold"
                          :class="taxAmount > 0 ? 'text-blue-700' : 'text-slate-800'"
                          x-text="taxAmount > 0 ? formatRupiah(taxAmount) + ',00' : '0,00'"></span>
                </div>
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <span class="text-slate-600">Biaya Lain :</span>
                    <span class="font-mono text-slate-800 font-bold">0,00</span>
                </div>
                <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                    <span class="text-slate-600">Alamat Kirim :</span>
                    <span class="text-slate-500 font-mono">-</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-600">Pembulatan :</span>
                    <span class="font-mono font-bold"
                          :class="roundingAmount !== 0 ? (roundingAmount > 0 ? 'text-amber-700' : 'text-emerald-700') : 'text-slate-800'"
                          x-text="roundingAmount !== 0 ? formatRupiah(roundingAmount) + ',00' : '0,00'"></span>
                </div>
            </div>

            <!-- KARTU 3 (KANAN - COL 3): SUB TOTAL, VOUCHER & TUKAR POIN MEMBER -->
            <div class="col-span-3 bg-white border border-slate-200 rounded-xl p-3 shadow-xs flex flex-col justify-between gap-2">
                
                <!-- SUB TOTAL BESAR -->
                <div class="flex items-center justify-between">
                    <span class="font-extrabold text-slate-800 text-xs flex items-center gap-1.5">
                        <span class="text-sm">🔄</span>
                        <span>Sub Total :</span>
                    </span>
                    <span class="font-black font-mono text-xl text-slate-900 tracking-tight"
                          x-text="formatRupiah(subtotal) + ',00'"></span>
                </div>

                <!-- KODE VOUCHER (F4) INPUT GROUP -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-slate-700 text-[11px] flex items-center gap-1">
                            <span>🎟️</span>
                            <span>Kode Voucher (F4) :</span>
                        </span>
                        <template x-if="voucherCode">
                            <button type="button" @click="removeVoucher()" class="text-[10px] text-rose-600 hover:underline font-bold">[Hapus]</button>
                        </template>
                    </div>
                    <div class="flex items-center gap-1">
                        <input type="text"
                               x-ref="voucherInput"
                               x-model="voucherCodeInput"
                               @keydown.enter.prevent="applyVoucherCode()"
                               :disabled="voucherCode !== null"
                               placeholder="Masukkan Kode Voucher..."
                               class="excel-input flex-1 font-mono uppercase font-bold text-amber-800 border-slate-300 disabled:bg-slate-100 rounded-lg py-1 text-xs">
                        <button type="button"
                                @click="voucherCode ? removeVoucher() : applyVoucherCode()"
                                class="p-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg text-slate-700 cursor-pointer transition-all"
                                :title="voucherCode ? 'Batalkan Voucher' : 'Cek & Terapkan Voucher'">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        </button>
                    </div>
                    <template x-if="voucherDiscount > 0">
                        <div class="text-[10.5px] text-emerald-700 font-bold flex items-center justify-between mt-1">
                            <span x-text="'Potongan [' + voucherCode + ']:'"></span>
                            <span x-text="'-Rp ' + formatRupiah(voucherDiscount)"></span>
                        </div>
                    </template>
                </div>

                <!-- TUKAR POIN MEMBER (F6) -->
                <div class="border-t border-slate-100 pt-2">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-slate-700 text-[11px] flex items-center gap-1">
                            <span>⭐</span>
                            <span>Tukar Poin Member (F6) :</span>
                        </span>
                        <template x-if="redeemPoints > 0">
                            <button type="button" @click="resetRedeemPoints()" class="text-[10px] text-rose-600 hover:underline font-bold cursor-pointer">[Batal]</button>
                        </template>
                    </div>

                    <!-- Pelanggan Umum / Member -->
                    <div class="flex items-center justify-between gap-1">
                        <span class="text-xs text-slate-700 font-medium truncate max-w-[120px]"
                              x-text="selectedCustomer && selectedCustomer.code !== 'CUST-UMUM' ? selectedCustomer.name : 'Pelanggan Umum'"></span>
                        <button type="button"
                                @click="openTukarPoinModal()"
                                class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white rounded-lg font-bold text-xs flex items-center gap-1.5 shadow-sm cursor-pointer transition-all active:scale-98">
                            <span>⭐</span>
                            <span>Tukar Poin [F6]</span>
                        </button>
                    </div>
                    <template x-if="pointsDiscount > 0">
                        <div class="text-[10.5px] text-emerald-700 font-bold flex items-center justify-between mt-1">
                            <span x-text="'Potongan (' + redeemPoints + ' Pts):'"></span>
                            <span x-text="'-Rp ' + formatRupiah(pointsDiscount)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 4. BARISAN AKSI UTAMA COMMAND BAR (MODERN ENTERPRISE)          -->
        <!-- ============================================================== -->
        <div class="bg-white border border-slate-200 rounded-xl p-2.5 flex items-center justify-between gap-2 shadow-xs shrink-0">
            
            <!-- LEFT COMMAND BUTTONS -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button"
                        @click="openLookupModal()"
                        class="px-4 py-2 bg-[#1877F2] hover:bg-[#1565C0] text-white font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-sm transition-all cursor-pointer"
                        title="Cek Harga & Ketersediaan Stok Real-Time [F9]">
                    <span>🔍</span>
                    <span>Cek Harga [F9]</span>
                </button>
                <button type="button"
                        @click="openCheckoutModal()"
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer">
                    <span>💾</span>
                    <span>Simpan</span>
                </button>
                <button type="button"
                        @click="resetCart()"
                        class="px-3.5 py-2 bg-white hover:bg-rose-50 border border-rose-300 text-rose-600 font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer">
                    <span>🗑️</span>
                    <span>Batal [ESC]</span>
                </button>
                <button type="button"
                        @click="openReprintModal()"
                        class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer">
                    <span>🖨️</span>
                    <span>Cetak</span>
                </button>
                <button type="button"
                        @click="holdCurrentCart()"
                        class="px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer"
                        title="Tahan / Pending Transaksi (Ctrl+1)">
                    <span>⏱️</span>
                    <span>Pending [CTRL+1]</span>
                </button>
                <button type="button"
                        @click="openPendingModal()"
                        class="px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 font-bold text-xs rounded-lg flex items-center gap-1.5 shadow-2xs transition-all cursor-pointer"
                        title="Daftar Transaksi Pending (Ctrl+2 / F7)">
                    <span>📁</span>
                    <span>DR.Pending [CTRL+2]</span>
                </button>
            </div>

            <!-- RIGHT GIANT BAYAR BUTTON -->
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="openCheckoutModal()"
                        :disabled="cart.length === 0"
                        class="bg-gradient-to-r from-[#059669] to-[#10B981] hover:from-[#047857] hover:to-[#059669] text-white font-black text-sm px-8 py-2.5 rounded-xl shadow-md flex items-center gap-2 cursor-pointer transition-all active:scale-98 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                    <span>BAYAR [F12]</span>
                    <svg class="w-4 h-4 text-emerald-200 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

    </div>

    <!-- ============================================================== -->
    <!-- 5. STATUS BAR PALING BAWAH (MODERN ENTERPRISE DARK NAVY)       -->
    <!-- ============================================================== -->
    <footer class="bg-[#03153b] text-slate-300 text-[11px] px-4 py-2 flex items-center justify-between font-medium tracking-tight shadow-md border-t border-blue-900/60 shrink-0">
        <div class="flex items-center gap-3.5 flex-wrap">
            <span><strong class="text-amber-300 font-mono">[F1]</strong> Scan/Jumlah</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[F2]</strong> Buka Laci</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[F3]</strong> Pelanggan</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[F4]</strong> Voucher</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[F6]</strong> Tukar Poin</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-emerald-400 font-mono">[F9]</strong> Cek Harga</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[F10]</strong> Cetak Ulang</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[Del]</strong> Hapus Baris</span>
            <span class="text-slate-600">|</span>
            <span><strong class="text-amber-300 font-mono">[ESC]</strong> Batal/Tutup</span>
        </div>
        <div class="flex items-center gap-3 font-mono text-slate-400 text-[10px]">
            <span>SIGMA ERP Engine v1.0 | PostgreSQL 16+</span>
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" title="Sistem Aktif"></span>
            <span title="Jaringan Stabil">📶</span>
            <span title="Audio Aktif">🔊</span>
        </div>
    </footer>

    <!-- ============================================================== -->
    <!-- 6. MODALS POPUP (CHECKOUT, VOUCHER, SHIFT, BLIND CLOSING)      -->
    <!-- ============================================================== -->

    <!-- A. MODAL CHECKOUT / PEMBAYARAN [F12] -->
    <div x-show="checkoutModal"
         x-cloak
         @keydown.escape.window="if(checkoutModal) { checkoutModal = false; focusQuantityInput(); }"
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl max-w-xl w-full shadow-2xl flex flex-col overflow-hidden text-slate-800 text-xs animate-in fade-in zoom-in duration-150">
            
            <!-- HEADER MODAL -->
            <div class="bg-gradient-to-r from-[#03153b] via-[#082b6b] to-[#041945] text-white px-5 py-3.5 flex items-center justify-between border-b border-blue-900/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-base">
                        💳
                    </div>
                    <div>
                        <h3 class="font-extrabold text-sm tracking-wide">
                            Pembayaran Kasir [F12]
                        </h3>
                        <p class="text-[10.5px] text-slate-300">Pilih metode pembayaran & input nominal pelunasan</p>
                    </div>
                </div>
                <button type="button"
                        @click="checkoutModal = false; focusQuantityInput()"
                        class="text-slate-300 hover:text-white hover:bg-white/10 rounded-lg px-2 py-1 font-bold text-lg transition-colors cursor-pointer"
                        title="Tutup (ESC)">
                    &times;
                </button>
            </div>

            <div class="p-4 space-y-3.5 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- TOTAL TAGIHAN GAYA POS MODERN -->
                <div class="bg-gradient-to-br from-[#072152] via-[#09357a] to-[#08489b] border border-cyan-400/40 rounded-xl p-3.5 text-right shadow-md text-white">
                    <div class="text-[11px] text-cyan-200 font-extrabold uppercase tracking-wider flex items-center justify-between">
                        <span>Total Tagihan Belanja</span>
                        <div class="flex items-center gap-2">
                            <template x-if="selectedTaxMode !== 'NON' && selectedTaxMode !== 'NON_TAX'">
                                <span class="text-[10px] font-mono text-cyan-200 font-normal" x-text="'Pajak: ' + (selectedTaxMode === 'PPN_INCLUDE' || selectedTaxMode === 'INCLUSIVE' ? 'PPN Inc' : 'PPN Exc') + ' (Rp ' + formatRupiah(taxAmount) + ')'"></span>
                            </template>
                            <template x-if="roundingAmount !== 0">
                                <span :class="roundingAmount > 0 ? 'text-amber-300' : 'text-emerald-300'"
                                      class="text-[10px] font-mono font-bold"
                                      x-text="(roundingAmount > 0 ? 'Pembulatan: +Rp ' : 'Pembulatan: -Rp ') + formatRupiah(Math.abs(roundingAmount))"></span>
                            </template>
                        </div>
                    </div>
                    <div class="text-3xl font-black text-[#00F2FE] font-mono tracking-tight mt-1 drop-shadow-[0_0_12px_rgba(0,242,254,0.4)]"
                         x-text="'Rp ' + formatRupiah(grandTotal) + ',00'"></div>
                </div>

                <!-- PILIH METODE BAYAR (TABS) -->
                <div>
                    <label class="block font-extrabold text-slate-800 mb-1.5 text-xs">Pilih Metode Pembayaran:</label>
                    <div class="grid grid-cols-6 gap-1.5">
                        <template x-for="m in ['CASH', 'TRANSFER', 'QRIS', 'SPLIT', 'TEMPO', 'VOUCHER']" :key="m">
                            <button type="button"
                                    @click="paymentMethod = m; onPaymentMethodChange()"
                                    :class="paymentMethod === m ? 'bg-blue-700 text-white border-blue-800 font-bold shadow-xs ring-2 ring-blue-300' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300 font-semibold'"
                                    class="py-2 border rounded-md text-xs text-center transition-all cursor-pointer flex flex-col items-center justify-center gap-0.5">
                                <span class="text-sm" x-text="m === 'CASH' ? '💵' : (m === 'TRANSFER' ? '🏦' : (m === 'QRIS' ? '📱' : (m === 'SPLIT' ? '⚖️' : (m === 'TEMPO' ? '📝' : '🎟️'))))"></span>
                                <span x-text="m"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- 1. INPUT UANG TUNAI (CASH) - BESAR, SIMBOL RP, AUTO DOT FORMATTING -->
                <div x-show="paymentMethod === 'CASH'" class="space-y-3 bg-emerald-50/40 border border-emerald-200 rounded-lg p-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-extrabold text-slate-800 text-xs">
                                Uang Diterima Kasir:
                            </label>
                            <span class="text-[10px] text-emerald-800 font-semibold">Ketik nominal tanpa titik (otomatis diformat)</span>
                        </div>
                        <div class="relative flex items-center">
                            <span class="absolute left-4 font-mono font-black text-2xl text-emerald-700 select-none">Rp</span>
                            <input type="text"
                                   inputmode="numeric"
                                   x-ref="paidAmountInput"
                                   :value="paidAmountFormatted"
                                   @input="onPaidAmountInput($event)"
                                   @keydown.enter.prevent="submitCheckout()"
                                   placeholder="0"
                                   class="w-full text-3xl font-black font-mono text-right pl-16 pr-4 py-3 bg-white border-2 border-emerald-500 rounded-lg shadow-xs text-slate-900 focus:outline-hidden focus:ring-4 focus:ring-emerald-200 focus:border-emerald-600 tracking-wider">
                        </div>
                    </div>

                    <!-- Tombol Uang Cepat (Quick Cash Presets) -->
                    <div class="grid grid-cols-6 gap-1 font-mono text-xs font-bold">
                        <button type="button" @click="setPaidAmount(grandTotal)" class="py-1.5 bg-blue-100 text-blue-900 border border-blue-300 hover:bg-blue-200 rounded-md shadow-2xs">Uang Pas</button>
                        @php
                            $quickCashNominals = array_filter(array_map('intval', explode(',', $company->cashier_quick_cash_nominal ?? '10000,20000,50000,100000,200000')));
                            $quickCashNominals = array_slice($quickCashNominals, 0, 5);
                        @endphp
                        @foreach($quickCashNominals as $qcn)
                            <button type="button" @click="setPaidAmount({{ $qcn }})" class="py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded-md shadow-2xs">
                                {{ $qcn >= 1000 ? ($qcn / 1000) . 'k' : $qcn }}
                            </button>
                        @endforeach
                    </div>

                    <!-- DISPLAY KEMBALIAN KONTRAS TINGGI -->
                    <template x-if="paidAmount >= grandTotal">
                        <div class="space-y-1.5">
                            <div class="bg-emerald-500/15 border-2 border-emerald-500 rounded-lg p-3 flex justify-between items-center font-mono">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-xs">✓</span>
                                    <div>
                                        <span class="text-xs font-black uppercase tracking-wider text-emerald-950 block">Kembalian Kasir</span>
                                        <span class="text-[10px] text-emerald-700">Kembalian uang tunai ke pelanggan</span>
                                    </div>
                                </div>
                                <span class="text-3xl font-black text-emerald-800 tracking-tight"
                                      x-text="'Rp ' + formatRupiah(paidAmount - grandTotal) + ',00'">
                                </span>
                            </div>

                            <template x-if="paidAmount - grandTotal > {{ (float) ($company->cashier_max_change_limit ?? 500000) }}">
                                <div class="bg-amber-100 border border-amber-300 text-amber-900 px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-2xs">
                                    <span>⚠️</span>
                                    <span>Kembalian melebihi batas standar toko (Rp {{ number_format($company->cashier_max_change_limit ?? 500000, 0, ',', '.') }}). Harap hitung uang secara seksama.</span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="paidAmount < grandTotal">
                        <div class="bg-rose-50 border-2 border-rose-400 rounded-lg p-3 flex justify-between items-center font-mono">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-lg shadow-xs">!</span>
                                <div>
                                    <span class="text-xs font-black uppercase tracking-wider text-rose-950 block">Uang Kurang</span>
                                    <span class="text-[10px] text-rose-700">Nominal diterima belum mencukupi</span>
                                </div>
                            </div>
                            <span class="text-2xl font-black text-rose-700 tracking-tight"
                                  x-text="'-Rp ' + formatRupiah(grandTotal - paidAmount) + ',00'">
                            </span>
                        </div>
                    </template>
                </div>

                <!-- 2. PILIHAN BANK REKENING (TRANSFER) -->
                <div x-show="paymentMethod === 'TRANSFER'" x-cloak class="space-y-3 bg-blue-50/50 border border-blue-200 rounded-lg p-3">
                    <div class="flex items-center justify-between">
                        <label class="font-extrabold text-slate-800 text-xs">
                            Pilih Rekening Bank Tujuan Toko:
                        </label>
                        <span class="text-[10px] font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded border border-blue-200">
                            Transfer Resmi Toko
                        </span>
                    </div>

                    <!-- Grid Kartu Bank -->
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="b in bankAccounts" :key="b.code">
                            <div @click="selectBank(b)"
                                 class="p-2.5 rounded-lg border-2 cursor-pointer transition-all flex flex-col justify-between gap-1 shadow-2xs"
                                 :class="selectedBankName === b.bank_name ? 'bg-white border-blue-600 ring-2 ring-blue-200' : 'bg-slate-50 border-slate-200 hover:border-slate-300'">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base font-black text-blue-900 tracking-wider" x-text="b.bank_name"></span>
                                        <span class="text-[10px] font-mono text-slate-400" x-text="'[' + b.code + ']'"></span>
                                    </div>
                                    <span x-show="selectedBankName === b.bank_name" class="text-blue-600 font-extrabold text-xs">✓ Dipilih</span>
                                </div>
                                <div class="font-mono font-black text-sm text-slate-900 tracking-wider" x-text="b.account_number"></div>
                                <div class="text-[10px] text-slate-600 truncate font-semibold" x-text="b.account_holder"></div>
                            </div>
                        </template>
                    </div>

                    <!-- Input Nomor Referensi Transfer -->
                    <div>
                        <label class="block font-bold text-slate-700 text-[11px] mb-1">
                            Nomor Referensi / Catatan Pengirim (Opsional):
                        </label>
                        <input type="text"
                               x-model="paymentReference"
                               @keydown.enter.prevent="submitCheckout()"
                               placeholder="Contoh: Ref 984021 / a.n. Bu Siti..."
                               class="excel-input w-full p-2 border-slate-300 font-mono text-xs rounded bg-white">
                    </div>
                </div>

                <!-- 3. PEMBAYARAN QRIS -->
                <div x-show="paymentMethod === 'QRIS'" x-cloak class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-center space-y-2">
                    <div class="font-bold text-xs text-slate-800">Pembayaran QRIS Nasional (Semua Bank & Dompet Digital)</div>
                    <div class="p-3 bg-white border border-slate-300 rounded-lg inline-block shadow-sm">
                        <div class="w-32 h-32 bg-slate-100 flex flex-col items-center justify-center border-2 border-dashed border-slate-300 rounded">
                            <span class="text-3xl">📱</span>
                            <span class="text-[10px] font-mono font-bold text-slate-700 mt-1">QRIS STATIS TOKO</span>
                            <span class="text-[9px] text-slate-400">NMID: ID1020304050</span>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-600">
                        Arahkan pelanggan scan QRIS di layar/mesin EDC. Pastikan kasir melihat layar HP pelanggan berstatus <strong>BERHASIL</strong>.
                    </p>
                </div>

                <!-- 4. PEMBAYARAN VOUCHER -->
                <div x-show="paymentMethod === 'VOUCHER'" x-cloak class="p-3 bg-amber-50 border border-amber-200 rounded-lg space-y-2">
                    <div class="font-bold text-xs text-amber-900">Pembayaran Menggunakan Voucher Belanja / Gift Card</div>
                    <template x-if="voucherCode">
                        <div class="p-2.5 bg-white border border-amber-300 rounded flex justify-between items-center font-mono">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block" x-text="'Kode Voucher: ' + voucherCode"></span>
                                <span class="text-[10px] text-emerald-700 font-semibold">Tervalidasi & Siap Digunakan</span>
                            </div>
                            <span class="text-lg font-black text-amber-700" x-text="'-Rp ' + formatRupiah(voucherDiscount)"></span>
                        </div>
                    </template>
                    <template x-if="!voucherCode">
                        <div class="text-xs text-slate-600">
                            Belum ada voucher yang diinput. Kasir dapat memasukkan kode voucher di kolom voucher toolbar bawah [F4].
                        </div>
                    </template>
                </div>

                <!-- 5. PANEL KREDIT / TEMPO -->
                <div x-show="paymentMethod === 'TEMPO'" x-cloak class="space-y-2">
                    <div class="border rounded-lg p-3 text-xs"
                         :class="isWalkInCustomer || grandTotal > availableCredit ? 'bg-rose-50 border-rose-300 text-rose-900' : 'bg-emerald-50 border-emerald-300 text-emerald-900'">
                        <div class="font-bold flex items-center justify-between mb-1.5 pb-1 border-b border-slate-300/60">
                            <span>Pemeriksaan Plafon Kredit:</span>
                            <span class="font-mono text-[11px]" x-text="selectedCustomer ? selectedCustomer.name : 'Belum Dipilih'"></span>
                        </div>

                        <template x-if="isWalkInCustomer">
                            <div class="text-rose-700 font-semibold leading-relaxed">
                                ⚠️ <strong>Pelanggan UMUM tidak memiliki fasilitas kredit/tempo.</strong><br>
                                Harap pilih pelanggan grosir terdaftar di kolom pelanggan [F3] atau pilih cara bayar Tunai/QRIS/Transfer.
                            </div>
                        </template>

                        <template x-if="!isWalkInCustomer && grandTotal > availableCredit">
                            <div class="space-y-1">
                                <div class="text-rose-700 font-bold">
                                    ⚠️ Transaksi melebihi sisa plafon kredit!
                                </div>
                                <div class="flex justify-between font-mono text-[11px]">
                                    <span>Sisa Plafon Kredit:</span>
                                    <strong x-text="'Rp ' + formatRupiah(availableCredit)"></strong>
                                </div>
                                <div class="flex justify-between font-mono text-[11px]">
                                    <span>Total Tagihan Transaksi:</span>
                                    <strong class="text-rose-700" x-text="'Rp ' + formatRupiah(grandTotal)"></strong>
                                </div>
                            </div>
                        </template>

                        <template x-if="!isWalkInCustomer && grandTotal <= availableCredit">
                            <div class="space-y-1">
                                <div class="text-emerald-800 font-bold">
                                    ✅ Limit Kredit Pelanggan Memadai.
                                </div>
                                <div class="flex justify-between font-mono text-[11px]">
                                    <span>Sisa Plafon Saat Ini:</span>
                                    <span x-text="'Rp ' + formatRupiah(availableCredit)"></span>
                                </div>
                                <div class="flex justify-between font-mono text-[11px] pt-1 border-t border-emerald-200">
                                    <span>Estimasi Sisa Plafon Baru:</span>
                                    <strong class="text-emerald-900 font-black" x-text="'Rp ' + formatRupiah(availableCredit - grandTotal)"></strong>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- 6. PANEL SPLIT PAYMENT (2 PILIHAN MODE: TUNAI+NON-TUNAI LUNAS ATAU BAYAR SEBAGIAN+TEMPO) -->
                <div x-show="paymentMethod === 'SPLIT'" x-cloak class="space-y-3 bg-purple-50/50 border-2 border-purple-300 rounded-xl p-3.5 shadow-xs">
                    <!-- SUB-TABS MODE SPLIT BILL -->
                    <div class="flex items-center justify-between pb-2 border-b border-purple-200">
                        <div class="flex items-center gap-1.5 text-purple-950 font-extrabold text-xs">
                            <span class="text-base">⚖️</span>
                            <span>Mode Split Bill / Pisah Pembayaran:</span>
                        </div>
                        <div class="flex items-center gap-1 bg-white p-0.5 rounded-lg border border-purple-200">
                            <button type="button"
                                    @click="splitType = 'CASH_NON_CASH'"
                                    :class="splitType === 'CASH_NON_CASH' ? 'bg-purple-700 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-purple-700'"
                                    class="px-2.5 py-1 rounded text-[11px] font-semibold transition-colors cursor-pointer flex items-center gap-1">
                                <span>💵+📱</span>
                                <span>Tunai + Non-Tunai (Lunas)</span>
                            </button>
                            <button type="button"
                                    @click="splitType = 'TEMPO'"
                                    :class="splitType === 'TEMPO' ? 'bg-purple-700 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-purple-700'"
                                    class="px-2.5 py-1 rounded text-[11px] font-semibold transition-colors cursor-pointer flex items-center gap-1">
                                <span>📝</span>
                                <span>Sebagian + Tempo / Piutang</span>
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SUB-MODE 1: TUNAI + NON-TUNAI (QRIS / TRANSFER) -->
                    <!-- ============================================== -->
                    <div x-show="splitType === 'CASH_NON_CASH'" class="space-y-3">
                        <div class="bg-white border border-purple-200 rounded-lg p-2.5 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-700">Total Tagihan Belanja:</span>
                                <div class="text-xs text-slate-500">Bisa digunakan untuk pelanggan umum maupun member</div>
                            </div>
                            <span class="font-mono text-base font-black text-purple-900" x-text="'Rp ' + formatRupiah(grandTotal)"></span>
                        </div>

                        <!-- 1. INPUT PORSI TUNAI -->
                        <div class="space-y-1.5 bg-emerald-50/60 border border-emerald-200 rounded-lg p-3">
                            <div class="flex items-center justify-between text-xs">
                                <label class="font-extrabold text-emerald-950 flex items-center gap-1">
                                    <span>💵</span>
                                    <span>Porsi Uang Tunai (Cash):</span>
                                </label>
                                <span class="text-[10.5px] text-emerald-800 font-semibold">Berapa yang dibayar tunai?</span>
                            </div>
                            <div class="relative flex items-center">
                                <span class="absolute left-3 font-mono font-bold text-lg text-emerald-700 select-none">Rp</span>
                                <input type="text"
                                       inputmode="numeric"
                                       x-ref="splitCashInput"
                                       :value="splitCashAmountFormatted"
                                       @input="onSplitCashInput($event)"
                                       placeholder="0"
                                       class="w-full text-xl font-black font-mono text-right pl-12 pr-3 py-2 bg-white border-2 border-emerald-400 rounded-lg text-slate-900 focus:outline-hidden focus:ring-3 focus:ring-emerald-200 focus:border-emerald-600">
                            </div>
                            <!-- Quick Buttons Tunai -->
                            <div class="grid grid-cols-5 gap-1 font-mono text-[11px] font-bold">
                                <button type="button" @click="setSplitCash(Math.round(grandTotal * 0.5))" class="py-1 bg-white hover:bg-emerald-100 text-emerald-900 border border-emerald-300 rounded cursor-pointer">50%</button>
                                <button type="button" @click="setSplitCash(20000)" class="py-1 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">20k</button>
                                <button type="button" @click="setSplitCash(50000)" class="py-1 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">50k</button>
                                <button type="button" @click="setSplitCash(100000)" class="py-1 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">100k</button>
                                <button type="button" @click="setSplitCash(200000)" class="py-1 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">200k</button>
                            </div>

                            <!-- Uang Tunai Fisik Diterima & Kembalian -->
                            <div class="pt-2 border-t border-emerald-200 grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <label class="block font-bold text-slate-700 text-[11px] mb-1">Uang Tunai Diterima:</label>
                                    <input type="text"
                                           inputmode="numeric"
                                           :value="splitCashTenderedFormatted"
                                           @input="onSplitCashTenderedInput($event)"
                                           class="w-full py-1.5 px-2 bg-white border border-emerald-300 rounded font-mono font-bold text-right text-xs">
                                </div>
                                <div class="bg-white border border-emerald-200 rounded p-1.5 flex flex-col justify-center text-right">
                                    <span class="text-[10px] text-slate-500 font-bold uppercase">Kembalian Tunai:</span>
                                    <span class="font-mono text-sm font-black text-emerald-700" x-text="'Rp ' + formatRupiah(splitCashChange)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. PILIHAN METODE & SISA NON-TUNAI -->
                        <div class="space-y-2 bg-blue-50/60 border border-blue-200 rounded-lg p-3">
                            <div class="flex items-center justify-between text-xs">
                                <label class="font-extrabold text-blue-950 flex items-center gap-1">
                                    <span>📱</span>
                                    <span>Porsi Non-Tunai (Otomatis):</span>
                                </label>
                                <span class="font-mono text-base font-black text-blue-800" x-text="'Rp ' + formatRupiah(splitNonCashAmount)"></span>
                            </div>

                            <!-- Pilihan Non-Tunai: QRIS vs Transfer -->
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <button type="button"
                                        @click="splitNonCashMethod = 'QRIS'"
                                        :class="splitNonCashMethod === 'QRIS' ? 'bg-blue-700 text-white font-bold ring-2 ring-blue-300' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'"
                                        class="py-1.5 border rounded-md text-xs text-center cursor-pointer font-semibold flex items-center justify-center gap-1.5">
                                    <span>📱</span>
                                    <span>QRIS (Otomatis)</span>
                                </button>
                                <button type="button"
                                        @click="splitNonCashMethod = 'TRANSFER'"
                                        :class="splitNonCashMethod === 'TRANSFER' ? 'bg-blue-700 text-white font-bold ring-2 ring-blue-300' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'"
                                        class="py-1.5 border rounded-md text-xs text-center cursor-pointer font-semibold flex items-center justify-center gap-1.5">
                                    <span>🏦</span>
                                    <span>Transfer Bank</span>
                                </button>
                            </div>

                            <!-- Jika Transfer, pilih bank -->
                            <div x-show="splitNonCashMethod === 'TRANSFER'" class="space-y-1.5 pt-2 border-t border-blue-200">
                                <label class="block font-bold text-slate-700 text-[11px]">Rekening Bank Toko:</label>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <template x-for="b in bankAccounts" :key="b.code">
                                        <div @click="selectBank(b)"
                                             class="p-2 rounded border cursor-pointer text-xs"
                                             :class="selectedBankName === b.bank_name ? 'bg-white border-blue-600 ring-2 ring-blue-200' : 'bg-slate-50 border-slate-200 hover:border-slate-300'">
                                            <div class="flex items-center justify-between font-bold">
                                                <span x-text="b.bank_name"></span>
                                                <span x-show="selectedBankName === b.bank_name" class="text-blue-600 font-bold text-[10px]">✓</span>
                                            </div>
                                            <div class="font-mono text-[11px] font-bold text-blue-900" x-text="b.account_number"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- RINGKASAN STATUS SPLIT BILL -->
                        <div class="bg-emerald-100/80 border border-emerald-300 rounded-lg p-2.5 text-xs text-emerald-950 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-base">✅</span>
                                <div>
                                    <strong class="block">Transaksi Split Lunas Seketika</strong>
                                    <span class="text-[11px] text-emerald-800">
                                        Tunai: Rp <span x-text="formatRupiah(splitCashAmount)"></span> +
                                        <span x-text="splitNonCashMethod"></span>: Rp <span x-text="formatRupiah(splitNonCashAmount)"></span>
                                    </span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-emerald-600 text-white font-extrabold text-[10.5px]">PAID / LUNAS</span>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SUB-MODE 2: BAYAR SEBAGIAN + TEMPO / PIUTANG    -->
                    <!-- ============================================== -->
                    <div x-show="splitType === 'TEMPO'" class="space-y-3">
                        <!-- Pilihan Metode untuk Nominal yang Dibayar Sekarang -->
                        <div>
                            <label class="block font-bold text-slate-700 text-[11px] mb-1">
                                Metode Bayar Sebagian (Bayar Sekarang):
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button"
                                        @click="splitMethod = 'CASH'"
                                        :class="splitMethod === 'CASH' ? 'bg-purple-700 text-white font-bold border-purple-900 shadow-xs ring-2 ring-purple-300' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'"
                                        class="py-1.5 border rounded-md text-xs text-center cursor-pointer font-semibold">
                                    💵 Tunai (CASH)
                                </button>
                                <button type="button"
                                        @click="splitMethod = 'TRANSFER'"
                                        :class="splitMethod === 'TRANSFER' ? 'bg-purple-700 text-white font-bold border-purple-900 shadow-xs ring-2 ring-purple-300' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'"
                                        class="py-1.5 border rounded-md text-xs text-center cursor-pointer font-semibold">
                                    🏦 Transfer Bank
                                </button>
                                <button type="button"
                                        @click="splitMethod = 'QRIS'"
                                        :class="splitMethod === 'QRIS' ? 'bg-purple-700 text-white font-bold border-purple-900 shadow-xs ring-2 ring-purple-300' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-100'"
                                        class="py-1.5 border rounded-md text-xs text-center cursor-pointer font-semibold">
                                    📱 QRIS
                                </button>
                            </div>
                        </div>

                        <!-- Pilihan Bank jika Split Method adalah TRANSFER -->
                        <div x-show="splitMethod === 'TRANSFER'" class="space-y-1.5 pt-1 border-t border-purple-200">
                            <label class="block font-bold text-slate-700 text-[11px]">
                                Pilih Rekening Bank Toko:
                            </label>
                            <div class="grid grid-cols-2 gap-1.5">
                                <template x-for="b in bankAccounts" :key="b.code">
                                    <div @click="selectBank(b)"
                                         class="p-2 rounded-lg border cursor-pointer text-xs transition-all"
                                         :class="selectedBankName === b.bank_name ? 'bg-white border-purple-600 ring-2 ring-purple-200' : 'bg-slate-50 border-slate-200 hover:border-slate-300'">
                                        <div class="flex items-center justify-between font-bold">
                                            <span x-text="b.bank_name"></span>
                                            <span x-show="selectedBankName === b.bank_name" class="text-purple-600 font-bold text-[10px]">✓</span>
                                        </div>
                                        <div class="font-mono text-[11px] font-bold text-purple-900" x-text="b.account_number"></div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Input Nominal Bayar Sekarang -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="font-extrabold text-slate-800 text-xs">
                                    Nominal Dibayar Sekarang:
                                </label>
                                <span class="text-[10px] text-purple-800 font-semibold">Ketik nominal yang diterima saat ini</span>
                            </div>
                            <div class="relative flex items-center">
                                <span class="absolute left-4 font-mono font-black text-2xl text-purple-700 select-none">Rp</span>
                                <input type="text"
                                       inputmode="numeric"
                                       x-ref="splitPaidAmountInput"
                                       :value="paidAmountFormatted"
                                       @input="onPaidAmountInput($event)"
                                       @keydown.enter.prevent="submitCheckout()"
                                       placeholder="0"
                                       class="w-full text-2xl font-black font-mono text-right pl-16 pr-4 py-2.5 bg-white border-2 border-purple-500 rounded-lg shadow-xs text-slate-900 focus:outline-hidden focus:ring-4 focus:ring-purple-200 focus:border-purple-600 tracking-wider">
                            </div>
                        </div>

                        <!-- Quick Buttons untuk Split -->
                        <div class="grid grid-cols-5 gap-1 font-mono text-xs font-bold">
                            <button type="button" @click="setPaidAmount(Math.round(grandTotal * 0.5))" class="py-1.5 bg-purple-100 text-purple-900 border border-purple-300 hover:bg-purple-200 rounded cursor-pointer">50%</button>
                            <button type="button" @click="setPaidAmount(50000)" class="py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">50k</button>
                            <button type="button" @click="setPaidAmount(100000)" class="py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">100k</button>
                            <button type="button" @click="setPaidAmount(200000)" class="py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">200k</button>
                            <button type="button" @click="setPaidAmount(500000)" class="py-1.5 bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 rounded cursor-pointer">500k</button>
                        </div>

                        <!-- KARTU ALOKASI SISA KE PIUTANG / TEMPO PELANGGAN -->
                        <div class="border-2 rounded-lg p-3 text-xs space-y-2"
                             :class="isWalkInCustomer || splitRemainingCredit > availableCredit ? 'bg-rose-50 border-rose-300 text-rose-900' : 'bg-white border-purple-400 text-slate-800'">
                            
                            <div class="flex items-center justify-between pb-1 border-b border-slate-200 font-bold">
                                <span class="flex items-center gap-1">
                                    <span>📝 Alokasi Sisa ke Piutang / Tempo:</span>
                                </span>
                                <span class="font-mono text-xs text-blue-900 font-bold" x-text="selectedCustomer ? selectedCustomer.name : 'Belum Dipilih'"></span>
                            </div>

                            <template x-if="isWalkInCustomer">
                                <div class="text-rose-700 font-semibold leading-relaxed">
                                    ⚠️ <strong>Pelanggan UMUM tidak memiliki fasilitas kredit/tempo.</strong><br>
                                    Untuk pembayaran kombinasi Tunai + QRIS/Transfer tanpa hutang, silakan klik tombol <strong>"Tunai + Non-Tunai (Lunas)"</strong> di atas.
                                </div>
                            </template>

                            <template x-if="!isWalkInCustomer">
                                <div class="space-y-1.5 font-mono text-xs">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>Total Tagihan:</span>
                                        <span x-text="'Rp ' + formatRupiah(grandTotal)"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-purple-900 font-bold">
                                        <span>Dibayar Sekarang:</span>
                                        <span x-text="'Rp ' + formatRupiah(paidAmount)"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm font-black text-rose-700 pt-1 border-t border-slate-200">
                                        <span>Sisa Masuk Piutang (Kredit):</span>
                                        <span x-text="'Rp ' + formatRupiah(splitRemainingCredit)"></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center text-[11px] text-slate-500 pt-1 border-t border-dashed border-slate-200">
                                        <span>Sisa Plafon Kredit Pelanggan:</span>
                                        <strong x-text="'Rp ' + formatRupiah(availableCredit)"></strong>
                                    </div>

                                    <template x-if="splitRemainingCredit > availableCredit">
                                        <div class="text-rose-700 font-bold text-[11px] pt-1">
                                            ⚠️ Sisa piutang melebihi sisa plafon kredit pelanggan!
                                        </div>
                                    </template>

                                    <template x-if="splitRemainingCredit <= availableCredit">
                                        <div class="flex justify-between items-center text-[11px] text-emerald-800 font-bold">
                                            <span>Estimasi Sisa Plafon Baru:</span>
                                            <strong x-text="'Rp ' + formatRupiah(availableCredit - splitRemainingCredit)"></strong>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

            </div>

            <!-- FOOTER TOMBOL AKSI MODAL -->
            <div class="bg-slate-50 px-5 py-3 border-t border-slate-200 flex items-center justify-between gap-3">
                <button type="button"
                        @click="checkoutModal = false; focusQuantityInput()"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 font-bold text-slate-700 transition-colors cursor-pointer">
                    Batal (ESC)
                </button>
                <button type="button"
                        @click="submitCheckout()"
                        :disabled="!canSubmitCheckout"
                        class="bg-gradient-to-r from-[#059669] to-[#10B981] hover:from-[#047857] hover:to-[#059669] text-white flex-1 py-2.5 rounded-xl font-black text-sm text-center justify-center shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all cursor-pointer">
                    PROSES SELESAI &amp; CETAK STRUK [Enter]
                </button>
            </div>
        </div>
    </div>

    <!-- B. MODAL BUKA SHIFT -->
    <div x-show="openShiftModal || !activeShiftId" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-sm w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-xl">💼</span>
                    <h3 class="font-extrabold text-sm text-slate-900">Buka Shift Kasir Baru</h3>
                </div>
            </div>
            <p class="text-slate-600">Masukkan modal awal kasir sebelum transaksi:</p>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Modal Awal Kasir (Rp):</label>
                <input type="number"
                       x-ref="startingCashInput"
                       x-model.number="startingCash"
                       @keydown.enter.prevent="submitOpenShift()"
                       class="pos-input w-full text-lg font-bold font-mono text-emerald-800 p-2.5 border-emerald-500 focus:ring-emerald-200">
            </div>
            <div class="pt-1">
                <button type="button" @click="submitOpenShift()" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold justify-center shadow-md cursor-pointer transition-all">
                    Buka Shift Kasir (Enter)
                </button>
            </div>
        </div>
    </div>

    <!-- C. MODAL TUTUP SHIFT (BLIND CLOSING RULE) -->
    <div x-show="blindCloseModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-md w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-rose-900 flex items-center gap-1.5">
                    <span>🔒</span>
                    <span>Tutup Shift (Blind Closing)</span>
                </h3>
                <button type="button" @click="blindCloseModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl text-amber-900">
                <strong>Aturan Blind Closing:</strong> Masukkan uang fisik aktual hasil hitungan di laci kasir tanpa melihat estimasi sistem.
            </div>
            <div>
                <label class="block font-bold text-slate-800 mb-1">Total Uang Fisik Aktual (Rp):</label>
                <input type="number"
                       x-model.number="actualCashCounted"
                       @keydown.enter.prevent="submitBlindClose()"
                       class="pos-input w-full text-xl font-bold font-mono text-slate-900 p-2.5 border-rose-500 focus:ring-rose-200">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Catatan Shift:</label>
                <textarea x-model="closingNotes" class="pos-input w-full p-2" rows="2" placeholder="Catatan shift jika ada..."></textarea>
            </div>
            <div class="flex gap-2 pt-2 border-t border-slate-200">
                <button type="button" @click="blindCloseModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">Batal</button>
                <button type="button" @click="submitBlindClose()" class="flex-1 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl justify-center shadow-md cursor-pointer transition-all">
                    Konfirmasi Tutup Shift (Enter)
                </button>
            </div>
        </div>
    </div>

    <!-- D. MODAL CASH DROP -->
    <div x-show="cashDropModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-sm w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>💼</span>
                    <span>Setor Kas ke Brankas (Cash Drop)</span>
                </h3>
                <button type="button" @click="cashDropModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Jumlah Setor (Rp):</label>
                <input type="number" x-model.number="cashDropAmount" class="pos-input w-full font-mono text-base font-bold p-2">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Alasan / Catatan:</label>
                <input type="text" x-model="cashDropReason" class="pos-input w-full p-2" placeholder="Setoran brankas berkala">
            </div>
            <div class="flex gap-2 pt-2 border-t border-slate-200">
                <button type="button" @click="cashDropModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">Batal</button>
                <button type="button" @click="submitCashDrop()" class="flex-1 py-2 bg-[#1877F2] hover:bg-[#1565C0] text-white font-bold rounded-xl justify-center shadow-md cursor-pointer transition-all">
                    Simpan Setoran
                </button>
            </div>
        </div>
    </div>

    <!-- E. MODAL DETAIL ITEM / UBAH SATUAN & QTY -->
    <div x-show="editItemModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-sm w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>📦</span>
                    <span>Detail Item &amp; Satuan Kemasan</span>
                </h3>
                <button type="button" @click="editItemModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <template x-if="selectedItemForEdit">
                <div class="space-y-3">
                    <div>
                        <span class="text-slate-500 font-semibold">Nama Barang:</span>
                        <div class="font-black text-slate-900 text-sm mt-0.5" x-text="selectedItemForEdit.product_name"></div>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Pilih Satuan Kemasan:</label>
                        <select x-model="selectedItemForEdit.product_unit_id"
                                @change="onItemUnitChange(selectedItemForEdit)"
                                class="pos-input w-full font-bold">
                            <template x-for="u in selectedItemForEdit.available_units" :key="u.id">
                                <option :value="u.id" x-text="u.name + ' (' + u.code + ')'"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kuantitas (Qty):</label>
                        <input type="number"
                               x-model.number="selectedItemForEdit.quantity"
                               @change="onItemQtyChange(selectedItemForEdit)"
                               @keydown.enter.prevent="editItemModal = false"
                               min="1"
                               class="pos-input w-full text-base font-bold font-mono p-2">
                    </div>
                    <div class="pt-2">
                        <button type="button" @click="editItemModal = false" class="w-full py-2.5 bg-[#1877F2] hover:bg-[#1565C0] text-white rounded-xl font-bold justify-center shadow-md cursor-pointer transition-all">
                            Selesai (Enter)
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- F. MODAL CETAK ULANG STRUK [F10] -->
    <div x-show="reprintModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-lg w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>🖨️</span>
                    <span>Cetak Ulang Struk (Reprint) [F10]</span>
                </h3>
                <button type="button" @click="reprintModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <div class="max-h-56 overflow-y-auto divide-y divide-slate-100 bg-slate-50 border border-slate-200 rounded-xl">
                <template x-for="t in recentTransactions" :key="t.id">
                    <div class="p-2.5 hover:bg-blue-50/80 flex justify-between items-center cursor-pointer transition-colors"
                         :class="selectedReprintTx?.id === t.id ? 'bg-blue-100/80 font-bold' : ''"
                         @click="selectTransactionForReprint(t)">
                        <div>
                            <div class="font-mono text-slate-900 font-bold" x-text="t.invoice_number"></div>
                            <div class="text-[10px] text-slate-500 font-mono" x-text="t.created_at"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-blue-700" x-text="'Rp ' + formatRupiah(t.grand_total)"></div>
                            <span class="text-[10px] bg-white border border-slate-200 px-1.5 py-0.2 rounded font-semibold text-slate-600" x-text="t.payment_method"></span>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="isPastShiftReprint" class="p-3 bg-amber-50 border border-amber-300 rounded-xl space-y-1.5">
                <label class="block font-bold text-amber-900">Shift Lampau Memerlukan PIN Manajer:</label>
                <input type="password" x-model="managerPin" placeholder="Default: 1234" class="pos-input w-full font-mono">
            </div>
            <div class="flex gap-2 pt-2 border-t border-slate-200">
                <button type="button" @click="reprintModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">Tutup</button>
                <button type="button" @click="executeReprint()" class="flex-1 py-2 bg-[#1877F2] hover:bg-[#1565C0] text-white rounded-xl font-bold justify-center shadow-md cursor-pointer transition-all">
                    Cetak Ulang Struk
                </button>
            </div>
        </div>
    </div>

    <!-- G. MODAL SIMULASI BUKA LACI KASIR (CASH DRAWER KICK) [F2] -->
    <div x-show="cashDrawerModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-sm w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-amber-900 flex items-center gap-1.5">
                    <span>🔓</span>
                    <span>Buka Laci Kasir [Cash Drawer]</span>
                </h3>
                <button type="button" @click="cashDrawerModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>
            <div class="p-3.5 bg-amber-50 border border-amber-300 rounded-xl text-amber-950 space-y-2">
                <div class="flex items-center gap-2 text-sm font-bold text-amber-800">
                    <span class="text-xl">⚡</span> Perintah ESC/POS Terkirim!
                </div>
                <p class="text-[11px] text-slate-700 leading-relaxed">
                    Sinyal pin laci kasir (Pin 2 / Pin 5: <code class="bg-amber-100 px-1 py-0.5 rounded font-mono text-rose-800 font-bold">ESC p 0 25 250</code>) berhasil dieksekusi ke printer kasir thermal.
                </p>
                <div class="p-2 bg-emerald-100 border border-emerald-300 rounded-lg text-emerald-900 font-semibold text-center">
                    🔔 Status Laci: Terbuka
                </div>
            </div>
            <div class="pt-1">
                <button type="button" @click="cashDrawerModal = false" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold justify-center shadow-md cursor-pointer transition-all">
                    Tutup Notifikasi [ESC / Enter]
                </button>
            </div>
        </div>
    </div>

    <!-- G2. MODAL DAFTAR TRANSAKSI DITAHAN (PENDING / RECALL CARTS) [Ctrl+2 / F7] -->
    <div x-show="pendingModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-xl w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150"
             @click.away="pendingModal = false">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-1.5">
                    <span>📋 Daftar Transaksi Ditahan (Pending / Recall) [Ctrl+2]</span>
                    <span class="px-2 py-0.5 rounded-full bg-blue-700 text-white text-[11px]" x-text="heldCarts.length + ' Transaksi'"></span>
                </h3>
                <button type="button" @click="pendingModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>

            <div x-show="heldCarts.length === 0" class="py-12 text-center text-slate-400 space-y-2">
                <div class="text-3xl">📭</div>
                <div class="font-semibold text-slate-600">Tidak ada transaksi yang sedang ditahan.</div>
                <div class="text-[11px]">Tekan <strong>[Ctrl+1]</strong> atau tombol <strong>Pending</strong> saat ada transaksi di keranjang untuk menahannya sementara.</div>
            </div>

            <div x-show="heldCarts.length > 0" class="overflow-y-auto max-h-[350px] space-y-2 pr-1">
                <template x-for="(held, idx) in heldCarts" :key="held.id">
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl shadow-2xs hover:border-blue-400 transition-all flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 text-sm truncate" x-text="held.customer_name"></span>
                                <span class="px-1.5 py-0.2 bg-white border border-slate-200 rounded text-[10px] font-mono text-slate-500" x-text="held.timestamp"></span>
                            </div>
                            <div class="flex items-center gap-2 text-[11px] text-slate-600 font-mono">
                                <span class="font-bold text-blue-700" x-text="held.total_items + ' Item'"></span>
                                <span>•</span>
                                <span>Total: <strong class="text-emerald-700 font-bold" x-text="'Rp ' + formatRupiah(held.subtotal)"></strong></span>
                            </div>
                            <div x-show="held.notes" class="text-[10px] text-slate-500 italic truncate" x-text="'Catatan: ' + held.notes"></div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="recallTransaction(idx)"
                                    class="px-3 py-1.5 bg-[#1877F2] hover:bg-[#1565C0] text-white font-bold rounded-lg shadow-2xs text-xs flex items-center gap-1 cursor-pointer transition-all">
                                <span>⚡</span>
                                <span>Buka (Recall)</span>
                            </button>
                            <button type="button"
                                    @click="deleteHeldTransaction(idx)"
                                    class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-lg text-xs cursor-pointer transition-all"
                                    title="Hapus transaksi pending">
                                🗑️
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-2 border-t border-slate-200 flex items-center justify-between">
                <span class="text-[10px] text-slate-500">Tersimpan otomatis di penyimpanan lokal kasir.</span>
                <button type="button" @click="pendingModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                    Tutup [ESC]
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL PENUKARAN BARANG / RETUR KASIR [F8] -->
    <div x-show="exchangeModal" x-cloak class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-rose-600 rounded-2xl p-5 max-w-2xl w-full shadow-2xl flex flex-col max-h-[92vh] animate-in fade-in zoom-in duration-150 text-xs text-slate-800"
             @click.away="if (!exchangeSubmitting) exchangeModal = false">
            
            <!-- HEADER MODAL -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🔄</span>
                    <div>
                        <h3 class="font-black text-sm text-slate-900 uppercase tracking-tight">
                            Penukaran Barang / Retur Struk Kasir [F8]
                        </h3>
                        <p class="text-[11px] text-slate-500">
                            Cari faktur belanja konsumen untuk memproses retur barang, pengembalian dana, atau tukar item.
                        </p>
                    </div>
                </div>
                <button type="button" @click="exchangeModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">
                    &times;
                </button>
            </div>

            <!-- SEARCH INVOICE INPUT -->
            <div class="py-3 border-b border-slate-100">
                <form @submit.prevent="searchExchangeInvoice()" class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-2.5 text-slate-400">🔍</span>
                        <input type="text"
                               id="exchange-invoice-input"
                               x-model="exchangeInvoiceInput"
                               placeholder="Scan barcode struk atau ketik No. Faktur (contoh: INV-202610...)"
                               class="pos-input w-full pl-8 pr-3 py-2 font-mono font-bold text-xs uppercase border-slate-300 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-lg">
                    </div>
                    <button type="submit"
                            :disabled="exchangeLoading || !exchangeInvoiceInput.trim()"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold rounded-lg transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1.5 shadow-2xs">
                        <span x-show="!exchangeLoading">Cari Faktur</span>
                        <span x-show="exchangeLoading">Mencari...</span>
                    </button>
                </form>
            </div>

            <!-- HASIL TRANSAKSI & DAFTAR ITEM -->
            <div class="flex-1 overflow-y-auto py-3 space-y-3 pr-1">
                <template x-if="!exchangeTx && !exchangeLoading">
                    <div class="py-10 text-center text-slate-400 space-y-2">
                        <div class="text-4xl">🧾</div>
                        <div class="font-bold text-slate-600">Silakan scan struk atau masukkan nomor faktur belanja</div>
                        <p class="text-[11px] max-w-sm mx-auto text-slate-400">
                            Sistem akan menampilkan rincian barang yang dibeli serta batas sisa kuantitas yang masih dapat diretur.
                        </p>
                    </div>
                </template>

                <template x-if="exchangeTx">
                    <div class="space-y-3">
                        <!-- Ringkasan Faktur -->
                        <div class="bg-rose-50/60 border border-rose-200 rounded-xl p-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-rose-900 text-sm" x-text="exchangeTx.invoice_number"></span>
                                    <span class="px-1.5 py-0.2 rounded bg-rose-200 text-rose-800 font-bold text-[10px]" x-text="exchangeTx.payment_method"></span>
                                </div>
                                <div class="text-[11px] text-slate-600 mt-0.5">
                                    Pelanggan: <strong class="text-slate-900" x-text="exchangeTx.customer_name"></strong> • 
                                    Total: <strong class="text-emerald-700 font-mono" x-text="'Rp ' + formatRupiah(exchangeTx.grand_total)"></strong>
                                </div>
                            </div>
                            <a :href="'/sales/returns/create?invoice_number=' + encodeURIComponent(exchangeTx.invoice_number)"
                               target="_blank"
                               class="text-[11px] font-bold text-rose-700 hover:text-rose-900 underline flex items-center gap-1">
                                <span>Buka Form Retur Lengkap ↗</span>
                            </a>
                        </div>

                        <!-- Tabel Barang -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                            <table class="w-full text-left text-[11px] border-collapse">
                                <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[9.5px]">
                                    <tr>
                                        <th class="py-2 px-2.5 text-center w-8">Pilih</th>
                                        <th class="py-2 px-2.5">Barang</th>
                                        <th class="py-2 px-2 text-center">Beli</th>
                                        <th class="py-2 px-2 text-center">Sisa</th>
                                        <th class="py-2 px-2 text-center w-20">Qty Retur</th>
                                        <th class="py-2 px-2.5 text-center w-32">Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="item in exchangeTx.items" :key="item.id">
                                        <tr class="hover:bg-slate-50 transition-colors" :class="item.selected ? 'bg-rose-50/40' : ''">
                                            <td class="py-2 px-2.5 text-center">
                                                <input type="checkbox"
                                                       x-model="item.selected"
                                                       :disabled="item.max_returnable <= 0"
                                                       class="w-4 h-4 text-rose-600 rounded">
                                            </td>
                                            <td class="py-2 px-2.5">
                                                <div class="font-bold text-slate-900" x-text="item.product_name"></div>
                                                <div class="text-[10px] text-slate-400 font-mono" x-text="'SKU: ' + item.sku + ' • Rp ' + formatRupiah(item.unit_price)"></div>
                                            </td>
                                            <td class="py-2 px-2 text-center font-mono font-semibold" x-text="item.quantity + ' ' + item.unit_name"></td>
                                            <td class="py-2 px-2 text-center font-mono font-bold"
                                                :class="item.max_returnable > 0 ? 'text-emerald-700' : 'text-slate-400'"
                                                x-text="item.max_returnable + ' ' + item.unit_name"></td>
                                            <td class="py-2 px-2 text-center">
                                                <input type="number"
                                                       x-model="item.return_qty"
                                                       :disabled="!item.selected || item.max_returnable <= 0"
                                                       min="0.01"
                                                       :max="item.max_returnable"
                                                       step="any"
                                                       class="pos-input w-16 text-center py-1 font-mono font-bold text-xs border-slate-300">
                                            </td>
                                            <td class="py-2 px-2.5 text-center">
                                                <select x-model="item.condition"
                                                        :disabled="!item.selected"
                                                        class="pos-input w-full py-1 text-[10px] font-semibold border-slate-300">
                                                    <option value="GOOD">Layak Jual (Restock)</option>
                                                    <option value="DAMAGED">Rusak (Karantina)</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Form Pengaturan Pengembalian Dana & Otorisasi -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Metode Pengembalian:</label>
                                <select x-model="exchangeRefundMethod" class="pos-input w-full py-1.5 font-bold border-slate-300">
                                    <option value="CASH">💵 Tunai (Kembalikan Kas)</option>
                                    <option value="TRANSFER">🏦 Transfer Bank</option>
                                    <option value="CREDIT_REDUCTION">📝 Potong Saldo Piutang (Kredit)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-slate-600 mb-1">Alasan Penukaran / Retur:</label>
                                <input type="text"
                                       x-model="exchangeReturnReason"
                                       placeholder="Contoh: Salah ukuran, cacat fisik..."
                                       class="pos-input w-full py-1.5 border-slate-300">
                            </div>
                            @if(!in_array(auth()->user()?->role, ['owner', 'manager', 'store_head']))
                            <div class="sm:col-span-2 bg-amber-50 border border-amber-200 rounded-lg p-2.5 flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-bold text-amber-900 text-xs">Otorisasi Manajer Diperlukan</div>
                                    <div class="text-[10.5px] text-amber-700">Masukkan PIN Manajer yang berwenang untuk merestui retur ini.</div>
                                </div>
                                <input type="password"
                                       x-model="exchangeManagerPin"
                                       placeholder="PIN Manajer"
                                       class="pos-input w-36 py-1.5 text-center font-mono font-black border-amber-300 rounded">
                            </div>
                            @endif
                        </div>
                    </div>
                </template>
            </div>

            <!-- FOOTER TOMBOL AKSI -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-between gap-2">
                <button type="button"
                        @click="exchangeModal = false"
                        :disabled="exchangeSubmitting"
                        class="btn-pos-cmd px-4 py-2 font-bold cursor-pointer">
                    Batal [ESC]
                </button>
                <div class="flex items-center gap-2">
                    <button type="button"
                            x-show="exchangeTx"
                            @click="submitPosReturn()"
                            :disabled="exchangeSubmitting"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-extrabold rounded-lg shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50">
                        <span x-show="!exchangeSubmitting">⚡ Konfirmasi Retur &amp; Cetak Nota</span>
                        <span x-show="exchangeSubmitting">Memproses...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- H. MODAL SUKSES TRANSAKSI BERNUANSA ISLAMI (ALHAMDULILLAH) -->
    <div x-show="successModal"
         x-cloak
         @keydown.window.enter="if(successModal) { finishAndPrintTransaction(); }"
         @keydown.window.space="if(successModal) { finishAndPrintTransaction(); }"
         class="fixed inset-0 bg-slate-950/75 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-emerald-600 rounded-2xl max-w-md w-full shadow-2xl p-6 text-center space-y-4 animate-in fade-in zoom-in duration-200">
            
            <!-- Ikon Centang Beranimasi Nuansa Islami -->
            <div class="flex justify-center">
                <div class="w-16 h-16 rounded-full bg-emerald-100 border-4 border-emerald-500 flex items-center justify-center shadow-lg">
                    <svg class="w-9 h-9 text-emerald-600 stroke-current" fill="none" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </div>

            <!-- Pesan Islami Sesuai Instruksi User -->
            <div>
                <h3 class="text-xl font-black text-emerald-800 tracking-tight font-serif mb-1">
                    Alhamdulillah
                </h3>
                <p class="text-sm font-bold text-slate-800">
                    Bukti transaksi sudah tersimpan
                </p>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Semoga menjadi jual beli yang berkah & bernilai ibadah.
                </p>
            </div>

            <!-- Rincian Faktur & Total -->
            <template x-if="lastTransaction">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 font-mono text-xs space-y-1.5 text-left shadow-2xs">
                    <div class="flex justify-between items-center pb-1 border-b border-slate-200">
                        <span class="text-slate-500 font-sans">No. Faktur:</span>
                        <span class="font-bold text-slate-900" x-text="lastTransaction.invoice_number"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-sans">Total Belanja:</span>
                        <strong class="text-slate-900" x-text="'Rp ' + formatRupiah(lastTransaction.grand_total)"></strong>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 font-sans">Metode Bayar:</span>
                        <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]" x-text="lastTransaction.payment_method"></span>
                    </div>
                    <template x-if="lastTransaction.payment_method === 'CASH'">
                        <div class="flex justify-between items-center pt-1 border-t border-slate-200 font-bold">
                            <span class="text-emerald-800 font-sans font-black">Kembalian:</span>
                            <span class="text-emerald-700 text-sm font-black" x-text="'Rp ' + formatRupiah(lastTransaction.change_amount)"></span>
                        </div>
                    </template>
                    <template x-if="lastTransaction.payment_method === 'SPLIT'">
                        <div class="space-y-1 pt-1 border-t border-slate-200 text-[11px]">
                            <div class="flex justify-between items-center text-purple-900 font-bold">
                                <span class="font-sans" x-text="'Dibayar (' + (lastTransaction.split_payment_method || 'TUNAI') + '):'"></span>
                                <span x-text="'Rp ' + formatRupiah(lastTransaction.paid_amount)"></span>
                            </div>
                            <div class="flex justify-between items-center text-rose-700 font-bold">
                                <span class="font-sans">Masuk Piutang (Kredit):</span>
                                <span x-text="'Rp ' + formatRupiah(lastTransaction.credit_amount)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Countdown Bar Otomatis -->
            <div>
                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-emerald-500 h-1.5 rounded-full transition-all duration-100 ease-linear"
                         :style="'width: ' + autoPrintProgress + '%'"></div>
                </div>
                <div class="text-[10px] text-slate-400 mt-1 flex justify-between items-center">
                    <span>Mencetak otomatis dalam beberapa detik...</span>
                    <span class="font-bold text-emerald-700">[Enter] / [Spasi]</span>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-1">
                <button type="button"
                        x-ref="btnSuccessOk"
                        @click="finishAndPrintTransaction()"
                        class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-extrabold text-sm rounded-lg shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <span>🖨️ Cetak Struk & Transaksi Baru [Enter]</span>
                </button>
            </div>
        </div>
    </div>

    <!-- I. MODAL GANTI KASIR BERTUGAS -->
    <div x-show="switchCashierModal" x-cloak class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl p-5 max-w-md w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-xl">👤</span>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900">Ganti Kasir yang Bertugas</h3>
                        <p class="text-[10.5px] text-slate-500">Pilih akun kasir yang akan menjalankan shift transaksi</p>
                    </div>
                </div>
                <button type="button" @click="switchCashierModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold cursor-pointer">&times;</button>
            </div>

            <!-- Kasir Aktif Saat Ini -->
            <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-lg flex items-center justify-between">
                <div>
                    <span class="text-[10px] uppercase font-bold text-emerald-800 tracking-wider">Kasir Aktif Sekarang:</span>
                    <div class="font-black text-sm text-emerald-950">{{ $cashier->name }}</div>
                    <div class="text-[10.5px] text-emerald-700">{{ $cashier->email }}</div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-600 text-white font-bold text-[10.5px] rounded-full shadow-2xs">Sedang Login</span>
            </div>

            <!-- Pencarian Kasir -->
            <div>
                <label class="block font-bold text-slate-700 mb-1">Cari Kasir:</label>
                <input type="text"
                       x-model="cashierSearch"
                       placeholder="Ketik nama atau email kasir..."
                       class="pos-input w-full p-2 text-xs border-slate-300 rounded-lg">
            </div>

            <!-- Daftar Kasir Tersedia -->
            <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-lg bg-slate-50/50">
                <template x-for="c in filteredCashiers" :key="c.id">
                    <div @click="switchCashier(c.id)"
                         class="p-2.5 flex items-center justify-between cursor-pointer hover:bg-blue-50 transition-colors"
                         :class="c.id === '{{ $cashier->id }}' ? 'bg-emerald-50/80 font-bold' : ''">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-slate-200 border border-slate-300 flex items-center justify-center font-bold text-slate-700 text-xs shadow-2xs"
                                 x-text="c.name.substring(0, 2).toUpperCase()"></div>
                            <div>
                                <div class="font-bold text-slate-900 text-xs" x-text="c.name"></div>
                                <div class="text-[10px] text-slate-500" x-text="c.email"></div>
                            </div>
                        </div>
                        <template x-if="c.id === '{{ $cashier->id }}'">
                            <span class="text-emerald-600 font-extrabold text-xs flex items-center gap-1">
                                <span>✓</span> Aktif
                            </span>
                        </template>
                        <template x-if="c.id !== '{{ $cashier->id }}'">
                            <button type="button" class="btn-pos-cmd px-2.5 py-1 text-[11px] font-semibold text-blue-700 hover:bg-blue-100">
                                Pilih
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <div class="flex justify-end pt-1">
                <button type="button" @click="switchCashierModal = false" class="btn-pos-cmd px-4 py-2 font-bold text-slate-700 hover:bg-slate-200">
                    Tutup (ESC)
                </button>
            </div>
        </div>
    </div>

    <!-- J. MODAL PENGATURAN MINI PRINTER THERMAL (58mm / 80mm) -->
    <div x-show="printerSettingModal" x-cloak class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-slate-700 rounded-xl p-5 max-w-lg w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🖨️</span>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-900">Pengaturan Mini Printer Kasir (Thermal)</h3>
                        <p class="text-[10.5px] text-slate-500">Konfigurasi ukuran kertas struk, laci kasir, dan format cetak</p>
                    </div>
                </div>
                <button type="button" @click="printerSettingModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold cursor-pointer">&times;</button>
            </div>

            <!-- PILIHAN UKURAN KERTAS (58mm vs 80mm) -->
            <div class="space-y-2">
                <label class="block font-black text-slate-800 text-xs">
                    Ukuran Lebar Kertas Struk:
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <!-- OPSI 58mm -->
                    <div @click="setPaperSize('58')"
                         class="p-3 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-2"
                         :class="printerPaperSize === '58' ? 'border-indigo-600 bg-indigo-50/70 ring-2 ring-indigo-200' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900 text-sm">58 mm (Mini Thermal)</span>
                            <span x-show="printerPaperSize === '58'" class="w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">✓</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Cocok untuk <strong>Printer Bluetooth Portable</strong> &amp; Mini USB Kasir (Lebar cetak ~48mm, 32 Karakter).
                        </p>
                        <div class="text-[10px] font-mono text-indigo-700 font-bold bg-white/80 px-2 py-0.5 rounded border border-indigo-200 w-fit">
                            Rekomendasi Hemat Kertas
                        </div>
                    </div>

                    <!-- OPSI 80mm -->
                    <div @click="setPaperSize('80')"
                         class="p-3 rounded-xl border-2 cursor-pointer transition-all flex flex-col justify-between gap-2"
                         :class="printerPaperSize === '80' ? 'border-indigo-600 bg-indigo-50/70 ring-2 ring-indigo-200' : 'border-slate-200 bg-slate-50 hover:border-slate-300'">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-slate-900 text-sm">80 mm (Standar POS)</span>
                            <span x-show="printerPaperSize === '80'" class="w-4 h-4 rounded-full bg-indigo-600 text-white text-[10px] font-bold flex items-center justify-center">✓</span>
                        </div>
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            Cocok untuk <strong>Epson TM-T82 / Bixolon / Star</strong> Kasir Desktop (Lebar cetak ~72mm, 48 Karakter).
                        </p>
                        <div class="text-[10px] font-mono text-slate-600 font-bold bg-white/80 px-2 py-0.5 rounded border border-slate-200 w-fit">
                            Layout Luas &amp; Detail
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOGGLE OPSI PRINTER -->
            <div class="space-y-2.5 pt-1 border-t border-slate-200">
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" x-model="printerAutoPrint" @change="savePrinterSettings()" class="w-4 h-4 text-indigo-600 rounded">
                    <div>
                        <div class="font-bold text-slate-800">Auto Print Struk Setelah Checkout</div>
                        <div class="text-[10.5px] text-slate-500">Membuka dialog cetak otomatis begitu transaksi selesai</div>
                    </div>
                </label>

                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" x-model="printerAutoDrawer" @change="savePrinterSettings()" class="w-4 h-4 text-indigo-600 rounded">
                    <div>
                        <div class="font-bold text-slate-800">Buka Laci Kasir Otomatis (Drawer Kick)</div>
                        <div class="text-[10.5px] text-slate-500">Kirim sinyal pulsa ESC/POS ke laci kasir saat transaksi tunai</div>
                    </div>
                </label>
            </div>

            <!-- CATATAN FOOTER STRUK -->
            <div class="space-y-1 pt-1 border-t border-slate-200">
                <label class="block font-bold text-slate-800">
                    Pesan Khusus Footer Struk:
                </label>
                <textarea x-model="printerFooterNote"
                          @change="savePrinterSettings()"
                          rows="2"
                          class="pos-input w-full p-2 text-xs border-slate-300 rounded-lg"
                          placeholder="Contoh: Barang yang sudah dibeli tidak dapat ditukar kecuali ada perjanjian..."></textarea>
                <p class="text-[10px] text-slate-400">Pesan ini akan otomatis tercetak di baris paling bawah struk belanja.</p>
            </div>

            <!-- KEBIJAKAN PEMBULATAN TRANSAKSI (ROUNDING ENGINE) -->
            <div class="space-y-2 pt-2.5 border-t border-slate-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 font-black text-slate-800 text-xs">
                        <span>🪙</span>
                        <span>Pembulatan Nilai Transaksi Akhir (Rounding Engine)</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="roundingEnabled" @change="saveRoundingSettingsRemote()" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
                
                <div x-show="roundingEnabled" class="grid grid-cols-3 gap-2 bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-[11px]">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Kelipatan:</label>
                        <select x-model="roundingStep" @change="saveRoundingSettingsRemote()" class="w-full p-1.5 bg-white border border-slate-300 rounded text-xs font-semibold">
                            <option value="50">Rp 50</option>
                            <option value="100">Rp 100</option>
                            <option value="500">Rp 500</option>
                            <option value="1000">Rp 1.000</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Arah Mode:</label>
                        <select x-model="roundingMode" @change="saveRoundingSettingsRemote()" class="w-full p-1.5 bg-white border border-slate-300 rounded text-xs font-semibold">
                            <option value="ROUND_NEAREST">Terdekat</option>
                            <option value="ROUND_UP">Ke Atas (Ceil)</option>
                            <option value="ROUND_DOWN">Ke Bawah (Floor)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Cakupan:</label>
                        <select x-model="roundingAppliesTo" @change="saveRoundingSettingsRemote()" class="w-full p-1.5 bg-white border border-slate-300 rounded text-xs font-semibold">
                            <option value="CASH_ONLY">Hanya Tunai/Split</option>
                            <option value="ALL">Semua Metode</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TOMBOL AKSI MODAL -->
            <div class="bg-slate-50 -mx-5 -mb-5 px-5 py-3 rounded-b-xl border-t border-slate-200 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="testPrintReceipt()"
                            class="btn-pos-cmd bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold px-3 py-2 flex items-center gap-1.5 shadow-xs cursor-pointer">
                        <span>🖨️ Cetak Uji Coba</span>
                    </button>
                    <button type="button"
                            @click="triggerCashDrawer()"
                            class="btn-pos-cmd bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white font-bold px-3 py-2 flex items-center gap-1.5 shadow-xs cursor-pointer"
                            title="Uji Sinyal Pembuka Laci Kasir RJ11 / ESC-POS">
                        <span>🔓 Uji Laci</span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="openReceiptSimulation(); printerSettingModal = false;"
                            class="btn-pos-cmd bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2 flex items-center gap-1 cursor-pointer">
                        <span>🧾 Lihat Simulasi</span>
                    </button>
                    <button type="button"
                            @click="printerSettingModal = false"
                            class="btn-pos-cmd px-4 py-2 font-bold text-slate-700 hover:bg-slate-200 cursor-pointer">
                        Selesai (ESC)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- K. MODAL SIMULASI TAMPILAN STRUK THERMAL KASIR (58mm & 80mm) -->
    <div x-show="receiptSimulationModal" x-cloak class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-5 max-w-xl w-full shadow-2xl flex flex-col max-h-[92vh] animate-in fade-in zoom-in duration-150">
            
            <!-- HEADER SIMULASI -->
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center gap-2 text-white">
                    <span class="text-xl">🧾</span>
                    <div>
                        <h3 class="font-extrabold text-sm text-slate-100">Simulasi Tampilan Struk Thermal Kasir</h3>
                        <p class="text-[10.5px] text-slate-400">Pratinjau langsung wujud fisik struk saat tercetak di mini printer</p>
                    </div>
                </div>
                <!-- TOGGLE 58mm / 80mm DI DALAM SIMULASI -->
                <div class="flex items-center gap-1 bg-slate-800 p-1 rounded-lg border border-slate-700">
                    <button type="button"
                            @click="simulatedPaperSize = '58'"
                            :class="simulatedPaperSize === '58' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-400 hover:text-white font-medium'"
                            class="px-2.5 py-1 text-xs rounded-md transition-all cursor-pointer">
                        58 mm
                    </button>
                    <button type="button"
                            @click="simulatedPaperSize = '80'"
                            :class="simulatedPaperSize === '80' ? 'bg-indigo-600 text-white font-extrabold shadow-xs' : 'text-slate-400 hover:text-white font-medium'"
                            class="px-2.5 py-1 text-xs rounded-md transition-all cursor-pointer">
                        80 mm
                    </button>
                    <button type="button" @click="receiptSimulationModal = false" class="ml-2 text-slate-400 hover:text-white text-base font-bold cursor-pointer px-1.5">&times;</button>
                </div>
            </div>

            <!-- CONTAINER SIMULASI KERTAS THERMAL -->
            <div class="flex-1 overflow-y-auto py-5 flex justify-center bg-slate-950/60 rounded-xl my-3">
                <!-- WADAH KERTAS STRUK THERMAL -->
                <div class="bg-white text-slate-900 font-mono shadow-2xl transition-all duration-200 border-x border-slate-300 relative select-text"
                     :style="simulatedPaperSize === '80' ? 'width: 420px; font-size: 12px; padding: 16px 20px;' : 'width: 310px; font-size: 11px; padding: 12px 14px;'">
                    
                    <!-- HEADER TOKO -->
                    <div class="text-center space-y-0.5 pb-2">
                        <div class="font-extrabold text-sm tracking-wide uppercase">{{ $company->name }}</div>
                        <div class="font-bold text-xs">{{ $store->name }}</div>
                        <div class="text-[9.5px] text-slate-600">{{ $store->address ?? 'Pusat Distribusi Retail & Grosir' }}</div>
                        <div class="text-[9.5px] text-slate-600">Telp: {{ $store->phone ?? '0812-3456-7890' }}</div>
                    </div>

                    <div class="border-b border-dashed border-slate-900 my-2"></div>

                    <!-- META TRANSAKSI & KASIR -->
                    <div class="text-[10.5px] space-y-0.5 leading-tight">
                        <div class="flex justify-between">
                            <span>No: POS/{{ now()->format('Ymd') }}/0042</span>
                            <span class="text-right">{{ now()->format('d/m/y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-bold">Kasir: {{ $cashier->name }}</span>
                            <span class="text-right">Tier: Retail (Umum)</span>
                        </div>
                        <div class="text-slate-600">Pelanggan: Pelanggan Umum</div>
                    </div>

                    <div class="border-b border-dashed border-slate-900 my-2"></div>

                    <!-- DAFTAR ITEM CONTOH -->
                    <div class="space-y-1.5 leading-snug">
                        <div>
                            <div class="font-bold truncate">INDOMIE GORENG SPESIAL</div>
                            <div class="flex justify-between text-slate-700 pl-2 text-[10px]">
                                <span>5 PCS x 3.100</span>
                                <span class="font-bold text-slate-900">15.500</span>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold truncate">MINYAK GORENG BIMOLI 2L</div>
                            <div class="flex justify-between text-slate-700 pl-2 text-[10px]">
                                <span>1 PCH x 38.500</span>
                                <span class="font-bold text-slate-900">38.500</span>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold truncate">GULA PASIR GULAKU 1KG</div>
                            <div class="flex justify-between text-slate-700 pl-2 text-[10px]">
                                <span>2 BKS x 17.500</span>
                                <span class="font-bold text-slate-900">35.000</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-b border-dashed border-slate-900 my-2"></div>

                    <!-- TOTAL PEMBAYARAN -->
                    <div class="space-y-0.5 leading-tight">
                        <div class="flex justify-between">
                            <span>Subtotal:</span>
                            <span class="font-bold">Rp 89.000</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Diskon Promo:</span>
                            <span>-Rp 2.000</span>
                        </div>
                        <div class="flex justify-between font-extrabold text-sm pt-1 border-t border-slate-400">
                            <span>TOTAL:</span>
                            <span>Rp 87.000</span>
                        </div>
                        <div class="border-b border-dashed border-slate-900 my-1.5"></div>
                        <div class="flex justify-between">
                            <span>Bayar (TUNAI):</span>
                            <span class="font-bold">Rp 100.000</span>
                        </div>
                        <div class="flex justify-between font-bold">
                            <span>Kembali:</span>
                            <span class="font-bold">Rp 13.000</span>
                        </div>
                    </div>

                    <div class="border-b-2 border-double border-slate-900 my-2.5"></div>

                    <!-- FOOTER STRUK -->
                    <div class="text-center space-y-1 text-[9.5px]">
                        <div class="tracking-widest font-bold font-mono text-xs py-0.5">*POS-{{ now()->format('Ymd') }}-0042*</div>
                        <div class="font-bold" x-text="printerFooterNote || 'Barang yang dibeli tidak dapat ditukar/dikembalikan'"></div>
                        <div class="text-slate-500 italic">Terima Kasih Atas Kunjungan Anda</div>
                        <div class="font-bold tracking-wider pt-1 text-[9px] text-slate-700">SIGMA POS PRO • VERSI RESMI</div>
                    </div>
                </div>
            </div>

            <!-- FOOTER AKSI SIMULASI -->
            <div class="flex items-center justify-between border-t border-slate-800 pt-3">
                <div class="text-[11px] text-slate-400">
                    Lebar saat ini: <strong class="text-emerald-400" x-text="simulatedPaperSize + ' mm'"></strong> (<span x-text="simulatedPaperSize === '58' ? 'Maksimal 32 Karakter/Baris' : 'Maksimal 48 Karakter/Baris'"></span>)
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="testPrintReceipt()"
                            class="btn-pos-cmd bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-1.5 flex items-center gap-1.5 cursor-pointer shadow-xs">
                        <span>🖨️ Cetak Struk Uji Coba</span>
                    </button>
                    <button type="button"
                            @click="receiptSimulationModal = false"
                            class="btn-pos-cmd px-4 py-1.5 font-bold text-slate-300 hover:bg-slate-800 cursor-pointer">
                        Tutup (ESC)
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- L. MODAL CEK HARGA & KETERSEDIAAN STOK REAL-TIME [F9] -->
    <div x-show="lookupModal" x-cloak class="fixed inset-0 bg-black/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border-2 border-[#125BCE] rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh] text-slate-800 text-xs animate-in fade-in zoom-in duration-150">
            
            <!-- HEADER MODAL -->
            <div class="bg-gradient-to-r from-[#0B1F4D] via-[#125BCE] to-[#0B1F4D] text-white px-5 py-3.5 flex items-center justify-between border-b border-[#18BDEB]/30">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center text-lg border border-white/20">
                        🔍
                    </div>
                    <div>
                        <h3 class="font-black text-sm tracking-wide flex items-center gap-2">
                            <span>Pengecekan Harga &amp; Ketersediaan Stok</span>
                            <span class="px-2 py-0.5 rounded-full bg-[#18BDEB]/20 text-[#18BDEB] border border-[#18BDEB]/40 text-[10px] font-mono font-bold">F9</span>
                        </h3>
                        <p class="text-[11px] text-slate-300">Cek harga semua tier &amp; sisa stok toko tanpa mengubah keranjang kasir</p>
                    </div>
                </div>
                <button type="button" @click="lookupModal = false; focusQuantityInput()" class="text-slate-300 hover:text-white text-xl font-bold p-1 cursor-pointer">&times;</button>
            </div>

            <!-- SEARCH INPUT BAR -->
            <div class="p-4 bg-slate-50 border-b border-slate-200">
                <form @submit.prevent="executeLookup()" class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
                        <input type="text"
                               x-ref="lookupInput"
                               x-model="lookupQuery"
                               placeholder="Tembak barcode scanner atau ketik nama barang / SKU..."
                               class="w-full pl-10 pr-4 py-2.5 bg-white border-2 border-[#125BCE] rounded-xl text-xs font-semibold text-slate-900 shadow-inner focus:outline-hidden focus:ring-3 focus:ring-[#18BDEB]/30">
                    </div>
                    <button type="submit"
                            :disabled="lookupLoading || !lookupQuery.trim()"
                            class="px-5 py-2.5 bg-[#125BCE] hover:bg-[#0E4DB3] active:bg-[#0B3D8F] text-white font-extrabold rounded-xl shadow-sm transition-all flex items-center gap-1.5 cursor-pointer disabled:opacity-50">
                        <span x-show="!lookupLoading">Cari</span>
                        <span x-show="lookupLoading">Memuat...</span>
                        <kbd class="px-1.5 py-0.2 bg-black/20 rounded text-[9.5px] font-mono">Enter</kbd>
                    </button>
                </form>
            </div>

            <!-- CONTENT BODY -->
            <div class="p-5 overflow-y-auto space-y-4 flex-1">

                <!-- KONDISI KETIKA BELUM ADA PENCARIAN -->
                <div x-show="!lookupResult && !lookupError && !lookupLoading" class="py-12 text-center text-slate-400 space-y-2">
                    <span class="text-4xl block">📦</span>
                    <div class="font-bold text-slate-600 text-sm">Silakan Scan Barcode atau Ketik Nama Produk</div>
                    <p class="text-[11px] text-slate-400 max-w-md mx-auto">
                        Anda dapat memeriksa harga eceran, grosir, dan ketersediaan stok fisik tanpa khawatir merusak atau mengubah keranjang transaksi kasir yang sedang aktif.
                    </p>
                </div>

                <!-- PESAN ERROR JIKA TIDAK DITEMUKAN -->
                <div x-show="lookupError" class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 flex items-center gap-3">
                    <span class="text-xl">⚠️</span>
                    <span class="font-semibold text-xs" x-text="lookupError"></span>
                </div>

                <!-- HASIL PENCARIAN DETAIL PRODUK -->
                <template x-if="lookupResult">
                    <div class="space-y-4">
                        <!-- HEADER PRODUK & STOK TOKO -->
                        <div class="bg-gradient-to-br from-slate-50 to-blue-50/40 border border-blue-200/80 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md bg-[#125BCE]/10 text-[#125BCE] font-mono font-bold text-[10.5px]" x-text="'SKU: ' + lookupResult.sku"></span>
                                    <span class="px-2 py-0.5 rounded-md bg-slate-200 text-slate-700 font-mono font-semibold text-[10.5px]" x-text="'Barcode: ' + lookupResult.barcode"></span>
                                    <span class="px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 font-semibold text-[10.5px]" x-text="lookupResult.category"></span>
                                </div>
                                <h4 class="font-black text-base text-slate-900 mt-1.5" x-text="lookupResult.name"></h4>
                            </div>

                            <!-- BADGE STOK TOKO TERSEDIA -->
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <div class="bg-emerald-50 border border-emerald-300 rounded-xl px-3.5 py-2 text-right shadow-2xs">
                                    <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1 justify-end">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Stok Siap Jual
                                    </div>
                                    <div class="font-mono font-black text-lg text-emerald-700" x-text="lookupResult.stock_available_formatted + ' PCS'"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 3-WAY STOCK BREAKDOWN -->
                        <div class="grid grid-cols-3 gap-2 text-[11px]">
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-2.5 text-center">
                                <span class="text-slate-500 block text-[10px] font-bold uppercase">Stok Fisik Gudang/Toko</span>
                                <strong class="font-mono text-slate-800 text-sm" x-text="lookupResult.stock_physical + ' PCS'"></strong>
                            </div>
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 text-center">
                                <span class="text-amber-700 block text-[10px] font-bold uppercase">Dipesan (Reserved PWA)</span>
                                <strong class="font-mono text-amber-900 text-sm" x-text="lookupResult.stock_reserved + ' PCS'"></strong>
                            </div>
                            <div class="bg-emerald-50 border border-emerald-300 rounded-lg p-2.5 text-center">
                                <span class="text-emerald-700 block text-[10px] font-bold uppercase">Stok Tersedia (Available)</span>
                                <strong class="font-mono text-emerald-900 text-sm" x-text="lookupResult.stock_available + ' PCS'"></strong>
                            </div>
                        </div>

                        <!-- TABEL DETAIL HARGA PER SATUAN & TIER PELANGGAN -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h5 class="font-extrabold text-xs text-slate-800 flex items-center gap-1.5">
                                    <span>🏷️</span>
                                    <span>Daftar Harga Satuan &amp; Level Pelanggan</span>
                                </h5>
                                <span class="text-[10.5px] text-slate-500">Otomatis dihitung sesuai AGENTS.md 3D Pricing</span>
                            </div>

                            <div class="border border-slate-200 rounded-xl overflow-hidden shadow-2xs">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-slate-100 border-b border-slate-200 text-slate-700 text-[10.5px] font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="py-2 px-3">Satuan Kemasan</th>
                                            <th class="py-2 px-3">Isi Konversi</th>
                                            <th class="py-2 px-3 text-right">Stok Kemasan</th>
                                            <th class="py-2 px-3 text-right">Harga Umum (Retail)</th>
                                            <th class="py-2 px-3 text-right">Aksi Kasir</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 text-xs">
                                        <template x-for="unit in lookupResult.units" :key="unit.id">
                                            <tr class="hover:bg-blue-50/40 transition-colors">
                                                <td class="py-2.5 px-3">
                                                    <span class="font-black text-slate-900" x-text="unit.code"></span>
                                                    <span class="text-slate-400 text-[10.5px] ml-1" x-text="'(' + unit.name + ')'"></span>
                                                    <template x-if="unit.is_base">
                                                        <span class="ml-1.5 px-1.5 py-0.2 rounded bg-blue-100 text-blue-700 text-[9px] font-bold">Base</span>
                                                    </template>
                                                </td>
                                                <td class="py-2.5 px-3 font-mono text-slate-600" x-text="'1 ' + unit.code + ' = ' + unit.conversion_factor + ' PCS'"></td>
                                                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700" x-text="unit.stock_formatted"></td>
                                                <td class="py-2.5 px-3 text-right font-mono font-black text-sm text-[#125BCE]">
                                                    <span x-text="unit.tier_prices && unit.tier_prices.length > 0 ? unit.tier_prices[0].price_formatted : '-'"></span>
                                                </td>
                                                <td class="py-2.5 px-3 text-right">
                                                    <button type="button"
                                                            @click="addLookupToCart(unit)"
                                                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold rounded-lg text-[11px] shadow-2xs transition-all cursor-pointer inline-flex items-center gap-1">
                                                        <span>+ Masuk Kasir</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- RINCIAN HARGA TIER LEVEL JIKA ADA GROSIR -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                            <span class="font-bold text-slate-700 text-[11px] block">Informasi Harga Pelanggan Khusus (Member &amp; Grosir):</span>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[10.5px]">
                                <template x-if="lookupResult.units && lookupResult.units.length > 0">
                                    <template x-for="tierP in lookupResult.units[0].tier_prices" :key="tierP.tier_id">
                                        <div class="bg-white p-2 border border-slate-200 rounded-lg shadow-2xs">
                                            <span class="text-slate-500 block truncate" x-text="tierP.tier_name"></span>
                                            <strong class="font-mono text-slate-900 font-black text-xs" x-text="tierP.price_formatted"></strong>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>

                    </div>
                </template>

            </div>

            <!-- FOOTER AKSI MODAL -->
            <div class="bg-slate-100 border-t border-slate-200 px-5 py-3 flex items-center justify-between gap-3">
                <div class="text-[11px] text-slate-500">
                    Tekan <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded text-[10px] font-mono">ESC</kbd> untuk menutup tanpa mengubah keranjang belanja.
                </div>
                <div class="flex items-center gap-2">
                    <template x-if="lookupResult">
                        <button type="button"
                                @click="addLookupToCart()"
                                class="btn-action-primary py-2 px-4 rounded-xl font-bold flex items-center gap-1.5 cursor-pointer shadow-xs">
                            <span>🛒 Masukkan ke Keranjang [Enter]</span>
                        </button>
                    </template>
                    <button type="button"
                            @click="lookupModal = false; focusQuantityInput()"
                            class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl cursor-pointer transition-colors">
                        Tutup (ESC)
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- M. MODAL TUKAR POIN LOYALITAS PELANGGAN [F6] -->
    <div x-show="tukarPoinModal" x-cloak class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-5 max-w-md w-full shadow-2xl text-xs space-y-4 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-200 pb-2.5">
                <h3 class="font-extrabold text-sm text-slate-900 flex items-center gap-2">
                    <span>⭐</span>
                    <span>Penukaran Poin Loyalitas Pelanggan [F6]</span>
                </h3>
                <button type="button" @click="tukarPoinModal = false; focusQuantityInput()" class="text-slate-400 hover:text-slate-700 font-bold text-lg cursor-pointer">&times;</button>
            </div>

            <template x-if="!selectedCustomer || selectedCustomer.code === 'CUST-UMUM'">
                <div class="p-3 bg-amber-50 border border-amber-300 rounded-xl space-y-1.5">
                    <div class="font-bold text-amber-900">Pelanggan Umum (Non-Member) Terpilih</div>
                    <p class="text-[11px] text-amber-800">
                        Penukaran poin loyalitas hanya dapat digunakan untuk pelanggan/member terdaftar yang memiliki saldo poin. Silakan pilih member pada data Pelanggan terlebih dahulu.
                    </p>
                </div>
            </template>

            <template x-if="selectedCustomer && selectedCustomer.code !== 'CUST-UMUM'">
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-semibold">Pelanggan:</span>
                            <span class="font-bold text-slate-900" x-text="selectedCustomer.name"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 font-semibold">Saldo Poin Tersedia:</span>
                            <span class="font-bold text-amber-700 font-mono text-sm" x-text="(selectedCustomer.loyalty_points || 0) + ' Poin'"></span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] text-slate-500">
                            <span>Nilai Konversi:</span>
                            <span x-text="'1 Poin = Rp ' + formatRupiah(pointRate) + ' (Min. ' + minPointsToRedeem + ' Poin)'"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jumlah Poin yang Ditukarkan:</label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   min="0"
                                   :max="selectedCustomer.loyalty_points || 0"
                                   x-model.number="redeemPointsInput"
                                   @input="onPointsInputChange()"
                                   @keydown.enter.prevent="applyPointsRedemption(); tukarPoinModal = false; focusQuantityInput();"
                                   placeholder="Masukkan kuota poin..."
                                   class="pos-input flex-1 font-mono text-base font-bold p-2">
                            <button type="button"
                                    @click="redeemAllCustomerPoints()"
                                    class="px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg font-bold text-xs cursor-pointer">
                                Tukar Semua
                            </button>
                        </div>
                    </div>

                    <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl flex justify-between items-center font-bold text-emerald-900">
                        <span>Potongan Diskon Belanja:</span>
                        <span class="font-mono text-sm font-black" x-text="'Rp ' + formatRupiah(Math.min(Math.max(0, subtotal - voucherDiscount), (parseInt(redeemPointsInput) || 0) * pointRate))"></span>
                    </div>
                </div>
            </template>

            <div class="flex gap-2 pt-2 border-t border-slate-200">
                <template x-if="redeemPoints > 0">
                    <button type="button"
                            @click="resetRedeemPoints(); tukarPoinModal = false; focusQuantityInput();"
                            class="px-3 py-2 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 font-bold rounded-xl cursor-pointer">
                        Batalkan Poin
                    </button>
                </template>
                <button type="button" @click="tukarPoinModal = false; focusQuantityInput()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                    Tutup (ESC)
                </button>
                <template x-if="selectedCustomer && selectedCustomer.code !== 'CUST-UMUM'">
                    <button type="button"
                            @click="applyPointsRedemption(); tukarPoinModal = false; focusQuantityInput();"
                            class="flex-1 py-2 bg-[#1877F2] hover:bg-[#1565C0] text-white font-bold rounded-xl justify-center shadow-md cursor-pointer transition-all">
                        Terapkan Diskon Poin
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT STATE & KEYBOARD SHORTCUTS ENGINE -->
    <script>
        function posDesktopApp() {
            return {
                csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                activeShiftId: '{{ $activeShift?->id }}',
                customers: @json($customers),
                customerTiers: @json($customerTiers),
                selectedCustomerId: '{{ $customers->firstWhere("code", "CUST-UMUM")?->id ?? $customers->first()?->id }}',
                selectedTierId: '{{ $customers->firstWhere("code", "CUST-UMUM")?->customer_tier_id ?? $customerTiers->firstWhere("is_default", true)?->id ?? $customerTiers->first()?->id }}',
                transactionDate: '{{ now()->format("d/m/Y H:i:s") }}',
                liveClock: '',

                // Sales & Pengaturan Pajak ERP (AGENTS.md Section 5 - Zero Kasir Override)
                salesList: @json($salesList),
                selectedSalesId: '{{ $cashier->id }}',
                taxConfigurations: @json($taxConfigurations),
                erpTaxMode: '{{ $erpTaxMode }}',
                erpTaxRate: {{ (float) $erpTaxRate }},
                erpTaxRatePercent: {{ (float) $erpTaxRatePercent }},
                erpTaxName: '{{ $erpTaxName }}',
                erpTaxLabel: '{{ $erpTaxLabel }}',
                selectedTaxMode: '{{ $erpTaxMode }}',
                taxRate: {{ (float) $erpTaxRate }},

                // Bank Transfer State
                bankAccounts: @json($bankAccounts),
                selectedBankCode: '1002-001',
                selectedBankName: 'BCA',
                selectedBankAccountNumber: '123-456-7890',
                paymentReference: '',
                paidAmountFormatted: '0',
                splitMethod: 'CASH',

                get selectedCustomer() {
                    return this.customers.find(c => c.id === this.selectedCustomerId) || null;
                },

                get isWalkInCustomer() {
                    if (!this.selectedCustomer) return true;
                    return this.selectedCustomer.code === 'CUST-UMUM' || parseFloat(this.selectedCustomer.credit_limit) <= 0;
                },

                get availableCredit() {
                    if (!this.selectedCustomer) return 0;
                    return Math.max(0, parseFloat(this.selectedCustomer.credit_limit || 0) - parseFloat(this.selectedCustomer.current_credit_balance || 0));
                },

                // Keranjang & Baris Aktif
                cart: [],
                cartIdempotencyKey: (window.crypto && window.crypto.randomUUID)
                    ? window.crypto.randomUUID()
                    : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                        const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                        return v.toString(16);
                    }),
                selectedCartIdx: 0,

                generateUuid() {
                    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                        return window.crypto.randomUUID();
                    }
                    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                        const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                        return v.toString(16);
                    });
                },

                // Fitur Krusial: Opsi Jumlah Sebelum Scan
                inputScanQty: 1,
                searchQuery: '',
                searchResults: [],
                selectedSearchIdx: 0,

                // Voucher & Diskon
                voucherCodeInput: '',
                voucherCode: null,
                voucherDiscount: 0,
                transactionNotes: '',

                // Promosi & Tebus Murah State (AGENTS.md Section 3)
                activePromotions: @json($activePromotions ?? []),

                get eligibleRewards() {
                    const nonPromoSubtotal = this.cart
                        .filter(item => !item.is_tebus_murah)
                        .reduce((acc, item) => acc + (parseFloat(item.subtotal) || 0), 0);

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

                isRewardAlreadyClaimed(reward) {
                    return this.cart.some(item => item.product_id === reward.reward_product_id && item.is_tebus_murah);
                },

                async claimTebusMurah(reward) {
                    if (this.isRewardAlreadyClaimed(reward)) return;
                    const prod = reward.promo.reward_product;
                    if (!prod) return;

                    const unit = prod.units && prod.units.length > 0 ? prod.units[0] : null;
                    if (!unit) return;

                    const qty = reward.max_quantity || 1;
                    const specialPrice = reward.special_price;

                    let normalPrice = specialPrice;
                    if (prod.prices && prod.prices.length > 0) {
                        const prObj = prod.prices.find(p => p.product_unit_id === unit.id) || prod.prices[0];
                        normalPrice = parseFloat(prObj.price) || specialPrice;
                    }

                    const discountPerUnit = Math.max(0, normalPrice - specialPrice);
                    const totalDiscount = discountPerUnit * qty;

                    const newItem = {
                        product_id: prod.id,
                        product_name: prod.name,
                        sku: prod.sku,
                        barcode: prod.barcode,
                        product_unit_id: unit.id,
                        available_units: prod.units,
                        quantity: qty,
                        unit_price: normalPrice,
                        discount_amount: totalDiscount,
                        subtotal: specialPrice * qty,
                        is_tebus_murah: true,
                        promotion_id: reward.promotion_id
                    };

                    this.cart.push(newItem);
                    this.selectedCartIdx = this.cart.length - 1;
                },

                // Poin Loyalitas (Loyalty Points)
                redeemPointsInput: '',
                redeemPoints: 0,
                pointsDiscount: 0,
                pointRate: {{ (float) ($loyaltyProgram->point_redemption_value ?? 100) }},
                minPointsToRedeem: {{ (int) ($loyaltyProgram->min_points_to_redeem ?? 10) }},

                // Modals
                tukarPoinModal: false,
                openShiftModal: false,
                checkoutModal: false,
                editItemModal: false,
                cashDropModal: false,
                blindCloseModal: false,
                reprintModal: false,
                cashDrawerModal: false,
                successModal: false,
                switchCashierModal: false,
                printerSettingModal: false,
                receiptSimulationModal: false,
                pendingModal: false,
                exchangeModal: false,
                exchangeInvoiceInput: '',
                exchangeTx: null,
                exchangeLoading: false,
                exchangeReturnReason: 'Tukar Barang Konsumen',
                exchangeRefundMethod: 'CASH',
                exchangeManagerPin: '',
                exchangeSubmitting: false,

                // Cek Harga & Stok Real-Time [F9]
                lookupModal: false,
                lookupQuery: '',
                lookupLoading: false,
                lookupResult: null,
                lookupError: '',
                heldCarts: JSON.parse(localStorage.getItem('sigma_held_carts') || '[]'),
                lastTransaction: null,
                autoPrintProgress: 100,
                autoPrintTimer: null,
                autoPrintInterval: null,

                // Kasir & Printer Thermal State
                allCashiers: @json($allCashiers),
                cashierSearch: '',
                printerPaperSize: localStorage.getItem('sigma_pos_printer_paper') || '{{ $company->receipt_paper_size ?? '58' }}',
                printerAutoPrint: localStorage.getItem('sigma_pos_printer_autoprint') !== null ? (localStorage.getItem('sigma_pos_printer_autoprint') === 'true') : {{ ($company->receipt_auto_print ?? true) ? 'true' : 'false' }},
                printerAutoDrawer: localStorage.getItem('sigma_pos_printer_autodrawer') !== null ? (localStorage.getItem('sigma_pos_printer_autodrawer') === 'true') : {{ ($company->receipt_open_drawer ?? true) ? 'true' : 'false' }},
                printerFooterNote: localStorage.getItem('sigma_pos_printer_footer') || '{{ addslashes(str_replace(["\r\n", "\r", "\n"], ' ', $company->receipt_footer_line1 ?? $company->receipt_footer ?? 'Barang yang dibeli tidak dapat ditukar/dikembalikan.')) }}',
                simulatedPaperSize: '58',

                get filteredCashiers() {
                    if (!this.cashierSearch) return this.allCashiers;
                    const q = this.cashierSearch.toLowerCase();
                    return this.allCashiers.filter(c => (c.name && c.name.toLowerCase().includes(q)) || (c.email && c.email.toLowerCase().includes(q)));
                },

                // Modal state inputs
                startingCash: 200000,
                paymentMethod: 'CASH',
                paidAmount: 0,
                cashDropAmount: 50000,
                cashDropReason: '',
                actualCashCounted: 0,
                closingNotes: '',

                // Pengaturan Pembulatan Transaksi (Rounding Engine)
                roundingEnabled: {{ ($company->rounding_enabled ?? false) ? 'true' : 'false' }},
                roundingStep: {{ (int) ($company->rounding_step ?? 100) }},
                roundingMode: '{{ $company->rounding_mode ?? "ROUND_NEAREST" }}',
                roundingAppliesTo: '{{ $company->rounding_applies_to ?? "CASH_ONLY" }}',

                // True Split Bill & Split Tempo state
                splitType: 'CASH_NON_CASH', // 'CASH_NON_CASH' (Lunas seketika) or 'TEMPO' (Piutang)
                splitCashAmount: 0,
                splitCashAmountFormatted: '0',
                splitCashTendered: 0,
                splitCashTenderedFormatted: '0',
                splitNonCashMethod: 'QRIS', // 'QRIS' or 'TRANSFER'

                selectedItemForEdit: null,
                recentTransactions: @json($recentTransactions),
                selectedReprintTx: null,
                isPastShiftReprint: false,
                managerPin: '',

                get subtotal() {
                    return this.cart.reduce((acc, item) => acc + (parseFloat(item.subtotal) || 0), 0);
                },

                get netSubtotal() {
                    return Math.max(0, this.subtotal - this.voucherDiscount - this.pointsDiscount);
                },

                get taxAmount() {
                    if (this.selectedTaxMode === 'PPN_EXCLUDE' || this.selectedTaxMode === 'EXCLUSIVE') {
                        return Math.round(this.netSubtotal * this.taxRate);
                    } else if (this.selectedTaxMode === 'PPN_INCLUDE' || this.selectedTaxMode === 'INCLUSIVE') {
                        return Math.round(this.netSubtotal - (this.netSubtotal / (1 + this.taxRate)));
                    }
                    return 0;
                },

                get rawGrandTotal() {
                    if (this.selectedTaxMode === 'PPN_EXCLUDE' || this.selectedTaxMode === 'EXCLUSIVE') {
                        return Math.max(0, this.netSubtotal + this.taxAmount);
                    }
                    return this.netSubtotal;
                },

                get roundingAmount() {
                    if (!this.roundingEnabled) return 0;
                    const appliesTo = this.roundingAppliesTo || 'CASH_ONLY';
                    const isCash = this.paymentMethod === 'CASH' ||
                        (this.paymentMethod === 'SPLIT' && (this.splitType === 'CASH_NON_CASH' || this.splitMethod === 'CASH'));
                    if (appliesTo === 'CASH_ONLY' && !isCash) return 0;

                    const raw = this.rawGrandTotal;
                    const step = parseInt(this.roundingStep) || 100;
                    if (step <= 0) return 0;

                    let rounded = raw;
                    if (this.roundingMode === 'ROUND_UP') {
                        rounded = Math.ceil(raw / step) * step;
                    } else if (this.roundingMode === 'ROUND_DOWN') {
                        rounded = Math.floor(raw / step) * step;
                    } else {
                        rounded = Math.round(raw / step) * step;
                    }
                    return Math.round(rounded - raw);
                },

                get grandTotal() {
                    return Math.max(0, this.rawGrandTotal + this.roundingAmount);
                },

                get splitNonCashAmount() {
                    return Math.max(0, this.grandTotal - (parseFloat(this.splitCashAmount) || 0));
                },

                get splitCashChange() {
                    const tendered = parseFloat(this.splitCashTendered) || 0;
                    const cashNeeded = parseFloat(this.splitCashAmount) || 0;
                    return Math.max(0, tendered - cashNeeded);
                },

                get splitRemainingCredit() {
                    return Math.max(0, this.grandTotal - this.paidAmount);
                },

                get canSubmitCheckout() {
                    if (this.cart.length === 0) return false;
                    if (this.paymentMethod === 'CASH') {
                        return this.paidAmount >= this.grandTotal;
                    }
                    if (this.paymentMethod === 'TEMPO') {
                        return !this.isWalkInCustomer && this.grandTotal <= this.availableCredit;
                    }
                    if (this.paymentMethod === 'TRANSFER') {
                        return !!this.selectedBankName;
                    }
                    if (this.paymentMethod === 'SPLIT') {
                        if (this.splitType === 'CASH_NON_CASH') {
                            const cashAmt = parseFloat(this.splitCashAmount) || 0;
                            const tendered = parseFloat(this.splitCashTendered) || 0;
                            if (cashAmt <= 0) return false;
                            if (cashAmt >= this.grandTotal) return false;
                            if (tendered < cashAmt) return false;
                            if (this.splitNonCashMethod === 'TRANSFER' && !this.selectedBankName) return false;
                            return true;
                        } else {
                            if (this.isWalkInCustomer) return false;
                            if (this.paidAmount <= 0) return false;
                            if (this.paidAmount >= this.grandTotal) return false;
                            if (this.splitRemainingCredit > this.availableCredit) return false;
                            if (this.splitMethod === 'TRANSFER' && !this.selectedBankName) return false;
                            return true;
                        }
                    }
                    return true;
                },

                initApp() {
                    // Update live clock
                    setInterval(() => {
                        const now = new Date();
                        this.liveClock = now.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' + now.toLocaleTimeString('id-ID');
                    }, 1000);

                    // ALUR UTAMA: Default fokus langsung di kolom "Jumlah" (Opsi Jumlah Sebelum Scan)
                    this.$nextTick(() => {
                        this.focusQuantityInput();
                    });
                },

                focusQuantityInput() {
                    this.$nextTick(() => {
                        if (this.$refs.inputJumlah) {
                            this.$refs.inputJumlah.focus();
                            this.$refs.inputJumlah.select();
                        }
                    });
                },

                focusBarcodeInput() {
                    this.$nextTick(() => {
                        if (this.$refs.inputBarcode) {
                            this.$refs.inputBarcode.focus();
                            this.$refs.inputBarcode.select();
                        }
                    });
                },

                handleGlobalKeys(e) {
                    if (this.successModal) {
                        if (e.key === 'Enter' || e.key === ' ' || e.key === 'Escape') {
                            e.preventDefault();
                            this.finishAndPrintTransaction();
                            return;
                        }
                    }

                    // Shortcut Pending & Recall Transaksi (Ctrl+1 / Ctrl+2)
                    if ((e.ctrlKey || e.metaKey) && e.key === '1') {
                        e.preventDefault();
                        this.holdCurrentCart();
                        return;
                    }
                    if ((e.ctrlKey || e.metaKey) && e.key === '2') {
                        e.preventDefault();
                        this.openPendingModal();
                        return;
                    }

                    switch(e.key) {
                        case 'F1':
                            e.preventDefault();
                            this.focusBarcodeInput();
                            break;
                        case 'F2':
                            e.preventDefault();
                            this.triggerCashDrawer();
                            break;
                        case 'F3':
                            e.preventDefault();
                            this.cycleCustomer();
                            break;
                        case 'F4':
                            e.preventDefault();
                            this.focusVoucherInput();
                            break;
                        case 'F6':
                            e.preventDefault();
                            this.openTukarPoinModal();
                            break;
                        case 'F7':
                            e.preventDefault();
                            this.openPendingModal();
                            break;
                        case 'F8':
                            e.preventDefault();
                            this.openExchangeModal();
                            break;
                        case 'F9':
                            e.preventDefault();
                            this.openLookupModal();
                            break;
                        case 'F10':
                            e.preventDefault();
                            this.openReprintModal();
                            break;
                        case 'F12':
                            e.preventDefault();
                            if (this.cart.length > 0) {
                                this.openCheckoutModal();
                            }
                            break;
                        case 'Delete':
                            if (!this.isAnyModalOpen() && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
                                e.preventDefault();
                                this.removeSelectedCartRow();
                            }
                            break;
                        case 'Escape':
                            e.preventDefault();
                            this.closeAllModals();
                            this.clearSearch();
                            this.focusQuantityInput();
                            break;
                    }
                },

                isAnyModalOpen() {
                    return this.checkoutModal || this.openShiftModal || this.editItemModal ||
                           this.cashDropModal || this.blindCloseModal || this.reprintModal || this.cashDrawerModal || 
                           this.successModal || this.switchCashierModal || this.printerSettingModal || this.receiptSimulationModal || this.pendingModal || this.exchangeModal || this.tukarPoinModal || this.lookupModal;
                },

                closeAllModals() {
                    if (this.successModal) {
                        this.finishAndPrintTransaction();
                        return;
                    }
                    this.checkoutModal = false;
                    this.editItemModal = false;
                    this.cashDropModal = false;
                    this.blindCloseModal = false;
                    this.reprintModal = false;
                    this.cashDrawerModal = false;
                    this.switchCashierModal = false;
                    this.printerSettingModal = false;
                    this.receiptSimulationModal = false;
                    this.pendingModal = false;
                    this.exchangeModal = false;
                    this.tukarPoinModal = false;
                    this.lookupModal = false;
                    this.focusQuantityInput();
                },

                // Pencarian & Scan Barcode dengan Stok & Harga Jual Real-Time
                async onSearchInput() {
                    const q = this.searchQuery.trim();
                    if (q.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    try {
                        const tierParam = this.selectedTierId ? `&customer_tier_id=${encodeURIComponent(this.selectedTierId)}` : '';
                        const res = await fetch(`/pos/search?q=${encodeURIComponent(q)}${tierParam}`);
                        this.searchResults = await res.json();
                        this.selectedSearchIdx = 0;
                    } catch (e) {
                        console.error('Search error', e);
                    }
                },

                navigateDropdown(step) {
                    if (this.searchResults.length === 0) return;
                    this.selectedSearchIdx = (this.selectedSearchIdx + step + this.searchResults.length) % this.searchResults.length;
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.searchResults = [];
                },

                async handleBarcodeEnter() {
                    if (this.searchResults.length > 0) {
                        await this.selectProductFromSearch(this.searchResults[this.selectedSearchIdx]);
                        return;
                    }

                    const q = this.searchQuery.trim();
                    if (!q) {
                        this.focusQuantityInput();
                        return;
                    }

                    const tierParam = this.selectedTierId ? `&customer_tier_id=${encodeURIComponent(this.selectedTierId)}` : '';
                    const res = await fetch(`/pos/search?q=${encodeURIComponent(q)}${tierParam}`);
                    const data = await res.json();
                    if (data.length > 0) {
                        await this.selectProductFromSearch(data[0]);
                    } else {
                        alert('Produk barcode/nama "' + q + '" tidak ditemukan.');
                        this.focusBarcodeInput();
                    }
                },

                /**
                 * EKSEKUSI PENAMBAHAN ITEM DENGAN OPSI JUMLAH SEBELUM SCAN
                 */
                async selectProductFromSearch(prod) {
                    this.clearSearch();

                    // Ambil jumlah yang sudah diketik kasir di kolom "Jumlah" (atau ambil otomatis dari barcode timbangan elektronik)
                    const qtyToAdd = prod.scale_weight ? parseFloat(prod.scale_weight) : Math.max(1, parseFloat(this.inputScanQty) || 1);

                    // Cek jika sudah ada di keranjang dengan satuan yang sama
                    const existingItem = this.cart.find(i => i.product_id === prod.id && i.product_unit_id === prod.matched_unit_id);
                    if (existingItem) {
                        existingItem.quantity += qtyToAdd;
                        await this.recalculateItemPrice(existingItem);
                        this.selectedCartIdx = this.cart.indexOf(existingItem);
                    } else {
                        const newItem = {
                            product_id: prod.id,
                            product_name: prod.name,
                            sku: prod.sku,
                            barcode: prod.barcode,
                            product_unit_id: prod.matched_unit_id,
                            available_units: prod.units,
                            available_stock: prod.stock,
                            stock_formatted: prod.stock_formatted,
                            unit_code: prod.matched_unit_code,
                            quantity: qtyToAdd,
                            discount_amount: 0,
                            unit_price: 0,
                            subtotal: 0,
                        };
                        await this.recalculateItemPrice(newItem);
                        this.cart.push(newItem);
                        this.selectedCartIdx = this.cart.length - 1;
                    }

                    // Reset kolom Jumlah kembali ke 1,00
                    this.inputScanQty = 1;

                    // Kembalikan fokus ke kolom Jumlah
                    this.focusQuantityInput();
                },

                async recalculateItemPrice(item) {
                    try {
                        const res = await fetch('/pos/calculate-price', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                product_id: item.product_id,
                                product_unit_id: item.product_unit_id,
                                customer_tier_id: this.selectedTierId,
                                quantity: item.quantity,
                            }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            item.unit_price = data.unit_price;
                            const gross = (data.unit_price || 0) * (item.quantity || 1);
                            item.discount_amount = item.discount_amount || 0;
                            item.subtotal = Math.max(0, gross - item.discount_amount);
                        } else {
                            alert(data.message);
                        }
                    } catch (e) {
                        console.error('Price recalculation failed', e);
                    }
                },

                onItemDiscountChange(item) {
                    if (!item.discount_amount || item.discount_amount < 0) item.discount_amount = 0;
                    const gross = (item.unit_price || 0) * (item.quantity || 1);
                    if (item.discount_amount > gross) item.discount_amount = gross;
                    item.subtotal = Math.max(0, gross - item.discount_amount);
                },

                openEditItemModal(item) {
                    this.selectedItemForEdit = item;
                    this.editItemModal = true;
                },

                removeCartRow(idx) {
                    this.cart.splice(idx, 1);
                    if (this.selectedCartIdx >= this.cart.length) {
                        this.selectedCartIdx = Math.max(0, this.cart.length - 1);
                    }
                    this.focusQuantityInput();
                },

                async onItemUnitChange(item) {
                    await this.recalculateItemPrice(item);
                },

                async onItemQtyChange(item) {
                    if (item.quantity < 1) item.quantity = 1;
                    await this.recalculateItemPrice(item);
                },

                /**
                 * FITUR TAHAN & BUKA TRANSAKSI (HOLD & RECALL CARTS)
                 */
                holdCurrentCart() {
                    if (this.cart.length === 0) {
                        alert('Keranjang belanja masih kosong, tidak ada transaksi yang dapat ditahan.');
                        return;
                    }

                    const held = {
                        id: 'HOLD_' + Date.now(),
                        timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                        date: new Date().toLocaleDateString('id-ID'),
                        customer_name: this.selectedCustomer ? this.selectedCustomer.name : 'Pelanggan Umum',
                        customer_id: this.selectedCustomerId,
                        tier_id: this.selectedTierId,
                        sales_id: this.selectedSalesId,
                        tax_mode: this.erpTaxMode,
                        idempotency_key: this.cartIdempotencyKey || this.generateUuid(),
                        cart: JSON.parse(JSON.stringify(this.cart)),
                        total_items: this.cart.reduce((a, c) => a + (parseFloat(c.quantity) || 0), 0),
                        subtotal: this.subtotal,
                        notes: this.transactionNotes || '',
                    };

                    this.heldCarts.push(held);
                    localStorage.setItem('sigma_held_carts', JSON.stringify(this.heldCarts));

                    // Reset keranjang aktif
                    this.cart = [];
                    this.cartIdempotencyKey = this.generateUuid();
                    this.voucherCode = null;
                    this.voucherDiscount = 0;
                    this.voucherCodeInput = '';
                    this.transactionNotes = '';
                    this.focusQuantityInput();

                    alert('Alhamdulillah, transaksi [' + held.customer_name + '] berhasil ditahan (Pending). Silakan melayani pelanggan berikutnya.');
                },

                openPendingModal() {
                    this.pendingModal = true;
                },

                recallTransaction(idx) {
                    if (this.cart.length > 0) {
                        if (!confirm('Keranjang saat ini berisi ' + this.cart.length + ' item. Tahan transaksi aktif ini terlebih dahulu sebelum membuka transaksi lain?')) {
                            return;
                        }
                        this.holdCurrentCart();
                    }

                    const item = this.heldCarts[idx];
                    if (!item) return;

                    this.cart = item.cart;
                    this.cartIdempotencyKey = item.idempotency_key || this.generateUuid();
                    this.selectedCustomerId = item.customer_id;
                    this.selectedTierId = item.tier_id;
                    this.selectedSalesId = item.sales_id;
                    this.selectedTaxMode = this.erpTaxMode;
                    this.transactionNotes = item.notes;

                    this.heldCarts.splice(idx, 1);
                    localStorage.setItem('sigma_held_carts', JSON.stringify(this.heldCarts));
                    this.pendingModal = false;
                    this.focusBarcodeInput();
                },

                deleteHeldTransaction(idx) {
                    if (confirm('Hapus transaksi yang ditahan ini?')) {
                        this.heldCarts.splice(idx, 1);
                        localStorage.setItem('sigma_held_carts', JSON.stringify(this.heldCarts));
                    }
                },

                // Fitur [F8] Penukaran Barang / Retur Struk Kasir
                openExchangeModal() {
                    this.exchangeModal = true;
                    this.exchangeInvoiceInput = '';
                    this.exchangeTx = null;
                    this.exchangeManagerPin = '';
                    this.$nextTick(() => {
                        const inp = document.getElementById('exchange-invoice-input');
                        if (inp) inp.focus();
                    });
                },

                async searchExchangeInvoice() {
                    const inv = this.exchangeInvoiceInput.trim();
                    if (!inv) return;
                    this.exchangeLoading = true;
                    try {
                        const res = await fetch(`/pos/lookup-invoice?invoice_number=${encodeURIComponent(inv)}`);
                        const data = await res.json();
                        if (data.success) {
                            this.exchangeTx = data.transaction;
                            this.exchangeTx.items.forEach(it => {
                                it.selected = false;
                                it.return_qty = it.max_returnable > 0 ? 1 : 0;
                                it.condition = 'GOOD';
                            });
                        } else {
                            alert(data.message || 'Faktur tidak ditemukan.');
                            this.exchangeTx = null;
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan saat mencari faktur.');
                    } finally {
                        this.exchangeLoading = false;
                    }
                },

                async submitPosReturn() {
                    if (!this.exchangeTx) return;
                    const selectedItems = this.exchangeTx.items.filter(it => it.selected && it.return_qty > 0);
                    if (selectedItems.length === 0) {
                        alert('Pilih setidaknya satu item yang ingin ditukar / diretur.');
                        return;
                    }

                    if (!confirm('Proses penukaran/retur untuk ' + selectedItems.length + ' item ini?')) return;

                    this.exchangeSubmitting = true;
                    try {
                        const payload = {
                            refund_method: this.exchangeRefundMethod,
                            reason: this.exchangeReturnReason || 'Tukar Barang Kasir',
                            manager_pin: this.exchangeManagerPin || null,
                            shift_id: '{{ $activeShift?->id }}',
                            items: selectedItems.map(it => ({
                                pos_transaction_item_id: it.id,
                                quantity: parseFloat(it.return_qty),
                                condition: it.condition || 'GOOD'
                            }))
                        };

                        const res = await fetch(`/pos/transactions/${this.exchangeTx.id}/return`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        if (data.success) {
                            alert('Alhamdulillah, ' + data.message);
                            if (data.return?.id) {
                                window.open('/sales/returns/' + data.return.id + '/print', '_blank');
                            }
                            this.exchangeModal = false;
                            this.focusBarcodeInput();
                        } else {
                            alert('Gagal memproses retur: ' + (data.message || 'Terjadi kesalahan.'));
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan teknis saat memproses retur.');
                    } finally {
                        this.exchangeSubmitting = false;
                    }
                },

                // Fitur [F9] Cek Harga & Ketersediaan Stok Real-Time (Tanpa Ganggu Keranjang)
                openLookupModal() {
                    this.lookupModal = true;
                    this.lookupQuery = '';
                    this.lookupResult = null;
                    this.lookupError = '';
                    this.$nextTick(() => {
                        if (this.$refs.lookupInput) {
                            this.$refs.lookupInput.focus();
                            this.$refs.lookupInput.select();
                        }
                    });
                },

                async executeLookup() {
                    const q = (this.lookupQuery || '').trim();
                    if (!q) {
                        this.lookupError = 'Silakan ketik nama barang atau scan barcode terlebih dahulu.';
                        return;
                    }
                    this.lookupLoading = true;
                    this.lookupError = '';
                    this.lookupResult = null;
                    try {
                        const response = await fetch(`{{ route('pos.lookup-item') }}?q=${encodeURIComponent(q)}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.lookupResult = data.product;
                            this.$nextTick(() => {
                                if (this.$refs.lookupInput) {
                                    this.$refs.lookupInput.select();
                                }
                            });
                        } else {
                            this.lookupError = data.message || 'Barang tidak ditemukan dalam sistem katalog.';
                        }
                    } catch (err) {
                        console.error('Error lookup item:', err);
                        this.lookupError = 'Gagal menghubungi server untuk cek harga & stok.';
                    } finally {
                        this.lookupLoading = false;
                    }
                },

                async addLookupToCart(unit = null) {
                    if (!this.lookupResult) return;
                    if (this.lookupResult.stock_available <= 0) {
                        alert('Perhatian: Sisa stok barang ini di toko kosong / 0 (Zero Negative Stock Policy).');
                        return;
                    }
                    const prodObj = {
                        id: this.lookupResult.id,
                        name: this.lookupResult.name,
                        sku: this.lookupResult.sku,
                        barcode: this.lookupResult.barcode,
                        matched_unit_id: unit ? unit.id : (this.lookupResult.units?.[0]?.id || null),
                        matched_unit_code: unit ? unit.code : (this.lookupResult.units?.[0]?.code || 'PCS'),
                        units: this.lookupResult.units,
                        stock: this.lookupResult.stock_available,
                        stock_formatted: `${this.lookupResult.stock_available} ${unit ? unit.code : 'PCS'}`
                    };
                    await this.selectProductFromSearch(prodObj);
                    this.lookupModal = false;
                },

                navigateCartRow(step) {
                    if (this.cart.length === 0) return;
                    this.selectedCartIdx = Math.max(0, Math.min(this.cart.length - 1, this.selectedCartIdx + step));
                },

                removeSelectedCartRow() {
                    if (this.cart.length === 0) return;
                    this.cart.splice(this.selectedCartIdx, 1);
                    if (this.selectedCartIdx >= this.cart.length) {
                        this.selectedCartIdx = Math.max(0, this.cart.length - 1);
                    }
                    this.focusQuantityInput();
                },

                resetCart() {
                    if (this.cart.length > 0 && confirm('Kosongkan keranjang belanja kasir?')) {
                        this.cart = [];
                        this.cartIdempotencyKey = this.generateUuid();
                        this.voucherCode = null;
                        this.voucherDiscount = 0;
                        this.voucherCodeInput = '';
                        this.focusQuantityInput();
                    }
                },

                async onCustomerChange() {
                    this.resetRedeemPoints();
                    const cust = this.selectedCustomer;
                    if (cust && cust.customer_tier_id) {
                        this.selectedTierId = cust.customer_tier_id;
                    }
                    await this.sync3DPricingForCart();
                },

                openTukarPoinModal() {
                    this.tukarPoinModal = true;
                    if (this.redeemPoints > 0) {
                        this.redeemPointsInput = this.redeemPoints;
                    }
                },

                async applyPointsRedemption() {
                    const pts = parseInt(this.redeemPointsInput) || 0;
                    if (pts <= 0) {
                        this.resetRedeemPoints();
                        return;
                    }
                    if (!this.selectedCustomer) {
                        alert('Pilih pelanggan terlebih dahulu untuk menukarkan poin.');
                        return;
                    }
                    const maxCustPts = parseInt(this.selectedCustomer.loyalty_points) || 0;
                    if (pts > maxCustPts) {
                        alert('Poin yang ingin ditukarkan (' + pts + ') melebihi saldo poin pelanggan (' + maxCustPts + ').');
                        this.redeemPointsInput = maxCustPts;
                        return;
                    }
                    if (pts < this.minPointsToRedeem) {
                        alert('Minimal penukaran poin adalah ' + this.minPointsToRedeem + ' poin.');
                        return;
                    }
                    const maxDiscount = Math.max(0, this.subtotal - this.voucherDiscount);
                    const calculatedDiscount = Math.min(maxDiscount, pts * this.pointRate);
                    this.redeemPoints = pts;
                    this.pointsDiscount = calculatedDiscount;
                    if (this.paymentMethod === 'CASH') {
                        this.paidAmount = this.grandTotal;
                    }
                },

                redeemAllCustomerPoints() {
                    if (!this.selectedCustomer) {
                        alert('Pilih pelanggan terlebih dahulu.');
                        return;
                    }
                    const maxCustPts = parseInt(this.selectedCustomer.loyalty_points) || 0;
                    if (maxCustPts <= 0) {
                        alert('Pelanggan tidak memiliki saldo poin.');
                        return;
                    }
                    const maxDiscount = Math.max(0, this.subtotal - this.voucherDiscount);
                    const neededPts = Math.ceil(maxDiscount / this.pointRate);
                    const ptsToUse = Math.min(maxCustPts, neededPts);
                    this.redeemPointsInput = ptsToUse;
                    this.applyPointsRedemption();
                },

                resetRedeemPoints() {
                    this.redeemPointsInput = '';
                    this.redeemPoints = 0;
                    this.pointsDiscount = 0;
                    if (this.paymentMethod === 'CASH') {
                        this.paidAmount = this.grandTotal;
                    }
                },

                onPointsInputChange() {
                    if (!this.redeemPointsInput || parseInt(this.redeemPointsInput) <= 0) {
                        this.resetRedeemPoints();
                    }
                },

                cycleCustomer() {
                    const select = this.$refs.customerSelect;
                    if (select && this.customers.length > 0) {
                        select.selectedIndex = (select.selectedIndex + 1) % select.options.length;
                        this.selectedCustomerId = select.value;
                        this.onCustomerChange();
                    }
                },

                cycleSales() {
                    const select = this.$refs.salesSelect;
                    if (select && this.salesList.length > 0) {
                        select.selectedIndex = (select.selectedIndex + 1) % select.options.length;
                        this.selectedSalesId = select.value;
                    }
                },

                onTaxModeChange() {
                    if (this.paymentMethod !== 'CASH' || this.paidAmount === 0) {
                        this.paidAmount = this.grandTotal;
                    }
                },

                triggerCashDrawer() {
                    this.playCashDrawerSound();
                    this.cashDrawerModal = true;
                },

                playCashDrawerSound() {
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(800, audioCtx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.08);
                        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.start();
                        osc.stop(audioCtx.currentTime + 0.2);
                    } catch (e) {
                        // Audio context fallback
                    }
                },

                async sync3DPricingForCart() {
                    if (this.cart.length === 0) return;

                    try {
                        const payload = {
                            customer_tier_id: this.selectedTierId,
                            items: this.cart.map(item => ({
                                product_id: item.product_id,
                                product_unit_id: item.product_unit_id,
                                quantity: item.quantity,
                                discount_amount: item.discount_amount || 0,
                            })),
                        };

                        const res = await fetch('/pos/recalculate-cart', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();
                        if (data.success && data.items) {
                            data.items.forEach(rec => {
                                if (this.cart[rec.index]) {
                                    this.cart[rec.index].unit_price = rec.unit_price;
                                    this.cart[rec.index].subtotal = rec.subtotal;
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Batch price sync failed', e);
                        for (const item of this.cart) {
                            await this.recalculateItemPrice(item);
                        }
                    }
                },

                openEditModal() {
                    if (this.cart.length === 0) return;
                    this.selectedItemForEdit = this.cart[this.selectedCartIdx];
                    this.editItemModal = true;
                },

                focusVoucherInput() {
                    this.$nextTick(() => {
                        if (this.$refs.voucherInput) {
                            this.$refs.voucherInput.focus();
                            this.$refs.voucherInput.select();
                        }
                    });
                },

                async applyVoucherCode() {
                    const code = (this.voucherCodeInput || '').trim().toUpperCase();
                    if (!code) {
                        alert('Masukkan kode voucher terlebih dahulu.');
                        return;
                    }

                    try {
                        const res = await fetch('/pos/validate-voucher', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                code: code,
                                subtotal: this.subtotal,
                            }),
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.voucherCode = data.voucher.code;
                            this.voucherDiscount = parseFloat(data.voucher.discount_amount);
                            alert('✅ ' + data.message);
                        } else {
                            alert('⚠️ ' + data.message);
                            this.voucherCode = null;
                            this.voucherDiscount = 0;
                        }
                    } catch (e) {
                        console.error('Validate voucher failed', e);
                        alert('Gagal memvalidasi voucher.');
                    }
                },

                removeVoucher() {
                    this.voucherCode = null;
                    this.voucherDiscount = 0;
                    this.voucherCodeInput = '';
                },

                onPaidAmountInput(event) {
                    let raw = event.target.value.replace(/\D/g, '');
                    let num = parseInt(raw, 10) || 0;
                    this.paidAmount = num;
                    this.paidAmountFormatted = num > 0 ? num.toLocaleString('id-ID') : '';
                },

                setPaidAmount(amount) {
                    this.paidAmount = Math.max(0, Math.round(amount));
                    this.paidAmountFormatted = this.paidAmount > 0 ? this.paidAmount.toLocaleString('id-ID') : '0';
                    this.$nextTick(() => {
                        this.$refs.paidAmountInput?.focus();
                        this.$refs.paidAmountInput?.select();
                    });
                },

                selectBank(bank) {
                    this.selectedBankCode = bank.code;
                    this.selectedBankName = bank.bank_name;
                    this.selectedBankAccountNumber = bank.account_number;
                },

                initSplitAmounts() {
                    const half = Math.round(this.grandTotal * 0.5);
                    this.splitCashAmount = half;
                    this.splitCashAmountFormatted = half.toLocaleString('id-ID');
                    this.splitCashTendered = half;
                    this.splitCashTenderedFormatted = half.toLocaleString('id-ID');
                    this.setPaidAmount(half);
                },

                onSplitCashInput(event) {
                    const raw = event.target.value.replace(/\D/g, '');
                    const num = parseInt(raw) || 0;
                    this.splitCashAmount = Math.min(this.grandTotal, num);
                    this.splitCashAmountFormatted = this.splitCashAmount > 0 ? this.splitCashAmount.toLocaleString('id-ID') : '';
                    if (this.splitCashTendered < this.splitCashAmount) {
                        this.splitCashTendered = this.splitCashAmount;
                        this.splitCashTenderedFormatted = this.splitCashAmountFormatted;
                    }
                },

                setSplitCash(val) {
                    this.splitCashAmount = Math.min(this.grandTotal, Math.round(val));
                    this.splitCashAmountFormatted = this.splitCashAmount > 0 ? this.splitCashAmount.toLocaleString('id-ID') : '0';
                    this.splitCashTendered = this.splitCashAmount;
                    this.splitCashTenderedFormatted = this.splitCashAmountFormatted;
                },

                onSplitCashTenderedInput(event) {
                    const raw = event.target.value.replace(/\D/g, '');
                    const num = parseInt(raw) || 0;
                    this.splitCashTendered = num;
                    this.splitCashTenderedFormatted = num > 0 ? num.toLocaleString('id-ID') : '';
                },

                async saveRoundingSettingsRemote() {
                    try {
                        await fetch('/pos/settings/rounding', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                rounding_enabled: this.roundingEnabled ? 1 : 0,
                                rounding_step: parseInt(this.roundingStep) || 100,
                                rounding_mode: this.roundingMode,
                                rounding_applies_to: this.roundingAppliesTo,
                            }),
                        });
                    } catch (e) {
                        console.error('Gagal update pembulatan kasir:', e);
                    }
                },

                openCheckoutModal() {
                    if (!this.activeShiftId) {
                        alert('Perhatian: Shift kasir belum aktif! Harap buka shift kasir terlebih dahulu dengan menginput modal awal.');
                        this.openShiftModal = true;
                        return;
                    }
                    if (!this.cart || this.cart.length === 0) {
                        alert('Keranjang belanja kasir masih kosong.');
                        this.focusQuantityInput();
                        return;
                    }
                    this.checkoutModal = true;
                    if (this.paymentMethod === 'SPLIT') {
                        this.initSplitAmounts();
                    } else {
                        this.paidAmount = this.grandTotal;
                        this.paidAmountFormatted = this.grandTotal > 0 ? Math.round(this.grandTotal).toLocaleString('id-ID') : '0';
                    }
                    if (!this.selectedBankName && this.bankAccounts && this.bankAccounts.length > 0) {
                        this.selectBank(this.bankAccounts[0]);
                    }
                    this.$nextTick(() => {
                        if (this.paymentMethod === 'CASH') {
                            this.$refs.paidAmountInput?.focus();
                            this.$refs.paidAmountInput?.select();
                        } else if (this.paymentMethod === 'SPLIT') {
                            if (this.splitType === 'CASH_NON_CASH') {
                                this.$refs.splitCashInput?.focus();
                                this.$refs.splitCashInput?.select();
                            } else {
                                this.$refs.splitPaidAmountInput?.focus();
                                this.$refs.splitPaidAmountInput?.select();
                            }
                        }
                    });
                },

                onPaymentMethodChange() {
                    if (this.paymentMethod === 'SPLIT') {
                        this.initSplitAmounts();
                        this.$nextTick(() => {
                            if (this.splitType === 'CASH_NON_CASH') {
                                this.$refs.splitCashInput?.focus();
                                this.$refs.splitCashInput?.select();
                            } else {
                                this.$refs.splitPaidAmountInput?.focus();
                                this.$refs.splitPaidAmountInput?.select();
                            }
                        });
                    } else if (this.paymentMethod !== 'CASH') {
                        this.paidAmount = this.grandTotal;
                        this.paidAmountFormatted = this.grandTotal > 0 ? Math.round(this.grandTotal).toLocaleString('id-ID') : '0';
                    } else {
                        this.$nextTick(() => {
                            this.$refs.paidAmountInput?.focus();
                            this.$refs.paidAmountInput?.select();
                        });
                    }
                },

                async submitCheckout() {
                    if (!this.activeShiftId) {
                        alert('Perhatian: Shift kasir belum aktif! Harap buka shift kasir terlebih dahulu.');
                        this.checkoutModal = false;
                        this.openShiftModal = true;
                        return;
                    }

                    if (!this.cart || this.cart.length === 0) {
                        alert('Keranjang belanja kasir masih kosong.');
                        return;
                    }

                    if (this.paymentMethod === 'CASH' && this.paidAmount < this.grandTotal) {
                        alert('Uang pembayaran tunai kurang dari total belanja.');
                        return;
                    }

                    if (this.paymentMethod === 'TEMPO') {
                        if (this.isWalkInCustomer) {
                            alert('Pelanggan UMUM tidak memiliki fasilitas kredit/tempo. Harap pilih pelanggan grosir terdaftar.');
                            return;
                        }
                        if (this.grandTotal > this.availableCredit) {
                            alert('Total transaksi (Rp ' + this.formatRupiah(this.grandTotal) + ') melebihi sisa plafon kredit pelanggan (Rp ' + this.formatRupiah(this.availableCredit) + ')!');
                            return;
                        }
                    }

                    if (this.paymentMethod === 'SPLIT') {
                        if (this.splitType === 'CASH_NON_CASH') {
                            const cashAmt = parseFloat(this.splitCashAmount) || 0;
                            const tendered = parseFloat(this.splitCashTendered) || 0;
                            if (cashAmt <= 0) {
                                alert('Porsi nominal tunai harus lebih dari Rp 0.');
                                return;
                            }
                            if (cashAmt >= this.grandTotal) {
                                alert('Porsi nominal tunai tidak boleh melebihi atau sama dengan total belanja (Gunakan metode Tunai biasa).');
                                return;
                            }
                            if (tendered < cashAmt) {
                                alert('Uang tunai diterima (Rp ' + this.formatRupiah(tendered) + ') kurang dari porsi tunai yang harus dibayar (Rp ' + this.formatRupiah(cashAmt) + ').');
                                return;
                            }
                            if (this.splitNonCashMethod === 'TRANSFER' && !this.selectedBankName) {
                                alert('Harap pilih salah satu rekening Bank tujuan transfer toko.');
                                return;
                            }
                        } else {
                            if (this.isWalkInCustomer) {
                                alert('Pelanggan UMUM tidak memiliki fasilitas kredit/tempo. Harap pilih pelanggan grosir terdaftar atau gunakan tab "Tunai + Non-Tunai (Lunas)".');
                                return;
                            }
                            if (this.paidAmount <= 0) {
                                alert('Nominal yang dibayar sekarang harus lebih dari 0.');
                                return;
                            }
                            if (this.paidAmount >= this.grandTotal) {
                                alert('Nominal bayar sebagian tidak boleh melebihi atau sama dengan total belanja (Gunakan metode Tunai/Transfer).');
                                return;
                            }
                            if (this.splitRemainingCredit > this.availableCredit) {
                                alert('Sisa tagihan (Rp ' + this.formatRupiah(this.splitRemainingCredit) + ') melebihi sisa plafon kredit pelanggan (Rp ' + this.formatRupiah(this.availableCredit) + ')!');
                                return;
                            }
                            if (this.splitMethod === 'TRANSFER' && !this.selectedBankName) {
                                alert('Harap pilih salah satu rekening Bank tujuan transfer toko.');
                                return;
                            }
                        }
                    }

                    if (this.paymentMethod === 'TRANSFER' && !this.selectedBankName) {
                        alert('Harap pilih salah satu rekening Bank tujuan transfer toko.');
                        return;
                    }

                    try {
                        const isTransfer = this.paymentMethod === 'TRANSFER' ||
                            (this.paymentMethod === 'SPLIT' && ((this.splitType === 'CASH_NON_CASH' && this.splitNonCashMethod === 'TRANSFER') || (this.splitType === 'TEMPO' && this.splitMethod === 'TRANSFER')));

                        let splitCashVal = null;
                        let splitNonCashVal = null;
                        let splitNonCashMeth = null;
                        let paidVal = this.paidAmount;
                        let creditVal = 0;

                        if (this.paymentMethod === 'SPLIT') {
                            if (this.splitType === 'CASH_NON_CASH') {
                                splitCashVal = parseFloat(this.splitCashAmount) || 0;
                                splitNonCashVal = Math.max(0, this.grandTotal - splitCashVal);
                                splitNonCashMeth = this.splitNonCashMethod || 'QRIS';
                                paidVal = parseFloat(this.splitCashTendered) || splitCashVal;
                                creditVal = 0;
                            } else {
                                splitCashVal = this.splitMethod === 'CASH' ? this.paidAmount : 0;
                                splitNonCashVal = this.splitMethod !== 'CASH' ? this.paidAmount : 0;
                                splitNonCashMeth = this.splitMethod !== 'CASH' ? this.splitMethod : null;
                                paidVal = this.paidAmount;
                                creditVal = this.splitRemainingCredit;
                            }
                        } else if (this.paymentMethod === 'TEMPO') {
                            creditVal = this.grandTotal;
                        }

                        const normalizedTax = (this.selectedTaxMode === 'INCLUSIVE' || this.selectedTaxMode === 'PPN_INCLUDE')
                            ? 'PPN_INCLUDE'
                            : ((this.selectedTaxMode === 'EXCLUSIVE' || this.selectedTaxMode === 'PPN_EXCLUDE') ? 'PPN_EXCLUDE' : 'NON');

                        if (!this.cartIdempotencyKey) {
                            this.cartIdempotencyKey = this.generateUuid();
                        }

                        const payload = {
                            shift_id: this.activeShiftId || null,
                            idempotency_key: this.cartIdempotencyKey,
                            customer_tier_id: this.selectedTierId || null,
                            customer_id: this.selectedCustomerId || null,
                            sales_person_id: this.selectedSalesId || null,
                            tax_amount: this.taxAmount || 0,
                            tax_mode: normalizedTax,
                            bank_name: isTransfer ? this.selectedBankName : null,
                            bank_account_number: isTransfer ? this.selectedBankAccountNumber : null,
                            payment_reference: isTransfer ? this.paymentReference : null,
                            items: this.cart.map(i => ({
                                product_id: i.product_id,
                                product_unit_id: i.product_unit_id,
                                quantity: i.quantity,
                                discount_amount: i.discount_amount || 0,
                                is_tebus_murah: i.is_tebus_murah || false,
                                promotion_id: i.promotion_id || null,
                            })),
                            payment_method: this.paymentMethod,
                            paid_amount: paidVal,
                            split_payment_method: this.paymentMethod === 'SPLIT' ? (this.splitType === 'CASH_NON_CASH' ? splitNonCashMeth : this.splitMethod) : null,
                            split_cash_amount: splitCashVal,
                            split_non_cash_amount: splitNonCashVal,
                            split_non_cash_method: splitNonCashMeth,
                            rounding_amount: this.roundingAmount || 0,
                            credit_amount: creditVal || 0,
                            voucher_code: this.voucherCode || null,
                            redeem_points: this.redeemPoints || 0,
                            notes: this.transactionNotes || null,
                            manager_pin: this.managerPin || null,
                        };

                        const res = await fetch('/pos/checkout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });

                        let data;
                        try {
                            data = await res.json();
                        } catch (parseErr) {
                            throw new Error('Respon server tidak berformat JSON (Status HTTP ' + res.status + ').');
                        }

                        if (res.ok && data.success) {
                            this.checkoutModal = false;
                            this.lastTransaction = {
                                id: data.transaction_id,
                                invoice_number: data.invoice_number,
                                grand_total: data.grand_total,
                                rounding_amount: data.rounding_amount,
                                change_amount: data.change_amount,
                                payment_method: this.paymentMethod,
                                split_payment_method: data.split_payment_method,
                                split_cash_amount: data.split_cash_amount,
                                split_non_cash_amount: data.split_non_cash_amount,
                                split_non_cash_method: data.split_non_cash_method,
                                paid_amount: data.paid_amount,
                                credit_amount: data.credit_amount,
                            };
                            if (this.selectedCustomer) {
                                if (this.redeemPoints > 0) {
                                    this.selectedCustomer.loyalty_points = Math.max(0, (parseInt(this.selectedCustomer.loyalty_points) || 0) - this.redeemPoints);
                                }
                                if (data.points_earned > 0) {
                                    this.selectedCustomer.loyalty_points = (parseInt(this.selectedCustomer.loyalty_points) || 0) + data.points_earned;
                                }
                                if (this.paymentMethod === 'TEMPO' || (this.paymentMethod === 'SPLIT' && this.splitType === 'TEMPO')) {
                                    const creditAdded = this.paymentMethod === 'SPLIT' ? this.splitRemainingCredit : this.grandTotal;
                                    this.selectedCustomer.current_credit_balance = (parseFloat(this.selectedCustomer.current_credit_balance || 0) + creditAdded).toString();
                                }
                            }
                            this.resetRedeemPoints();
                            this.startSuccessAutoPrint();
                        } else {
                            if (res.status === 401) {
                                alert('Sesi login telah berakhir. Harap login kembali.');
                                window.location.href = '/login';
                                return;
                            }
                            if (res.status === 419) {
                                alert('Sesi token kasir telah kedaluwarsa. Halaman akan dimuat ulang otomatis.');
                                window.location.reload();
                                return;
                            }
                            const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : ('Gagal memproses checkout (Status HTTP ' + res.status + ').'));
                            alert('Gagal Checkout:\n' + errMsg);
                        }
                    } catch (e) {
                        alert('Terjadi kesalahan saat memproses checkout:\n' + e.message);
                    }
                },

                playSuccessSound() {
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const notes = [523.25, 659.25, 783.99]; // C5, E5, G5
                        notes.forEach((freq, i) => {
                            const osc = audioCtx.createOscillator();
                            const gain = audioCtx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, audioCtx.currentTime + i * 0.08);
                            gain.gain.setValueAtTime(0.12, audioCtx.currentTime + i * 0.08);
                            gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + i * 0.08 + 0.3);
                            osc.connect(gain);
                            gain.connect(audioCtx.destination);
                            osc.start(audioCtx.currentTime + i * 0.08);
                            osc.stop(audioCtx.currentTime + i * 0.08 + 0.35);
                        });
                    } catch (e) {
                        // Browser audio policy fallback
                    }
                },

                startSuccessAutoPrint() {
                    this.playSuccessSound();
                    this.successModal = true;
                    this.autoPrintProgress = 100;

                    const totalDuration = 2500;
                    const intervalTime = 50;
                    const step = (intervalTime / totalDuration) * 100;

                    if (this.autoPrintInterval) clearInterval(this.autoPrintInterval);
                    if (this.autoPrintTimer) clearTimeout(this.autoPrintTimer);

                    this.autoPrintInterval = setInterval(() => {
                        this.autoPrintProgress = Math.max(0, this.autoPrintProgress - step);
                    }, intervalTime);

                    this.autoPrintTimer = setTimeout(() => {
                        this.finishAndPrintTransaction();
                    }, totalDuration);

                    this.$nextTick(() => {
                        this.$refs.btnSuccessOk?.focus();
                    });
                },

                finishAndPrintTransaction() {
                    if (this.autoPrintInterval) {
                        clearInterval(this.autoPrintInterval);
                        this.autoPrintInterval = null;
                    }
                    if (this.autoPrintTimer) {
                        clearTimeout(this.autoPrintTimer);
                        this.autoPrintTimer = null;
                    }

                    if (this.lastTransaction && this.lastTransaction.id) {
                        // 1. Trigger Buka Laci Kasir Otomatis (Drawer Kick) jika ada pembayaran tunai
                        const isCashTransaction = this.lastTransaction.payment_method === 'CASH' || 
                                                  (parseFloat(this.lastTransaction.split_cash_amount) || 0) > 0;
                        if (this.printerAutoDrawer && isCashTransaction) {
                            this.playCashDrawerSound();
                            console.log('⚡ [SIGMA POS] Cash drawer kick triggered (ESC/POS: ESC p 0 25 250)');
                        }

                        // 2. Buka dialog cetak struk otomatis jika autoprint aktif
                        if (this.printerAutoPrint) {
                            const drawerParam = (this.printerAutoDrawer && isCashTransaction) ? '&drawer=1' : '';
                            window.open('/pos/receipt/' + this.lastTransaction.id + '?paper=' + this.printerPaperSize + '&autoprint=1' + drawerParam, '_blank', 'width=420,height=650');
                        }
                    }

                    this.cart = [];
                    this.cartIdempotencyKey = this.generateUuid();
                    this.voucherCode = null;
                    this.voucherDiscount = 0;
                    this.voucherCodeInput = '';
                    this.transactionNotes = '';
                    this.paymentReference = '';
                    this.selectedTaxMode = this.erpTaxMode;
                    this.taxRate = this.erpTaxRate;
                    this.successModal = false;
                    this.lastTransaction = null;
                    this.resetRedeemPoints();

                    this.focusQuantityInput();
                },

                async submitOpenShift() {
                    try {
                        const res = await fetch('/pos/open-shift', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({ starting_cash: this.startingCash }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.ok && data.success) {
                            window.location.reload();
                        } else {
                            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Gagal membuka shift.');
                            alert('Gagal Buka Shift:\n' + msg);
                        }
                    } catch (e) {
                        alert('Gagal membuka shift:\n' + e.message);
                    }
                },

                openCashDropModal() {
                    this.cashDropModal = true;
                },

                async submitCashDrop() {
                    try {
                        const res = await fetch('/pos/cash-drop', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                shift_id: this.activeShiftId,
                                amount: this.cashDropAmount,
                                reason: this.cashDropReason,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.ok && data.success) {
                            this.cashDropModal = false;
                            alert('Cash drop Rp ' + this.formatRupiah(this.cashDropAmount) + ' berhasil dicatat.');
                        } else {
                            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Gagal mencatat cash drop.');
                            alert('Gagal Cash Drop:\n' + msg);
                        }
                    } catch (e) {
                        alert('Gagal mencatat cash drop:\n' + e.message);
                    }
                },

                openBlindCloseModal() {
                    this.blindCloseModal = true;
                },

                async submitBlindClose() {
                    try {
                        const res = await fetch('/pos/blind-close', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                shift_id: this.activeShiftId,
                                actual_cash_counted: this.actualCashCounted,
                                notes: this.closingNotes,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.ok && data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Gagal menutup shift.');
                            alert('Gagal Tutup Shift:\n' + msg);
                        }
                    } catch (e) {
                        alert('Gagal menutup shift:\n' + e.message);
                    }
                },

                async openReprintModal() {
                    this.reprintModal = true;
                    try {
                        const res = await fetch('/pos/recent-transactions', {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        this.recentTransactions = data;
                        if (data.length > 0 && !this.selectedReprintTx) {
                            this.selectTransactionForReprint(data[0]);
                        }
                    } catch (e) {
                        console.error('Failed to load recent transactions', e);
                    }
                },

                selectTransactionForReprint(t) {
                    this.selectedReprintTx = t;
                    this.isPastShiftReprint = Boolean(t.cashier_shift_id && this.activeShiftId && t.cashier_shift_id !== this.activeShiftId);
                    this.managerPin = '';
                },

                async executeReprint() {
                    if (!this.selectedReprintTx) {
                        alert('Pilih faktur transaksi terlebih dahulu.');
                        return;
                    }
                    if (this.isPastShiftReprint && !this.managerPin) {
                        alert('Cetak ulang transaksi dari shift lampau memerlukan PIN Otorisasi Manajer.');
                        return;
                    }
                    try {
                        const res = await fetch('/pos/reprint/' + this.selectedReprintTx.id, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                shift_id: this.activeShiftId,
                                paper: this.printerPaperSize,
                                manager_pin: this.managerPin || null,
                            }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (res.ok && data.success) {
                            window.open(data.print_url, '_blank', 'width=420,height=650');
                            this.reprintModal = false;
                        } else {
                            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Gagal mencetak ulang.');
                            alert('Gagal Cetak Ulang:\n' + msg);
                        }
                    } catch (e) {
                        alert('Gagal mencetak ulang:\n' + e.message);
                    }
                },

                setPaperSize(size) {
                    this.printerPaperSize = size;
                    this.simulatedPaperSize = size;
                    this.savePrinterSettings();
                },

                savePrinterSettings() {
                    localStorage.setItem('sigma_pos_printer_paper', this.printerPaperSize);
                    localStorage.setItem('sigma_pos_printer_autoprint', this.printerAutoPrint);
                    localStorage.setItem('sigma_pos_printer_autodrawer', this.printerAutoDrawer);
                    localStorage.setItem('sigma_pos_printer_footer', this.printerFooterNote);
                },

                switchCashier(cashierId) {
                    if (!cashierId) return;
                    window.location.href = '/pos?cashier_id=' + cashierId;
                },

                openPrinterSettings() {
                    this.printerSettingModal = true;
                },

                openReceiptSimulation() {
                    this.simulatedPaperSize = this.printerPaperSize;
                    this.receiptSimulationModal = true;
                },

                testPrintReceipt() {
                    const url = '{{ route("pos.receipt.sample") }}?paper=' + this.printerPaperSize + '&autoprint=1';
                    window.open(url, '_blank', 'width=420,height=650');
                },

                formatRupiah(num) {
                    return Math.round(num || 0).toLocaleString('id-ID');
                },

                formatDec(num) {
                    return parseFloat(num || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</body>
</html>
