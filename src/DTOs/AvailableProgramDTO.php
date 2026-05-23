<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class AvailableProgramDTO
{
    public function __construct(public int $partsCount) {}

    public function toArray(): array
    {
        return ['parts_count' => $this->partsCount];
    }
}
