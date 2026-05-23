<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class AvailableProgramDTO
{
    public function __construct(
        public string $type,
        /** @var int[] */
        public array $availablePartsCount,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'available_parts_count' => $this->availablePartsCount,
        ];
    }
}
