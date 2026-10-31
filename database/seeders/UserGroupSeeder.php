<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();
        if (! $company) {
            return;
        }

        DB::transaction(function () use ($company) {
            $presets = UserGroup::getPresets();
            $createdGroups = [];

            foreach ($presets as $code => $data) {
                $group = UserGroup::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $data['name'],
                        'description' => $data['description'],
                        'permissions' => $data['permissions'],
                        'is_system' => true,
                    ]
                );
                $createdGroups[$code] = $group;
            }

            // Map existing users to user groups
            $users = User::all();
            foreach ($users as $user) {
                $targetCode = match ($user->role) {
                    'owner' => 'ADMINISTRATOR',
                    'manager' => 'MANAGER',
                    'cashier' => 'KASIR',
                    'warehouse' => 'GUDANG',
                    'purchasing' => 'PURCHASING',
                    'finance' => 'KEUANGAN',
                    default => 'KASIR',
                };

                if (isset($createdGroups[$targetCode]) && ! $user->user_group_id) {
                    $user->user_group_id = $createdGroups[$targetCode]->id;
                    $user->save();
                }
            }
        });
    }
}
