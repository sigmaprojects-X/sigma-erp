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
        // 1. Tambah kolom deposit_balance pada tabel customers
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'deposit_balance')) {
                $table->decimal('deposit_balance', 15, 2)->default(0.00)->after('current_credit_balance');
            }
        });

        // 2. Tambah kolom deposit_balance pada tabel suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'deposit_balance')) {
                $table->decimal('deposit_balance', 15, 2)->default(0.00)->after('is_active');
            }
        });

        // 3. Tabel Mutasi Deposito Pelanggan
        Schema::create('customer_deposits', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();

            $table->string('deposit_number', 50); // e.g. DEP-CUST-20261028-0001
            $table->enum('type', ['TOP_UP', 'WITHDRAWAL', 'USAGE', 'REFUND'])->default('TOP_UP');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2)->default(0.00);
            $table->decimal('balance_after', 15, 2)->default(0.00);

            $table->string('payment_method', 30)->default('CASH'); // CASH, BANK_TRANSFER, QRIS
            $table->foreignUuid('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignUuid('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete(); // Akun Kas/Bank
            $table->foreignUuid('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->string('reference_type', 50)->nullable(); // POS_TRANSACTION, CUSTOMER_RECEIVABLE, MANUAL
            $table->uuid('reference_id')->nullable();
            $table->string('reference_number', 100)->nullable();

            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['company_id', 'deposit_number']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'transaction_date']);
        });

        // 4. Tabel Mutasi Deposito Supplier (Uang Muka Pembelian)
        Schema::create('supplier_deposits', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('deposit_number', 50); // e.g. DEP-SUP-20261028-0001
            $table->enum('type', ['TOP_UP', 'WITHDRAWAL', 'USAGE', 'REFUND'])->default('TOP_UP');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2)->default(0.00);
            $table->decimal('balance_after', 15, 2)->default(0.00);

            $table->string('payment_method', 30)->default('BANK_TRANSFER'); // CASH, BANK_TRANSFER
            $table->foreignUuid('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignUuid('account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete(); // Akun Kas/Bank
            $table->foreignUuid('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->string('reference_type', 50)->nullable(); // PURCHASE, AP_SETTLEMENT, MANUAL
            $table->uuid('reference_id')->nullable();
            $table->string('reference_number', 100)->nullable();

            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['company_id', 'deposit_number']);
            $table->index(['company_id', 'supplier_id']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_deposits');
        Schema::dropIfExists('customer_deposits');

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'deposit_balance')) {
                $table->dropColumn('deposit_balance');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'deposit_balance')) {
                $table->dropColumn('deposit_balance');
            }
        });
    }
};
