<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentSettlementItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'consignment_settlement_items';

    protected $fillable = [
        'consignment_settlement_id',
        'consignment_receipt_item_id',
        'product_id',
        'product_unit_id',
        'settled_quantity',
        'unit_cost',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'settled_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_amount' => 'decimal:2',
        ];
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ConsignmentSettlement::class, 'consignment_settlement_id');
    }

    public function receiptItem(): BelongsTo
    {
        return $this->belongsTo(ConsignmentReceiptItem::class, 'consignment_receipt_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
