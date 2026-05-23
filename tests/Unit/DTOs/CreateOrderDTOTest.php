<?php

use Rilong\MonobankInstallments\DTOs\AdditionalParamsDTO;
use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\FinancialCompanyMerchantInfoDTO;
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
        availablePrograms: [new AvailableProgramDTO(type: 'payment_installments', availablePartsCount: [3, 6, 9])],
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

it('AvailableProgramDTO holds type and availablePartsCount', function () {
    $dto = new AvailableProgramDTO('payment_installments', [3, 6, 9]);
    expect($dto->type)->toBe('payment_installments')
        ->and($dto->availablePartsCount)->toBe([3, 6, 9]);
});

it('AvailableProgramDTO toArray uses correct API keys', function () {
    $dto = new AvailableProgramDTO('payment_installments', [3, 6, 9]);
    expect($dto->toArray())->toBe([
        'type' => 'payment_installments',
        'available_parts_count' => [3, 6, 9],
    ]);
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
        'store_order_id' => 'order-1',
        'client_phone' => '+380991234567',
        'total_sum' => 1000.0,
        'invoice' => ['number' => 'INV-001', 'date' => '2026-05-22'],
        'products' => [['name' => 'Phone', 'count' => 1, 'sum' => 1000.0]],
        'available_programs' => [['type' => 'payment_installments', 'available_parts_count' => [3, 6, 9]]],
        'result_callback' => 'https://example.com/callback',
    ]);
});

it('CreateOrderDTO toArray omits result_callback when null', function () {
    $dto = new CreateOrderDTO(
        storeOrderId: 'order-2',
        clientPhone: '+380991234567',
        totalSum: 500.0,
        invoice: new InvoiceDTO('INV-002', '2026-05-22'),
        products: [new ProductDTO('Item', 1, 500.0)],
        availablePrograms: [new AvailableProgramDTO('payment_installments', [3])],
    );
    expect($dto->toArray())->not->toHaveKey('result_callback');
});

// --- AdditionalParamsDTO ---

it('AdditionalParamsDTO toArray includes only non-null fields', function () {
    $dto = new AdditionalParamsDTO(sellerPhone: '+380991234567', nds: 100.0);
    expect($dto->toArray())->toBe([
        'seller_phone' => '+380991234567',
        'nds' => 100.0,
    ]);
});

it('AdditionalParamsDTO toArray returns empty array when all null', function () {
    $dto = new AdditionalParamsDTO();
    expect($dto->toArray())->toBe([]);
});

it('AdditionalParamsDTO toArray includes ext_initial_sum', function () {
    $dto = new AdditionalParamsDTO(extInitialSum: 200.0);
    expect($dto->toArray())->toBe(['ext_initial_sum' => 200.0]);
});

// --- FinancialCompanyMerchantInfoDTO ---

it('FinancialCompanyMerchantInfoDTO toArray includes only non-null fields', function () {
    $dto = new FinancialCompanyMerchantInfoDTO(storeName: 'My Shop', edrpouCode: '12345678');
    expect($dto->toArray())->toBe([
        'store_name' => 'My Shop',
        'edrpou_code' => '12345678',
    ]);
});

it('FinancialCompanyMerchantInfoDTO toArray returns empty array when all null', function () {
    $dto = new FinancialCompanyMerchantInfoDTO();
    expect($dto->toArray())->toBe([]);
});

it('FinancialCompanyMerchantInfoDTO toArray includes iban_account', function () {
    $dto = new FinancialCompanyMerchantInfoDTO(ibanAccount: 'UA123456789');
    expect($dto->toArray())->toBe(['iban_account' => 'UA123456789']);
});

// --- CreateOrderDTO with optional nested objects ---

it('CreateOrderDTO toArray includes additional_params when set', function () {
    $dto = new CreateOrderDTO(
        storeOrderId: 'order-3',
        clientPhone: '+380991234567',
        totalSum: 500.0,
        invoice: new InvoiceDTO('INV-003', '2026-05-22'),
        products: [new ProductDTO('Item', 1, 500.0)],
        availablePrograms: [new AvailableProgramDTO('payment_installments', [3])],
        additionalParams: new AdditionalParamsDTO(sellerPhone: '+380991111111'),
    );
    expect($dto->toArray())->toHaveKey('additional_params')
        ->and($dto->toArray()['additional_params'])->toBe(['seller_phone' => '+380991111111']);
});

it('CreateOrderDTO toArray includes financial_company_merchant_info when set', function () {
    $dto = new CreateOrderDTO(
        storeOrderId: 'order-4',
        clientPhone: '+380991234567',
        totalSum: 500.0,
        invoice: new InvoiceDTO('INV-004', '2026-05-22'),
        products: [new ProductDTO('Item', 1, 500.0)],
        availablePrograms: [new AvailableProgramDTO('payment_installments', [3])],
        financialCompanyMerchantInfo: new FinancialCompanyMerchantInfoDTO(storeName: 'Shop', edrpouCode: '12345678', ibanAccount: 'UA123'),
    );
    expect($dto->toArray()['financial_company_merchant_info'])->toBe([
        'store_name' => 'Shop',
        'edrpou_code' => '12345678',
        'iban_account' => 'UA123',
    ]);
});

it('CreateOrderDTO toArray omits additional_params and financial_company_merchant_info when null', function () {
    $dto = new CreateOrderDTO(
        storeOrderId: 'order-5',
        clientPhone: '+380991234567',
        totalSum: 500.0,
        invoice: new InvoiceDTO('INV-005', '2026-05-22'),
        products: [new ProductDTO('Item', 1, 500.0)],
        availablePrograms: [new AvailableProgramDTO('payment_installments', [3])],
    );
    expect($dto->toArray())
        ->not->toHaveKey('additional_params')
        ->not->toHaveKey('financial_company_merchant_info');
});
