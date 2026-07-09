# API Contracts

Base URL for local development:

```text
http://127.0.0.1:8000/api
```

The internal Laravel-to-Go service contract is documented separately in `docs/LARAVEL_GO_CONTRACT.md`.

## Create Vehicle

`POST /vehicles`

```json
{
  "external_code": "CAR-001",
  "brand": "Honda",
  "model": "Civic",
  "version": "EXL 2.0",
  "year": 2020,
  "model_year": 2021,
  "price": 118900.00,
  "mileage": 42000,
  "fuel_type": "flex",
  "transmission": "automatic",
  "color": "gray",
  "description": "Demo vehicle",
  "status": "active"
}
```

Example:

```bash
curl -X POST http://127.0.0.1:8000/api/vehicles \
  -H 'Content-Type: application/json' \
  -d '{
    "external_code": "CAR-900",
    "brand": "Honda",
    "model": "Civic",
    "version": "EXL 2.0",
    "year": 2020,
    "model_year": 2021,
    "price": 118900.00,
    "mileage": 42000,
    "fuel_type": "flex",
    "transmission": "automatic",
    "color": "gray",
    "status": "active"
  }'
```

## List Vehicles

`GET /vehicles`

Returns paginated vehicles.

Example:

```bash
curl http://127.0.0.1:8000/api/vehicles
```

## Show Vehicle

`GET /vehicles/{vehicle}`

Returns one vehicle with integration logs loaded.

## Update Vehicle

`PUT /vehicles/{vehicle}`

Accepts the same fields as creation. Fields may be sent partially.

## Delete Vehicle

`DELETE /vehicles/{vehicle}`

Deletes the vehicle and its integration logs.

## Sync Vehicle

`POST /vehicles/{vehicle}/sync`

```json
{
  "providers": ["olx", "mercado_livre", "icarros"]
}
```

If providers are omitted, Laravel syncs all supported providers.

Example:

```bash
curl -X POST http://127.0.0.1:8000/api/vehicles/1/sync \
  -H 'Content-Type: application/json' \
  -d '{"providers":["olx","mercado_livre","icarros"]}'
```

Example response:

```json
{
  "message": "Vehicle synchronization requested.",
  "data": {
    "hub_url": "http://localhost:8080",
    "vehicle_id": 1,
    "vehicle_external_code": "CAR-001",
    "results": [
      {
        "provider": "olx",
        "status": "published",
        "external_reference": "OLX-123456",
        "error_message": null
      }
    ]
  }
}
```

Validation notes:

- Unknown providers return HTTP 422.
- If `providers` is omitted, all supported providers are used.
- This endpoint calls the Go Integration Hub through `INTEGRATION_HUB_URL`.
- Provider execution is still simulated inside the Go hub until real marketplace adapters are added.

## Vehicle Integration Summary

`GET /vehicles/{vehicle}/integration-summary`

Returns the current provider-level status for all supported providers. Providers with no log yet return `not_synced`.

Example:

```bash
curl http://127.0.0.1:8000/api/vehicles/1/integration-summary
```

Example response:

```json
{
  "data": [
    {
      "provider": "olx",
      "status": "published",
      "operation": "publish",
      "external_reference": "OLX-123456",
      "error_message": null,
      "attempts": 2,
      "last_attempt_at": "2026-07-09T14:10:00.000000Z"
    },
    {
      "provider": "mercado_livre",
      "status": "not_synced",
      "operation": null,
      "external_reference": null,
      "error_message": null,
      "attempts": 0,
      "last_attempt_at": null
    }
  ]
}
```

## Integration Callback

`POST /integration-callbacks`

```json
{
  "vehicle_external_code": "CAR-001",
  "provider": "olx",
  "operation": "publish",
  "status": "published",
  "external_reference": "OLX-123456",
  "response_payload": {
    "message": "Vehicle published successfully"
  }
}
```

Example:

```bash
curl -X POST http://127.0.0.1:8000/api/integration-callbacks \
  -H 'Content-Type: application/json' \
  -d '{
    "vehicle_external_code": "CAR-001",
    "provider": "olx",
    "operation": "publish",
    "status": "published",
    "external_reference": "OLX-123456",
    "response_payload": {
      "message": "Vehicle published successfully"
    }
  }'
```

Validation notes:

- Unknown providers, operations, or statuses return HTTP 422.
- Unknown `vehicle_external_code` returns HTTP 404.
- The received callback payload is stored in `request_payload`.

## List Vehicle Integration Logs

`GET /vehicles/{vehicle}/integration-logs`

Returns paginated integration logs ordered by latest attempt.

Example:

```bash
curl http://127.0.0.1:8000/api/vehicles/1/integration-logs
```

## Accepted Values

Vehicle status:

- `draft`
- `active`
- `inactive`

Providers:

- `olx`
- `mercado_livre`
- `icarros`

Operations:

- `publish`
- `update`
- `delete`
- `status_check`

Integration statuses:

- `pending`
- `processing`
- `published`
- `failed`
- `rejected`
- `requires_action`

Summary-only status:

- `not_synced`: returned only by `GET /vehicles/{vehicle}/integration-summary` when a provider has no integration log yet.
