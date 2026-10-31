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
        // Tutup duplikat shift OPEN (jika ada) kecuali yang paling baru per kasir & toko
        DB::statement("
            UPDATE cashier_shifts cs
            SET status = 'CLOSED',
                closed_at = COALESCE(closed_at, NOW())
            WHERE cs.status = 'OPEN'
              AND EXISTS (
                  SELECT 1
                  FROM cashier_shifts newer
                  WHERE newer.store_id = cs.store_id
                    AND newer.cashier_id = cs.cashier_id
                    AND newer.status = 'OPEN'
                    AND (
                        newer.opened_at > cs.opened_at
                        OR (newer.opened_at = cs.opened_at AND newer.id > cs.id)
                    )
              )
        ");

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS cashier_shifts_one_open_per_cashier_unique
            ON cashier_shifts (store_id, cashier_id)
            WHERE status = 'OPEN'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS cashier_shifts_one_open_per_cashier_unique');
    }
};
