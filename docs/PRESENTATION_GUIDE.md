# Presentation Guide

## Suggested Order

1. Start with the problem: dealerships need one vehicle inventory source that can publish to multiple marketplaces.
2. Show the monorepo structure and explain why Laravel and Go live together.
3. Open the Laravel vehicle list at `/vehicles`.
4. Open a vehicle detail page and show integration history.
5. Trigger `POST /api/vehicles/{vehicle}/sync`.
6. Show new logs and explain provider-level statuses.
7. Send a callback payload to `POST /api/integration-callbacks`.
8. Close by showing `docs/ARCHITECTURE.md` and the future Go hub plan.

## Key Decisions

- Laravel is the main backend because the role evaluates Laravel and because it is strong for APIs, validation, migrations, and simple panels.
- Go is planned for the integration hub because provider adapters, retries, and external calls are a good fit for a focused service.
- Real marketplace APIs are intentionally out of scope for this first phase because credentials and approval processes would distract from architecture.
- The vehicle model is canonical so provider-specific rules do not leak into the core application.
- Integration logs are persisted because integration products need auditability and operational visibility.

## How To Explain Laravel

Laravel owns the business-facing part of the product:

- Vehicle CRUD.
- Validation.
- Database model.
- API contract.
- Web visibility.
- Integration status history.

The code is split into controllers, form requests, resources, models, and services so each layer has a clear responsibility.

## How To Explain Future Go

The Go hub will become a provider execution layer.

It should receive a canonical vehicle payload from Laravel, map that payload to each provider, handle retries or provider-specific errors, and call Laravel back when the final status changes.

## Questions An Evaluator May Ask

**Why not integrate real OLX or Mercado Livre now?**

Because this phase is about architecture and contract design. Real integrations usually need credentials, approval, OAuth, and provider-specific test environments.

**Why keep integration logs instead of only the latest status?**

Logs preserve operational history, make support easier, and show why a vehicle is blocked or failed.

**Why put the simulated hub client in Laravel?**

It keeps the end-to-end product demonstrable while preserving a clear replacement point for the future Go service.

**What would change in production?**

Add authentication, authorization, background jobs, retries, webhook signatures, observability, and real HTTP calls to the Go hub.

## Current Limitations

- No authentication in this phase.
- No real provider calls.
- No queue workers yet.
- No webhook signature validation yet.
- Docker is only a light placeholder, not a production setup.

## Future Improvements

- Implement the Go integration hub.
- Add queued sync jobs.
- Add provider adapter contracts in Go.
- Add callback signature validation.
- Add admin authentication.
- Add observability for integration failures and retry counts.
