<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class InvoiceDTO
{
    public function __construct(
        public string $number,
        public string $date,
    ) {}

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'date'   => $this->date,
        ];
    }
}
