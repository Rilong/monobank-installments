<?php

use Rilong\MonobankInstallments\Responses\CancelOrderResponse;
use Rilong\MonobankInstallments\Responses\ConfirmOrderResponse;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderStateResponse;

// --- CreateOrderResponse ---

it('CreateOrderResponse holds orderId', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect($r->orderId)->toBe('uuid-123');
});

it('CreateOrderResponse jsonSerialize returns correct shape', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect($r->jsonSerialize())->toBe(['order_id' => 'uuid-123']);
});

it('CreateOrderResponse __toString returns json', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect((string) $r)->toBe('{"order_id":"uuid-123"}');
});

it('CreateOrderResponse is json_encodable', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect(json_encode($r))->toBe('{"order_id":"uuid-123"}');
});

// --- OrderStateResponse ---

it('OrderStateResponse holds all fields', function () {
    $r = new OrderStateResponse('uuid-1', 'IN_PROCESS', 'WAITING_FOR_CLIENT');
    expect($r->orderId)->toBe('uuid-1')
        ->and($r->state)->toBe('IN_PROCESS')
        ->and($r->orderSubState)->toBe('WAITING_FOR_CLIENT');
});

it('OrderStateResponse jsonSerialize returns snake_case keys', function () {
    $r = new OrderStateResponse('uuid-1', 'SUCCESS', 'DONE');
    expect($r->jsonSerialize())->toBe([
        'order_id'        => 'uuid-1',
        'state'           => 'SUCCESS',
        'order_sub_state' => 'DONE',
    ]);
});

it('OrderStateResponse __toString returns json', function () {
    $r = new OrderStateResponse('uuid-1', 'SUCCESS', 'DONE');
    expect((string) $r)->toBe('{"order_id":"uuid-1","state":"SUCCESS","order_sub_state":"DONE"}');
});

// --- ConfirmOrderResponse ---

it('ConfirmOrderResponse holds success', function () {
    $r = new ConfirmOrderResponse(true);
    expect($r->success)->toBeTrue();
});

it('ConfirmOrderResponse jsonSerialize returns correct shape', function () {
    $r = new ConfirmOrderResponse(true);
    expect($r->jsonSerialize())->toBe(['success' => true]);
});

it('ConfirmOrderResponse __toString returns json', function () {
    $r = new ConfirmOrderResponse(true);
    expect((string) $r)->toBe('{"success":true}');
});

// --- CancelOrderResponse ---

it('CancelOrderResponse holds success', function () {
    $r = new CancelOrderResponse(true);
    expect($r->success)->toBeTrue();
});

it('CancelOrderResponse jsonSerialize returns correct shape', function () {
    $r = new CancelOrderResponse(true);
    expect($r->jsonSerialize())->toBe(['success' => true]);
});

it('CancelOrderResponse __toString returns json', function () {
    $r = new CancelOrderResponse(true);
    expect((string) $r)->toBe('{"success":true}');
});
