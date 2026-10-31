<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_stocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'physical_quantity',
        'reserved_quantity',
        'quarantined_quantity',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'physical_quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'quarantined_quantity' => 'decimal:4',
        ];
    }

    /**
     * Formula Available Stock = Physical - Reserved - Quarantined (AGENTS.md Section 2)
     */
    protected function availableQuantity(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, (float) $this->physical_quantity - (float) $this->reserved_quantity - (float) $this->quarantined_quantity)
        );
    }

    /**
     * Get the parent company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store where stock is located.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
