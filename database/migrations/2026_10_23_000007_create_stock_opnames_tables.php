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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();

            $table->string('opname_number', 50)->unique(); // OPN-YYYYMMDD-XXXX
            $table->date('opname_date');
            
            // DRAFT -> IN_PROGRESS -> PENDING_APPROVAL -> COMPLETED / CANCELLED
            $table->string('status', 25)->default('DRAFT')->index();
            $table->boolean('is_blind')->default(true); // AGENTS.md: Blind Stock Opname

            $table->decimal('total_system_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_actual_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_discrepancy_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_discrepancy_value', 15, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->uuid('created_by')->index();

            // Approval oleh Store Manager / Back-Office
            $table->uuid('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'opname_date']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            // Sembunyikan system_quantity dari tampilan staf saat blind opname aktif
            $table->decimal('system_quantity', 15, 4)->default(0.0000);
            $table->decimal('actual_quantity', 15, 4)->default(0.0000);
            $table->decimal('discrepancy_quantity', 15, 4)->default(0.0000); // actual - system
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP saat opname
            $table->decimal('discrepancy_value', 15, 2)->default(0.00); // discrepancy * unit_cost

            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['stock_opname_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
