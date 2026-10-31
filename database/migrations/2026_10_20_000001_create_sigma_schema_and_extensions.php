<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Dedicated schema for SIGMA ERP as per AGENTS.md Section 1
            DB::statement('CREATE SCHEMA IF NOT EXISTS sigma;');
            // Trigram extension for fast POS search as per AGENTS.md Section 3
            DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm";');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Extension and schema are generally preserved in PostgreSQL environments
    }
};
