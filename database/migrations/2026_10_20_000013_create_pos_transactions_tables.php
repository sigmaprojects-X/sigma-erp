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
        Schema::create('pos_transactions', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('cashier_shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->foreignUuid('customer_tier_id')->constrained('customer_tiers')->cascadeOnDelete();
            $table->uuid('cashier_id')->index();

            $table->string('invoice_number', 50)->unique();
            $table->integer('total_item_count')->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);

            // Voucher & Gift Card (AGENTS.md Section 3)
            $table->foreignUuid('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->decimal('voucher_discount', 15, 2)->default(0.00);

            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2);

            // Metode Pembayaran (CASH, QRIS, TRANSFER, VOUCHER, TEMPO / KREDIT - AGENTS.md Section 4A)
            $table->string('payment_method', 20)->index();
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('change_amount', 15, 2)->default(0.00);

            $table->string('status', 20)->default('COMPLETED')->index(); // COMPLETED, VOID

            // Fitur Audit Struk Reprint (AGENTS.md Section 4B)
            $table->integer('reprint_count')->default(0);
            $table->timestamp('last_reprinted_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['cashier_shift_id', 'created_at']);
        });

        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->decimal('quantity', 12, 2); // Kuantitas dalam kemasan yang dibeli
            $table->decimal('base_quantity', 15, 4); // Kuantitas terkonversi ke satuan dasar (untuk mutasi stok)
            $table->decimal('unit_cost', 15, 4)->default(0.0000); // HPP Moving Average saat transaksi terjadi
            $table->decimal('unit_price', 15, 2); // Harga 3D dari mesin harga otomatis
            $table->decimal('subtotal', 15, 2); // quantity * unit_price
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('total_price', 15, 2);

            $table->timestamps();

            $table->index(['pos_transaction_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_items');
        Schema::dropIfExists('pos_transactions');
    }
};
