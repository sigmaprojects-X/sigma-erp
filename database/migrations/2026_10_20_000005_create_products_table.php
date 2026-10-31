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
        Schema::create('products', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->string('sku', 100);
            $table->string('name', 255);
            $table->string('slug', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('category', 100)->nullable();
            $table->string('brand', 100)->nullable();

            // Kolom Wajib Perhitungan HPP Moving Average (AGENTS.md Section 2)
            // formula: HPP baru = (total_stock_value + incoming_value) / (total_quantity + incoming_qty)
            $table->decimal('cost_price', 15, 4)->default(0.0000); // Nilai HPP Moving Average berjalan
            $table->decimal('last_purchase_price', 15, 4)->default(0.0000); // Harga beli terakhir dari supplier
            $table->decimal('total_stock_value', 18, 4)->default(0.0000); // Akumulasi total nilai stok untuk weighted average
            $table->decimal('total_quantity', 15, 4)->default(0.0000); // Akumulasi total kuantitas stok untuk weighted average

            // Pengaturan Inventori & Stock Safety
            $table->decimal('min_stock_alert', 12, 2)->default(0.00); // Peringatan restock minimum
            $table->decimal('max_stock', 12, 2)->nullable();
            $table->boolean('has_serial_number')->default(false);
            $table->boolean('has_batch_expiry')->default(false);
            $table->boolean('is_point_of_sale')->default(true); // Flag tampil di POS kasir
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'store_id']);
        });

        // PostgreSQL Trigram index & Check Constraints (AGENTS.md Section 2 & 3)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX products_name_trgm_idx ON products USING gin (name gin_trgm_ops);');
            DB::statement('CREATE INDEX products_sku_trgm_idx ON products USING gin (sku gin_trgm_ops);');
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_cost_price_check CHECK (cost_price >= 0);');
            DB::statement('ALTER TABLE products ADD CONSTRAINT products_total_quantity_check CHECK (total_quantity >= 0);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
