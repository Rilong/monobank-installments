# Return Order Endpoint Design

**Date:** 2026-05-23
**Endpoint:** `POST /api/order/return`

## Overview

Add a `returnOrder` method to the `MonobankInstallments` service that calls the Monobank Parts return endpoint. Follows the same patterns as existing methods (`createOrder`, `confirmOrder`, `cancelOrder`).

## Request

**DTO:** `ReturnOrderDTO`

Fields (all snake_case in serialized output):
- `order_id` (string, required) — UUID of the order
- `sum` (float >= 0.01, required) — return amount in UAH
- `store_return_id` (string, required) — store's unique return identifier
- `return_money_to_card` (`ReturnMoneyTo` enum, required) — pure enum with cases `Card` and `Cash`; `toArray()` maps `Card → true`, `Cash → false` (PHP backed enums don't support bool)
- `additional_params` (optional) — `ReturnAdditionalParamsDTO`

**DTO:** `ReturnAdditionalParamsDTO`

Fields:
- `nds` (float, optional) — VAT amount

`toArray()` uses `array_filter` to omit null values, matching existing DTO conventions.

## Response

**DTO:** `ReturnOrderResponse`

Fields:
- `status` (string) — `"OK"` on success

Implements `JsonSerializable`, matching existing response conventions.

## Service Method

```php
public function returnOrder(ReturnOrderDTO $dto): ReturnOrderResponse
{
    $data = $this->client->post('return', $dto->toArray());
    return new ReturnOrderResponse($data['status']);
}
```

## New Files

- `src/Enums/ReturnMoneyTo.php` — pure enum with cases `Card` and `Cash`
- `src/DTOs/ReturnAdditionalParamsDTO.php`
- `src/DTOs/ReturnOrderDTO.php`
- `src/Responses/ReturnOrderResponse.php`

## Modified Files

- `src/MonobankInstallments.php` — add `returnOrder()` method and imports

## Testing

- Unit test for `ReturnOrderDTO::toArray()` — with and without `additional_params`
- Unit test for `ReturnAdditionalParamsDTO::toArray()` — omits null `nds`
- Feature/integration test for `returnOrder()` — mock HTTP client, assert correct payload sent and `ReturnOrderResponse` returned
