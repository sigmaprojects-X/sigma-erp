<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'company_id',
        'source_store_id',
        'destination_store_id',
        'transfer_number',
        'transfer_date',
        'status',
        'driver_name',
        'vehicle_number',
        'dispatched_at',
        'dispatched_by',
        'received_at',
        'received_by',
        'total_items',
        'total_sent_quantity',
        'total_received_quantity',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'date',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'total_items' => 'integer',
            'total_sent_quantity' => 'decimal:4',
            'total_received_quantity' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sourceStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'source_store_id');
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function dispatcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispatched_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
