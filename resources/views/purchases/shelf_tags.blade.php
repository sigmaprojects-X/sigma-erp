<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label Rak (Price Tag) - {{ $purchase->reference_number }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            padding: 20px;
        }

        .no-print-bar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-print {
            background-color: #10b981;
            color: white;
            font-weight: 700;
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        .btn-back {
            background-color: #475569;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        /* GRID LABEL RAK (STANDARD SHELF TAG SHEET) */
        .shelf-tags-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            max-width: 900px;
            margin: 0 auto;
        }

        .shelf-tag {
            background: white;
            border: 2px solid #0f172a;
            border-radius: 6px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 140px;
            page-break-inside: avoid;
        }

        .tag-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
        }

        .tag-name {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin: 6px 0;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .tag-body {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: auto;
        }

        .tag-price-wrap {
            text-align: right;
        }

        .tag-price-label {
            font-size: 9px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tag-price {
            font-size: 22px;
            font-weight: 900;
            color: #0f172a;
            font-family: "Courier New", monospace;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .tag-sku-barcode {
            font-size: 9px;
            color: #475569;
            font-family: monospace;
        }

        .barcode-lines {
            font-family: 'Libre Barcode 39', 'Courier New', monospace;
            font-size: 20px;
            letter-spacing: 2px;
            line-height: 1;
            color: #0f172a;
            margin-top: 2px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .shelf-tags-container {
                max-width: 100%;
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }
            .shelf-tag {
                border: 1.5px solid #000;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div>
            <strong>🏷️ Cetak Label Rak (Shelf Tag)</strong>
            <span style="font-size: 12px; opacity: 0.8; margin-left: 10px;">Faktur: {{ $purchase->reference_number }} | {{ count($purchase->items) }} Barang</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('purchases.show', $purchase->id) }}" class="btn-back">← Kembali ke Faktur</a>
            <button onclick="window.print()" class="btn-print">🖨️ Cetak Label Sekarang (Ctrl+P)</button>
        </div>
    </div>

    <div class="shelf-tags-container">
        @foreach($purchase->items as $it)
            @php
                $baseUnit = $it->product->units->firstWhere('is_base_unit', true) ?? $it->productUnit;
                $retailPrice = $it->product->prices
                    ->where('product_unit_id', $baseUnit->id)
                    ->where('min_quantity', 1.0)
                    ->first();
                $displayPrice = $it->new_selling_price ?: ($retailPrice ? (float) $retailPrice->price : 0);
                $barcode = $it->product->barcodes->first()?->barcode ?: $it->product->sku;
            @endphp
            <div class="shelf-tag">
                <div class="tag-header">
                    <span>SIGMA MART</span>
                    <span>Tgl: {{ date('d/m/y') }}</span>
                </div>

                <div class="tag-name">
                    {{ $it->product->name }}
                </div>

                <div class="tag-body">
                    <div>
                        <div class="tag-sku-barcode">SKU: {{ $it->product->sku }}</div>
                        <div class="tag-sku-barcode">Satuan: <strong>{{ $baseUnit->unit_code }}</strong></div>
                        <div class="barcode-lines">*{{ $barcode }}*</div>
                    </div>

                    <div class="tag-price-wrap">
                        <div class="tag-price-label">Harga Eceran:</div>
                        <div class="tag-price">
                            Rp {{ number_format($displayPrice, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</body>
</html>
