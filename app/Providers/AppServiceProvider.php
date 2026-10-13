<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Pagination\Paginator;
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
        Paginator::useBootstrap();
        app(\App\Services\SystemSettings::class)->load();
        \App\Models\ProductStock::updated(static function(\App\Models\ProductStock $stock){
            app(\App\Services\NotificationSignals::class)->stockChanged($stock);
        });
    }
}
