<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class FinancialCompanyMerchantInfoDTO
{
    public function __construct(
        public ?string $storeName = null,
        public ?string $edrpouCode = null,
        public ?string $ibanAccount = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'store_name' => $this->storeName,
            'edrpou_code' => $this->edrpouCode,
            'iban_account' => $this->ibanAccount,
        ], fn($v) => $v !== null);
    }
}
