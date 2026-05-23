<?php

namespace Rilong\MonobankInstallments\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void configure(string $storeId, string $storeSecret, string $baseUrl = 'https://u2.monobank.com.ua')
 * @method static \Rilong\MonobankInstallments\Responses\CreateOrderResponse createOrder(\Rilong\MonobankInstallments\DTOs\CreateOrderDTO $dto)
 * @method static \Rilong\MonobankInstallments\Responses\OrderStateResponse getState(string $orderId)
 * @method static \Rilong\MonobankInstallments\Responses\ConfirmOrderResponse confirmOrder(string $orderId)
 * @method static \Rilong\MonobankInstallments\Responses\CancelOrderResponse cancelOrder(string $orderId)
 */
class MonobankInstallments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'monobank-installments';
    }
}
