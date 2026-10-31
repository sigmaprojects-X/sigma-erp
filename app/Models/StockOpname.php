<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_opnames';

    protected $fillable = [
        'company_id',
        'store_id',
        'opname_number',
        'opname_date',
        'status',
        'is_blind',
        'total_system_quantity',
        'total_actual_quantity',
        'total_discrepancy_quantity',
        'total_discrepancy_value',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'is_blind' => 'boolean',
            'approved_at' => 'datetime',
            'total_system_quantity' => 'decimal:4',
            'total_actual_quantity' => 'decimal:4',
            'total_discrepancy_quantity' => 'decimal:4',
            'total_discrepancy_value' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
