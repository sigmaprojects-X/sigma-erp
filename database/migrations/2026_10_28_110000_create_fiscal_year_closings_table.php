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
        Schema::create('fiscal_year_closings', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->integer('fiscal_year');
            $table->date('closing_date');
            $table->decimal('total_revenue', 15, 2)->default(0.00);
            $table->decimal('total_expense', 15, 2)->default(0.00);
            $table->decimal('net_profit', 15, 2)->default(0.00);
            $table->foreignUuid('retained_earnings_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->foreignUuid('closing_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignUuid('closed_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('CLOSED'); // CLOSED, LOCKED
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'fiscal_year'], 'uk_fiscal_year_company');
            $table->index(['company_id', 'status'], 'idx_fiscal_year_company_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_year_closings');
    }
};
