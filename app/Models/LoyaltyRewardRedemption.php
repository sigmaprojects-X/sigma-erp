<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyRewardRedemption extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'loyalty_reward_redemptions';

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'loyalty_reward_id',
        'redemption_number',
        'points_used',
        'quantity',
        'redemption_date',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points_used' => 'integer',
            'quantity' => 'decimal:4',
            'redemption_date' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loyaltyReward(): BelongsTo
    {
        return $this->belongsTo(LoyaltyReward::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
