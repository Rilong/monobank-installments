<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class InvoiceDTO
{
    public function __construct(
        public string $number,
        public string $date,
        public string $source,
        public ?string $pointId = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'number' => $this->number,
            'date' => $this->date,
            'source' => $this->source,
            'point_id' => $this->pointId,
        ], fn($v) => $v !== null);
    }
}
