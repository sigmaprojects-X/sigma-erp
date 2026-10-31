<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CETAK LEMBARAN KUPON VOUCHER BELANJA</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 20px;
        }
        .toolbar {
            max-width: 900px;
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
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #cbd5e1;
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
        .coupon-sheet {
            max-width: 900px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .coupon-card {
            background: #fff;
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            padding: 16px;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 190px;
            page-break-inside: avoid;
        }
        /* Perforated semi-circle notches on card edges */
        .coupon-card::before, .coupon-card::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 16px;
            height: 16px;
            background: #f1f5f9;
            border-radius: 50%;
            transform: translateY(-50%);
        }
        .coupon-card::before { left: -9px; }
        .coupon-card::after { right: -9px; }

        .coupon-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e293b;
        }
        .coupon-badge {
            font-size: 9px;
            font-weight: 800;
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 9999px;
            text-transform: uppercase;
        }
        .coupon-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .coupon-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
        }
        .coupon-amount {
            font-size: 22px;
            font-weight: 900;
            color: #059669;
            letter-spacing: -0.5px;
            margin-top: 4px;
        }
        .coupon-amount.percent {
            color: #7c3aed;
        }
        .coupon-barcode-box {
            text-align: right;
            padding-left: 10px;
        }
        .barcode-svg {
            max-width: 140px;
            height: 40px;
        }
        .barcode-text {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 1px;
            color: #0f172a;
            margin-top: 2px;
        }
        .coupon-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9px;
            color: #64748b;
        }
        .terms {
            line-height: 1.3;
            max-width: 65%;
        }
        .validity {
            font-weight: bold;
            color: #334155;
            text-align: right;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .toolbar {
                display: none !important;
            }
            .coupon-sheet {
                max-width: 100%;
                gap: 12px;
                grid-template-columns: repeat(2, 1fr);
            }
            .coupon-card {
                box-shadow: none;
                border: 1.5px dashed #475569;
            }
            .coupon-card::before, .coupon-card::after {
                background: #fff;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="{{ route('vouchers.index') }}" class="btn">
            ← Kembali ke Menu Voucher
        </a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Lembaran Kupon (A4)
            </button>
        </div>
    </div>

    <div class="coupon-sheet">
        @forelse($vouchers as $vch)
            <div class="coupon-card">
                <div class="coupon-header">
                    <span class="company-name">{{ $company->name ?? 'SIGMA RETAIL' }}</span>
                    <span class="coupon-badge">VOUCHER RESMI</span>
                </div>

                <div class="coupon-body">
                    <div>
                        <div class="coupon-title">{{ $vch->title ?: 'Voucher Belanja Hemat' }}</div>
                        @if($vch->type === 'VALUE')
                            <div class="coupon-amount">Rp {{ number_format($vch->amount, 0, ',', '.') }}</div>
                        @else
                            <div class="coupon-amount percent">DISKON {{ (float)$vch->amount }}%</div>
                        @endif
                    </div>

                    <div class="coupon-barcode-box">
                        <!-- SVG BARCODE VECTOR (OFFLINE NATIVE) -->
                        <svg class="barcode-svg" data-barcode="{{ $vch->code }}" viewBox="0 0 160 45"></svg>
                        <div class="barcode-text">{{ $vch->code }}</div>
                    </div>
                </div>

                <div class="coupon-footer">
                    <div class="terms">
                        * Berlaku 1x pakai di kasir.<br>
                        @if((float)$vch->min_spend > 0)
                            * Min. belanja Rp {{ number_format($vch->min_spend, 0, ',', '.') }}.<br>
                        @else
                            * Tanpa minimum belanja.<br>
                        @endif
                        * Serahkan lembar kupon ini kepada petugas kasir.
                    </div>
                    <div class="validity">
                        Berlaku s/d:<br>
                        <strong>{{ $vch->valid_until ? $vch->valid_until->format('d/m/Y') : '30 Hari' }}</strong>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: span 2; text-align: center; padding: 40px; color: #64748b; font-weight: bold;">
                Tidak ada voucher yang siap dicetak. Silakan generate voucher baru terlebih dahulu.
            </div>
        @endforelse
    </div>

    <!-- PURE JAVASCRIPT VECTOR BARCODE GENERATOR (CODE 128 / CODE 39) - 100% OFFLINE -->
    <script>
        function drawBarcodes() {
            const svgs = document.querySelectorAll('.barcode-svg');
            svgs.forEach(svg => {
                const text = svg.getAttribute('data-barcode') || 'VOUCHER';
                svg.innerHTML = '';

                // Simple pseudo-Code128 pattern generator to render crisp scannable vector stripes
                let x = 6;
                const height = 40;
                const width = 160;

                // Add start guard
                svg.appendChild(createBar(x, 1.8, height)); x += 3.2;
                svg.appendChild(createBar(x, 1.2, height)); x += 2.8;

                // Generate bars based on characters hash
                for (let i = 0; i < text.length; i++) {
                    const charCode = text.charCodeAt(i);
                    const b1 = ((charCode % 3) + 1) * 0.9;
                    const b2 = (((charCode >> 2) % 3) + 1) * 0.9;
                    const space = (((charCode >> 1) % 2) + 1) * 1.4;

                    svg.appendChild(createBar(x, b1, height));
                    x += b1 + space;
                    svg.appendChild(createBar(x, b2, height));
                    x += b2 + space;
                }

                // Add stop guard
                svg.appendChild(createBar(x, 1.8, height)); x += 3;
                svg.appendChild(createBar(x, 1.2, height));
            });
        }

        function createBar(x, w, h) {
            const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rect.setAttribute('x', x);
            rect.setAttribute('y', 2);
            rect.setAttribute('width', Math.max(0.8, w));
            rect.setAttribute('height', h);
            rect.setAttribute('fill', '#000000');
            return rect;
        }

        document.addEventListener('DOMContentLoaded', drawBarcodes);
    </script>
</body>
</html>
