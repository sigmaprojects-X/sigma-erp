<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentReceiptItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'consignment_receipt_items';

    protected $fillable = [
        'consignment_receipt_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'unit_cost',
        'sold_quantity',
        'returned_quantity',
        'settled_quantity',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'sold_quantity' => 'decimal:4',
            'returned_quantity' => 'decimal:4',
            'settled_quantity' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ConsignmentReceipt::class, 'consignment_receipt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    /**
     * Sisa kuantitas terjual yang belum dibuatkan tagihan (unsettled sold quantity).
     */
    public function getUnsettledSoldQuantityAttribute(): float
    {
        return max(0.0, (float) $this->sold_quantity - (float) $this->settled_quantity);
    }
}
