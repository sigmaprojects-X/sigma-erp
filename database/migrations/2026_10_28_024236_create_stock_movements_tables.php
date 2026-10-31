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
        Schema::create('stock_movements', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();

            $table->string('movement_number', 50)->unique(); // IN-YYYYMMDD-XXXX atau OUT-YYYYMMDD-XXXX
            $table->string('movement_type', 10)->index(); // IN atau OUT
            $table->date('movement_date');
            $table->string('category_reason', 50)->index(); // BONUS_SUPPLIER, SAMPLE, INTERNAL_USE, PROMOTION_GIVEAWAY, SPOILAGE_WASTE, CORRECTION, OTHER
            $table->string('recipient_or_source', 150)->nullable(); // Nama Staf Pengambil / Supplier Pemberi
            $table->string('status', 20)->default('COMPLETED')->index(); // COMPLETED

            $table->integer('total_items')->default(0);
            $table->decimal('total_cost_value', 18, 4)->default(0.0000);

            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('approved_by')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'movement_type', 'movement_date']);
            $table->index(['store_id', 'movement_type', 'movement_date']);
        });

        Schema::create('stock_movement_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('stock_movement_id')->constrained('stock_movements')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->string('unit_code', 20);
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->decimal('quantity', 12, 2);
            $table->decimal('base_quantity', 15, 4);

            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->decimal('total_cost', 18, 4)->default(0.0000);

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movement_items');
        Schema::dropIfExists('stock_movements');
    }
};
