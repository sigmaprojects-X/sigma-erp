<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $movement->movement_type === 'IN' ? 'Bukti Penerimaan Item Masuk' : 'Bukti Pengeluaran Item Keluar' }} - {{ $movement->movement_number }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1e293b;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-name {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
        }
        .company-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            text-align: right;
        }
        .doc-title h1 {
            font-size: 16px;
            margin: 0;
            font-weight: 900;
            text-transform: uppercase;
        }
        .doc-number {
            font-family: monospace;
            font-size: 13px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 3px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .info-row {
            display: flex;
            margin-bottom: 4px;
        }
        .info-label {
            width: 140px;
            font-weight: bold;
            color: #64748b;
        }
        .info-value {
            flex: 1;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }
        td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            font-size: 11px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 28%;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #0f172a;
            margin-top: 60px;
            margin-bottom: 4px;
        }
        .signature-title {
            font-size: 11px;
            font-weight: bold;
            color: #475569;
        }
        .no-print {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            font-size: 12px;
        }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak Dokumen</button>
        <button onclick="window.close()" class="btn btn-secondary">✕ Tutup</button>
    </div>

    <div class="header">
        <div>
            <div class="company-name">{{ $movement->company->name ?? 'SIGMA RETAIL INDONESIA' }}</div>
            <div class="company-sub">Cabang: {{ $movement->store->name ?? '-' }} ({{ $movement->store->code ?? '-' }})</div>
            <div class="company-sub">{{ $movement->store->address ?? 'Operasional Retail & Pergudangan' }}</div>
        </div>
        <div class="doc-title">
            <h1>{{ $movement->movement_type === 'IN' ? 'Bukti Penerimaan Item Masuk' : 'Bukti Pengeluaran Item Keluar' }}</h1>
            <div class="doc-number">{{ $movement->movement_number }}</div>
            <div class="company-sub">Tanggal: {{ \Carbon\Carbon::parse($movement->movement_date)->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div>
            <div class="info-row">
                <span class="info-label">Kategori Alasan:</span>
                <span class="info-value">{{ $movement->category_reason }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">{{ $movement->movement_type === 'IN' ? 'Sumber / Dari:' : 'Penerima / Untuk:' }}</span>
                <span class="info-value">{{ $movement->recipient_or_source ?: '-' }}</span>
            </div>
        </div>
        <div>
            <div class="info-row">
                <span class="info-label">Petugas / Operator:</span>
                <span class="info-value">{{ $movement->creator?->name ?? 'Sistem' }} ({{ $movement->creator?->role ?? '-' }})</span>
            </div>
            <div class="info-row">
                <span class="info-label">Catatan:</span>
                <span class="info-value">{{ $movement->notes ?: '-' }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th style="width: 120px;">Kode SKU</th>
                <th>Nama Barang / Produk</th>
                <th class="text-center" style="width: 90px;">Satuan</th>
                <th class="text-right" style="width: 100px;">Jumlah Qty</th>
                <th style="width: 140px;">Keterangan Item</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movement->items as $idx => $it)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="font-mono"><strong>{{ $it->product?->sku }}</strong></td>
                    <td><strong>{{ $it->product?->name }}</strong></td>
                    <td class="text-center">{{ $it->unit_code }}</td>
                    <td class="text-right font-mono" style="font-size: 13px; font-weight: bold;">
                        {{ $movement->movement_type === 'IN' ? '+' : '-' }}{{ (float) $it->quantity }}
                    </td>
                    <td>{{ $it->notes ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-title">Dibuat Oleh (Petugas)</div>
            <div class="signature-line"></div>
            <div>{{ $movement->creator?->name ?? 'Staf Gudang' }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Disetujui (Kepala Toko / SPV)</div>
            <div class="signature-line"></div>
            <div>( ..................................... )</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">{{ $movement->movement_type === 'IN' ? 'Diserahkan Oleh' : 'Diterima Oleh' }}</div>
            <div class="signature-line"></div>
            <div>{{ $movement->recipient_or_source ? $movement->recipient_or_source : '( ..................................... )' }}</div>
        </div>
    </div>

</body>
</html>
