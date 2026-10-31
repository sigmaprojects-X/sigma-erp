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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('payment_number', 50)->unique(); // PAY-YYYYMMDD-XXXX
            $table->date('payment_date');
            $table->string('payment_method', 20)->default('TRANSFER'); // CASH, TRANSFER
            $table->foreignUuid('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();

            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('reference_number', 100)->nullable(); // No bukti transfer bank
            $table->text('notes')->nullable();

            $table->uuid('created_by')->index();
            $table->timestamps();

            $table->index(['company_id', 'payment_date']);
            $table->index(['supplier_id', 'payment_date']);
        });

        Schema::create('supplier_payment_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('supplier_payment_id')->constrained('supplier_payments')->cascadeOnDelete();
            $table->foreignUuid('purchase_id')->constrained('purchases')->cascadeOnDelete();

            $table->decimal('amount_paid', 15, 2);
            $table->timestamps();

            $table->index(['supplier_payment_id', 'purchase_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_items');
        Schema::dropIfExists('supplier_payments');
    }
};
