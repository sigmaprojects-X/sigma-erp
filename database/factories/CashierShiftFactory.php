<?php

namespace Database\Factories;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierShift>
 */
class CashierShiftFactory extends Factory
{
    protected $model = CashierShift::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'store_id' => Store::factory(),
            'cashier_id' => User::factory(),
            'shift_number' => 'SHF-0001',
            'starting_cash' => 100000,
            'status' => 'OPEN',
            'opened_at' => now(),
        ];
    }
}
