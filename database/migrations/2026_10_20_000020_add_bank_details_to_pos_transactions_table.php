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
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->string('bank_name', 50)->nullable()->after('payment_method');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('payment_reference', 100)->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'payment_reference']);
        });
    }
};
