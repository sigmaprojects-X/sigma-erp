<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductStock>
 */
class ProductStockFactory extends Factory
{
    protected $model = ProductStock::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'product_id' => Product::factory(),
            'physical_quantity' => 100,
            'reserved_quantity' => 0,
            'quarantined_quantity' => 0,
        ];
    }
}
