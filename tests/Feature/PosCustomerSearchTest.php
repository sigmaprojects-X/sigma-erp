<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCustomerSearchTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected CustomerTier $defaultTier;

    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->seed(AccountingSeeder::class);

        $this->company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $this->store = Store::where('code', 'STR-001')->firstOrFail();
        $this->defaultTier = CustomerTier::where('code', 'RETAIL')->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();

        // Create test customers
        Customer::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_tier_id' => $this->defaultTier->id,
            'code' => 'CUST-WA01',
            'name' => 'Haji Mansyur Sembako',
            'phone' => '081234567890',
            'address' => 'Jl. Pasar Baru No. 10',
            'is_active' => true,
        ]);

        Customer::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_tier_id' => $this->defaultTier->id,
            'code' => 'CUST-WA02',
            'name' => 'Ibu Siti Khadijah',
            'phone' => '085799988877',
            'address' => 'Komplek Melati Indah B-12',
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_search_customer_by_phone_number(): void
    {
        $response = $this->actingAs($this->cashier)->getJson('/pos/customers/search?q=081234567890');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Haji Mansyur Sembako'])
            ->assertJsonMissing(['name' => 'Ibu Siti Khadijah']);
    }

    public function test_cashier_can_search_customer_by_partial_name(): void
    {
        $response = $this->actingAs($this->cashier)->getJson('/pos/customers/search?q=Khadijah');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => 'Ibu Siti Khadijah'])
            ->assertJsonMissing(['name' => 'Haji Mansyur Sembako']);
    }

    public function test_cashier_can_quick_store_new_customer(): void
    {
        $payload = [
            'name' => 'Pak Joko Widodo Baru',
            'phone' => '082111223344',
            'address' => 'Jl. Merdeka No. 45',
        ];

        $response = $this->actingAs($this->cashier)->postJson('/pos/customers/quick-store', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('customer.name', 'Pak Joko Widodo Baru')
            ->assertJsonPath('customer.phone', '082111223344');

        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'name' => 'Pak Joko Widodo Baru',
            'phone' => '082111223344',
        ]);
    }
}
