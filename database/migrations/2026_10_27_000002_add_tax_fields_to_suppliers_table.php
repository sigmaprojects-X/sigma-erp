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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_pkp')->default(false)->after('name');
            $table->string('npwp', 50)->nullable()->after('is_pkp');
            $table->string('tax_name', 150)->nullable()->after('npwp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['is_pkp', 'npwp', 'tax_name']);
        });
    }
};
