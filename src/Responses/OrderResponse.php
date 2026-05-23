<?php

namespace Rilong\MonobankInstallments\Responses;

use Rilong\MonobankInstallments\Enums\OrderState;
use Rilong\MonobankInstallments\Enums\OrderSubState;

readonly class OrderResponse implements \JsonSerializable
{
    public function __construct(
        public string $orderId,
        public OrderState $state,
        public OrderSubState $orderSubState,
        public ?string $message = null,
    ) {}

    public function jsonSerialize(): array
    {
        return array_filter([
            'order_id' => $this->orderId,
            'state' => $this->state->value,
            'order_sub_state' => $this->orderSubState->value,
            'message' => $this->message,
        ], fn($v) => $v !== null);
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
