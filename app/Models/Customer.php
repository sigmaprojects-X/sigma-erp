<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'customer_tier_id',
        'code',
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'current_credit_balance',
        'credit_term_days',
        'deposit_balance',
        'loyalty_points',
        'lifetime_points',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'current_credit_balance' => 'decimal:2',
            'deposit_balance' => 'decimal:2',
            'credit_term_days' => 'integer',
            'loyalty_points' => 'integer',
            'lifetime_points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Sisa plafon kredit yang masih tersedia untuk transaksi tempo.
     */
    public function getAvailableCreditAttribute(): float
    {
        return max(0.0, (float) $this->credit_limit - (float) $this->current_credit_balance);
    }

    /**
     * Cek apakah pelanggan diperbolehkan mengambil transaksi Tempo dengan nominal tertentu.
     */
    public function canTakeCredit(float $amount): bool
    {
        if ((float) $this->credit_limit <= 0) {
            return false;
        }

        return ((float) $this->current_credit_balance + $amount) <= (float) $this->credit_limit;
    }

    /**
     * Cek apakah pelanggan ini adalah Pelanggan Umum / Guest Walk-in.
     */
    public function isGeneralOrWalkIn(): bool
    {
        $code = strtoupper((string) $this->code);
        $name = strtolower((string) $this->name);

        if (in_array($code, ['UMUM', 'GENERAL', 'CUST-GENERAL', 'WALK-IN', 'CUST-000', 'GUEST'])) {
            return true;
        }

        if (str_contains($name, 'umum') || str_contains($name, 'walk-in') || str_contains($name, 'guest')) {
            return true;
        }

        return false;
    }

    /**
     * Get the company that owns the customer.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the store that owns the customer (optional).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(CustomerDeposit::class);
    }

    /**
     * Get the price tier associated with this customer.
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(CustomerTier::class, 'customer_tier_id');
    }

    /**
     * Get the price tier associated with this customer (alias).
     */
    public function customerTier(): BelongsTo
    {
        return $this->belongsTo(CustomerTier::class, 'customer_tier_id');
    }

    /**
     * Get the POS transactions made by this customer.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    /**
     * Get debt / receivable payments made by this customer.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * Get loyalty point mutations for this customer.
     */
    public function pointMutations(): HasMany
    {
        return $this->hasMany(CustomerPointMutation::class);
    }
}
