<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pengingat Tagihan - {{ $transaction->invoice_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
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
            background: #dc2626;
            color: #fff;
            border-color: #b91c1c;
        }
        .btn-primary:hover {
            background: #b91c1c;
        }
        .document-box {
            max-width: 820px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.07);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
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
            background: #fee2e2;
            color: #991b1b;
            font-weight: 800;
            font-size: 11pt;
            padding: 4px 12px;
            border-radius: 4px;
            border: 1px solid #f87171;
            letter-spacing: 0.5px;
        }
        .doc-badge .date {
            font-size: 9pt;
            color: #64748b;
            margin-top: 6px;
        }
        .meta-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .meta-col h3 {
            font-size: 9pt;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
        }
        .meta-col p {
            font-size: 10pt;
            margin-bottom: 3px;
        }
        .letter-body {
            margin-bottom: 24px;
            font-size: 10.5pt;
            color: #334155;
            text-align: justify;
        }
        .bill-card {
            background: #fff;
            border: 2px solid #ef4444;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .bill-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px dashed #f1f5f9;
        }
        .bill-row:last-child {
            border-bottom: none;
            padding-top: 10px;
            margin-top: 6px;
            border-top: 2px solid #ef4444;
        }
        .bill-row.highlight {
            font-size: 13pt;
            font-weight: 800;
            color: #b91c1c;
        }
        .terbilang-box {
            margin-top: 8px;
            padding: 8px 12px;
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            font-size: 9.5pt;
            font-style: italic;
            color: #7f1d1d;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 24px;
        }
        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
        }
        .items-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bank-info {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 28px;
        }
        .bank-info h4 {
            font-size: 10pt;
            font-weight: 700;
            color: #166534;
            margin-bottom: 8px;
        }
        .bank-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .bank-item {
            background: #fff;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 9pt;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 36px;
            text-align: center;
        }
        .sig-space {
            height: 70px;
        }
        .sig-name {
            font-weight: 700;
            border-top: 1px solid #334155;
            padding-top: 4px;
            display: inline-block;
            min-width: 180px;
        }
        @media print {
            body {
                background: #fff !important;
                padding: 0 !important;
                font-size: 10pt;
            }
            .toolbar {
                display: none !important;
            }
            .document-box {
                border: none !important;
                box-shadow: none !important;
                padding: 15mm 12mm !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('sales.transactions') }}" class="btn">
            &larr; Kembali ke Riwayat Transaksi
        </a>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('sales.print-invoice', $transaction->id) }}" target="_blank" class="btn">
                📄 Lihat Faktur Asli
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Surat Pengingat (A4)
            </button>
        </div>
    </div>

    <div class="document-box">
        <!-- Header Perusahaan -->
        <div class="header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
            <div style="display: flex; gap: 14px; align-items: center;">
                @if(optional($transaction->store->company)->logo_url)
                    <img src="{{ $transaction->store->company->logo_url }}" alt="Logo" style="max-height: 60px; max-width: 120px; object-fit: contain;">
                @endif
                <div class="company-info">
                    <h1>{{ $transaction->store->company->name ?? config('app.name', 'SIGMA ERP') }}</h1>
                    <p><strong>{{ $transaction->store->name ?? 'Toko / Cabang Pusat' }}</strong></p>
                    <p>{{ $transaction->store->address ?? 'Alamat Operasional Perusahaan' }}</p>
                    <p>Telepon: {{ $transaction->store->phone ?? '-' }} @if(optional($transaction->store->company)->whatsapp) | WA: {{ $transaction->store->company->whatsapp }} @endif</p>
                </div>
            </div>
            <div class="doc-badge">
                <div class="tag">SURAT PENGINGAT TAGIHAN</div>
                <div class="date">Tanggal Terbit: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div class="date">Ref: SP-{{ $transaction->invoice_number }}</div>
            </div>
        </div>

        <!-- Kepada Yth & Informasi Faktur -->
        <div class="meta-section">
            <div class="meta-col">
                <h3>Kepada Yth. (Pelanggan):</h3>
                <p><strong>{{ $transaction->customer->name ?? 'Pelanggan Umum' }}</strong></p>
                @if($transaction->customer)
                    <p>{{ $transaction->customer->address ?? 'Alamat tidak terdata' }}</p>
                    <p>No. Telepon / WA: {{ $transaction->customer->phone ?? '-' }}</p>
                    @if($transaction->customer->email)
                        <p>Email: {{ $transaction->customer->email }}</p>
                    @endif
                @endif
            </div>
            <div class="meta-col">
                <h3>Rujukan Transaksi:</h3>
                <p><strong>No. Faktur:</strong> {{ $transaction->invoice_number }}</p>
                <p><strong>Tanggal Faktur:</strong> {{ \Carbon\Carbon::parse($transaction->transaction_date)->translatedFormat('d F Y') }}</p>
                <p><strong>Jatuh Tempo:</strong>
                    @if($transaction->due_date)
                        <span style="font-weight: 700; color: {{ \Carbon\Carbon::parse($transaction->due_date)->isPast() ? '#b91c1c' : '#d97706' }}">
                            {{ \Carbon\Carbon::parse($transaction->due_date)->translatedFormat('d F Y') }}
                            @if(\Carbon\Carbon::parse($transaction->due_date)->isPast())
                                (Terlambat {{ \Carbon\Carbon::parse($transaction->due_date)->diffInDays(now()) }} hari)
                            @endif
                        </span>
                    @else
                        -
                    @endif
                </p>
                <p><strong>Sales In-Charge:</strong> {{ $transaction->salesPerson->name ?? '-' }}</p>
            </div>
        </div>

        <!-- Surat Pengantar Formal -->
        <div class="letter-body">
            <p>Dengan hormat,</p>
            <p style="margin-top: 8px;">
                Bersama surat ini, kami memberitahukan bahwa berdasarkan catatan pembukuan kami per tanggal <strong>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</strong>, terdapat kewajiban pembayaran faktur penjualan tempo yang belum dilunasi dengan rincian sebagai berikut:
            </p>
        </div>

        <!-- Ringkasan Nilai Tagihan -->
        @php
            $grandTotal = (float) $transaction->grand_total;
            $paidAmount = (float) $transaction->paid_amount;
            $remaining = (float) ($transaction->remaining_receivable ?: $transaction->credit_amount);
        @endphp
        <div class="bill-card">
            <div class="bill-row">
                <span>Nilai Total Faktur:</span>
                <span><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></span>
            </div>
            <div class="bill-row">
                <span>Uang Muka / Pembayaran Diterima:</span>
                <span style="color: #16a34a;">(-) Rp {{ number_format($paidAmount, 0, ',', '.') }}</span>
            </div>
            <div class="bill-row highlight">
                <span>SISA PIUTANG TERTUNGGAK:</span>
                <span>Rp {{ number_format($remaining, 0, ',', '.') }}</span>
            </div>
            <div class="terbilang-box">
                <strong>Terbilang:</strong> <em># {{ $terbilang }} #</em>
            </div>
        </div>

        <!-- Rincian Barang yang Dibeli -->
        <h4 style="font-size: 10pt; font-weight: 700; margin-bottom: 8px; color: #334155;">Rincian Barang yang Dipesan:</h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">No</th>
                    <th>Nama Barang / Deskripsi</th>
                    <th class="text-center" style="width: 80px;">Jumlah</th>
                    <th class="text-right" style="width: 110px;">Harga Satuan</th>
                    <th class="text-right" style="width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transaction->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product->name ?? 'Barang' }}</strong>
                        <div style="font-size: 8pt; color: #64748b;">SKU: {{ $item->product->sku ?? '-' }}</div>
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->productUnit->unit_code ?? $item->unit_code ?? 'Pcs' }}</td>
                    <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Instruksi Rekening Pembayaran -->
        <div class="bank-info">
            <h4>Pilihan Metode & Rekening Pembayaran Resmi:</h4>
            <p style="font-size: 9pt; color: #166534; margin-bottom: 8px;">
                Pembayaran dapat ditransfer ke salah satu rekening bank resmi perusahaan berikut dengan mencantumkan nomor invoice <strong>{{ $transaction->invoice_number }}</strong> pada berita transfer:
            </p>
            <div class="bank-grid">
                @forelse($bankAccounts as $bank)
                <div class="bank-item">
                    <strong>{{ $bank->bank_name }}</strong><br>
                    No. Rek: <span style="font-weight: 700; font-size: 10pt; color: #0f172a;">{{ $bank->account_number }}</span><br>
                    a/n: {{ $bank->account_holder }}
                </div>
                @empty
                <div class="bank-item">
                    <strong>BANK PERUSAHAAN</strong><br>
                    No. Rek: <span style="font-weight: 700; font-size: 10pt; color: #0f172a;">Hubungi Bagian Keuangan</span><br>
                    a/n: {{ $transaction->store->company->name ?? config('app.name') }}
                </div>
                @endforelse
            </div>
        </div>

        <div class="letter-body">
            <p>
                Apabila Bapak/Ibu telah melakukan pembayaran sebelum surat pengingat ini diterima, kami mohon konfirmasi dan bukti transfer dapat dikirimkan kepada bagian keuangan kami melalui nomor <strong>{{ $transaction->store->phone ?? 'telepon kantor' }}</strong> agar dapat segera kami perbarui pada catatan pembukuan kami.
            </p>
            <p style="margin-top: 8px;">
                Atas perhatian, kerjasama, dan penyelesaian pembayaran yang baik dari Bapak/Ibu, kami ucapkan terima kasih.
            </p>
        </div>

        <!-- Tanda Tangan Formal -->
        <div class="signatures">
            <div>
                <p>Hormat kami,</p>
                <p style="font-size: 9pt; color: #64748b;">Bagian Keuangan & Piutang (AR)</p>
                <div class="sig-space"></div>
                <div class="sig-name">( Finance / AR Officer )</div>
            </div>
            <div>
                <p>Mengetahui,</p>
                <p style="font-size: 9pt; color: #64748b;">Kepala Toko / Finance Manager</p>
                <div class="sig-space"></div>
                <div class="sig-name">( Manager Keuangan )</div>
            </div>
        </div>
    </div>

</body>
</html>
