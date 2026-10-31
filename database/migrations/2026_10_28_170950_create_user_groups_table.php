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
        if (!Schema::hasTable('user_groups')) {
            Schema::create('user_groups', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('code', 50);
                $table->string('name', 100);
                $table->string('description', 255)->nullable();
                $table->json('permissions')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();

                $table->unique(['company_id', 'code']);
            });
        }

        if (!Schema::hasColumn('users', 'user_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUuid('user_group_id')->nullable()->constrained('user_groups')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'user_group_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['user_group_id']);
                $table->dropColumn('user_group_id');
            });
        }

        Schema::dropIfExists('user_groups');
    }
};
