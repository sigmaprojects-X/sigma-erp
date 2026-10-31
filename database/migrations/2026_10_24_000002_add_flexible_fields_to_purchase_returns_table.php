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
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->uuid('purchase_id')->nullable()->change();
            $table->string('return_type', 30)->default('INVOICE_BASED')->after('store_id')->index();
            $table->uuid('approved_by')->nullable()->after('created_by')->index();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('notes')->nullable()->after('reason');
        });

        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->uuid('purchase_item_id')->nullable()->change();
            $table->string('condition', 30)->default('GOOD')->after('subtotal');
            $table->string('notes', 255)->nullable()->after('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropColumn(['condition', 'notes']);
        });

        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn(['return_type', 'approved_by', 'approved_at', 'notes']);
        });
    }
};
