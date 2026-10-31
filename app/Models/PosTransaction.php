<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTransaction extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'cashier_shift_id',
        'customer_tier_id',
        'customer_id',
        'cashier_id',
        'sales_person_id',
        'invoice_number',
        'idempotency_key',
        'total_item_count',
        'subtotal',
        'discount_amount',
        'points_earned',
        'points_redeemed',
        'points_discount_amount',
        'voucher_id',
        'voucher_discount',
        'tax_amount',
        'tax_mode',
        'grand_total',
        'payment_method',
        'split_payment_method',
        'bank_name',
        'bank_account_number',
        'payment_reference',
        'paid_amount',
        'credit_amount',
        'remaining_receivable',
        'due_date',
        'payment_status',
        'change_amount',
        'rounding_amount',
        'split_cash_amount',
        'split_non_cash_amount',
        'split_non_cash_method',
        'status',
        'reprint_count',
        'last_reprinted_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_item_count' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'points_earned' => 'integer',
            'points_redeemed' => 'integer',
            'points_discount_amount' => 'decimal:2',
            'voucher_discount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'remaining_receivable' => 'decimal:2',
            'due_date' => 'date',
            'change_amount' => 'decimal:2',
            'rounding_amount' => 'decimal:2',
            'split_cash_amount' => 'decimal:2',
            'split_non_cash_amount' => 'decimal:2',
            'reprint_count' => 'integer',
            'last_reprinted_at' => 'datetime',
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
     * Get the shift.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    /**
     * Get the customer tier used for pricing.
     */
    public function customerTier(): BelongsTo
    {
        return $this->belongsTo(CustomerTier::class);
    }

    /**
     * Get the customer for this transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the salesperson who served this transaction.
     */
    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    /**
     * Get the voucher if used.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get all transaction items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    /**
     * Get all return records for this transaction.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(PosReturn::class);
    }

    /**
     * Get all payment allocation items for this transaction.
     */
    public function customerPaymentItems(): HasMany
    {
        return $this->hasMany(CustomerPaymentItem::class, 'pos_transaction_id');
    }
}
