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
        Schema::create('companies', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('tax_id', 50)->nullable(); // NPWP / NIK Perusahaan
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->string('currency_code', 10)->default('IDR');
            $table->boolean('is_tax_registered')->default(false); // Mode Non-PKP vs PKP (AGENTS.md Section 5)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
