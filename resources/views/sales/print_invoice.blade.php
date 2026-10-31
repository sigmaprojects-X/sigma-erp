<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAKTUR PENJUALAN - {{ $transaction->invoice_number }}</title>
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
        .invoice-box {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .toolbar {
            max-width: 850px;
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
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .company-title {
            font-size: 16pt;
            font-weight: 900;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 9pt;
            line-height: 1.4;
            color: #222;
        }
        .invoice-title-block {
            text-align: right;
        }
        .invoice-title {
            font-size: 16pt;
            font-weight: 900;
            text-decoration: underline;
        }
        .invoice-no {
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
            width: 110px;
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
            grid-template-columns: 1.3fr 1fr;
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
            height: 60px;
        }
        .sig-name {
            font-weight: bold;
            border-top: 1px dotted #000;
            display: inline-block;
            min-width: 140px;
            padding-top: 4px;
        }

        /* MEDIA PRINT SETTINGS UNTUK A4 & CONTINUOUS FORM DOT MATRIX */
        @media print {
            body {
                background: #fff;
                padding: 0;
                color: #000;
                font-family: 'Courier New', Courier, monospace;
            }
            .invoice-box {
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
                margin: 8mm 10mm 8mm 10mm;
            }
            .items-table td {
                border-bottom: 1px dotted #666;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('sales.transactions') }}" class="btn">
            ← Kembali ke Transaksi
        </a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Faktur (Print / PDF)
            </button>
        </div>
    </div>

    <div class="invoice-box">
        <!-- HEADER PERUSAHAAN & JUDUL FAKTUR -->
        <div class="header">
            <div>
                <div class="company-title">{{ $transaction->store->company->name ?? 'SIGMA RETAIL & WHOLESALE' }}</div>
                <div class="company-sub">
                    <strong>{{ $transaction->store->name ?? 'Outlet Utama' }}</strong> ({{ $transaction->store->code ?? 'HO' }})<br>
                    {{ $transaction->store->address ?? 'Pusat Distribusi & Pergudangan' }}<br>
                    Telp: {{ $transaction->store->phone ?? '(021) 555-0199' }} | Email: info@sigmaerp.id
                </div>
            </div>
            <div class="invoice-title-block">
                <div class="invoice-title">FAKTUR PENJUALAN</div>
                <div class="invoice-no">{{ $transaction->invoice_number }}</div>
                <div style="font-size: 8.5pt; color: #444; margin-top: 2px;">
                    Tanggal: {{ $transaction->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        <!-- INFORMASI PELANGGAN & DETAIL TRANSAKSI -->
        <div class="meta-grid">
            <div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Kepada Yth:</td>
                        <td><strong>{{ $transaction->customer->name ?? 'Pelanggan Umum (Walk-in)' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Kode Cust:</td>
                        <td>{{ $transaction->customer->code ?? '-' }} (Tier: {{ $transaction->customerTier->name ?? 'Umum' }})</td>
                    </tr>
                    <tr>
                        <td class="label">Alamat:</td>
                        <td>{{ $transaction->customer->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">No. Telepon:</td>
                        <td>{{ $transaction->customer->phone ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Pembayaran:</td>
                        <td><strong>{{ $transaction->payment_method }}</strong></td>
                    </tr>
                    @if($transaction->payment_method === 'CREDIT' || $transaction->credit_amount > 0)
                        <tr>
                            <td class="label" style="color: #b91c1c;">Jatuh Tempo:</td>
                            <td style="color: #b91c1c; font-weight: bold;">
                                {{ $transaction->due_date ? date('d/m/Y', strtotime($transaction->due_date)) : '30 Hari' }}
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Kasir/Sales:</td>
                        <td>{{ $transaction->salesPerson->name ?? 'Petugas Kasir' }}</td>
                    </tr>
                    @if($transaction->payment_reference)
                        <tr>
                            <td class="label">No. Ref:</td>
                            <td>{{ $transaction->payment_reference }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- TABEL ITEM BARANG -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">NO</th>
                    <th style="width: 120px;">KODE / SKU</th>
                    <th>NAMA BARANG</th>
                    <th style="width: 70px;" class="text-center">SATUAN</th>
                    <th style="width: 60px;" class="text-right">QTY</th>
                    <th style="width: 110px;" class="text-right">HARGA (Rp)</th>
                    <th style="width: 90px;" class="text-right">DISKON</th>
                    <th style="width: 120px;" class="text-right">SUBTOTAL (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->product->sku ?? $item->product->barcode ?? '-' }}</td>
                        <td>
                            <strong>{{ $item->product->name }}</strong>
                        </td>
                        <td class="text-center">{{ $item->productUnit->unit_code ?? 'Pcs' }}</td>
                        <td class="text-right">{{ (float) $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right">{{ (float) $item->discount_amount > 0 ? number_format($item->discount_amount, 0, ',', '.') : '-' }}</td>
                        <td class="text-right font-bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- RINGKASAN & TERBILANG -->
        <div class="summary-grid">
            <div class="terbilang-box">
                <div class="terbilang-label">Terbilang :</div>
                <div class="terbilang-text">"# {{ $terbilang }} #"</div>
                <div style="margin-top: 10px; font-size: 8.5pt; color: #555;">
                    <em>Catatan: Barang yang sudah dibeli dapat diretur dalam tempo maksimal 3 hari kerja dengan menyertakan faktur resmi ini.</em>
                </div>
            </div>

            <div>
                <table class="calc-table">
                    <tr>
                        <td>Total Item ({{ $transaction->items->count() }} macam):</td>
                        <td class="text-right font-bold">{{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if((float) $transaction->discount_amount > 0)
                        <tr>
                            <td>Diskon Faktur:</td>
                            <td class="text-right" style="color: #b91c1c;">-{{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if((float) $transaction->voucher_discount > 0)
                        <tr>
                            <td>Potongan Voucher:</td>
                            <td class="text-right" style="color: #b91c1c;">-{{ number_format($transaction->voucher_discount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if((float) $transaction->tax_amount > 0)
                        <tr>
                            <td>PPN (Pajak):</td>
                            <td class="text-right">{{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>TOTAL AKHIR:</td>
                        <td class="text-right">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Dibayar ({{ $transaction->payment_method }}):</td>
                        <td class="text-right font-bold">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($transaction->payment_method === 'CREDIT' || $transaction->credit_amount > 0)
                        <tr style="color: #b91c1c; font-weight: bold;">
                            <td>Sisa Piutang (Tempo):</td>
                            <td class="text-right">Rp {{ number_format($transaction->remaining_receivable ?: $transaction->credit_amount, 0, ',', '.') }}</td>
                        </tr>
                    @else
                        <tr>
                            <td>Kembalian:</td>
                            <td class="text-right font-bold">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- TANDA TANGAN (3 KOLOM RESMI) -->
        <div class="signatures">
            <div>
                <div>Penerima / Pelanggan,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $transaction->customer->name ?? 'Pelanggan' }} )</div>
            </div>
            <div>
                <div>Bagian Pengiriman / Gudang,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( .......................... )</div>
            </div>
            <div>
                <div>Hormat Kami / Kasir,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $transaction->salesPerson->name ?? 'Petugas Faktur' }} )</div>
            </div>
        </div>
    </div>

</body>
</html>
