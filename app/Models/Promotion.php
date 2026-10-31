<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'promotions';

    protected $fillable = [
        'company_id',
        'store_id',
        'code',
        'name',
        'description',
        'type', // BUY_X_GET_Y, TEBUS_MURAH, DISCOUNT_PERCENT, DISCOUNT_NOMINAL
        'buy_product_id',
        'buy_quantity',
        'reward_product_id',
        'reward_quantity',
        'special_price',
        'discount_percent',
        'discount_nominal',
        'min_spend',
        'start_date',
        'end_date',
        'days_of_week',
        'is_active',
        'max_uses',
        'current_uses',
    ];

    protected function casts(): array
    {
        return [
            'buy_quantity' => 'decimal:4',
            'reward_quantity' => 'decimal:4',
            'special_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_nominal' => 'decimal:2',
            'min_spend' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'days_of_week' => 'array',
            'is_active' => 'boolean',
            'max_uses' => 'integer',
            'current_uses' => 'integer',
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

    public function buyProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'buy_product_id');
    }

    public function rewardProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'reward_product_id');
    }

    /**
     * Cek apakah promosi ini sedang berlaku pada tanggal dan kondisi tertentu.
     */
    public function isValidNow(float $spendAmount = 0.0, ?string $storeId = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->store_id && $storeId && $this->store_id !== $storeId) {
            return false;
        }

        $today = now()->format('Y-m-d');
        if ($this->start_date && $today < $this->start_date->format('Y-m-d')) {
            return false;
        }

        if ($this->end_date && $today > $this->end_date->format('Y-m-d')) {
            return false;
        }

        if ($this->days_of_week && is_array($this->days_of_week) && count($this->days_of_week) > 0) {
            $dayOfWeek = (int) now()->dayOfWeek; // 0 (Sun) to 6 (Sat)
            if (! in_array($dayOfWeek, $this->days_of_week)) {
                return false;
            }
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
