<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_number_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('document_type', 30)->index(); // POS, SO, SI, SR, PO, GRN, PR, OPN, TR, ADJ, IN, OUT, JRN, CAS
            $table->string('module', 30)->default('penjualan'); // penjualan, pembelian, persediaan, akuntansi
            $table->string('name', 100);
            $table->string('prefix', 30);
            $table->string('pattern', 100)->default('{PREFIX}-{Y}{m}-{SEQ}');
            $table->smallInteger('digits')->default(4); // e.g. 4 -> 0001
            $table->string('reset_cycle', 20)->default('MONTHLY'); // MONTHLY, YEARLY, CONTINUOUS
            $table->unsignedInteger('current_sequence')->default(0);
            $table->string('last_period', 20)->nullable(); // e.g. '202610' or '2026'
            $table->boolean('is_locked')->default(true); // kasir/staf tidak boleh ubah nomor
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_number_patterns');
    }
};
