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
        Schema::create('customers', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('customer_tier_id')->constrained('customer_tiers')->cascadeOnDelete();

            $table->string('code', 50); // CUST-UMUM, CUST-001, etc.
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();

            // Fasilitas Kredit & Tempo (AGENTS.md Section 4A)
            $table->decimal('credit_limit', 15, 2)->default(0.00); // Plafon Kredit / Limit Piutang
            $table->decimal('current_credit_balance', 15, 2)->default(0.00); // Saldo Piutang Berjalan
            $table->integer('credit_term_days')->default(0); // Jatuh Tempo (e.g. 14 hari, 30 hari)

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'customer_tier_id']);
            $table->index(['company_id', 'name']);
        });

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->foreignUuid('customer_id')->nullable()->after('customer_tier_id')->constrained('customers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });

        Schema::dropIfExists('customers');
    }
};
