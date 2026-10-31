<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequirementAllocation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_requirement_allocations';

    protected $fillable = [
        'stock_requirement_id',
        'allocation_type',
        'reference_id',
        'reference_item_id',
        'reference_number',
        'idempotency_key',
        'initial_allocated_base_qty',
        'received_base_qty',
        'released_base_qty',
        'outstanding_base_qty',
        'status',
        'allocated_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'initial_allocated_base_qty' => 'decimal:4',
            'received_base_qty' => 'decimal:4',
            'released_base_qty' => 'decimal:4',
            'outstanding_base_qty' => 'decimal:4',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(StockRequirement::class, 'stock_requirement_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeCommitted(Builder $query): Builder
    {
        return $query->where('status', 'COMMITTED');
    }
}
