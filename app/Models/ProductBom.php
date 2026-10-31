<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBom extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_boms';

    protected $fillable = [
        'company_id',
        'parent_product_id',
        'component_product_id',
        'component_product_unit_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
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
