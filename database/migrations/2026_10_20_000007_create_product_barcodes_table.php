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
        Schema::create('product_barcodes', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->string('barcode', 100);
            $table->boolean('is_primary')->default(false); // Barcode utama satuan ini
            $table->timestamps();

            $table->unique(['company_id', 'barcode']);
            $table->index(['product_id', 'product_unit_id']);
            $table->index(['company_id', 'product_id']);
        });

        // PostgreSQL Trigram index for lightning-fast barcode search (AGENTS.md Section 3)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX product_barcodes_barcode_trgm_idx ON product_barcodes USING gin (barcode gin_trgm_ops);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
