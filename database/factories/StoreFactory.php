<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => 'STR-001',
            'name' => 'Toko Utama Sigma',
            'type' => 'STORE',
            'is_active' => true,
            'is_main_store' => true,
        ];
    }
}
