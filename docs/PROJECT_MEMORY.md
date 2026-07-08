# Project Memory — AutoSync Hub

## Product Vision

AutoSync Hub demonstrates a professional backend and integration architecture for automotive inventory synchronization. The system manages vehicle records in Laravel and prepares a clear path for a future Go hub that will publish vehicles to marketplaces such as OLX, Mercado Livre, and iCarros.

## Current Scope

- Laravel monorepo foundation under `apps/backend-laravel`.
- Vehicle CRUD API.
- Integration log model and history API.
- Simulated vehicle synchronization with provider-level results.
- Callback endpoint for future hub status updates.
- Blade screens for listing vehicles and showing integration history.
- Demo seed data.
- Feature tests for main API flows.
- Documentation for architecture, integration flow, API contracts, and presentation.

## Out of Scope

- Real marketplace API calls.
- OAuth or marketplace credential handling.
- Scraping.
- Full Go service implementation.
- Authentication and user management.
- Production Docker setup.
- Queue workers and retry orchestration.

## Architecture Decisions

- Use a monorepo so the Laravel backend and future Go hub can evolve together while keeping their responsibilities explicit.
- Start with Laravel because the target opportunity focuses on Laravel and the first phase needs CRUD, validation, migrations, API routes, and simple screens.
- Keep the Go service as a documented placeholder in this phase to avoid pretending external integrations exist before contracts are stable.
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

## Pending Tasks

- Run `composer install` in an environment with PHP and Composer.
- Run migrations, seeders, and tests locally.
- Replace simulated hub behavior with a real HTTP client when the Go service exists.
- Implement the Go integration hub.
- Add authentication and webhook security before any production use.
- Improve Docker setup with a Composer-capable PHP image or custom Dockerfile.

## Important Commands

```bash
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
php artisan test
```

## Presentation Notes

- Emphasize that the current phase is intentionally honest: it demonstrates contracts and flow without fake claims of real marketplace integration.
- Show how Laravel owns the canonical domain and status visibility.
- Show where the future Go hub will connect: `IntegrationHubClient`.
- Explain that enums and form requests make provider/status values explicit and reviewable.
- Use seeded vehicles to quickly demonstrate list, detail, sync, logs, and callback behavior.
