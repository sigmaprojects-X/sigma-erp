<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cashier_shifts';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'cashier_id',
        'shift_number',
        'starting_cash',
        'status',
        'opened_at',
        'closed_at',
        'actual_cash_counted',
        'expected_cash',
        'cash_discrepancy',
        'discrepancy_resolved',
        'resolved_by',
        'resolution_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starting_cash' => 'decimal:2',
            'actual_cash_counted' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_discrepancy' => 'decimal:2',
            'discrepancy_resolved' => 'boolean',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
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
     * Get the store.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the cashier user.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the transactions created during this shift.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class);
    }

    /**
     * Get the cash drops during this shift.
     */
    public function cashDrops(): HasMany
    {
        return $this->hasMany(CashDrop::class);
    }
}
