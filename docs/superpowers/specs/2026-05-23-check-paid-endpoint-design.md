# Check Paid Endpoint Design

**Date:** 2026-05-23
**Endpoint:** `POST /api/order/check/paid`

## Overview

Add a `checkPaid` method to the `MonobankInstallments` service that checks whether a Monobank installment order has been fully paid by the client. Returns `true` for `fully_paid` when all payments have status `SUCCESS`/`DONE`, `false` if there is outstanding debt or active payments. Only works for orders with `SUCCESS` status.

## Request

- **Body:** `{ "order_id": "<uuid>" }` — the unique order identifier
- **Auth:** HMAC-SHA256 signed via existing `MonobankClient` (`store-id` + `signature` headers)

## Response

```json
{
  "fully_paid": true,
  "bank_can_return_money_to_card": true
}
```

- `fully_paid` — whether the order has been fully paid by the client
- `bank_can_return_money_to_card` — whether the bank can return money to the card on order return

## Components

### `src/Responses/CheckPaidResponse.php`

Readonly class implementing `JsonSerializable` with two `bool` properties:

- `fullyPaid` — mapped from `fully_paid`
- `bankCanReturnMoneyToCard` — mapped from `bank_can_return_money_to_card`

Follows the same structure as `ReturnOrderResponse`.

### `MonobankInstallments::checkPaid(string $orderId): CheckPaidResponse`

Calls `$this->client->post('check/paid', ['order_id' => $orderId])` and constructs a `CheckPaidResponse` from the result. The `MonobankClient` builds the full URL as `/api/order/{endpoint}`, so `'check/paid'` correctly maps to `/api/order/check/paid`.

## Error Handling

No special handling needed. `MonobankClient::post()` already throws `MonobankInstallmentsException` on non-2xx responses, consistent with all other methods.

## Testing

One feature test in `MonobankInstallmentsTest.php`:
- Fakes `POST /api/order/check/paid` with a 200 response containing `fully_paid` and `bank_can_return_money_to_card`
- Asserts the returned `CheckPaidResponse` has the correct property values
