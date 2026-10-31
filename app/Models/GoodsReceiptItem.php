<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'goods_receipt_items';

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'product_unit_id',
        'unit_code',
        'conversion_factor',
        'quantity_received',
        'base_quantity',
        'estimated_unit_price',
        'allocated_landed_cost',
        'effective_cost_price',
        'subtotal',
        'batch_number',
        'expiry_date',
        'physical_condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'quantity_received' => 'decimal:2',
            'base_quantity' => 'decimal:4',
            'estimated_unit_price' => 'decimal:2',
            'allocated_landed_cost' => 'decimal:2',
            'effective_cost_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'expiry_date' => 'date',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
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
