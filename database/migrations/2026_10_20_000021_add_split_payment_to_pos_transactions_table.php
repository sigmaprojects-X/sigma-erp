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
            $table->decimal('credit_amount', 15, 2)->default(0.00)->after('paid_amount');
            $table->string('split_payment_method', 20)->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn(['credit_amount', 'split_payment_method']);
        });
    }
};
