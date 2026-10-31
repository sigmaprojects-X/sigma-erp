<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgram extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'loyalty_programs';

    protected $fillable = [
        'company_id',
        'name',
        'spend_amount_per_point',
        'point_redemption_value',
        'min_points_to_redeem',
        'points_expiry_days',
        'allow_merchandise_redemption',
        'only_registered_customers',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'spend_amount_per_point' => 'decimal:2',
            'point_redemption_value' => 'decimal:2',
            'min_points_to_redeem' => 'integer',
            'points_expiry_days' => 'integer',
            'allow_merchandise_redemption' => 'boolean',
            'only_registered_customers' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
