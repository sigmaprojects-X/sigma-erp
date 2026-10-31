<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    protected $model = ProductUnit::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => null,
            'product_id' => Product::factory(),
            'unit_code' => 'PCS',
            'unit_name' => 'Pieces',
            'conversion_factor' => 1,
            'is_base_unit' => true,
            'is_active' => true,
        ];
    }
}
