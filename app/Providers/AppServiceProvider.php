<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Perlindungan HPP / Cost of Goods Sold (AGENTS.md Section 6 & Owner Decision K-02)
        Gate::define('view-hpp', function (User $user) {
            return $user->canViewHpp();
        });

        // 2. Modul Penjualan & POS
        Gate::define('access-pos', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'store_head', 'cashier', 'sales'], true);
        });

        Gate::define('view-sales', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'store_head', 'finance', 'sales'], true);
        });

        Gate::define('use-manager-pin', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'store_head'], true);
        });

        // 3. Modul Pembelian (Purchasing)
        Gate::define('view-purchases', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'purchasing', 'finance'], true);
        });

        Gate::define('manage-purchases', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'purchasing'], true);
        });

        // 4. Modul Master Data
        Gate::define('manage-products', function (User $user) {
            return in_array($user->role, ['owner', 'manager'], true);
        });

        Gate::define('manage-master', function (User $user) {
            return in_array($user->role, ['owner', 'manager'], true);
        });

        Gate::define('view-customers', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'store_head', 'finance', 'sales'], true);
        });

        Gate::define('manage-suppliers', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'purchasing'], true);
        });

        Gate::define('view-stock', function (User $user) {
            return in_array($user->role, ['owner', 'manager', 'store_head', 'purchasing', 'finance', 'warehouse'], true);
        });

        // 5. Modul Settings
        Gate::define('manage-settings', function (User $user) {
            return in_array($user->role, ['owner', 'manager'], true);
        });
    }
}
