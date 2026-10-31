<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota Retur Pembelian - {{ $return->return_number }}</title>
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
            background-color: #059669;
            color: white;
        }
        .btn-print:hover { background-color: #047857; }

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
            padding: 36px 40px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            position: relative;
        }

        /* HEADER DOKUMEN */
        .doc-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 2px solid #0f172a;
            margin-bottom: 16px;
        }

        .company-info h2 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.3px;
            text-transform: uppercase;
        }

        .company-info .store-name {
            font-size: 13px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 2px;
        }

        .company-info p {
            color: #64748b;
            font-size: 11px;
            margin-top: 2px;
        }

        .doc-meta {
            text-align: right;
        }

        .doc-title {
            font-size: 16px;
            font-weight: 800;
            color: #dc2626;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .doc-number {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            background: #f8fafc;
            padding: 3px 8px;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            display: inline-block;
        }

        /* KARTU INFORMASI SUPPLIER & REFERENSI */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 14px 16px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .info-card h4 {
            font-size: 11px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .info-row {
            display: flex;
            margin-bottom: 3px;
            font-size: 11.5px;
        }

        .info-label {
            width: 110px;
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            flex: 1;
            color: #0f172a;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .badge-flexible { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-invoice { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-draft { background: #fef3c7; color: #92400e; }

        /* TABEL ITEM RETUR */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }

        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11.5px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .col-num { width: 35px; text-align: center; }
        .col-qty { width: 70px; text-align: right; }
        .col-unit { width: 65px; text-align: center; }
        .col-price { width: 100px; text-align: right; }
        .col-subtotal { width: 115px; text-align: right; }

        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        /* RINGKASAN TOTAL & ALOKASI */
        .summary-container {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .settlement-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 14px;
        }

        .settlement-card h4 {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .allocation-table {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }
        .allocation-table th {
            text-align: left;
            color: #64748b;
            font-weight: 600;
            padding: 3px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .allocation-table td {
            padding: 4px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .totals-table {
            width: 100%;
            font-size: 12px;
        }
        .totals-table td {
            padding: 4px 6px;
        }
        .totals-table .total-label {
            text-align: right;
            color: #64748b;
            font-weight: 600;
        }
        .totals-table .total-value {
            text-align: right;
            font-weight: 700;
            color: #0f172a;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .totals-table tr.grand-total {
            border-top: 2px solid #0f172a;
            font-size: 14px;
        }
        .totals-table tr.grand-total .total-value {
            color: #dc2626;
            font-weight: 800;
        }

        /* KOLOM TANDA TANGAN */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 30px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .sig-box {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 120px;
            border: 1px dashed #cbd5e1;
            padding: 8px 6px;
            border-radius: 4px;
            background: #ffffff;
        }

        .sig-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }

        .sig-name {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            border-top: 1px solid #64748b;
            padding-top: 4px;
            margin: 0 10px;
        }

        .sig-sub {
            font-size: 9.5px;
            color: #64748b;
        }

        /* FOOTER AUDIT */
        .doc-footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 9.5px;
            color: #94a3b8;
        }

        /* MEDIA PRINT SETTINGS */
        @media print {
            body {
                background: none;
                padding: 0;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .document-sheet {
                border: none;
                box-shadow: none;
                padding: 10px 15px;
                max-width: 100%;
            }

            @page {
                size: A4 portrait;
                margin: 10mm 12mm 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR ATAS (HANYA DITAMPILKAN DI LAYAR) -->
    <div class="no-print-toolbar">
        <div class="toolbar-title">
            <span>🔄</span>
            <span>Pratinjau Dokumen Cetak Retur Pembelian</span>
            <span style="background: #334155; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-family: monospace;">
                {{ $return->return_number }}
            </span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('purchases.returns.index') }}" class="btn btn-back">
                <span>⬅️</span>
                <span>Kembali</span>
            </a>
            <button onclick="window.print()" class="btn btn-print">
                <span>🖨️</span>
                <span>Cetak Nota Retur</span>
            </button>
        </div>
    </div>

    <!-- LEMBAR DOKUMEN CETAK RESMI -->
    <div class="document-sheet">

        <!-- KOP / HEADER PERUSAHAAN -->
        <div class="doc-header">
            <div class="company-info">
                <h2>{{ $return->company->name ?? 'PT SIGMA RETAIL INDONESIA' }}</h2>
                <div class="store-name">🏪 {{ $return->store->name ?? 'Gudang Pusat / Toko Utama' }}</div>
                <p>{{ $return->store->address ?? 'Jl. Raya Operasional SIGMA No. 88, Jawa Timur' }}</p>
                <p>Telp: {{ $return->store->phone ?? '0812-3456-7890' }} | Email: info@sigmamart.co.id</p>
            </div>
            <div class="doc-meta">
                <div class="doc-title">NOTA RETUR PEMBELIAN</div>
                <div class="doc-subtitle">SURAT JALAN PENGEMBALIAN BARANG KE SUPPLIER</div>
                <div class="doc-number">{{ $return->return_number }}</div>
            </div>
        </div>

        <!-- INFORMASI SUPPLIER & METADATA DOKUMEN -->
        <div class="info-grid">
            <div class="info-card">
                <h4>🏢 SUPPLIER TUJUAN PENGEMBALIAN</h4>
                <div class="info-row">
                    <span class="info-label">Nama Supplier:</span>
                    <span class="info-value" style="font-size: 12.5px; color: #1e40af;">{{ $return->supplier->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kode Vendor:</span>
                    <span class="info-value font-mono">{{ $return->supplier->code ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. Telepon:</span>
                    <span class="info-value">{{ $return->supplier->phone ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Alamat:</span>
                    <span class="info-value">{{ $return->supplier->address ?? '-' }}</span>
                </div>
            </div>

            <div class="info-card">
                <h4>📋 DETAIL DOKUMEN & REFERENSI</h4>
                <div class="info-row">
                    <span class="info-label">Tanggal Retur:</span>
                    <span class="info-value">{{ $return->return_date ? $return->return_date->format('d F Y') : now()->format('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jenis Dokumen:</span>
                    <span class="info-value">
                        @if($return->return_type === 'FLEXIBLE')
                            <span class="badge badge-flexible">📦 JALUR 2: RETUR FLEKSIBEL</span>
                        @else
                            <span class="badge badge-invoice">🧾 JALUR 1: DARI FAKTUR</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Faktur Asal:</span>
                    <span class="info-value font-mono">
                        @if($return->purchase)
                            {{ $return->purchase->reference_number }} (Inv: {{ $return->purchase->invoice_number ?: '-' }})
                        @else
                            <span style="color: #64748b; font-style: italic;">Non-Faktur (Fleksibel)</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status Dokumen:</span>
                    <span class="info-value">
                        @if($return->status === 'COMPLETED' || $return->status === 'APPROVED')
                            <span class="badge badge-completed">✅ DISETUJUI / RESMI</span>
                        @elseif($return->status === 'DRAFT')
                            <span class="badge badge-draft">⏳ DRAFT</span>
                        @else
                            <span class="badge">{{ $return->status }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- TABEL BARANG YANG DIRETUR -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-num">No</th>
                    <th>Kode / SKU</th>
                    <th>Nama Barang &amp; Deskripsi</th>
                    <th class="col-unit">Satuan</th>
                    <th class="col-qty">Kuantitas</th>
                    <th class="col-price">Harga (HPP)</th>
                    <th class="col-subtotal">Subtotal Nilai</th>
                    <th>Alasan / Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($return->items as $idx => $item)
                    <tr>
                        <td class="col-num font-mono">{{ $idx + 1 }}</td>
                        <td class="font-mono" style="font-weight: 600; color: #334155;">
                            {{ $item->product?->sku ?? '-' }}
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $item->product?->name ?? 'Barang Tanpa Nama' }}</div>
                            @if($item->notes)
                                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Catatan: {{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="col-unit font-mono" style="font-weight: 600;">
                            {{ $item->unit?->unit_code ?? ($item->purchaseItem?->unit_code ?? 'PCS') }}
                        </td>
                        <td class="col-qty font-mono" style="font-weight: 700; color: #dc2626;">
                            {{ number_format((float) $item->quantity, 0, ',', '.') }}
                        </td>
                        <td class="col-price font-mono">
                            Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="col-subtotal font-mono" style="font-weight: 700;">
                            Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}
                        </td>
                        <td>
                            <span style="font-size: 10.5px; color: #475569;">
                                {{ $item->condition ?? ($return->reason ?? 'Barang Rusak / Tidak Sesuai') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">
                            Tidak ada rincian item dalam dokumen ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- RINGKASAN TOTAL & METODE PENYELESAIAN -->
        <div class="summary-container">
            <!-- Sisi Kiri: Alokasi / Metode Penyelesaian -->
            <div class="settlement-card">
                <h4>
                    <span>💳</span>
                    <span>Metode Penyelesaian: 
                        @if($return->refund_type === 'DEBT_REDUCTION')
                            <strong style="color: #92400e;">POTONG UTANG SUPPLIER</strong>
                        @elseif($return->refund_type === 'CASH')
                            <strong style="color: #065f46;">PENGEMBALIAN KAS TUNAI</strong>
                        @else
                            <strong>{{ $return->refund_type }}</strong>
                        @endif
                    </span>
                </h4>

                @if($return->refund_type === 'DEBT_REDUCTION')
                    @if($return->debtAllocations->isNotEmpty())
                        <p style="font-size: 10.5px; color: #64748b; margin-bottom: 6px;">
                            Nilai retur ini secara otomatis memotong saldo kewajiban hutang faktur berikut:
                        </p>
                        <table class="allocation-table">
                            <thead>
                                <tr>
                                    <th>No. Faktur Pembelian</th>
                                    <th>Tgl Faktur</th>
                                    <th style="text-align: right;">Nominal Potong</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($return->debtAllocations as $alloc)
                                    <tr>
                                        <td class="font-mono" style="font-weight: 700; color: #1e40af;">
                                            {{ $alloc->purchase?->reference_number ?? '-' }}
                                            @if($alloc->purchase?->invoice_number)
                                                <span style="font-size: 9.5px; color: #64748b;">({{ $alloc->purchase->invoice_number }})</span>
                                            @endif
                                        </td>
                                        <td>{{ $alloc->purchase?->purchase_date ? \Carbon\Carbon::parse($alloc->purchase->purchase_date)->format('d/m/Y') : '-' }}</td>
                                        <td class="font-mono" style="text-align: right; font-weight: 700; color: #047857;">
                                            Rp {{ number_format((float) $alloc->allocated_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p style="font-size: 10.5px; color: #64748b;">
                            Memotong saldo hutang pada faktur asal <strong>{{ $return->purchase?->reference_number ?? '-' }}</strong>.
                        </p>
                    @endif
                @elseif($return->refund_type === 'CASH')
                    <p style="font-size: 10.5px; color: #065f46;">
                        Pengembalian uang tunai telah dikonfirmasi dan dicatat ke akun Kas Toko.
                    </p>
                @endif

                @if($return->notes)
                    <div style="margin-top: 8px; padding-top: 6px; border-top: 1px dashed #cbd5e1; font-size: 10.5px; color: #475569;">
                        <strong>Catatan Dokumen:</strong> {{ $return->notes }}
                    </div>
                @endif
            </div>

            <!-- Sisi Kanan: Rekapitulasi Nominal -->
            <div>
                <table class="totals-table">
                    <tr>
                        <td class="total-label">Subtotal Nilai Barang:</td>
                        <td class="total-value">Rp {{ number_format((float) ($return->subtotal ?: $return->grand_total), 0, ',', '.') }}</td>
                    </tr>
                    @if((float) $return->tax_amount > 0)
                        <tr>
                            <td class="total-label">Pajak (PPN):</td>
                            <td class="total-value">Rp {{ number_format((float) $return->tax_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="total-label" style="font-size: 12.5px; color: #0f172a;">TOTAL NILAI RETUR:</td>
                        <td class="total-value">Rp {{ number_format((float) $return->grand_total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- KOLOM TANDA TANGAN SERAH TERIMA FISIK (3 PIHAK) -->
        <div class="signatures-grid">
            <div class="sig-box">
                <span class="sig-title">Yang Menyerahkan (Toko / Gudang)</span>
                <div style="flex: 1;"></div>
                <div>
                    <div class="sig-name">{{ $return->creator?->name ?? 'Staf Operasional' }}</div>
                    <div class="sig-sub">Dibuat: {{ $return->created_at ? $return->created_at->format('d/m/Y H:i') : '-' }}</div>
                </div>
            </div>

            <div class="sig-box">
                <span class="sig-title">Mengetahui &amp; Menyetujui</span>
                <div style="flex: 1;"></div>
                <div>
                    <div class="sig-name">{{ $return->approver?->name ?? ($return->status === 'COMPLETED' ? 'Store Manager' : '( Belum Di-approve )') }}</div>
                    <div class="sig-sub">Disetujui: {{ $return->approved_at ? \Carbon\Carbon::parse($return->approved_at)->format('d/m/Y H:i') : '-' }}</div>
                </div>
            </div>

            <div class="sig-box">
                <span class="sig-title">Yang Menerima (Driver / Vendor)</span>
                <div style="flex: 1;"></div>
                <div>
                    <div class="sig-name">( .................................................... )</div>
                    <div class="sig-sub">Nama Terang &amp; No. Polisi Kendaraan</div>
                </div>
            </div>
        </div>

        <!-- FOOTER CATATAN HUKUM & AUDIT TRAIL -->
        <div class="doc-footer">
            <div>
                &bull; Dokumen ini adalah bukti sah serah terima fisik barang retur dan dasar penyesuaian kewajiban hutang dagang.
            </div>
            <div>
                Dicetak: {{ now()->format('d/m/Y H:i:s') }} | Operator: {{ auth()->user()?->name ?? 'System' }}
            </div>
        </div>

    </div>

</body>
</html>
