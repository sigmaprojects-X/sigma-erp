<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, HasUuids;

    public const TYPE_BARANG = 'BARANG';

    public const TYPE_JASA = 'JASA';

    public const TYPE_RAKITAN_PROSES = 'RAKITAN_PROSES';

    public const TYPE_RAKITAN_NON_PROSES = 'RAKITAN_NON_PROSES';

    public const TYPE_NON_INVENTORY = 'NON_INVENTORY';

    public const TYPE_BIAYA = 'BIAYA';

    public const SUPPORTED_TYPES = [
        self::TYPE_BARANG,
        self::TYPE_JASA,
        self::TYPE_RAKITAN_PROSES,
        self::TYPE_RAKITAN_NON_PROSES,
        self::TYPE_NON_INVENTORY,
        self::TYPE_BIAYA,
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'store_id',
        'category_id',
        'supplier_id',
        'sku',
        'type',
        'name',
        'slug',
        'description',
        'notes',
        'category',
        'sub_category',
        'brand',
        'rack_location',
        'sale_status',
        'cost_price',
        'last_purchase_price',
        'total_stock_value',
        'total_quantity',
        'min_stock_alert',
        'max_stock',
        'has_serial_number',
        'has_batch_expiry',
        'is_consignment',
        'consignment_supplier_id',
        'consignment_cost',
        'is_point_of_sale',
        'is_active',
        'tax_type',
        'tax_reference_code',
        'image_path',
        'additional_images',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'last_purchase_price' => 'decimal:4',
            'total_stock_value' => 'decimal:4',
            'total_quantity' => 'decimal:4',
            'min_stock_alert' => 'decimal:2',
            'max_stock' => 'decimal:2',
            'has_serial_number' => 'boolean',
            'has_batch_expiry' => 'boolean',
            'is_consignment' => 'boolean',
            'consignment_cost' => 'decimal:4',
            'is_point_of_sale' => 'boolean',
            'is_active' => 'boolean',
            'additional_images' => 'array',
        ];
    }

    /**
     * Get the parent company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the category of this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the preferred supplier for this product.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the store if product is store-scoped.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get all packaging units for this product.
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get the base unit for this product.
     */
    public function baseUnit(): HasOne
    {
        return $this->hasOne(ProductUnit::class)->where('is_base_unit', true);
    }

    /**
     * Get the base unit name or code attribute.
     */
    public function getBaseUnitAttribute(): string
    {
        $unit = $this->relationLoaded('baseUnit')
            ? $this->getRelation('baseUnit')
            : $this->baseUnit()->first();

        return $unit?->unit_name ?: ($unit?->unit_code ?: 'PCS');
    }

    /**
     * Get all barcodes for this product.
     */
    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /**
     * Get all pricing configurations for this product.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get the stock records for this product across stores.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    /**
     * Get the stock ledger / mutations for this product.
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    /**
     * Get the BOM (Bill of Materials) recipe components if this product is an assembled item.
     */
    public function boms(): HasMany
    {
        return $this->hasMany(ProductBom::class, 'parent_product_id');
    }

    /**
     * Get the assembly records for this product.
     */
    public function assemblies(): HasMany
    {
        return $this->hasMany(ProductAssembly::class, 'parent_product_id');
    }

    /**
     * Get the batches / lots for this product.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Supplier pemilik barang titipan jika produk bertipe konsinyasi.
     */
    public function consignmentSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'consignment_supplier_id');
    }

    /**
     * Apakah tipe produk ini melacak stok fisik gudang/toko secara langsung.
     */
    public function tracksInventory(): bool
    {
        return in_array($this->type ?: self::TYPE_BARANG, [
            self::TYPE_BARANG,
            self::TYPE_RAKITAN_PROSES,
        ], true);
    }

    /**
     * Label tipe produk yang mudah dibaca.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type ?: self::TYPE_BARANG) {
            self::TYPE_BARANG => 'Barang Fisik',
            self::TYPE_JASA => 'Jasa / Service',
            self::TYPE_RAKITAN_PROSES => 'Rakitan Produksi',
            self::TYPE_RAKITAN_NON_PROSES => 'Paket Bundling',
            self::TYPE_NON_INVENTORY => 'Non Inventory',
            self::TYPE_BIAYA => 'Biaya / Beban',
            default => $this->type ?: 'Barang Fisik',
        };
    }
}
