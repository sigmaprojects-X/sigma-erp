<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_opname_items';

    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'system_quantity',
        'actual_quantity',
        'discrepancy_quantity',
        'unit_cost',
        'discrepancy_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'decimal:4',
            'actual_quantity' => 'decimal:4',
            'discrepancy_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'discrepancy_value' => 'decimal:2',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class, 'stock_opname_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
