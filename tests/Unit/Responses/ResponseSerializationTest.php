<?php

use Rilong\MonobankInstallments\Enums\OrderState;
use Rilong\MonobankInstallments\Enums\OrderSubState;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderResponse;

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

// --- OrderResponse ---

it('OrderResponse holds all fields', function () {
    $r = new OrderResponse('uuid-1', OrderState::InProcess, OrderSubState::WaitingForClient);
    expect($r->orderId)->toBe('uuid-1')
        ->and($r->state)->toBe(OrderState::InProcess)
        ->and($r->orderSubState)->toBe(OrderSubState::WaitingForClient)
        ->and($r->message)->toBeNull();
});

it('OrderResponse jsonSerialize returns snake_case keys', function () {
    $r = new OrderResponse('uuid-1', OrderState::Success, OrderSubState::Done);
    expect($r->jsonSerialize())->toBe([
        'order_id' => 'uuid-1',
        'state' => 'SUCCESS',
        'order_sub_state' => 'DONE',
    ]);
});

it('OrderResponse jsonSerialize includes message when set', function () {
    $r = new OrderResponse('uuid-1', OrderState::Fail, OrderSubState::Fail, 'Some error');
    expect($r->jsonSerialize())->toBe([
        'order_id' => 'uuid-1',
        'state' => 'FAIL',
        'order_sub_state' => 'FAIL',
        'message' => 'Some error',
    ]);
});

it('OrderResponse __toString returns json', function () {
    $r = new OrderResponse('uuid-1', OrderState::Success, OrderSubState::Done);
    expect((string) $r)->toBe('{"order_id":"uuid-1","state":"SUCCESS","order_sub_state":"DONE"}');
});

it('OrderResponse is json_encodable', function () {
    $r = new OrderResponse('uuid-1', OrderState::Success, OrderSubState::Done);
    expect(json_encode($r))->toBe('{"order_id":"uuid-1","state":"SUCCESS","order_sub_state":"DONE"}');
});

// --- ReturnOrderResponse ---

use Rilong\MonobankInstallments\Responses\ReturnOrderResponse;

it('ReturnOrderResponse holds status', function () {
    $r = new ReturnOrderResponse('OK');
    expect($r->status)->toBe('OK');
});

it('ReturnOrderResponse jsonSerialize returns status array', function () {
    $r = new ReturnOrderResponse('OK');
    expect($r->jsonSerialize())->toBe(['status' => 'OK']);
});

it('ReturnOrderResponse __toString returns json', function () {
    $r = new ReturnOrderResponse('OK');
    expect((string) $r)->toBe('{"status":"OK"}');
});

it('ReturnOrderResponse is json_encodable', function () {
    $r = new ReturnOrderResponse('OK');
    expect(json_encode($r))->toBe('{"status":"OK"}');
});
