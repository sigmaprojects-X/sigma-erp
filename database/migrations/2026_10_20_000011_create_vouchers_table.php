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
        Schema::create('vouchers', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->string('code', 50)->index(); // Kode unik voucher / barcode (AGENTS.md Section 3)
            $table->string('type', 20)->default('VALUE'); // VALUE (Nominal Potongan), DISCOUNT_PERCENT (% Diskon)
            $table->decimal('amount', 15, 2); // Nominal Rp atau Persen diskon
            $table->decimal('min_spend', 15, 2)->default(0.00); // Syarat minimal belanja

            // Status voucher: ISSUED, REDEEMED, EXPIRED (Single-use tracking - AGENTS.md Section 3)
            $table->string('status', 20)->default('ISSUED')->index();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->timestamp('redeemed_at')->nullable();
            $table->uuid('redeemed_in_transaction_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
