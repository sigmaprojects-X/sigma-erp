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
        // Taxation Flexible Toggle: Non-PKP vs PKP with effective dates (AGENTS.md Section 5)
        Schema::create('tax_configurations', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->boolean('is_tax_enabled')->default(false); // false = Mode Non-PKP (No VAT), true = Mode PKP (VAT aktif)
            $table->string('tax_name', 50)->default('PPN');
            $table->decimal('tax_rate_percent', 5, 2)->default(11.00); // Tarif Pajak PPN (misal 11% atau 12%)
            $table->boolean('is_tax_inclusive')->default(false); // false = PPN ditambahkan di luar, true = harga sudah include PPN
            $table->date('effective_from')->index();
            $table->date('effective_to')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'is_tax_enabled']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_configurations');
    }
};
