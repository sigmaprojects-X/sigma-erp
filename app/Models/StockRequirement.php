<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockRequirement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_requirements';

    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'target_stock',
        'available_stock_snapshot',
        'gross_deficit',
        'total_allocated',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_stock' => 'decimal:4',
            'available_stock_snapshot' => 'decimal:4',
            'gross_deficit' => 'decimal:4',
            'total_allocated' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(StockRequirementAllocation::class, 'stock_requirement_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            'OPEN',
            'PARTIALLY_ALLOCATED',
            'AWAITING_SUPPLY',
            'OVER_COMMITTED',
        ]);
    }
}
