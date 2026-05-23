<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class OrderDataResponse implements \JsonSerializable
{
    public function __construct(
        public float $totalSum,
        public string $source,
        public string $invoiceNumber,
        public string $invoiceDate,
        public string $pointId,
        public string $storeOrderId,
        public ?string $createTimestamp,
        /** @var ReverseItem[] */
        public array $reverseList,
        public string $maskedCard,
        public string $iban,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            totalSum: $data['total_sum'],
            source: $data['source'],
            invoiceNumber: $data['invoice_number'],
            invoiceDate: $data['invoice_date'],
            pointId: $data['point_id'],
            storeOrderId: $data['store_order_id'],
            createTimestamp: $data['create_timestamp'] ?? null,
            reverseList: array_map(
                fn(array $item) => new ReverseItem($item['sum'], $item['timestamp']),
                $data['reverse_list'] ?? [],
            ),
            maskedCard: $data['maskedCard'],
            iban: $data['iban'],
        );
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'total_sum' => $this->totalSum,
            'source' => $this->source,
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'point_id' => $this->pointId,
            'store_order_id' => $this->storeOrderId,
            'create_timestamp' => $this->createTimestamp,
            'reverse_list' => array_map(fn(ReverseItem $item) => [
                'sum' => $item->sum,
                'timestamp' => $item->timestamp,
            ], $this->reverseList),
            'maskedCard' => $this->maskedCard,
            'iban' => $this->iban,
        ], fn($v) => $v !== null);
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
