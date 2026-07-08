# API Contracts

Base URL for local development:

```text
http://127.0.0.1:8000/api
```

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

## List Vehicles

`GET /vehicles`

Returns paginated vehicles.

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

## List Vehicle Integration Logs

`GET /vehicles/{vehicle}/integration-logs`

Returns paginated integration logs ordered by latest attempt.

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
