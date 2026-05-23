<?php

use Illuminate\Support\Facades\Http;
use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\InvoiceDTO;
use Rilong\MonobankInstallments\DTOs\ProductDTO;
use Rilong\MonobankInstallments\Enums\OrderState;
use Rilong\MonobankInstallments\Enums\OrderSubState;
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;
use Rilong\MonobankInstallments\MonobankInstallments;
use Rilong\MonobankInstallments\DTOs\ReturnAdditionalParamsDTO;
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderResponse;
use Rilong\MonobankInstallments\Responses\ReturnOrderResponse;

function makeDTO(): CreateOrderDTO
{
    return new CreateOrderDTO(
        storeOrderId: 'order-1',
        clientPhone: '+380991234567',
        totalSum: 1000.0,
        invoice: new InvoiceDTO('INV-001', '2026-05-22'),
        products: [new ProductDTO('Phone', 1, 1000.0)],
        availablePrograms: [new AvailableProgramDTO('payment_installments', [3, 6, 9])],
    );
}

beforeEach(function () {
    MonobankInstallments::configure(storeId: 'test-store', storeSecret: 'test-secret');
});

it('configure() sets storeId and storeSecret used in requests', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid'], 201)]);

    MonobankInstallments::configure(storeId: 'my-store', storeSecret: 'my-secret');
    (new MonobankInstallments())->createOrder(makeDTO());

    Http::assertSent(fn($req) => $req->hasHeader('store-id', 'my-store'));
});

it('configure() sets custom baseUrl', function () {
    Http::fake([
        'https://u2-demo-ext.mono.st4g3.com/api/order/create' => Http::response(['order_id' => 'uuid'], 201),
    ]);

    MonobankInstallments::configure(
        storeId: 'test_store_with_confirm',
        storeSecret: 'secret',
        baseUrl: 'https://u2-demo-ext.mono.st4g3.com'
    );

    (new MonobankInstallments())->createOrder(makeDTO());

    Http::assertSent(fn($req) => str_contains($req->url(), 'u2-demo-ext.mono.st4g3.com'));
});

it('createOrder() returns CreateOrderResponse with orderId', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid-123'], 201)]);

    $response = (new MonobankInstallments())->createOrder(makeDTO());

    expect($response)->toBeInstanceOf(CreateOrderResponse::class)
        ->and($response->orderId)->toBe('uuid-123');
});

it('createOrder() sends correct payload to API', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid'], 201)]);

    (new MonobankInstallments())->createOrder(makeDTO());

    Http::assertSent(function ($req) {
        $body = json_decode($req->body(), true);
        return $body['store_order_id'] === 'order-1'
            && $body['client_phone'] === '+380991234567'
            && $body['total_sum'] == 1000.0;
    });
});

it('createOrder() throws MonobankInstallmentsException on API error', function () {
    Http::fake(['*' => Http::response(['message' => 'Validation failed'], 400)]);

    expect(fn() => (new MonobankInstallments())->createOrder(makeDTO()))
        ->toThrow(MonobankInstallmentsException::class, 'Validation failed');
});

it('getState() returns OrderResponse', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123',
        'state' => 'IN_PROCESS',
        'order_sub_state' => 'WAITING_FOR_CLIENT',
    ], 200)]);

    $response = (new MonobankInstallments())->getState('uuid-123');

    expect($response)->toBeInstanceOf(OrderResponse::class)
        ->and($response->orderId)->toBe('uuid-123')
        ->and($response->state)->toBe(OrderState::InProcess)
        ->and($response->orderSubState)->toBe(OrderSubState::WaitingForClient);
});

it('getState() sends order_id in payload', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123', 'state' => 'SUCCESS', 'order_sub_state' => 'DONE',
    ], 200)]);

    (new MonobankInstallments())->getState('uuid-123');

    Http::assertSent(fn($req) => json_decode($req->body(), true)['order_id'] === 'uuid-123');
});

it('confirmOrder() returns OrderResponse with state and subState', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123',
        'state' => 'SUCCESS',
        'order_sub_state' => 'DONE',
    ], 200)]);

    $response = (new MonobankInstallments())->confirmOrder('uuid-123');

    expect($response)->toBeInstanceOf(OrderResponse::class)
        ->and($response->orderId)->toBe('uuid-123')
        ->and($response->state)->toBe(OrderState::Success)
        ->and($response->orderSubState)->toBe(OrderSubState::Done);
});

it('confirmOrder() posts to /api/order/confirm', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123', 'state' => 'SUCCESS', 'order_sub_state' => 'DONE',
    ], 200)]);

    (new MonobankInstallments())->confirmOrder('uuid-123');

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/confirm'));
});

it('cancelOrder() returns OrderResponse with state and subState', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123',
        'state' => 'FAIL',
        'order_sub_state' => 'REJECTED_BY_CLIENT',
    ], 200)]);

    $response = (new MonobankInstallments())->cancelOrder('uuid-123');

    expect($response)->toBeInstanceOf(OrderResponse::class)
        ->and($response->orderId)->toBe('uuid-123')
        ->and($response->state)->toBe(OrderState::Fail)
        ->and($response->orderSubState)->toBe(OrderSubState::RejectedByClient);
});

it('cancelOrder() posts to /api/order/reject', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123', 'state' => 'FAIL', 'order_sub_state' => 'REJECTED_BY_CLIENT',
    ], 200)]);

    (new MonobankInstallments())->cancelOrder('uuid-123');

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/reject'));
});

it('returnOrder() returns ReturnOrderResponse with status OK', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);

    $dto = new ReturnOrderDTO(
        orderId: 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        sum: 1250.5,
        storeReturnId: 'RET-12345',
        returnMoneyTo: ReturnMoneyTo::Card,
        additionalParams: new ReturnAdditionalParamsDTO(nds: 208.42),
    );

    $response = (new MonobankInstallments())->returnOrder($dto);

    expect($response)->toBeInstanceOf(ReturnOrderResponse::class)
        ->and($response->status)->toBe('OK');
});

it('returnOrder() posts to /api/order/return', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);

    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
    );

    (new MonobankInstallments())->returnOrder($dto);

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/return'));
});

it('returnOrder() sends correct payload', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);

    $dto = new ReturnOrderDTO(
        orderId: 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        sum: 1250.5,
        storeReturnId: 'RET-12345',
        returnMoneyTo: ReturnMoneyTo::Card,
        additionalParams: new ReturnAdditionalParamsDTO(nds: 208.42),
    );

    (new MonobankInstallments())->returnOrder($dto);

    Http::assertSent(function ($req) {
        $body = json_decode($req->body(), true);
        return $body['order_id'] === 'fa4a8249-336e-4e6d-9b85-79bc8be62377'
            && $body['sum'] == 1250.5
            && $body['store_return_id'] === 'RET-12345'
            && $body['return_money_to_card'] === true
            && $body['additional_params']['nds'] == 208.42;
    });
});

it('returnOrder() throws MonobankInstallmentsException on API error', function () {
    Http::fake(['*' => Http::response(['message' => 'Order not found'], 404)]);

    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
    );

    expect(fn() => (new MonobankInstallments())->returnOrder($dto))
        ->toThrow(MonobankInstallmentsException::class, 'Order not found');
});
