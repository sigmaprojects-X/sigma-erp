<?php

namespace App\Models;

use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sales_orders';

    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'order_number',
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'completed_at',
        'stock_deducted_at',
        'pos_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'stock_deducted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Otomatis kurangi stok barang ketika status pesanan berubah menjadi 'Selesai' / 'COMPLETED' / 'SELESAI'
        static::updating(function (SalesOrder $order) {
            if ($order->isDirty('status')) {
                $newStatus = strtoupper((string) $order->status);
                $oldStatus = strtoupper((string) $order->getOriginal('status'));

                $completedStatuses = ['COMPLETED', 'SELESAI', 'PROCESSED'];

                if (in_array($newStatus, $completedStatuses, true) && ! in_array($oldStatus, $completedStatuses, true)) {
                    if ($order->stock_deducted_at === null && empty($order->pos_transaction_id)) {
                        $order->completed_at = $order->completed_at ?? now();
                        $order->deductInventoryStock(Auth::id() ?? $order->created_by);
                    }
                }
            }
        });
    }

    /**
     * Otomasi Pengurangan Stok Inventaris saat Pesanan Selesai (AGENTS.md Section 2 & 4D).
     * Mendukung realisasi reservasi stok (PWA) maupun pengurangan langsung stok fisik (Backoffice).
     */
    public function deductInventoryStock(?string $userId = null): void
    {
        if ($this->stock_deducted_at !== null) {
            return;
        }

        $inventoryService = app(InventoryService::class);

        DB::transaction(function () use ($inventoryService, $userId) {
            $this->loadMissing(['items.product', 'items.productUnit', 'store']);

            foreach ($this->items as $item) {
                $product = $item->product;
                $store = $this->store;
                $baseQty = (float) $item->quantity * (float) ($item->conversion_factor ?? 1.0);

                if ($baseQty <= 0) {
                    continue;
                }

                $stock = ProductStock::where('store_id', $store->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                $currentReserved = $stock ? (float) $stock->reserved_quantity : 0.0;

                if ($currentReserved > 0) {
                    $fulfillReservedQty = min($currentReserved, $baseQty);
                    $inventoryService->fulfillReservedStock(
                        product: $product,
                        store: $store,
                        quantity: $fulfillReservedQty,
                        referenceNumber: $this->order_number,
                        notes: "Penyelesaian pesanan [{$this->order_number}] (Pemenuhan Reservasi)",
                        userId: $userId
                    );

                    $remainingToDeduct = $baseQty - $fulfillReservedQty;
                    if ($remainingToDeduct > 0) {
                        $inventoryService->deductStock(
                            product: $product,
                            store: $store,
                            quantity: $remainingToDeduct,
                            mutationType: 'SALE',
                            referenceNumber: $this->order_number,
                            notes: "Penyelesaian pesanan [{$this->order_number}] (Sisa non-reservasi)",
                            userId: $userId
                        );
                    }
                } else {
                    $inventoryService->deductStock(
                        product: $product,
                        store: $store,
                        quantity: $baseQty,
                        mutationType: 'SALE',
                        referenceNumber: $this->order_number,
                        notes: "Penyelesaian pesanan penjualan [{$this->order_number}]",
                        userId: $userId
                    );
                }
            }

            $this->stock_deducted_at = now();
            if (! $this->completed_at) {
                $this->completed_at = now();
            }

            if (! $this->isDirty()) {
                $this->saveQuietly();
            }
        });
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
