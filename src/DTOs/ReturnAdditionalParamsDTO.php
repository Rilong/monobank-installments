<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class ReturnAdditionalParamsDTO
{
    public function __construct(
        public ?float $nds = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'nds' => $this->nds,
        ], fn($v) => $v !== null);
    }
}
