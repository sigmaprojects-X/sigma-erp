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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('return_number', 50)->unique(); // RBP-YYYYMMDD-XXXX
            $table->date('return_date');
            $table->string('refund_type', 20)->default('DEBT_REDUCTION'); // DEBT_REDUCTION (potong hutang), CASH, TRANSFER

            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);

            $table->string('reason', 255)->nullable();
            $table->string('status', 20)->default('COMPLETED')->index(); // COMPLETED, CANCELLED
            $table->uuid('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(['supplier_id', 'return_date']);
            $table->index(['purchase_id']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignUuid('purchase_item_id')->constrained('purchase_items')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->decimal('quantity', 12, 2);
            $table->decimal('base_quantity', 15, 4);
            $table->decimal('unit_price', 15, 2); // Harga beli saat faktur asal
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();

            $table->index(['purchase_return_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
