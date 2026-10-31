<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReturnItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_return_items';

    protected $fillable = [
        'pos_return_id',
        'pos_transaction_item_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'base_quantity',
        'unit_price',
        'subtotal',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'base_quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function posReturn(): BelongsTo
    {
        return $this->belongsTo(PosReturn::class, 'pos_return_id');
    }

    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(PosTransactionItem::class, 'pos_transaction_item_id');
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
