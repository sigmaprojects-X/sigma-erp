<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_movements';

    protected $fillable = [
        'company_id',
        'store_id',
        'movement_number',
        'movement_type',
        'movement_date',
        'category_reason',
        'recipient_or_source',
        'status',
        'total_items',
        'total_cost_value',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'total_items' => 'integer',
            'total_cost_value' => 'decimal:4',
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

    public function items(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
