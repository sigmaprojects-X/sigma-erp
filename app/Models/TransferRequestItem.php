<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferRequestItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transfer_request_items';

    protected $fillable = [
        'transfer_request_id',
        'product_id',
        'product_unit_id',
        'requested_quantity',
        'approved_quantity',
        'fulfilled_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:4',
            'approved_quantity' => 'decimal:4',
            'fulfilled_quantity' => 'decimal:4',
        ];
    }

    public function transferRequest(): BelongsTo
    {
        return $this->belongsTo(TransferRequest::class, 'transfer_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
