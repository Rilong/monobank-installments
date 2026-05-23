<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class ProductDTO
{
    public function __construct(
        public string $name,
        public int $count,
        public float $sum,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'count' => $this->count,
            'sum' => $this->sum,
        ];
    }
}
