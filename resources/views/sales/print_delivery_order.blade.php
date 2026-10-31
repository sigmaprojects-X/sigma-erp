<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SURAT JALAN (DELIVERY ORDER) - {{ $transaction->invoice_number }}</title>
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
        .do-box {
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
            background: #059669;
            color: #fff;
            border-color: #047857;
        }
        .btn-primary:hover {
            background: #047857;
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
        }
        .company-sub {
            font-size: 9pt;
            line-height: 1.4;
            color: #222;
        }
        .do-title-block {
            text-align: right;
        }
        .do-title {
            font-size: 16pt;
            font-weight: 900;
            text-decoration: underline;
        }
        .do-no {
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
            font-size: 10pt;
            margin-bottom: 14px;
        }
        .items-table th {
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 8px 4px;
            text-align: left;
            font-weight: 800;
        }
        .items-table td {
            padding: 8px 4px;
            border-bottom: 1px dashed #cbd5e1;
            vertical-align: top;
        }
        .text-center {
            text-align: center !important;
        }
        .text-right {
            text-align: right !important;
        }
        .instructions-box {
            border: 1px dashed #666;
            padding: 10px;
            border-radius: 4px;
            font-size: 8.5pt;
            margin-top: 10px;
            background: #fafafa;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 12px;
            text-align: center;
            margin-top: 36px;
            font-size: 8.5pt;
        }
        .sig-space {
            height: 60px;
        }
        .sig-name {
            font-weight: bold;
            border-top: 1px dotted #000;
            display: inline-block;
            min-width: 120px;
            padding-top: 4px;
        }

        /* MEDIA PRINT SETTINGS */
        @media print {
            body {
                background: #fff;
                padding: 0;
                color: #000;
                font-family: 'Courier New', Courier, monospace;
            }
            .do-box {
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
                🚚 Cetak Surat Jalan (Print / PDF)
            </button>
        </div>
    </div>

    <div class="do-box">
        <!-- HEADER PERUSAHAAN & SURAT JALAN -->
        <div class="header">
            <div>
                <div class="company-title">{{ $transaction->store->company->name ?? 'SIGMA LOGISTICS & WAREHOUSE' }}</div>
                <div class="company-sub">
                    <strong>{{ $transaction->store->name ?? 'Gudang / Outlet Distribusi' }}</strong> ({{ $transaction->store->code ?? 'HO' }})<br>
                    {{ $transaction->store->address ?? 'Pusat Distribusi Logistik' }}<br>
                    Telp: {{ $transaction->store->phone ?? '(021) 555-0199' }}
                </div>
            </div>
            <div class="do-title-block">
                <div class="do-title">SURAT JALAN PENGIRIMAN</div>
                <div class="do-no">DO-{{ substr($transaction->invoice_number, 4) }}</div>
                <div style="font-size: 8.5pt; color: #444; margin-top: 2px;">
                    Faktur Ref: {{ $transaction->invoice_number }}<br>
                    Tanggal: {{ $transaction->created_at->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>

        <!-- INFORMASI ALAMAT TUJUAN PENGIRIMAN -->
        <div class="meta-grid">
            <div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Penerima / Toko:</td>
                        <td><strong>{{ $transaction->customer->name ?? 'Pelanggan Umum' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Alamat Kirim:</td>
                        <td><strong>{{ $transaction->customer->address ?? 'Ambil Sendiri / Di Tempat' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">No. Telepon / HP:</td>
                        <td>{{ $transaction->customer->phone ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="meta-table">
                    <tr>
                        <td class="label">Status Pengiriman:</td>
                        <td><strong>SIAP DIKIRIM (DISPATCH)</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Gudang Asal:</td>
                        <td>{{ $transaction->store->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Admin Pembuat:</td>
                        <td>{{ $transaction->salesPerson->name ?? 'Admin Operasional' }}</td>
                    </tr>
                    @if($transaction->notes)
                        <tr>
                            <td class="label">Catatan Driver:</td>
                            <td><em>{{ $transaction->notes }}</em></td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- TABEL RINCIAN FISIK BARANG (HARGA DISEMBUNYIKAN) -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">NO</th>
                    <th style="width: 140px;">KODE / SKU</th>
                    <th>NAMA BARANG / DESKRIPSI</th>
                    <th style="width: 90px;" class="text-center">SATUAN</th>
                    <th style="width: 90px;" class="text-center">QTY KIRIM</th>
                    <th style="width: 120px;" class="text-center">CEK FISIK GUDANG</th>
                    <th style="width: 120px;" class="text-center">CEK TERIMA PELANGGAN</th>
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
                        <td class="text-center"><strong>{{ $item->productUnit->unit_code ?? 'Pcs' }}</strong></td>
                        <td class="text-center" style="font-size: 11pt; font-weight: bold;">{{ (float) $item->quantity }}</td>
                        <td class="text-center" style="color: #666;">[ &nbsp; ] OK</td>
                        <td class="text-center" style="color: #666;">[ &nbsp; ] SESUAI</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- INSTRUKSI & PERINGATAN LOGISTIK -->
        <div class="instructions-box">
            <strong>PERHATIAN PENGIRIMAN & PENERIMAAN:</strong><br>
            1. Harap periksa fisik kemasan, segel, dan kuantitas barang bersama pengemudi/kurir sebelum menandatangani surat jalan ini.<br>
            2. Klaim kekurangan atau kerusakan fisik barang tidak dapat dilayani setelah surat jalan ini ditandatangani tanpa catatan.<br>
            3. Lembar 1 (Putih): Arsip Gudang & Finance | Lembar 2 (Merah): Pelanggan / Toko Penerima | Lembar 3 (Kuning): Pengemudi.
        </div>

        <!-- TANDA TANGAN (4 PIHAK RESMI) -->
        <div class="signatures">
            <div>
                <div>Admin Kantor,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $transaction->salesPerson->name ?? 'Admin' }} )</div>
            </div>
            <div>
                <div>Petugas Gudang,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( .......................... )</div>
            </div>
            <div>
                <div>Pengemudi / Sopir,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( .......................... )</div>
            </div>
            <div>
                <div>Diterima Pelanggan,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $transaction->customer->name ?? 'Penerima' }} )</div>
            </div>
        </div>
    </div>

</body>
</html>
