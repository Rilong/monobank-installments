<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class AdditionalParamsDTO
{
    public function __construct(
        public ?string $sellerPhone = null,
        public ?float $nds = null,
        public ?float $extInitialSum = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'seller_phone' => $this->sellerPhone,
            'nds' => $this->nds,
            'ext_initial_sum' => $this->extInitialSum,
        ], fn($v) => $v !== null);
    }
}
