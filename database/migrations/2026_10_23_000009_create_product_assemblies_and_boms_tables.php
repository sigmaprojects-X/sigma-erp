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
        // 1. Template Resep / Bill of Materials (BOM) Paket Produk
        Schema::create('product_boms', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('component_product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4); // Jumlah komponen per 1 unit parent barang jadi
            $table->timestamps();

            $table->index(['company_id', 'parent_product_id']);
            $table->index(['component_product_id']);
        });

        // 2. Transaksi Perakitan & Pembongkaran Barang (Assembly & Disassembly)
        Schema::create('product_assemblies', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('assembly_number', 50)->unique();
            $table->date('assembly_date');
            $table->string('type', 20)->default('ASSEMBLY')->index(); // ASSEMBLY, DISASSEMBLY
            $table->foreignUuid('parent_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('parent_product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2); // Kuantitas barang jadi yang dirakit/dibongkar
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP per unit paket jadi
            $table->decimal('total_cost', 15, 2)->default(0.00); // Total HPP gabungan seluruh komponen
            $table->text('notes')->nullable();
            $table->uuid('assembled_by')->nullable()->index();
            $table->timestamps();

            $table->index(['company_id', 'store_id', 'assembly_date']);
        });

        // 3. Rincian Komponen Bahan Baku Perakitan
        Schema::create('product_assembly_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('product_assembly_id')->constrained('product_assemblies')->cascadeOnDelete();
            $table->foreignUuid('component_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('component_product_unit_id')->constrained('product_units')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2); // Kuantitas dalam kemasan komponen
            $table->decimal('base_quantity', 15, 4); // Kuantitas konversi ke satuan dasar
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP komponen saat perakitan
            $table->decimal('total_cost', 15, 2)->default(0.00); // quantity * unit_cost
            $table->timestamps();

            $table->index(['product_assembly_id', 'component_product_id'], 'assembly_items_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_assembly_items');
        Schema::dropIfExists('product_assemblies');
        Schema::dropIfExists('product_boms');
    }
};
