<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class CreateOrderResponse implements \JsonSerializable
{
    public function __construct(public string $orderId) {}

    public function jsonSerialize(): array
    {
        return ['order_id' => $this->orderId];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
