<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penerimaan Barang - {{ $receipt->receipt_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            color: #1e293b;
            padding: 24px;
            font-size: 12px;
            line-height: 1.5;
        }

        /* TOOLBAR ATAS (TIDAK DICETAK) */
        .no-print-toolbar {
            max-width: 820px;
            margin: 0 auto 20px auto;
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 13px;
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-print {
            background-color: #2563eb;
            color: white;
        }
        .btn-print:hover { background-color: #1d4ed8; }

        .btn-back {
            background-color: #475569;
            color: white;
        }
        .btn-back:hover { background-color: #334155; }

        /* LEMBAR DOKUMEN CETAK (A4 STANDARD) */
        .document-sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            border: 1px solid #cbd5e1;
        }

        /* KOP SURAT */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .company-info h1 {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }

        .company-info p {
            color: #64748b;
            font-size: 11px;
            line-height: 1.4;
        }

        .doc-title-box {
            text-align: right;
        }

        .doc-title-box h2 {
            font-size: 16px;
            font-weight: 900;
            color: #2563eb;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .doc-number {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        /* METADATA GRID */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
            padding: 14px 18px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .meta-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .meta-row {
            display: flex;
            font-size: 11.5px;
        }

        .meta-label {
            width: 140px;
            color: #64748b;
            font-weight: 600;
        }

        .meta-value {
            flex: 1;
            color: #0f172a;
            font-weight: 700;
        }

        /* TABEL BARANG */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        table.items-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 800;
            font-size: 10.5px;
            text-transform: uppercase;
            padding: 9px 10px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }

        table.items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            vertical-align: top;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: ui-monospace, monospace; }

        /* CATATAN */
        .notes-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 28px;
            font-size: 11px;
        }

        .notes-box strong {
            color: #334155;
            display: block;
            margin-bottom: 2px;
        }

        /* TANDA TANGAN 3 PIHAK */
        .signatures-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-top: 36px;
            text-align: center;
        }

        .signature-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 8px;
            background: #fafafa;
        }

        .sig-title {
            font-weight: 700;
            color: #475569;
            font-size: 11px;
            margin-bottom: 60px;
        }

        .sig-name {
            font-weight: 800;
            color: #0f172a;
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            margin: 0 12px;
            font-size: 11px;
        }

        .sig-role {
            font-size: 10px;
            color: #64748b;
        }

        /* FOOTER CETAK */
        .footer-print {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #94a3b8;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none;
            }
            .document-sheet {
                box-shadow: none;
                border: none;
                padding: 10px 20px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR (NON-PRINT) -->
    <div class="no-print-toolbar">
        <div class="toolbar-title">
            <span>📦 Preview Cetak Surat Laporan Penerimaan Barang (LPB)</span>
        </div>
        <div class="toolbar-actions">
            <button onclick="window.print()" class="btn btn-print">
                🖨️ Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-back">
                Tutup
            </button>
        </div>
    </div>

    <!-- LEMBAR DOKUMEN -->
    <div class="document-sheet">

        <!-- KOP SURAT -->
        <div class="header-section">
            <div class="company-info">
                <h1>{{ $receipt->company->name ?? 'SIGMA RETAIL INDONESIA' }}</h1>
                <p>{{ $receipt->store->name ?? 'Gudang Pusat' }}</p>
                <p>{{ $receipt->store->address ?? 'Pusat Distribusi & Logistik' }}</p>
            </div>
            <div class="doc-title-box">
                <h2>SURAT PENERIMAAN BARANG</h2>
                <div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">GOODS RECEIPT NOTE (LPB)</div>
                <div class="doc-number">No: {{ $receipt->receipt_number }}</div>
            </div>
        </div>

        <!-- METADATA DOKUMEN -->
        <div class="meta-grid">
            <div class="meta-group">
                <div class="meta-row">
                    <span class="meta-label">No. Surat Jalan (DO):</span>
                    <span class="meta-value">{{ $receipt->delivery_order_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Pemasok / Vendor:</span>
                    <span class="meta-value">{{ $receipt->supplier->name }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">No. Purchase Order:</span>
                    <span class="meta-value">{{ $receipt->purchaseOrder?->po_number ?? '-' }}</span>
                </div>
            </div>
            <div class="meta-group">
                <div class="meta-row">
                    <span class="meta-label">Tanggal Masuk:</span>
                    <span class="meta-value">{{ $receipt->receipt_date?->format('d/m/Y') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Lokasi Gudang:</span>
                    <span class="meta-value">{{ $receipt->store->name }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Petugas Penerima:</span>
                    <span class="meta-value">{{ $receipt->receiver?->name ?? 'Staf Gudang' }}</span>
                </div>
            </div>
        </div>

        <!-- TABEL BARANG DITERIMA -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">No</th>
                    <th style="width: 100px;">SKU</th>
                    <th>Deskripsi Barang</th>
                    <th class="text-center" style="width: 60px;">Satuan</th>
                    <th class="text-center" style="width: 80px;">Qty Diterima</th>
                    <th class="text-center" style="width: 110px;">Batch / Exp</th>
                    <th class="text-center" style="width: 90px;">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->items as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="font-mono">{{ $item->product->sku }}</td>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                            @if($item->notes)
                                <div style="font-size: 10px; color: #64748b;">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->unit_code }}</td>
                        <td class="text-center" style="font-weight: 800; font-size: 12px;">
                            {{ number_format($item->quantity_received, 0, ',', '.') }}
                        </td>
                        <td class="text-center font-mono" style="font-size: 10.5px;">
                            {{ $item->batch_number ?? '-' }}
                            @if($item->expiry_date)
                                <div style="font-size: 9.5px; color: #64748b;">Exp: {{ $item->expiry_date->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="text-center" style="font-weight: 600;">
                            {{ $item->physical_condition ?? 'BAIK' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- CATATAN DOKUMEN -->
        <div class="notes-box">
            <strong>Catatan Tambahan Penerimaan Gudang:</strong>
            {{ $receipt->notes ?: 'Barang telah diterima dan diperiksa secara fisik sesuai dengan Surat Jalan yang terlampir.' }}
        </div>

        <!-- TANDA TANGAN 3 PIHAK -->
        <div class="signatures-section">
            <div class="signature-box">
                <div class="sig-title">Diserahkan Oleh (Supir / Kurir Vendor)</div>
                <div class="sig-name">( ....................................... )</div>
                <div class="sig-role">Ekspedisi / Supplier</div>
            </div>

            <div class="signature-box">
                <div class="sig-title">Diterima & Diperiksa Oleh</div>
                <div class="sig-name">{{ $receipt->receiver?->name ?? '( ....................................... )' }}</div>
                <div class="sig-role">Petugas Gudang Logistik</div>
            </div>

            <div class="signature-box">
                <div class="sig-title">Mengetahui & Menyetujui</div>
                <div class="sig-name">( ....................................... )</div>
                <div class="sig-role">Kepala Gudang / Store Manager</div>
            </div>
        </div>

        <!-- FOOTER DOKUMEN -->
        <div class="footer-print">
            <span>Dicetak secara otomatis oleh SIGMA ERP &amp; POS pada {{ now()->format('d/m/Y H:i:s') }}</span>
            <span>Halaman 1 dari 1 • Dokumen Sah Bukti Fisik Pergudangan</span>
        </div>

    </div>

</body>
</html>
