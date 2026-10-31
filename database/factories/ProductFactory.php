<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => null,
            'sku' => 'PRD-001',
            'name' => 'Produk Uji Standar',
            'cost_price' => 10000,
            'last_purchase_price' => 10000,
            'total_quantity' => 100,
            'total_stock_value' => 1000000,
            'min_stock_alert' => 5,
            'is_point_of_sale' => true,
            'is_active' => true,
        ];
    }
}
