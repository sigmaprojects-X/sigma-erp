<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsignmentSettlement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'consignment_settlements';

    protected $fillable = [
        'company_id',
        'store_id',
        'supplier_id',
        'settlement_number',
        'settlement_date',
        'period_start',
        'period_end',
        'total_sold_quantity',
        'total_settlement_amount',
        'payment_status',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'period_start' => 'date',
            'period_end' => 'date',
            'total_sold_quantity' => 'decimal:4',
            'total_settlement_amount' => 'decimal:2',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentSettlementItem::class, 'consignment_settlement_id');
    }
}
