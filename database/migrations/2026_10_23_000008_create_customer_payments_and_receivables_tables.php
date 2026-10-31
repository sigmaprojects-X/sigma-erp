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
        // 1. Tambah kolom piutang pada pos_transactions jika belum ada
        Schema::table('pos_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_transactions', 'due_date')) {
                $table->date('due_date')->nullable()->after('credit_amount')->index();
            }
            if (! Schema::hasColumn('pos_transactions', 'payment_status')) {
                $table->string('payment_status', 20)->default('PAID')->after('status')->index(); // PAID, UNPAID, PARTIALLY_PAID
            }
            if (! Schema::hasColumn('pos_transactions', 'remaining_receivable')) {
                $table->decimal('remaining_receivable', 15, 2)->default(0.00)->after('credit_amount');
            }
        });

        // 2. Buat tabel customer_payments (Penerimaan Pembayaran / Pelunasan Piutang Pelanggan)
        Schema::create('customer_payments', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('payment_number', 50)->unique();
            $table->date('payment_date');
            $table->string('payment_method', 20); // CASH, TRANSFER
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->uuid('received_by')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'store_id', 'payment_date']);
            $table->index(['customer_id', 'payment_date']);
        });

        // 3. Buat tabel customer_payment_items (Alokasi Pembayaran ke Faktur Penjualan)
        Schema::create('customer_payment_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('customer_payment_id')->constrained('customer_payments')->cascadeOnDelete();
            $table->foreignUuid('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);

            $table->timestamps();

            $table->index(['customer_payment_id', 'pos_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_items');
        Schema::dropIfExists('customer_payments');

        Schema::table('pos_transactions', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('pos_transactions', 'due_date')) {
                $cols[] = 'due_date';
            }
            if (Schema::hasColumn('pos_transactions', 'payment_status')) {
                $cols[] = 'payment_status';
            }
            if (Schema::hasColumn('pos_transactions', 'remaining_receivable')) {
                $cols[] = 'remaining_receivable';
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
