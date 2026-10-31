<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsignmentReceipt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'consignment_receipts';

    protected $fillable = [
        'company_id',
        'store_id',
        'supplier_id',
        'receipt_number',
        'receipt_date',
        'total_items',
        'total_estimated_amount',
        'status',
        'notes',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'total_items' => 'integer',
            'total_estimated_amount' => 'decimal:2',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsignmentReceiptItem::class, 'consignment_receipt_id');
    }
}
