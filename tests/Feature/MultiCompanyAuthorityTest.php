<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\UserGroup;
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
        $this->withoutVite();
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

    public function test_non_owner_without_store_is_denied_instead_of_falling_back_to_session_company(): void
    {
        $companyB = Company::create([
            'code' => 'COMP-ORPHAN',
            'name' => 'PT Tenant Sesi',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);
        $orphanManager = User::factory()->create([
            'role' => 'manager',
            'store_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($orphanManager)
            ->withSession(['active_company_id' => $companyB->id])
            ->get(route('sales.index'));

        $response->assertForbidden();
    }

    public function test_manager_cannot_create_staff_without_a_store_assignment(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();

        $response = $this->actingAs($manager)->from(route('settings.users.index'))
            ->post(route('settings.users.store'), [
                'name' => 'Staff Tanpa Cabang',
                'email' => 'orphan.staff@sigma.local',
                'password' => 'password123',
                'role' => 'cashier',
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('store_id');
        $this->assertDatabaseMissing('users', ['email' => 'orphan.staff@sigma.local']);
    }

    public function test_manager_cannot_assign_staff_to_another_company_store_or_permission_group(): void
    {
        $companyA = Company::firstOrFail();
        $companyB = Company::create([
            'code' => 'COMP-ASSIGN',
            'name' => 'PT Tenant Lain',
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);
        $storeB = Store::create([
            'company_id' => $companyB->id,
            'code' => 'STR-ASSIGN-B',
            'name' => 'Cabang Tenant Lain',
            'is_active' => true,
        ]);
        $foreignGroup = UserGroup::create([
            'company_id' => $companyB->id,
            'code' => 'FOREIGN-CASHIER',
            'name' => 'Kelompok Kasir Tenant Lain',
            'permissions' => [],
            'is_system' => false,
        ]);
        $manager = User::where('role', 'manager')->firstOrFail();
        $storeA = Store::where('company_id', $companyA->id)->firstOrFail();

        $storeResponse = $this->actingAs($manager)->from(route('settings.users.index'))
            ->post(route('settings.users.store'), [
                'name' => 'Kasir Cabang Asing',
                'email' => 'foreign.store@sigma.local',
                'password' => 'password123',
                'role' => 'cashier',
                'store_id' => $storeB->id,
            ]);
        $storeResponse->assertSessionHasErrors('store_id');

        $groupResponse = $this->actingAs($manager)->from(route('settings.users.index'))
            ->post(route('settings.users.store'), [
                'name' => 'Kasir Grup Asing',
                'email' => 'foreign.group@sigma.local',
                'password' => 'password123',
                'role' => 'cashier',
                'store_id' => $storeA->id,
                'user_group_id' => $foreignGroup->id,
            ]);
        $groupResponse->assertSessionHasErrors('user_group_id');

        $this->assertDatabaseMissing('users', ['email' => 'foreign.store@sigma.local']);
        $this->assertDatabaseMissing('users', ['email' => 'foreign.group@sigma.local']);
    }

    public function test_new_company_receives_only_its_own_default_records(): void
    {
        $companyA = Company::firstOrFail();
        $companyB = app(CompanyService::class)->createCompany([
            'code' => 'COMP-ISOLATED',
            'name' => 'PT Tenant Baru',
        ]);

        $this->assertDatabaseHas('stores', ['company_id' => $companyB->id, 'is_main_store' => true]);
        $this->assertDatabaseHas('customer_tiers', ['company_id' => $companyB->id, 'code' => 'RETAIL']);
        $this->assertDatabaseHas('chart_of_accounts', ['company_id' => $companyB->id]);
        $mainStoreCode = Store::where('company_id', $companyB->id)->where('is_main_store', true)->value('code');
        $this->assertDatabaseMissing('stores', ['company_id' => $companyA->id, 'code' => $mainStoreCode]);
        $this->assertNotSame($companyA->id, $companyB->id);
    }

    public function test_branch_codes_are_unique_within_company_and_can_repeat_in_another_company(): void
    {
        $this->withoutVite();
        $companyA = Company::firstOrFail();
        $storeA = Store::create([
            'company_id' => $companyA->id,
            'code' => 'BRANCH-01',
            'name' => 'Cabang SIGMA A',
            'type' => 'STORE',
            'is_active' => true,
            'is_main_store' => false,
        ]);

        $companyB = app(CompanyService::class)->createCompany([
            'code' => 'COMP-BRANCH',
            'name' => 'PT Tenant Cabang B',
        ]);
        $owner = User::where('role', 'owner')->firstOrFail();

        $response = $this->actingAs($owner)
            ->withSession(['active_company_id' => $companyB->id])
            ->post(route('settings.stores.store'), [
                'code' => ' branch-01 ',
                'name' => 'Cabang OCTA B',
                'type' => 'STORE',
            ]);

        $response->assertRedirect(route('settings.stores.index'));
        $this->assertDatabaseHas('stores', [
            'company_id' => $companyA->id,
            'code' => 'BRANCH-01',
            'name' => 'Cabang SIGMA A',
        ]);
        $this->assertDatabaseHas('stores', [
            'company_id' => $companyB->id,
            'code' => 'BRANCH-01',
            'name' => 'Cabang OCTA B',
        ]);

        $duplicateResponse = $this->from(route('settings.stores.index'))->post(route('settings.stores.store'), [
            'code' => 'BRANCH-01',
            'name' => 'Cabang Duplikat Tenant B',
            'type' => 'STORE',
        ]);
        $duplicateResponse->assertSessionHasErrors('code');

        $storesResponse = $this->actingAs($owner)->withSession(['active_company_id' => $companyB->id])->get(route('settings.stores.index'));
        $storesResponse->assertOk();
        $this->assertFalse($storesResponse->viewData('stores')->contains('id', $storeA->id));
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
        $this->withoutVite();
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

