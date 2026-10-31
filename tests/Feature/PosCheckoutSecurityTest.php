<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CashierShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerTier;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Models\Store;
use App\Models\TaxConfiguration;
use App\Models\User;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Store $store;

    protected CustomerTier $defaultTier;

    protected Product $product;

    protected ProductUnit $unit;

    protected User $cashier;

    protected User $manager;

    protected CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
        $this->seed(AccountingSeeder::class);

        $this->company = Company::where('code', 'SIGMA-HO')->firstOrFail();
        $this->store = Store::where('code', 'STR-001')->firstOrFail();
        $this->defaultTier = CustomerTier::where('code', 'RETAIL')->firstOrFail();
        $this->product = Product::where('sku', 'PRD-001')->firstOrFail();
        $this->unit = ProductUnit::where('product_id', $this->product->id)->firstOrFail();
        $this->cashier = User::where('role', 'cashier')->firstOrFail();
        $this->manager = User::where('role', 'manager')->firstOrFail();
        $this->shift = CashierShift::where('cashier_id', $this->cashier->id)->firstOrFail();
    }

    public function test_checkout_ignores_client_rounding_amount_and_caps_server_rounding_at_500(): void
    {
        // 1. Saat rounding company non-aktif, klien mengirim rounding_amount = -10000 untuk memotong tagihan
        $this->company->update([
            'rounding_enabled' => false,
        ]);

        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 30000,
            'rounding_amount' => -10000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $tx1 = PosTransaction::findOrFail($response->json('transaction_id'));
        $this->assertEquals(0.0, (float) $tx1->rounding_amount);
        $this->assertEquals(30000.0, (float) $tx1->grand_total);

        // 2. Saat rounding company aktif dengan step 1000 (ROUND_UP), harga 15.100 seharusnya dibulatkan maksimal +500 (bukan +900)
        ProductPrice::where('product_id', $this->product->id)
            ->where('customer_tier_id', $this->defaultTier->id)
            ->update(['price' => 15100]);

        $this->company->update([
            'rounding_enabled' => true,
            'rounding_step' => 1000,
            'rounding_mode' => 'ROUND_UP',
            'rounding_applies_to' => 'ALL',
        ]);

        $responseUp = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'rounding_amount' => 50000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $responseUp->assertStatus(200)
            ->assertJsonPath('success', true);

        $txUp = PosTransaction::findOrFail($responseUp->json('transaction_id'));
        $this->assertEquals(500.0, (float) $txUp->rounding_amount);
        $this->assertEquals(15600.0, (float) $txUp->grand_total);

        // 3. Saat ROUND_DOWN dengan harga 15.900 dan step 1000, pembulatan dibatasi maksimal -500 (bukan -900)
        ProductPrice::where('product_id', $this->product->id)
            ->where('customer_tier_id', $this->defaultTier->id)
            ->update(['price' => 15900]);

        $this->company->update([
            'rounding_mode' => 'ROUND_DOWN',
        ]);

        $responseDown = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'rounding_amount' => -9000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $responseDown->assertStatus(200)
            ->assertJsonPath('success', true);

        $txDown = PosTransaction::findOrFail($responseDown->json('transaction_id'));
        $this->assertEquals(-500.0, (float) $txDown->rounding_amount);
        $this->assertEquals(15400.0, (float) $txDown->grand_total);
    }

    public function test_checkout_ignores_client_manual_discount_and_uses_promo_engine_or_manager_pin(): void
    {
        // 1. Tanpa promo aktif dan tanpa PIN manajer, discount_amount dari klien diabaikan (Rp 0)
        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                    'discount_amount' => 14000,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $tx1 = PosTransaction::with('items')->findOrFail($response->json('transaction_id'));
        $this->assertEquals(0.0, (float) $tx1->items->first()->discount_amount);
        $this->assertEquals(15000.0, (float) $tx1->grand_total);

        // 2. Dengan promo aktif di server (DISCOUNT_PERCENT 10%), diskon dihitung dari PromoEngineService (1.500), bukan nilai klien (14.000)
        $promo = Promotion::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'code' => 'PROMO-10',
            'name' => 'Diskon 10 Persen',
            'type' => 'DISCOUNT_PERCENT',
            'discount_percent' => 10,
            'min_spend' => 10000,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'is_active' => true,
        ]);

        $responsePromo = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                    'discount_amount' => 14000,
                ],
            ],
        ]);

        $responsePromo->assertStatus(200)
            ->assertJsonPath('success', true);

        $txPromo = PosTransaction::with('items')->findOrFail($responsePromo->json('transaction_id'));
        $this->assertEquals(1500.0, (float) $txPromo->items->first()->discount_amount);
        $this->assertEquals(13500.0, (float) $txPromo->grand_total);

        $promo->update(['is_active' => false]);

        // 3. Diskon manual dengan PIN manajer yang salah harus ditolak (403) dan dicatat ke AuditLog
        $responseBadPin = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'manager_pin' => '999999',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                    'discount_amount' => 2000,
                ],
            ],
        ]);

        $responseBadPin->assertStatus(403);
        $this->assertTrue(
            AuditLog::where('action', 'MANAGER_PIN_FAILED')->exists(),
            'AuditLog harus mencatat kegagalan otorisasi PIN Manajer.'
        );

        // 4. Diskon manual dengan PIN manajer yang valid (role manager, Gate use-manager-pin) diizinkan dan dicatat ke AuditLog
        $responseValidPin = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'manager_pin' => '123456',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                    'discount_amount' => 2000,
                ],
            ],
        ]);

        $responseValidPin->assertStatus(200)
            ->assertJsonPath('success', true);

        $txManual = PosTransaction::with('items')->findOrFail($responseValidPin->json('transaction_id'));
        $this->assertEquals(2000.0, (float) $txManual->items->first()->discount_amount);
        $this->assertEquals(13000.0, (float) $txManual->grand_total);
        $this->assertTrue(
            AuditLog::where('module', 'POS')->where('action', 'MANUAL_DISCOUNT')->exists(),
            'AuditLog harus mencatat persetujuan diskon manual menggunakan PIN Manajer.'
        );
    }

    public function test_checkout_ignores_client_tax_amount_and_recalculates_from_active_tax_config(): void
    {
        TaxConfiguration::create([
            'company_id' => $this->company->id,
            'is_tax_enabled' => true,
            'tax_name' => 'PPN',
            'tax_rate_percent' => 11.00,
            'is_tax_inclusive' => false,
            'effective_from' => now()->subDays(5)->toDateString(),
            'effective_to' => now()->addDays(5)->toDateString(),
        ]);

        // Klien mencoba mengirim tax_amount = 0 dan tax_mode = NON padahal PPN 11% Exclude aktif
        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 50000,
            'tax_amount' => 0,
            'tax_mode' => 'NON',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 2, // 2 x 15.000 = 30.000, PPN 11% = 3.300, Grand Total = 33.300
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $tx = PosTransaction::findOrFail($response->json('transaction_id'));
        $this->assertSame('PPN_EXCLUDE', $tx->tax_mode);
        $this->assertEquals(3300.0, (float) $tx->tax_amount);
        $this->assertEquals(33300.0, (float) $tx->grand_total);
    }

    public function test_checkout_ignores_client_customer_tier_id_and_uses_customer_or_default_tier(): void
    {
        $wholesaleTier = CustomerTier::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => null,
            'code' => 'GROSIR',
            'name' => 'Grosir Partai',
            'is_default' => false,
        ]);

        ProductPrice::factory()->create([
            'company_id' => $this->company->id,
            'store_id' => null,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'customer_tier_id' => $wholesaleTier->id,
            'min_quantity' => 1,
            'price' => 11000,
        ]);

        // 1. Tanpa customer_id, klien mengirim customer_tier_id = GROSIR (11.000), server wajib memakai tier default RETAIL (15.000)
        $responseWalkIn = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $wholesaleTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $responseWalkIn->assertStatus(200)
            ->assertJsonPath('success', true);

        $txWalkIn = PosTransaction::findOrFail($responseWalkIn->json('transaction_id'));
        $this->assertSame($this->defaultTier->id, $txWalkIn->customer_tier_id);
        $this->assertEquals(15000.0, (float) $txWalkIn->grand_total);

        // 2. Dengan customer_id milik pelanggan GROSIR, meskipun klien mengirim customer_tier_id = RETAIL, server memakai tier milik customer (GROSIR = 11.000)
        $wholesaleCustomer = Customer::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_tier_id' => $wholesaleTier->id,
            'code' => 'CUST-GRS-01',
            'name' => 'Toko Makmur Grosir',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $responseMember = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_id' => $wholesaleCustomer->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $responseMember->assertStatus(200)
            ->assertJsonPath('success', true);

        $txMember = PosTransaction::findOrFail($responseMember->json('transaction_id'));
        $this->assertSame($wholesaleTier->id, $txMember->customer_tier_id);
        $this->assertEquals(11000.0, (float) $txMember->grand_total);
    }

    public function test_pos_controller_always_determines_enforced_tax_mode_from_back_office_and_ignores_request_tax_mode(): void
    {
        TaxConfiguration::create([
            'company_id' => $this->company->id,
            'is_tax_enabled' => true,
            'tax_name' => 'PPN',
            'tax_rate_percent' => 11.00,
            'is_tax_inclusive' => true,
            'effective_from' => now()->subDays(2)->toDateString(),
            'effective_to' => now()->addDays(2)->toDateString(),
        ]);

        $capturedTaxMode = null;
        $realPosService = app(\App\Services\PosService::class);
        $mock = \Mockery::mock($realPosService)->makePartial();
        $mock->shouldReceive('checkout')
            ->once()
            ->withArgs(function (...$args) use (&$capturedTaxMode) {
                // Argumen ke-10 (index 9) pada PosService::checkout adalah $taxMode
                $capturedTaxMode = $args[9] ?? null;

                return true;
            })
            ->andReturnUsing(function (...$args) use ($realPosService) {
                return $realPosService->checkout(...$args);
            });

        $this->app->instance(\App\Services\PosService::class, $mock);

        // Request mengirim tax_mode = 'NON' (atau array/string tidak valid), PosController wajib mengabaikannya dan meneruskan PPN_INCLUDE dari TaxConfiguration
        $response = $this->actingAs($this->cashier)->postJson('/pos/checkout', [
            'shift_id' => $this->shift->id,
            'customer_tier_id' => $this->defaultTier->id,
            'payment_method' => 'CASH',
            'paid_amount' => 20000,
            'tax_mode' => 'NON',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame('PPN_INCLUDE', $capturedTaxMode);
    }
}
