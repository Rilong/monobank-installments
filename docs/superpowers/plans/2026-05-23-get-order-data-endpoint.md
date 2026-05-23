# Get Order Data Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `getOrderData(string $orderId): OrderDataResponse` to `MonobankInstallments` that calls `POST /api/order/data` and returns full order details.

**Architecture:** Two new readonly response classes (`ReverseItem`, `OrderDataResponse`) following the existing pattern; one new method on `MonobankInstallments` that delegates to `MonobankClient::post()`. Tests are added to the existing feature test file using `Http::fake()`.

**Tech Stack:** PHP 8.2, Laravel HTTP client (`Illuminate\Support\Facades\Http`), Pest

---

## File Map

| Action | Path | Responsibility |
|---|---|---|
| Create | `src/Responses/ReverseItem.php` | Value object for a single `reverse_list` item |
| Create | `src/Responses/OrderDataResponse.php` | Full response for `/api/order/data` |
| Modify | `src/MonobankInstallments.php` | Add `getOrderData()` method |
| Modify | `tests/Feature/MonobankInstallmentsTest.php` | Four new tests |

---

### Task 1: Write failing tests for `getOrderData()`

**Files:**
- Modify: `tests/Feature/MonobankInstallmentsTest.php`

- [ ] **Step 1: Add the four tests at the end of the file**

Add these tests to the end of `tests/Feature/MonobankInstallmentsTest.php`:

```php
use Rilong\MonobankInstallments\Responses\OrderDataResponse;

it('getOrderData() returns OrderDataResponse with all fields', function () {
    Http::fake(['*' => Http::response([
        'total_sum' => 2499.99,
        'source' => 'INTERNET',
        'invoice_number' => 'INV-001234',
        'invoice_date' => '2024-01-23',
        'point_id' => 'STORE-001',
        'store_order_id' => 'ORD-2024-001234',
        'create_timestamp' => null,
        'reverse_list' => [
            ['sum' => 500, 'timestamp' => null],
        ],
        'maskedCard' => '5375 41** **** 1234',
        'iban' => 'UA123456789012345678901234567',
    ], 200)]);

    $response = (new MonobankInstallments())->getOrderData('fa4a8249-336e-4e6d-9b85-79bc8be62377');

    expect($response)->toBeInstanceOf(OrderDataResponse::class)
        ->and($response->totalSum)->toBe(2499.99)
        ->and($response->source)->toBe('INTERNET')
        ->and($response->invoiceNumber)->toBe('INV-001234')
        ->and($response->invoiceDate)->toBe('2024-01-23')
        ->and($response->pointId)->toBe('STORE-001')
        ->and($response->storeOrderId)->toBe('ORD-2024-001234')
        ->and($response->createTimestamp)->toBeNull()
        ->and($response->reverseList[0]->sum)->toBe(500.0)
        ->and($response->reverseList[0]->timestamp)->toBeNull()
        ->and($response->maskedCard)->toBe('5375 41** **** 1234')
        ->and($response->iban)->toBe('UA123456789012345678901234567');
});

it('getOrderData() posts to /api/order/data', function () {
    Http::fake(['*' => Http::response([
        'total_sum' => 2499.99,
        'source' => 'INTERNET',
        'invoice_number' => 'INV-001234',
        'invoice_date' => '2024-01-23',
        'point_id' => 'STORE-001',
        'store_order_id' => 'ORD-2024-001234',
        'create_timestamp' => null,
        'reverse_list' => [],
        'maskedCard' => '5375 41** **** 1234',
        'iban' => 'UA123456789012345678901234567',
    ], 200)]);

    (new MonobankInstallments())->getOrderData('uuid-123');

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/data'));
});

it('getOrderData() sends order_id in payload', function () {
    Http::fake(['*' => Http::response([
        'total_sum' => 2499.99,
        'source' => 'INTERNET',
        'invoice_number' => 'INV-001234',
        'invoice_date' => '2024-01-23',
        'point_id' => 'STORE-001',
        'store_order_id' => 'ORD-2024-001234',
        'create_timestamp' => null,
        'reverse_list' => [],
        'maskedCard' => '5375 41** **** 1234',
        'iban' => 'UA123456789012345678901234567',
    ], 200)]);

    (new MonobankInstallments())->getOrderData('uuid-123');

    Http::assertSent(fn($req) => json_decode($req->body(), true)['order_id'] === 'uuid-123');
});

it('getOrderData() throws MonobankInstallmentsException on API error', function () {
    Http::fake(['*' => Http::response(['message' => 'Order not found'], 404)]);

    expect(fn() => (new MonobankInstallments())->getOrderData('uuid-123'))
        ->toThrow(MonobankInstallmentsException::class, 'Order not found');
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php --filter="getOrderData"
```

Expected: 4 failures — `Call to undefined method ... getOrderData()` or similar.

---

### Task 2: Create `ReverseItem`

**Files:**
- Create: `src/Responses/ReverseItem.php`

- [ ] **Step 1: Create the file**

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class ReverseItem
{
    public function __construct(
        public float $sum,
        public ?string $timestamp,
    ) {}
}
```

---

### Task 3: Create `OrderDataResponse`

**Files:**
- Create: `src/Responses/OrderDataResponse.php`

- [ ] **Step 1: Create the file**

```php
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
        public array $reverseList,
        public string $maskedCard,
        public string $iban,
    ) {}

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
```

---

### Task 4: Add `getOrderData()` and verify all tests pass

**Files:**
- Modify: `src/MonobankInstallments.php`

- [ ] **Step 1: Add the import and method to `MonobankInstallments`**

At the top of `src/MonobankInstallments.php`, add the two new imports alongside the existing ones:

```php
use Rilong\MonobankInstallments\Responses\OrderDataResponse;
use Rilong\MonobankInstallments\Responses\ReverseItem;
```

Add the method after `returnOrder()`:

```php
public function getOrderData(string $orderId): OrderDataResponse
{
    $data = $this->client->post('data', ['order_id' => $orderId]);

    return new OrderDataResponse(
        totalSum: $data['total_sum'],
        source: $data['source'],
        invoiceNumber: $data['invoice_number'],
        invoiceDate: $data['invoice_date'],
        pointId: $data['point_id'],
        storeOrderId: $data['store_order_id'],
        createTimestamp: $data['create_timestamp'] ?? null,
        reverseList: array_map(
            fn(array $item) => new ReverseItem($item['sum'], $item['timestamp'] ?? null),
            $data['reverse_list'],
        ),
        maskedCard: $data['maskedCard'],
        iban: $data['iban'],
    );
}
```

- [ ] **Step 2: Run the new tests**

```bash
./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php --filter="getOrderData"
```

Expected: 4 tests pass.

- [ ] **Step 3: Run the full test suite**

```bash
./vendor/bin/pest
```

Expected: All tests pass, no regressions.

- [ ] **Step 4: Commit (ask user for confirmation first)**

Stage and commit:

```bash
git add src/Responses/ReverseItem.php \
        src/Responses/OrderDataResponse.php \
        src/MonobankInstallments.php \
        tests/Feature/MonobankInstallmentsTest.php
git commit -m "feat: add getOrderData method for POST /api/order/data"
```
