<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductUnit;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Store;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ReservationSchedulerAndLauncherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMinimalData();
    }

    public function test_release_expired_reservations_command_releases_stock_and_cancels_expired_orders(): void
    {
        $company = Company::firstOrFail();
        $store = Store::firstOrFail();
        $product = Product::where('sku', 'PRD-001')->firstOrFail();
        $unit = ProductUnit::where('product_id', $product->id)->firstOrFail();
        $stock = ProductStock::where('product_id', $product->id)->where('store_id', $store->id)->firstOrFail();

        $stock->update([
            'physical_quantity' => 100,
            'reserved_quantity' => 8,
            'quarantined_quantity' => 0,
        ]);

        $expiredOrder = SalesOrder::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'order_number' => 'ORD-PWA-EXPIRED-01',
            'order_date' => now()->toDateString(),
            'status' => 'PENDING',
            'subtotal' => 75000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 75000,
        ]);
        SalesOrder::where('id', $expiredOrder->id)->update([
            'created_at' => now()->subMinutes(35),
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $expiredOrder->id,
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'unit_code' => $unit->unit_code,
            'conversion_factor' => 1,
            'quantity' => 5,
            'unit_price' => 15000,
            'discount_amount' => 0,
            'subtotal' => 75000,
        ]);

        $activeOrder = SalesOrder::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'order_number' => 'ORD-PWA-ACTIVE-01',
            'order_date' => now()->toDateString(),
            'status' => 'PENDING',
            'subtotal' => 45000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 45000,
        ]);
        SalesOrder::where('id', $activeOrder->id)->update([
            'created_at' => now()->subMinutes(10),
        ]);

        SalesOrderItem::create([
            'sales_order_id' => $activeOrder->id,
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'unit_code' => $unit->unit_code,
            'conversion_factor' => 1,
            'quantity' => 3,
            'unit_price' => 15000,
            'discount_amount' => 0,
            'subtotal' => 45000,
        ]);

        $exitCode = Artisan::call('sigma:release-expired-reservations');
        $this->assertSame(0, $exitCode);

        $this->assertSame('CANCELLED', $expiredOrder->fresh()->status);
        $this->assertSame('PENDING', $activeOrder->fresh()->status);
        $this->assertEquals(3.0, (float) $stock->fresh()->reserved_quantity);
    }

    public function test_release_expired_reservations_is_registered_in_scheduler(): void
    {
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $matching = $events->first(function ($event) {
            return str_contains((string) $event->command, 'sigma:release-expired-reservations');
        });

        $this->assertNotNull($matching, 'Command sigma:release-expired-reservations harus terdaftar di Schedule.');
        $this->assertSame('*/5 * * * *', $matching->expression);
    }

    public function test_launchers_start_schedule_work_and_stop_script_terminates_it(): void
    {
        $startBat = File::get(base_path('START-SIGMA-SERVER.bat'));
        $silentVbs = File::get(base_path('start_server_silent.vbs'));
        $stopBat = File::get(base_path('STOP-SIGMA-SERVER.bat'));

        $this->assertStringContainsString('schedule:work', $startBat, 'START-SIGMA-SERVER.bat harus menjalankan php artisan schedule:work');
        $this->assertStringContainsString('schedule:work', $silentVbs, 'start_server_silent.vbs harus menjalankan php artisan schedule:work');
        $this->assertStringContainsString('schedule:work', $stopBat, 'STOP-SIGMA-SERVER.bat harus menghentikan proses schedule:work');
    }
}
