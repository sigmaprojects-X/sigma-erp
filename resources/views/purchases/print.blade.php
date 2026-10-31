<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Pembelian - {{ $purchase->reference_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 24px;
            line-height: 1.45;
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
            background: #0f766e;
            color: #fff;
            border-color: #0d9488;
        }
        .btn-primary:hover {
            background: #115e59;
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
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-info h1 {
            font-size: 15pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .company-info p {
            font-size: 8.5pt;
            color: #475569;
            margin-top: 1px;
        }
        .doc-badge {
            text-align: right;
        }
        .doc-badge .tag {
            display: inline-block;
            background: #ccfbf1;
            color: #0f766e;
            font-weight: 800;
            font-size: 11pt;
            padding: 3px 12px;
            border-radius: 4px;
            border: 1px solid #99f6e4;
            letter-spacing: 0.5px;
        }
        .doc-badge .ref-no {
            font-family: monospace;
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
        }
        .doc-badge .sub-no {
            font-size: 8.5pt;
            color: #64748b;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 20px;
            margin-bottom: 16px;
        }
        .card-meta {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
        }
        .card-meta h3 {
            font-size: 8pt;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .meta-row {
            display: flex;
            font-size: 8.5pt;
            margin-bottom: 3px;
        }
        .meta-label {
            width: 110px;
            color: #64748b;
            font-weight: 500;
        }
        .meta-val {
            flex: 1;
            color: #0f172a;
            font-weight: 600;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .table-items th {
            background: #f1f5f9;
            color: #334155;
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            letter-spacing: 0.3px;
        }
        .table-items td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 8.5pt;
        }
        .table-items tr:nth-child(even) td {
            background: #fafafa;
        }
        .col-center { text-align: center; }
        .col-right { text-align: right; }
        .font-mono { font-family: monospace; }
        
        .summary-section {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .terbilang-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 8.5pt;
            color: #166534;
        }
        .terbilang-box strong {
            display: block;
            font-size: 7.5pt;
            text-transform: uppercase;
            color: #15803d;
            margin-bottom: 3px;
        }
        .notes-box {
            margin-top: 8px;
            font-size: 8pt;
            color: #475569;
            border-left: 2px solid #cbd5e1;
            padding-left: 8px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 3px 0;
            font-size: 8.5pt;
        }
        .totals-table .label {
            color: #475569;
        }
        .totals-table .val {
            text-align: right;
            font-weight: 600;
            font-family: monospace;
            color: #0f172a;
        }
        .totals-table .grand-row td {
            border-top: 1px solid #0f172a;
            border-bottom: 2px solid #0f172a;
            padding: 6px 0;
            font-weight: 800;
            font-size: 10pt;
            color: #0f172a;
        }
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .sig-box {
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 8.5pt;
            color: #334155;
        }
        .sig-space {
            height: 55px;
        }
        .sig-title {
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }
        .watermark {
            margin-top: 24px;
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                font-size: 9pt;
            }
            .toolbar { display: none !important; }
            .document-box {
                border: none;
                box-shadow: none;
                padding: 15px 20px;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('purchases.show', $purchase->id) }}" class="btn">
            &larr; Kembali ke Detail Faktur
        </a>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('purchases.shelf-tags', $purchase->id) }}" target="_blank" class="btn">
                🏷️ Cetak Label Rak
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Faktur Pembelian (A4)
            </button>
        </div>
    </div>

    <div class="document-box">
        <!-- HEADER KOP USAHA -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $purchase->company?->name ?? 'SIGMA RETAIL & GROSIR' }}</h1>
                <p><strong>{{ $purchase->store?->name ?? 'Gudang Pusat / HO' }}</strong></p>
                <p>{{ $purchase->store?->address ?? ($purchase->company?->address ?? 'Jl. Raya Pusat Perdagangan No. 1') }}</p>
                <p>Telp: {{ $purchase->store?->phone ?? '-' }} | Email: {{ $purchase->company?->email ?? '-' }}</p>
            </div>
            <div class="doc-badge">
                <div class="tag">FAKTUR PEMBELIAN</div>
                <div class="ref-no">{{ $purchase->reference_number }}</div>
                <div class="sub-no">No. Supplier: <strong>{{ $purchase->invoice_number }}</strong></div>
            </div>
        </div>

        <!-- META GRID -->
        <div class="meta-grid">
            <!-- Pemasok / Supplier -->
            <div class="card-meta">
                <h3>🏭 Data Pemasok / Supplier</h3>
                <div class="meta-row">
                    <span class="meta-label">Nama Supplier</span>
                    <span class="meta-val">
                        {{ $purchase->supplier?->name ?? '-' }}
                        @if($purchase->supplier?->is_pkp)
                            <span style="display: inline-block; background: #dbeafe; color: #1e40af; font-size: 7.5pt; font-weight: 800; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">PKP</span>
                        @else
                            <span style="display: inline-block; background: #f1f5f9; color: #475569; font-size: 7.5pt; font-weight: 700; padding: 1px 5px; border-radius: 3px; margin-left: 4px;">Non-PKP</span>
                        @endif
                    </span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Kode / Kontak</span>
                    <span class="meta-val">{{ $purchase->supplier?->code ?? '-' }} | {{ $purchase->supplier?->contact_person ?? '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Telepon / HP</span>
                    <span class="meta-val">{{ $purchase->supplier?->phone ?? '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Alamat</span>
                    <span class="meta-val">{{ $purchase->supplier?->address ?? '-' }}</span>
                </div>
                @if($purchase->supplier?->npwp)
                    <div class="meta-row">
                        <span class="meta-label">NPWP Supplier</span>
                        <span class="meta-val font-mono">{{ $purchase->supplier->npwp }}</span>
                    </div>
                @endif
            </div>

            <!-- Detail Transaksi & Pembayaran -->
            <div class="card-meta">
                <h3>📄 Rincian Penerimaan &amp; Pembayaran</h3>
                <div class="meta-row">
                    <span class="meta-label">Tanggal Faktur</span>
                    <span class="meta-val">{{ $purchase->purchase_date->format('d/m/Y') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Metode Bayar</span>
                    <span class="meta-val">
                        @if($purchase->payment_type === 'CASH')
                            💵 KAS TUNAI
                        @elseif($purchase->payment_type === 'TRANSFER')
                            🏦 TRANSFER BANK ({{ $purchase->bankAccount?->bank_name ?: 'BANK' }})
                        @else
                            ⏳ TEMPO (Hutang Dagang)
                        @endif
                    </span>
                </div>
                @if($purchase->payment_type === 'TEMPO' && $purchase->due_date)
                    <div class="meta-row">
                        <span class="meta-label">Jatuh Tempo</span>
                        <span class="meta-val" style="color: #b91c1c;">
                            {{ $purchase->due_date->format('d/m/Y') }}
                            ({{ $purchase->due_date->diffInDays($purchase->purchase_date) }} Hari)
                        </span>
                    </div>
                @endif
                <div class="meta-row">
                    <span class="meta-label">Gudang Penerima</span>
                    <span class="meta-val">{{ $purchase->store?->name ?? 'Gudang Utama' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Pencatat / Staff</span>
                    <span class="meta-val">{{ $purchase->creator?->name ?? 'Staff Purchasing' }}</span>
                </div>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="table-items">
            <thead>
                <tr>
                    <th style="width: 30px;" class="col-center">No</th>
                    <th style="width: 100px;">SKU / Barcode</th>
                    <th>Nama Barang / Produk</th>
                    <th style="width: 70px;" class="col-center">Satuan</th>
                    <th style="width: 50px;" class="col-center">Qty</th>
                    <th style="width: 90px;" class="col-right">Harga Beli</th>
                    <th style="width: 55px;" class="col-center">Disc (%)</th>
                    <th style="width: 105px;" class="col-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $idx => $it)
                    <tr>
                        <td class="col-center font-mono">{{ $idx + 1 }}</td>
                        <td class="font-mono" style="font-size: 8pt; color: #475569;">
                            {{ $it->product?->sku ?? '-' }}
                        </td>
                        <td>
                            <strong>{{ $it->product?->name ?? '-' }}</strong>
                        </td>
                        <td class="col-center">
                            {{ $it->productUnit?->unit_name ?? ($it->unit_code ?? 'PCS') }}
                        </td>
                        <td class="col-center font-mono" style="font-weight: 700;">
                            {{ number_format($it->quantity, 0, ',', '.') }}
                        </td>
                        <td class="col-right font-mono">
                            {{ number_format($it->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="col-center font-mono">
                            {{ (float) $it->discount_percent > 0 ? number_format($it->discount_percent, 1) . '%' : '-' }}
                        </td>
                        <td class="col-right font-mono" style="font-weight: 700;">
                            {{ number_format($it->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- SUMMARY & SIGNATURES -->
        <div class="summary-section">
            <div>
                <div class="terbilang-box">
                    <strong>Terbilang:</strong>
                    <em># {{ $terbilang }} #</em>
                </div>
                @if($purchase->notes)
                    <div class="notes-box">
                        <strong>Catatan:</strong> {{ $purchase->notes }}
                    </div>
                @endif
            </div>

            <div>
                <table class="totals-table">
                    <tr>
                        <td class="label">Subtotal Barang</td>
                        <td class="val">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if((float) $purchase->discount_amount > 0)
                        <tr>
                            <td class="label">Diskon Faktur</td>
                            <td class="val" style="color: #16a34a;">- Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if((float) ($purchase->shipping_cost ?? 0) > 0)
                        <tr>
                            <td class="label">Ongkos Kirim (Landed Cost)</td>
                            <td class="val">+ Rp {{ number_format($purchase->shipping_cost, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if((float) $purchase->tax_amount > 0)
                        <tr>
                            <td class="label">Pajak PPN Masukan</td>
                            <td class="val">+ Rp {{ number_format($purchase->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-row">
                        <td class="label">TOTAL AKHIR</td>
                        <td class="val">Rp {{ number_format($purchase->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    @if($purchase->payment_type === 'TEMPO')
                        <tr>
                            <td class="label" style="padding-top: 6px;">Telah Dibayar (DP/Cicil)</td>
                            <td class="val" style="padding-top: 6px; color: #16a34a;">Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label"><strong>Sisa Hutang Dagang</strong></td>
                            <td class="val" style="color: #b91c1c; font-weight: 800;">Rp {{ number_format($remainingDebt, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- 3 KOLOM TANDA TANGAN FORMAL -->
        <div class="signatures">
            <div>
                <div class="sig-title">Diterima Bagian Gudang</div>
                <div class="sig-space"></div>
                <div class="sig-box">( ............................................ )</div>
            </div>
            <div>
                <div class="sig-title">Dibuat Bagian Purchasing</div>
                <div class="sig-space"></div>
                <div class="sig-box">( {{ $purchase->creator?->name ?? 'Staff Pembelian' }} )</div>
            </div>
            <div>
                <div class="sig-title">Disetujui Keuangan / Owner</div>
                <div class="sig-space"></div>
                <div class="sig-box">( ............................................ )</div>
            </div>
        </div>

        <div class="watermark">
            Dokumen Sah Sistem SIGMA ERP &amp; POS • Dicetak pada {{ now()->format('d/m/Y H:i') }} WIB • Bukti Penerimaan Barang &amp; Pengakuan Hutang Usaha
        </div>
    </div>

</body>
</html>
