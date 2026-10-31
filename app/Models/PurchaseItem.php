<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_unit_id',
        'unit_code',
        'conversion_factor',
        'quantity',
        'base_quantity',
        'unit_price',
        'base_unit_price',
        'discount_percent',
        'subtotal',
        'allocated_shipping_cost',
        'old_cost_price',
        'new_cost_price',
        'is_price_increased',
        'is_selling_price_updated',
        'old_selling_price',
        'new_selling_price',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:4',
            'quantity' => 'decimal:2',
            'base_quantity' => 'decimal:4',
            'unit_price' => 'decimal:2',
            'base_unit_price' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'old_cost_price' => 'decimal:4',
            'new_cost_price' => 'decimal:4',
            'old_selling_price' => 'decimal:2',
            'new_selling_price' => 'decimal:2',
            'is_price_increased' => 'boolean',
            'is_selling_price_updated' => 'boolean',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
