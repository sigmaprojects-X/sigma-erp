@extends('layouts.app')

@section('title', 'Pendaftaran Aset Tetap Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6"
     x-data="{
         cost: {{ old('acquisition_cost', 0) }},
         salvage: {{ old('salvage_value', 0) }},
         usefulMonths: {{ old('useful_life_months', 48) }},
         fundingSource: '{{ old('funding_source', 'CASH') }}',
         category: '{{ old('category', 'EQUIPMENT') }}',
         get monthlyDepreciation() {
             let c = parseFloat(this.cost) || 0;
             let s = parseFloat(this.salvage) || 0;
             let m = parseInt(this.usefulMonths) || 1;
             if (m <= 0 || c <= s) return 0;
             return Math.round((c - s) / m);
         },
         formatRp(val) {
             return 'Rp ' + Math.round(val || 0).toLocaleString('id-ID');
         }
     }">

    <!-- BACK & TITLE -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('assets.index') }}" class="p-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-50 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100">Registrasi Aset Tetap Baru</h1>
                <p class="text-xs text-slate-500 dark:text-slate-400">Pencatatan aset modal toko beserta jadwal penyusutan dan jurnal otomatis</p>
            </div>
        </div>
    </div>

    <!-- ERROR & SUCCESS ALERTS -->
    @if ($errors->any())
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 rounded-2xl text-rose-700 dark:text-rose-300 text-xs">
            <div class="font-bold mb-1">Periksa kembali isian formulir:</div>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('assets.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- CARD 1: INFORMASI UMUM ASET -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <span class="text-blue-600 dark:text-blue-400 font-bold">1.</span>
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Informasi & Identitas Aset</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Kode Aset -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Kode Aset / Tagging <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="asset_code"
                           value="{{ old('asset_code', $suggestedCode) }}"
                           required
                           class="w-full py-2 px-3 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl font-mono focus:bg-white focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Format: AST-YYYYMM-XXX</span>
                </div>

                <!-- Nama Aset -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Nama Aset / Deskripsi Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           placeholder="Contoh: Genset Honda 5kVA Toko Utama"
                           required
                           class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                </div>

                <!-- Golongan / Kategori -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Golongan Kategori Akuntansi <span class="text-rose-500">*</span>
                    </label>
                    <select name="category"
                            x-model="category"
                            required
                            class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                        <option value="EQUIPMENT">Peralatan & Mesin (Akun 1501-001 / Akum 1502-001)</option>
                        <option value="VEHICLE">Kendaraan Operasional (Akun 1501-002 / Akum 1502-002)</option>
                        <option value="BUILDING">Bangunan & Renovasi (Akun 1501-003 / Akum 1502-003)</option>
                        <option value="FURNITURE">Mebel & Inventaris Toko (Akun 1501-004 / Akum 1502-004)</option>
                    </select>
                </div>

                <!-- Lokasi Toko / Cabang -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Lokasi Penempatan / Cabang
                    </label>
                    <select name="store_id"
                            class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                        <option value="">🏢 Kantor Pusat / HO (Umum)</option>
                        @foreach($stores as $st)
                            <option value="{{ $st->id }}" {{ old('store_id') === $st->id ? 'selected' : '' }}>
                                🏪 {{ $st->name }} ({{ $st->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- CARD 2: NILAI PEROLEHAN & PENYUSUTAN GARIS LURUS -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <span class="text-blue-600 dark:text-blue-400 font-bold">2.</span>
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Nilai Perolehan & Parameter Depresiasi</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tanggal Perolehan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Tanggal Perolehan / Pembelian <span class="text-rose-500">*</span>
                    </label>
                    <input type="date"
                           name="acquisition_date"
                           value="{{ old('acquisition_date', date('Y-m-d')) }}"
                           required
                           class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                </div>

                <!-- Masa Manfaat (Bulan) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Masa Manfaat (Bulan) <span class="text-rose-500">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number"
                               name="useful_life_months"
                               x-model.number="usefulMonths"
                               min="1"
                               max="600"
                               required
                               class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 font-bold">
                        <span class="text-xs text-slate-500 whitespace-nowrap" x-text="'(' + ((usefulMonths / 12).toFixed(1)) + ' thn)'"></span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        <span class="text-[10px] text-slate-400">Pilihan cepat:</span>
                        <button type="button" @click="usefulMonths = 12" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md font-semibold text-slate-600 dark:text-slate-300">1 Thn</button>
                        <button type="button" @click="usefulMonths = 24" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md font-semibold text-slate-600 dark:text-slate-300">2 Thn</button>
                        <button type="button" @click="usefulMonths = 48" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md font-semibold text-slate-600 dark:text-slate-300">4 Thn</button>
                        <button type="button" @click="usefulMonths = 96" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md font-semibold text-slate-600 dark:text-slate-300">8 Thn</button>
                        <button type="button" @click="usefulMonths = 240" class="px-2 py-0.5 text-[10px] bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-md font-semibold text-slate-600 dark:text-slate-300">20 Thn</button>
                    </div>
                </div>

                <!-- Harga Perolehan (Cost) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Harga Perolehan (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number"
                           name="acquisition_cost"
                           x-model.number="cost"
                           step="0.01"
                           min="1000"
                           required
                           class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100 font-bold">
                    <span class="text-[11px] text-blue-600 dark:text-blue-400 font-medium mt-0.5 block" x-text="formatRp(cost)"></span>
                </div>

                <!-- Nilai Residu (Salvage Value) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Nilai Residu / Sisa (Rp)
                    </label>
                    <input type="number"
                           name="salvage_value"
                           x-model.number="salvage"
                           step="0.01"
                           min="0"
                           class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                    <span class="text-[11px] text-slate-400 mt-0.5 block">Nilai akhir aset saat habis masa manfaat (default 0)</span>
                </div>
            </div>

            <!-- PREVIEW KALKULASI GARIS LURUS -->
            <div class="mt-3 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40 rounded-xl border border-blue-200/60 dark:border-blue-900/40">
                <div class="flex items-center justify-between text-xs font-bold text-blue-900 dark:text-blue-200">
                    <span>⚡ Simulasi Depresiasi Garis Lurus (Straight-Line):</span>
                    <span class="font-mono text-sm text-blue-700 dark:text-blue-300" x-text="formatRp(monthlyDepreciation) + ' / bulan'"></span>
                </div>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-slate-600 dark:text-slate-400 border-t border-blue-200/50 dark:border-blue-900/50 pt-2">
                    <div>Dasar Penyusutan: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="formatRp(cost - salvage)"></span></div>
                    <div>Umur Ekonomis: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="usefulMonths + ' Bulan'"></span></div>
                    <div>Depresiasi per Tahun: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="formatRp(monthlyDepreciation * 12)"></span></div>
                </div>
            </div>
        </div>

        <!-- CARD 3: SUMBER DANA AKUISISI & OTOMASI JURNAL -->
        <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <span class="text-blue-600 dark:text-blue-400 font-bold">3.</span>
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Sumber Dana & Otomasi Jurnal Akuisisi</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Sumber Dana -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Sumber Pembayaran / Asal Perolehan <span class="text-rose-500">*</span>
                    </label>
                    <select name="funding_source"
                            x-model="fundingSource"
                            required
                            class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                        <option value="CASH">KAS TOKO (Akun 1001-001 Kas Utama)</option>
                        <option value="BANK">TRANSFER BANK (Pilih Rekening Bank)</option>
                        <option value="MODAL_AWAL">SETORAN MODAL AWAL (Akun 3001-001 Modal Pemilik)</option>
                        <option value="NONE">TANPA JURNAL OTOMATIS (Migrasi Saldo Awal / Sudah Dijurnal)</option>
                    </select>
                </div>

                <!-- Pilihan Rekening Bank jika BANK -->
                <div x-show="fundingSource === 'BANK'" x-cloak>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Pilih Rekening Bank Sumber Dana <span class="text-rose-500">*</span>
                    </label>
                    <select name="bank_account_id"
                            class="w-full py-2 px-3 text-xs bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 text-slate-900 dark:text-slate-100">
                        <option value="">-- Pilih Bank --</option>
                        @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_account_id') === $bank->id ? 'selected' : '' }}>
                                {{ $bank->bank_name }} - {{ $bank->account_number }} ({{ $bank->account_holder ?? $bank->bank_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- PREVIEW JURNAL AKUISISI -->
            <div x-show="fundingSource !== 'NONE'" class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200/60 dark:border-slate-700/60 text-xs text-slate-600 dark:text-slate-300">
                <span class="font-bold text-slate-800 dark:text-slate-100">Pratinjau Jurnal General Ledger yang akan dibuat:</span>
                <div class="mt-2 font-mono text-[11px] bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 space-y-1">
                    <div class="text-emerald-700 dark:text-emerald-400">
                        [DR] Aset Tetap (<span x-text="category"></span>) : <span x-text="formatRp(cost)"></span>
                    </div>
                    <div class="text-rose-700 dark:text-rose-400 pl-4">
                        [CR] <span x-text="fundingSource === 'CASH' ? '1001-001 Kas Utama' : (fundingSource === 'BANK' ? '1002-xxx Rekening Bank' : '3001-001 Modal Pemilik')"></span> : <span x-text="formatRp(cost)"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SUBMIT BUTTONS -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('assets.index') }}"
               class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold rounded-xl text-xs transition-colors">
                Batal
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl text-xs shadow-sm shadow-blue-500/25 transition-all">
                💾 Daftarkan & Catat Jurnal Aset
            </button>
        </div>
    </form>
</div>
@endsection
