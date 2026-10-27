<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        if (config('app.local_network')) {
            Vite::useHotFile(storage_path('framework/kardex-network.hot'));
            config(['session.domain' => null, 'session.secure' => false, 'app.debug' => false]);
        }
    }
}
