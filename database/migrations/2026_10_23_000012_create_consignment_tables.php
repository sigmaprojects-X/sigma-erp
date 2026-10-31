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
        // 1. Tambah Kolom Konsinyasi ke Tabel Products
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_consignment')->default(false)->after('has_batch_expiry');
            $table->foreignUuid('consignment_supplier_id')->nullable()->after('is_consignment')->constrained('suppliers')->nullOnDelete();
            $table->decimal('consignment_cost', 15, 4)->default(0.0000)->after('consignment_supplier_id');
        });

        // 2. Tabel Penerimaan Barang Konsinyasi (Consignment Receipts)
        Schema::create('consignment_receipts', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('receipt_number', 50)->unique();
            $table->date('receipt_date');
            $table->integer('total_items')->default(0);
            $table->decimal('total_estimated_amount', 15, 2)->default(0.00);
            $table->string('status', 20)->default('RECEIVED'); // RECEIVED, SETTLED, CLOSED
            $table->text('notes')->nullable();
            $table->uuid('received_by')->nullable()->index();

            $table->timestamps();

            $table->index(['store_id', 'supplier_id', 'status']);
            $table->index(['receipt_date']);
        });

        // 3. Tabel Detail Item Penerimaan Konsinyasi
        Schema::create('consignment_receipt_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('consignment_receipt_id')->constrained('consignment_receipts')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->decimal('quantity', 15, 4); // Kuantitas titipan yang diterima
            $table->decimal('unit_cost', 15, 4); // Harga kesepakatan konsinyasi per unit
            $table->decimal('sold_quantity', 15, 4)->default(0.0000); // Laku terjual di POS
            $table->decimal('returned_quantity', 15, 4)->default(0.0000); // Diretur ke supplier
            $table->decimal('settled_quantity', 15, 4)->default(0.0000); // Sudah dibuatkan tagihan/settlement
            $table->decimal('total_cost', 15, 2);

            $table->timestamps();

            $table->index(['consignment_receipt_id', 'product_id'], 'idx_cs_receipt_item_prod');
        });

        // 4. Tabel Rekonsiliasi & Tagihan Konsinyasi (Consignment Settlements)
        Schema::create('consignment_settlements', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('settlement_number', 50)->unique();
            $table->date('settlement_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('total_sold_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_settlement_amount', 15, 2)->default(0.00);
            $table->string('payment_status', 20)->default('UNPAID'); // UNPAID, PAID
            $table->string('payment_method', 20)->nullable(); // CASH, TRANSFER
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(['store_id', 'supplier_id', 'payment_status']);
        });

        // 5. Tabel Detail Item Settlement Tagihan Konsinyasi
        Schema::create('consignment_settlement_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('consignment_settlement_id')->constrained('consignment_settlements')->cascadeOnDelete();
            $table->foreignUuid('consignment_receipt_item_id')->constrained('consignment_receipt_items')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->decimal('settled_quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_amount', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consignment_settlement_items');
        Schema::dropIfExists('consignment_settlements');
        Schema::dropIfExists('consignment_receipt_items');
        Schema::dropIfExists('consignment_receipts');

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['consignment_supplier_id']);
            $table->dropColumn(['is_consignment', 'consignment_supplier_id', 'consignment_cost']);
        });
    }
};
