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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();

            $table->string('adjustment_number', 50)->unique(); // ADJ-YYYYMMDD-XXXX
            $table->date('adjustment_date');
            $table->string('adjustment_type', 30)->default('DAMAGE_WASTE')->index(); // DAMAGE_WASTE, INTERNAL_USE, STOCK_IN_CORRECTION, STOCK_OUT_CORRECTION
            $table->string('status', 20)->default('COMPLETED')->index(); // COMPLETED, PENDING_APPROVAL, REJECTED

            $table->integer('total_items')->default(0);
            $table->decimal('total_cost_value', 18, 4)->default(0.0000);

            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('approved_by')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'adjustment_date']);
            $table->index(['store_id', 'adjustment_date']);
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->string('unit_code', 20);
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->decimal('quantity', 12, 2);
            $table->decimal('base_quantity', 15, 4);

            $table->decimal('unit_cost', 15, 4)->default(0.0000);
            $table->decimal('total_cost', 18, 4)->default(0.0000);

            $table->string('action', 30)->default('MOVE_TO_QUARANTINE'); // MOVE_TO_QUARANTINE, DEDUCT_DIRECT, ADD_STOCK
            $table->string('reason', 150)->nullable(); // Misal: Pecah di Rak, Kedaluwarsa, Pemakaian Toko, Bonus Supplier
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
