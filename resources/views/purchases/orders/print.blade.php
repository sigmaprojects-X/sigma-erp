<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 24px;
            line-height: 1.5;
        }
        .toolbar {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #1e293b;
            transition: all 0.15s ease;
        }
        .btn:hover {
            background: #f8fafc;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .document-box {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 36px 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .company-info h1 {
            font-size: 16pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .company-info p {
            font-size: 9pt;
            color: #475569;
            margin-top: 2px;
        }
        .doc-badge {
            text-align: right;
        }
        .doc-badge .tag {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            font-weight: 800;
            font-size: 12pt;
            padding: 4px 14px;
            border-radius: 4px;
            border: 1px solid #93c5fd;
            letter-spacing: 0.5px;
        }
        .doc-badge .po-no {
            font-family: monospace;
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: 6px;
        }
        .doc-badge .date {
            font-size: 9pt;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .meta-col h3 {
            font-size: 8.5pt;
            text-transform: uppercase;
            font-weight: 800;
            color: #64748b;
            margin-bottom: 6px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .meta-col p {
            font-size: 9.5pt;
            margin-bottom: 2px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 16px;
        }
        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 7px 10px;
            border: 1px solid #cbd5e1;
            font-size: 8.5pt;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 18px;
        }
        .summary-card {
            width: 320px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            overflow: hidden;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            font-size: 9.5pt;
            border-bottom: 1px dashed #e2e8f0;
        }
        .summary-row:last-child {
            border-bottom: none;
            background: #f8fafc;
            font-weight: 800;
            color: #1e3a8a;
            font-size: 11pt;
            border-top: 2px solid #3b82f6;
        }
        .terbilang-box {
            padding: 8px 12px;
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            font-size: 9pt;
            font-style: italic;
            color: #1e40af;
            margin-bottom: 20px;
        }
        .instructions-box {
            background: #fefce8;
            border: 1px solid #fef08a;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 8.5pt;
            color: #713f12;
            margin-bottom: 24px;
            line-height: 1.4;
        }
        .instructions-box h4 {
            font-weight: 700;
            font-size: 9pt;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .instructions-box ul {
            padding-left: 18px;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 32px;
            text-align: center;
        }
        .sig-space {
            height: 60px;
        }
        .sig-name {
            font-weight: 700;
            border-top: 1px solid #334155;
            padding-top: 4px;
            display: inline-block;
            min-width: 140px;
            font-size: 9pt;
        }
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                font-size: 9.5pt;
            }
            .toolbar {
                display: none !important;
            }
            .document-box {
                border: none !important;
                box-shadow: none !important;
                padding: 12mm 10mm !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('purchases.orders.show', $purchaseOrder->id) }}" class="btn">
            &larr; Kembali ke Detail PO
        </a>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('purchases.orders.index') }}" class="btn">
                📋 Daftar PO
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Surat Pesanan (PO A4)
            </button>
        </div>
    </div>

    <div class="document-box">
        <!-- Header Perusahaan -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $purchaseOrder->company->name ?? config('app.name', 'PT SIGMA RETAIL INDONESIA') }}</h1>
                <p><strong>{{ $purchaseOrder->store->name ?? 'Kantor Pusat & Gudang Distribusi' }}</strong></p>
                <p>{{ $purchaseOrder->store->address ?? 'Jl. Boulevard Retail No. 88, Jakarta' }}</p>
                <p>Telepon: {{ $purchaseOrder->store->phone ?? '-' }} | Email: {{ $purchaseOrder->store->email ?? '-' }}</p>
            </div>
            <div class="doc-badge">
                <div class="tag">PURCHASE ORDER</div>
                <div class="po-no">{{ $purchaseOrder->po_number }}</div>
                <div class="date">Tanggal PO: {{ $purchaseOrder->order_date ? $purchaseOrder->order_date->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</div>
                <div class="date">Status: <strong>{{ $purchaseOrder->status }}</strong></div>
            </div>
        </div>

        <!-- Meta Vendor & Pengiriman -->
        <div class="meta-section">
            <div class="meta-col">
                <h3>Kepada Yth. Pemasok (Vendor):</h3>
                <p><strong>{{ $purchaseOrder->supplier->name ?? 'Supplier Umum' }}</strong></p>
                @if($purchaseOrder->supplier)
                    <p>{{ $purchaseOrder->supplier->address ?: 'Alamat tidak terdata' }}</p>
                    <p>Telepon: {{ $purchaseOrder->supplier->phone ?: '-' }}</p>
                    <p>PIC: {{ $purchaseOrder->supplier->contact_person ?: '-' }}</p>
                    @if($purchaseOrder->supplier->email)
                        <p>Email: {{ $purchaseOrder->supplier->email }}</p>
                    @endif
                @endif
            </div>
            <div class="meta-col">
                <h3>Kirim Ke (Tujuan Pengiriman & Gudang):</h3>
                <p><strong>{{ $purchaseOrder->store->name ?? 'Gudang Utama' }}</strong></p>
                <p>{{ $purchaseOrder->store->address ?? '-' }}</p>
                <p>Kontak Gudang: {{ $purchaseOrder->store->phone ?? '-' }}</p>
                <p><strong>Estimasi Tanggal Tiba:</strong> 
                    {{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->translatedFormat('d F Y') : 'Sesuai Kesepakatan' }}
                </p>
            </div>
        </div>

        <!-- Tabel Item Pesanan -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">No</th>
                    <th style="width: 100px;">SKU / Barcode</th>
                    <th>Nama Barang / Deskripsi</th>
                    <th class="text-center" style="width: 90px;">Jumlah</th>
                    <th class="text-right" style="width: 110px;">Harga Satuan</th>
                    <th class="text-right" style="width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-size: 8.5pt;">{{ $item->product->sku ?? '-' }}</td>
                    <td>
                        <strong>{{ $item->product->name ?? 'Barang' }}</strong>
                        @if($item->product && $item->product->barcode)
                            <span style="font-size: 8pt; color: #64748b; font-family: monospace;">({{ $item->product->barcode }})</span>
                        @endif
                    </td>
                    <td class="text-center font-bold">
                        {{ number_format((float) $item->quantity_ordered, 0, ',', '.') }} {{ $item->productUnit->unit_code ?? 'Pcs' }}
                    </td>
                    <td class="text-right font-mono">
                        Rp {{ number_format((float) $item->estimated_cost, 0, ',', '.') }}
                    </td>
                    <td class="text-right font-mono font-bold">
                        Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Ringkasan Nilai PO -->
        <div class="summary-wrap">
            <div class="summary-card">
                <div class="summary-row">
                    <span>Subtotal Nilai Barang:</span>
                    <span>Rp {{ number_format((float) $purchaseOrder->subtotal, 0, ',', '.') }}</span>
                </div>
                @if((float) $purchaseOrder->tax_amount > 0)
                <div class="summary-row">
                    <span>Pajak (PPN):</span>
                    <span>Rp {{ number_format((float) $purchaseOrder->tax_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="summary-row">
                    <span>TOTAL NILAI PO:</span>
                    <span>Rp {{ number_format((float) $purchaseOrder->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="terbilang-box">
            <strong>Terbilang:</strong> <em># {{ $terbilang }} #</em>
        </div>

        <!-- Instruksi Khusus & Syarat Penerimaan -->
        <div class="instructions-box">
            <h4>Syarat &amp; Ketentuan Penerimaan Barang:</h4>
            <ul>
                <li>Setiap pengiriman fisik barang WAJIB menyertakan Surat Jalan resmi yang mencantumkan Nomor PO ini: <strong>{{ $purchaseOrder->po_number }}</strong>.</li>
                <li>Barang akan diperiksa oleh petugas gudang. Barang yang rusak, cacat, atau tidak sesuai spesifikasi akan ditolak atau diretur.</li>
                <li>Penagihan pembayaran harus melampirkan Faktur Pajak (bila PKP), kwitansi asli, dan salinan Laporan Penerimaan Barang (LPB) yang telah ditandatangani gudang.</li>
            </ul>
        </div>

        @if($purchaseOrder->notes)
        <div style="font-size: 8.5pt; color: #475569; margin-bottom: 20px;">
            <strong>Catatan Tambahan:</strong> {{ $purchaseOrder->notes }}
        </div>
        @endif

        <!-- Tanda Tangan 3 Pihak Resmi -->
        <div class="signatures">
            <div>
                <p>Dibuat Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Bagian Pengadaan / Purchasing</p>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $purchaseOrder->creator?->name ?? '( Staff Purchasing )' }}</div>
            </div>
            <div>
                <p>Disetujui Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Manajer Toko / Direktur</p>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $purchaseOrder->approver?->name ?? '( Store Manager )' }}</div>
            </div>
            <div>
                <p>Dikonfirmasi Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Pihak Pemasok / Supplier</p>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $purchaseOrder->supplier?->contact_person ?: 'Tanda Tangan & Cap' }} )</div>
            </div>
        </div>
    </div>

</body>
</html>
