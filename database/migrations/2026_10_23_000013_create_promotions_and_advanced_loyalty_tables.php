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
        // 1. Tambah Multiplier Poin ke Customer Tiers
        Schema::table('customer_tiers', function (Blueprint $table) {
            $table->decimal('point_multiplier', 5, 2)->default(1.00)->after('description');
        });

        // 2. Tambah Expiry Days ke Loyalty Programs & Expired At ke Customer Point Mutations
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->integer('points_expiry_days')->default(365)->after('min_points_to_redeem'); // Poin berlaku 1 tahun
            $table->boolean('allow_merchandise_redemption')->default(true)->after('points_expiry_days');
        });

        Schema::table('customer_point_mutations', function (Blueprint $table) {
            $table->date('expired_at')->nullable()->after('balance_after');
            $table->boolean('is_expired')->default(false)->after('expired_at');
        });

        // 3. Tabel Katalog Hadiah / Merchandise Penukaran Poin (Loyalty Rewards)
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('points_required'); // Jumlah poin yang harus ditukar
            $table->decimal('stock_quantity', 12, 2)->default(0.00); // Stok fisik hadiah
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });

        // 4. Tabel Riwayat Penukaran Hadiah Merchandise (Reward Redemptions)
        Schema::create('loyalty_reward_redemptions', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('loyalty_reward_id')->constrained('loyalty_rewards')->cascadeOnDelete();

            $table->string('redemption_number', 50)->unique();
            $table->integer('points_used');
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->date('redemption_date');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();

            $table->timestamps();
            $table->index(['customer_id', 'redemption_date']);
        });

        // 5. Tabel Mesin Promo (Promotions Engine: Diskon, Buy X Get Y, Tebus Murah)
        Schema::create('promotions', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->string('code', 50)->nullable()->index();
            $table->string('name', 150);
            $table->string('type', 30); // DISCOUNT_PERCENT, DISCOUNT_NOMINAL, BUY_X_GET_Y, TEBUS_MURAH
            $table->text('description')->nullable();

            // Aturan Buy X Get Y & Tebus Murah (PWP)
            $table->foreignUuid('buy_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('buy_quantity', 12, 2)->default(1.00);
            $table->foreignUuid('reward_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('reward_quantity', 12, 2)->default(1.00);
            $table->decimal('special_price', 15, 2)->nullable(); // Harga tebus murah (misal Rp 5.000)

            // Aturan Diskon Biasa
            $table->decimal('discount_percent', 5, 2)->default(0.00);
            $table->decimal('discount_nominal', 15, 2)->default(0.00);
            $table->decimal('min_spend', 15, 2)->default(0.00); // Syarat minimal belanja (PWP)
            $table->integer('max_usage_per_transaction')->default(1);

            // Waktu Aktif Promo
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->json('days_of_week')->nullable(); // e.g. ["FRI","SAT","SUN"]
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->index(['company_id', 'is_active', 'start_date', 'end_date']);
        });

        // 6. Perluas Tabel Vouchers ala Minimarket (Indomaret Barcode & Receipt Voucher)
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('barcode', 50)->nullable()->after('code')->index();
            $table->string('title', 150)->nullable()->after('barcode');
            $table->boolean('is_printed_on_receipt')->default(false)->after('min_spend');
            $table->decimal('trigger_min_spend', 15, 2)->default(0.00)->after('is_printed_on_receipt'); // Syarat belanja di struk sebelumnya
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'title', 'is_printed_on_receipt', 'trigger_min_spend']);
        });

        Schema::dropIfExists('promotions');
        Schema::dropIfExists('loyalty_reward_redemptions');
        Schema::dropIfExists('loyalty_rewards');

        Schema::table('customer_point_mutations', function (Blueprint $table) {
            $table->dropColumn(['expired_at', 'is_expired']);
        });

        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn(['points_expiry_days', 'allow_merchandise_redemption']);
        });

        Schema::table('customer_tiers', function (Blueprint $table) {
            $table->dropColumn(['point_multiplier']);
        });
    }
};
