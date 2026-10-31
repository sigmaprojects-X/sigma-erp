<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_units';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'unit_code',
        'unit_name',
        'conversion_factor',
        'weight_gram',
        'length_cm',
        'width_cm',
        'height_cm',
        'point_reward',
        'commission_rate',
        'is_base_unit',
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
            'conversion_factor' => 'decimal:4',
            'weight_gram' => 'decimal:2',
            'length_cm' => 'decimal:2',
            'width_cm' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'point_reward' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'is_base_unit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the company that owns the unit.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store that owns the unit.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the product that uses this unit.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the barcodes associated with this unit.
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /**
     * Get the prices associated with this unit.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
