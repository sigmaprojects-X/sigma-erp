<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CustomerTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerTier>
 */
class CustomerTierFactory extends Factory
{
    protected $model = CustomerTier::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => null,
            'code' => 'RETAIL',
            'name' => 'Retail Umum',
            'default_margin_percentage' => 20.00,
            'is_default' => true,
            'is_active' => true,
        ];
    }
}
