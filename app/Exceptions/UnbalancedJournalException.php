<?php

namespace App\Exceptions;

use RuntimeException;

class UnbalancedJournalException extends RuntimeException
{
    public function __construct(
        public readonly float $totalDebit,
        public readonly float $totalCredit,
        public readonly float $difference,
        string $message = ''
    ) {
        $msg = $message ?: "Jurnal Umum tidak seimbang (Unbalanced Double-Entry). Total Debit: {$totalDebit}, Total Kredit: {$totalCredit}, Selisih: {$difference}.";
        parent::__construct($msg, 422);
    }
}
