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
            if (!Schema::hasColumn('companies', 'whatsapp')) {
                $table->string('whatsapp', 50)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('companies', 'website')) {
                $table->string('website', 150)->nullable()->after('email');
            }
            if (!Schema::hasColumn('companies', 'tax_name')) {
                $table->string('tax_name', 150)->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('companies', 'tax_address')) {
                $table->text('tax_address')->nullable()->after('tax_name');
            }
            if (!Schema::hasColumn('companies', 'default_tax_rate')) {
                $table->decimal('default_tax_rate', 5, 2)->default(11.00)->after('is_tax_registered');
            }
            if (!Schema::hasColumn('companies', 'tax_mode')) {
                $table->string('tax_mode', 20)->default('INCLUSIVE')->after('default_tax_rate'); // INCLUSIVE, EXCLUSIVE, NON_TAX
            }
        });

        Schema::table('stores', function (Blueprint $table) {
            if (!Schema::hasColumn('stores', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('stores', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('city');
            }
            if (!Schema::hasColumn('stores', 'pic_name')) {
                $table->string('pic_name', 100)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('stores', 'is_main_store')) {
                $table->boolean('is_main_store')->default(false)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp',
                'website',
                'tax_name',
                'tax_address',
                'default_tax_rate',
                'tax_mode',
            ]);
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'postal_code',
                'pic_name',
                'is_main_store',
            ]);
        });
    }
};
