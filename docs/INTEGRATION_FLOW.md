# Integration Flow

```text
Vehicle created in Laravel
↓
User requests sync
↓
Laravel creates pending integration logs
↓
Laravel calls IntegrationHubClient
↓
IntegrationHubClient calls the Go Integration Hub
↓
Go hub simulates provider results and dispatches callbacks
↓
Laravel persists provider statuses
↓
Callback can also update final status
```

## Current Flow

1. A vehicle is created through `POST /api/vehicles` or seeded for demo.
2. A user requests synchronization through `POST /api/vehicles/{vehicle}/sync`.
3. Laravel validates the requested providers.
4. `VehicleSyncService` creates pending logs for the selected providers.
5. `IntegrationHubClient` sends the canonical sync request to the Go hub at `INTEGRATION_HUB_URL`.
6. The Go hub validates the payload, simulates provider execution, and returns provider results.
7. `VehicleSyncService` persists the returned provider results.
8. The web screens and API can show the status history.

## Callback Flow

The endpoint `POST /api/integration-callbacks` is the path from Go back to Laravel.

The callback:

- Finds the vehicle by `vehicle_external_code`.
- Validates provider, operation, and status.
- Creates or updates an integration log.
- Stores the response payload for auditability.
- Returns the persisted log as JSON.

## Go Flow

The Laravel client now sends the canonical vehicle payload to `INTEGRATION_HUB_URL`.

The Laravel contract should stay stable while provider-specific complexity lives in Go.

The detailed service-to-service contract is documented in:

```text
docs/LARAVEL_GO_CONTRACT.md
```
