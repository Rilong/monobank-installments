<?php

namespace Rilong\MonobankInstallments;

use Illuminate\Support\ServiceProvider;

class MonobankInstallmentsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('monobank-installments', function () {
            return new MonobankInstallments();
        });
    }

    public function boot(): void {}
}
