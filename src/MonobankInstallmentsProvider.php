<?php

namespace Rilong\MonobankInstallments;

use Illuminate\Support\ServiceProvider;

class MonobankInstallmentsProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the main class to use with the facade
        $this->app->singleton('monobank-installments', function () {
            return new MonobankInstallments();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Optional: Publish configuration file, migrations, etc.
    }
}