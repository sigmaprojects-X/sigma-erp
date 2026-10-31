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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('cashier')->after('email')->index();
            $table->foreignUuid('store_id')->nullable()->after('role')->constrained('stores')->nullOnDelete();
            $table->string('manager_pin', 255)->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('manager_pin');
            $table->date('valid_from')->nullable()->after('is_active');
            $table->date('valid_until')->nullable()->after('valid_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn([
                'role',
                'store_id',
                'manager_pin',
                'is_active',
                'valid_from',
                'valid_until',
            ]);
        });
    }
};
