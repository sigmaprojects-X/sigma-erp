@extends('layouts.app')

@section('title', 'Detail Opname ' . $opname->opname_number)
@section('tab_title', 'Detail Opname')

@section('content')
<div class="space-y-4 p-4 max-w-7xl mx-auto w-full">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-0.5">
                <a href="{{ route('warehouse.opnames.index') }}" class="hover:text-blue-600 font-semibold">Stock Opname</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-300 font-bold font-mono">{{ $opname->opname_number }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                    {{ $opname->opname_number }}
                </h1>
                @if($opname->status === 'IN_PROGRESS')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 animate-pulse">📝 IN_PROGRESS</span>
                @elseif($opname->status === 'PENDING_APPROVAL')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300">⏳ PENDING APPROVAL (Menunggu Rekonsiliasi)</span>
                @elseif($opname->status === 'COMPLETED')
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✅ COMPLETED (Telah Direkonsiliasi)</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">{{ $opname->status }}</span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($opname->status === 'IN_PROGRESS')
                <a href="{{ route('warehouse.opname-pwa.session', $opname->id) }}"
                   target="_blank"
                   class="px-3.5 py-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
                    <span>📱 Buka Hitung di PWA HP</span>
                </a>
            @endif
            <a href="{{ route('warehouse.opnames.index') }}"
               class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl shadow-xs transition-colors">
                ← Kembali ke Daftar
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400 text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs">
            <ul class="list-disc pl-4 space-y-0.5 font-semibold">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- INFORMASI RINGKASAN SESI -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-2">
            <h2 class="font-bold uppercase tracking-wider text-slate-400 text-[10.5px]">Toko & Lokasi</h2>
            <div class="font-black text-slate-900 dark:text-white text-sm">{{ $opname->store->name }}</div>
            <div class="text-slate-400 text-[11px]">Kode Toko: <span class="font-mono font-bold">{{ $opname->store->code }}</span></div>
            <div class="text-slate-400 text-[11px]">Tanggal Opname: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $opname->opname_date->format('d/m/Y') }}</span></div>
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-2">
            <h2 class="font-bold uppercase tracking-wider text-slate-400 text-[10.5px]">Petugas & Audit</h2>
            <div class="text-slate-700 dark:text-slate-300">Inisiator: <span class="font-bold">{{ $opname->creator ? $opname->creator->name : '-' }}</span></div>
            <div class="text-slate-700 dark:text-slate-300">Disetujui Oleh: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $opname->approver ? $opname->approver->name : '-' }}</span></div>
            @if($opname->notes)
                <div class="text-slate-400 text-[11px] truncate">Catatan: {{ $opname->notes }}</div>
            @endif
        </div>

        <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-xs text-xs space-y-2">
            <h2 class="font-bold uppercase tracking-wider text-slate-400 text-[10.5px]">Rekap Fisik & Finansial</h2>
            <div class="flex justify-between">
                <span class="text-slate-400">Total Fisik:</span>
                <span class="font-mono font-bold">{{ number_format($opname->total_actual_quantity, 2) }} unit</span>
            </div>
            @if($isManager)
                <div class="flex justify-between">
                    <span class="text-slate-400">Total Selisih:</span>
                    <span class="font-mono font-bold {{ $opname->total_discrepancy_quantity < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $opname->total_discrepancy_quantity > 0 ? '+' : '' }}{{ number_format($opname->total_discrepancy_quantity, 2) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Nilai Selisih:</span>
                    <span class="font-mono font-bold {{ $opname->total_discrepancy_value < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $opname->total_discrepancy_value >= 0 ? '+' : '' }}Rp {{ number_format($opname->total_discrepancy_value, 0, ',', '.') }}
                    </span>
                </div>
            @else
                <div class="p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-[10.5px] text-slate-400 italic">
                    🛡️ Blind Mode: Nilai dan stok sistem disembunyikan untuk staf hitung fisik.
                </div>
            @endif
        </div>
    </div>

    <!-- FORM INPUT HITUNGAN FISIK / TABEL REKONSILIASI -->
    <div class="bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                <span>📦 Lembar Penghitungan Fisik Produk</span>
            </h2>
            <span class="text-xs text-slate-400 font-bold">Total Item: {{ $opname->items->count() }}</span>
        </div>

        @if($opname->status === 'IN_PROGRESS')
            <form action="{{ route('warehouse.opnames.count', $opname->id) }}" method="POST">
                @csrf
                <div class="p-3 bg-gradient-to-r from-blue-700 to-indigo-800 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <label class="text-xs font-bold uppercase tracking-wider text-blue-100 flex items-center gap-1.5 whitespace-nowrap">
                        <span>⚡</span> Scan Barcode / Cari Produk di Lembar Opname:
                    </label>
                    <div class="flex-1 max-w-md relative">
                        <input type="text" id="opnameSearchInput"
                               placeholder="Scan barcode atau ketik SKU/Nama lalu tekan Enter..."
                               class="w-full px-3 py-1.5 bg-white text-slate-900 placeholder-slate-400 text-xs font-semibold rounded-lg shadow-inner focus:outline-hidden focus:ring-2 focus:ring-blue-400"
                               autocomplete="off">
                    </div>
                </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-400 uppercase text-[10px] font-bold border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="p-3 w-12 text-center">No</th>
                        <th class="p-3">Kode & Nama Produk</th>
                        <th class="p-3">Satuan Dasar</th>
                        @if($isManager)
                            <th class="p-3 text-right">Stok Sistem</th>
                        @endif
                        <th class="p-3 text-right">Hitungan Fisik Riil</th>
                        @if($isManager)
                            <th class="p-3 text-right">Selisih Fisik</th>
                            <th class="p-3 text-right">HPP (Modal)</th>
                            <th class="p-3 text-right">Nilai Selisih (Rp)</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium text-slate-800 dark:text-slate-200">
                    @foreach($opname->items as $index => $item)
                        @php
                            $barcode = $item->product->barcodes->first()?->barcode ?? '';
                        @endphp
                        <tr class="opname-row hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                            id="opnameRow_{{ $item->product_id }}"
                            data-sku="{{ strtolower($item->product->sku) }}"
                            data-name="{{ strtolower($item->product->name) }}"
                            data-barcode="{{ strtolower($barcode) }}">
                            <td class="p-3 text-center text-slate-400">{{ $index + 1 }}</td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $item->product->name }}</div>
                                <div class="text-[10px] font-mono text-slate-400">{{ $item->product->sku }}</div>
                            </td>
                            <td class="p-3 font-semibold text-slate-500">
                                {{ $item->product->base_unit }}
                            </td>

                            {{-- STOK SISTEM HANYA TAMPIL UNTUK MANAGER --}}
                            @if($isManager)
                                <td class="p-3 text-right font-mono font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format($item->system_quantity, 2) }}
                                </td>
                            @endif

                            {{-- INPUT FISIK RIIL --}}
                            <td class="p-3 text-right font-mono">
                                @if($opname->status === 'IN_PROGRESS')
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" onclick="openBackofficeUnitCalc('{{ $item->product_id }}')"
                                                class="px-2 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-100 dark:bg-blue-950 dark:hover:bg-blue-900 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-800 text-xs font-bold transition-all cursor-pointer shadow-2xs shrink-0 flex items-center gap-1"
                                                title="Buka Kalkulator Multi-Satuan (Dus, Pack, Pcs)">
                                            <span>📦</span>
                                            <span class="hidden md:inline text-[11px]">Hitung Satuan</span>
                                        </button>
                                        <input type="number" step="0.01" min="0"
                                               name="actual_counts[{{ $item->product_id }}]"
                                               id="bo_input_{{ $item->product_id }}"
                                               value="{{ $item->actual_quantity > 0 ? (float)$item->actual_quantity : '' }}"
                                               placeholder="0"
                                               required
                                               class="w-24 px-2 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-right font-bold text-xs focus:ring-2 focus:ring-blue-500">
                                        <input type="hidden" name="actual_notes[{{ $item->product_id }}]"
                                               id="bo_notes_{{ $item->product_id }}"
                                               value="{{ $item->notes }}">
                                    </div>
                                    <div id="bo_notes_badge_{{ $item->product_id }}"
                                         class="{{ empty($item->notes) ? 'hidden ' : '' }}text-[10px] text-blue-600 dark:text-blue-400 font-semibold mt-1">
                                        📦 <span id="bo_notes_text_{{ $item->product_id }}">{{ $item->notes }}</span>
                                    </div>
                                @else
                                    <div>
                                        <span class="font-bold font-mono">{{ number_format($item->actual_quantity, 2) }}</span>
                                        <span class="text-[10px] text-slate-400 uppercase ml-0.5">{{ $item->product->base_unit }}</span>
                                    </div>
                                    @if(!empty($item->notes))
                                        <div class="text-[10.5px] text-blue-600 dark:text-blue-400 font-medium mt-0.5">
                                            📦 {{ $item->notes }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- SELISIH & FINANSIAL HANYA UNTUK MANAGER --}}
                            @if($isManager)
                                <td class="p-3 text-right font-mono font-bold">
                                    @if($item->discrepancy_quantity > 0)
                                        <span class="text-emerald-600">+{{ number_format($item->discrepancy_quantity, 2) }}</span>
                                    @elseif($item->discrepancy_quantity < 0)
                                        <span class="text-rose-600">{{ number_format($item->discrepancy_quantity, 2) }}</span>
                                    @else
                                        <span class="text-slate-400">0.00</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-mono text-slate-500">
                                    Rp {{ number_format($item->unit_cost, 0, ',', '.') }}
                                </td>
                                <td class="p-3 text-right font-mono font-bold">
                                    @if($item->discrepancy_value > 0)
                                        <span class="text-emerald-600">+Rp {{ number_format($item->discrepancy_value, 0, ',', '.') }}</span>
                                    @elseif($item->discrepancy_value < 0)
                                        <span class="text-rose-600">-Rp {{ number_format(abs($item->discrepancy_value), 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-slate-400">Rp 0</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($opname->status === 'IN_PROGRESS')
            <div class="p-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-md transition-all shadow-blue-500/25 cursor-pointer">
                    💾 Simpan & Ajukan Hitungan Fisik ke Manager
                </button>
            </div>
            </form>
        @endif
    </div>

    <!-- FORM APPROVAL & REKONSILIASI OLEH MANAGER -->
    @if($isManager && $opname->status === 'PENDING_APPROVAL')
        <div class="bg-gradient-to-r from-slate-900 to-indigo-950 border border-indigo-800/50 rounded-2xl p-5 text-white shadow-lg space-y-4">
            <div class="flex items-center gap-2 text-indigo-400 font-extrabold text-xs uppercase tracking-wider">
                <span>⚖️ Rekonsiliasi & Otorisasi Penyesuaian Stok (Manager Approval)</span>
            </div>
            <p class="text-xs text-indigo-200/80 leading-relaxed">
                Menyetujui rekonsiliasi ini akan secara otomatis:
                <br>1. Mengkoreksi saldo stok fisik pada database ke angka fisik riil yang diinput staf.
                <br>2. Mencatat mutasi kartu stok penyesuaian opname (`OPNAME_ADJUSTMENT_PLUS` / `MINUS`).
                <br>3. Memposting <strong>Jurnal Penyesuaian Akuntansi Seimbang</strong> ke Buku Besar (Persediaan vs Pendapatan/Beban Selisih Stok).
            </p>

            <form action="{{ route('warehouse.opnames.approve', $opname->id) }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-indigo-200 mb-1">Catatan Persetujuan Rekonsiliasi (Wajib dicatat untuk audit)</label>
                    <input type="text" name="approval_notes" required placeholder="Contoh: Selisih minus 2 sak beras karena bocor atap, surplus minyak dari sisa promo"
                           class="w-full px-3 py-2 bg-indigo-900/60 border border-indigo-700/60 rounded-xl text-white text-xs placeholder-indigo-400/50 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit" onclick="return confirm('Apakah Anda yakin menyetujui hasil opname ini dan memposting jurnal penyesuaian keuangan?')"
                            class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-xs rounded-xl shadow-lg transition-all shadow-emerald-500/20 cursor-pointer">
                        ✅ Setujui Rekonsiliasi Stok & Posting Jurnal
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<!-- MODAL KALKULATOR MULTI-SATUAN (BACK-OFFICE) -->
<div id="boUnitModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs" style="display: none;">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-md p-6 shadow-2xl animate-in zoom-in-95 duration-150 transition-colors">
        <div class="flex items-start justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">📦 Kalkulator Satuan Kemasan</span>
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white line-clamp-1 mt-0.5" id="boCalcProductName">Nama Produk</h3>
                <div class="text-[11px] text-slate-400 font-mono" id="boCalcProductSku">SKU</div>
            </div>
            <button type="button" onclick="closeBoUnitCalc()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center font-bold text-lg cursor-pointer transition-colors">&times;</button>
        </div>

        <div class="p-2.5 bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-900/60 rounded-xl text-[11px] text-blue-700 dark:text-blue-300 leading-snug mb-3">
            💡 Masukkan jumlah kemasan fisik yang ditemukan (misal: 2 Dus + 1 Pcs). Sistem otomatis mengalikan faktor konversi ke satuan dasar.
        </div>

        <div class="space-y-2.5 max-h-[50vh] overflow-y-auto pr-1" id="boCalcUnitsContainer">
            <!-- Render unit rows dynamically -->
        </div>

        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 space-y-3">
            <div class="bg-gradient-to-r from-blue-900 to-indigo-900 border border-blue-500/30 rounded-2xl p-3 text-white flex items-center justify-between">
                <div>
                    <div class="text-[10px] text-blue-200 font-bold uppercase">Total Satuan Dasar</div>
                    <div class="text-xs text-blue-100" id="boCalcFormula">0 Satuan</div>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-mono font-black text-cyan-300" id="boCalcTotal">0</span>
                    <span class="text-xs font-bold text-blue-200 ml-1 uppercase" id="boCalcBaseUnit">PCS</span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button type="button" onclick="closeBoUnitCalc()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl cursor-pointer">
                    Batal
                </button>
                <button type="button" onclick="applyBoUnitCalc()" class="px-5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md cursor-pointer transition-all">
                    Terapkan ke Baris Ini
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const BACKOFFICE_PRODUCTS = {
    @foreach($opname->items as $it)
        '{{ $it->product_id }}': {
            name: {!! json_encode($it->product->name) !!},
            sku: {!! json_encode($it->product->sku) !!},
            base_unit: {!! json_encode($it->product->base_unit) !!},
            units: [
                @php
                    $sortedUnits = $it->product->units ? $it->product->units->where('is_active', true)->sortByDesc('conversion_factor')->values() : collect();
                    if ($sortedUnits->isEmpty()) {
                        $sortedUnits = collect([[
                            'unit_code' => $it->product->base_unit,
                            'unit_name' => $it->product->base_unit,
                            'conversion_factor' => 1.0,
                            'is_base_unit' => true
                        ]]);
                    }
                @endphp
                @foreach($sortedUnits as $u)
                    {
                        unit_code: {!! json_encode($u['unit_code'] ?? $u->unit_code) !!},
                        unit_name: {!! json_encode(($u['unit_name'] ?? $u->unit_name) ?: ($u['unit_code'] ?? $u->unit_code)) !!},
                        conversion_factor: {{ (float) ($u['conversion_factor'] ?? $u->conversion_factor) }},
                        is_base_unit: {{ (!empty($u['is_base_unit']) || !empty($u->is_base_unit)) ? 'true' : 'false' }}
                    },
                @endforeach
            ]
        },
    @endforeach
};

let activeBoProductId = null;
let boUnitValues = {};

function openBackofficeUnitCalc(productId) {
    activeBoProductId = productId;
    const prod = BACKOFFICE_PRODUCTS[productId];
    if (!prod) return;

    document.getElementById('boCalcProductName').innerText = prod.name;
    document.getElementById('boCalcProductSku').innerText = prod.sku;
    document.getElementById('boCalcBaseUnit').innerText = prod.base_unit;

    const container = document.getElementById('boCalcUnitsContainer');
    container.innerHTML = '';
    boUnitValues = {};

    // Cek apakah ada catatan existing di input notes
    const existingNotes = document.getElementById('bo_notes_' + productId)?.value.trim() || '';
    const existingTotal = parseFloat(document.getElementById('bo_input_' + productId)?.value || 0);

    prod.units.forEach((u, idx) => {
        boUnitValues[u.unit_code] = 0;

        // Render card unit
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
            </div>
            <div class="flex items-center gap-1.5 shrink-0">
                <button type="button" onclick="stepBoUnit('${u.unit_code}', -1)"
                        class="w-7 h-7 rounded-lg bg-slate-200 hover:bg-rose-100 hover:text-rose-600 dark:bg-slate-700 dark:hover:bg-rose-950 font-bold text-xs flex items-center justify-center cursor-pointer transition-colors">-</button>
                <input type="number" step="any" min="0" id="bo_uinput_${u.unit_code}"
                       value="0" oninput="onBoUnitInputChange('${u.unit_code}')"
                       class="w-16 px-2 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg text-center font-mono font-bold text-xs focus:ring-2 focus:ring-blue-500">
                <button type="button" onclick="stepBoUnit('${u.unit_code}', 1)"
                        class="w-7 h-7 rounded-lg bg-slate-200 hover:bg-emerald-100 hover:text-emerald-600 dark:bg-slate-700 dark:hover:bg-emerald-950 font-bold text-xs flex items-center justify-center cursor-pointer transition-colors">+</button>
            </div>
        `;
        container.appendChild(row);
    });

    // Jika ada catatan rincian teks (misal: "2 DUS, 1 PCS"), coba parse
    if (existingNotes) {
        parseExistingNotesIntoBoUnits(existingNotes, prod);
    } else if (existingTotal > 0 && prod.units.length === 1) {
        const u = prod.units[0];
        boUnitValues[u.unit_code] = existingTotal;
        const el = document.getElementById('bo_uinput_' + u.unit_code);
        if (el) el.value = existingTotal;
    }

    recalcBoUnits();
    document.getElementById('boUnitModal').style.display = 'flex';
}

function parseExistingNotesIntoBoUnits(notes, prod) {
    // Format: "2 DUS, 1 PCS"
    const parts = notes.split(',');
    parts.forEach(p => {
        const trimmed = p.trim();
        const match = trimmed.match(/^([\d\.]+)\s*(.+)$/i);
        if (match) {
            const qty = parseFloat(match[1]);
            const code = match[2].trim().toUpperCase();
            if (boUnitValues.hasOwnProperty(code)) {
                boUnitValues[code] = qty;
                const el = document.getElementById('bo_uinput_' + code);
                if (el) el.value = qty;
            }
        }
    });
}

function stepBoUnit(code, step) {
    const el = document.getElementById('bo_uinput_' + code);
    let val = parseFloat(el?.value || 0) + step;
    val = Math.max(0, val);
    if (el) el.value = val;
    boUnitValues[code] = val;
    recalcBoUnits();
}

function onBoUnitInputChange(code) {
    const el = document.getElementById('bo_uinput_' + code);
    let val = Math.max(0, parseFloat(el?.value || 0));
    boUnitValues[code] = val;
    recalcBoUnits();
}

function recalcBoUnits() {
    if (!activeBoProductId) return;
    const prod = BACKOFFICE_PRODUCTS[activeBoProductId];
    let total = 0;
    let parts = [];

    prod.units.forEach(u => {
        const count = parseFloat(boUnitValues[u.unit_code] || 0);
        if (count > 0) {
            const sub = count * u.conversion_factor;
            total += sub;
            parts.push(`${count} ${u.unit_code}`);
        }
    });

    document.getElementById('boCalcTotal').innerText = total;
    document.getElementById('boCalcFormula').innerText = parts.length > 0 ? parts.join(' + ') : '0 Satuan';
}

function applyBoUnitCalc() {
    if (!activeBoProductId) return;
    const prod = BACKOFFICE_PRODUCTS[activeBoProductId];
    let total = 0;
    let parts = [];

    prod.units.forEach(u => {
        const count = parseFloat(boUnitValues[u.unit_code] || 0);
        if (count > 0) {
            total += count * u.conversion_factor;
            parts.push(`${count} ${u.unit_code}`);
        }
    });

    const notes = parts.join(', ');

    // Update input di baris tabel
    const countInput = document.getElementById('bo_input_' + activeBoProductId);
    const notesInput = document.getElementById('bo_notes_' + activeBoProductId);
    const notesBadge = document.getElementById('bo_notes_badge_' + activeBoProductId);
    const notesText = document.getElementById('bo_notes_text_' + activeBoProductId);

    if (countInput) countInput.value = total;
    if (notesInput) notesInput.value = notes;

    if (notes) {
        if (notesBadge) notesBadge.classList.remove('hidden');
        if (notesText) notesText.innerText = notes;
    } else {
        if (notesBadge) notesBadge.classList.add('hidden');
    }

    closeBoUnitCalc();
}

function closeBoUnitCalc() {
    document.getElementById('boUnitModal').style.display = 'none';
    activeBoProductId = null;
}

document.addEventListener('DOMContentLoaded', () => {
    const scanInput = document.getElementById('opnameSearchInput');
    scanInput?.focus();

    scanInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const term = scanInput.value.trim().toLowerCase();
            if (!term) return;

            const rows = document.querySelectorAll('.opname-row');
            let foundRow = null;

            // Cari exact barcode atau SKU terlebih dahulu
            for (const r of rows) {
                if (r.dataset.barcode === term || r.dataset.sku === term) {
                    foundRow = r;
                    break;
                }
            }

            // Jika tidak ada, cari partial nama
            if (!foundRow) {
                for (const r of rows) {
                    if (r.dataset.name.includes(term) || r.dataset.sku.includes(term)) {
                        foundRow = r;
                        break;
                    }
                }
            }

            if (foundRow) {
                document.querySelectorAll('.opname-row').forEach(r => r.classList.remove('bg-emerald-100', 'dark:bg-emerald-950/60'));
                foundRow.classList.add('bg-emerald-100', 'dark:bg-emerald-950/60');
                foundRow.scrollIntoView({ behavior: 'smooth', block: 'center' });

                const input = foundRow.querySelector('input[type="number"]');
                if (input) {
                    input.focus();
                    input.select();
                }
                scanInput.value = '';
            } else {
                alert(`Produk dengan barcode/nama '${term}' tidak ditemukan dalam lembar opname ini.`);
                scanInput.select();
            }
        }
    });
});
</script>
@endsection
