<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat Tabel LPB (Goods Receipts / Surat Jalan Penerimaan Gudang)
        Schema::create('goods_receipts', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            
            $table->string('receipt_number', 50)->unique(); // LPB-YYYYMMDD-XXXX
            $table->string('delivery_order_number', 100);   // Nomor Surat Jalan dari Supplier
            $table->date('receipt_date');
            $table->string('status', 30)->default('RECEIVED'); // RECEIVED, MATCHED, CANCELLED
            
            $table->integer('total_items_received')->default(0);
            $table->decimal('estimated_total_value', 15, 2)->default(0.00);
            $table->decimal('shipping_cost', 15, 2)->default(0.00);   // Landed cost: Ongkir
            $table->decimal('handling_cost', 15, 2)->default(0.00);   // Landed cost: Bongkar Muat
            $table->decimal('total_landed_cost', 15, 2)->default(0.00);
            
            $table->string('driver_name', 100)->nullable();
            $table->string('vehicle_number', 30)->nullable();
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'store_id', 'receipt_date'], 'idx_gr_store_date');
            $table->index(['supplier_id', 'status'], 'idx_gr_supplier_status');
        });

        // 2. Buat Tabel Item LPB
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignUuid('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            
            $table->string('unit_code', 20)->default('PCS');
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('base_quantity', 12, 4);
            $table->decimal('estimated_unit_price', 15, 2)->default(0.00);
            $table->decimal('allocated_landed_cost', 15, 2)->default(0.00);
            $table->decimal('effective_cost_price', 15, 2)->default(0.00); // HPP Satuan Riil
            $table->decimal('subtotal', 15, 2)->default(0.00);
            
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('physical_condition', 100)->default('BAIK');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['goods_receipt_id', 'product_id'], 'idx_gri_receipt_product');
        });

        // 3. Tambahkan kolom relasi LPB ke tabel Faktur Pembelian (Purchases)
        if (!Schema::hasColumn('purchases', 'goods_receipt_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->foreignUuid('goods_receipt_id')->nullable()->after('purchase_order_id')->constrained('goods_receipts')->nullOnDelete();
                $table->string('three_way_match_status', 30)->default('DIRECT')->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('purchases', 'goods_receipt_id')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->dropForeign(['goods_receipt_id']);
                $table->dropColumn(['goods_receipt_id', 'three_way_match_status']);
            });
        }

        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
