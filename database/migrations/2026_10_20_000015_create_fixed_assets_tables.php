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
        // 1. Fixed Assets (Aset Tetap & Penyusutan Garis Lurus - AGENTS.md Section 5)
        Schema::create('fixed_assets', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->string('asset_code', 50)->index(); // Kode Aset (misal: AST-2026-001)
            $table->string('name', 150);
            $table->string('category', 50); // EQUIPMENT, VEHICLE, BUILDING, FURNITURE
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 18, 2); // Harga Perolehan Aset
            $table->decimal('salvage_value', 18, 2)->default(0.00); // Nilai Residu
            $table->integer('useful_life_months'); // Masa Manfaat (Bulan)
            $table->decimal('monthly_depreciation_amount', 18, 2); // Beban Penyusutan Bulanan (Straight-Line)
            $table->decimal('accumulated_depreciation', 18, 2)->default(0.00); // Total Akumulasi Penyusutan
            $table->decimal('book_value', 18, 2); // Nilai Buku (Acquisition Cost - Accumulated Depreciation)
            $table->date('last_depreciation_date')->nullable();
            $table->string('status', 20)->default('ACTIVE')->index(); // ACTIVE, FULLY_DEPRECIATED, DISPOSED

            $table->timestamps();

            $table->unique(['company_id', 'asset_code']);
        });

        // 2. Asset Depreciation Records (Riwayat Periodik Penyusutan Bulanan)
        Schema::create('asset_depreciation_records', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignUuid('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->date('period_date')->index(); // Tanggal Periode (misal: akhir bulan 2026-10-31)
            $table->decimal('amount', 18, 2); // Nilai penyusutan bulan ini
            $table->decimal('book_value_after', 18, 2); // Sisa nilai buku setelah penyusutan
            $table->timestamps();

            $table->unique(['fixed_asset_id', 'period_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_records');
        Schema::dropIfExists('fixed_assets');
    }
};
