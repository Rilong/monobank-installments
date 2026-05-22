# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

`rilong/monobank-installments` is a Laravel package (PHP 8.1+, Laravel 12/13) that integrates Monobank Parts (installment payments). The package is in early development — the main class and service provider are stubs.

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

- `src/MonobankInstallments.php` — main service class (currently a stub), registered as a singleton under the `monobank-installments` key
- `src/MonobankInstallmentsProvider.php` — Laravel `ServiceProvider` that binds `MonobankInstallments` into the container; `boot()` is the place to publish config/migrations
- Tests use **Pest** (via `pestphp/pest`); the test harness is **Orchestra Workbench** (`orchestra/workbench`), which bootstraps a minimal Laravel app for package testing
- Namespace: `Rilong\MonobankInstallments\` → `src/`
