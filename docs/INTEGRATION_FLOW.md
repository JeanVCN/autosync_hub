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
Client simulates future Go hub provider results
↓
Laravel persists provider statuses
↓
Callback can update final status later
```

## Current Flow

1. A vehicle is created through `POST /api/vehicles` or seeded for demo.
2. A user requests synchronization through `POST /api/vehicles/{vehicle}/sync`.
3. Laravel validates the requested providers.
4. `VehicleSyncService` creates pending logs for the selected providers.
5. `IntegrationHubClient` simulates the future Go hub response.
6. `VehicleSyncService` persists the returned provider results.
7. The web screens and API can show the status history.

## Callback Flow

The endpoint `POST /api/integration-callbacks` represents the future path from Go back to Laravel.

The callback:

- Finds the vehicle by `vehicle_external_code`.
- Validates provider, operation, and status.
- Creates or updates an integration log.
- Stores the response payload for auditability.
- Returns the persisted log as JSON.

## Future Go Flow

When the Go hub is implemented, the simulated client should be replaced by an HTTP client that sends the canonical vehicle payload to `INTEGRATION_HUB_URL`.

The Laravel contract should stay stable while provider-specific complexity moves to Go.

The detailed service-to-service contract is documented in:

```text
docs/LARAVEL_GO_CONTRACT.md
```
