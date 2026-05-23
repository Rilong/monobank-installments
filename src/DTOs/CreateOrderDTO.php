<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class CreateOrderDTO
{
    public function __construct(
        public string $storeOrderId,
        public string $clientPhone,
        public float $totalSum,
        public InvoiceDTO $invoice,
        /** @var ProductDTO[] */
        public array $products,
        /** @var AvailableProgramDTO[] */
        public array $availablePrograms,
        public ?string $resultCallback = null,
        public ?AdditionalParamsDTO $additionalParams = null,
        public ?FinancialCompanyMerchantInfoDTO $financialCompanyMerchantInfo = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'store_order_id' => $this->storeOrderId,
            'client_phone' => $this->clientPhone,
            'total_sum' => $this->totalSum,
            'invoice' => $this->invoice->toArray(),
            'products' => array_map(fn(ProductDTO $p) => $p->toArray(), $this->products),
            'available_programs' => array_map(fn(AvailableProgramDTO $p) => $p->toArray(), $this->availablePrograms),
            'result_callback' => $this->resultCallback,
            'additional_params' => $this->additionalParams?->toArray(),
            'financial_company_merchant_info' => $this->financialCompanyMerchantInfo?->toArray(),
        ], fn($v) => $v !== null);
    }
}
