<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan Transfer {{ $transfer->transfer_number }} - {{ $company->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 10mm 10mm 10mm;
            }
            body {
                background: white !important;
                color: #0f172a !important;
                font-size: 8.5pt !important;
                line-height: 1.35 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-table th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                border: 1px solid #475569 !important;
            }
            .print-table td {
                border: 1px solid #94a3b8 !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 font-sans antialiased p-4 sm:p-8">

    <!-- FLOATING ACTION BUTTONS (NO-PRINT) -->
    <div class="max-w-4xl mx-auto mb-4 flex items-center justify-between no-print">
        <a href="{{ route('warehouse.transfers.show', $transfer->id) }}"
           class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-xs flex items-center gap-1.5 transition-colors cursor-pointer">
            <span>&larr;</span>
            <span>Kembali ke Detail Transfer</span>
        </a>
        <button onclick="window.print()"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-2 transition-transform active:scale-95 cursor-pointer">
            <span>🖨️</span>
            <span>CETAK SURAT JALAN [PRINT]</span>
        </button>
    </div>

    <!-- MAIN PAPER SHEET (A4 CONTAINER) -->
    <div class="max-w-4xl mx-auto bg-white border border-slate-200 shadow-lg rounded-2xl p-6 sm:p-8 print:border-none print:shadow-none print:p-0 print:rounded-none">

        <!-- KOP SURAT RESMI PERUSAHAAN -->
        <div class="flex items-start justify-between pb-4 border-b-2 border-slate-900 gap-4">
            <div class="flex items-center gap-3">
                @if($company->logo_url)
                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }}" class="h-16 w-auto object-contain">
                @else
                    <div class="w-12 h-12 bg-slate-900 text-white rounded-xl flex items-center justify-center font-black text-xl">
                        Σ
                    </div>
                @endif
                <div>
                    <h1 class="text-base font-black text-slate-900 uppercase tracking-tight">{{ $company->name }}</h1>
                    <p class="text-xs text-slate-600 max-w-md leading-tight mt-0.5">
                        {{ $company->address ?: 'Pusat Distribusi Retail & Logistik Multi-Store' }}
                    </p>
                    <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                        @if($company->phone) Telp: {{ $company->phone }} @endif
                        @if($company->whatsapp) | WA: {{ $company->whatsapp }} @endif
                        @if($company->email) | Email: {{ $company->email }} @endif
                    </div>
                </div>
            </div>

            <!-- TITLE & BADGE STATUS -->
            <div class="text-right shrink-0">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black uppercase tracking-wider rounded-md">
                    SURAT JALAN PENGIRIMAN
                </span>
                <div class="text-[10px] text-slate-400 font-mono mt-1">INTERNAL STOCK TRANSFER NOTE</div>
                <div class="mt-1 font-mono font-black text-sm text-blue-700">{{ $transfer->transfer_number }}</div>
            </div>
        </div>

        <!-- METADATA PENGIRIMAN & CABANG -->
        <div class="grid grid-cols-2 gap-6 my-4 text-xs">
            <!-- CABANG PENGIRIM (ASAL) -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Lokasi Asal (Pengirim):</span>
                <div class="font-black text-slate-900 text-sm">{{ $transfer->sourceStore->name }}</div>
                <div class="text-slate-600 leading-tight">{{ $transfer->sourceStore->address ?: 'Alamat belum diset' }}</div>
                <div class="text-slate-500 pt-1">
                    <span class="font-semibold">PIC Asal:</span> {{ $transfer->sourceStore->pic_name ?: ($transfer->dispatcher ? $transfer->dispatcher->name : 'Kepala Gudang') }}
                    @if($transfer->sourceStore->phone) | Telp: {{ $transfer->sourceStore->phone }} @endif
                </div>
            </div>

            <!-- CABANG PENERIMA (TUJUAN) & LOGISTIK -->
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">Lokasi Tujuan (Penerima):</span>
                <div class="font-black text-blue-900 text-sm">{{ $transfer->destinationStore->name }}</div>
                <div class="text-slate-600 leading-tight">{{ $transfer->destinationStore->address ?: 'Alamat belum diset' }}</div>
                <div class="text-slate-500 pt-1">
                    <span class="font-semibold">PIC Tujuan:</span> {{ $transfer->destinationStore->pic_name ?: 'Kepala Toko' }}
                    @if($transfer->destinationStore->phone) | Telp: {{ $transfer->destinationStore->phone }} @endif
                </div>
            </div>
        </div>

        <!-- INFO DOKUMEN & KENDARAAN -->
        <div class="grid grid-cols-4 gap-3 bg-slate-100/70 border border-slate-200 rounded-lg p-2.5 text-[11px] mb-4">
            <div>
                <span class="text-slate-500 block">Tanggal Dokumen:</span>
                <span class="font-bold text-slate-800">{{ $transfer->transfer_date ? $transfer->transfer_date->isoFormat('D MMMM Y') : date('d/m/Y') }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Waktu Kirim (Dispatch):</span>
                <span class="font-bold text-slate-800">{{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d/m/Y H:i') : 'Belum Berangkat' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">Nama Supir / Driver:</span>
                <span class="font-bold text-slate-800">{{ $transfer->driver_name ?: '-' }}</span>
            </div>
            <div>
                <span class="text-slate-500 block">No. Polisi Kendaraan:</span>
                <span class="font-mono font-bold text-slate-800">{{ $transfer->vehicle_number ?: '-' }}</span>
            </div>
        </div>

        <!-- TABEL RINCIAN BARANG PENGIRIMAN -->
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-left text-xs border-collapse print-table">
                <thead>
                    <tr class="bg-slate-100 border-y-2 border-slate-800 text-slate-900 font-black uppercase text-[10.5px]">
                        <th class="py-2.5 px-3 w-8 text-center border border-slate-300">No</th>
                        <th class="py-2.5 px-3 border border-slate-300">Kode / Barcode</th>
                        <th class="py-2.5 px-3 border border-slate-300">Nama Barang &amp; Deskripsi</th>
                        <th class="py-2.5 px-3 text-right border border-slate-300 w-24">Qty Kirim</th>
                        <th class="py-2.5 px-3 text-center border border-slate-300 w-20">Satuan</th>
                        <th class="py-2.5 px-3 text-right border border-slate-300 w-28">Konversi Satuan</th>
                        <th class="py-2.5 px-3 text-center border border-slate-300 w-24">Cek Fisik</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-medium text-slate-800">
                    @forelse($transfer->items as $index => $item)
                        <tr class="border-b border-slate-200">
                            <td class="py-2 px-3 text-center font-bold text-slate-500 border border-slate-200">{{ $index + 1 }}</td>
                            <td class="py-2 px-3 font-mono font-bold text-slate-700 border border-slate-200">
                                <div>{{ $item->product->sku ?: '-' }}</div>
                                @if(optional($item->product->barcodes->first())->barcode)
                                    <div class="text-[10px] text-slate-400 font-mono">{{ optional($item->product->barcodes->first())->barcode }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-3 border border-slate-200">
                                <div class="font-black text-slate-900">{{ $item->product->name }}</div>
                                @if($item->notes)
                                    <div class="text-[10px] text-slate-500 italic mt-0.5">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="py-2 px-3 text-right font-mono font-black text-slate-900 border border-slate-200 text-sm">
                                {{ number_format($item->quantity_sent, 2) }}
                            </td>
                            <td class="py-2 px-3 text-center font-bold text-slate-700 border border-slate-200">
                                {{ $item->productUnit ? $item->productUnit->unit_code : $item->product->base_unit }}
                            </td>
                            <td class="py-2 px-3 text-right font-mono text-slate-600 border border-slate-200 text-[11px]">
                                {{ number_format($item->base_quantity_sent, 2) }} {{ $item->product->base_unit }}
                            </td>
                            <td class="py-2 px-3 text-center border border-slate-200">
                                <div class="w-4 h-4 mx-auto border border-slate-400 rounded-sm"></div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400 border border-slate-200">
                                Tidak ada item barang yang terdaftar dalam transfer ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 border-t-2 border-slate-800 font-black text-xs">
                        <td colspan="3" class="py-2 px-3 text-right uppercase border border-slate-300">Total Kuantitas Kirim:</td>
                        <td class="py-2 px-3 text-right font-mono text-sm font-black border border-slate-300">
                            {{ number_format($transfer->items->sum('quantity_sent'), 2) }}
                        </td>
                        <td colspan="3" class="py-2 px-3 border border-slate-300"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- CATATAN & INSTRUKSI PENGIRIMAN -->
        @if($transfer->notes)
            <div class="mb-6 p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                <span class="font-bold text-slate-700">Catatan Khusus Pengiriman:</span>
                <p class="text-slate-600 mt-0.5 leading-relaxed">{{ $transfer->notes }}</p>
            </div>
        @endif

        <!-- PERNYATAAN SERAH TERIMA & 3 KOLOM TANDA TANGAN -->
        <div class="mt-8 pt-4 border-t border-slate-200 text-xs">
            <p class="text-[11px] text-slate-500 italic mb-4 leading-relaxed">
                * Barang telah diperiksa dan diserahterimakan dalam kondisi baik, tersegel, dan sesuai dengan rincian kuantitas di atas.
            </p>

            <div class="grid grid-cols-3 gap-6 text-center">
                <!-- TANDA TANGAN PENGIRIM -->
                <div class="space-y-14">
                    <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">Petugas Pengirim (Asal)</span>
                    <div class="border-t border-slate-800 pt-1 font-bold text-slate-900">
                        ( {{ $transfer->dispatcher ? $transfer->dispatcher->name : ($transfer->creator ? $transfer->creator->name : '..................................') }} )
                    </div>
                </div>

                <!-- TANDA TANGAN PENGEMUDI -->
                <div class="space-y-14">
                    <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">Pengemudi / Supir</span>
                    <div class="border-t border-slate-800 pt-1 font-bold text-slate-900 font-mono">
                        ( {{ $transfer->driver_name ?: '..................................' }} )
                    </div>
                </div>

                <!-- TANDA TANGAN PENERIMA -->
                <div class="space-y-14">
                    <span class="font-bold text-slate-700 uppercase tracking-wider text-[11px] block">Petugas Penerima (Tujuan)</span>
                    <div class="border-t border-slate-800 pt-1 font-bold text-slate-900">
                        ( {{ $transfer->receiver ? $transfer->receiver->name : '..................................' }} )
                    </div>
                </div>
            </div>
        </div>

        <!-- WATERMARK REPRINT FOOTER -->
        <div class="mt-8 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-mono">
            <span>SIGMA ERP &amp; POS Multi-Store Logistics</span>
            <span>Dicetak pada: {{ now()->isoFormat('D MMMM Y, HH:mm:ss') }} | Ref: {{ $transfer->id }}</span>
        </div>

    </div>

</body>
</html>
