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
        Schema::create('pos_returns', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignUuid('cashier_shift_id')->nullable()->constrained('cashier_shifts')->nullOnDelete();

            $table->uuid('processed_by')->index(); // User kasir/staff yang memproses
            $table->uuid('approved_by')->nullable()->index(); // Manager yang mengesahkan

            $table->string('return_number', 50)->unique(); // RET-YYYYMMDD-XXXX
            $table->date('return_date');
            $table->string('refund_method', 20)->default('CASH'); // CASH, TRANSFER, CREDIT_REDUCTION

            $table->decimal('subtotal_returned', 15, 2)->default(0.00);
            $table->decimal('tax_returned', 15, 2)->default(0.00);
            $table->decimal('total_refund_amount', 15, 2)->default(0.00);

            $table->string('reason', 255)->nullable();
            $table->string('status', 20)->default('COMPLETED')->index(); // COMPLETED, CANCELLED

            $table->timestamps();

            $table->index(['store_id', 'return_date']);
            $table->index(['pos_transaction_id']);
        });

        Schema::create('pos_return_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('pos_return_id')->constrained('pos_returns')->cascadeOnDelete();
            $table->foreignUuid('pos_transaction_item_id')->constrained('pos_transaction_items')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->decimal('quantity', 12, 2); // Jumlah kemasan yang diretur
            $table->decimal('base_quantity', 15, 4); // Jumlah terkonversi ke satuan dasar
            $table->decimal('unit_price', 15, 2); // Harga jual saat transaksi asal
            $table->decimal('subtotal', 15, 2); // quantity * unit_price (setelah proporsi diskon jika ada)
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP saat transaksi asal

            $table->timestamps();

            $table->index(['pos_return_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_return_items');
        Schema::dropIfExists('pos_returns');
    }
};
