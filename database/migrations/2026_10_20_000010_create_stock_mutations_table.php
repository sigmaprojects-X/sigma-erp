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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('reference_number', 100)->index(); // No Dokumen (GRN, POS, TRF, SO, dll.)
            $table->string('mutation_type', 50)->index(); // GOODS_RECEIPT, SALE, SALE_RETURN, TRANSFER_IN, TRANSFER_OUT, etc.
            $table->decimal('quantity', 15, 4); // Jumlah mutasi (+ masuk, - keluar)
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP saat transaksi terjadi
            $table->decimal('total_cost', 18, 4)->default(0.0000); // quantity * unit_cost

            // Audit Trail Saldo Stok (Sebelum vs Sesudah)
            $table->decimal('qty_before', 15, 4)->default(0.0000);
            $table->decimal('qty_after', 15, 4)->default(0.0000);

            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(['store_id', 'product_id', 'created_at'], 'idx_stock_mutations_ledger');
            $table->index(['company_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};
