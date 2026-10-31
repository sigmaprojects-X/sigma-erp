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
        Schema::create('product_stocks', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            // Formula: Available Stock = physical_quantity - reserved_quantity - quarantined_quantity (AGENTS.md Section 2)
            $table->decimal('physical_quantity', 15, 4)->default(0.0000); // Stok fisik aktual di cabang/gudang
            $table->decimal('reserved_quantity', 15, 4)->default(0.0000); // Stok reservasi pesanan PWA (TTL 30 menit)
            $table->decimal('quarantined_quantity', 15, 4)->default(0.0000); // Stok rusak / retur dalam karantina

            $table->timestamps();

            $table->unique(['store_id', 'product_id'], 'unique_store_product_stock');
            $table->index(['company_id', 'product_id']);
        });

        // Enforce Zero Negative Inventory at PostgreSQL database level (AGENTS.md Section 2)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_stocks ADD CONSTRAINT chk_physical_quantity_non_negative CHECK (physical_quantity >= 0);');
            DB::statement('ALTER TABLE product_stocks ADD CONSTRAINT chk_reserved_quantity_non_negative CHECK (reserved_quantity >= 0);');
            DB::statement('ALTER TABLE product_stocks ADD CONSTRAINT chk_quarantined_quantity_non_negative CHECK (quarantined_quantity >= 0);');
            DB::statement('ALTER TABLE product_stocks ADD CONSTRAINT chk_available_quantity_valid CHECK (physical_quantity >= (reserved_quantity + quarantined_quantity));');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
