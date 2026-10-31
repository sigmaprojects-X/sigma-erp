<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYearClosing extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'fiscal_year_closings';

    protected $fillable = [
        'company_id',
        'fiscal_year',
        'closing_date',
        'total_revenue',
        'total_expense',
        'net_profit',
        'retained_earnings_account_id',
        'closing_journal_entry_id',
        'closed_by',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'closing_date' => 'date',
            'total_revenue' => 'decimal:2',
            'total_expense' => 'decimal:2',
            'net_profit' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function retainedEarningsAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'retained_earnings_account_id');
    }

    public function closingJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'closing_journal_entry_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
