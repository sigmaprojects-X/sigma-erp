<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vouchers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'code',
        'barcode',
        'title',
        'type',
        'amount',
        'min_spend',
        'trigger_min_spend',
        'is_printed_on_receipt',
        'status',
        'valid_from',
        'valid_until',
        'redeemed_at',
        'redeemed_in_transaction_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'min_spend' => 'decimal:2',
            'trigger_min_spend' => 'decimal:2',
            'is_printed_on_receipt' => 'boolean',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the voucher.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store (if voucher is store-specific).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Cek apakah voucher masih aktif dan valid untuk digunakan.
     */
    public function isValidForUse(float $cartTotal = 0.0): bool
    {
        $now = now();

        if ($this->status !== 'ISSUED') {
            return false;
        }

        if ($now->lt($this->valid_from) || $now->gt($this->valid_until)) {
            return false;
        }

        if ($cartTotal > 0 && $cartTotal < (float) $this->min_spend) {
            return false;
        }

        return true;
    }
}
