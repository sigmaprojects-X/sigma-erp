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
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('cashier_default_starting_cash', 15, 2)->default(200000.00)->after('receipt_print_copies');
            $table->decimal('cashier_max_starting_cash', 15, 2)->default(2000000.00)->after('cashier_default_starting_cash');
            $table->boolean('cashier_require_starting_cash')->default(true)->after('cashier_max_starting_cash');
            $table->decimal('cashier_max_change_limit', 15, 2)->default(500000.00)->after('cashier_require_starting_cash');
            $table->decimal('cashier_cash_drop_threshold', 15, 2)->default(2000000.00)->after('cashier_max_change_limit');
            $table->boolean('cashier_cash_drop_warning_enabled')->default(true)->after('cashier_cash_drop_threshold');
            $table->decimal('cashier_max_discrepancy_tolerance', 15, 2)->default(2000.00)->after('cashier_cash_drop_warning_enabled');
            $table->boolean('cashier_allow_pending_transaction')->default(true)->after('cashier_max_discrepancy_tolerance');
            $table->smallInteger('cashier_max_pending_transactions')->default(5)->after('cashier_allow_pending_transaction');
            $table->string('cashier_quick_cash_nominal', 100)->default('10000,20000,50000,100000')->after('cashier_max_pending_transactions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'cashier_default_starting_cash',
                'cashier_max_starting_cash',
                'cashier_require_starting_cash',
                'cashier_max_change_limit',
                'cashier_cash_drop_threshold',
                'cashier_cash_drop_warning_enabled',
                'cashier_max_discrepancy_tolerance',
                'cashier_allow_pending_transaction',
                'cashier_max_pending_transactions',
                'cashier_quick_cash_nominal',
            ]);
        });
    }
};
