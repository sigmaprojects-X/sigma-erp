<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductBatch extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_batches';

    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'batch_number',
        'expired_date',
        'manufacture_date',
        'initial_quantity',
        'current_quantity',
        'unit_cost',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expired_date' => 'date',
            'manufacture_date' => 'date',
            'initial_quantity' => 'decimal:4',
            'current_quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function posItems(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class, 'batch_id');
    }

    /**
     * Scope untuk query FEFO (First Expired First Out).
     * Mengambil batch yang masih memiliki stok aktif, diurutkan dari tanggal expired terdekat.
     */
    public function scopeFefoOrder(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE')
            ->where('current_quantity', '>', 0)
            ->whereDate('expired_date', '>=', today())
            ->orderBy('expired_date', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Scope untuk batch yang mendekati expired dalam N hari.
     */
    public function scopeNearExpired(Builder $query, int $days = 30): Builder
    {
        $today = today();
        $targetDate = today()->addDays($days);

        return $query->where('status', 'ACTIVE')
            ->where('current_quantity', '>', 0)
            ->whereBetween('expired_date', [$today, $targetDate])
            ->orderBy('expired_date', 'asc');
    }

    /**
     * Scope untuk batch yang sudah kedaluwarsa.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'EXPIRED')
                ->orWhere(function ($sub) {
                    $sub->where('status', 'ACTIVE')
                        ->whereDate('expired_date', '<', today());
                });
        })->where('current_quantity', '>', 0)
            ->orderBy('expired_date', 'asc');
    }

    /**
     * Hitung sisa hari menuju kedaluwarsa.
     */
    public function getDaysRemainingAttribute(): int
    {
        if (! $this->expired_date) {
            return 9999;
        }

        return (int) today()->diffInDays(Carbon::parse($this->expired_date), false);
    }
}
