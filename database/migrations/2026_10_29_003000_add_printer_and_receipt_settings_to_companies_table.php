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
            $table->string('receipt_paper_size', 10)->default('58')->after('receipt_footer');
            $table->string('receipt_font_family', 30)->default('monospace')->after('receipt_paper_size');
            $table->string('receipt_font_size', 20)->default('normal')->after('receipt_font_family');
            $table->string('receipt_item_layout', 30)->default('compact_double')->after('receipt_font_size');
            $table->boolean('receipt_show_logo')->default(true)->after('receipt_item_layout');
            $table->string('receipt_header_line1', 255)->nullable()->after('receipt_show_logo');
            $table->string('receipt_header_line2', 255)->nullable()->after('receipt_header_line1');
            $table->text('receipt_footer_line1')->nullable()->after('receipt_header_line2');
            $table->text('receipt_footer_line2')->nullable()->after('receipt_footer_line1');
            $table->boolean('receipt_show_customer')->default(true)->after('receipt_footer_line2');
            $table->boolean('receipt_show_cashier')->default(true)->after('receipt_show_customer');
            $table->boolean('receipt_show_barcode')->default(true)->after('receipt_show_cashier');
            $table->boolean('receipt_auto_print')->default(true)->after('receipt_show_barcode');
            $table->boolean('receipt_auto_cut')->default(true)->after('receipt_auto_print');
            $table->boolean('receipt_open_drawer')->default(true)->after('receipt_auto_cut');
            $table->smallInteger('receipt_print_copies')->default(1)->after('receipt_open_drawer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_paper_size',
                'receipt_font_family',
                'receipt_font_size',
                'receipt_item_layout',
                'receipt_show_logo',
                'receipt_header_line1',
                'receipt_header_line2',
                'receipt_footer_line1',
                'receipt_footer_line2',
                'receipt_show_customer',
                'receipt_show_cashier',
                'receipt_show_barcode',
                'receipt_auto_print',
                'receipt_auto_cut',
                'receipt_open_drawer',
                'receipt_print_copies',
            ]);
        });
    }
};
