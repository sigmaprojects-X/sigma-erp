<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\CustomerTier;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\MinimalTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_login_and_get_sales_returns_200(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame('sigma_erp_testing', DB::connection()->getDatabaseName());

        $this->seed(MinimalTestSeeder::class);

        $this->assertSame(1, Company::count());
        $this->assertSame(1, Store::count());
        $this->assertSame(1, CustomerTier::where('is_default', true)->count());
        $this->assertSame(1, Product::count());
        $this->assertSame(1, ProductUnit::count());
        $this->assertSame(1, ProductPrice::count());

        $stock = ProductStock::firstOrFail();
        $this->assertEquals(100.0, (float) $stock->physical_quantity);

        $owner = User::where('role', 'owner')->firstOrFail();
        $this->assertNotNull(User::where('role', 'manager')->first());
        $cashier = User::where('role', 'cashier')->firstOrFail();

        $shift = CashierShift::where('cashier_id', $cashier->id)->firstOrFail();
        $this->assertSame('OPEN', $shift->status);

        $loginResponse = $this->post('/login', [
            'email' => $owner->email,
            'password' => 'password',
        ]);

        $loginResponse->assertRedirect(route('sales.index'));
        $this->assertAuthenticatedAs($owner);

        $salesResponse = $this->get('/sales');
        $salesResponse->assertStatus(200);
    }
}
