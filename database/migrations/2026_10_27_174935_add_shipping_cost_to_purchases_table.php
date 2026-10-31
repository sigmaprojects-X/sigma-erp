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
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'shipping_cost')) {
                    $table->decimal('shipping_cost', 15, 2)->default(0.00)->after('discount_amount');
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_items', 'allocated_shipping_cost')) {
                    $table->decimal('allocated_shipping_cost', 15, 2)->default(0.00)->after('subtotal');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'shipping_cost')) {
                    $table->dropColumn('shipping_cost');
                }
            });
        }

        if (Schema::hasTable('purchase_items')) {
            Schema::table('purchase_items', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_items', 'allocated_shipping_cost')) {
                    $table->dropColumn('allocated_shipping_cost');
                }
            });
        }
    }
};
