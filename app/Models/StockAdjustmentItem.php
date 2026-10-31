<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_adjustment_items';

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'product_unit_id',
        'unit_code',
        'conversion_factor',
        'quantity',
        'base_quantity',
        'unit_cost',
        'total_cost',
        'action',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'quantity' => 'decimal:2',
            'base_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
        ];
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
