<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class ReturnOrderResponse implements \JsonSerializable
{
    public function __construct(
        public string $status,
    ) {}

    public function jsonSerialize(): array
    {
        return ['status' => $this->status];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
