<?php

use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;

it('stores status code', function () {
    $e = new MonobankInstallmentsException('Not found', 404);
    expect($e->statusCode)->toBe(404);
});

it('stores message', function () {
    $e = new MonobankInstallmentsException('Unauthorized', 401);
    expect($e->getMessage())->toBe('Unauthorized');
});

it('wraps a previous exception', function () {
    $prev = new RuntimeException('original');
    $e = new MonobankInstallmentsException('Wrapped', 500, $prev);
    expect($e->getPrevious())->toBe($prev);
});

it('is a RuntimeException', function () {
    $e = new MonobankInstallmentsException('error', 500);
    expect($e)->toBeInstanceOf(RuntimeException::class);
});
