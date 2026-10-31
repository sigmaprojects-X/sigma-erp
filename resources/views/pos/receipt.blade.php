@php
    $effectiveCompany = $transaction->company ?? $company ?? \App\Models\Company::first();
    $effectiveStore = $transaction->store ?? $store ?? \App\Models\Store::first();
    $paperSize = request('paper', $paperSize ?? ($effectiveCompany?->receipt_paper_size ?? '58'));
    
    // Konfigurasi Font
    $fontFamilyChoice = $effectiveCompany?->receipt_font_family ?? 'monospace';
    $fontFamilyCss = match($fontFamilyChoice) {
        'courier' => "'Courier New', Courier, monospace",
        'sans-serif' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
        default => "'Courier New', Courier, monospace",
    };

    $fontSizeChoice = $effectiveCompany?->receipt_font_size ?? 'normal';
    $baseFontSize = match($fontSizeChoice) {
        'compact' => ($paperSize == '80' ? '11px' : '9.5px'),
        'large' => ($paperSize == '80' ? '13.5px' : '11.5px'),
        default => ($paperSize == '80' ? '12px' : '10.5px'),
    };

    $headerTitleSize = match($fontSizeChoice) {
        'compact' => ($paperSize == '80' ? '13px' : '11px'),
        'large' => ($paperSize == '80' ? '15.5px' : '13.5px'),
        default => ($paperSize == '80' ? '14px' : '12px'),
    };

    $copiesCount = (int) ($effectiveCompany?->receipt_print_copies ?? 1);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->invoice_number }} ({{ $paperSize }}mm)</title>
    <style>
        @page {
            margin: 0;
            size: {{ $paperSize == '80' ? '80mm auto' : '58mm auto' }};
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: {!! $fontFamilyCss !!};
            font-size: {{ $baseFontSize }};
            line-height: 1.25;
            color: #000;
            width: {{ $paperSize == '80' ? '72mm' : '48mm' }};
            margin: 0 auto;
            padding: {{ $paperSize == '80' ? '8px 10px' : '4px 6px' }};
            background: #fff;
            @if($effectiveCompany?->receipt_auto_cut)
            padding-bottom: 25px; /* Margin feed agar aman melewati pisau cutter */
            @endif
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }
        .divider {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
        }
        .double-divider {
            border-bottom: 2px double #000;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            vertical-align: top;
            padding: 1.5px 0;
        }
        .watermark {
            background-color: #000;
            color: #fff;
            padding: 3px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .barcode-box {
            font-family: 'Libre Barcode 39', 'Courier New', monospace;
            font-size: 26px;
            letter-spacing: 2px;
            line-height: 1;
            margin: 5px 0 2px 0;
        }
        .receipt-copy-badge {
            display: inline-block;
            border: 1px solid #000;
            padding: 1px 4px;
            font-size: 8.5px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        /* Toolbar Non-Cetak */
        .no-print-bar {
            background: #1e293b;
            color: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            width: 100%;
        }
        .btn-tool {
            padding: 4px 10px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 11px;
            text-decoration: none;
        }
        .btn-print { background: #10b981; color: #fff; }
        .btn-paper { background: #3b82f6; color: #fff; }
        .btn-close { background: #64748b; color: #fff; }

        @media print {
            .no-print-bar { display: none !important; }
            body {
                width: 100% !important;
                margin: 0 !important;
                padding: 4px !important;
                @if($effectiveCompany?->receipt_auto_cut)
                padding-bottom: 25px !important;
                @endif
            }
            .page-break {
                page-break-after: always;
                height: 0;
                display: block;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR NON-PRINT -->
    <div class="no-print-bar">
        <div>
            <strong>Struk POS</strong> (Ukuran: <strong>{{ $paperSize }}mm</strong>)
            @if(!empty($isTestPrint))
                <span style="background: #f59e0b; color: #000; padding: 1px 4px; border-radius: 3px; font-weight: bold; margin-left: 4px;">TEST PRINT</span>
            @endif
        </div>
        <div style="display: flex; gap: 6px;">
            <a href="{{ request()->fullUrlWithQuery(['paper' => ($paperSize == '58' ? '80' : '58')]) }}" class="btn-tool btn-paper">
                Ubah ke {{ ($paperSize == '58' ? '80mm' : '58mm') }}
            </a>
            <button onclick="window.print()" class="btn-tool btn-print">🖨️ Cetak</button>
            <button onclick="window.close()" class="btn-tool btn-close">Tutup</button>
        </div>
    </div>

    @for($copyIndex = 1; $copyIndex <= $copiesCount; $copyIndex++)

        @if($copiesCount > 1)
            <div class="center">
                <span class="receipt-copy-badge">
                    {{ $copyIndex === 1 ? '*** LEMBAR KONSUMEN ***' : '*** LEMBAR ARSIP KASIR / TOKO ***' }}
                </span>
            </div>
        @endif

        <!-- WATERMARK REPRINT (JIKA CETAK ULANG) -->
        @if(request('reprint') || $transaction->reprint_count > 0 || !empty($isReprint))
            <div class="watermark">
                ** CETAK ULANG / REPRINT **<br>
                <span style="font-size: 8.5px; font-weight: normal;">
                    {{ $transaction->last_reprinted_at ? $transaction->last_reprinted_at->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}
                    @if($transaction->reprint_count > 0)
                        (Ke-{{ $transaction->reprint_count }})
                    @endif
                </span>
            </div>
        @endif

        <!-- HEADER STRUK TOKO -->
        <div class="center">
            @if(($effectiveCompany?->receipt_show_logo ?? true) && $effectiveCompany?->logo_path && file_exists(public_path($effectiveCompany->logo_path)))
                <div style="margin-bottom: 4px;">
                    <img src="{{ asset($effectiveCompany->logo_path) }}" alt="Logo" style="max-height: {{ $paperSize == '80' ? '46px' : '38px' }}; max-width: 80%; filter: grayscale(100%) contrast(200%);">
                </div>
            @endif

            <div class="bold" style="font-size: {{ $headerTitleSize }};">{{ $effectiveCompany->name }}</div>
            <div class="bold">{{ $effectiveStore->name }}</div>
            <div style="font-size: 9px;">{{ $effectiveStore->address ?? 'Pusat Distribusi Retail & Grosir' }}</div>
            <div style="font-size: 9px;">Telp: {{ $effectiveStore->phone ?? '-' }}</div>

            @if($effectiveCompany?->receipt_header_line1)
                <div style="font-size: 8.5px; font-style: italic; margin-top: 1px;">{{ $effectiveCompany->receipt_header_line1 }}</div>
            @endif
            @if($effectiveCompany?->receipt_header_line2)
                <div style="font-size: 8.5px; font-style: italic;">{{ $effectiveCompany->receipt_header_line2 }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- INFO TRANSAKSI & KASIR BERTUGAS -->
        <table>
            <tr>
                <td>No: {{ $transaction->invoice_number }}</td>
                <td class="right">{{ $transaction->created_at->format('d/m/y H:i') }}</td>
            </tr>
            <tr>
                @if($effectiveCompany?->receipt_show_cashier ?? true)
                    <td class="bold">Kasir: {{ $transaction->cashier?->name ?? $transaction->shift?->cashier?->name ?? 'Kasir Bertugas' }}</td>
                @else
                    <td></td>
                @endif
                <td class="right">Tier: {{ $transaction->customerTier?->name ?? 'Retail' }}</td>
            </tr>
            @if(($effectiveCompany?->receipt_show_customer ?? true) && $transaction->customer && $transaction->customer->code !== 'CUST-UMUM')
                <tr>
                    <td colspan="2">Pelanggan: {{ $transaction->customer->name }}</td>
                </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- RINCIAN BARANG -->
        <table>
            @foreach($transaction->items as $item)
                @if(($effectiveCompany?->receipt_item_layout ?? 'compact_double') === 'single_line' && $paperSize == '80')
                    <tr>
                        <td class="bold" style="max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $item->product->name }}
                        </td>
                        <td class="center" style="white-space: nowrap; padding: 0 4px;">
                            {{ (int)$item->quantity }} {{ $item->unit->unit_code }}
                        </td>
                        <td class="right bold" style="white-space: nowrap;">
                            {{ number_format($item->total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                @else
                    <tr>
                        <td colspan="2" class="bold">{{ $item->product->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding-left: 6px;">
                            {{ (int)$item->quantity }} {{ $item->unit->unit_code }} x {{ number_format($item->unit_price, 0, ',', '.') }}
                        </td>
                        <td class="right bold">
                            {{ number_format($item->total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>

        <div class="divider"></div>

        <!-- TOTAL & PEMBAYARAN -->
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="right bold">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($transaction->discount_amount > 0)
                <tr>
                    <td>Diskon:</td>
                    <td class="right">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($transaction->voucher_discount > 0)
                <tr>
                    <td>Voucher ({{ $transaction->voucher?->code }}):</td>
                    <td class="right">-Rp {{ number_format($transaction->voucher_discount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if(($transaction->points_discount_amount ?? 0) > 0)
                <tr>
                    <td>Tukar Poin ({{ $transaction->points_redeemed }} Poin):</td>
                    <td class="right">-Rp {{ number_format($transaction->points_discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($transaction->tax_amount > 0)
                <tr>
                    <td>PPN:</td>
                    <td class="right">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if(isset($transaction->rounding_amount) && (float)$transaction->rounding_amount != 0)
                <tr>
                    <td>Pembulatan:</td>
                    <td class="right bold">{{ (float)$transaction->rounding_amount > 0 ? '+Rp ' : '-Rp ' }}{{ number_format(abs($transaction->rounding_amount), 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr style="font-size: {{ $headerTitleSize }};">
                <td class="bold">TOTAL:</td>
                <td class="right bold">Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="2" style="border-bottom: 1px dashed #000; padding: 2px 0;"></td></tr>

            @if($transaction->payment_method === 'SPLIT')
                @if((float)($transaction->split_non_cash_amount ?? 0) > 0)
                    <tr>
                        <td>Bayar (TUNAI):</td>
                        <td class="right bold">Rp {{ number_format($transaction->split_cash_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Bayar ({{ $transaction->split_non_cash_method ?? 'QRIS' }}):</td>
                        <td class="right bold">Rp {{ number_format($transaction->split_non_cash_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if($transaction->split_non_cash_method === 'TRANSFER' && $transaction->bank_name)
                        <tr>
                            <td style="font-size: 9px; padding-left: 6px;">Bank:</td>
                            <td class="right" style="font-size: 9px;">{{ $transaction->bank_name }} ({{ $transaction->bank_account_number }})</td>
                        </tr>
                    @endif
                    @if($transaction->change_amount > 0)
                        <tr>
                            <td class="bold">Kembali:</td>
                            <td class="right bold">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td>Bayar ({{ $transaction->split_payment_method ?? 'TUNAI' }}):</td>
                        <td class="right bold">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Sisa (TEMPO/KREDIT):</td>
                        <td class="right bold">Rp {{ number_format($transaction->credit_amount, 0, ',', '.') }}</td>
                    </tr>
                @endif
            @else
                <tr>
                    <td>Bayar ({{ $transaction->payment_method }}):</td>
                    <td class="right bold">Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</td>
                </tr>
                @if($transaction->payment_method === 'TRANSFER' && $transaction->bank_name)
                    <tr>
                        <td style="font-size: 9px; padding-left: 6px;">Bank Tujuan:</td>
                        <td class="right" style="font-size: 9px;">{{ $transaction->bank_name }} ({{ $transaction->bank_account_number }})</td>
                    </tr>
                @endif
                @if($transaction->payment_method === 'TEMPO')
                    <tr>
                        <td>Piutang (TEMPO):</td>
                        <td class="right bold">Rp {{ number_format($transaction->credit_amount ?: $transaction->grand_total, 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($transaction->payment_method === 'CASH')
                    <tr>
                        <td class="bold">Kembali:</td>
                        <td class="right bold">Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</td>
                    </tr>
                @endif
            @endif
        </table>

        <div class="double-divider"></div>

        <!-- FOOTER STRUK -->
        <div class="center" style="font-size: 9px; margin-top: 4px;">
            @php
                $footer1 = $effectiveCompany?->receipt_footer_line1 ?? $effectiveCompany?->receipt_footer ?? "Terima Kasih Atas Kunjungan Anda\nBarang yang dibeli tidak dapat ditukar/dikembalikan";
            @endphp
            <div>{!! nl2br(e($footer1)) !!}</div>
            
            @if($effectiveCompany?->receipt_footer_line2)
                <div style="margin-top: 3px; font-style: italic;">{{ $effectiveCompany->receipt_footer_line2 }}</div>
            @endif

            <div style="margin-top: 3px; font-weight: bold; letter-spacing: 0.5px;">SIGMA POS PRO</div>
        </div>

        <!-- BARCODE NO TRANSAKSI (OPSIONAL) -->
        @if($effectiveCompany?->receipt_show_barcode ?? true)
            <div class="center" style="margin-top: 5px;">
                <div class="barcode-box">*{{ $transaction->invoice_number }}*</div>
                <div style="font-size: 8px; font-mono; letter-spacing: 1px;">{{ $transaction->invoice_number }}</div>
            </div>
        @endif

        @if(preg_match('/\[FOOT_VOUCHER:(.*?)\|(.*?)\|(.*?)\]/', $transaction->notes ?? '', $vchMatches))
            <div style="border: 2px dashed #000; margin-top: 12px; padding: 8px 6px; text-align: center; border-radius: 4px;">
                <div style="font-weight: bold; font-size: 11px; letter-spacing: 1px;">*** VOUCHER BELANJA ***</div>
                <div style="font-size: 9px; margin: 3px 0;">Selamat! Anda Mendapatkan Voucher Diskon:</div>
                <div style="font-size: 15px; font-weight: bold; margin: 2px 0;">Rp {{ $vchMatches[2] }}</div>
                <div style="font-family: monospace; font-size: 13px; font-weight: bold; letter-spacing: 1.5px; margin: 5px 0; background: #eee; padding: 3px; border-radius: 3px;">
                    {{ $vchMatches[1] }}
                </div>
                <div style="font-size: 8px; color: #333; margin-top: 3px;">Gunakan kode di atas pada transaksi berikutnya</div>
                <div style="font-size: 8px; font-weight: bold;">Berlaku s/d {{ $vchMatches[3] }}</div>
            </div>
        @endif

        @if($copyIndex < $copiesCount)
            <div class="page-break" style="margin: 20px 0; border-top: 1px dotted #ccc;"></div>
        @endif

    @endfor

    <script>
        // Auto-print & Auto-close handler
        @if(request('autoprint'))
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 150);
            });
            window.addEventListener('afterprint', function() {
                setTimeout(function() {
                    window.close();
                }, 300);
            });
        @endif

        // Cash Drawer Kick Signal Helper
        function kickDrawerDirect() {
            try {
                // Notifikasi visual dan audio pulsa laci
                console.log('ESC/POS Drawer Kick Pulse: \x1b\x70\x00\x19\xfa');
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
