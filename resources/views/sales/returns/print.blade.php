<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOTA RETUR PENJUALAN - {{ $return->return_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace, 'Segoe UI', Tahoma, sans-serif;
            font-size: 11pt;
            color: #000;
            background-color: #f1f5f9;
            padding: 20px;
        }
        .note-box {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .toolbar {
            max-width: 800px;
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
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #94a3b8;
            background: #fff;
            color: #1e293b;
        }
        .btn-primary {
            background: #e11d48;
            color: #fff;
            border-color: #be123c;
        }
        .btn-primary:hover {
            background: #be123c;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .company-title {
            font-size: 15pt;
            font-weight: 900;
        }
        .company-sub {
            font-size: 9pt;
            line-height: 1.4;
            color: #222;
        }
        .title-block {
            text-align: right;
        }
        .note-title {
            font-size: 14pt;
            font-weight: 900;
            text-decoration: underline;
            color: #000;
        }
        .note-no {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 4px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            font-size: 9.5pt;
            margin-bottom: 14px;
            line-height: 1.5;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            vertical-align: top;
            padding: 2px 0;
        }
        .meta-table td.label {
            width: 120px;
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 14px;
        }
        .items-table th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 6px 4px;
            text-align: left;
            font-weight: 800;
        }
        .items-table td {
            padding: 6px 4px;
            border-bottom: 1px dashed #cbd5e1;
            vertical-align: top;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
            margin-top: 10px;
            font-size: 9.5pt;
        }
        .terbilang-box {
            border: 1px dashed #000;
            padding: 10px;
            border-radius: 4px;
            background: #fafafa;
        }
        .terbilang-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
        }
        .terbilang-text {
            font-style: italic;
            font-weight: bold;
            line-height: 1.3;
        }
        .calc-table {
            width: 100%;
            border-collapse: collapse;
        }
        .calc-table td {
            padding: 3px 0;
        }
        .calc-table tr.grand-total td {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            font-size: 11pt;
            font-weight: 900;
            padding: 6px 0;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            text-align: center;
            margin-top: 36px;
            font-size: 9pt;
        }
        .sig-space {
            height: 55px;
        }
        .sig-name {
            font-weight: bold;
            border-top: 1px dotted #000;
            display: inline-block;
            min-width: 130px;
            padding-top: 4px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                font-family: 'Courier New', Courier, monospace;
            }
            .note-box {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                width: 100%;
            }
            .toolbar {
                display: none !important;
            }
            @page {
                size: auto;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('sales.returns.index') }}" class="btn">
            ← Kembali ke Daftar Retur
        </a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Nota Retur (Print / PDF)
            </button>
        </div>
    </div>

    <div class="note-box">
        <!-- HEADER -->
        <div class="header">
            <div>
                <div class="company-title">{{ $return->store->company->name ?? 'SIGMA RETAIL & WHOLESALE' }}</div>
                <div class="company-sub">
                    <strong>{{ $return->store->name ?? 'Outlet Utama' }}</strong> ({{ $return->store->code ?? 'HO' }})<br>
                    {{ $return->store->address ?? 'Pusat Distribusi & Operasional' }}<br>
                    Telp: {{ $return->store->phone ?? '(021) 555-0199' }}
                </div>
            </div>
            <div class="title-block">
                <div class="note-title">NOTA RETUR PENJUALAN</div>
                <div style="font-size: 8.5pt; font-weight: bold; color: #555;">(CREDIT NOTE)</div>
                <div class="note-no">{{ $return->return_number }}</div>
                <div style="font-size: 8.5pt; color: #444; margin-top: 2px;">
                    Tanggal: {{ $return->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        <!-- META DETAILS -->
        <div class="meta-grid">
            <div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Faktur Penjualan:</td>
                        <td><strong>{{ $return->transaction->invoice_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Pelanggan:</td>
                        <td><strong>{{ $return->transaction->customer->name ?? 'Pelanggan Umum (Walk-in)' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Alasan Retur:</td>
                        <td><em>{{ $return->reason }}</em></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Metode Refund:</td>
                        <td><strong>{{ $return->refund_method }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Petugas Kasir:</td>
                        <td>{{ $return->processor->name ?? 'Kasir' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Otorisasi Manajer:</td>
                        <td>{{ $return->approver->name ?? 'Manajer Toko' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">NO</th>
                    <th style="width: 130px;">KODE / SKU</th>
                    <th>NAMA BARANG DIRETUR</th>
                    <th style="width: 70px;" class="text-center">SATUAN</th>
                    <th style="width: 70px;" class="text-right">QTY RETUR</th>
                    <th style="width: 120px;" class="text-right">HARGA (Rp)</th>
                    <th style="width: 130px;" class="text-right">SUBTOTAL (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->product->sku ?? $item->product->barcode ?? '-' }}</td>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                        </td>
                        <td class="text-center">{{ $item->unit->unit_code ?? 'Pcs' }}</td>
                        <td class="text-right font-bold">{{ (float) $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right font-bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- SUMMARY & TERBILANG -->
        <div class="summary-grid">
            <div class="terbilang-box">
                <div class="terbilang-label">Terbilang :</div>
                <div class="terbilang-text">"# {{ $terbilang }} #"</div>
                <div style="margin-top: 10px; font-size: 8.5pt; color: #555;">
                    <em>Bukti pengembalian barang dan pemotongan nilai tagihan resmi yang telah disetujui bersama.</em>
                </div>
            </div>

            <div>
                <table class="calc-table">
                    <tr>
                        <td>Subtotal Nilai Barang:</td>
                        <td class="text-right font-bold">Rp {{ number_format($return->subtotal_returned, 0, ',', '.') }}</td>
                    </tr>
                    @if((float) $return->tax_returned > 0)
                        <tr>
                            <td>Pajak (PPN) Dikembalikan:</td>
                            <td class="text-right font-bold">Rp {{ number_format($return->tax_returned, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>TOTAL REFUND:</td>
                        <td class="text-right" style="color: #be123c;">Rp {{ number_format($return->total_refund_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Status Penyesuaian:</td>
                        <td class="text-right font-bold">{{ $return->refund_method === 'CREDIT_REDUCTION' ? 'Potong Saldo Piutang' : 'Dana Dikembalikan' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- TANDA TANGAN -->
        <div class="signatures">
            <div>
                <div>Pelanggan / Penyerah,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $return->transaction->customer->name ?? 'Pelanggan' }} )</div>
            </div>
            <div>
                <div>Petugas Kasir / Admin,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $return->processor->name ?? 'Petugas' }} )</div>
            </div>
            <div>
                <div>Manajer / Otorisator,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $return->approver->name ?? 'Manajer' }} )</div>
            </div>
        </div>
    </div>

</body>
</html>
