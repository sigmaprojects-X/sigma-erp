<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly string $productId,
        public readonly string $storeId,
        public readonly float $requestedQuantity,
        public readonly float $availableQuantity,
        string $message = ''
    ) {
        $msg = $message ?: "Stok tidak mencukupi untuk produk ID [{$productId}] di toko [{$storeId}]. Diminta: {$requestedQuantity}, Tersedia: {$availableQuantity}.";
        parent::__construct($msg, 422);
    }
}
