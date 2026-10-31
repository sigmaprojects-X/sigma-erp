<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Supplier extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'suppliers';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'is_pkp',
        'npwp',
        'tax_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'term_of_payment_days',
        'bank_name',
        'bank_account_no',
        'bank_account_holder',
        'deposit_balance',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'term_of_payment_days' => 'integer',
            'deposit_balance' => 'decimal:2',
            'is_pkp' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function deposits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SupplierDeposit::class);
    }
}
