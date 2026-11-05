<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\CompanyService;
use App\Services\PosService;
use Database\Seeders\MinimalTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiCompanyAuthorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MinimalTestSeeder::class);
    }

    public function test_non_owner_is_forbidden_from_switching_company(): void
    {
        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Perusahaan B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $manager = User::where('role', 'manager')->firstOrFail();
        $this->actingAs($manager);

        $response = $this->post(route('company.switch'), [
            'company_id' => $companyB->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_switch_company(): void
    {
        $companyB = Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Perusahaan B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $owner = User::where('role', 'owner')->firstOrFail();
        $this->actingAs($owner);

        $response = $this->post(route('company.switch'), [
            'company_id' => $companyB->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($companyB->id, session('active_company_id'));
    }

    public function test_available_companies_is_scoped_for_manager(): void
    {
        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Perusahaan B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $manager = User::where('role', 'manager')->firstOrFail();
        $this->actingAs($manager);

        $service = app(CompanyService::class);
        $available = $service->getAvailableCompanies();

        // Manager hanya boleh melihat 1 perusahaan tempat tokonya bernaung
        $this->assertCount(1, $available);
        $this->assertEquals($manager->store->company_id, $available->first()->id);
    }

    public function test_available_companies_shows_all_for_owner(): void
    {
        Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Perusahaan B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $owner = User::where('role', 'owner')->firstOrFail();
        $this->actingAs($owner);

        $service = app(CompanyService::class);
        $available = $service->getAvailableCompanies();

        $this->assertGreaterThanOrEqual(2, $available->count());
    }

    public function test_manager_cannot_see_owner_in_user_management(): void
    {
        $company = Company::firstOrFail();
        $manager = User::where('role', 'manager')->firstOrFail();
        $this->actingAs($manager);

        $response = $this->get(route('settings.users.index'));
        $response->assertOk();

        $usersInView = $response->viewData('users');
        $this->assertFalse($usersInView->contains('role', 'owner'));

        $rolesInView = $response->viewData('roles');
        $this->assertArrayNotHasKey('owner', $rolesInView);
    }

    public function test_manager_cannot_create_owner_user(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $this->actingAs($manager);

        $response = $this->post(route('settings.users.store'), [
            'name' => 'Hacker Owner',
            'email' => 'hacker@sigma.local',
            'password' => 'password123',
            'role' => 'owner',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@sigma.local']);
    }

    public function test_pos_discount_pin_verification_rejects_pin_from_other_company_manager(): void
    {
        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-OTHER',
            'name' => 'PT Perusahaan Lain',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $storeB = Store::create([
            'company_id' => $companyB->id,
            'code' => 'STR-B01',
            'name' => 'Cabang Perusahaan B',
            'is_active' => true,
        ]);

        // Manajer milik Perusahaan B dengan PIN unik 999999
        $foreignManager = User::create([
            'name' => 'Manager B',
            'email' => 'mgr.b@sigma.local',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'store_id' => $storeB->id,
            'is_active' => true,
            'manager_pin' => Hash::make('999999'),
        ]);

        $cashier = User::where('role', 'cashier')->firstOrFail();
        $shift = CashierShift::where('cashier_id', $cashier->id)->where('status', 'OPEN')->first();
        if (! $shift) {
            $storeA = Store::where('company_id', $companyA->id)->firstOrFail();
            $shift = CashierShift::create([
                'company_id' => $companyA->id,
                'store_id' => $storeA->id,
                'cashier_id' => $cashier->id,
                'shift_number' => 'S-TEST-001',
                'starting_cash' => 100000,
                'opened_at' => now(),
                'status' => 'OPEN',
            ]);
        }

        $product = Product::where('company_id', $companyA->id)->firstOrFail();
        $tier = \App\Models\CustomerTier::where('company_id', $companyA->id)->firstOrFail();

        $posService = app(PosService::class);

        $cart = [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 50000,
                'cost_price' => 30000,
                'discount_amount' => 5000, // Diskon manual butuh PIN
            ],
        ];

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        // Eksekusi checkout dengan PIN manajer dari Perusahaan B -> harus ditolak
        $posService->checkout(
            shift: $shift,
            tier: $tier,
            cartItems: $cart,
            paymentMethod: 'CASH',
            paidAmount: 50000,
            managerPin: '999999'
        );
    }

    public function test_autonumber_and_reports_use_active_company(): void
    {
        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-B',
            'name' => 'PT Perusahaan B',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $owner = User::where('role', 'owner')->firstOrFail();
        $this->actingAs($owner);

        // Switch active company to Company B
        session(['active_company_id' => $companyB->id]);

        // AutoNumberSetting index
        $resNumbering = $this->get(route('settings.numbering.index'));
        $resNumbering->assertOk();
        $this->assertEquals($companyB->id, $resNumbering->viewData('company')->id);

        // CustomerPoint index
        $resPoints = $this->get(route('sales.points.index'));
        $resPoints->assertOk();

        // PromotionReport index
        $resPromo = $this->get(route('reports.promotions'));
        $resPromo->assertOk();
    }
}
