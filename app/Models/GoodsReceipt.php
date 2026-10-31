<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceipt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'goods_receipts';

    protected $fillable = [
        'company_id',
        'store_id',
        'purchase_order_id',
        'supplier_id',
        'receipt_number',
        'delivery_order_number',
        'receipt_date',
        'status',
        'total_items_received',
        'estimated_total_value',
        'shipping_cost',
        'handling_cost',
        'total_landed_cost',
        'driver_name',
        'vehicle_number',
        'received_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'total_items_received' => 'integer',
            'estimated_total_value' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'handling_cost' => 'decimal:2',
            'total_landed_cost' => 'decimal:2',
        ];
    }

    public function getReceivedDateAttribute()
    {
        return $this->receipt_date;
    }

    public function getTotalEstimatedAmountAttribute()
    {
        return $this->estimated_total_value;
    }

    public function getShippingFeeAttribute()
    {
        return $this->shipping_cost;
    }

    public function getHandlingFeeAttribute()
    {
        return $this->handling_cost;
    }

    public function getPurchaseIdAttribute(): ?string
    {
        return $this->purchase?->id;
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'goods_receipt_id');
    }

    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class, 'goods_receipt_id');
    }
}
