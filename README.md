# monobank-installments

🇺🇦 [Українська версія нижче](#monobank-installments-українська)

💳 A Laravel package for integrating [Monobank Parts](https://monobank.ua/api-docs/chast) installment payments into your application — create orders, track their state, confirm delivery, handle returns, and more.

Let your customers pay in installments via Monobank while keeping your integration clean and fully typed. Every request is automatically signed with HMAC-SHA256, all inputs and outputs are readonly DTOs, and the entire API surface is available through a single Laravel Facade.

**Features:**
- 🔐 Automatic HMAC-SHA256 request signing — no manual auth setup
- 📦 Fully typed DTOs for all inputs and responses
- 🏪 Facade + service container support
- ⚡ Works with Laravel's package auto-discovery
- 🔄 Full order lifecycle: create → confirm → return
- 🧪 Tested with Pest + Orchestra Workbench

## Requirements

- PHP 8.2+
- Laravel 12 or 13

## Installation

```bash
composer require rilong/monobank-installments
```

The service provider and facade are registered automatically via Laravel's package discovery.

## Configuration

Call `MonobankInstallments::configure()` before making any API calls — typically in a service provider's `boot()` method or in your application's bootstrap code.

**Option 1 — via `.env`:**

Add credentials to your `.env`:

```
MONOBANK_STORE_ID=your-store-id
MONOBANK_STORE_SECRET=your-store-secret
```

Then configure in `AppServiceProvider::boot()`:

```php
use Rilong\MonobankInstallments\MonobankInstallments;

MonobankInstallments::configure(
    storeId: env('MONOBANK_STORE_ID'),
    storeSecret: env('MONOBANK_STORE_SECRET'),
);
```

**Option 2 — via a config file:**

Create a config file (e.g. `config/monobank.php`):

```php
return [
    'store_id'     => env('MONOBANK_STORE_ID'),
    'store_secret' => env('MONOBANK_STORE_SECRET'),
];
```

Then configure using it:

```php
MonobankInstallments::configure(
    storeId: config('monobank.store_id'),
    storeSecret: config('monobank.store_secret'),
    baseUrl: config('monobank.base_url'), // optional, defaults to 'https://u2.monobank.com.ua'
);
```

## Usage

All methods are available via the facade:

```php
use Rilong\MonobankInstallments\Facades\MonobankInstallments;
```

### Create order

Initiates a new installment order for a client. Returns an `orderId` that you use in all subsequent calls for this order.

`CreateOrderDTO` fields:

| Field | Type | Required | Description |
|---|---|---|---|
| `storeOrderId` | `string` | yes | Your internal order ID |
| `clientPhone` | `string` | yes | Client phone in `+380XXXXXXXXX` format |
| `totalSum` | `float` | yes | Total order amount |
| `invoice` | `InvoiceDTO` | yes | Invoice number, date, source channel, and optional point ID |
| `products` | `ProductDTO[]` | yes | List of products in the order |
| `availablePrograms` | `AvailableProgramDTO[]` | yes | Installment programs to offer the client |
| `resultCallback` | `string` | no | URL Monobank will POST the result to |
| `additionalParams` | `AdditionalParamsDTO` | no | Seller phone, VAT, external initial sum |
| `financialCompanyMerchantInfo` | `FinancialCompanyMerchantInfoDTO` | no | Financial company merchant details |

`InvoiceDTO` fields:

| Field | Type | Required | Description |
|---|---|---|---|
| `number` | `string` | yes | Invoice number |
| `date` | `string` | yes | Invoice date (`YYYY-MM-DD`) |
| `source` | `string` | yes | Payment channel (e.g. `INTERNET`, `TERMINAL`) |
| `pointId` | `string` | no | Store point identifier |

```php
use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\InvoiceDTO;
use Rilong\MonobankInstallments\DTOs\ProductDTO;
use Rilong\MonobankInstallments\Facades\MonobankInstallments;

$response = MonobankInstallments::createOrder(new CreateOrderDTO(
    storeOrderId: 'order-123',
    clientPhone: '+380991234561',
    totalSum: 12000.00,
    invoice: new InvoiceDTO(
        number: 'INV-001',
        date: '2024-01-15',
        source: 'INTERNET',
    ),
    products: [
        new ProductDTO(name: 'Laptop', count: 1, sum: 12000.00),
    ],
    availablePrograms: [
        new AvailableProgramDTO(type: 'payment_installments', availablePartsCount: [3, 6, 12]),
    ],
    resultCallback: 'https://example.com/callback',
));

echo $response->orderId;
```

### Get order state

Returns the current state of an order. Poll this endpoint after order creation to track the client's progress through the installment flow.

```php
$response = MonobankInstallments::getState('order-id');

echo $response->orderId;
echo $response->state->value;        // SUCCESS | FAIL | IN_PROCESS
echo $response->orderSubState->value;
echo $response->message;             // nullable, human-readable status detail
```

### Confirm order

Confirms the order on the store side. Call this once the order has been shipped to the client. Returns an `OrderResponse` with the updated state.

```php
$response = MonobankInstallments::confirmOrder('order-id');

echo $response->state->value;
```

### Cancel order

Rejects an open order before it is confirmed (i.e. before shipment). Use this if the client changes their mind or the goods are no longer available. Returns an `OrderResponse` with the updated state.

```php
$response = MonobankInstallments::cancelOrder('order-id');

echo $response->state->value;
```

### Return order

Creates a full or partial return for a previously confirmed order. `ReturnMoneyTo::Card` sends the refund to the client's card; `ReturnMoneyTo::Cash` marks it as a cash refund.

```php
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;

$response = MonobankInstallments::returnOrder(new ReturnOrderDTO(
    orderId: 'order-id',
    sum: 12000.00,
    storeReturnId: 'return-123',
    returnMoneyTo: ReturnMoneyTo::Card,
));

echo $response->status;
```

### Get order data

Fetches full information about an order including return history. Useful for reconciling operations, displaying order status in a client dashboard, analytics, reporting, and processing returns.

Returns:
- Basic order data (amount, status, dates)
- Client information
- Installment details (number of payments, amounts)
- Full return history with dates and amounts
- Current outstanding balance

```php
$response = MonobankInstallments::getOrderData('order-id');

echo $response->totalSum;
echo $response->invoiceNumber;
echo $response->maskedCard;
echo $response->iban;

foreach ($response->reverseList as $reverseItem) {
    echo $reverseItem->sum;
    echo $reverseItem->timestamp;
}
```

### Check paid status

Checks whether the client has fully paid off their installments, and whether the bank can issue a card refund if a return is needed.

```php
$response = MonobankInstallments::checkPaid('order-id');

$response->fullyPaid;                 // true once all installments are settled
$response->bankCanReturnMoneyToCard;  // true if a card refund is available
```

## Response types

| Method | Response class | Key properties |
|---|---|---|
| `createOrder` | `CreateOrderResponse` | `orderId` |
| `getState` | `OrderResponse` | `orderId`, `state`, `orderSubState`, `message` |
| `confirmOrder` | `OrderResponse` | same as above |
| `cancelOrder` | `OrderResponse` | same as above |
| `returnOrder` | `ReturnOrderResponse` | `status` |
| `getOrderData` | `OrderDataResponse` | full order details |
| `checkPaid` | `CheckPaidResponse` | `fullyPaid`, `bankCanReturnMoneyToCard` |

All response classes implement `JsonSerializable` and `__toString()`.

### OrderResponse

`OrderResponse` is returned by `getState()`, `confirmOrder()`, and `cancelOrder()`. It represents the current state of an order at the time of the call.

| Property | Type | Description |
|---|---|---|
| `orderId` | `string` | Monobank-generated order ID |
| `state` | `OrderState` | Top-level order state (`IN_PROCESS`, `SUCCESS`, `FAIL`) |
| `orderSubState` | `OrderSubState` | Detailed sub-state — use this to determine the exact situation |
| `message` | `string\|null` | Optional human-readable message from Monobank |

```php
$response = MonobankInstallments::getState('order-id');

match ($response->orderSubState) {
    OrderSubState::WaitingForStoreConfirm => MonobankInstallments::confirmOrder($response->orderId),
    OrderSubState::RejectedByClient       => // notify the customer,
    OrderSubState::Done                   => // order fully complete,
    default                               => // handle other states,
};
```

> **Tip:** Always branch on `orderSubState`, not `state`. Two orders can share the same `state` (e.g. `FAIL`) but require different handling depending on the sub-state.

### Order states

**`OrderState`**

| Value | Meaning |
|---|---|
| `IN_PROCESS` | Order is in progress |
| `SUCCESS` | Order completed successfully |
| `FAIL` | Order failed |

**`OrderSubState`**

| Value | Meaning |
|---|---|
| `ACTIVE` | Order is active |
| `DONE` | Order is fully done |
| `RETURNED` | Order has been returned |
| `WAITING_FOR_CLIENT` | Awaiting client action |
| `WAITING_FOR_STORE_CONFIRM` | Awaiting store confirmation (call `confirmOrder`) |
| `CLIENT_NOT_FOUND` | Client not found in Monobank |
| `EXCEEDED_SUM_LIMIT` | Order amount exceeds client's limit |
| `PAY_PARTS_ARE_NOT_ACCEPTABLE` | Selected installment plan not available |
| `EXISTS_OTHER_OPEN_ORDER` | Client already has an open order |
| `NOT_ENOUGH_MONEY_FOR_INIT_DEBIT` | Insufficient funds for initial debit |
| `CLIENT_PUSH_TIMEOUT` | Client did not respond in time |
| `FRAUD_REJECTED` | Rejected by fraud detection |
| `REJECTED_BY_CLIENT` | Client declined the offer |
| `REJECTED_BY_STORE` | Rejected by the store |
| `RESTRICTED_BY_RISKS` | Blocked by risk rules |
| `FAIL` | General failure |

## Exceptions

All API errors throw `Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException`, which exposes a `statusCode` property alongside the standard exception message.

```php
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;

try {
    $response = MonobankInstallments::getState('order-id');
} catch (MonobankInstallmentsException $e) {
    echo $e->statusCode;
    echo $e->getMessage();
}
```

## License

MIT

---

# monobank-installments (Українська)

💳 Laravel-пакет для інтеграції [Monobank оплати частинами](https://monobank.ua/api-docs/chast) (оплата частинами) у ваш застосунок — створюйте заявки, відстежуйте їх статус, підтверджуйте доставку, обробляйте повернення та багато іншого.

Дозвольте вашим клієнтам платити частинами через Monobank, зберігаючи інтеграцію чистою та повністю типізованою. Кожен запит автоматично підписується за допомогою HMAC-SHA256, всі вхідні та вихідні дані — readonly DTO, а весь API доступний через єдиний Laravel Facade.

**Можливості:**
- 🔐 Автоматичне підписування запитів HMAC-SHA256 — без ручного налаштування авторизації
- 📦 Повністю типізовані DTO для всіх вхідних і вихідних даних
- 🏪 Підтримка Facade + service container
- ⚡ Працює з автовідкриттям пакетів Laravel
- 🔄 Повний життєвий цикл заявки: створення → підтвердження → повернення
- 🧪 Протестовано з Pest + Orchestra Workbench

## Вимоги

- PHP 8.2+
- Laravel 12 або 13

## Встановлення

```bash
composer require rilong/monobank-installments
```

Сервіс-провайдер та фасад реєструються автоматично через механізм автовідкриття пакетів Laravel.

## Налаштування

Викличте `MonobankInstallments::configure()` перед будь-якими API-запитами — зазвичай у методі `boot()` сервіс-провайдера або під час завантаження застосунку.

**Варіант 1 — через `.env`:**

Додайте облікові дані до `.env`:

```
MONOBANK_STORE_ID=your-store-id
MONOBANK_STORE_SECRET=your-store-secret
```

Потім налаштуйте у `AppServiceProvider::boot()`:

```php
use Rilong\MonobankInstallments\MonobankInstallments;

MonobankInstallments::configure(
    storeId: env('MONOBANK_STORE_ID'),
    storeSecret: env('MONOBANK_STORE_SECRET'),
);
```

**Варіант 2 — через файл конфігурації:**

Створіть файл конфігурації (наприклад, `config/monobank.php`):

```php
return [
    'store_id'     => env('MONOBANK_STORE_ID'),
    'store_secret' => env('MONOBANK_STORE_SECRET'),
];
```

Потім використайте його:

```php
MonobankInstallments::configure(
    storeId: config('monobank.store_id'),
    storeSecret: config('monobank.store_secret'),
    baseUrl: config('monobank.base_url'), // необов'язково, за замовчуванням 'https://u2.monobank.com.ua'
);
```

## Використання

Всі методи доступні через фасад:

```php
use Rilong\MonobankInstallments\Facades\MonobankInstallments;
```

### Створення заявки

Ініціює нову заявку на оплату частинами для клієнта. Повертає `orderId`, який використовується у всіх наступних запитах для цієї заявки.

Поля `CreateOrderDTO`:

| Поле | Тип | Обов'язкове | Опис |
|---|---|---|---|
| `storeOrderId` | `string` | так | Внутрішній ID заявки магазину |
| `clientPhone` | `string` | так | Телефон клієнта у форматі `+380XXXXXXXXX` |
| `totalSum` | `float` | так | Загальна сума замовлення |
| `invoice` | `InvoiceDTO` | так | Номер, дата, канал прийому платежу та необов'язковий ID точки продажу |
| `products` | `ProductDTO[]` | так | Список товарів у замовленні |
| `availablePrograms` | `AvailableProgramDTO[]` | так | Програми розстрочки для клієнта |
| `resultCallback` | `string` | ні | URL для POST-сповіщення від Monobank |
| `additionalParams` | `AdditionalParamsDTO` | ні | Телефон продавця, ПДВ, зовнішня початкова сума |
| `financialCompanyMerchantInfo` | `FinancialCompanyMerchantInfoDTO` | ні | Дані мерчанта фінансової компанії |

Поля `InvoiceDTO`:

| Поле | Тип | Обов'язкове | Опис |
|---|---|---|---|
| `number` | `string` | так | Номер накладної |
| `date` | `string` | так | Дата накладної (`YYYY-MM-DD`) |
| `source` | `string` | так | Канал прийому платежу (наприклад, `INTERNET`, `TERMINAL`) |
| `pointId` | `string` | ні | Ідентифікатор точки продажу |

```php
use Rilong\MonobankInstallments\DTOs\AvailableProgramDTO;
use Rilong\MonobankInstallments\DTOs\CreateOrderDTO;
use Rilong\MonobankInstallments\DTOs\InvoiceDTO;
use Rilong\MonobankInstallments\DTOs\ProductDTO;
use Rilong\MonobankInstallments\Facades\MonobankInstallments;

$response = MonobankInstallments::createOrder(new CreateOrderDTO(
    storeOrderId: 'order-123',
    clientPhone: '+380991234561',
    totalSum: 12000.00,
    invoice: new InvoiceDTO(
        number: 'INV-001',
        date: '2024-01-15',
        source: 'INTERNET',
    ),
    products: [
        new ProductDTO(name: 'Ноутбук', count: 1, sum: 12000.00),
    ],
    availablePrograms: [
        new AvailableProgramDTO(type: 'payment_installments', availablePartsCount: [3, 6, 12]),
    ],
    resultCallback: 'https://example.com/callback',
));

echo $response->orderId;
```

### Отримання статусу заявки

Повертає поточний статус заявки. Опитуйте цей метод після створення заявки для відстеження прогресу клієнта.

```php
$response = MonobankInstallments::getState('order-id');

echo $response->orderId;
echo $response->state->value;        // SUCCESS | FAIL | IN_PROCESS
echo $response->orderSubState->value;
echo $response->message;             // nullable, деталі статусу
```

### Підтвердження заявки

Підтверджує заявку зі сторони магазину. Викликайте після відправки товару клієнту. Повертає `OrderResponse` з оновленим статусом.

```php
$response = MonobankInstallments::confirmOrder('order-id');

echo $response->state->value;
```

### Скасування заявки

Відхиляє відкриту заявку до її підтвердження (тобто до відправки товару). Використовуйте, якщо клієнт передумав або товар недоступний. Повертає `OrderResponse` з оновленим статусом.

```php
$response = MonobankInstallments::cancelOrder('order-id');

echo $response->state->value;
```

### Повернення заявки

Створює повне або часткове повернення для раніше підтвердженої заявки. `ReturnMoneyTo::Card` — повернення на картку клієнта; `ReturnMoneyTo::Cash` — готівкове повернення.

```php
use Rilong\MonobankInstallments\DTOs\ReturnOrderDTO;
use Rilong\MonobankInstallments\Enums\ReturnMoneyTo;

$response = MonobankInstallments::returnOrder(new ReturnOrderDTO(
    orderId: 'order-id',
    sum: 12000.00,
    storeReturnId: 'return-123',
    returnMoneyTo: ReturnMoneyTo::Card,
));

echo $response->status;
```

### Отримання даних заявки

Отримує повну інформацію про заявку, включаючи історію повернень. Корисно для звірки операцій, відображення статусу в особистому кабінеті, аналітики, звітності та обробки повернень.

Повертає:
- Базові дані заявки (сума, статус, дати)
- Інформацію про клієнта
- Деталі ПЧ (кількість платежів, суми)
- Повну історію повернень з датами та сумами
- Поточний залишок заборгованості

```php
$response = MonobankInstallments::getOrderData('order-id');

echo $response->totalSum;
echo $response->invoiceNumber;
echo $response->maskedCard;
echo $response->iban;

foreach ($response->reverseList as $reverseItem) {
    echo $reverseItem->sum;
    echo $reverseItem->timestamp;
}
```

### Перевірка статусу оплати

Перевіряє, чи клієнт повністю погасив розстрочку, та чи може банк повернути кошти на картку у разі повернення.

```php
$response = MonobankInstallments::checkPaid('order-id');

$response->fullyPaid;                 // true, якщо всі платежі погашено
$response->bankCanReturnMoneyToCard;  // true, якщо доступне повернення на картку
```

## Типи відповідей

| Метод | Клас відповіді | Основні поля |
|---|---|---|
| `createOrder` | `CreateOrderResponse` | `orderId` |
| `getState` | `OrderResponse` | `orderId`, `state`, `orderSubState`, `message` |
| `confirmOrder` | `OrderResponse` | аналогічно до вище |
| `cancelOrder` | `OrderResponse` | аналогічно до вище |
| `returnOrder` | `ReturnOrderResponse` | `status` |
| `getOrderData` | `OrderDataResponse` | повні дані заявки |
| `checkPaid` | `CheckPaidResponse` | `fullyPaid`, `bankCanReturnMoneyToCard` |

Всі класи відповідей реалізують `JsonSerializable` та `__toString()`.

### OrderResponse

`OrderResponse` повертається методами `getState()`, `confirmOrder()` та `cancelOrder()`. Представляє поточний стан заявки на момент виклику.

| Властивість | Тип | Опис |
|---|---|---|
| `orderId` | `string` | ID заявки, згенерований Monobank |
| `state` | `OrderState` | Загальний статус заявки (`IN_PROCESS`, `SUCCESS`, `FAIL`) |
| `orderSubState` | `OrderSubState` | Детальний підстатус — використовуйте його для визначення конкретної ситуації |
| `message` | `string\|null` | Необов'язкове повідомлення від Monobank |

```php
$response = MonobankInstallments::getState('order-id');

match ($response->orderSubState) {
    OrderSubState::WaitingForStoreConfirm => MonobankInstallments::confirmOrder($response->orderId),
    OrderSubState::RejectedByClient       => // сповістити клієнта,
    OrderSubState::Done                   => // заявка повністю завершена,
    default                               => // обробка інших статусів,
};
```

> **Порада:** Завжди перевіряйте `orderSubState`, а не `state`. Дві заявки можуть мати однаковий `state` (наприклад, `FAIL`), але потребувати різної обробки залежно від підстатусу.

### Статуси заявки

**`OrderState`**

| Значення | Опис |
|---|---|
| `IN_PROCESS` | Заявка в обробці |
| `SUCCESS` | Заявка успішно завершена |
| `FAIL` | Заявка відхилена |

**`OrderSubState`**

| Значення | Опис |
|---|---|
| `ACTIVE` | Заявка активна |
| `DONE` | Заявка повністю завершена |
| `RETURNED` | Заявку повернено |
| `WAITING_FOR_CLIENT` | Очікування дії від клієнта |
| `WAITING_FOR_STORE_CONFIRM` | Очікування підтвердження магазину (викличте `confirmOrder`) |
| `CLIENT_NOT_FOUND` | Клієнта не знайдено в Monobank |
| `EXCEEDED_SUM_LIMIT` | Сума заявки перевищує ліміт клієнта |
| `PAY_PARTS_ARE_NOT_ACCEPTABLE` | Обраний план розстрочки недоступний |
| `EXISTS_OTHER_OPEN_ORDER` | Клієнт вже має відкриту заявку |
| `NOT_ENOUGH_MONEY_FOR_INIT_DEBIT` | Недостатньо коштів для початкового списання |
| `CLIENT_PUSH_TIMEOUT` | Клієнт не відповів вчасно |
| `FRAUD_REJECTED` | Відхилено системою фрод-моніторингу |
| `REJECTED_BY_CLIENT` | Клієнт відмовився від пропозиції |
| `REJECTED_BY_STORE` | Відхилено магазином |
| `RESTRICTED_BY_RISKS` | Заблоковано ризик-правилами |
| `FAIL` | Загальна помилка |

## Винятки

Всі API-помилки генерують `Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException`, який містить властивість `statusCode` поряд зі стандартним повідомленням про помилку.

```php
use Rilong\MonobankInstallments\Exceptions\MonobankInstallmentsException;

try {
    $response = MonobankInstallments::getState('order-id');
} catch (MonobankInstallmentsException $e) {
    echo $e->statusCode;
    echo $e->getMessage();
}
```

## Ліцензія

MIT
