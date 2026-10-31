<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_prices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'product_unit_id',
        'customer_tier_id',
        'min_quantity',
        'price',
        'margin_percentage',
        'discount_1',
        'discount_2',
        'discount_3',
        'discount_4',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_quantity' => 'decimal:2',
            'price' => 'decimal:2',
            'margin_percentage' => 'decimal:2',
            'discount_1' => 'decimal:2',
            'discount_2' => 'decimal:2',
            'discount_3' => 'decimal:2',
            'discount_4' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the parent company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store (if this is a store-specific price override).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the packaging unit (Dimensi 1).
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    /**
     * Alias for unit relationship.
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    /**
     * Get the customer tier (Dimensi 2).
     */
    public function customerTier(): BelongsTo
    {
        return $this->belongsTo(CustomerTier::class, 'customer_tier_id');
    }
}
