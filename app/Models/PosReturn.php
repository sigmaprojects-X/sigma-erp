<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosReturn extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_returns';

    protected $fillable = [
        'company_id',
        'store_id',
        'pos_transaction_id',
        'cashier_shift_id',
        'processed_by',
        'approved_by',
        'return_number',
        'return_date',
        'refund_method',
        'subtotal_returned',
        'tax_returned',
        'total_refund_amount',
        'reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'return_date' => 'date',
            'subtotal_returned' => 'decimal:2',
            'tax_returned' => 'decimal:2',
            'total_refund_amount' => 'decimal:2',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosReturnItem::class);
    }
}
