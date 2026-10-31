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
        Schema::create('purchase_return_debt_allocations', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();

            $table->decimal('allocated_amount', 15, 2);
            $table->string('notes', 255)->nullable();

            $table->timestamps();

            $table->index(['purchase_id', 'purchase_return_id'], 'idx_prda_purchase_return');
            $table->index(['purchase_return_id'], 'idx_prda_return');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_debt_allocations');
    }
};
