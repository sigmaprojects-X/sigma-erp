<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Archive only the five audited zero-stock demo products in PT SIGMA JAYA NUSANTARA.
     * Transaction and stock history are intentionally preserved.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $company = DB::table('companies')
                ->where('name', 'PT SIGMA JAYA NUSANTARA')
                ->first();

            // Keep staging and development data untouched.
            if (! app()->environment('production') || ! $company) {
                return;
            }

            $expectedProducts = [
                'SBK-BRS-001' => '01a19bdd-0509-72d1-beec-12d4e7a33302',
                'SBK-GLA-001' => '01a19bdd-0575-7146-acab-4b17752c8e15',
                'SBK-MYK-001' => '01a19bdd-0549-730b-a096-69dfc356850b',
                'SBK-TLR-001' => '01a19bdd-05bd-708a-8e0a-829ebbc78fd0',
                'SBK-TRG-001' => '01a19bdd-0599-72e9-a1bb-9dc279287b7d',
            ];

            $products = DB::table('products')
                ->where('company_id', $company->id)
                ->whereIn('sku', array_keys($expectedProducts))
                ->get(['id', 'sku', 'total_quantity']);

            $actualProducts = $products->pluck('id', 'sku')->all();
            ksort($actualProducts);
            ksort($expectedProducts);

            if ($actualProducts !== $expectedProducts) {
                throw new RuntimeException(
                    'Demo product archive aborted: expected audited SKU/ID set was not found in PT SIGMA JAYA NUSANTARA.'
                );
            }

            $productIds = array_values($expectedProducts);
            $hasProductQuantity = $products->contains(
                fn ($product): bool => abs((float) $product->total_quantity) > 0.0001
            );
            $hasStockQuantity = DB::table('product_stocks')
                ->where('company_id', $company->id)
                ->whereIn('product_id', $productIds)
                ->where(function ($query): void {
                    $query->where('physical_quantity', '<>', 0)
                        ->orWhere('reserved_quantity', '<>', 0)
                        ->orWhere('quarantined_quantity', '<>', 0);
                })
                ->exists();

            if ($hasProductQuantity || $hasStockQuantity) {
                throw new RuntimeException(
                    'Demo product archive aborted: one or more audited products now have stock. Resolve inventory before discontinuing them.'
                );
            }

            DB::table('products')
                ->where('company_id', $company->id)
                ->whereIn('id', $productIds)
                ->update([
                    'sale_status' => 'DISCONTINUED',
                    'is_point_of_sale' => false,
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Do not automatically re-enable archived demo products on rollback.
     * An administrator can explicitly restore individual products if needed.
     */
    public function down(): void
    {
        // Intentionally no-op: preserve the explicit discontinued state.
    }
};
