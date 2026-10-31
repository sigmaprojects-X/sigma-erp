<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPointMutation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'customer_point_mutations';

    protected $fillable = [
        'company_id',
        'customer_id',
        'pos_transaction_id',
        'type',
        'points',
        'balance_before',
        'balance_after',
        'expired_at',
        'is_expired',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',
            'expired_at' => 'date',
            'is_expired' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
