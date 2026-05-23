<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class CheckPaidResponse implements \JsonSerializable
{
    public function __construct(
        public bool $fullyPaid,
        public bool $bankCanReturnMoneyToCard,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'fully_paid' => $this->fullyPaid,
            'bank_can_return_money_to_card' => $this->bankCanReturnMoneyToCard,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
