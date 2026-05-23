<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class ReverseItem
{
    public function __construct(
        public float $sum,
        public string $timestamp,
    ) {}
}
