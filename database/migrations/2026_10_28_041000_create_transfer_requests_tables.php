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
        Schema::create('transfer_requests', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('request_number', 50)->unique(); // TR-YYYYMMDD-XXXX

            // Toko peminta & toko tujuan (biasanya Gudang Pusat)
            $table->foreignUuid('requesting_store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignUuid('destination_store_id')->constrained('stores')->cascadeOnDelete();

            $table->date('request_date');
            // REQUESTED, APPROVED, PARTIAL, REJECTED, FULFILLED, CANCELLED
            $table->string('status', 30)->default('REQUESTED')->index();

            $table->integer('total_items')->default(0);
            $table->decimal('total_requested_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_approved_quantity', 15, 4)->default(0.0000);
            $table->decimal('total_fulfilled_quantity', 15, 4)->default(0.0000);

            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            // Link ke stock_transfers jika sudah digenerate jadi Surat Jalan Transfer
            $table->foreignUuid('stock_transfer_id')->nullable()->constrained('stock_transfers')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'status', 'request_date']);
            $table->index(['requesting_store_id', 'status']);
            $table->index(['destination_store_id', 'status']);
        });

        Schema::create('transfer_request_items', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('transfer_request_id')->constrained('transfer_requests')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('product_unit_id')->constrained('product_units')->cascadeOnDelete();

            $table->decimal('requested_quantity', 15, 4)->default(1.0000);
            $table->decimal('approved_quantity', 15, 4)->default(0.0000);
            $table->decimal('fulfilled_quantity', 15, 4)->default(0.0000);

            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index(['transfer_request_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_request_items');
        Schema::dropIfExists('transfer_requests');
    }
};
