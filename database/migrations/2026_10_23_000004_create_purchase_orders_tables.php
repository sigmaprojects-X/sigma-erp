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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('po_number', 50)->unique(); // PO-YYYYMMDD-XXXX
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            
            // DRAFT -> ORDERED -> PARTIALLY_RECEIVED -> COMPLETED / CANCELLED
            $table->string('status', 25)->default('DRAFT')->index();
            
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->uuid('created_by')->index();
            $table->uuid('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'order_date']);
            $table->index(['store_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->string('unit_code', 20);
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->decimal('quantity_ordered', 12, 2);
            $table->decimal('quantity_received', 12, 2)->default(0.00);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();

            $table->index(['purchase_order_id', 'product_id']);
        });

        // Hubungkan PO dengan Goods Receipt di tabel purchases
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignUuid('purchase_order_id')->nullable()->after('store_id')->constrained('purchase_orders')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });

        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
