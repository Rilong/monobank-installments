<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class OrderStateResponse implements \JsonSerializable
{
    public function __construct(
        public string $orderId,
        public string $state,
        public string $orderSubState,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'order_id'        => $this->orderId,
            'state'           => $this->state,
            'order_sub_state' => $this->orderSubState,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
