<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferRequest extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transfer_requests';

    protected $fillable = [
        'company_id',
        'request_number',
        'requesting_store_id',
        'destination_store_id',
        'request_date',
        'status',
        'total_items',
        'total_requested_quantity',
        'total_approved_quantity',
        'total_fulfilled_quantity',
        'notes',
        'rejection_reason',
        'created_by',
        'approved_by',
        'approved_at',
        'stock_transfer_id',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'approved_at' => 'datetime',
            'total_items' => 'integer',
            'total_requested_quantity' => 'decimal:4',
            'total_approved_quantity' => 'decimal:4',
            'total_fulfilled_quantity' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requestingStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'requesting_store_id');
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferRequestItem::class, 'transfer_request_id');
    }
}
