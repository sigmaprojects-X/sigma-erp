<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('source_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('destination_store_id')->constrained('stores')->cascadeOnDelete();

            $table->string('transfer_number', 50)->unique(); // TRF-YYYYMMDD-XXXX
            $table->date('transfer_date');
            
            // DRAFT -> IN_TRANSIT (Dikirim) -> RECEIVED (Diterima) / CANCELLED
            $table->string('status', 25)->default('DRAFT')->index();

            $table->string('driver_name', 100)->nullable();
            $table->string('vehicle_number', 50)->nullable();

            $table->timestamp('dispatched_at')->nullable();
            $table->uuid('dispatched_by')->nullable()->index();

            $table->timestamp('received_at')->nullable();
            $table->uuid('received_by')->nullable()->index();

            $table->integer('total_items')->default(0);
            $table->decimal('total_sent_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_received_quantity', 15, 4)->default(0.0000);
            $table->text('notes')->nullable();

            $table->uuid('created_by')->index();
            $table->timestamps();

            $table->index(['company_id', 'transfer_date']);
            $table->index(['source_store_id', 'status']);
            $table->index(['destination_store_id', 'status']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->string('unit_code', 20);
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->decimal('quantity_sent', 12, 2);
            $table->decimal('base_quantity_sent', 15, 4);
            $table->decimal('quantity_received', 12, 2)->default(0.00);
            $table->decimal('base_quantity_received', 15, 4)->default(0.0000);
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP saat barang dikirim

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stock_transfer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
