<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Z-REPORT PENUTUPAN SHIFT - {{ $shift->shift_number }}</title>
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
        .report-box {
            max-width: 650px;
            margin: 0 auto;
            background: #fff;
            padding: 24px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .toolbar {
            max-width: 650px;
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
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .company-title {
            font-size: 15pt;
            font-weight: 900;
        }
        .report-title {
            font-size: 13pt;
            font-weight: 900;
            margin-top: 4px;
            letter-spacing: 1px;
        }
        .sub-header {
            font-size: 9pt;
            color: #333;
            margin-top: 4px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 12px;
        }
        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .section-title {
            font-size: 10pt;
            font-weight: 900;
            text-transform: uppercase;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 4px 0;
            margin: 12px 0 6px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        .data-table td {
            padding: 3px 0;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .bold {
            font-weight: bold;
        }
        .highlight-row {
            font-weight: 900;
            font-size: 10.5pt;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .discrepancy-box {
            margin-top: 12px;
            padding: 10px;
            border: 2px solid #000;
            text-align: center;
        }
        .discrepancy-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .discrepancy-val {
            font-size: 14pt;
            font-weight: 900;
            margin-top: 2px;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            min-width: 140px;
            padding-top: 4px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .report-box {
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
        <a href="{{ route('reports.shifts') }}" class="btn">
            ← Kembali ke Daftar Shift
        </a>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak Laporan (Print / Thermal 80mm)
            </button>
        </div>
    </div>

    <div class="report-box">
        <!-- HEADER -->
        <div class="header">
            <div class="company-title">{{ $shift->store->company->name ?? 'SIGMA ERP & POS' }}</div>
            <div class="sub-header">
                <strong>{{ $shift->store->name ?? 'Outlet Penjualan' }}</strong> ({{ $shift->store->code ?? 'STR' }})<br>
                {{ $shift->store->address ?? 'Alamat Outlet' }}
            </div>
            <div class="report-title">*** LAPORAN Z-REPORT PENUTUPAN KASIR ***</div>
            <div style="font-size: 8.5pt; color: #444; margin-top: 2px;">
                DOKUMEN FINANSIAL RAHASIA (AUDIT ONLY)
            </div>
        </div>

        <!-- INFO SHIFT -->
        <table class="meta-table">
            <tr>
                <td style="width: 130px;">Nomor Shift</td>
                <td style="width: 10px;">:</td>
                <td><strong>{{ $shift->shift_number }}</strong></td>
            </tr>
            <tr>
                <td>Petugas Kasir</td>
                <td>:</td>
                <td><strong>{{ $shift->cashier->name ?? 'Kasir' }}</strong></td>
            </tr>
            <tr>
                <td>Waktu Buka Shift</td>
                <td>:</td>
                <td>{{ $shift->opened_at ? $shift->opened_at->format('d/m/Y H:i:s') : '-' }}</td>
            </tr>
            <tr>
                <td>Waktu Tutup Shift</td>
                <td>:</td>
                <td>{{ $shift->closed_at ? $shift->closed_at->format('d/m/Y H:i:s') : '-' }}</td>
            </tr>
        </table>

        <!-- SECTION 1: REKONSILIASI KAS LACI (BLIND CLOSING AUDIT) -->
        <div class="section-title">1. REKONSILIASI KAS LACI KASIR (CASH AUDIT)</div>
        <table class="data-table">
            <tr>
                <td>(+) Modal Awal Kasir (Starting Cash)</td>
                <td class="text-right">Rp {{ number_format((float) $shift->starting_cash, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>(+) Penjualan Tunai Bersih (Cash Sales)</td>
                <td class="text-right">Rp {{ number_format($totalCash, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>(-) Kas Drop / Setoran Brankas ({{ $shift->cashDrops->count() }}x)</td>
                <td class="text-right">Rp {{ number_format($totalDrops, 0, ',', '.') }}</td>
            </tr>
            <tr class="highlight-row">
                <td>(=) TOTAL KAS SISTEM SEHARUSNYA:</td>
                <td class="text-right">Rp {{ number_format((float) $shift->expected_cash, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="bold">Uang Fisik Dihitung Kasir (Blind Count):</td>
                <td class="text-right bold">Rp {{ number_format((float) $shift->actual_cash_counted, 0, ',', '.') }}</td>
            </tr>
        </table>

        <!-- KOTAK SELISIH / DISCREPANCY -->
        @php $diff = (float) $shift->cash_discrepancy; @endphp
        <div class="discrepancy-box">
            <div class="discrepancy-title">STATUS SELISIH FISIK VS SISTEM:</div>
            @if(abs($diff) < 0.01)
                <div class="discrepancy-val" style="color: #047857;">BALANCE (PAS / SESUAI)</div>
            @elseif($diff > 0)
                <div class="discrepancy-val" style="color: #b45309;">LEBIH KAS: +Rp {{ number_format($diff, 0, ',', '.') }}</div>
            @else
                <div class="discrepancy-val" style="color: #b91c1c;">KURANG KAS: -Rp {{ number_format(abs($diff), 0, ',', '.') }}</div>
            @endif
        </div>

        <!-- SECTION 2: BREAKDOWN METODE PEMBAYARAN -->
        <div class="section-title">2. RINGKASAN OMZET PER METODE BAYAR</div>
        <table class="data-table">
            <tr>
                <td>Tunai / Cash ({{ $cashTxs->count() }} trx)</td>
                <td class="text-right">Rp {{ number_format($totalCash, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Transfer Bank ({{ $transferTxs->count() }} trx)</td>
                <td class="text-right">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>QRIS ({{ $qrisTxs->count() }} trx)</td>
                <td class="text-right">Rp {{ number_format($totalQris, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Split Payment ({{ $splitTxs->count() }} trx)</td>
                <td class="text-right">Rp {{ number_format((float) $splitTxs->sum('paid_amount'), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Piutang Dagang / Tempo ({{ $creditTxs->count() }} trx)</td>
                <td class="text-right">Rp {{ number_format($totalCredit, 0, ',', '.') }}</td>
            </tr>
            @if($totalVoucher > 0)
                <tr style="color: #666;">
                    <td>Potongan Voucher Belanja</td>
                    <td class="text-right">-Rp {{ number_format($totalVoucher, 0, ',', '.') }}</td>
                </tr>
            @endif
            @if($totalPointsDiscount > 0)
                <tr style="color: #666;">
                    <td>Potongan Poin Reward ({{ $totalPointsRedeemed }} Pts)</td>
                    <td class="text-right">-Rp {{ number_format($totalPointsDiscount, 0, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="highlight-row">
                <td>GRAND TOTAL OMZET PENJUALAN:</td>
                <td class="text-right">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>
        </table>

        <!-- SECTION 3: STATISTIK TRANSAKSI -->
        <div class="section-title">3. STATISTIK PENJUALAN SHIFT</div>
        <table class="data-table">
            <tr>
                <td>Total Faktur Berhasil Terbit</td>
                <td class="text-right bold">{{ $shift->transactions->count() }} Faktur</td>
            </tr>
            <tr>
                <td>Total Barang Fisik Terjual</td>
                <td class="text-right bold">
                    {{ (float) $shift->transactions->flatMap->items->sum('quantity') }} Item
                </td>
            </tr>
        </table>

        <!-- TANDA TANGAN SERAH TERIMA -->
        <div class="signatures">
            <div>
                <div>Kasir Yang Menyerahkan,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $shift->cashier->name ?? 'Kasir' }} )</div>
            </div>
            <div>
                <div>Auditor Keuangan / Store Manager,</div>
                <div class="sig-space"></div>
                <div class="sig-name">( .......................... )</div>
            </div>
        </div>

        <div style="text-align: center; font-size: 8pt; color: #666; margin-top: 24px;">
            Dicetak secara resmi dari Sistem SIGMA ERP pada {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

</body>
</html>
