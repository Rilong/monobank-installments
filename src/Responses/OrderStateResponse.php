<?php

namespace Rilong\MonobankInstallments\Responses;

use Rilong\MonobankInstallments\Enums\OrderState;
use Rilong\MonobankInstallments\Enums\OrderSubState;

readonly class OrderStateResponse implements \JsonSerializable
{
    public function __construct(
        public string $orderId,
        public OrderState $state,
        public OrderSubState $orderSubState,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'order_id' => $this->orderId,
            'state' => $this->state->value,
            'order_sub_state' => $this->orderSubState->value,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
