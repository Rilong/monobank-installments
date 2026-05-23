# Return Order Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `returnOrder()` method to `MonobankInstallments` that calls `POST /api/order/return` and returns a `ReturnOrderResponse`.

**Architecture:** Three new files (enum, two DTOs, one response) plus a new method on the existing service class. Follows the same readonly-class + `toArray()` + `JsonSerializable` patterns used throughout the package. `ReturnMoneyTo` is a pure PHP enum because PHP backed enums don't support `bool`.

**Tech Stack:** PHP 8.2+, Laravel 12/13, Pest (tests), Orchestra Workbench (test bootstrap), `Illuminate\Support\Facades\Http` for HTTP faking in feature tests.

---

### Task 1: `ReturnMoneyTo` enum

**Files:**
- Create: `src/Enums/ReturnMoneyTo.php`

- [ ] **Step 1: Create the enum**

```php
<?php

namespace Rilong\MonobankInstallments\Enums;

enum ReturnMoneyTo
{
    case Card;
    case Cash;
}
```

- [ ] **Step 2: Verify the file parses without errors**

Run: `php -l src/Enums/ReturnMoneyTo.php`
Expected: `No syntax errors detected in src/Enums/ReturnMoneyTo.php`

- [ ] **Step 3: Commit**

```bash
git add src/Enums/ReturnMoneyTo.php
git commit -m "feat: add ReturnMoneyTo enum"
```

---

### Task 2: `ReturnAdditionalParamsDTO`

**Files:**
- Create: `src/DTOs/ReturnAdditionalParamsDTO.php`
- Test: `tests/Unit/DTOs/ReturnOrderDTOTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/DTOs/ReturnOrderDTOTest.php`:

```php
<?php

use Rilong\MonobankInstallments\DTOs\ReturnAdditionalParamsDTO;

it('ReturnAdditionalParamsDTO toArray includes nds when set', function () {
    $dto = new ReturnAdditionalParamsDTO(nds: 208.42);
    expect($dto->toArray())->toBe(['nds' => 208.42]);
});

it('ReturnAdditionalParamsDTO toArray returns empty array when all null', function () {
    $dto = new ReturnAdditionalParamsDTO();
    expect($dto->toArray())->toBe([]);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `./vendor/bin/pest tests/Unit/DTOs/ReturnOrderDTOTest.php`
Expected: FAIL with class not found error.

- [ ] **Step 3: Create the DTO**

```php
<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class ReturnAdditionalParamsDTO
{
    public function __construct(
        public ?float $nds = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'nds' => $this->nds,
        ], fn($v) => $v !== null);
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `./vendor/bin/pest tests/Unit/DTOs/ReturnOrderDTOTest.php`
Expected: 2 passed.

- [ ] **Step 5: Commit**

```bash
git add src/DTOs/ReturnAdditionalParamsDTO.php tests/Unit/DTOs/ReturnOrderDTOTest.php
git commit -m "feat: add ReturnAdditionalParamsDTO"
```

---

### Task 3: `ReturnOrderDTO`

**Files:**
- Create: `src/DTOs/ReturnOrderDTO.php`
- Modify: `tests/Unit/DTOs/ReturnOrderDTOTest.php` — append new tests

- [ ] **Step 1: Append the failing tests**

Add to `tests/Unit/DTOs/ReturnOrderDTOTest.php`:

```php
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;

it('ReturnOrderDTO toArray produces correct payload with Card', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        sum: 1250.5,
        storeReturnId: 'RET-12345',
        returnMoneyTo: ReturnMoneyTo::Card,
    );
    expect($dto->toArray())->toBe([
        'order_id'             => 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        'sum'                  => 1250.5,
        'store_return_id'      => 'RET-12345',
        'return_money_to_card' => true,
    ]);
});

it('ReturnOrderDTO toArray maps Cash to false for return_money_to_card', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 500.0,
        storeReturnId: 'RET-99999',
        returnMoneyTo: ReturnMoneyTo::Cash,
    );
    expect($dto->toArray()['return_money_to_card'])->toBeFalse();
});

it('ReturnOrderDTO toArray includes additional_params when set', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
        additionalParams: new ReturnAdditionalParamsDTO(nds: 208.42),
    );
    expect($dto->toArray()['additional_params'])->toBe(['nds' => 208.42]);
});

it('ReturnOrderDTO toArray omits additional_params when null', function () {
    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
    );
    expect($dto->toArray())->not->toHaveKey('additional_params');
});
```

- [ ] **Step 2: Run tests to verify new ones fail**

Run: `./vendor/bin/pest tests/Unit/DTOs/ReturnOrderDTOTest.php`
Expected: first 2 pass, last 4 fail with class not found error.

- [ ] **Step 3: Create the DTO**

```php
<?php

namespace Rilong\MonobankInstallments\DTOs;

use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;

readonly class ReturnOrderDTO
{
    public function __construct(
        public string $orderId,
        public float $sum,
        public string $storeReturnId,
        public ReturnMoneyTo $returnMoneyTo,
        public ?ReturnAdditionalParamsDTO $additionalParams = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'order_id'             => $this->orderId,
            'sum'                  => $this->sum,
            'store_return_id'      => $this->storeReturnId,
            'return_money_to_card' => $this->returnMoneyTo === ReturnMoneyTo::Card,
        ];

        if ($this->additionalParams !== null) {
            $data['additional_params'] = $this->additionalParams->toArray();
        }

        return $data;
    }
}
```

- [ ] **Step 4: Run tests to verify all pass**

Run: `./vendor/bin/pest tests/Unit/DTOs/ReturnOrderDTOTest.php`
Expected: 6 passed.

- [ ] **Step 5: Commit**

```bash
git add src/DTOs/ReturnOrderDTO.php tests/Unit/DTOs/ReturnOrderDTOTest.php
git commit -m "feat: add ReturnOrderDTO"
```

---

### Task 4: `ReturnOrderResponse`

**Files:**
- Create: `src/Responses/ReturnOrderResponse.php`
- Modify: `tests/Unit/Responses/ResponseSerializationTest.php` — append new tests

- [ ] **Step 1: Append the failing tests**

Add to the bottom of `tests/Unit/Responses/ResponseSerializationTest.php`:

```php
use Rilong\MonobankInstallments\Responses\ReturnOrderResponse;

// --- ReturnOrderResponse ---

it('ReturnOrderResponse holds status', function () {
    $r = new ReturnOrderResponse('OK');
    expect($r->status)->toBe('OK');
});

it('ReturnOrderResponse jsonSerialize returns status array', function () {
    $r = new ReturnOrderResponse('OK');
    expect($r->jsonSerialize())->toBe(['status' => 'OK']);
});

it('ReturnOrderResponse __toString returns json', function () {
    $r = new ReturnOrderResponse('OK');
    expect((string) $r)->toBe('{"status":"OK"}');
});

it('ReturnOrderResponse is json_encodable', function () {
    $r = new ReturnOrderResponse('OK');
    expect(json_encode($r))->toBe('{"status":"OK"}');
});
```

- [ ] **Step 2: Run tests to verify new ones fail**

Run: `./vendor/bin/pest tests/Unit/Responses/ResponseSerializationTest.php`
Expected: existing tests pass, new ones fail with class not found.

- [ ] **Step 3: Create the response**

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class ReturnOrderResponse implements \JsonSerializable
{
    public function __construct(
        public string $status,
    ) {}

    public function jsonSerialize(): array
    {
        return ['status' => $this->status];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
```

- [ ] **Step 4: Run tests to verify all pass**

Run: `./vendor/bin/pest tests/Unit/Responses/ResponseSerializationTest.php`
Expected: all pass (previously passing + 4 new).

- [ ] **Step 5: Commit**

```bash
git add src/Responses/ReturnOrderResponse.php tests/Unit/Responses/ResponseSerializationTest.php
git commit -m "feat: add ReturnOrderResponse"
```

---

### Task 5: `returnOrder()` service method

**Files:**
- Modify: `src/MonobankInstallments.php` — add method + imports
- Modify: `tests/Feature/MonobankInstallmentsTest.php` — append feature tests

- [ ] **Step 1: Append the failing feature tests**

Add to the bottom of `tests/Feature/MonobankInstallmentsTest.php`:

```php
use Rilong\MonobankInstallments\DTOs\ReturnAdditionalParamsDTO;
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;
use Rilong\MonobankInstallments\Responses\ReturnOrderResponse;

it('returnOrder() returns ReturnOrderResponse with status OK', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);

    $dto = new ReturnOrderDTO(
        orderId: 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        sum: 1250.5,
        storeReturnId: 'RET-12345',
        returnMoneyTo: ReturnMoneyTo::Card,
        additionalParams: new ReturnAdditionalParamsDTO(nds: 208.42),
    );

    $response = (new MonobankInstallments())->returnOrder($dto);

    expect($response)->toBeInstanceOf(ReturnOrderResponse::class)
        ->and($response->status)->toBe('OK');
});

it('returnOrder() posts to /api/order/return', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);

    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
    );

    (new MonobankInstallments())->returnOrder($dto);

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/return'));
});

it('returnOrder() sends correct payload', function () {
    Http::fake(['*' => Http::response(['status' => 'OK'], 200)]);

    $dto = new ReturnOrderDTO(
        orderId: 'fa4a8249-336e-4e6d-9b85-79bc8be62377',
        sum: 1250.5,
        storeReturnId: 'RET-12345',
        returnMoneyTo: ReturnMoneyTo::Card,
        additionalParams: new ReturnAdditionalParamsDTO(nds: 208.42),
    );

    (new MonobankInstallments())->returnOrder($dto);

    Http::assertSent(function ($req) {
        $body = json_decode($req->body(), true);
        return $body['order_id'] === 'fa4a8249-336e-4e6d-9b85-79bc8be62377'
            && $body['sum'] == 1250.5
            && $body['store_return_id'] === 'RET-12345'
            && $body['return_money_to_card'] === true
            && $body['additional_params']['nds'] == 208.42;
    });
});

it('returnOrder() throws MonobankInstallmentsException on API error', function () {
    Http::fake(['*' => Http::response(['message' => 'Order not found'], 404)]);

    $dto = new ReturnOrderDTO(
        orderId: 'uuid',
        sum: 100.0,
        storeReturnId: 'RET-1',
        returnMoneyTo: ReturnMoneyTo::Card,
    );

    expect(fn() => (new MonobankInstallments())->returnOrder($dto))
        ->toThrow(MonobankInstallmentsException::class, 'Order not found');
});
```

- [ ] **Step 2: Run tests to verify new ones fail**

Run: `./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php`
Expected: existing tests pass, new ones fail with method not found.

- [ ] **Step 3: Add the method to `MonobankInstallments`**

Add these imports to `src/MonobankInstallments.php` (after the existing `use` statements):

```php
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Responses\ReturnOrderResponse;
```

Add this method inside the class (after `cancelOrder()`):

```php
public function returnOrder(ReturnOrderDTO $dto): ReturnOrderResponse
{
    $data = $this->client->post('return', $dto->toArray());

    return new ReturnOrderResponse($data['status']);
}
```

- [ ] **Step 4: Run the full test suite to verify everything passes**

Run: `./vendor/bin/pest`
Expected: all tests pass, no failures.

- [ ] **Step 5: Commit**

```bash
git add src/MonobankInstallments.php tests/Feature/MonobankInstallmentsTest.php
git commit -m "feat: add returnOrder method for POST /api/order/return"
```
