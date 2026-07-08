# AutoSync Hub

AutoSync Hub is a demonstrative backend and integrations project for automotive inventory synchronization. It shows how a Laravel backend can manage vehicles, request marketplace synchronization, receive provider status updates, and stay prepared for a future Go integration hub.

The first implementation phase focuses on `apps/backend-laravel`. The Go service is intentionally represented only by a placeholder because real providers such as OLX, Mercado Livre, and iCarros usually require credentials, approval flows, OAuth, and provider-specific contracts.

## Why Laravel and Go

Laravel is the main application backend: it owns vehicle registration, the public API, presentation screens, and integration status storage.

Go will later become the integration hub: a focused service for provider adapters, retries, external API calls, normalization, and callback delivery back to Laravel.

This split keeps the Laravel application clean while still showing a realistic path for scaling integration workloads.

## Repository Structure

```text
autosync_hub/
  apps/
    backend-laravel/
    integration-hub-go/
  docs/
```

## Current Features

- Vehicle CRUD API.
- Vehicle sync endpoint with simulated provider results.
- Integration log history by provider and operation.
- Callback endpoint prepared for future Go hub updates.
- Simple Blade screens for listing and inspecting vehicles.
- Demo seed data for vehicles and integration logs.
- Feature tests for the main API flows.

## Requirements

- PHP 8.2 or newer.
- Composer.
- SQLite, MySQL, or PostgreSQL. SQLite is enough for the demo.

## Setup

```bash
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Open:

- Web panel: `http://127.0.0.1:8000/vehicles`
- API base: `http://127.0.0.1:8000/api`

## Tests

```bash
cd apps/backend-laravel
php artisan test
```

## Main Endpoints

- `GET /api/vehicles`
- `POST /api/vehicles`
- `GET /api/vehicles/{vehicle}`
- `PUT /api/vehicles/{vehicle}`
- `DELETE /api/vehicles/{vehicle}`
- `POST /api/vehicles/{vehicle}/sync`
- `GET /api/vehicles/{vehicle}/integration-logs`
- `POST /api/integration-callbacks`

## Status

This phase delivers a clean Laravel foundation with simulated integration behavior. The next phase is to implement `apps/integration-hub-go` and replace the simulated client with real HTTP calls to that service.

See `docs/` for architecture, API contracts, integration flow, presentation guidance, and the living project memory.
