<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Hanya mengekspos field publik (tanpa cost_price / HPP).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeUnits = $this->whenLoaded('units', function () {
            return $this->units->where('is_active', true)->values();
        }, collect());

        $defaultUnit = $activeUnits->firstWhere('is_base_unit', true) ?? $activeUnits->first();

        $sellingPrice = 0.0;
        if ($this->relationLoaded('prices') && $this->prices->isNotEmpty()) {
            $retailPrice = $this->prices->first(function ($pr) use ($defaultUnit) {
                return (! $defaultUnit || $pr->product_unit_id === $defaultUnit->id)
                    && ($pr->customerTier?->code === 'RETAIL' || $pr->customerTier?->is_default);
            });
            $fallbackPrice = $defaultUnit
                ? $this->prices->firstWhere('product_unit_id', $defaultUnit->id)
                : $this->prices->first();

            $sellingPrice = (float) ($retailPrice?->price ?? $fallbackPrice?->price ?? 0.0);
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'selling_price' => $sellingPrice,
            'available_stock' => (float) ($this->available_stock ?? 0.0),
            'units' => $activeUnits->map(function ($unit) {
                $unitSellingPrice = 0.0;
                if ($this->relationLoaded('prices') && $this->prices->isNotEmpty()) {
                    $uPrice = $this->prices->first(function ($pr) use ($unit) {
                        return $pr->product_unit_id === $unit->id
                            && ($pr->customerTier?->code === 'RETAIL' || $pr->customerTier?->is_default);
                    }) ?? $this->prices->firstWhere('product_unit_id', $unit->id);

                    $unitSellingPrice = (float) ($uPrice?->price ?? 0.0);
                }

                return [
                    'id' => $unit->id,
                    'unit_code' => $unit->unit_code,
                    'unit_name' => $unit->unit_name,
                    'conversion_factor' => (float) $unit->conversion_factor,
                    'is_base_unit' => (bool) $unit->is_base_unit,
                    'selling_price' => $unitSellingPrice,
                ];
            })->values()->all(),
        ];
    }
}
