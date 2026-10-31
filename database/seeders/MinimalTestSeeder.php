<?php

namespace Database\Seeders;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MinimalTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $company = Company::factory()->create([
                'code' => 'SIGMA-HO',
                'name' => 'PT Sigma Retail Indonesia',
            ]);

            $store = Store::factory()->create([
                'company_id' => $company->id,
                'code' => 'STR-001',
                'name' => 'Toko Utama Sigma',
            ]);

            $tier = CustomerTier::factory()->create([
                'company_id' => $company->id,
                'store_id' => null,
                'code' => 'RETAIL',
                'name' => 'Retail Umum',
                'is_default' => true,
            ]);

            $product = Product::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'sku' => 'PRD-001',
                'name' => 'Produk Uji Standar',
                'cost_price' => 10000,
                'last_purchase_price' => 10000,
                'total_quantity' => 100,
                'total_stock_value' => 1000000,
            ]);

            $unit = ProductUnit::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'unit_code' => 'PCS',
                'unit_name' => 'Pieces',
                'conversion_factor' => 1,
                'is_base_unit' => true,
            ]);

            ProductPrice::factory()->create([
                'company_id' => $company->id,
                'store_id' => null,
                'product_id' => $product->id,
                'product_unit_id' => $unit->id,
                'customer_tier_id' => $tier->id,
                'min_quantity' => 1,
                'price' => 15000,
            ]);

            ProductStock::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'product_id' => $product->id,
                'physical_quantity' => 100,
                'reserved_quantity' => 0,
                'quarantined_quantity' => 0,
            ]);

            User::factory()->owner()->create([
                'name' => 'Owner Sigma',
                'email' => 'owner@sigma.test',
                'store_id' => $store->id,
            ]);

            User::factory()->manager()->create([
                'name' => 'Manager Sigma',
                'email' => 'manager@sigma.test',
                'store_id' => $store->id,
            ]);

            $cashier = User::factory()->cashier()->create([
                'name' => 'Kasir Sigma',
                'email' => 'cashier@sigma.test',
                'store_id' => $store->id,
            ]);

            CashierShift::factory()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'cashier_id' => $cashier->id,
                'shift_number' => 'SHF-0001',
                'starting_cash' => 100000,
                'status' => 'OPEN',
            ]);
        });
    }
}
