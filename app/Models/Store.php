<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stores';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'phone',
        'pic_name',
        'address',
        'city',
        'postal_code',
        'is_active',
        'is_main_store',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_main_store' => 'boolean',
        ];
    }

    /**
     * Get the parent company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store-specific customer tiers.
     */
    public function customerTiers(): HasMany
    {
        return $this->hasMany(CustomerTier::class);
    }

    /**
     * Get the products assigned to this store.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the store-specific product prices.
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get the stock records in this store.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Get the stock ledger / mutations in this store.
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }
}
