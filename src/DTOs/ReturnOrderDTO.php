<?php

namespace Rilong\MonobankInstallments\DTOs;

use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;

readonly class ReturnOrderDTO
{
    public function __construct(
        public string $orderId,
        public float $sum,
        public string $storeReturnId,
        public ReturnMoneyTo $returnMoneyTo,
        public ?ReturnAdditionalParamsDTO $additionalParams = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'order_id' => $this->orderId,
            'sum' => $this->sum,
            'store_return_id' => $this->storeReturnId,
            'return_money_to_card' => $this->returnMoneyTo === ReturnMoneyTo::Card,
            'additional_params' => $this->additionalParams?->toArray(),
        ], fn($v) => $v !== null);
    }
}
