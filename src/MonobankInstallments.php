<?php

namespace Rilong\MonobankInstallments;

use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\OrderState;
use Rilong\MonobankInstallments\Enums\OrderSubState;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderDataResponse;
use Rilong\MonobankInstallments\Responses\OrderResponse;
use Rilong\MonobankInstallments\Responses\ReturnOrderResponse;

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

    public function getState(string $orderId): OrderResponse
    {
        $data = $this->client->post('state', ['order_id' => $orderId]);

        return new OrderResponse(
            orderId: $data['order_id'],
            state: OrderState::from($data['state']),
            orderSubState: OrderSubState::from($data['order_sub_state']),
            message: $data['message'] ?? null,
        );
    }

    public function confirmOrder(string $orderId): OrderResponse
    {
        $data = $this->client->post('confirm', ['order_id' => $orderId]);

        return new OrderResponse(
            orderId: $data['order_id'],
            state: OrderState::from($data['state']),
            orderSubState: OrderSubState::from($data['order_sub_state']),
            message: $data['message'] ?? null,
        );
    }

    public function cancelOrder(string $orderId): OrderResponse
    {
        $data = $this->client->post('reject', ['order_id' => $orderId]);

        return new OrderResponse(
            orderId: $data['order_id'],
            state: OrderState::from($data['state']),
            orderSubState: OrderSubState::from($data['order_sub_state']),
            message: $data['message'] ?? null,
        );
    }

    public function returnOrder(ReturnOrderDTO $dto): ReturnOrderResponse
    {
        $data = $this->client->post('return', $dto->toArray());

        return new ReturnOrderResponse($data['status']);
    }

    public function getOrderData(string $orderId): OrderDataResponse
    {
        $data = $this->client->post('data', ['order_id' => $orderId]);

        return OrderDataResponse::from($data);
    }
}
