<?php

use Rilong\MonobankInstallments\DTOs\ReturnAdditionalParamsDTO;
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;

it('ReturnAdditionalParamsDTO toArray includes nds when set', function () {
    $dto = new ReturnAdditionalParamsDTO(nds: 208.42);
    expect($dto->toArray())->toBe(['nds' => 208.42]);
});

it('ReturnAdditionalParamsDTO toArray returns empty array when all null', function () {
    $dto = new ReturnAdditionalParamsDTO();
    expect($dto->toArray())->toBe([]);
});

it('ReturnOrderDTO toArray produces correct payload with Card', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        sum: 1250.5,
        storeReturnId: 'RET-12345',
        returnMoneyTo: ReturnMoneyTo::Card,
    );
    expect($dto->toArray())->toBe([
        'order_id' => 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        'sum' => 1250.5,
        'store_return_id' => 'RET-12345',
        'return_money_to_card' => true,
    ]);
});

it('ReturnOrderDTO toArray maps Cash to false for return_money_to_card', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 500.0,
        storeReturnId: 'RET-99999',
        returnMoneyTo: ReturnMoneyTo::Cash,
    );
    expect($dto->toArray()['return_money_to_card'])->toBeFalse();
});

it('ReturnOrderDTO toArray includes additional_params when set', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
        additionalParams: new ReturnAdditionalParamsDTO(nds: 208.42),
    );
    expect($dto->toArray()['additional_params'])->toBe(['nds' => 208.42]);
});

it('ReturnOrderDTO toArray omits additional_params when null', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
    );
    expect($dto->toArray())->not->toHaveKey('additional_params');
});
