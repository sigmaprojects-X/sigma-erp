<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Diskon, Voucher & Poin - SIGMA ERP</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            color: #000;
            margin: 15mm 15mm 15mm 15mm;
            line-height: 1.3;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .meta-table, .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 4px 6px;
        }
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .font-bold { font-weight: bold; }
        .summary-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 10px;
        }
        .metric-card {
            border: 1px solid #333;
            padding: 8px;
            flex: 1;
            background: #fafafa;
        }
        .metric-title {
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
        }
        .metric-value {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 3px;
        }
        @media print {
            .no-print { display: none; }
            @page { margin: 12mm; size: A4 portrait; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 15px; background: #e0f2fe; padding: 10px; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <strong>Preview Cetak Dokumen A4 Resmi</strong>
        </div>
        <button onclick="window.print()" style="background: #0284c7; color: #fff; border: none; padding: 6px 16px; border-radius: 4px; font-weight: bold; cursor: pointer;">
            Cetak Sekarang (Ctrl + P)
        </button>
    </div>

    <!-- KOP RESMI LAPORAN -->
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="title">{{ $company->name }}</div>
                <div>Laporan Analisa Promosi, Voucher Belanja, Poin Loyalitas & Diskon</div>
                <div style="font-size: 8pt; color: #555;">{{ $company->address ?? 'Head Office Retail' }} • Telp: {{ $company->phone ?? '-' }}</div>
            </div>
            <div style="text-align: right; font-size: 8pt;">
                <div><strong>Periode:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
                <div><strong>Cabang:</strong> {{ $selectedStore?->name ?? 'Semua Cabang Toko' }}</div>
                <div><strong>Dicetak:</strong> {{ now()->format('d/m/Y H:i') }} WIB</div>
            </div>
        </div>
    </div>

    <!-- RINGKASAN METRIK -->
    <div class="summary-box">
        <div class="metric-card">
            <div class="metric-title">Diskon Produk/Tier</div>
            <div class="metric-value font-mono">Rp {{ number_format($totalItemDiscounts, 0, ',', '.') }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Potongan Voucher</div>
            <div class="metric-value font-mono">Rp {{ number_format($totalVoucherDiscounts, 0, ',', '.') }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Potongan Poin Member</div>
            <div class="metric-value font-mono">Rp {{ number_format($totalPointsDiscounts, 0, ',', '.') }}</div>
            <div style="font-size: 7.5pt; color: #666;">({{ number_format($totalPointsRedeemed) }} Pts Ditukar)</div>
        </div>
        <div class="metric-card">
            <div class="metric-title">Total Penghematan Konsumen</div>
            <div class="metric-value font-mono">Rp {{ number_format($totalCustomerSavings, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- TABEL TRANSAKSI -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>No. Faktur POS</th>
                <th>Tanggal</th>
                <th>Member / Pelanggan</th>
                <th class="text-right">Diskon Produk</th>
                <th class="text-right">Potongan Voucher</th>
                <th class="text-right">Potongan Poin ⭐</th>
                <th class="text-right">Grand Total</th>
                <th class="text-center">Poin Baru</th>
                <th>Kasir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $t)
                <tr>
                    <td class="text-center font-mono">{{ $index + 1 }}</td>
                    <td class="font-mono font-bold">{{ $t->invoice_number }}</td>
                    <td>{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $t->customer?->name ?? 'Pelanggan Umum' }}</td>
                    <td class="text-right font-mono">{{ $t->discount_amount > 0 ? 'Rp '.number_format($t->discount_amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">{{ $t->voucher_discount > 0 ? 'Rp '.number_format($t->voucher_discount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">{{ $t->points_discount_amount > 0 ? 'Rp '.number_format($t->points_discount_amount, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono font-bold">Rp {{ number_format($t->grand_total, 0, ',', '.') }}</td>
                    <td class="text-center font-mono">{{ $t->points_earned > 0 ? '+'.$t->points_earned : '-' }}</td>
                    <td>{{ $t->cashier?->name ?? 'Kasir POS' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px;">Tidak ada transaksi promosi atau diskon pada periode terpilih.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f5f5f5; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL PENGHEMATAN:</td>
                <td class="text-right font-mono">Rp {{ number_format($totalItemDiscounts, 0, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format($totalVoucherDiscounts, 0, ',', '.') }}</td>
                <td class="text-right font-mono">Rp {{ number_format($totalPointsDiscounts, 0, ',', '.') }}</td>
                <td colspan="3" class="text-left font-mono">Total Akumulasi: Rp {{ number_format($totalCustomerSavings, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN AUDIT -->
    <div style="margin-top: 30px; display: flex; justify-content: space-between; page-break-inside: avoid;">
        <div style="text-align: center; width: 180px;">
            <div>Dibuat Oleh,</div>
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; font-weight: bold;">{{ auth()->user()?->name ?? 'Staff Admin' }}</div>
            <div style="font-size: 7.5pt; color: #555;">Operator / Kasir</div>
        </div>
        <div style="text-align: center; width: 180px;">
            <div>Diperiksa &amp; Diverifikasi,</div>
            <div style="height: 50px;"></div>
            <div style="border-top: 1px solid #000; font-weight: bold;">Manager Toko</div>
            <div style="font-size: 7.5pt; color: #555;">Kepala Cabang Retail</div>
        </div>
    </div>

</body>
</html>
