<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cash_transactions';

    protected $fillable = [
        'company_id',
        'store_id',
        'transaction_number',
        'type',
        'transaction_date',
        'source_account_id',
        'destination_account_id',
        'category_account_id',
        'amount',
        'admin_fee',
        'admin_fee_account_id',
        'contact_person',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'admin_fee' => 'decimal:2',
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

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'source_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'destination_account_id');
    }

    public function categoryAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'category_account_id');
    }

    public function adminFeeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'admin_fee_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
