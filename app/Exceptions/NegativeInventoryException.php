<?php

namespace App\Exceptions;

use RuntimeException;

class NegativeInventoryException extends RuntimeException
{
    public function __construct(
        public readonly string $productId,
        public readonly string $storeId,
        public readonly float $currentQuantity,
        public readonly float $attemptedDeduction,
        string $message = ''
    ) {
        $msg = $message ?: "Pelanggaran Aturan 'Zero Negative Inventory' pada produk ID [{$productId}]. Stok sekarang: {$currentQuantity}, Pengurangan yang dicoba: {$attemptedDeduction}.";
        parent::__construct($msg, 422);
    }
}
