<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah Kolom Saldo Poin ke Tabel Customers
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('loyalty_points')->default(0)->after('credit_term_days');
            $table->integer('lifetime_points')->default(0)->after('loyalty_points');
        });

        // 2. Tambah Kolom Pelacakan Poin ke Tabel Transaksi POS
        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->after('discount_amount');
            $table->integer('points_redeemed')->default(0)->after('points_earned');
            $table->decimal('points_discount_amount', 15, 2)->default(0.00)->after('points_redeemed');
        });

        // 3. Tabel Konfigurasi Aturan Program Loyalitas (Loyalty Program Rules)
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 100)->default('Program Poin Pelanggan');
            $table->decimal('spend_amount_per_point', 15, 2)->default(10000.00); // Belanja per Rp 10.000 = 1 poin
            $table->decimal('point_redemption_value', 15, 2)->default(100.00); // 1 poin bernilai Rp 100 diskon
            $table->integer('min_points_to_redeem')->default(10); // Minimal tukar 10 poin
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });

        // 4. Tabel Buku Besar Mutasi Poin Pelanggan (Point Ledger)
        Schema::create('customer_point_mutations', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('pos_transaction_id')->nullable()->constrained('pos_transactions')->nullOnDelete();

            $table->string('type', 20); // EARN, REDEEM, ADJUSTMENT, EXPIRED
            $table->integer('points'); // Kuantitas poin (+ atau -)
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->string('description', 255);
            $table->uuid('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
            $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_point_mutations');
        Schema::dropIfExists('loyalty_programs');

        Schema::table('pos_transactions', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_redeemed', 'points_discount_amount']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points', 'lifetime_points']);
        });
    }
};
