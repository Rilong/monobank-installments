<?php

use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\InvoiceDTO;
use Rilong\MonobankInstallments\DTOs\ProductDTO;

function makeCreateOrderDTO(): CreateOrderDTO
{
    return new CreateOrderDTO(
        storeOrderId: 'order-1',
        clientPhone: '+380991234567',
        totalSum: 1000.0,
        invoice: new InvoiceDTO(number: 'INV-001', date: '2026-05-22'),
        products: [new ProductDTO(name: 'Phone', count: 1, sum: 1000.0)],
        availablePrograms: [new AvailableProgramDTO(partsCount: 6)],
        resultCallback: 'https://example.com/callback',
    );
}

it('InvoiceDTO holds number and date', function () {
    $dto = new InvoiceDTO('INV-001', '2026-05-22');
    expect($dto->number)->toBe('INV-001')
        ->and($dto->date)->toBe('2026-05-22');
});

it('InvoiceDTO toArray uses snake_case keys', function () {
    $dto = new InvoiceDTO('INV-001', '2026-05-22');
    expect($dto->toArray())->toBe(['number' => 'INV-001', 'date' => '2026-05-22']);
});

it('ProductDTO holds name, count, sum', function () {
    $dto = new ProductDTO('Phone', 1, 1000.0);
    expect($dto->name)->toBe('Phone')
        ->and($dto->count)->toBe(1)
        ->and($dto->sum)->toBe(1000.0);
});

it('ProductDTO toArray returns correct shape', function () {
    $dto = new ProductDTO('Phone', 1, 1000.0);
    expect($dto->toArray())->toBe(['name' => 'Phone', 'count' => 1, 'sum' => 1000.0]);
});

it('AvailableProgramDTO holds partsCount', function () {
    $dto = new AvailableProgramDTO(6);
    expect($dto->partsCount)->toBe(6);
});

it('AvailableProgramDTO toArray uses snake_case key', function () {
    $dto = new AvailableProgramDTO(6);
    expect($dto->toArray())->toBe(['parts_count' => 6]);
});

it('CreateOrderDTO holds all fields', function () {
    $dto = makeCreateOrderDTO();
    expect($dto->storeOrderId)->toBe('order-1')
        ->and($dto->clientPhone)->toBe('+380991234567')
        ->and($dto->totalSum)->toBe(1000.0)
        ->and($dto->resultCallback)->toBe('https://example.com/callback');
});

it('CreateOrderDTO toArray produces correct API payload', function () {
    $dto = makeCreateOrderDTO();
    expect($dto->toArray())->toBe([
        'store_order_id'     => 'order-1',
        'client_phone'       => '+380991234567',
        'total_sum'          => 1000.0,
        'invoice'            => ['number' => 'INV-001', 'date' => '2026-05-22'],
        'products'           => [['name' => 'Phone', 'count' => 1, 'sum' => 1000.0]],
        'available_programs' => [['parts_count' => 6]],
        'result_callback'    => 'https://example.com/callback',
    ]);
});

it('CreateOrderDTO toArray omits result_callback when null', function () {
    $dto = new CreateOrderDTO(
        storeOrderId: 'order-2',
        clientPhone: '+380991234567',
        totalSum: 500.0,
        invoice: new InvoiceDTO('INV-002', '2026-05-22'),
        products: [new ProductDTO('Item', 1, 500.0)],
        availablePrograms: [new AvailableProgramDTO(3)],
    );
    expect($dto->toArray())->not->toHaveKey('result_callback');
});
