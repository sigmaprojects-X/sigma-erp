@extends('layouts.app')

@section('title', 'Penomoran Otomatis & Format Nomor Transaksi')
@section('tab_title', 'Penomoran Otomatis')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full" x-data="autoNumberSettingsApp()">

    <!-- HEADER TITLE & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('settings.general') }}" class="hover:text-blue-700 font-semibold">Pengaturan</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold">Penomoran Otomatis</span>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>🔢 Penomoran Otomatis &amp; Format Nomor Transaksi (Auto-Numbering)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Atur awalan kode (prefix), format tanggal, dan siklus reset nomor faktur untuk seluruh modul toko.
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <form action="{{ route('settings.numbering.restore-defaults') }}" method="POST" class="inline"
                  onsubmit="return confirm('Apakah Anda yakin ingin memulihkan seluruh pola penomoran ke standar bawaan sistem?');">
                @csrf
                <button type="submit"
                        class="px-3.5 py-2 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
                    <span>↺ Pulihkan Bawaan</span>
                </button>
            </form>
            <a href="{{ route('pos.index') }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition-all shadow-emerald-900/20 active:scale-95">
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

    <!-- SUB-NAVIGATION TABS (LENGKAP & KONSISTEN) -->
    <div class="flex items-center space-x-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
        <a href="{{ route('settings.general') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>⚙️ Profil &amp; Pajak</span>
        </a>
        <a href="{{ route('settings.stores.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>🏪 Cabang &amp; Toko</span>
        </a>
        <a href="{{ route('settings.users.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>👥 Pengguna &amp; RBAC</span>
        </a>
        <a href="{{ route('settings.printer') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>🖨️ Printer &amp; Struk</span>
        </a>
        <a href="{{ route('settings.numbering.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-black transition-all bg-blue-600 text-white shadow-sm flex items-center gap-1.5 whitespace-nowrap">
            <span>🔢 Penomoran Otomatis</span>
        </a>
        <a href="{{ route('settings.cashier') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>💼 Operasional Kasir</span>
        </a>
        <a href="{{ route('settings.maintenance.index') }}"
           class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 whitespace-nowrap">
            <span>🛡️ Pemeliharaan &amp; Backup</span>
        </a>
    </div>

    <!-- MODULE ACCORDIONS -->
    <div class="space-y-4">
        @foreach($groupedPatterns as $modKey => $group)
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden shadow-xs">
                
                <!-- GROUP HEADER -->
                <div class="bg-slate-50 dark:bg-slate-800/80 p-3.5 sm:p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="text-xl">{{ $group['icon'] }}</span>
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">{{ $group['name'] }}</h2>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $group['description'] }}</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-0.5 text-[10.5px] font-extrabold rounded-full bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                        {{ $group['items']->count() }} Dokumen
                    </span>
                </div>

                <!-- PATTERNS LIST -->
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($group['items'] as $pattern)
                        <div class="p-4 sm:p-5 hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors"
                             x-data="simplePatternRow({
                                 id: '{{ $pattern->id }}',
                                 prefix: '{{ $pattern->prefix }}',
                                 pattern: '{{ $pattern->pattern }}',
                                 digits: {{ (int) $pattern->digits }},
                                 resetCycle: '{{ $pattern->reset_cycle }}',
                                 currentSequence: {{ (int) $pattern->current_sequence }}
                             })">

                            <form action="{{ route('settings.numbering.update', $pattern->id) }}" method="POST" class="space-y-3">
                                @csrf
                                @method('PUT')

                                <!-- ROW 1: HEADER & LIVE PREVIEW -->
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-mono font-black bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                                            {{ $pattern->document_type }}
                                        </span>
                                        <span class="font-black text-sm text-slate-900 dark:text-white">
                                            {{ $pattern->name }}
                                        </span>
                                        @if($pattern->is_locked)
                                            <span class="px-2 py-0.5 text-[9.5px] font-extrabold rounded bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200">
                                                🔒 Terkunci
                                            </span>
                                        @endif
                                    </div>

                                    <!-- REAL-TIME LIVE RESULT BADGE -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-semibold">Contoh Nomor:</span>
                                        <span class="font-mono text-xs font-black px-3 py-1 rounded-lg bg-slate-900 text-emerald-400 border border-slate-700 shadow-inner"
                                              x-text="getLivePreview()">
                                            {{ $pattern->pattern }}
                                        </span>
                                    </div>
                                </div>

                                <!-- ROW 2: FORM CONTROLS (SIMPLE & INTUITIVE) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end pt-1">
                                    
                                    <!-- 1. AWALAN KODE (PREFIX) -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Awalan Kode:</label>
                                        <input type="text" name="prefix" x-model="prefix" required maxlength="20"
                                               class="w-full text-xs font-mono uppercase font-bold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    </div>

                                    <!-- 2. PILIHAN FORMAT PENOMORAN (PRESET DROPDOWN) -->
                                    <div class="lg:col-span-4">
                                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Pilihan Format Nomor:</label>
                                        <select x-model="selectedPreset" @change="onPresetChange()"
                                                class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                            <option value="STANDARD">Standar (Awalan-TahunBulan-Nomor)</option>
                                            <option value="STORE">Lengkap Toko (Awalan-Toko-TahunBulan-Nomor)</option>
                                            <option value="DAILY">Harian (Awalan-TahunBulanTanggal-Nomor)</option>
                                            <option value="SHORT">Ringkas (Awalan-ThnBln-Nomor)</option>
                                            <option value="CUSTOM">Kustom Format Manual...</option>
                                        </select>
                                        
                                        <!-- KOLOM PATTERN AKTIF / KUSTOM -->
                                        <input type="text" name="pattern" x-model="pattern" required maxlength="100"
                                               :class="selectedPreset === 'CUSTOM' ? 'mt-1.5' : 'hidden'"
                                               placeholder="Contoh: {PREFIX}-{Y}{m}-{SEQ}"
                                               class="w-full text-xs font-mono py-1.5 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                                    </div>

                                    <!-- 3. PANJANG DIGIT -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Digit Urutan:</label>
                                        <select name="digits" x-model.number="digits"
                                                class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                            <option value="3">3 Digit (001)</option>
                                            <option value="4">4 Digit (0001)</option>
                                            <option value="5">5 Digit (00001)</option>
                                            <option value="6">6 Digit (000001)</option>
                                        </select>
                                    </div>

                                    <!-- 4. SIKLUS RESET -->
                                    <div class="lg:col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 mb-1">Siklus Reset:</label>
                                        <select name="reset_cycle" x-model="resetCycle"
                                                class="w-full text-xs font-semibold py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                                            <option value="MONTHLY">Tiap Bulan (Tgl 1)</option>
                                            <option value="YEARLY">Tiap Tahun (1 Jan)</option>
                                            <option value="CONTINUOUS">Terus Menerus</option>
                                        </select>
                                    </div>

                                    <!-- 5. AKSI SIMPAN & ATUR COUNTER -->
                                    <div class="lg:col-span-2 flex items-center gap-1.5">
                                        <button type="submit"
                                                class="flex-1 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-lg shadow-xs transition-all cursor-pointer">
                                            Simpan
                                        </button>
                                        <button type="button"
                                                @click="openResetModal('{{ $pattern->id }}', '{{ $pattern->name }}', {{ $pattern->current_sequence }})"
                                                class="py-2 px-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg border border-slate-300 dark:border-slate-600 transition-colors cursor-pointer"
                                                title="Setel ulang nomor urut saat ini">
                                            #{{ $pattern->current_sequence }}
                                        </button>
                                    </div>

                                </div>
                            </form>

                        </div>
                    @endforeach
                </div>

            </div>
        @endforeach
    </div>

    <!-- MODAL RESET SEQUENCE / COUNTER -->
    <div x-show="openResetSequenceModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-2xl max-w-sm w-full p-5 shadow-2xl border border-slate-200 dark:border-slate-700 space-y-4"
             @click.away="openResetSequenceModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🔢 Atur Nomor Urutan Terakhir</span>
                </h3>
                <button type="button" @click="openResetSequenceModal = false" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
            </div>

            <form :action="'{{ url('settings/numbering') }}/' + targetPatternId + '/reset-sequence'" method="POST" class="space-y-3.5">
                @csrf
                @method('PATCH')
                <div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mb-2">
                        Setel counter urutan terakhir untuk <strong class="text-slate-900 dark:text-white" x-text="targetPatternName"></strong>:
                    </p>
                    <input type="number" name="current_sequence" min="0" x-model="targetSequence" required
                           class="w-full text-center font-mono text-base font-black py-2 px-3 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                    <p class="text-[10.5px] text-slate-400 mt-1">Transaksi berikutnya akan menggunakan nomor urut <code>current + 1</code>.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                    <button type="button" @click="openResetSequenceModal = false"
                            class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-lg cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black rounded-lg shadow-sm cursor-pointer">
                        Perbarui Counter
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- SCRIPT LOGIC (SIMPLE & INTUITIVE) -->
<script>
function autoNumberSettingsApp() {
    return {
        openResetSequenceModal: false,
        targetPatternId: '',
        targetPatternName: '',
        targetSequence: 0,

        openResetModal(id, name, seq) {
            this.targetPatternId = id;
            this.targetPatternName = name;
            this.targetSequence = seq;
            this.openResetSequenceModal = true;
        }
    };
}

function simplePatternRow(config) {
    return {
        id: config.id,
        prefix: config.prefix,
        pattern: config.pattern,
        digits: config.digits,
        resetCycle: config.resetCycle,
        currentSequence: config.currentSequence,
        selectedPreset: 'STANDARD',

        init() {
            // Deteksi preset awal berdasarkan pattern
            if (this.pattern === '{PREFIX}-{Y}{m}-{SEQ}') {
                this.selectedPreset = 'STANDARD';
            } else if (this.pattern === '{PREFIX}-{STORE}-{Y}{m}-{SEQ}') {
                this.selectedPreset = 'STORE';
            } else if (this.pattern === '{PREFIX}-{Y}{m}{d}-{SEQ}') {
                this.selectedPreset = 'DAILY';
            } else if (this.pattern === '{PREFIX}-{y}{m}-{SEQ}') {
                this.selectedPreset = 'SHORT';
            } else {
                this.selectedPreset = 'CUSTOM';
            }
        },

        onPresetChange() {
            if (this.selectedPreset === 'STANDARD') {
                this.pattern = '{PREFIX}-{Y}{m}-{SEQ}';
            } else if (this.selectedPreset === 'STORE') {
                this.pattern = '{PREFIX}-{STORE}-{Y}{m}-{SEQ}';
            } else if (this.selectedPreset === 'DAILY') {
                this.pattern = '{PREFIX}-{Y}{m}{d}-{SEQ}';
            } else if (this.selectedPreset === 'SHORT') {
                this.pattern = '{PREFIX}-{y}{m}-{SEQ}';
            }
        },

        getLivePreview() {
            const now = new Date();
            const Y = now.getFullYear().toString();
            const y = Y.substring(2);
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            const store = 'STR001';
            const sampleSeq = String(this.currentSequence > 0 ? this.currentSequence : 1).padStart(this.digits, '0');

            return (this.pattern || '{PREFIX}-{SEQ}')
                .replace(/{PREFIX}/g, (this.prefix || 'DOC').toUpperCase())
                .replace(/{Y}/g, Y)
                .replace(/{y}/g, y)
                .replace(/{m}/g, m)
                .replace(/{d}/g, d)
                .replace(/{STORE}/g, store)
                .replace(/{SEQ}/g, sampleSeq);
        }
    };
}
</script>
@endsection
