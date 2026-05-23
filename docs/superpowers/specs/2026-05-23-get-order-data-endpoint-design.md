# Design: GET Order Data Endpoint (`POST /api/order/data`)

**Date:** 2026-05-23

## Overview

Add a `getOrderData(string $orderId): OrderDataResponse` method to `MonobankInstallments` that calls `POST /api/order/data` and returns detailed order information including financial data, invoice details, and a list of refunds (reverse_list).

## Request

- **Endpoint:** `POST /api/order/data`
- **Body:** `{ "order_id": "<uuid>" }`
- **Auth:** HMAC-SHA256 signed via existing `MonobankClient` (`store-id` + `signature` headers)

No DTO is needed — the request body is a single string field, matching the pattern used by `getState()`, `confirmOrder()`, and `cancelOrder()`.

## Response

**`OrderDataResponse`** — readonly class implementing `JsonSerializable`:

| Property | Type | API field |
|---|---|---|
| `$totalSum` | `float` | `total_sum` |
| `$source` | `string` | `source` (enum: INTERNET, STORE, CHECKOUT, MARKETPLACE) |
| `$invoiceNumber` | `string` | `invoice_number` |
| `$invoiceDate` | `string` | `invoice_date` (date string) |
| `$pointId` | `string` | `point_id` |
| `$storeOrderId` | `string` | `store_order_id` |
| `$createTimestamp` | `?string` | `create_timestamp` (nullable date-time) |
| `$reverseList` | `ReverseItem[]` | `reverse_list` |
| `$maskedCard` | `string` | `maskedCard` |
| `$iban` | `string` | `iban` |

**`ReverseItem`** — small readonly class (not `JsonSerializable`, used only inside `OrderDataResponse`):

| Property | Type | API field |
|---|---|---|
| `$sum` | `float` | `sum` |
| `$timestamp` | `?string` | `timestamp` (nullable date-time) |

## Architecture

### New files

- `src/Responses/ReverseItem.php` — readonly value object for a single reverse_list entry
- `src/Responses/OrderDataResponse.php` — readonly response class with all order data fields

### Modified files

- `src/MonobankInstallments.php` — add `getOrderData(string $orderId): OrderDataResponse`
- `tests/Feature/MonobankInstallmentsTest.php` — add tests (see Testing section)

## Data Flow

```
caller → getOrderData($orderId)
       → MonobankClient::post('data', ['order_id' => $orderId])
       → POST /api/order/data (signed)
       → map response array → ReverseItem[] → OrderDataResponse
```

## Error Handling

No new error handling needed. `MonobankClient` already throws `MonobankInstallmentsException` on non-2xx responses.

## Testing

Four tests in `MonobankInstallmentsTest.php`, matching existing test style:

1. `getOrderData() returns OrderDataResponse with all fields` — fake full response, assert instance and field values (including `reverseList[0]->sum`)
2. `getOrderData() posts to /api/order/data` — assert URL ends with `/api/order/data`
3. `getOrderData() sends order_id in payload` — assert `json_decode($req->body())['order_id']`
4. `getOrderData() throws MonobankInstallmentsException on API error` — fake 400, assert exception
