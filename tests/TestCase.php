<?php

namespace Rilong\MonobankInstallments\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rilong\MonobankInstallments\MonobankInstallmentsProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MonobankInstallmentsProvider::class,
        ];
    }
}
