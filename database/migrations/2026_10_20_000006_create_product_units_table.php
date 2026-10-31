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
        Schema::create('product_units', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('unit_code', 30); // PCS, PACK, DUS, BOX, KG, etc.
            $table->string('unit_name', 50); // Pieces, Pack isi 10, Dus isi 120, etc.
            $table->decimal('conversion_factor', 12, 4)->default(1.0000); // Nilai pengali ke satuan dasar (base unit = 1)
            $table->boolean('is_base_unit')->default(false); // Penanda satuan terkecil / dasar produk
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'unit_code']);
            $table->index(['company_id', 'product_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_units ADD CONSTRAINT product_units_conversion_factor_check CHECK (conversion_factor > 0);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
