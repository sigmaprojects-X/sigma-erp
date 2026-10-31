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
        if (!Schema::hasColumn('companies', 'rounding_enabled')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->boolean('rounding_enabled')->default(false);
                $table->integer('rounding_step')->default(100);
                $table->string('rounding_mode', 30)->default('ROUND_NEAREST');
                $table->string('rounding_applies_to', 30)->default('CASH_ONLY');
            });
        }

        if (!Schema::hasColumn('pos_transactions', 'rounding_amount')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                $table->decimal('rounding_amount', 15, 2)->nullable()->default(0.00);
                $table->decimal('split_cash_amount', 15, 2)->nullable()->default(0.00);
                $table->decimal('split_non_cash_amount', 15, 2)->nullable()->default(0.00);
                $table->string('split_non_cash_method', 30)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pos_transactions', 'rounding_amount')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                $table->dropColumn([
                    'rounding_amount',
                    'split_cash_amount',
                    'split_non_cash_amount',
                    'split_non_cash_method',
                ]);
            });
        }

        if (Schema::hasColumn('companies', 'rounding_enabled')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn([
                    'rounding_enabled',
                    'rounding_step',
                    'rounding_mode',
                    'rounding_applies_to',
                ]);
            });
        }
    }
};
