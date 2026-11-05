<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'tax_id',
        'email',
        'phone',
        'whatsapp',
        'website',
        'address',
        'wallpaper_path',
        'logo_path',
        'tax_name',
        'tax_address',
        'default_tax_rate',
        'tax_mode',
        'receipt_footer',
        'currency_code',
        'is_tax_registered',
        'is_active',
        'min_profit_percentage',
        'rounding_enabled',
        'rounding_step',
        'rounding_mode',
        'rounding_applies_to',
        'receipt_paper_size',
        'receipt_font_family',
        'receipt_font_size',
        'receipt_item_layout',
        'receipt_show_logo',
        'receipt_header_line1',
        'receipt_header_line2',
        'receipt_footer_line1',
        'receipt_footer_line2',
        'receipt_show_customer',
        'receipt_show_cashier',
        'receipt_show_barcode',
        'receipt_auto_print',
        'receipt_auto_cut',
        'receipt_open_drawer',
        'receipt_print_copies',
        'cashier_default_starting_cash',
        'cashier_max_starting_cash',
        'cashier_require_starting_cash',
        'cashier_max_change_limit',
        'cashier_cash_drop_threshold',
        'cashier_cash_drop_warning_enabled',
        'cashier_max_discrepancy_tolerance',
        'cashier_allow_pending_transaction',
        'cashier_max_pending_transactions',
        'cashier_quick_cash_nominal',
    ];

    /**
     * Get the desktop wallpaper URL.
     * Fallbacks to default Endogan banner if not customized.
     */
    public function getWallpaperUrlAttribute(): string
    {
        if ($this->wallpaper_path && file_exists(public_path($this->wallpaper_path))) {
            return asset($this->wallpaper_path);
        }

        return asset('images/banner.jpg');
    }

    /**
     * Get the company logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path && file_exists(public_path($this->logo_path))) {
            return asset($this->logo_path);
        }

        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_tax_registered' => 'boolean',
            'default_tax_rate' => 'decimal:2',
            'min_profit_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'rounding_enabled' => 'boolean',
            'rounding_step' => 'integer',
            'receipt_show_logo' => 'boolean',
            'receipt_show_customer' => 'boolean',
            'receipt_show_cashier' => 'boolean',
            'receipt_show_barcode' => 'boolean',
            'receipt_auto_print' => 'boolean',
            'receipt_auto_cut' => 'boolean',
            'receipt_open_drawer' => 'boolean',
            'receipt_print_copies' => 'integer',
            'cashier_default_starting_cash' => 'decimal:2',
            'cashier_max_starting_cash' => 'decimal:2',
            'cashier_require_starting_cash' => 'boolean',
            'cashier_max_change_limit' => 'decimal:2',
            'cashier_cash_drop_threshold' => 'decimal:2',
            'cashier_cash_drop_warning_enabled' => 'boolean',
            'cashier_max_discrepancy_tolerance' => 'decimal:2',
            'cashier_allow_pending_transaction' => 'boolean',
            'cashier_max_pending_transactions' => 'integer',
        ];
    }

    /**
     * Get the stores belonging to the company.
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Get the customer tiers belonging to the company.
     */
    public function customerTiers(): HasMany
    {
        return $this->hasMany(CustomerTier::class);
    }

    /**
     * Get the products belonging to the company.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the product units belonging to the company.
     */
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get the product barcodes belonging to the company.
     */
    public function productBarcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class);
    }

    /**
     * Get the product prices belonging to the company.
     */
    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
