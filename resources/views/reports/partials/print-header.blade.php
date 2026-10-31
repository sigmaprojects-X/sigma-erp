@php
    $cmp = $company ?? \App\Models\Company::where('code', 'SIGMA-HO')->first() ?? \App\Models\Company::first();
    $cmpName = $cmp?->name ?? 'PT SIGMA PROJECTS INDONESIA';
    $cmpAddress = $cmp?->address ?? 'Jl. Jendral Sudirman No. 88, Jakarta Pusat';
    $cmpPhone = $cmp?->phone ?? '021-5551234';
    $cmpEmail = $cmp?->email ?? 'hq@sigmamart.co.id';
    $cmpTaxId = $cmp?->tax_id ?? '01.234.567.8-901.000';
    $reportCode = $docCode ?? ('SIGMA/RPT/' . now()->format('Ymd') . '/' . strtoupper(substr(md5($title ?? 'REPORT'), 0, 4)));
    $authUserName = auth()->user()?->name ?? 'Administrator';
    $authUserRole = strtoupper(auth()->user()?->role ?? 'USER');
    $currentStore = auth()->user()?->store?->name ?? 'Kantor Pusat (Head Office)';
@endphp

<div class="hidden print:block mb-4 pb-2 border-b-2 border-slate-900 print-avoid-break">
    <!-- TOP STRIP: LOGO + COMPANY INFO & DOCUMENT AUDIT METADATA -->
    <div class="flex items-start justify-between gap-4 pb-2">
        <!-- COMPANY PROFILE -->
        <div class="flex items-start gap-3">
            <div class="h-11 w-11 rounded-lg border border-slate-900 bg-slate-900 text-white flex items-center justify-center font-black text-xl tracking-tighter shrink-0 select-none">
                Σ
            </div>
            <div>
                <div class="font-black text-base text-slate-900 tracking-tight leading-tight">
                    {{ strtoupper($cmpName) }}
                </div>
                <div class="text-[9pt] text-slate-700 font-semibold leading-tight mt-0.5">
                    Sistem Enterprise Resource Planning &amp; POS Ritel Modern
                </div>
                <div class="text-[8pt] text-slate-600 leading-tight mt-0.5">
                    {{ $cmpAddress }} • Telp: {{ $cmpPhone }} • Email: {{ $cmpEmail }}
                </div>
                <div class="text-[7.5pt] text-slate-500 font-mono mt-0.5">
                    NPWP: {{ $cmpTaxId }} • Unit Operasional: {{ $currentStore }}
                </div>
            </div>
        </div>

        <!-- DOCUMENT CONTROL BOX -->
        <div class="border border-slate-800 bg-slate-50/80 p-2 rounded text-[7.5pt] font-mono shrink-0 w-64 text-right">
            <div class="flex justify-between border-b border-slate-200 pb-0.5 mb-0.5">
                <span class="text-slate-500">KODE DOKUMEN:</span>
                <span class="font-bold text-slate-900">{{ $reportCode }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-0.5 mb-0.5">
                <span class="text-slate-500">WAKTU CETAK:</span>
                <span class="font-bold text-slate-900">{{ now()->translatedFormat('d M Y, H:i') }} WIB</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-0.5 mb-0.5">
                <span class="text-slate-500">PETUGAS / USER:</span>
                <span class="font-bold text-slate-900">{{ $authUserName }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">KLASIFIKASI:</span>
                <span class="font-bold text-emerald-800">DOKUMEN RESMI (VALID)</span>
            </div>
        </div>
    </div>

    <!-- DOUBLE RULE ACCENT -->
    <div class="border-t border-slate-300 pt-2">
        <div class="text-center">
            <h2 class="text-sm font-black uppercase tracking-wider text-slate-900 mb-0.5">
                {{ $title ?? 'LAPORAN SISTEM INFORMASI BISNIS' }}
            </h2>
            @if(isset($subtitle) && $subtitle)
                <p class="text-[8pt] text-slate-600 italic">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        <!-- REPORT METADATA STRIP (PERIODE, CABANG, DLL) -->
        <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-50 border border-slate-300 p-1.5 rounded text-[7.5pt]">
            <div>
                <span class="text-slate-500 block text-[6.5pt] uppercase font-bold">Periode Laporan:</span>
                <span class="font-bold text-slate-900">
                    {{ $periodLabel ?? ($period['label'] ?? (isset($startDate, $endDate) ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y') : '-')) }}
                </span>
            </div>
            <div>
                <span class="text-slate-500 block text-[6.5pt] uppercase font-bold">Cakupan Cabang:</span>
                <span class="font-bold text-slate-900">
                    {{ $storeName ?? ($store?->name ?? 'Semua Cabang (Konsolidasi Pusat)') }}
                </span>
            </div>
            @if(!empty($extraFilters))
                @foreach($extraFilters as $lbl => $val)
                    <div>
                        <span class="text-slate-500 block text-[6.5pt] uppercase font-bold">{{ $lbl }}:</span>
                        <span class="font-bold text-slate-900 truncate block">{{ $val }}</span>
                    </div>
                @endforeach
            @else
                <div>
                    <span class="text-slate-500 block text-[6.5pt] uppercase font-bold">Mata Uang:</span>
                    <span class="font-bold text-slate-900">IDR (Rupiah Indonesia)</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[6.5pt] uppercase font-bold">Basis Akuntansi:</span>
                    <span class="font-bold text-slate-900">Moving Average / Akrual</span>
                </div>
            @endif
        </div>
    </div>
</div>
