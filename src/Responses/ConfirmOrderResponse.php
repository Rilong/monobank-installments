<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class ConfirmOrderResponse implements \JsonSerializable
{
    public function __construct(public bool $success) {}

    public function jsonSerialize(): array
    {
        return ['success' => $this->success];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
