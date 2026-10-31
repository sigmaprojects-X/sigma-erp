<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAssembly extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_assemblies';

    protected $fillable = [
        'company_id',
        'store_id',
        'assembly_number',
        'assembly_date',
        'type',
        'parent_product_id',
        'parent_product_unit_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'notes',
        'assembled_by',
    ];

    protected function casts(): array
    {
        return [
            'assembly_date' => 'date',
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function parentUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'parent_product_unit_id');
    }

    public function assembler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assembled_by');
    }

    public function creator(): BelongsTo
    {
        return $this->assembler();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductAssemblyItem::class, 'product_assembly_id');
    }
}
