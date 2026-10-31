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
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 30)->default('BARANG')->after('sku'); // BARANG, JASA, RAKITAN_PROSES, RAKITAN_NON_PROSES, NON_INVENTORY, BIAYA, VARIAN
            $table->foreignUuid('supplier_id')->nullable()->after('category_id')->constrained('suppliers')->nullOnDelete();
            $table->string('sub_category', 100)->nullable()->after('category');
            $table->string('rack_location', 100)->nullable()->after('brand');
            $table->string('sale_status', 30)->default('ACTIVE')->after('rack_location'); // ACTIVE, DISCONTINUED, BUY_ONLY
            $table->text('notes')->nullable()->after('description');
            $table->string('tax_type', 30)->default('NON')->after('sale_status'); // NON, PPN, PPN_MEWAH
            $table->string('tax_reference_code', 100)->nullable()->after('tax_type'); // Kode DJP e-Faktur
            $table->string('image_path', 255)->nullable()->after('notes');
            $table->json('additional_images')->nullable()->after('image_path');
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->decimal('weight_gram', 12, 2)->default(0.00)->after('conversion_factor');
            $table->decimal('length_cm', 12, 2)->default(0.00)->after('weight_gram');
            $table->decimal('width_cm', 12, 2)->default(0.00)->after('length_cm');
            $table->decimal('height_cm', 12, 2)->default(0.00)->after('width_cm');
            $table->decimal('point_reward', 10, 2)->default(0.00)->after('height_cm');
            $table->decimal('commission_rate', 15, 2)->default(0.00)->after('point_reward');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->decimal('discount_1', 5, 2)->default(0.00)->after('margin_percentage');
            $table->decimal('discount_2', 5, 2)->default(0.00)->after('discount_1');
            $table->decimal('discount_3', 5, 2)->default(0.00)->after('discount_2');
            $table->decimal('discount_4', 5, 2)->default(0.00)->after('discount_3');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropColumn(['discount_1', 'discount_2', 'discount_3', 'discount_4']);
        });

        Schema::table('product_units', function (Blueprint $table) {
            $table->dropColumn(['weight_gram', 'length_cm', 'width_cm', 'height_cm', 'point_reward', 'commission_rate']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn([
                'type',
                'sub_category',
                'rack_location',
                'sale_status',
                'notes',
                'tax_type',
                'tax_reference_code',
                'image_path',
                'additional_images',
            ]);
        });
    }
};
