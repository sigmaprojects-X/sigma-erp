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
        Schema::create('purchases', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('invoice_number', 100); // Nomor Faktur Asli dari Supplier
            $table->string('reference_number', 50)->unique(); // Nomor Bukti Pembelian Internal (FB-YYYYMMDD-XXXX)
            $table->date('purchase_date');
            $table->date('due_date')->nullable(); // Tanggal Jatuh Tempo (untuk pembayaran TEMPO)
            $table->string('payment_type', 20)->default('CASH'); // CASH, TRANSFER, TEMPO
            $table->foreignUuid('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();

            $table->string('status', 20)->default('RECEIVED')->index(); // RECEIVED, DRAFT, CANCELLED
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['company_id', 'purchase_date']);
            $table->index(['store_id', 'purchase_date']);
            $table->index(['supplier_id', 'purchase_date']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->string('unit_code', 20); // DUS, PACK, PCS
            $table->decimal('conversion_factor', 12, 4)->default(1.0000); // 40
            $table->decimal('quantity', 12, 2); // 1.00
            $table->decimal('base_quantity', 15, 4); // 40.0000

            $table->decimal('unit_price', 15, 2); // Harga Beli per Unit Kemasan (misal 128.000)
            $table->decimal('base_unit_price', 15, 4); // Harga Beli per Satuan Dasar (misal 3.200)
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2); // 128.000

            // Rekam audit HPP Moving Average
            $table->decimal('old_cost_price', 15, 4)->default(0.0000); // HPP sebelum beli (3.000)
            $table->decimal('new_cost_price', 15, 4)->default(0.0000); // HPP setelah moving average (3.160)

            // Audit Opsi B: Kenaikan Harga Beli & Penyesuaian Harga Jual
            $table->boolean('is_price_increased')->default(false);
            $table->boolean('is_selling_price_updated')->default(false);
            $table->decimal('old_selling_price', 15, 2)->nullable();
            $table->decimal('new_selling_price', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};
