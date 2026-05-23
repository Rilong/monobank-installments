# Check Paid Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `checkPaid(string $orderId): CheckPaidResponse` to `MonobankInstallments`, calling `POST /api/order/check/paid` and returning a typed response DTO.

**Architecture:** Follow the existing pattern — new readonly response class in `src/Responses/`, new method on `MonobankInstallments` that delegates to `MonobankClient::post()`, and a feature test that fakes HTTP.

**Tech Stack:** PHP 8.2+, Laravel Http facade, Pest

---

### Task 1: Add `CheckPaidResponse`

**Files:**
- Create: `src/Responses/CheckPaidResponse.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/MonobankInstallmentsTest.php` (at the end of the file, before the final closing):

```php
it('checkPaid() returns CheckPaidResponse with correct values', function () {
    Http::fake(['*' => Http::response([
        'fully_paid' => true,
        'bank_can_return_money_to_card' => true,
    ], 200)]);

    $response = (new MonobankInstallments())->checkPaid('fa4a8249-336e-4e6d-9b85-79bc8be62377');

    expect($response)->toBeInstanceOf(\Rilong\MonobankInstallments\Responses\CheckPaidResponse::class)
        ->and($response->fullyPaid)->toBeTrue()
        ->and($response->bankCanReturnMoneyToCard)->toBeTrue();
});

it('checkPaid() posts to /api/order/check/paid', function () {
    Http::fake(['*' => Http::response([
        'fully_paid' => false,
        'bank_can_return_money_to_card' => false,
    ], 200)]);

    (new MonobankInstallments())->checkPaid('uuid-123');

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/check/paid'));
});

it('checkPaid() sends order_id in payload', function () {
    Http::fake(['*' => Http::response([
        'fully_paid' => false,
        'bank_can_return_money_to_card' => false,
    ], 200)]);

    (new MonobankInstallments())->checkPaid('uuid-123');

    Http::assertSent(fn($req) => json_decode($req->body(), true)['order_id'] === 'uuid-123');
});

it('checkPaid() throws MonobankInstallmentsException on API error', function () {
    Http::fake(['*' => Http::response(['message' => 'Order not found'], 404)]);

    expect(fn() => (new MonobankInstallments())->checkPaid('uuid-123'))
        ->toThrow(\Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException::class, 'Order not found');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php --filter="checkPaid"
```

Expected: FAIL — class `CheckPaidResponse` not found.

- [ ] **Step 3: Create `CheckPaidResponse`**

Create `src/Responses/CheckPaidResponse.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class CheckPaidResponse implements \JsonSerializable
{
    public function __construct(
        public bool $fullyPaid,
        public bool $bankCanReturnMoneyToCard,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'fully_paid' => $this->fullyPaid,
            'bank_can_return_money_to_card' => $this->bankCanReturnMoneyToCard,
        ];
    }
}
```

- [ ] **Step 4: Run tests — expect still failing** (method not yet defined)

```bash
./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php --filter="checkPaid"
```

Expected: FAIL — `Call to undefined method MonobankInstallments::checkPaid()`.

- [ ] **Step 5: Add `checkPaid()` to `MonobankInstallments`**

Add the use statement at the top of `src/MonobankInstallments.php` with the other response imports:

```php
use Rilong\MonobankInstallments\Responses\CheckPaidResponse;
```

Add the method at the end of the class body (after `getOrderData()`):

```php
public function checkPaid(string $orderId): CheckPaidResponse
{
    $data = $this->client->post('check/paid', ['order_id' => $orderId]);

    return new CheckPaidResponse(
        fullyPaid: $data['fully_paid'],
        bankCanReturnMoneyToCard: $data['bank_can_return_money_to_card'],
    );
}
```

- [ ] **Step 6: Run all tests**

```bash
./vendor/bin/pest
```

Expected: All tests pass, including the 4 new `checkPaid` tests.

- [ ] **Step 7: Commit** *(ask user for confirmation before running)*

```bash
git add src/Responses/CheckPaidResponse.php src/MonobankInstallments.php tests/Feature/MonobankInstallmentsTest.php
git commit -m "feat: add checkPaid method for POST /api/order/check/paid"
```
