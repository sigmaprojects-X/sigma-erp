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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->uuid('cashier_id')->index();

            $table->string('shift_number', 50)->index();
            $table->decimal('starting_cash', 15, 2)->default(0.00); // Kas awal (modal kasir)
            $table->string('status', 20)->default('OPEN')->index(); // OPEN, CLOSED
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            // Blind Closing Rule (AGENTS.md Section 4A):
            // Kasir menginput uang fisik tanpa melihat ekspektasi sistem
            $table->decimal('actual_cash_counted', 15, 2)->nullable(); // Fisik hitungan kasir
            $table->decimal('expected_cash', 15, 2)->nullable(); // Dihitung otomatis oleh backend
            $table->decimal('cash_discrepancy', 15, 2)->nullable(); // Selisih (+ Over, - Short)
            $table->boolean('discrepancy_resolved')->default(false);
            $table->uuid('resolved_by')->nullable(); // Manager yang menyelesaikan selisih
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'status']);
        });

        Schema::create('cash_drops', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('cashier_shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->decimal('amount', 15, 2); // Jumlah uang kas yang disetor ke brankas di tengah shift
            $table->string('reason', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_drops');
        Schema::dropIfExists('cashier_shifts');
    }
};
