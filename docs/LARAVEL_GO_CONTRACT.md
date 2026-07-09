# Laravel-Go Contract — AutoSync Hub

This document defines the contract between the Laravel backend and the future Go Integration Hub.

Contract version:

```text
2026-07-09
```

The goal is to keep Laravel responsible for the canonical business domain and make Go responsible for provider execution, retries, external API details, and callback delivery.

## Services

### Laravel Backend

Base local URL:

```text
http://localhost:8000
```

Responsibilities:

- Own canonical vehicle records.
- Validate user-facing API requests.
- Persist integration logs.
- Expose callback endpoint for Go.
- Display provider status and integration history.

### Go Integration Hub

Base local URL:

```text
http://localhost:8080
```

Responsibilities:

- Receive sync requests from Laravel.
- Run provider adapters.
- Normalize provider responses.
- Handle provider retries and timeouts.
- Send callback updates to Laravel.

## Environment Variables

Laravel will use:

```text
INTEGRATION_HUB_URL=http://localhost:8080
INTEGRATION_HUB_TIMEOUT_SECONDS=5
INTEGRATION_HUB_TOKEN=
INTEGRATION_CALLBACK_URL=http://localhost:8000/api/integration-callbacks
INTEGRATION_CONTRACT_VERSION=2026-07-09
```

Meaning:

- `INTEGRATION_HUB_URL`: base URL of the Go service.
- `INTEGRATION_HUB_TIMEOUT_SECONDS`: Laravel HTTP timeout when calling Go.
- `INTEGRATION_HUB_TOKEN`: shared service token for Laravel-to-Go calls.
- `INTEGRATION_CALLBACK_URL`: callback URL Go should call after processing provider statuses.
- `INTEGRATION_CONTRACT_VERSION`: explicit contract version sent in requests.

## Laravel to Go: Sync Vehicle

Future endpoint in Go:

```text
POST /sync-requests
```

Full local URL:

```text
POST http://localhost:8080/sync-requests
```

### Headers

```text
Content-Type: application/json
Accept: application/json
X-Contract-Version: 2026-07-09
X-Request-Id: <uuid>
Idempotency-Key: <stable-key>
Authorization: Bearer <INTEGRATION_HUB_TOKEN>
```

Notes:

- `Authorization` is optional in local development, but required outside local/demo mode.
- `X-Request-Id` is unique per Laravel sync request.
- `Idempotency-Key` must stay stable for the same vehicle/providers/request intent.

### Request Payload

```json
{
  "contract_version": "2026-07-09",
  "request_id": "95ef2cc9-820f-4812-a103-d61d1738be46",
  "idempotency_key": "vehicle-CAR-001-publish-olx-mercado_livre-icarros",
  "callback_url": "http://localhost:8000/api/integration-callbacks",
  "operation": "publish",
  "providers": ["olx", "mercado_livre", "icarros"],
  "vehicle": {
    "id": 1,
    "external_code": "CAR-001",
    "brand": "Honda",
    "model": "Civic",
    "version": "EXL 2.0",
    "year": 2020,
    "model_year": 2021,
    "price": 118900.0,
    "mileage": 42000,
    "fuel_type": "flex",
    "transmission": "automatic",
    "color": "gray",
    "description": "Vehicle description",
    "status": "active",
    "updated_at": "2026-07-09T14:10:00Z"
  }
}
```

### Required Fields

- `contract_version`
- `request_id`
- `idempotency_key`
- `callback_url`
- `operation`
- `providers`
- `vehicle.external_code`
- `vehicle.brand`
- `vehicle.model`
- `vehicle.year`
- `vehicle.model_year`
- `vehicle.price`
- `vehicle.status`

### Optional Fields

- `vehicle.id`
- `vehicle.version`
- `vehicle.mileage`
- `vehicle.fuel_type`
- `vehicle.transmission`
- `vehicle.color`
- `vehicle.description`
- `vehicle.updated_at`

## Go to Laravel: Immediate Sync Response

The Go hub should return quickly after accepting the request.

Recommended success response:

```json
{
  "request_id": "95ef2cc9-820f-4812-a103-d61d1738be46",
  "status": "accepted",
  "message": "Sync request accepted for processing",
  "accepted_providers": ["olx", "mercado_livre", "icarros"]
}
```

Recommended partial validation error:

```json
{
  "request_id": "95ef2cc9-820f-4812-a103-d61d1738be46",
  "status": "rejected",
  "message": "Request contains invalid providers",
  "errors": {
    "providers": ["unknown_provider is not supported"]
  }
}
```

## Go to Laravel: Callback

Existing Laravel endpoint:

```text
POST /api/integration-callbacks
```

Full local URL:

```text
POST http://localhost:8000/api/integration-callbacks
```

### Headers

```text
Content-Type: application/json
Accept: application/json
X-Contract-Version: 2026-07-09
X-Request-Id: <same-or-child-request-id>
Authorization: Bearer <callback-token>
```

The current Laravel implementation validates payload shape. Signature/token enforcement is planned before production use.

### Callback Payload

```json
{
  "vehicle_external_code": "CAR-001",
  "provider": "olx",
  "operation": "publish",
  "status": "published",
  "external_reference": "OLX-123456",
  "response_payload": {
    "message": "Vehicle published successfully",
    "provider_status_code": "200"
  }
}
```

### Failure Callback Payload

```json
{
  "vehicle_external_code": "CAR-001",
  "provider": "icarros",
  "operation": "publish",
  "status": "failed",
  "external_reference": null,
  "error_message": "Version field is required by provider",
  "response_payload": {
    "provider_status_code": "422",
    "provider_error_code": "missing_version"
  }
}
```

## Accepted Values

Providers:

```text
olx
mercado_livre
icarros
```

Operations:

```text
publish
update
delete
status_check
```

Callback statuses:

```text
pending
processing
published
failed
rejected
requires_action
```

## Error Handling

### Laravel calling Go

Expected Go status codes:

- `202 Accepted`: request accepted for processing.
- `400 Bad Request`: malformed JSON or invalid contract shape.
- `401 Unauthorized`: missing or invalid service token.
- `409 Conflict`: duplicated idempotency key with incompatible payload.
- `422 Unprocessable Entity`: valid JSON but invalid provider/domain fields.
- `500 Internal Server Error`: unexpected hub failure.
- `503 Service Unavailable`: hub temporarily unavailable.

Laravel behavior implemented in Phase 6:

- On `202`, persist processing logs.
- On `400`, `409`, or `422`, persist rejected/failed logs with error details.
- On `401`, `500`, `503`, or connection failure, persist failed logs for the requested providers.

### Go calling Laravel

Current Laravel status codes:

- `200 OK`: callback registered.
- `404 Not Found`: `vehicle_external_code` does not exist.
- `422 Unprocessable Entity`: invalid provider, operation, status, or payload.

## Idempotency Strategy

Laravel should generate an `Idempotency-Key` from:

```text
vehicle_external_code + operation + sorted providers + relevant vehicle updated_at/version marker
```

Go should store or cache the key long enough to prevent duplicate provider calls for the same logical request.

If the same key arrives with the same payload:

```text
return the previous accepted/result status
```

If the same key arrives with a different payload:

```text
return 409 Conflict
```

## Timeout and Retry Strategy

Laravel-to-Go timeout:

```text
INTEGRATION_HUB_TIMEOUT_SECONDS=5
```

Recommended behavior:

- Laravel should not block user requests waiting for marketplace providers.
- Go should accept the request quickly and process providers internally.
- Provider retry logic belongs in Go.
- Laravel callback processing should stay idempotent and fast.

## Security Strategy

Local/demo mode:

- Token can be empty.
- No webhook signature required yet.

Production direction:

- Laravel-to-Go requests use `Authorization: Bearer <token>`.
- Go-to-Laravel callbacks use a token or HMAC signature.
- Tokens must come from environment variables.
- Callback payloads should include request id and timestamp when signature validation is implemented.

## Versioning Strategy

The contract version is explicit:

```text
2026-07-09
```

When fields or behavior change incompatibly:

- update `INTEGRATION_CONTRACT_VERSION`;
- update this document;
- keep Laravel and Go compatible during migration;
- add tests for old/new behavior if both are temporarily supported.

## Current Implementation Status

The Go hub foundation now exists in:

```text
apps/integration-hub-go
```

It implements:

- `GET /healthz`
- `POST /sync-requests`
- simulated provider processing
- optional service-token authorization
- callback dispatch to Laravel

Laravel now calls the Go hub from:

```text
apps/backend-laravel/app/Services/IntegrationHub/IntegrationHubClient.php
```

Provider adapters are still simulated inside the Go hub. A later phase should replace those simulations with real provider adapters.
