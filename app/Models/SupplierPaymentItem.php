<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'supplier_payment_items';

    protected $fillable = [
        'supplier_payment_id',
        'purchase_id',
        'amount_paid',
    ];

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
