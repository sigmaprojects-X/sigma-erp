<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fixed_assets';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'asset_code',
        'name',
        'category',
        'acquisition_date',
        'acquisition_cost',
        'salvage_value',
        'useful_life_months',
        'monthly_depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'last_depreciation_date',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'salvage_value' => 'decimal:2',
            'useful_life_months' => 'integer',
            'monthly_depreciation_amount' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
            'book_value' => 'decimal:2',
            'last_depreciation_date' => 'date',
        ];
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store where asset is placed.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get depreciation history records.
     */
    public function depreciationRecords(): HasMany
    {
        return $this->hasMany(AssetDepreciationRecord::class, 'fixed_asset_id');
    }
}
