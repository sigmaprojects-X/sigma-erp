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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->string('transaction_number', 50)->unique(); // KM-YYYYMMDD-XXXX / KK-YYYYMMDD-XXXX / KT-YYYYMMDD-XXXX
            $table->string('type', 20)->index(); // CASH_IN, CASH_OUT, CASH_TRANSFER
            $table->date('transaction_date')->index();

            // Akun Kas / Bank
            $table->foreignUuid('source_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->foreignUuid('destination_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            // Akun Kategori Lawan (Pendapatan untuk CASH_IN, Beban untuk CASH_OUT)
            $table->foreignUuid('category_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            $table->decimal('amount', 18, 2)->default(0.00);
            $table->decimal('admin_fee', 18, 2)->default(0.00);
            $table->foreignUuid('admin_fee_account_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();

            $table->string('contact_person', 150)->nullable(); // Diterima Dari / Dibayar Kepada
            $table->text('notes')->nullable();

            $table->foreignUuid('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'type', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
