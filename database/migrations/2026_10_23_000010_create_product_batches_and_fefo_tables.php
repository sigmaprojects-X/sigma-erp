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
        // 1. Tabel Master Batch & Lot dengan Tanggal Kedaluwarsa (Expired Date)
        Schema::create('product_batches', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('batch_number', 50); // Nomor Batch / Lot dari Pabrik / Supplier
            $table->date('expired_date'); // Tanggal Kedaluwarsa (Kunci Inti FEFO)
            $table->date('manufacture_date')->nullable(); // Tanggal Produksi
            $table->decimal('initial_quantity', 15, 4)->default(0.0000);
            $table->decimal('current_quantity', 15, 4)->default(0.0000);
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP batch bersangkutan
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, EXPIRED, DEPLETED
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index Performa untuk Pencarian Cepat FEFO di Kasir POS & Gudang
            $table->index(['store_id', 'product_id', 'status', 'expired_date'], 'idx_batch_fefo_lookup');
            $table->index(['batch_number'], 'idx_batch_number');
            $table->index(['expired_date'], 'idx_batch_expiry');
        });

        // 2. Tambahkan Kolom Pelacakan Batch ke Item Penjualan Kasir POS
        if (Schema::hasTable('pos_transaction_items')) {
            Schema::table('pos_transaction_items', function (Blueprint $table) {
                $table->uuid('batch_id')->nullable()->after('product_unit_id');
                $table->string('batch_number', 50)->nullable()->after('batch_id');
                $table->date('expired_date')->nullable()->after('batch_number');

                $table->foreign('batch_id')->references('id')->on('product_batches')->nullOnDelete();
                $table->index(['batch_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pos_transaction_items')) {
            Schema::table('pos_transaction_items', function (Blueprint $table) {
                $table->dropForeign(['batch_id']);
                $table->dropIndex(['batch_id']);
                $table->dropColumn(['batch_id', 'batch_number', 'expired_date']);
            });
        }

        Schema::dropIfExists('product_batches');
    }
};
