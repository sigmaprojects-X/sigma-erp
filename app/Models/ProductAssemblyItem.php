<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAssemblyItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_assembly_items';

    protected $fillable = [
        'product_assembly_id',
        'component_product_id',
        'component_product_unit_id',
        'quantity',
        'base_quantity',
        'unit_cost',
        'total_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'base_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(ProductAssembly::class, 'product_assembly_id');
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function componentUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'component_product_unit_id');
    }
}
