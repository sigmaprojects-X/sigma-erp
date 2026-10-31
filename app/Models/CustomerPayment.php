<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'customer_payments';

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'payment_number',
        'payment_date',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'reference_number',
        'total_amount',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'total_amount' => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerPaymentItem::class, 'customer_payment_id');
    }
}
