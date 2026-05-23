<?php

use Illuminate\Support\Facades\Http;
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;
use Rilong\MonobankInstallments\MonobankClient;

it('posts to the correct endpoint URL', function () {
    Http::fake([
        'https://u2.monobank.com.ua/api/order/create' => Http::response(['order_id' => 'abc'], 201),
    ]);

    $client = new MonobankClient('my-store', 'my-secret', 'https://u2.monobank.com.ua');
    $client->post('create', ['store_order_id' => 'order-1']);

    Http::assertSent(fn($req) => $req->url() === 'https://u2.monobank.com.ua/api/order/create');
});

it('sends store-id header', function () {
    Http::fake(['*' => Http::response(['order_id' => 'abc'], 201)]);

    $client = new MonobankClient('my-store', 'my-secret', 'https://u2.monobank.com.ua');
    $client->post('create', ['store_order_id' => 'order-1']);

    Http::assertSent(fn($req) => $req->hasHeader('store-id', 'my-store'));
});

it('sends correct hmac-sha256 signature header', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $storeSecret = 'test-secret';
    $payload = ['store_order_id' => 'order-1'];
    $body = json_encode($payload);
    $expectedSignature = base64_encode(hash_hmac('sha256', $body, $storeSecret, true));

    $client = new MonobankClient('store', $storeSecret, 'https://u2.monobank.com.ua');
    $client->post('create', $payload);

    Http::assertSent(fn($req) => $req->header('signature')[0] === $expectedSignature);
});

it('returns decoded json on success', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid-123'], 201)]);

    $client = new MonobankClient('store', 'secret', 'https://u2.monobank.com.ua');
    $result = $client->post('create', ['store_order_id' => 'order-1']);

    expect($result)->toBe(['order_id' => 'uuid-123']);
});

it('throws MonobankInstallmentsException on 400', function () {
    Http::fake(['*' => Http::response(['message' => 'Validation error'], 400)]);

    $client = new MonobankClient('store', 'secret', 'https://u2.monobank.com.ua');

    expect(fn() => $client->post('create', []))
        ->toThrow(MonobankInstallmentsException::class, 'Validation error');
});

it('throws MonobankInstallmentsException on 401', function () {
    Http::fake(['*' => Http::response(['message' => 'Invalid signature'], 401)]);

    $client = new MonobankClient('store', 'wrong-secret', 'https://u2.monobank.com.ua');

    $e = null;
    try {
        $client->post('create', []);
    } catch (MonobankInstallmentsException $caught) {
        $e = $caught;
    }

    expect($e)->not->toBeNull()
        ->and($e->statusCode)->toBe(401);
});

it('throws MonobankInstallmentsException on 500', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    $client = new MonobankClient('store', 'secret', 'https://u2.monobank.com.ua');

    expect(fn() => $client->post('state', ['order_id' => 'uuid']))
        ->toThrow(MonobankInstallmentsException::class);
});

it('uses custom baseUrl', function () {
    Http::fake([
        'https://u2-demo-ext.mono.st4g3.com/api/order/create' => Http::response(['order_id' => 'sandbox-id'], 201),
    ]);

    $client = new MonobankClient('test_store', 'secret', 'https://u2-demo-ext.mono.st4g3.com');
    $result = $client->post('create', ['store_order_id' => 'order-1']);

    expect($result)->toBe(['order_id' => 'sandbox-id']);
});
