<?php

use App\Models\SalesOrder;
use App\Services\InventoryService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Otomatis melepaskan alokasi stok ter-reservasi untuk pesanan online PWA yang telah kedaluwarsa.
 * Sesuai aturan bisnis AGENTS.md Bagian 2 & 4D: TTL 30 Menit.
 */
Artisan::command('sigma:release-expired-reservations', function (InventoryService $inventoryService) {
    $this->info('Memeriksa reservasi stok PWA yang kedaluwarsa (TTL > 30 menit)...');

    $expiredOrders = SalesOrder::with(['items.product', 'items.productUnit', 'store'])
        ->where('status', 'PENDING')
        ->where('created_at', '<', now()->subMinutes(30))
        ->get();

    $count = 0;
    foreach ($expiredOrders as $order) {
        $released = DB::transaction(function () use ($order, $inventoryService) {
            $lockedOrder = SalesOrder::with(['items.product', 'store'])
                ->where('id', $order->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                return false;
            }

            foreach ($lockedOrder->items as $item) {
                if (! $item->product || ! $item->product->tracksInventory()) {
                    continue;
                }

                $baseQty = (float) $item->quantity * (float) ($item->conversion_factor ?? 1);
                $inventoryService->releaseReservedStock(
                    product: $item->product,
                    store: $lockedOrder->store,
                    quantity: $baseQty,
                    referenceNumber: $lockedOrder->order_number,
                    notes: "Pelepasan otomatis: TTL reservasi 30 menit kedaluwarsa [{$lockedOrder->order_number}]"
                );
            }

            $lockedOrder->update([
                'status' => 'CANCELLED',
                'notes' => trim(($lockedOrder->notes ?? '') . ' | Dibatalkan otomatis: TTL reservasi 30 menit kedaluwarsa.'),
            ]);

            return true;
        });

        if ($released) {
            $count++;
        }
    }

    $this->info("Berhasil membatalkan dan melepaskan reservasi untuk {$count} pesanan.");
})->purpose('Melepaskan kuantitas stok ter-reservasi untuk pesanan online PWA yang melewati batas waktu TTL 30 menit (AGENTS.md Section 2 & 4D)');

Schedule::command('sigma:release-expired-reservations')->everyFiveMinutes();

/**
 * Tampilkan environment aplikasi. Nilai APP_ENV dan APP_DEBUG ditentukan
 * oleh konfigurasi masing-masing deployment, bukan diubah saat aplikasi berjalan.
 */
Artisan::command('sigma:mode {target? : Gunakan status untuk melihat konfigurasi aktif}', function (?string $target = null) {
    if ($target && ! in_array(strtolower($target), ['status', 'check', 'info'], true)) {
        $this->error('Mode bersifat read-only. Atur APP_ENV dan APP_DEBUG pada konfigurasi deployment atau .env lokal; gunakan staging terpisah untuk uji coba.');
        return 1;
    }

    $currentEnv = config('app.env', 'production');
    $currentDebug = config('app.debug', false);

    $this->info('========================================');
    $this->info('  STATUS MODE SISTEM SIGMA ERP & POS   ');
    $this->info('========================================');
    $this->line('Mode Lingkungan (APP_ENV) : '.($currentEnv === 'production' ? '<fg=green;options=bold>PRODUCTION</>' : '<fg=yellow;options=bold>'.strtoupper($currentEnv).'</>'));
    $this->line('Debug Mode (APP_DEBUG)    : '.($currentDebug ? '<fg=yellow>ENABLED (true)</>' : '<fg=green>DISABLED (false)</>'));
    $this->line('Status Cache Konfigurasi : '.(app()->configurationIsCached() ? '<fg=green>CACHED (Aktif)</>' : '<fg=yellow>NOT CACHED (Nonaktif)</>'));
    $this->line('Status Cache Rute URL    : '.(app()->routesAreCached() ? '<fg=green>CACHED (Aktif)</>' : '<fg=yellow>NOT CACHED (Nonaktif)</>'));
    $this->comment('Mode tidak dapat diubah dari aplikasi yang sedang berjalan.');
    return 0;
})->purpose('Menampilkan status environment SIGMA ERP secara read-only');
