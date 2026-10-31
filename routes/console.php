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
 * Pengalih Mode Operasional SIGMA ERP:
 * - Mode Development: APP_ENV=local, APP_DEBUG=true, cache nonaktif (live reload)
 * - Mode Production: APP_ENV=production, APP_DEBUG=false, cache penuh (transaksi kasir kilat & aman)
 */
Artisan::command('sigma:mode {target? : Mode tujuan: dev|prod|status}', function (?string $target = null) {
    $envPath = base_path('.env');
    if (! file_exists($envPath)) {
        $this->error('File .env tidak ditemukan!');
        return 1;
    }

    $currentEnv = config('app.env', 'local');
    $currentDebug = config('app.debug', true) ? 'true' : 'false';

    if (! $target || in_array(strtolower($target), ['status', 'check', 'info'])) {
        $this->info('========================================');
        $this->info('  STATUS MODE SISTEM SIGMA ERP & POS   ');
        $this->info('========================================');
        $this->line('Mode Lingkungan (APP_ENV) : '.($currentEnv === 'production' ? '<fg=green;options=bold>PRODUCTION</>' : '<fg=yellow;options=bold>DEVELOPMENT (local)</>'));
        $this->line('Debug Mode (APP_DEBUG)    : '.($currentDebug === 'true' ? '<fg=yellow>ENABLED (true)</>' : '<fg=green>DISABLED (false)</>'));
        $this->line('Status Cache Konfigurasi : '.(app()->configurationIsCached() ? '<fg=green>CACHED (Aktif)</>' : '<fg=yellow>NOT CACHED (Nonaktif)</>'));
        $this->line('Status Cache Rute URL    : '.(app()->routesAreCached() ? '<fg=green>CACHED (Aktif)</>' : '<fg=yellow>NOT CACHED (Nonaktif)</>'));
        $this->info('----------------------------------------');
        $this->line('Gunakan perintah berikut untuk beralih:');
        $this->line('  php artisan sigma:mode dev   -> Mode Development (Uji Coba)');
        $this->line('  php artisan sigma:mode prod  -> Mode Production (Kasir Toko)');
        return 0;
    }

    $target = strtolower($target);
    $envContent = file_get_contents($envPath);

    if (in_array($target, ['dev', 'development', 'local'])) {
        $envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=local', $envContent);
        $envContent = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=true', $envContent);
        file_put_contents($envPath, $envContent);

        $this->info('========================================');
        $this->info('  BERALIH KE MODE DEVELOPMENT 🛠️       ');
        $this->info('========================================');
        $this->comment('Membersihkan cache aplikasi...');
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('attrib -r "'.base_path('bootstrap/cache').'" 2>nul');
            @exec('attrib -r "'.base_path('bootstrap/cache\*.*').'" 2>nul');
        }
        Artisan::call('optimize:clear');
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('attrib -r "'.base_path('bootstrap/cache').'" 2>nul');
            @exec('attrib -r "'.base_path('bootstrap/cache\*.*').'" 2>nul');
        }
        $this->line(Artisan::output());

        $this->info('✅ Sistem SIGMA ERP sekarang dalam MODE DEVELOPMENT!');
        $this->line('• APP_ENV=local | APP_DEBUG=true');
        $this->line('• Caching dinonaktifkan (perubahan kode langsung aktif di browser)');
        $this->line('• Halaman error debug lengkap untuk penelusuran kendala');
        return 0;
    }

    if (in_array($target, ['prod', 'production', 'live'])) {
        $envContent = preg_replace('/^APP_ENV=.*/m', 'APP_ENV=production', $envContent);
        $envContent = preg_replace('/^APP_DEBUG=.*/m', 'APP_DEBUG=false', $envContent);
        file_put_contents($envPath, $envContent);

        $this->info('========================================');
        $this->info('  BERALIH KE MODE PRODUCTION 🚀         ');
        $this->info('========================================');
        $this->comment('Mengoptimalkan & mengompilasi cache...');
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('attrib -r "'.base_path('bootstrap/cache').'" 2>nul');
            @exec('attrib -r "'.base_path('bootstrap/cache\*.*').'" 2>nul');
        }
        Artisan::call('optimize:clear');
        if (PHP_OS_FAMILY === 'Windows') {
            @exec('attrib -r "'.base_path('bootstrap/cache').'" 2>nul');
            @exec('attrib -r "'.base_path('bootstrap/cache\*.*').'" 2>nul');
        }
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->info('✅ Sistem SIGMA ERP sekarang dalam MODE PRODUCTION!');
        $this->line('• APP_ENV=production | APP_DEBUG=false');
        $this->line('• Caching aktif penuh (kecepatan transaksi kasir maksimal)');
        $this->line('• Informasi rahasia dan database terlindungi dari kebocoran');
        return 0;
    }

    $this->error("Mode '{$target}' tidak dikenal. Pilih 'dev' atau 'prod'.");
    return 1;
})->purpose('Mengubah mode operasional SIGMA ERP: Development (Uji Coba & Coding) atau Production (Kasir Toko Live)');
