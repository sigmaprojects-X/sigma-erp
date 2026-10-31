<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyReward extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'loyalty_rewards';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'points_required',
        'stock_quantity',
        'product_id',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'points_required' => 'integer',
            'stock_quantity' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRewardRedemption::class);
    }
}
