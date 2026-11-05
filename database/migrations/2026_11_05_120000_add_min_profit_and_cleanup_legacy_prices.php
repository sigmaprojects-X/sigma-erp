<?php

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Supplier;
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
        // 1. Tambah kolom batas minimum keuntungan di tabel companies
        if (! Schema::hasColumn('companies', 'min_profit_percentage')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->decimal('min_profit_percentage', 5, 2)->default(0.00)->after('rounding_applies_to');
            });
        }

        // 2. Buat Supplier Umum di setiap perusahaan & tautkan produk yang belum punya supplier
        $companies = Company::all();
        foreach ($companies as $company) {
            $generalSupplier = Supplier::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => Supplier::GENERAL_CODE,
                ],
                [
                    'name' => Supplier::GENERAL_NAME,
                    'contact_person' => '-',
                    'phone' => '-',
                    'address' => '-',
                    'term_of_payment_days' => 0,
                    'is_active' => true,
                ]
            );

            // Update produk yang supplier_id nya null
            Product::where('company_id', $company->id)
                ->whereNull('supplier_id')
                ->update(['supplier_id' => $generalSupplier->id]);
        }

        // 3. Bersihkan harga lama yang di bawah HPP modal (seperti Rp 1, Rp 2)
        // Set is_active = false agar PricingEngine otomatis fallback ke harga Retail
        $prices = ProductPrice::with(['product', 'unit', 'customerTier'])->get();
        foreach ($prices as $price) {
            if (! $price->product) {
                continue;
            }

            $cost = (float) $price->product->cost_price;
            if ($cost <= 0) {
                continue;
            }

            $factor = $price->unit ? (float) $price->unit->conversion_factor : 1.0;
            if ($factor <= 0) {
                $factor = 1.0;
            }

            $effectiveCost = $cost * $factor;
            $sellingPrice = (float) $price->price;

            // Jika harga jual di bawah modal
            if ($sellingPrice < $effectiveCost) {
                // Nonaktifkan harga ini agar fallback ke Retail
                $price->update([
                    'is_active' => false,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('companies', 'min_profit_percentage')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('min_profit_percentage');
            });
        }
    }
};
