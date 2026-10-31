@extends('layouts.app')

@section('title', 'Saldo Awal Hutang Supplier')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                <a href="{{ route('accounting.coa.index') }}" class="hover:underline">Akuntansi</a>
                <span>/</span>
                <span>Saldo Awal Hutang</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center space-x-2.5">
                <span>📋 Saldo Awal Hutang Supplier (Opening AP)</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Catat faktur tagihan pembelian lama ke supplier yang masih belum lunas dari pembukuan sebelum migrasi.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('accounting.opening.payables.template') }}"
               class="inline-flex items-center space-x-2 px-3.5 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 text-xs font-bold transition-all shadow-xs">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh Template Excel (.xlsx)</span>
            </a>
            <button type="button" onclick="document.getElementById('modalImportPayables').classList.remove('hidden')"
                    class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 text-xs font-bold shadow-md shadow-slate-900/10 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span>📥 Import Massal Excel</span>
            </button>
            <button type="button" onclick="document.getElementById('modalAddPayable').classList.remove('hidden')"
                    class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-xs font-bold shadow-md shadow-blue-500/20 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ Input Manual</span>
            </button>
            <a href="{{ route('accounting.opening.coa.index') }}"
               class="inline-flex items-center space-x-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition-all">
                <span>← Saldo Awal COA</span>
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center space-x-2.5">
        <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="font-bold">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs">
        <div class="font-bold mb-1">⚠️ Terjadi Kesalahan:</div>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Total Hutang Card -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white p-5 rounded-2xl shadow-sm flex items-center justify-between">
        <div>
            <span class="text-xs uppercase tracking-wider text-rose-300 font-bold">Total Saldo Awal Hutang Supplier Terdaftar</span>
            <div class="text-2xl font-black font-mono mt-0.5 text-rose-400">
                Rp {{ number_format($totalPayable, 0, ',', '.') }}
            </div>
            <span class="text-[11px] text-slate-300">Dari {{ count($payables) }} faktur supplier lama yang belum lunas</span>
        </div>

        <a href="{{ route('reports.aging-payables') }}" class="px-3.5 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-all">
            Lihat Analisa Aging AP →
        </a>
    </div>

    <!-- Tabel Daftar Saldo Awal Hutang -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200/80 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="py-3 px-4">No. Faktur Supplier</th>
                        <th class="py-3 px-4">Nama Mitra Supplier</th>
                        <th class="py-3 px-4">Cabang Toko</th>
                        <th class="py-3 px-4">Tanggal Pembelian</th>
                        <th class="py-3 px-4">Jatuh Tempo</th>
                        <th class="py-3 px-4 text-right">Nilai Hutang (Rp)</th>
                        <th class="py-3 px-4 text-right">Sudah Dibayar</th>
                        <th class="py-3 px-4 text-right">Sisa Hutang</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @forelse($payables as $pay)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="py-3 px-4 font-mono font-bold text-blue-600 dark:text-blue-400">
                            {{ $pay->invoice_number }}
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                            {{ $pay->supplier?->name ?? 'Supplier Umum' }}
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $pay->store?->name ?? 'Semua Toko' }}
                        </td>
                        <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                            {{ $pay->purchase_date ? \Carbon\Carbon::parse($pay->purchase_date)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4 font-semibold text-rose-600 dark:text-rose-400">
                            {{ $pay->due_date ? \Carbon\Carbon::parse($pay->due_date)->format('d/m/Y') : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold">
                            Rp {{ number_format($pay->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            Rp {{ number_format($pay->paid_amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-600 dark:text-rose-400">
                            Rp {{ number_format(max(0, $pay->grand_total - $pay->paid_amount), 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($pay->paid_amount == 0)
                            <form action="{{ route('accounting.opening.payables.destroy', $pay->id) }}" method="POST" onsubmit="return confirm('Hapus saldo awal hutang faktur {{ $pay->invoice_number }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-500 hover:text-rose-700 font-bold text-[11px] hover:underline">
                                    Hapus
                                </button>
                            </form>
                            @else
                            <span class="text-[10px] text-slate-400 font-semibold">Terkunci (Ada Bayar)</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-400">
                            Belum ada saldo awal hutang supplier yang dicatat. Klik tombol di atas untuk menambah.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Input Saldo Awal Hutang -->
<div id="modalAddPayable" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">+ Input Saldo Awal Hutang Supplier</h3>
            <button type="button" onclick="document.getElementById('modalAddPayable').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form action="{{ route('accounting.opening.payables.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Supplier <span class="text-rose-500">*</span></label>
                <select name="supplier_id" required
                        class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-500 font-bold">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $s)
                    <option value="{{ $s->id }}">
                        {{ $s->name }} ({{ $s->phone ?: 'No kontak -' }})
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">No. Faktur Supplier Lama</label>
                    <input type="text" name="invoice_number" placeholder="Contoh: INV-SUP-001"
                           class="w-full px-3 py-2 text-xs font-mono rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cabang Toko</label>
                    <select name="store_id"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        @foreach($stores as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Tanggal Pembelian <span class="text-rose-500">*</span></label>
                    <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Jatuh Tempo <span class="text-rose-500">*</span></label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nilai Sisa Hutang (Rp) <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-xs font-bold text-slate-400">Rp</span>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0"
                           class="w-full pl-9 pr-3 py-2 text-sm font-mono font-bold rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-rose-600 dark:text-rose-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Keterangan / Rincian Faktur</label>
                <textarea name="notes" rows="2" placeholder="Contoh: Sisa hutang PO barang dagang bulan lalu..."
                          class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white"></textarea>
            </div>

            <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" onclick="document.getElementById('modalAddPayable').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Batal</button>
                <button type="submit"
                        class="px-5 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white rounded-xl shadow-md shadow-blue-500/20">Simpan Saldo Awal</button>
            </div>
        </form>
    </div>
    <!-- Modal Import Massal Excel Hutang -->
    <div id="modalImportPayables" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                        📥
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white">Import Massal Saldo Awal Hutang Supplier</h3>
                        <p class="text-[11px] text-slate-500">Unggah file Excel (.xlsx / .xls) atau CSV</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('modalImportPayables').classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold">✕</button>
            </div>

            <form action="{{ route('accounting.opening.payables.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Pilih Cabang Toko</label>
                    <select name="store_id" class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
                        @foreach($stores as $st)
                        <option value="{{ $st->id }}" {{ $storeId == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">File Excel / CSV Saldo Awal Hutang <span class="text-rose-500">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="w-full px-3 py-2 text-xs rounded-xl border border-dashed border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white cursor-pointer file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    <p class="text-[10px] text-slate-500 mt-1">Gunakan template Excel resmi agar format kolom langsung terbaca otomatis.</p>
                </div>

                <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-200 dark:border-indigo-800/60 text-indigo-800 dark:text-indigo-300 text-[11px] space-y-1">
                    <div class="font-bold flex items-center space-x-1">
                        <span>💡 Informasi Import Hutang:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-[10px] text-indigo-700 dark:text-indigo-300">
                        <li>Supplier dicocokkan otomatis berdasarkan KODE atau NAMA di sistem.</li>
                        <li>Jika nama supplier belum terdaftar, sistem akan otomatis mendaftarkannya sebagai mitra supplier baru.</li>
                        <li>Sistem otomatis membuat jurnal akuntansi seimbang: <strong>Debit Modal Awal</strong> &amp; <strong>Kredit Hutang Usaha</strong>.</li>
                    </ul>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('accounting.opening.payables.template') }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                        Unduh Template (.xlsx)
                    </a>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="document.getElementById('modalImportPayables').classList.add('hidden')"
                                class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Batal</button>
                        <button type="submit"
                                class="px-5 py-2 text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-md shadow-indigo-500/20">Mulai Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
