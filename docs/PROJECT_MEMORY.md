# Project Memory — AutoSync Hub

## Product Vision

AutoSync Hub demonstrates a professional backend and integration architecture for automotive inventory synchronization. The system manages vehicle records in Laravel and prepares a clear path for a future Go hub that will publish vehicles to marketplaces such as OLX, Mercado Livre, and iCarros.

## Current Scope

- Laravel monorepo foundation under `apps/backend-laravel`.
- Vehicle CRUD API.
- Integration log model and history API.
- Simulated vehicle synchronization with provider-level results.
- Go Integration Hub foundation with sync request endpoint and simulated providers.
- Callback endpoint for future hub status updates.
- Blade screens for listing vehicles and showing integration history.
- Demo seed data.
- Feature tests for main API flows.
- Documentation for architecture, integration flow, API contracts, and presentation.

## Out of Scope

- Real marketplace API calls.
- OAuth or marketplace credential handling.
- Scraping.
- Real provider implementation in Go.
- Authentication and user management.
- Production Docker setup.
- Queue workers and retry orchestration.

## Architecture Decisions

- Use a monorepo so the Laravel backend and future Go hub can evolve together while keeping their responsibilities explicit.
- Start with Laravel because the target opportunity focuses on Laravel and the first phase needs CRUD, validation, migrations, API routes, and simple screens.
- Keep the Go service focused on provider execution and callbacks while Laravel remains the canonical business backend.
- Use PHP enums for vehicle statuses, providers, operations, and integration statuses so accepted values are explicit and easy to validate.
- Place sync behavior in `VehicleSyncService` and hub communication in `IntegrationHubClient` so controllers remain thin.
- Simulate provider results through `IntegrationHubClient` as a temporary adapter point that can later become a real HTTP client.
- Store integration logs as append-friendly operational records because marketplace syncs need traceability.
- Use a canonical vehicle model in Laravel and leave provider-specific transformation for the future Go adapters.

## Domain Concepts

- Vehicle: canonical automotive inventory record managed by Laravel.
- Marketplace: external sales platform such as OLX, Mercado Livre, or iCarros.
- Integration: the process of publishing, updating, deleting, or checking status with a marketplace.
- Integration Log: persisted record of a sync attempt or callback update.
- Provider: a specific marketplace integration target.
- Sync Status: current result of an integration event, such as pending, processing, published, failed, rejected, or requires action.
- Callback/Webhook: future message from the Go hub back to Laravel with a provider status update.

## Current Implementation Status

- Monorepo folders created.
- Laravel skeleton files added manually because PHP and Composer were not available in the execution environment.
- Vehicle and IntegrationLog models, migrations, factories, and seeders created.
- API controllers, requests, resources, routes, and services created.
- Simple Blade pages created for `/vehicles` and `/vehicles/{vehicle}`.
- Feature tests created for create/list/sync/callback/history flows.
- Root README and docs created.
- Local execution was not verified in this environment because `php` and `composer` commands are unavailable.
- Added `docs/LARAVEL_FOR_GO_DEVELOPERS.md` as a learning guide that explains this Laravel project through comparisons with common Go backend patterns.
- Added `docs/DEVELOPMENT_ROADMAP.md` to track phases, resume points, completion criteria, and next steps.
- Completed Phase 2 runtime validation on 2026-07-09 using Docker image `composer:2`: dependencies installed, migrations and seeders ran, tests passed, and web/API routes were manually checked.
- Updated Laravel dependency from `^11.0` to `^12.0` because Composer blocked Laravel 11 releases due to security advisories.
- Added `mockery/mockery` to `require-dev` because Laravel feature tests required Mockery during teardown.
- Completed Phase 3 integration hardening on 2026-07-09: added provider summary endpoint, detail-page summary display, invalid payload tests, callback not-found handling tests, and curl examples in API contracts.
- Completed Phase 4 Laravel-Go contract design on 2026-07-09: added `docs/LARAVEL_GO_CONTRACT.md`, integration config for timeout/token/callback/contract version, and simulated contract metadata in `IntegrationHubClient`.
- Completed Phase 5 Go Integration Hub foundation on 2026-07-09: added a Go HTTP service with `/healthz`, `/sync-requests`, simulated providers, callback dispatcher, validation, tests, Docker Compose integration, and manual HTTP validation on `PORT=18080`.
- Completed Phase 6 Laravel-Go HTTP integration on 2026-07-09: `IntegrationHubClient` now calls `POST /sync-requests`, persists provider results returned by Go, records failed logs on hub rejection/unavailability, and was manually validated end-to-end with Laravel on `PORT=18000` and Go on `PORT=18080`.
- Switched the development database target from SQLite to PostgreSQL on 2026-07-09. SQLite remains configured for PHPUnit in-memory tests only.
- The Compose PostgreSQL service is exposed on host port `5433` and keeps port `5432` only inside the Docker network to avoid conflicts with local PostgreSQL installations.
- Started Phase 7 on 2026-07-09: the Go hub now has a `ProviderAdapter` boundary, registers simulated adapters per provider, and rejects supported providers when no adapter is configured.
- Continued Phase 7 on 2026-07-09: Go provider adapter errors now use configurable retries/backoff, and Laravel callbacks require `Authorization: Bearer <token>` when `INTEGRATION_HUB_TOKEN` is configured.

## Pending Tasks

- Continue Phase 7 from `docs/DEVELOPMENT_ROADMAP.md`.
- Add basic observability for provider calls and callbacks.
- Document limits before integrating real marketplace APIs.
- Keep PostgreSQL as the default development database and use SQLite only for isolated automated tests.
- Add authentication and webhook security before any production use.
- Improve Docker setup with a Composer-capable PHP image or custom Dockerfile.

## Important Commands

```bash
docker compose up -d postgres
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan test
```

```bash
cd apps/integration-hub-go
GOCACHE=/tmp/autosync-go-cache go test ./...
GOCACHE=/tmp/autosync-go-cache go run ./cmd/api
```

## Presentation Notes

- Emphasize that the current phase is intentionally honest: it demonstrates contracts and flow without fake claims of real marketplace integration.
- Show how Laravel owns the canonical domain and status visibility.
- Show how Laravel calls the Go hub through `IntegrationHubClient`.
- Show the Go hub endpoint `POST /sync-requests` as the integration boundary.
- Explain that enums and form requests make provider/status values explicit and reviewable.
- Use seeded vehicles to quickly demonstrate list, detail, sync, logs, and callback behavior.
