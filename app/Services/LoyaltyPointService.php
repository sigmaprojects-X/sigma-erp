<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPointMutation;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRewardRedemption;
use App\Models\PosTransaction;
use App\Models\ProductUnit;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoyaltyPointService
{
    /**
     * Ambil atau Buat Aturan Program Poin Loyalitas Perusahaan.
     */
    public function getActiveProgram(Company $company): LoyaltyProgram
    {
        return LoyaltyProgram::firstOrCreate(
            [
                'company_id' => $company->id,
                'is_active' => true,
            ],
            [
                'name' => 'Program Poin Reward Belanja',
                'spend_amount_per_point' => 10000.00, // Rp 10.000 belanja = 1 poin
                'point_redemption_value' => 100.00, // 1 poin bernilai diskon Rp 100
                'min_points_to_redeem' => 10, // Minimal 10 poin
                'points_expiry_days' => 365, // Berlaku 1 tahun
                'allow_merchandise_redemption' => true,
                'only_registered_customers' => true, // Default: hanya pelanggan terdaftar/member yang dapat poin
            ]
        );
    }

    /**
     * Hitung Estimasi Poin yang Diperoleh dari Belanja, termasuk Tier Multiplier dan Bonus Item.
     *
     * @param array<int, array{product_unit_id?: string, unit_id?: string, quantity?: float}> $cartItems
     */
    public function calculatePointsEarned(Customer $customer, float $spendAmount, array $cartItems = []): int
    {
        $program = $this->getActiveProgram($customer->company);
        if (! $program->is_active) {
            return 0;
        }

        // Cek apakah poin hanya untuk pelanggan terdaftar (bukan pelanggan umum/walk-in)
        if ($program->only_registered_customers && $customer->isGeneralOrWalkIn()) {
            return 0;
        }

        $basePoints = 0;
        if ($spendAmount > 0 && (float) $program->spend_amount_per_point > 0) {
            $rawPoints = floor($spendAmount / (float) $program->spend_amount_per_point);

            // Terapkan Multiplier Customer Tier jika ada
            $customer->loadMissing('tier');
            $multiplier = (float) ($customer->tier?->point_multiplier ?? 1.0);
            if ($multiplier <= 0) {
                $multiplier = 1.0;
            }

            $basePoints = (int) floor($rawPoints * $multiplier);
        }

        // Hitung Bonus Poin per Item Produk (product_units.point_reward)
        $itemBonusPoints = 0;
        foreach ($cartItems as $item) {
            $unitId = $item['product_unit_id'] ?? $item['unit_id'] ?? null;
            $qty = (float) ($item['quantity'] ?? 0);
            if ($unitId && $qty > 0) {
                $productUnit = ProductUnit::find($unitId);
                if ($productUnit && (int) $productUnit->point_reward > 0) {
                    $itemBonusPoints += (int) floor((int) $productUnit->point_reward * $qty);
                }
            }
        }

        return $basePoints + $itemBonusPoints;
    }

    /**
     * Hitung Nilai Diskon Rupiah dari Poin yang Ditukarkan.
     */
    public function calculateRedemptionDiscount(Customer $customer, int $pointsToRedeem, float $maxAllowedAmount): float
    {
        if ($pointsToRedeem <= 0) {
            return 0.0;
        }

        $program = $this->getActiveProgram($customer->company);
        if (! $program->is_active) {
            throw new \DomainException('Program loyalitas poin sedang tidak aktif.');
        }

        if ($pointsToRedeem < $program->min_points_to_redeem) {
            throw new \DomainException("Minimal penukaran poin adalah {$program->min_points_to_redeem} poin.");
        }

        $currentPoints = (int) $customer->loyalty_points;
        if ($pointsToRedeem > $currentPoints) {
            throw new \DomainException("Saldo poin pelanggan tidak mencukupi (Tersedia: {$currentPoints}, Ingin Tukar: {$pointsToRedeem}).");
        }

        $grossDiscount = (float) $pointsToRedeem * (float) $program->point_redemption_value;

        return min($maxAllowedAmount, round($grossDiscount, 2));
    }

    /**
     * Berikan Poin Hadiah ke Pelanggan dari Transaksi Belanja (EARN).
     */
    public function awardPoints(
        Customer $customer,
        int $points,
        PosTransaction $transaction,
        ?string $userId = null
    ): ?CustomerPointMutation {
        if ($points <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customer, $points, $transaction, $userId) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            $program = $this->getActiveProgram($lockedCustomer->company);
            $expiryDays = (int) ($program->points_expiry_days ?: 365);
            $expiredAt = now()->addDays($expiryDays)->toDateString();

            $before = (int) $lockedCustomer->loyalty_points;
            $after = $before + $points;
            $lifetime = (int) $lockedCustomer->lifetime_points + $points;

            $lockedCustomer->update([
                'loyalty_points' => $after,
                'lifetime_points' => $lifetime,
            ]);

            return CustomerPointMutation::create([
                'company_id' => $lockedCustomer->company_id,
                'customer_id' => $lockedCustomer->id,
                'pos_transaction_id' => $transaction->id,
                'type' => 'EARN',
                'points' => $points,
                'balance_before' => $before,
                'balance_after' => $after,
                'expired_at' => $expiredAt,
                'is_expired' => false,
                'description' => "Poin Belanja Faktur: {$transaction->invoice_number}",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Potong Poin Pelanggan untuk Penukaran Diskon (REDEEM).
     */
    public function redeemPoints(
        Customer $customer,
        int $points,
        PosTransaction $transaction,
        ?string $userId = null
    ): ?CustomerPointMutation {
        if ($points <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customer, $points, $transaction, $userId) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            $before = (int) $lockedCustomer->loyalty_points;
            if ($before < $points) {
                throw new \DomainException("Saldo poin tidak mencukupi. Tersedia: {$before}, Diminta: {$points}.");
            }

            $after = $before - $points;

            $lockedCustomer->update([
                'loyalty_points' => $after,
            ]);

            return CustomerPointMutation::create([
                'company_id' => $lockedCustomer->company_id,
                'customer_id' => $lockedCustomer->id,
                'pos_transaction_id' => $transaction->id,
                'type' => 'REDEEM',
                'points' => -$points,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => "Penukaran Diskon Belanja Faktur: {$transaction->invoice_number}",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Tukar Poin dengan Hadiah Fisik (Merchandise Reward).
     */
    public function redeemMerchandise(
        Customer $customer,
        LoyaltyReward $reward,
        Store $store,
        int $qty,
        ?string $userId = null,
        ?string $notes = null
    ): LoyaltyRewardRedemption {
        if ($qty <= 0) {
            throw new \DomainException('Jumlah penukaran harus minimal 1.');
        }

        return DB::transaction(function () use ($customer, $reward, $store, $qty, $userId, $notes) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            /** @var LoyaltyReward $lockedReward */
            $lockedReward = LoyaltyReward::where('id', $reward->id)->lockForUpdate()->firstOrFail();

            if (! $lockedReward->is_active) {
                throw new \DomainException("Hadiah {$lockedReward->name} sedang tidak aktif.");
            }

            if ((float) $lockedReward->stock_quantity < $qty) {
                throw new \DomainException("Stok hadiah {$lockedReward->name} tidak mencukupi (Tersedia: {$lockedReward->stock_quantity}).");
            }

            $totalPointsNeeded = (int) $lockedReward->points_required * $qty;
            $before = (int) $lockedCustomer->loyalty_points;

            if ($before < $totalPointsNeeded) {
                throw new \DomainException("Saldo poin pelanggan tidak mencukupi. Tersedia: {$before}, Dibutuhkan: {$totalPointsNeeded}.");
            }

            $after = $before - $totalPointsNeeded;

            // Kurangi poin pelanggan
            $lockedCustomer->update(['loyalty_points' => $after]);

            // Kurangi stok hadiah
            $lockedReward->decrement('stock_quantity', $qty);

            // Generate nomor klaim
            $prefix = 'RWD-' . date('Ymd');
            $uniqueCode = strtoupper(Str::random(4));
            $redemptionNumber = "{$prefix}-{$uniqueCode}";

            // Catat mutasi poin
            CustomerPointMutation::create([
                'company_id' => $lockedCustomer->company_id,
                'customer_id' => $lockedCustomer->id,
                'pos_transaction_id' => null,
                'type' => 'REDEEM',
                'points' => -$totalPointsNeeded,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => "Tukar Hadiah: {$lockedReward->name} ({$qty} pcs)",
                'created_by' => $userId,
            ]);

            // Simpan klaim hadiah
            return LoyaltyRewardRedemption::create([
                'company_id' => $lockedCustomer->company_id,
                'store_id' => $store->id,
                'customer_id' => $lockedCustomer->id,
                'loyalty_reward_id' => $lockedReward->id,
                'redemption_number' => $redemptionNumber,
                'points_used' => $totalPointsNeeded,
                'quantity' => $qty,
                'redemption_date' => now(),
                'notes' => $notes,
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Eksekusi Poin Kedaluwarsa.
     */
    public function expirePoints(Company $company): int
    {
        $today = now()->toDateString();
        $expiredMutations = CustomerPointMutation::where('company_id', $company->id)
            ->where('type', 'EARN')
            ->where('is_expired', false)
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', $today)
            ->get();

        $totalExpiredCount = 0;

        foreach ($expiredMutations as $mutation) {
            DB::transaction(function () use ($mutation, &$totalExpiredCount) {
                /** @var CustomerPointMutation $lockedMutation */
                $lockedMutation = CustomerPointMutation::where('id', $mutation->id)->lockForUpdate()->first();
                if ($lockedMutation->is_expired) {
                    return;
                }

                $customer = Customer::where('id', $lockedMutation->customer_id)->lockForUpdate()->first();
                if (! $customer) {
                    $lockedMutation->update(['is_expired' => true]);
                    return;
                }

                $pointsToExpire = min((int) $customer->loyalty_points, (int) $lockedMutation->points);
                if ($pointsToExpire > 0) {
                    $before = (int) $customer->loyalty_points;
                    $after = $before - $pointsToExpire;

                    $customer->update(['loyalty_points' => $after]);

                    CustomerPointMutation::create([
                        'company_id' => $customer->company_id,
                        'customer_id' => $customer->id,
                        'pos_transaction_id' => null,
                        'type' => 'EXPIRED',
                        'points' => -$pointsToExpire,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'description' => "Poin Kedaluwarsa dari Transaksi Sebelumnya",
                        'created_by' => null,
                    ]);
                }

                $lockedMutation->update(['is_expired' => true]);
                $totalExpiredCount++;
            });
        }

        return $totalExpiredCount;
    }

    /**
     * Penyesuaian Saldo Poin Manual (ADJUSTMENT).
     */
    public function adjustPoints(
        Customer $customer,
        int $pointsDelta,
        string $reason,
        ?string $userId = null
    ): CustomerPointMutation {
        return DB::transaction(function () use ($customer, $pointsDelta, $reason, $userId) {
            /** @var Customer $lockedCustomer */
            $lockedCustomer = Customer::where('id', $customer->id)->lockForUpdate()->firstOrFail();

            $before = (int) $lockedCustomer->loyalty_points;
            $after = max(0, $before + $pointsDelta);

            $lockedCustomer->update([
                'loyalty_points' => $after,
            ]);

            return CustomerPointMutation::create([
                'company_id' => $lockedCustomer->company_id,
                'customer_id' => $lockedCustomer->id,
                'pos_transaction_id' => null,
                'type' => 'ADJUSTMENT',
                'points' => $pointsDelta,
                'balance_before' => $before,
                'balance_after' => $after,
                'description' => "Penyesuaian Manual: {$reason}",
                'created_by' => $userId,
            ]);
        });
    }

    /**
     * Perbarui Konfigurasi Program Loyalitas.
     */
    public function updateProgram(Company $company, array $data): LoyaltyProgram
    {
        $program = $this->getActiveProgram($company);
        $program->update([
            'name' => $data['name'] ?? $program->name,
            'spend_amount_per_point' => (float) ($data['spend_amount_per_point'] ?? $program->spend_amount_per_point),
            'point_redemption_value' => (float) ($data['point_redemption_value'] ?? $program->point_redemption_value),
            'min_points_to_redeem' => (int) ($data['min_points_to_redeem'] ?? $program->min_points_to_redeem),
            'points_expiry_days' => (int) ($data['points_expiry_days'] ?? $program->points_expiry_days ?? 365),
            'allow_merchandise_redemption' => isset($data['allow_merchandise_redemption']) ? (bool) $data['allow_merchandise_redemption'] : $program->allow_merchandise_redemption,
            'only_registered_customers' => isset($data['only_registered_customers']) ? (bool) $data['only_registered_customers'] : $program->only_registered_customers,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $program->is_active,
        ]);

        return $program;
    }
}
