<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'product_unit_id',
        'unit_code',
        'conversion_factor',
        'quantity_sent',
        'base_quantity_sent',
        'quantity_received',
        'base_quantity_received',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'quantity_sent' => 'decimal:2',
            'base_quantity_sent' => 'decimal:4',
            'quantity_received' => 'decimal:2',
            'base_quantity_received' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
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
