# monobank-installments — Design Spec

**Date:** 2026-05-22
**Package:** `rilong/monobank-installments`
**API:** Monobank Parts (Покупка Частинами)

---

## Overview

A Laravel package that wraps the Monobank Parts installment payment API. Configuration is done once via a static `configure()` call in `AppServiceProvider` — no config file required. All public API methods are accessible through a Laravel Facade. Input and output are fully typed via readonly DTOs.

---

## Configuration

Called once in `AppServiceProvider::register()`:

```php
MonobankInstallments::configure(
    storeId: 'my-store',
    storeSecret: 'secret',
    baseUrl: 'https://u2.monobank.com.ua', // optional, production default
);
```

Environments:

| Environment | Base URL |
|-------------|----------|
| Production  | `https://u2.monobank.com.ua` |
| Stage       | `https://u2-ext.mono.st4g3.com` |
| Sandbox     | `https://u2-demo-ext.mono.st4g3.com` |

---

## File Structure

```
src/
├── MonobankInstallments.php          # Main service class (singleton)
├── MonobankInstallmentsProvider.php  # Laravel ServiceProvider
├── MonobankClient.php                # HTTP transport + HMAC-SHA256 signing
├── Facades/
│   └── MonobankInstallments.php      # Laravel Facade
├── DTOs/
│   ├── CreateOrderDTO.php
│   ├── ProductDTO.php
│   ├── InvoiceDTO.php
│   └── AvailableProgramDTO.php
├── Responses/
│   ├── CreateOrderResponse.php
│   ├── OrderStateResponse.php
│   ├── ConfirmOrderResponse.php
│   └── CancelOrderResponse.php
└── Exceptions/
    └── MonobankInstallmentsException.php
```

---

## Authentication

The Monobank Parts API uses HMAC-SHA256 request signing. Every request requires two headers:

- `store-id`: the merchant identifier (`storeId`)
- `signature`: `Base64(HMAC-SHA256(request_body_bytes, storeSecret))`

Signing is handled entirely inside `MonobankClient` — callers never deal with it.

---

## Public API

All methods are available via the Facade:

```php
use Rilong\MonobankInstallments\Facades\MonobankInstallments;

$response = MonobankInstallments::createOrder(CreateOrderDTO $dto): CreateOrderResponse;
$state    = MonobankInstallments::getState(string $orderId): OrderStateResponse;
$confirm  = MonobankInstallments::confirmOrder(string $orderId): ConfirmOrderResponse;
$cancel   = MonobankInstallments::cancelOrder(string $orderId): CancelOrderResponse;
```

> `$orderId` in `getState()`, `confirmOrder()`, and `cancelOrder()` is the Monobank-generated order ID returned by `createOrder()` (i.e. `CreateOrderResponse::$orderId`), **not** the store's own `store_order_id`.

---

## Data Flow

```
Facade → MonobankInstallments → MonobankClient → Monobank API
```

`MonobankClient` exposes one internal method:

```php
public function post(string $endpoint, array $payload): array
```

Steps per request:
1. JSON-encode `$payload` to UTF-8
2. Compute `Base64(HMAC-SHA256($body, $storeSecret))`
3. POST to `{baseUrl}/api/order/{endpoint}` with `store-id` and `signature` headers
4. On non-2xx: throw `MonobankInstallmentsException` with status code + API error message
5. Return decoded JSON array

`MonobankInstallments` calls the client and maps the raw array to a typed response DTO.

---

## DTOs

All DTOs are `readonly` classes (PHP 8.1+).

### Input DTOs

```php
readonly class CreateOrderDTO {
    public function __construct(
        public string $storeOrderId,       // unique per store, 1–64 chars
        public string $clientPhone,        // "+380XXXXXXXXX"
        public float  $totalSum,           // amount in UAH, >= 1
        public InvoiceDTO $invoice,
        /** @var ProductDTO[] */
        public array $products,
        /** @var AvailableProgramDTO[] */
        public array $availablePrograms,
        public ?string $resultCallback = null,
    ) {}
}

readonly class ProductDTO {
    public function __construct(
        public string $name,
        public int    $count,
        public float  $sum,
    ) {}
}

readonly class InvoiceDTO {
    public function __construct(
        public string $number,
        public string $date,  // "YYYY-MM-DD"
    ) {}
}

readonly class AvailableProgramDTO {
    public function __construct(
        public int $partsCount,  // e.g. 3, 6, 12
    ) {}
}
```

### Response DTOs

All response DTOs implement `\JsonSerializable` and `__toString()`.

```php
readonly class CreateOrderResponse implements \JsonSerializable {
    public function __construct(public string $orderId) {}

    public function jsonSerialize(): array { return ['order_id' => $this->orderId]; }
    public function __toString(): string { return json_encode($this->jsonSerialize()); }
}

readonly class OrderStateResponse implements \JsonSerializable {
    public function __construct(
        public string $orderId,
        public string $state,         // "IN_PROCESS" | "SUCCESS" | "FAIL"
        public string $orderSubState,
    ) {}

    public function jsonSerialize(): array {
        return [
            'order_id'        => $this->orderId,
            'state'           => $this->state,
            'order_sub_state' => $this->orderSubState,
        ];
    }
    public function __toString(): string { return json_encode($this->jsonSerialize()); }
}

readonly class ConfirmOrderResponse implements \JsonSerializable {
    public function __construct(public bool $success) {}

    public function jsonSerialize(): array { return ['success' => $this->success]; }
    public function __toString(): string { return json_encode($this->jsonSerialize()); }
}

readonly class CancelOrderResponse implements \JsonSerializable {
    public function __construct(public bool $success) {}

    public function jsonSerialize(): array { return ['success' => $this->success]; }
    public function __toString(): string { return json_encode($this->jsonSerialize()); }
}
```

---

## Exception

```php
class MonobankInstallmentsException extends \RuntimeException {
    public function __construct(
        string $message,
        public readonly int $statusCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
```

Thrown by `MonobankClient` on any non-2xx response. Carries the HTTP status code and the API's error message.

```php
try {
    $response = MonobankInstallments::createOrder($dto);
} catch (MonobankInstallmentsException $e) {
    // $e->statusCode, $e->getMessage()
}
```

---

## Order State Lifecycle

| State | Sub-state | Meaning |
|-------|-----------|---------|
| `IN_PROCESS` | `WAITING_FOR_CLIENT` | Awaiting client confirmation |
| `IN_PROCESS` | `WAITING_FOR_STORE_CONFIRM` | Call `confirmOrder()` to disburse funds |
| `SUCCESS` | `ACTIVE`, `DONE`, `RETURNED` | Terminal — no action needed |
| `FAIL` | Various | Handle error |

---

## Testing

Uses **Pest** + **Orchestra Workbench**. `MonobankClient` accepts an optional `\Illuminate\Http\Client\Factory` so `Http::fake()` can intercept calls without hitting the real API.

Test coverage:

- `createOrder()` — happy path returns `CreateOrderResponse`; API error throws `MonobankInstallmentsException`
- `getState()` — maps `state` and `order_sub_state` fields correctly
- `confirmOrder()` / `cancelOrder()` — return typed responses
- HMAC signature — correct header computed from body + secret
- `configure()` — `storeId`, `storeSecret`, `baseUrl` stored and used per request
- JSON serialization — `jsonSerialize()` and `__toString()` return correct shapes

---

## API Endpoints Reference

| Method | Endpoint | Maps to |
|--------|----------|---------|
| `createOrder()` | `POST /api/order/create` | Create installment order |
| `getState()` | `POST /api/order/state` | Poll current order status |
| `confirmOrder()` | `POST /api/order/confirm` | Confirm delivery, disburse funds |
| `cancelOrder()` | `POST /api/order/reject` | Cancel/reject order |
