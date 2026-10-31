<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'code' => 'SIGMA-HO',
            'name' => 'PT Sigma Retail Indonesia',
            'currency_code' => 'IDR',
            'is_tax_registered' => false,
            'is_active' => true,
        ];
    }
}
