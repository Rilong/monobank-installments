# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`rilong/monobank-installments` is a Laravel package (PHP 8.2+, Laravel 12/13) that integrates Monobank Parts (installment payments) via HMAC-SHA256 signed HTTP requests.

## Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest

# Run a single test file
./vendor/bin/pest tests/SomeTest.php
```

## Architecture

- `src/MonobankInstallments.php` — public service class with static `configure()` and four API methods: `createOrder()`, `getState()`, `confirmOrder()`, `cancelOrder()`
- `src/MonobankClient.php` — HTTP transport layer; signs every request with HMAC-SHA256 (`store-id` + `signature` headers)
- `src/MonobankInstallmentsProvider.php` — Laravel `ServiceProvider`; registers `MonobankInstallments` as a singleton under `monobank-installments`
- `src/Facades/MonobankInstallments.php` — Laravel Facade proxying to the `monobank-installments` singleton
- `src/DTOs/` — input DTOs (`CreateOrderDTO`, `InvoiceDTO`, `ProductDTO`, `AvailableProgramDTO`) with `toArray()` serialization to snake_case
- `src/Responses/` — readonly response DTOs (`CreateOrderResponse`, `OrderStateResponse`, `ConfirmOrderResponse`, `CancelOrderResponse`) implementing `JsonSerializable`
- `src/Exceptions/MonobankInstallmentsException.php` — single exception with a `statusCode` property
- Namespace: `Rilong\MonobankInstallments\` → `src/`
- Tests use **Pest** (via `pestphp/pest`); the test harness is **Orchestra Workbench** (`orchestra/workbench`), which bootstraps a minimal Laravel app for package testing

## Conventions
### Never commit without explicit user confirmation first.

**Why:** User was surprised by commits happening automatically during plan execution.

**How to apply:** Before every `git commit`, pause and ask the user if they want to commit, showing what will be staged. Wait for their "yes" before proceeding.
