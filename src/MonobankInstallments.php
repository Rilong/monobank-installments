<?php

namespace Rilong\MonobankInstallments;

use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\Enums\OrderState;
use Rilong\MonobankInstallments\Enums\OrderSubState;
use Rilong\MonobankInstallments\Responses\CancelOrderResponse;
use Rilong\MonobankInstallments\Responses\ConfirmOrderResponse;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderStateResponse;

class MonobankInstallments
{
    private static string $storeId = '';
    private static string $storeSecret = '';
    private static string $baseUrl = 'https://u2.monobank.com.ua';

    private MonobankClient $client;

    public static function configure(
        string $storeId,
        string $storeSecret,
        string $baseUrl = 'https://u2.monobank.com.ua',
    ): void {
        static::$storeId     = $storeId;
        static::$storeSecret = $storeSecret;
        static::$baseUrl     = $baseUrl;
    }

    public function __construct()
    {
        $this->client = new MonobankClient(
            static::$storeId,
            static::$storeSecret,
            static::$baseUrl,
        );
    }

    public function createOrder(CreateOrderDTO $dto): CreateOrderResponse
    {
        $data = $this->client->post('create', $dto->toArray());
       
        return new CreateOrderResponse($data['order_id']);
    }

    public function getState(string $orderId): OrderStateResponse
    {
        $data = $this->client->post('state', ['order_id' => $orderId]);
       
        return new OrderStateResponse(
            $data['order_id'],
            OrderState::from($data['state']),
            OrderSubState::from($data['order_sub_state']),
        );
    }

    public function confirmOrder(string $orderId): ConfirmOrderResponse
    {
        $data = $this->client->post('confirm', ['order_id' => $orderId]);

        return new ConfirmOrderResponse(
            $data['order_id'],
            OrderState::from($data['state']),
            OrderSubState::from($data['order_sub_state']),
            $data['message'] ?? null,
        );
    }

    public function cancelOrder(string $orderId): CancelOrderResponse
    {
        $data = $this->client->post('reject', ['order_id' => $orderId]);

        return new CancelOrderResponse($data['success']);
    }
}
