<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PricingEngineService
{
    /**
     * 3-Dimensional Automated Pricing Engine (AGENTS.md Section 3).
     *
     * Menghitung harga jual final secara otomatis di backend berdasarkan:
     * 1. Dimensi 1: Satuan / Kemasan (unit_code / product_unit_id)
     * 2. Dimensi 2: Customer Tier (customer_tier_id)
     * 3. Dimensi 3: Tiered Quantity / min_qty (min_quantity)
     *
     * ZERO MANUAL PRICE OVERRIDE: Kasir dilarang keras mengubah harga secara manual di POS screen.
     */
    public function calculatePrice(
        Product $product,
        ProductUnit $unit,
        CustomerTier $tier,
        float $quantity,
        ?Store $store = null
    ): float {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Kuantitas untuk kalkulasi harga harus lebih besar dari 0.');
        }

        // 1. Cek Prioritas 1: Harga Khusus Cabang / Store-specific Price Override
        if ($store) {
            $storePrice = ProductPrice::where('company_id', $product->company_id)
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->where('product_unit_id', $unit->id)
                ->where('customer_tier_id', $tier->id)
                ->where('min_quantity', '<=', $quantity)
                ->where('is_active', true)
                ->orderByDesc('min_quantity')
                ->first();

            if ($storePrice) {
                return (float) $storePrice->price;
            }
        }

        // 2. Cek Prioritas 2: Harga Global Perusahaan (store_id IS NULL)
        $globalPrice = ProductPrice::where('company_id', $product->company_id)
            ->whereNull('store_id')
            ->where('product_id', $product->id)
            ->where('product_unit_id', $unit->id)
            ->where('customer_tier_id', $tier->id)
            ->where('min_quantity', '<=', $quantity)
            ->where('is_active', true)
            ->orderByDesc('min_quantity')
            ->first();

        if ($globalPrice) {
            return (float) $globalPrice->price;
        }

        // 3. Fallback: Jika tier non-retail belum diset, gunakan tier default (RETAIL)
        if (! $tier->is_default) {
            $defaultTier = CustomerTier::where('company_id', $product->company_id)
                ->where('is_default', true)
                ->first();

            if ($defaultTier && $defaultTier->id !== $tier->id) {
                return $this->calculatePrice($product, $unit, $defaultTier, $quantity, $store);
            }
        }

        throw new \RuntimeException(
            "Harga jual untuk produk [{$product->name}], satuan [{$unit->unit_code}], tier [{$tier->name}], kuantitas [{$quantity}] belum ditentukan di sistem."
        );
    }

    /**
     * Pencarian Cepat Kasir (Lightning-Fast POS Search - AGENTS.md Section 3).
     *
     * 1. Deteksi Barcode Exact Match (pencarian instan saat scanner kasir berbunyi).
     * 2. Pencarian Parsial Kata Kunci (Nama / SKU / Barcode) menggunakan PostgreSQL Trigram (pg_trgm).
     */
    public function search(Company $company, string $query, ?Store $store = null, int $limit = 20): Collection
    {
        $cleanQuery = trim($query);

        if ($cleanQuery === '') {
            return collect();
        }

        // 1. Cek Exact Match Barcode
        $barcodeMatch = ProductBarcode::where('company_id', $company->id)
            ->where('barcode', $cleanQuery)
            ->with(['product.units', 'unit'])
            ->first();

        if ($barcodeMatch) {
            if ($barcodeMatch->product->is_active && $barcodeMatch->product->is_point_of_sale && $barcodeMatch->product->sale_status !== 'DISCONTINUED') {
                // Jika satuan kemasan khusus barcode ini dinonaktifkan, jangan match
                if ($barcodeMatch->unit && ! $barcodeMatch->unit->is_active) {
                    return collect();
                }

                return collect([[
                    'product' => $barcodeMatch->product,
                    'matched_unit' => $barcodeMatch->unit,
                    'barcode' => $barcodeMatch->barcode,
                    'match_type' => 'EXACT_BARCODE',
                ]]);
            }

            return collect();
        }

        // 1b. Deteksi Barcode Timbangan Digital Elektronik (In-store EAN-13 Scale Barcode: Prefix 20, 21, 22)
        // Format: PP CCCCC WWWWW K (PP: prefix 20/21/22, CCCCC: 5 digit item code, WWWWW: 5 digit weight gram, K: checksum)
        if (preg_match('/^(20|21|22)(\d{5})(\d{5})\d$/', $cleanQuery, $scaleMatches)) {
            $prefix = $scaleMatches[1];
            $itemCode = $scaleMatches[2];
            $rawMeasure = (int) $scaleMatches[3];
            $weightKg = round($rawMeasure / 1000, 3); // Konversi gram ke kilogram
            $cleanCode = ltrim($itemCode, '0') ?: $itemCode;

            $scaleBarcode = ProductBarcode::where('company_id', $company->id)
                ->where(function ($q) use ($itemCode, $cleanCode, $prefix) {
                    $q->where('barcode', $itemCode)
                        ->orWhere('barcode', $cleanCode)
                        ->orWhere('barcode', $prefix . $itemCode);
                })
                ->with(['product.units', 'unit'])
                ->first();

            $matchedProduct = null;
            $matchedUnit = null;

            if ($scaleBarcode && $scaleBarcode->product->is_active && $scaleBarcode->product->is_point_of_sale && $scaleBarcode->product->sale_status !== 'DISCONTINUED') {
                if (! $scaleBarcode->unit || $scaleBarcode->unit->is_active) {
                    $matchedProduct = $scaleBarcode->product;
                    $matchedUnit = $scaleBarcode->unit;
                }
            }

            if (! $matchedProduct) {
                $productMatch = Product::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->where('is_point_of_sale', true)
                    ->where('sale_status', '!=', 'DISCONTINUED')
                    ->where(function ($q) use ($itemCode, $cleanCode) {
                        $q->where('sku', $itemCode)
                            ->orWhere('sku', $cleanCode)
                            ->orWhere('sku', 'like', "%{$cleanCode}%");
                    })
                    ->with('units')
                    ->first();

                if ($productMatch) {
                    $matchedProduct = $productMatch;
                    $matchedUnit = $productMatch->units->firstWhere('is_base_unit', true) ?? $productMatch->units->first();
                }
            }

            if ($matchedProduct) {
                return collect([[
                    'product' => $matchedProduct,
                    'matched_unit' => $matchedUnit,
                    'barcode' => $cleanQuery,
                    'match_type' => 'SCALE_BARCODE',
                    'scale_weight' => $weightKg,
                ]]);
            }
        }

        // 2. Pencarian Cepat Nama / SKU Produk (Didukung PostgreSQL GIN pg_trgm Index)
        $term = '%'.$cleanQuery.'%';

        $products = Product::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('is_point_of_sale', true)
            ->where('sale_status', '!=', 'DISCONTINUED')
            ->where(function ($q) use ($term) {
                if (DB::getDriverName() === 'pgsql') {
                    $q->where('name', 'ILIKE', $term)
                        ->orWhere('sku', 'ILIKE', $term)
                        ->orWhereHas('barcodes', function ($bq) use ($term) {
                            $bq->where('barcode', 'ILIKE', $term)
                               ->where(function ($sub) {
                                   $sub->whereNull('product_unit_id')
                                       ->orWhereHas('unit', fn ($uq) => $uq->where('is_active', true));
                               });
                        });
                } else {
                    $q->where('name', 'LIKE', $term)
                        ->orWhere('sku', 'LIKE', $term)
                        ->orWhereHas('barcodes', function ($bq) use ($term) {
                            $bq->where('barcode', 'LIKE', $term)
                               ->where(function ($sub) {
                                   $sub->whereNull('product_unit_id')
                                       ->orWhereHas('unit', fn ($uq) => $uq->where('is_active', true));
                               });
                        });
                }
            })
            ->with(['units', 'barcodes.unit'])
            ->limit($limit)
            ->get();

        return $products->map(function ($product) {
            $baseUnit = $product->units->firstWhere('is_base_unit', true) ?? $product->units->first();

            return [
                'product' => $product,
                'matched_unit' => $baseUnit,
                'barcode' => $product->barcodes->first()?->barcode ?? '',
                'match_type' => 'SEARCH_MATCH',
            ];
        });
    }
}
