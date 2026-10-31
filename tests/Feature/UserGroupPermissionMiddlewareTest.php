<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGroupPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $cashier;

    protected User $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();

        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->warehouse = User::firstOrCreate(
            ['email' => 'gudang@sigma.local'],
            [
                'name' => 'Staf Gudang',
                'password' => bcrypt('password123'),
                'role' => 'warehouse',
                'store_id' => $this->manager->store_id,
                'is_active' => true,
            ]
        );
    }

    public function test_user_without_user_group_uses_default_role_permissions_without_breaking_legacy_roles(): void
    {
        $this->cashier->update(['user_group_id' => null]);
        $this->manager->update(['user_group_id' => null]);
        $this->warehouse->update(['user_group_id' => null]);

        $this->actingAs($this->cashier)
            ->get(route('pos.index'))
            ->assertOk();

        $this->actingAs($this->manager)
            ->get(route('master.products.create'))
            ->assertOk();

        $this->actingAs($this->warehouse)
            ->get(route('warehouse.movements.in.create'))
            ->assertOk();
    }

    public function test_user_group_checklist_restricts_route_access_when_permission_is_unchecked(): void
    {
        $company = Company::firstOrFail();

        $restrictedManagerGroup = UserGroup::create([
            'company_id' => $company->id,
            'code' => 'MGR_READ_ONLY',
            'name' => 'Manajer Hanya Baca Item & Stok',
            'permissions' => [
                'master_item.buka',
                'stock_in.buka',
                'settings_backup_db.buka',
            ],
            'is_system' => false,
        ]);

        $this->manager->update([
            'user_group_id' => $restrictedManagerGroup->id,
        ]);

        // Boleh membuka halaman daftar produk & stok masuk karena .buka dicentang
        $this->actingAs($this->manager->fresh())
            ->get(route('master.products'))
            ->assertOk();

        $this->actingAs($this->manager->fresh())
            ->get(route('warehouse.movements.in.index'))
            ->assertOk();

        // Ditolak (403) pada aksi .baru karena tidak dicentang di UserGroup
        $this->actingAs($this->manager->fresh())
            ->get(route('master.products.create'))
            ->assertStatus(403);

        $this->actingAs($this->manager->fresh())
            ->post(route('master.products.store'), [])
            ->assertStatus(403);

        $this->actingAs($this->manager->fresh())
            ->get(route('warehouse.movements.in.create'))
            ->assertStatus(403);

        $this->actingAs($this->manager->fresh())
            ->post(route('settings.maintenance.backup'))
            ->assertStatus(403);

        // Setelah Owner mencentang master_item.baru dan stock_in.baru lewat matriks izin
        $this->actingAs($this->owner)
            ->put(route('settings.user-groups.update', $restrictedManagerGroup->id), [
                'save_permissions_matrix' => '1',
                'permissions' => [
                    'master_item.buka',
                    'master_item.baru',
                    'stock_in.buka',
                    'stock_in.baru',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->manager->fresh())
            ->get(route('master.products.create'))
            ->assertOk();

        $this->actingAs($this->manager->fresh())
            ->get(route('warehouse.movements.in.create'))
            ->assertOk();
    }

    public function test_cashier_with_restricted_user_group_cannot_access_unchecked_features(): void
    {
        $company = Company::firstOrFail();

        $strictCashierGroup = UserGroup::create([
            'company_id' => $company->id,
            'code' => 'KASIR_TERBATAS',
            'name' => 'Kasir Hanya Transaksi Dasar',
            'permissions' => [
                'pos_terminal.buka',
                'pos_terminal.baru',
            ],
            'is_system' => false,
        ]);

        $this->cashier->update([
            'user_group_id' => $strictCashierGroup->id,
        ]);

        $this->actingAs($this->cashier->fresh())
            ->get(route('pos.index'))
            ->assertOk();

        // Tidak punya izin pos_return.baru maupun cash_out.baru
        $this->actingAs($this->cashier->fresh())
            ->postJson(route('pos.return', '00000000-0000-0000-0000-000000000001'), [])
            ->assertStatus(403);

        $this->actingAs($this->cashier->fresh())
            ->get(route('accounting.cash.out'))
            ->assertStatus(403);
    }

    public function test_owner_always_bypasses_group_restrictions(): void
    {
        $company = Company::firstOrFail();

        $emptyGroup = UserGroup::create([
            'company_id' => $company->id,
            'code' => 'EMPTY_GRP',
            'name' => 'Grup Kosong',
            'permissions' => [],
            'is_system' => false,
        ]);

        $this->owner->update(['user_group_id' => $emptyGroup->id]);

        $this->actingAs($this->owner->fresh())
            ->get(route('master.products.create'))
            ->assertOk();
    }
}
