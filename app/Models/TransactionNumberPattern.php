<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionNumberPattern extends Model
{
    use HasUuids;

    protected $table = 'transaction_number_patterns';

    protected $fillable = [
        'company_id',
        'document_type',
        'module',
        'name',
        'prefix',
        'pattern',
        'digits',
        'reset_cycle',
        'current_sequence',
        'last_period',
        'is_locked',
        'is_active',
    ];

    protected $casts = [
        'digits' => 'integer',
        'current_sequence' => 'integer',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Dapatkan daftar template penomoran standar bawaan sistem untuk seluruh modul ERP.
     */
    public static function getDefaultPatterns(): array
    {
        return [
            // PENJUALAN & KASIR
            'POS' => [
                'module' => 'penjualan',
                'name' => 'Struk Penjualan Kasir POS',
                'prefix' => 'POS',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'SO' => [
                'module' => 'penjualan',
                'name' => 'Pesanan Penjualan (Sales Order Grosir)',
                'prefix' => 'SO',
                'pattern' => '{PREFIX}/{Y}{m}/{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => false,
            ],
            'SI' => [
                'module' => 'penjualan',
                'name' => 'Faktur Penjualan Back-Office (Invoice)',
                'prefix' => 'INV',
                'pattern' => '{PREFIX}/{Y}{m}/{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'SR' => [
                'module' => 'penjualan',
                'name' => 'Retur Penjualan / Tukar Barang Konsumen',
                'prefix' => 'SR',
                'pattern' => '{PREFIX}/{Y}{m}/{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],

            // PEMBELIAN & SUPPLIER
            'PO' => [
                'module' => 'pembelian',
                'name' => 'Pesanan Pembelian (Purchase Order)',
                'prefix' => 'PO',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => false,
            ],
            'GRN' => [
                'module' => 'pembelian',
                'name' => 'Penerimaan Barang Supplier (Goods Receipt)',
                'prefix' => 'GRN',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'PR' => [
                'module' => 'pembelian',
                'name' => 'Retur Pembelian ke Supplier',
                'prefix' => 'PR',
                'pattern' => '{PREFIX}/{Y}{m}/{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],

            // PERSEDIAAN & GUDANG
            'OPN' => [
                'module' => 'persediaan',
                'name' => 'Dokumen Stock Opname Fisik Gudang',
                'prefix' => 'OPN',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'TR' => [
                'module' => 'persediaan',
                'name' => 'Surat Jalan Transfer Antar Cabang',
                'prefix' => 'TR',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'ADJ' => [
                'module' => 'persediaan',
                'name' => 'Penyesuaian Stok Harian & Karantina',
                'prefix' => 'ADJ',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'IN' => [
                'module' => 'persediaan',
                'name' => 'Item Masuk Non-Beli (Stock In Hibah/Sample)',
                'prefix' => 'IN',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'OUT' => [
                'module' => 'persediaan',
                'name' => 'Item Keluar Non-Jual (Stock Out Rusak/Promosi)',
                'prefix' => 'OUT',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],

            // AKUNTANSI & KEUANGAN
            'JRN' => [
                'module' => 'akuntansi',
                'name' => 'Bukti Jurnal Umum (General Ledger Entry)',
                'prefix' => 'JRN',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
            'CAS' => [
                'module' => 'akuntansi',
                'name' => 'Bukti Kas & Bank Masuk / Keluar Operasional',
                'prefix' => 'CAS',
                'pattern' => '{PREFIX}-{Y}{m}-{SEQ}',
                'digits' => 4,
                'reset_cycle' => 'MONTHLY',
                'is_locked' => true,
            ],
        ];
    }
}
