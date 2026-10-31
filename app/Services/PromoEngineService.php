<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Store;

class PromoEngineService
{
    /**
     * Dapatkan semua promosi aktif yang berlaku untuk toko & waktu saat ini.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Promotion>
     */
    public function getActivePromotions(Company $company, ?Store $store = null)
    {
        $storeId = $store?->id;

        return Promotion::where('company_id', $company->id)
            ->where('is_active', true)
            ->where(function ($q) use ($storeId) {
                $q->whereNull('store_id');
                if ($storeId) {
                    $q->orWhere('store_id', $storeId);
                }
            })
            ->where(function ($q) {
                $today = now()->format('Y-m-d');
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) {
                $today = now()->format('Y-m-d');
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->get()
            ->filter(fn (Promotion $p) => $p->isValidNow(0.0, $storeId));
    }

    /**
     * Evaluasi keranjang belanja untuk promo otomatis (Buy X Get Y, Tebus Murah, Diskon).
     *
     * @param array<int, array{product_id: string, quantity: float, unit_price: float, subtotal: float}> $cartItems
     * @return array{
     *     applied_promotions: array<int, array{promotion_id: string, name: string, type: string, discount_amount: float, description: string}>,
     *     total_promo_discount: float,
     *     eligible_rewards: array<int, array{promotion_id: string, name: string, reward_product_id: string, reward_product_name: string, special_price: float, max_quantity: float}>
     * }
     */
    public function evaluateCart(Company $company, ?Store $store, array $cartItems, float $cartSubtotal): array
    {
        $activePromos = $this->getActivePromotions($company, $store);
        $appliedPromotions = [];
        $eligibleRewards = [];
        $totalPromoDiscount = 0.0;

        // Group quantities by product_id
        $productQuantities = [];
        foreach ($cartItems as $item) {
            $pId = $item['product_id'];
            $productQuantities[$pId] = ($productQuantities[$pId] ?? 0.0) + (float) ($item['quantity'] ?? 0);
        }

        foreach ($activePromos as $promo) {
            // Cek batasan min spend
            if ($promo->min_spend > 0 && $cartSubtotal < (float) $promo->min_spend) {
                continue;
            }

            switch ($promo->type) {
                case 'BUY_X_GET_Y':
                    // Cek ketersediaan produk syarat X
                    $buyQty = (float) $promo->buy_quantity;
                    $ownedQty = $productQuantities[$promo->buy_product_id] ?? 0.0;

                    if ($buyQty > 0 && $ownedQty >= $buyQty) {
                        $multiplier = floor($ownedQty / $buyQty);
                        $freeQty = $multiplier * (float) $promo->reward_quantity;

                        // Jika produk reward juga ada di keranjang, beri diskon 100% untuk produk reward
                        $rewardInCartQty = $productQuantities[$promo->reward_product_id] ?? 0.0;
                        $eligibleQty = min($freeQty, $rewardInCartQty);

                        if ($eligibleQty > 0) {
                            $rewardProduct = $promo->rewardProduct ?? Product::find($promo->reward_product_id);
                            // Ambil harga satuan dari keranjang jika ada
                            $itemInCart = collect($cartItems)->firstWhere('product_id', $promo->reward_product_id);
                            $unitPrice = $itemInCart['unit_price'] ?? 0.0;
                            $discount = round($eligibleQty * $unitPrice, 2);

                            $appliedPromotions[] = [
                                'promotion_id' => $promo->id,
                                'name' => $promo->name,
                                'type' => $promo->type,
                                'discount_amount' => $discount,
                                'description' => "Promo Beli {$promo->buy_quantity} Gratis {$eligibleQty} ({$rewardProduct?->name})",
                            ];
                            $totalPromoDiscount += $discount;
                        } else {
                            // Pelanggan berhak klaim reward produk gratis
                            $rewardProduct = $promo->rewardProduct ?? Product::find($promo->reward_product_id);
                            $eligibleRewards[] = [
                                'promotion_id' => $promo->id,
                                'name' => $promo->name,
                                'reward_product_id' => $promo->reward_product_id,
                                'reward_product_name' => $rewardProduct?->name ?? 'Produk Hadiah',
                                'special_price' => 0.0,
                                'max_quantity' => $freeQty,
                            ];
                        }
                    }
                    break;

                case 'TEBUS_MURAH':
                    // Jika pembelanjaan >= min_spend, berhak tebus murah produk tertentu dengan special_price
                    $rewardProduct = $promo->rewardProduct ?? Product::find($promo->reward_product_id);
                    $eligibleRewards[] = [
                        'promotion_id' => $promo->id,
                        'name' => $promo->name,
                        'reward_product_id' => $promo->reward_product_id,
                        'reward_product_name' => $rewardProduct?->name ?? 'Produk Tebus Murah',
                        'special_price' => (float) $promo->special_price,
                        'max_quantity' => (float) ($promo->reward_quantity ?: 1.0),
                    ];
                    break;

                case 'DISCOUNT_PERCENT':
                    if ($promo->discount_percent > 0) {
                        $discount = round(($cartSubtotal * (float) $promo->discount_percent) / 100, 2);
                        $appliedPromotions[] = [
                            'promotion_id' => $promo->id,
                            'name' => $promo->name,
                            'type' => $promo->type,
                            'discount_amount' => $discount,
                            'description' => "Diskon Promo {$promo->discount_percent}%",
                        ];
                        $totalPromoDiscount += $discount;
                    }
                    break;

                case 'DISCOUNT_NOMINAL':
                    if ($promo->discount_nominal > 0) {
                        $discount = min($cartSubtotal, (float) $promo->discount_nominal);
                        $appliedPromotions[] = [
                            'promotion_id' => $promo->id,
                            'name' => $promo->name,
                            'type' => $promo->type,
                            'discount_amount' => $discount,
                            'description' => "Potongan Langsung Rp " . number_format($promo->discount_nominal, 0, ',', '.'),
                        ];
                        $totalPromoDiscount += $discount;
                    }
                    break;
            }
        }

        return [
            'applied_promotions' => $appliedPromotions,
            'total_promo_discount' => round($totalPromoDiscount, 2),
            'eligible_rewards' => $eligibleRewards,
        ];
    }
}
