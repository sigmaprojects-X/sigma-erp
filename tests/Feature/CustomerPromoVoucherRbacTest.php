<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\LoyaltyReward;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPromoVoucherRbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $manager;

    protected User $cashier;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();

        $company = Company::firstOrFail();
        $tier = CustomerTier::firstOrFail();

        $this->owner = User::where('role', 'owner')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->customer = Customer::create([
            'company_id' => $company->id,
            'customer_tier_id' => $tier->id,
            'code' => 'CUST-001',
            'name' => 'Pelanggan Loyalitas',
            'phone' => '08123456789',
            'loyalty_points' => 0,
            'lifetime_points' => 0,
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_access_read_routes_for_loyalty_promotions_and_vouchers(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('customers.loyalty.index'))
            ->assertOk();

        $this->actingAs($this->cashier)
            ->get(route('customers.loyalty.ledger', $this->customer->id))
            ->assertOk();

        $this->actingAs($this->cashier)
            ->get(route('customers.loyalty.lookup', $this->customer->id))
            ->assertOk();

        $this->actingAs($this->cashier)
            ->get(route('customers.rewards.index'))
            ->assertOk();

        $this->actingAs($this->cashier)
            ->get(route('promotions.index'))
            ->assertOk();

        $this->actingAs($this->cashier)
            ->get(route('vouchers.index'))
            ->assertOk();
    }

    public function test_cashier_is_forbidden_from_sensitive_loyalty_and_reward_actions(): void
    {
        $company = Company::firstOrFail();

        $reward = LoyaltyReward::create([
            'company_id' => $company->id,
            'code' => 'RWD-TEST-01',
            'name' => 'Payung Cantik',
            'points_required' => 50,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->cashier)
            ->get(route('customers.loyalty.settings'))
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->post(route('customers.loyalty.settings.update'), [
                'name' => 'Hacked Program',
                'spend_amount_per_point' => 1,
                'point_redemption_value' => 10000,
                'min_points_to_redeem' => 1,
            ])
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->post(route('customers.loyalty.adjust', $this->customer->id), [
                'points_delta' => 500,
                'reason' => 'Tambah poin sendiri',
            ])
            ->assertStatus(403);

        $this->assertEquals(0, (int) $this->customer->fresh()->loyalty_points);

        $this->actingAs($this->cashier)
            ->post(route('customers.loyalty.expire'))
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->get(route('customers.rewards.create'))
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->post(route('customers.rewards.store'), [
                'code' => 'RWD-CASHIER',
                'name' => 'Hadiah Ilegal',
                'points_required' => 1,
                'stock_quantity' => 5,
            ])
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->delete(route('customers.rewards.destroy', $reward->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('loyalty_rewards', ['id' => $reward->id]);
    }

    public function test_cashier_is_forbidden_from_creating_toggling_or_deleting_promotions_and_vouchers(): void
    {
        $company = Company::firstOrFail();

        $promotion = Promotion::create([
            'company_id' => $company->id,
            'code' => 'PRM-TEST-01',
            'name' => 'Promo Akhir Pekan',
            'type' => 'PERCENTAGE_DISCOUNT',
            'discount_value' => 10,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'is_active' => true,
        ]);

        $voucher = Voucher::create([
            'company_id' => $company->id,
            'code' => 'VCH-TEST-001',
            'type' => 'VALUE',
            'amount' => 50000,
            'min_spend' => 0,
            'status' => 'ISSUED',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ]);

        // Promotions write routes
        $this->actingAs($this->cashier)
            ->get(route('promotions.create'))
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->post(route('promotions.store'), [
                'code' => 'PRM-HACK',
                'name' => 'Promo Kasir',
                'type' => 'PERCENTAGE_DISCOUNT',
                'discount_value' => 90,
            ])
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->patch(route('promotions.toggle', $promotion->id))
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->delete(route('promotions.destroy', $promotion->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'is_active' => true]);

        // Vouchers write routes
        $this->actingAs($this->cashier)
            ->get(route('vouchers.create'))
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->post(route('vouchers.store'), [
                'type' => 'VALUE',
                'value' => 100000,
                'quantity' => 1,
                'valid_from' => now()->toDateString(),
                'valid_until' => now()->addDays(10)->toDateString(),
            ])
            ->assertStatus(403);

        $this->actingAs($this->cashier)
            ->delete(route('vouchers.destroy', $voucher->id))
            ->assertStatus(403);

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id]);
    }

    public function test_manager_can_adjust_points_and_manual_point_action_is_recorded_to_audit_log(): void
    {
        AuditLog::query()->delete();

        $response = $this->actingAs($this->manager)
            ->postJson(route('customers.loyalty.adjust', $this->customer->id), [
                'points_delta' => 125,
                'reason' => 'Kompensasi poin transaksi manual',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(125, (int) $this->customer->fresh()->loyalty_points);

        $auditLog = AuditLog::where('user_id', $this->manager->id)
            ->get()
            ->first(function (AuditLog $log) {
                return ($log->new_values['customer_id'] ?? null) === $this->customer->id
                    && ($log->new_values['points_delta'] ?? null) === 125
                    && ($log->old_values['loyalty_points'] ?? null) === 0
                    && ($log->new_values['loyalty_points'] ?? null) === 125;
            });

        $this->assertNotNull($auditLog, 'Penyesuaian poin manual harus tercatat di AuditLog.');
    }
}
