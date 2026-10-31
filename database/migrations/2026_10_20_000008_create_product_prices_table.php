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
        Schema::create('product_prices', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete(); // null = harga default seluruh cabang

            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->foreignUuid('customer_tier_id')->constrained('customer_tiers')->cascadeOnDelete();

            // 3-Dimensional Automated Pricing (AGENTS.md Section 3):
            // Dimensi 1: Satuan / Kemasan (product_unit_id)
            // Dimensi 2: Customer Tier (customer_tier_id)
            // Dimensi 3: Tiered Quantity / min_qty (min_quantity)
            $table->decimal('min_quantity', 12, 2)->default(1.00); // Tiered Quantity (min_qty)
            $table->decimal('price', 15, 2)->default(0.00); // Harga jual final
            $table->decimal('margin_percentage', 8, 2)->nullable(); // Target margin % untuk Margin Automation (Auto-Markup)

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index untuk kecepatan query backend pricing engine saat POS checkout
            $table->index(['company_id', 'product_id', 'product_unit_id', 'customer_tier_id', 'min_quantity'], 'idx_product_prices_lookup');
            $table->index(['company_id', 'store_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            // Unique index untuk harga global (store_id IS NULL)
            DB::statement('CREATE UNIQUE INDEX product_prices_global_unique ON product_prices (company_id, product_id, product_unit_id, customer_tier_id, min_quantity) WHERE store_id IS NULL;');
            // Unique index untuk harga khusus cabang (store_id IS NOT NULL)
            DB::statement('CREATE UNIQUE INDEX product_prices_store_unique ON product_prices (company_id, store_id, product_id, product_unit_id, customer_tier_id, min_quantity) WHERE store_id IS NOT NULL;');
            // Check constraints
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT product_prices_price_check CHECK (price >= 0);');
            DB::statement('ALTER TABLE product_prices ADD CONSTRAINT product_prices_min_quantity_check CHECK (min_quantity > 0);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
