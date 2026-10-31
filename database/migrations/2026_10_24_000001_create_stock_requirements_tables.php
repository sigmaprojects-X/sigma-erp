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
        // 1. Header Kebutuhan Stok (Parent Requirement)
        Schema::create('stock_requirements', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            $table->decimal('target_stock', 15, 4);
            $table->decimal('available_stock_snapshot', 15, 4)->default(0.0000);
            $table->decimal('gross_deficit', 15, 4)->default(0.0000);
            $table->decimal('total_allocated', 15, 4)->default(0.0000);

            // OPEN, PARTIALLY_ALLOCATED, AWAITING_SUPPLY, FULFILLED, OVER_COMMITTED, CLOSED_OBSOLETE, CANCELLED
            $table->string('status', 30)->default('OPEN')->index();

            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'store_id', 'status'], 'idx_stock_req_company_store_status');
            $table->index(['store_id', 'product_id', 'status'], 'idx_stock_req_store_product_status');
        });

        // PostgreSQL & SQLite Partial Unique Index:
        // Menjamin HANYA SATU header kebutuhan aktif untuk kombinasi (company_id, store_id, product_id)
        if (in_array(DB::getDriverName(), ['pgsql', 'sqlite'])) {
            DB::statement("
                CREATE UNIQUE INDEX uq_single_active_stock_requirement 
                ON stock_requirements (company_id, store_id, product_id) 
                WHERE status IN ('OPEN', 'PARTIALLY_ALLOCATED', 'AWAITING_SUPPLY', 'OVER_COMMITTED');
            ");
        }

        // 2. Baris Alokasi Transaksi (Child Allocations)
        Schema::create('stock_requirement_allocations', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('stock_requirement_id')
                ->constrained('stock_requirements')
                ->cascadeOnDelete();

            // Transaksi Sumber: PURCHASE_ORDER, STOCK_TRANSFER
            $table->string('allocation_type', 30)->index();
            $table->uuid('reference_id')->index(); // ID PO atau ID Transfer
            $table->uuid('reference_item_id')->nullable()->index(); // ID Item PO atau ID Item Transfer
            $table->string('reference_number', 100)->nullable()->index(); // No Dokumen (PO-xxx / TRF-xxx)
            $table->string('idempotency_key', 100)->nullable()->unique(); // Token idempotensi anti-duplikasi atomik

            // Kuantitas dalam Satuan Dasar Produk (Base UOM)
            $table->decimal('initial_allocated_base_qty', 15, 4);
            $table->decimal('received_base_qty', 15, 4)->default(0.0000);
            $table->decimal('released_base_qty', 15, 4)->default(0.0000);
            $table->decimal('outstanding_base_qty', 15, 4);

            // ACTIVE (DRAFT lock), COMMITTED (ORDERED/IN_TRANSIT), FULFILLED (Diterima lengkap), RELEASED (Batal/Ditolak)
            $table->string('status', 30)->default('ACTIVE')->index();

            $table->uuid('allocated_by')->nullable()->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['allocation_type', 'reference_id'], 'idx_alloc_type_ref_id');
            $table->unique(['allocation_type', 'reference_item_id'], 'uq_alloc_type_ref_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_requirement_allocations');
        Schema::dropIfExists('stock_requirements');
    }
};
