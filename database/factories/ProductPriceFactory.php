<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => null,
            'product_id' => Product::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'customer_tier_id' => CustomerTier::factory(),
            'min_quantity' => 1,
            'price' => 15000,
            'margin_percentage' => 50,
            'is_active' => true,
        ];
    }
}
