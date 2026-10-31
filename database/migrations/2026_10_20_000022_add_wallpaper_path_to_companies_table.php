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
            $table->string('wallpaper_path', 255)->nullable()->after('address');
            $table->string('logo_path', 255)->nullable()->after('wallpaper_path');
            $table->text('receipt_footer')->nullable()->after('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['wallpaper_path', 'logo_path', 'receipt_footer']);
        });
    }
};
