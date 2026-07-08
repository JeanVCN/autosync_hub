# Architecture

AutoSync Hub is organized as a monorepo because the product has one business flow split across two technical responsibilities:

- Laravel owns the main backend, vehicle records, API, web presentation, and integration status history.
- Go will later own provider execution, marketplace adapters, retries, and external API communication.

Keeping both applications in one repository makes the integration contract visible and easy to review during a technical presentation.

## Laravel Role

`apps/backend-laravel` is the current source of truth for the product domain.

It provides:

- Vehicle CRUD.
- Canonical vehicle model.
- Sync request endpoint.
- Integration log persistence.
- Callback endpoint for future hub updates.
- Simple Blade screens for demonstration.

Controllers stay thin. Validation is handled by Form Requests, response shape by Resources, and sync behavior by services under `App\Services\IntegrationHub`.

## Future Go Role

`apps/integration-hub-go` will become the integration hub.

Expected responsibilities:

- Receive sync requests from Laravel.
- Convert canonical vehicles into provider-specific payloads.
- Execute provider adapters for OLX, Mercado Livre, iCarros, and future marketplaces.
- Handle provider retries and status checks.
- Send callbacks to Laravel when final statuses change.

## Canonical Vehicle Model

Laravel stores a canonical vehicle record instead of provider-specific schemas. This avoids coupling the main application to marketplace rules too early.

Provider-specific requirements, such as mandatory fields or transformed descriptions, should live in the future Go adapters. Laravel should know the business object; the hub should know provider details.

## Integration Logs

`IntegrationLog` records each meaningful synchronization event. It stores provider, operation, status, payloads, external references, errors, attempts, and timestamps.

This makes the demo easy to inspect and creates an audit trail that would matter in a real dealership integration product.

## Current Tradeoff

The first version simulates hub responses in Laravel through `IntegrationHubClient`. This is intentional: it demonstrates the full product flow without depending on external credentials or unfinished marketplace integrations.
