# monobank-installments Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a fully typed Laravel package wrapping the Monobank Parts installment API with HMAC-SHA256 signing, a Facade, readonly DTOs, and a single custom exception.

**Architecture:** A thin `MonobankClient` handles HTTP transport and request signing; `MonobankInstallments` is the public service class that calls the client and maps raw responses to typed response DTOs. A Laravel Facade proxies to the singleton registered by the service provider.

**Tech Stack:** PHP 8.1+, Laravel 12/13, Pest, Orchestra Workbench, `illuminate/http`

---

## File Map

| Action | Path | Responsibility |
|--------|------|----------------|
| Create | `src/Exceptions/MonobankInstallmentsException.php` | Single exception with `statusCode` |
| Create | `src/Responses/CreateOrderResponse.php` | Wraps `orderId` from create response |
| Create | `src/Responses/OrderStateResponse.php` | Wraps `orderId`, `state`, `orderSubState` |
| Create | `src/Responses/ConfirmOrderResponse.php` | Wraps `success: true` |
| Create | `src/Responses/CancelOrderResponse.php` | Wraps `success: true` |
| Create | `src/DTOs/InvoiceDTO.php` | Invoice number + date |
| Create | `src/DTOs/ProductDTO.php` | Product name, count, sum |
| Create | `src/DTOs/AvailableProgramDTO.php` | Installment parts count |
| Create | `src/DTOs/CreateOrderDTO.php` | Full order creation payload |
| Create | `src/MonobankClient.php` | HTTP + HMAC-SHA256 signing |
| Modify | `src/MonobankInstallments.php` | `configure()` + 4 API methods |
| Create | `src/Facades/MonobankInstallments.php` | Laravel Facade |
| Modify | `src/MonobankInstallmentsProvider.php` | Singleton registration |
| Modify | `composer.json` | `extra.laravel` auto-discovery |
| Create | `tests/Unit/Exceptions/MonobankInstallmentsExceptionTest.php` | Exception tests |
| Create | `tests/Unit/Responses/ResponseSerializationTest.php` | JSON encoding tests |
| Create | `tests/Unit/DTOs/CreateOrderDTOTest.php` | DTO toArray() tests |
| Create | `tests/Feature/MonobankClientTest.php` | HTTP + signing tests |
| Create | `tests/Feature/MonobankInstallmentsTest.php` | Full integration tests |

---

## Task 1: Exception class

**Files:**
- Create: `src/Exceptions/MonobankInstallmentsException.php`
- Create: `tests/Unit/Exceptions/MonobankInstallmentsExceptionTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Exceptions/MonobankInstallmentsExceptionTest.php`:

```php
<?php

use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;

it('stores status code', function () {
    $e = new MonobankInstallmentsException('Not found', 404);
    expect($e->statusCode)->toBe(404);
});

it('stores message', function () {
    $e = new MonobankInstallmentsException('Unauthorized', 401);
    expect($e->getMessage())->toBe('Unauthorized');
});

it('wraps a previous exception', function () {
    $prev = new RuntimeException('original');
    $e = new MonobankInstallmentsException('Wrapped', 500, $prev);
    expect($e->getPrevious())->toBe($prev);
});

it('is a RuntimeException', function () {
    $e = new MonobankInstallmentsException('error', 500);
    expect($e)->toBeInstanceOf(RuntimeException::class);
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
./vendor/bin/pest tests/Unit/Exceptions/MonobankInstallmentsExceptionTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create the exception class**

Create `src/Exceptions/MonobankInstallmentsException.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Exceptions;

class MonobankInstallmentsException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
./vendor/bin/pest tests/Unit/Exceptions/MonobankInstallmentsExceptionTest.php
```

Expected: 4 tests, 4 passed.

- [ ] **Step 5: Commit**

```bash
git add src/Exceptions/MonobankInstallmentsException.php tests/Unit/Exceptions/MonobankInstallmentsExceptionTest.php
git commit -m "feat: add MonobankInstallmentsException"
```

---

## Task 2: Response DTOs

**Files:**
- Create: `src/Responses/CreateOrderResponse.php`
- Create: `src/Responses/OrderStateResponse.php`
- Create: `src/Responses/ConfirmOrderResponse.php`
- Create: `src/Responses/CancelOrderResponse.php`
- Create: `tests/Unit/Responses/ResponseSerializationTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Responses/ResponseSerializationTest.php`:

```php
<?php

use Rilong\MonobankInstallments\Responses\CancelOrderResponse;
use Rilong\MonobankInstallments\Responses\ConfirmOrderResponse;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderStateResponse;

// --- CreateOrderResponse ---

it('CreateOrderResponse holds orderId', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect($r->orderId)->toBe('uuid-123');
});

it('CreateOrderResponse jsonSerialize returns correct shape', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect($r->jsonSerialize())->toBe(['order_id' => 'uuid-123']);
});

it('CreateOrderResponse __toString returns json', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect((string) $r)->toBe('{"order_id":"uuid-123"}');
});

it('CreateOrderResponse is json_encodable', function () {
    $r = new CreateOrderResponse('uuid-123');
    expect(json_encode($r))->toBe('{"order_id":"uuid-123"}');
});

// --- OrderStateResponse ---

it('OrderStateResponse holds all fields', function () {
    $r = new OrderStateResponse('uuid-1', 'IN_PROCESS', 'WAITING_FOR_CLIENT');
    expect($r->orderId)->toBe('uuid-1')
        ->and($r->state)->toBe('IN_PROCESS')
        ->and($r->orderSubState)->toBe('WAITING_FOR_CLIENT');
});

it('OrderStateResponse jsonSerialize returns snake_case keys', function () {
    $r = new OrderStateResponse('uuid-1', 'SUCCESS', 'DONE');
    expect($r->jsonSerialize())->toBe([
        'order_id'        => 'uuid-1',
        'state'           => 'SUCCESS',
        'order_sub_state' => 'DONE',
    ]);
});

it('OrderStateResponse __toString returns json', function () {
    $r = new OrderStateResponse('uuid-1', 'SUCCESS', 'DONE');
    expect((string) $r)->toBe('{"order_id":"uuid-1","state":"SUCCESS","order_sub_state":"DONE"}');
});

// --- ConfirmOrderResponse ---

it('ConfirmOrderResponse holds success', function () {
    $r = new ConfirmOrderResponse(true);
    expect($r->success)->toBeTrue();
});

it('ConfirmOrderResponse jsonSerialize returns correct shape', function () {
    $r = new ConfirmOrderResponse(true);
    expect($r->jsonSerialize())->toBe(['success' => true]);
});

it('ConfirmOrderResponse __toString returns json', function () {
    $r = new ConfirmOrderResponse(true);
    expect((string) $r)->toBe('{"success":true}');
});

// --- CancelOrderResponse ---

it('CancelOrderResponse holds success', function () {
    $r = new CancelOrderResponse(true);
    expect($r->success)->toBeTrue();
});

it('CancelOrderResponse jsonSerialize returns correct shape', function () {
    $r = new CancelOrderResponse(true);
    expect($r->jsonSerialize())->toBe(['success' => true]);
});

it('CancelOrderResponse __toString returns json', function () {
    $r = new CancelOrderResponse(true);
    expect((string) $r)->toBe('{"success":true}');
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Unit/Responses/ResponseSerializationTest.php
```

Expected: FAIL — classes not found.

- [ ] **Step 3: Create response DTO classes**

Create `src/Responses/CreateOrderResponse.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class CreateOrderResponse implements \JsonSerializable
{
    public function __construct(public string $orderId) {}

    public function jsonSerialize(): array
    {
        return ['order_id' => $this->orderId];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
```

Create `src/Responses/OrderStateResponse.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class OrderStateResponse implements \JsonSerializable
{
    public function __construct(
        public string $orderId,
        public string $state,
        public string $orderSubState,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'order_id'        => $this->orderId,
            'state'           => $this->state,
            'order_sub_state' => $this->orderSubState,
        ];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
```

Create `src/Responses/ConfirmOrderResponse.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class ConfirmOrderResponse implements \JsonSerializable
{
    public function __construct(public bool $success) {}

    public function jsonSerialize(): array
    {
        return ['success' => $this->success];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
```

Create `src/Responses/CancelOrderResponse.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Responses;

readonly class CancelOrderResponse implements \JsonSerializable
{
    public function __construct(public bool $success) {}

    public function jsonSerialize(): array
    {
        return ['success' => $this->success];
    }

    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Unit/Responses/ResponseSerializationTest.php
```

Expected: 12 tests, 12 passed.

- [ ] **Step 5: Commit**

```bash
git add src/Responses/ tests/Unit/Responses/
git commit -m "feat: add response DTOs with JsonSerializable and __toString"
```

---

## Task 3: Input DTOs

**Files:**
- Create: `src/DTOs/InvoiceDTO.php`
- Create: `src/DTOs/ProductDTO.php`
- Create: `src/DTOs/AvailableProgramDTO.php`
- Create: `src/DTOs/CreateOrderDTO.php`
- Create: `tests/Unit/DTOs/CreateOrderDTOTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/DTOs/CreateOrderDTOTest.php`:

```php
<?php

use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\InvoiceDTO;
use Rilong\MonobankInstallments\DTOs\ProductDTO;

function makeCreateOrderDTO(): CreateOrderDTO
{
    return new CreateOrderDTO(
        storeOrderId: 'order-1',
        clientPhone: '+380991234567',
        totalSum: 1000.0,
        invoice: new InvoiceDTO(number: 'INV-001', date: '2026-05-22'),
        products: [new ProductDTO(name: 'Phone', count: 1, sum: 1000.0)],
        availablePrograms: [new AvailableProgramDTO(partsCount: 6)],
        resultCallback: 'https://example.com/callback',
    );
}

it('InvoiceDTO holds number and date', function () {
    $dto = new InvoiceDTO('INV-001', '2026-05-22');
    expect($dto->number)->toBe('INV-001')
        ->and($dto->date)->toBe('2026-05-22');
});

it('InvoiceDTO toArray uses snake_case keys', function () {
    $dto = new InvoiceDTO('INV-001', '2026-05-22');
    expect($dto->toArray())->toBe(['number' => 'INV-001', 'date' => '2026-05-22']);
});

it('ProductDTO holds name, count, sum', function () {
    $dto = new ProductDTO('Phone', 1, 1000.0);
    expect($dto->name)->toBe('Phone')
        ->and($dto->count)->toBe(1)
        ->and($dto->sum)->toBe(1000.0);
});

it('ProductDTO toArray returns correct shape', function () {
    $dto = new ProductDTO('Phone', 1, 1000.0);
    expect($dto->toArray())->toBe(['name' => 'Phone', 'count' => 1, 'sum' => 1000.0]);
});

it('AvailableProgramDTO holds partsCount', function () {
    $dto = new AvailableProgramDTO(6);
    expect($dto->partsCount)->toBe(6);
});

it('AvailableProgramDTO toArray uses snake_case key', function () {
    $dto = new AvailableProgramDTO(6);
    expect($dto->toArray())->toBe(['parts_count' => 6]);
});

it('CreateOrderDTO holds all fields', function () {
    $dto = makeCreateOrderDTO();
    expect($dto->storeOrderId)->toBe('order-1')
        ->and($dto->clientPhone)->toBe('+380991234567')
        ->and($dto->totalSum)->toBe(1000.0)
        ->and($dto->resultCallback)->toBe('https://example.com/callback');
});

it('CreateOrderDTO toArray produces correct API payload', function () {
    $dto = makeCreateOrderDTO();
    expect($dto->toArray())->toBe([
        'store_order_id'     => 'order-1',
        'client_phone'       => '+380991234567',
        'total_sum'          => 1000.0,
        'invoice'            => ['number' => 'INV-001', 'date' => '2026-05-22'],
        'products'           => [['name' => 'Phone', 'count' => 1, 'sum' => 1000.0]],
        'available_programs' => [['parts_count' => 6]],
        'result_callback'    => 'https://example.com/callback',
    ]);
});

it('CreateOrderDTO toArray omits result_callback when null', function () {
    $dto = new CreateOrderDTO(
        storeOrderId: 'order-2',
        clientPhone: '+380991234567',
        totalSum: 500.0,
        invoice: new InvoiceDTO('INV-002', '2026-05-22'),
        products: [new ProductDTO('Item', 1, 500.0)],
        availablePrograms: [new AvailableProgramDTO(3)],
    );
    expect($dto->toArray())->not->toHaveKey('result_callback');
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Unit/DTOs/CreateOrderDTOTest.php
```

Expected: FAIL — classes not found.

- [ ] **Step 3: Create DTO classes**

Create `src/DTOs/InvoiceDTO.php`:

```php
<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class InvoiceDTO
{
    public function __construct(
        public string $number,
        public string $date,
    ) {}

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'date'   => $this->date,
        ];
    }
}
```

Create `src/DTOs/ProductDTO.php`:

```php
<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class ProductDTO
{
    public function __construct(
        public string $name,
        public int    $count,
        public float  $sum,
    ) {}

    public function toArray(): array
    {
        return [
            'name'  => $this->name,
            'count' => $this->count,
            'sum'   => $this->sum,
        ];
    }
}
```

Create `src/DTOs/AvailableProgramDTO.php`:

```php
<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class AvailableProgramDTO
{
    public function __construct(public int $partsCount) {}

    public function toArray(): array
    {
        return ['parts_count' => $this->partsCount];
    }
}
```

Create `src/DTOs/CreateOrderDTO.php`:

```php
<?php

namespace Rilong\MonobankInstallments\DTOs;

readonly class CreateOrderDTO
{
    public function __construct(
        public string  $storeOrderId,
        public string  $clientPhone,
        public float   $totalSum,
        public InvoiceDTO $invoice,
        /** @var ProductDTO[] */
        public array   $products,
        /** @var AvailableProgramDTO[] */
        public array   $availablePrograms,
        public ?string $resultCallback = null,
    ) {}

    public function toArray(): array
    {
        $payload = [
            'store_order_id'     => $this->storeOrderId,
            'client_phone'       => $this->clientPhone,
            'total_sum'          => $this->totalSum,
            'invoice'            => $this->invoice->toArray(),
            'products'           => array_map(fn(ProductDTO $p) => $p->toArray(), $this->products),
            'available_programs' => array_map(fn(AvailableProgramDTO $p) => $p->toArray(), $this->availablePrograms),
        ];

        if ($this->resultCallback !== null) {
            $payload['result_callback'] = $this->resultCallback;
        }

        return $payload;
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Unit/DTOs/CreateOrderDTOTest.php
```

Expected: 9 tests, 9 passed.

- [ ] **Step 5: Commit**

```bash
git add src/DTOs/ tests/Unit/DTOs/
git commit -m "feat: add input DTOs with toArray() serialization"
```

---

## Task 4: MonobankClient

**Files:**
- Create: `src/MonobankClient.php`
- Create: `tests/Feature/MonobankClientTest.php`

Feature tests use the Orchestra Workbench TestCase (already configured in `tests/Pest.php` for the `Feature` directory), giving access to `Http::fake()`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/MonobankClientTest.php`:

```php
<?php

use Illuminate\Support\Facades\Http;
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;
use Rilong\MonobankInstallments\MonobankClient;

it('posts to the correct endpoint URL', function () {
    Http::fake([
        'https://u2.monobank.com.ua/api/order/create' => Http::response(['order_id' => 'abc'], 201),
    ]);

    $client = new MonobankClient('my-store', 'my-secret', 'https://u2.monobank.com.ua');
    $client->post('create', ['store_order_id' => 'order-1']);

    Http::assertSent(fn($req) => $req->url() === 'https://u2.monobank.com.ua/api/order/create');
});

it('sends store-id header', function () {
    Http::fake(['*' => Http::response(['order_id' => 'abc'], 201)]);

    $client = new MonobankClient('my-store', 'my-secret', 'https://u2.monobank.com.ua');
    $client->post('create', ['store_order_id' => 'order-1']);

    Http::assertSent(fn($req) => $req->hasHeader('store-id', 'my-store'));
});

it('sends correct hmac-sha256 signature header', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $storeSecret = 'test-secret';
    $payload = ['store_order_id' => 'order-1'];
    $body = json_encode($payload);
    $expectedSignature = base64_encode(hash_hmac('sha256', $body, $storeSecret, true));

    $client = new MonobankClient('store', $storeSecret, 'https://u2.monobank.com.ua');
    $client->post('create', $payload);

    Http::assertSent(fn($req) => $req->header('signature')[0] === $expectedSignature);
});

it('returns decoded json on success', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid-123'], 201)]);

    $client = new MonobankClient('store', 'secret', 'https://u2.monobank.com.ua');
    $result = $client->post('create', ['store_order_id' => 'order-1']);

    expect($result)->toBe(['order_id' => 'uuid-123']);
});

it('throws MonobankInstallmentsException on 400', function () {
    Http::fake(['*' => Http::response(['message' => 'Validation error'], 400)]);

    $client = new MonobankClient('store', 'secret', 'https://u2.monobank.com.ua');

    expect(fn() => $client->post('create', []))
        ->toThrow(MonobankInstallmentsException::class, 'Validation error');
});

it('throws MonobankInstallmentsException on 401', function () {
    Http::fake(['*' => Http::response(['message' => 'Invalid signature'], 401)]);

    $client = new MonobankClient('store', 'wrong-secret', 'https://u2.monobank.com.ua');

    $e = null;
    try {
        $client->post('create', []);
    } catch (MonobankInstallmentsException $caught) {
        $e = $caught;
    }

    expect($e)->not->toBeNull()
        ->and($e->statusCode)->toBe(401);
});

it('throws MonobankInstallmentsException on 500', function () {
    Http::fake(['*' => Http::response(['message' => 'Server error'], 500)]);

    $client = new MonobankClient('store', 'secret', 'https://u2.monobank.com.ua');

    expect(fn() => $client->post('state', ['order_id' => 'uuid']))
        ->toThrow(MonobankInstallmentsException::class);
});

it('uses custom baseUrl', function () {
    Http::fake([
        'https://u2-demo-ext.mono.st4g3.com/api/order/create' => Http::response(['order_id' => 'sandbox-id'], 201),
    ]);

    $client = new MonobankClient('test_store', 'secret', 'https://u2-demo-ext.mono.st4g3.com');
    $result = $client->post('create', ['store_order_id' => 'order-1']);

    expect($result)->toBe(['order_id' => 'sandbox-id']);
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Feature/MonobankClientTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create MonobankClient**

Create `src/MonobankClient.php`:

```php
<?php

namespace Rilong\MonobankInstallments;

use Illuminate\Support\Facades\Http;
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;

class MonobankClient
{
    public function __construct(
        private readonly string $storeId,
        private readonly string $storeSecret,
        private readonly string $baseUrl,
    ) {}

    public function post(string $endpoint, array $payload): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = base64_encode(hash_hmac('sha256', $body, $this->storeSecret, true));

        $response = Http::withHeaders([
            'store-id'  => $this->storeId,
            'signature' => $signature,
        ])
        ->withBody($body, 'application/json')
        ->post("{$this->baseUrl}/api/order/{$endpoint}");

        if (!$response->successful()) {
            $message = $response->json('message') ?? $response->body();
            throw new MonobankInstallmentsException($message, $response->status());
        }

        return $response->json() ?? [];
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Feature/MonobankClientTest.php
```

Expected: 8 tests, 8 passed.

- [ ] **Step 5: Commit**

```bash
git add src/MonobankClient.php tests/Feature/MonobankClientTest.php
git commit -m "feat: add MonobankClient with HMAC-SHA256 signing"
```

---

## Task 5: MonobankInstallments service

**Files:**
- Modify: `src/MonobankInstallments.php`
- Create: `tests/Feature/MonobankInstallmentsTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/MonobankInstallmentsTest.php`:

```php
<?php

use Illuminate\Support\Facades\Http;
use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\InvoiceDTO;
use Rilong\MonobankInstallments\DTOs\ProductDTO;
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;
use Rilong\MonobankInstallments\MonobankInstallments;
use Rilong\MonobankInstallments\Responses\CancelOrderResponse;
use Rilong\MonobankInstallments\Responses\ConfirmOrderResponse;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderStateResponse;

function makeDTO(): CreateOrderDTO
{
    return new CreateOrderDTO(
        storeOrderId: 'order-1',
        clientPhone: '+380991234567',
        totalSum: 1000.0,
        invoice: new InvoiceDTO('INV-001', '2026-05-22'),
        products: [new ProductDTO('Phone', 1, 1000.0)],
        availablePrograms: [new AvailableProgramDTO(6)],
    );
}

beforeEach(function () {
    MonobankInstallments::configure(storeId: 'test-store', storeSecret: 'test-secret');
});

it('configure() sets storeId and storeSecret used in requests', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid'], 201)]);

    MonobankInstallments::configure(storeId: 'my-store', storeSecret: 'my-secret');
    (new MonobankInstallments())->createOrder(makeDTO());

    Http::assertSent(fn($req) => $req->hasHeader('store-id', 'my-store'));
});

it('configure() sets custom baseUrl', function () {
    Http::fake([
        'https://u2-demo-ext.mono.st4g3.com/api/order/create' => Http::response(['order_id' => 'uuid'], 201),
    ]);

    MonobankInstallments::configure(
        storeId: 'test_store_with_confirm',
        storeSecret: 'secret',
        baseUrl: 'https://u2-demo-ext.mono.st4g3.com'
    );

    (new MonobankInstallments())->createOrder(makeDTO());

    Http::assertSent(fn($req) => str_contains($req->url(), 'u2-demo-ext.mono.st4g3.com'));
});

it('createOrder() returns CreateOrderResponse with orderId', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid-123'], 201)]);

    $response = (new MonobankInstallments())->createOrder(makeDTO());

    expect($response)->toBeInstanceOf(CreateOrderResponse::class)
        ->and($response->orderId)->toBe('uuid-123');
});

it('createOrder() sends correct payload to API', function () {
    Http::fake(['*' => Http::response(['order_id' => 'uuid'], 201)]);

    (new MonobankInstallments())->createOrder(makeDTO());

    Http::assertSent(function ($req) {
        $body = json_decode($req->body(), true);
        return $body['store_order_id'] === 'order-1'
            && $body['client_phone'] === '+380991234567'
            && $body['total_sum'] === 1000.0;
    });
});

it('createOrder() throws MonobankInstallmentsException on API error', function () {
    Http::fake(['*' => Http::response(['message' => 'Validation failed'], 400)]);

    expect(fn() => (new MonobankInstallments())->createOrder(makeDTO()))
        ->toThrow(MonobankInstallmentsException::class, 'Validation failed');
});

it('getState() returns OrderStateResponse', function () {
    Http::fake(['*' => Http::response([
        'order_id'        => 'uuid-123',
        'state'           => 'IN_PROCESS',
        'order_sub_state' => 'WAITING_FOR_CLIENT',
    ], 200)]);

    $response = (new MonobankInstallments())->getState('uuid-123');

    expect($response)->toBeInstanceOf(OrderStateResponse::class)
        ->and($response->orderId)->toBe('uuid-123')
        ->and($response->state)->toBe('IN_PROCESS')
        ->and($response->orderSubState)->toBe('WAITING_FOR_CLIENT');
});

it('getState() sends order_id in payload', function () {
    Http::fake(['*' => Http::response([
        'order_id' => 'uuid-123', 'state' => 'SUCCESS', 'order_sub_state' => 'DONE',
    ], 200)]);

    (new MonobankInstallments())->getState('uuid-123');

    Http::assertSent(fn($req) => json_decode($req->body(), true)['order_id'] === 'uuid-123');
});

it('confirmOrder() returns ConfirmOrderResponse with success true', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $response = (new MonobankInstallments())->confirmOrder('uuid-123');

    expect($response)->toBeInstanceOf(ConfirmOrderResponse::class)
        ->and($response->success)->toBeTrue();
});

it('confirmOrder() posts to /api/order/confirm', function () {
    Http::fake(['*' => Http::response([], 200)]);

    (new MonobankInstallments())->confirmOrder('uuid-123');

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/confirm'));
});

it('cancelOrder() returns CancelOrderResponse with success true', function () {
    Http::fake(['*' => Http::response([], 200)]);

    $response = (new MonobankInstallments())->cancelOrder('uuid-123');

    expect($response)->toBeInstanceOf(CancelOrderResponse::class)
        ->and($response->success)->toBeTrue();
});

it('cancelOrder() posts to /api/order/reject', function () {
    Http::fake(['*' => Http::response([], 200)]);

    (new MonobankInstallments())->cancelOrder('uuid-123');

    Http::assertSent(fn($req) => str_ends_with($req->url(), '/api/order/reject'));
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php
```

Expected: FAIL — `configure()` not defined.

- [ ] **Step 3: Implement MonobankInstallments**

Replace the contents of `src/MonobankInstallments.php`:

```php
<?php

namespace Rilong\MonobankInstallments;

use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\Responses\CancelOrderResponse;
use Rilong\MonobankInstallments\Responses\ConfirmOrderResponse;
use Rilong\MonobankInstallments\Responses\CreateOrderResponse;
use Rilong\MonobankInstallments\Responses\OrderStateResponse;

class MonobankInstallments
{
    private static string $storeId = '';
    private static string $storeSecret = '';
    private static string $baseUrl = 'https://u2.monobank.com.ua';

    private MonobankClient $client;

    public static function configure(
        string $storeId,
        string $storeSecret,
        string $baseUrl = 'https://u2.monobank.com.ua',
    ): void {
        static::$storeId    = $storeId;
        static::$storeSecret = $storeSecret;
        static::$baseUrl    = $baseUrl;
    }

    public function __construct()
    {
        $this->client = new MonobankClient(
            static::$storeId,
            static::$storeSecret,
            static::$baseUrl,
        );
    }

    public function createOrder(CreateOrderDTO $dto): CreateOrderResponse
    {
        $data = $this->client->post('create', $dto->toArray());
        return new CreateOrderResponse($data['order_id']);
    }

    public function getState(string $orderId): OrderStateResponse
    {
        $data = $this->client->post('state', ['order_id' => $orderId]);
        return new OrderStateResponse(
            $data['order_id'],
            $data['state'],
            $data['order_sub_state'],
        );
    }

    public function confirmOrder(string $orderId): ConfirmOrderResponse
    {
        $this->client->post('confirm', ['order_id' => $orderId]);
        return new ConfirmOrderResponse(true);
    }

    public function cancelOrder(string $orderId): CancelOrderResponse
    {
        $this->client->post('reject', ['order_id' => $orderId]);
        return new CancelOrderResponse(true);
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Feature/MonobankInstallmentsTest.php
```

Expected: 11 tests, 11 passed.

- [ ] **Step 5: Run the full test suite to catch regressions**

```bash
./vendor/bin/pest
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add src/MonobankInstallments.php tests/Feature/MonobankInstallmentsTest.php
git commit -m "feat: implement MonobankInstallments service with configure() and all API methods"
```

---

## Task 6: Facade, ServiceProvider & package wiring

**Files:**
- Create: `src/Facades/MonobankInstallments.php`
- Modify: `src/MonobankInstallmentsProvider.php`
- Modify: `composer.json`

No new tests — the Facade proxies to the service already covered in Task 5. The ServiceProvider binding is covered by Orchestra Workbench automatically loading it in Feature tests.

- [ ] **Step 1: Create the Facade**

Create `src/Facades/MonobankInstallments.php`:

```php
<?php

namespace Rilong\MonobankInstallments\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void configure(string $storeId, string $storeSecret, string $baseUrl = 'https://u2.monobank.com.ua')
 * @method static \Rilong\MonobankInstallments\Responses\CreateOrderResponse createOrder(\Rilong\MonobankInstallments\DTOs\CreateOrderDTO $dto)
 * @method static \Rilong\MonobankInstallments\Responses\OrderStateResponse getState(string $orderId)
 * @method static \Rilong\MonobankInstallments\Responses\ConfirmOrderResponse confirmOrder(string $orderId)
 * @method static \Rilong\MonobankInstallments\Responses\CancelOrderResponse cancelOrder(string $orderId)
 */
class MonobankInstallments extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'monobank-installments';
    }
}
```

- [ ] **Step 2: Update MonobankInstallmentsProvider**

Replace the contents of `src/MonobankInstallmentsProvider.php`:

```php
<?php

namespace Rilong\MonobankInstallments;

use Illuminate\Support\ServiceProvider;

class MonobankInstallmentsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('monobank-installments', function () {
            return new MonobankInstallments();
        });
    }

    public function boot(): void {}
}
```

- [ ] **Step 3: Add Laravel auto-discovery to composer.json**

Open `composer.json` and add an `extra` block after the `config` block:

```json
"extra": {
    "laravel": {
        "providers": [
            "Rilong\\MonobankInstallments\\MonobankInstallmentsProvider"
        ],
        "aliases": {
            "MonobankInstallments": "Rilong\\MonobankInstallments\\Facades\\MonobankInstallments"
        }
    }
}
```

The final `composer.json` should look like:

```json
{
    "name": "rilong/monobank-installments",
    "description": "A Laravel helper package for integrating Monobank Parts (installment payments) into your application",
    "type": "library",
    "require": {
        "php": "^8.1",
        "pestphp/pest": "^4.7",
        "illuminate/support": "^12.0|^13.0",
        "orchestra/workbench": "^11.1"
    },
    "license": "MIT",
    "autoload": {
        "psr-4": {
            "Rilong\\MonobankInstallments\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Rilong\\MonobankInstallments\\Tests\\": "tests/"
        }
    },
    "authors": [
        {
            "name": "Roman Hnatiuk",
            "email": "romgnatyuk@gmail.com"
        }
    ],
    "config": {
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Rilong\\MonobankInstallments\\MonobankInstallmentsProvider"
            ],
            "aliases": {
                "MonobankInstallments": "Rilong\\MonobankInstallments\\Facades\\MonobankInstallments"
            }
        }
    }
}
```

- [ ] **Step 4: Run the full test suite**

```bash
./vendor/bin/pest
```

Expected: all tests pass.

- [ ] **Step 5: Commit**

```bash
git add src/Facades/MonobankInstallments.php src/MonobankInstallmentsProvider.php composer.json
git commit -m "feat: add Facade, update ServiceProvider, wire Laravel auto-discovery"
```

---

## Self-Review Checklist

- [x] **Exception** — `MonobankInstallmentsException` with `statusCode`: Task 1 ✓
- [x] **Response DTOs** — all 4, `JsonSerializable` + `__toString`: Task 2 ✓
- [x] **Input DTOs** — all 4 with `toArray()` serialization to snake_case: Task 3 ✓
- [x] **MonobankClient** — HMAC-SHA256 signing, `store-id` header, error mapping: Task 4 ✓
- [x] **`configure(storeId, storeSecret, baseUrl)`** — static, default production URL: Task 5 ✓
- [x] **`createOrder()` / `getState()` / `confirmOrder()` / `cancelOrder()`**: Task 5 ✓
- [x] **Facade** — proxies to `monobank-installments` singleton: Task 6 ✓
- [x] **ServiceProvider** — singleton registration: Task 6 ✓
- [x] **composer.json auto-discovery** — providers + aliases: Task 6 ✓
- [x] **`$orderId` is Monobank-generated ID** — clarified in spec, reflected in test fixtures ✓
- [x] **No placeholders** — all code blocks are complete ✓
- [x] **Type consistency** — `CreateOrderResponse::$orderId`, `OrderStateResponse::$orderId/state/orderSubState` consistent across all tasks ✓
