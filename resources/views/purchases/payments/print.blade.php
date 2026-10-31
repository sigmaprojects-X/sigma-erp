<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran Hutang Supplier - {{ $payment->payment_number }}</title>
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
            background: #059669;
            color: #fff;
            border-color: #047857;
        }
        .btn-primary:hover {
            background: #047857;
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
            background: #d1fae5;
            color: #065f46;
            font-weight: 800;
            font-size: 11pt;
            padding: 4px 14px;
            border-radius: 4px;
            border: 1px solid #6ee7b7;
            letter-spacing: 0.5px;
        }
        .doc-badge .no {
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
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary-card {
            border: 2px solid #059669;
            background: #f0fdf4;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 18px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .summary-row.total {
            font-size: 13pt;
            font-weight: 800;
            color: #065f46;
        }
        .terbilang-box {
            padding: 8px 12px;
            background: #f0fdf4;
            border-left: 3px solid #059669;
            font-size: 9pt;
            font-style: italic;
            color: #065f46;
            margin-bottom: 20px;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 16px;
            margin-top: 36px;
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
            min-width: 120px;
            font-size: 8.5pt;
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
        <a href="{{ route('purchases.debt-ledger') }}" class="btn">
            &larr; Kembali ke Buku Hutang Supplier
        </a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Bukti Pengeluaran Kas (A4)
            </button>
        </div>
    </div>

    <div class="document-box">
        <!-- Header Perusahaan -->
        <div class="header">
            <div class="company-info">
                <h1>{{ $payment->company->name ?? config('app.name', 'PT SIGMA RETAIL INDONESIA') }}</h1>
                <p><strong>{{ $payment->store->name ?? 'Kantor Pusat & Perbendaharaan' }}</strong></p>
                <p>{{ $payment->store->address ?? 'Alamat Operasional Perusahaan' }}</p>
                <p>Telepon: {{ $payment->store->phone ?? '-' }} | Email: {{ $payment->store->email ?? '-' }}</p>
            </div>
            <div class="doc-badge">
                <div class="tag">BUKTI KAS KELUAR (PAYMENT VOUCHER)</div>
                <div class="no">{{ $payment->payment_number }}</div>
                <div class="date">Tanggal Bayar: {{ $payment->payment_date ? $payment->payment_date->translatedFormat('d F Y') : now()->translatedFormat('d F Y') }}</div>
            </div>
        </div>

        <!-- Meta Pembayaran & Supplier -->
        <div class="meta-section">
            <div class="meta-col">
                <h3>Dibayarkan Kepada (Vendor):</h3>
                <p><strong>{{ $payment->supplier->name ?? 'Pemasok Umum' }}</strong></p>
                <p>{{ $payment->supplier->address ?: 'Alamat tidak terdata' }}</p>
                <p>Telepon: {{ $payment->supplier->phone ?: '-' }}</p>
                <p>Kontak PIC: {{ $payment->supplier->contact_person ?: '-' }}</p>
            </div>
            <div class="meta-col">
                <h3>Rincian Kas / Bank Pengeluaran:</h3>
                <p><strong>Metode Bayar:</strong> {{ $payment->payment_method === 'CASH' ? '💵 KAS TUNAI' : '🏦 TRANSFER BANK' }}</p>
                @if($payment->bankAccount)
                    <p><strong>Bank Asal:</strong> {{ $payment->bankAccount->bank_name }} - {{ $payment->bankAccount->account_number }}</p>
                    <p><strong>Atas Nama:</strong> {{ $payment->bankAccount->account_holder }}</p>
                @endif
                @if($payment->reference_number)
                    <p><strong>No. Referensi / Ref Bank:</strong> {{ $payment->reference_number }}</p>
                @endif
                <p><strong>Petugas Kasir/Finance:</strong> {{ $payment->creator->name ?? 'Finance Officer' }}</p>
            </div>
        </div>

        <!-- Rincian Tagihan yang Dilunasi -->
        <h4 style="font-size: 9.5pt; font-weight: 700; margin-bottom: 6px; color: #334155;">Alokasi Pembayaran Faktur Pembelian Supplier:</h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">No</th>
                    <th style="width: 140px;">No. Faktur Supplier</th>
                    <th style="width: 130px;">No. Bukti Sistem (GRN)</th>
                    <th class="text-center" style="width: 100px;">Tgl Faktur</th>
                    <th class="text-right" style="width: 120px;">Total Tagihan</th>
                    <th class="text-right" style="width: 130px;">Jumlah yang Dibayar</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold;">{{ $item->purchase->invoice_number ?? '-' }}</td>
                    <td style="font-family: monospace;">{{ $item->purchase->reference_number ?? '-' }}</td>
                    <td class="text-center">{{ $item->purchase?->purchase_date ? $item->purchase->purchase_date->format('d/m/Y') : '-' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format((float) ($item->purchase?->grand_total ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold" style="color: #047857;">Rp {{ number_format((float) $item->amount_paid, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Ringkasan Total Pembayaran -->
        <div class="summary-card">
            <div class="summary-row total">
                <span>TOTAL PEMBAYARAN DIKELUARKAN:</span>
                <span>Rp {{ number_format((float) $payment->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="terbilang-box">
            <strong>Terbilang:</strong> <em># {{ $terbilang }} #</em>
        </div>

        @if($payment->notes)
        <div style="font-size: 8.5pt; color: #475569; margin-bottom: 24px;">
            <strong>Keterangan / Memo:</strong> {{ $payment->notes }}
        </div>
        @endif

        <!-- Tanda Tangan 4 Pihak Standar Akuntansi Perusahaan -->
        <div class="signatures">
            <div>
                <p>Dibukukan Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Kasir / Finance</p>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $payment->creator->name ?? '( Staff Finance )' }}</div>
            </div>
            <div>
                <p>Diperiksa Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Accounting Officer</p>
                <div class="sig-space"></div>
                <div class="sig-name">( Accounting )</div>
            </div>
            <div>
                <p>Disetujui Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Manajer Keuangan / Owner</p>
                <div class="sig-space"></div>
                <div class="sig-name">( Finance Manager )</div>
            </div>
            <div>
                <p>Diterima Oleh,</p>
                <p style="font-size: 8pt; color: #64748b;">Penerima / Vendor</p>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $payment->supplier->name ?? 'Supplier' }} )</div>
            </div>
        </div>
    </div>

</body>
</html>
