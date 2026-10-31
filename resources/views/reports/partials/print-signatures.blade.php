<div class="hidden print:block mt-6 pt-3 border-t border-slate-300 print-avoid-break">
    <div class="text-[7pt] text-slate-500 italic mb-2">
        * Dokumen ini dibuat dan divalidasi secara otomatis melalui modul SIGMA Enterprise ERP. Seluruh angka finansial telah diverifikasi dengan pencatatan jurnal akuntansi ganda (Double-Entry Bookkeeping).
    </div>

    <!-- 3-TIER SIGNATURE COLUMNS -->
    <div class="grid grid-cols-3 gap-4 text-center text-[8pt]">
        <!-- 1. DIBUAT OLEH -->
        <div class="border border-slate-300 rounded p-2 bg-white">
            <div class="text-[7pt] font-bold text-slate-500 uppercase tracking-wider mb-0.5">
                Dibuat Oleh:
            </div>
            <div class="text-[6.5pt] text-slate-400 mb-10">
                Tanggal: {{ now()->translatedFormat('d F Y') }}
            </div>
            <div class="border-b border-slate-800 mx-4"></div>
            <div class="font-bold text-slate-900 mt-1">
                {{ auth()->user()?->name ?? 'Staff Administrasi' }}
            </div>
            <div class="text-[7pt] text-slate-600 font-medium">
                {{ strtoupper(auth()->user()?->role ?? 'OPERATOR') }}
            </div>
        </div>

        <!-- 2. DIPERIKSA OLEH -->
        <div class="border border-slate-300 rounded p-2 bg-white">
            <div class="text-[7pt] font-bold text-slate-500 uppercase tracking-wider mb-0.5">
                Diperiksa Oleh:
            </div>
            <div class="text-[6.5pt] text-slate-400 mb-10">
                Tanggal: ........................................
            </div>
            <div class="border-b border-slate-800 mx-4"></div>
            <div class="font-bold text-slate-900 mt-1">
                ( .................................................... )
            </div>
            <div class="text-[7pt] text-slate-600 font-medium">
                Manager Keuangan &amp; Akuntansi
            </div>
        </div>

        <!-- 3. DISETUJUI OLEH -->
        <div class="border border-slate-300 rounded p-2 bg-white">
            <div class="text-[7pt] font-bold text-slate-500 uppercase tracking-wider mb-0.5">
                Disetujui Oleh:
            </div>
            <div class="text-[6.5pt] text-slate-400 mb-10">
                Tanggal: ........................................
            </div>
            <div class="border-b border-slate-800 mx-4"></div>
            <div class="font-bold text-slate-900 mt-1">
                ( .................................................... )
            </div>
            <div class="text-[7pt] text-slate-600 font-medium">
                Pimpinan / Direktur Operasional
            </div>
        </div>
    </div>

    <!-- PRINT TIMESTAMP & SYSTEM SIGNATURE -->
    <div class="mt-2 flex items-center justify-between text-[6.5pt] text-slate-400 font-mono">
        <div>SIGMA ERP Enterprise • ISO 9001 Compliant Audit Trail</div>
        <div>Halaman Dicetak Otomatis dari Host: {{ gethostname() ?: 'SIGMA-PROD' }}</div>
    </div>
</div>
