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
        // 1. Chart of Accounts (COA / Daftar Akun Buku Besar)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 50)->index(); // Misal: 1001-001 (Kas Toko)
            $table->string('name', 150);
            $table->string('type', 30); // ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE
            $table->string('normal_balance', 10); // DEBIT, CREDIT
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // 2. Journal Entries (Header Jurnal Umum Double-Entry)
        Schema::create('journal_entries', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();

            $table->string('entry_number', 50)->unique(); // Misal: JRN-20261020-0001
            $table->date('entry_date')->index();
            $table->string('reference_type', 50)->index(); // POS_SALE, GOODS_RECEIPT, VOUCHER_REDEMPTION, ASSET_DEPRECIATION, etc.
            $table->uuid('reference_id')->nullable()->index();
            $table->string('reference_number', 100)->nullable()->index(); // Nomor Faktur / No PO
            $table->text('description');

            $table->decimal('total_debit', 18, 2)->default(0.00);
            $table->decimal('total_credit', 18, 2)->default(0.00);
            $table->boolean('is_balanced')->default(true);
            $table->uuid('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
        });

        // Enforce Double-Entry Balancing at Database Level (AGENTS.md Section 5)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE journal_entries ADD CONSTRAINT chk_journal_entries_balanced CHECK (total_debit = total_credit);');
        }

        // 3. Journal Entry Lines (Detail Baris Debit & Kredit Jurnal)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $pk = $table->uuid('id')->primary();
            if (DB::getDriverName() === 'pgsql') {
                $pk->default(DB::raw('gen_random_uuid()'));
            }

            $table->foreignUuid('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignUuid('account_id')->constrained('chart_of_accounts')->cascadeOnDelete();

            $table->decimal('debit', 18, 2)->default(0.00);
            $table->decimal('credit', 18, 2)->default(0.00);
            $table->string('memo', 255)->nullable();

            $table->timestamps();

            $table->index(['journal_entry_id', 'account_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE journal_entry_lines ADD CONSTRAINT chk_journal_lines_non_negative CHECK (debit >= 0 AND credit >= 0);');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
