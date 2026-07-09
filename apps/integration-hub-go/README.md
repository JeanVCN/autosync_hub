# AutoSync Integration Hub Go

Este serviço é a fundação do hub de integração em Go do AutoSync Hub.

Ele segue o contrato documentado em:

```text
../../docs/LARAVEL_GO_CONTRACT.md
```

## Responsabilidades

- Receber solicitações de sincronização vindas do Laravel.
- Validar o payload canônico de veículo.
- Processar providers simulados como OLX, Mercado Livre e iCarros.
- Normalizar resultados por provider.
- Enviar callbacks para o Laravel quando `callback_url` estiver configurada.
- Servir como base para adapters reais no futuro.

## Estrutura

```text
cmd/api/              entrada HTTP do serviço
internal/config/      leitura de variáveis de ambiente
internal/httpapi/     rotas e handlers HTTP
internal/sync/        contrato e orquestração do fluxo de sync
internal/provider/    providers simulados
internal/callback/    envio de callbacks para o Laravel
```

## Como Rodar

```bash
cd apps/integration-hub-go
GOCACHE=/tmp/autosync-go-cache go run ./cmd/api
```

O serviço sobe em:

```text
http://localhost:8080
```

## Endpoints

```text
GET  /healthz
POST /sync-requests
```

## Exemplo de Sync Request

```bash
curl -X POST http://localhost:8080/sync-requests \
  -H 'Content-Type: application/json' \
  -d '{
    "contract_version": "2026-07-09",
    "request_id": "request-demo-1",
    "idempotency_key": "vehicle-CAR-001-publish-demo",
    "callback_url": "http://localhost:8000/api/integration-callbacks",
    "operation": "publish",
    "providers": ["olx", "mercado_livre", "icarros"],
    "vehicle": {
      "external_code": "CAR-001",
      "brand": "Honda",
      "model": "Civic",
      "version": "EXL 2.0",
      "year": 2020,
      "model_year": 2021,
      "price": 118900,
      "status": "active"
    }
  }'
```

## Testes

```bash
cd apps/integration-hub-go
GOCACHE=/tmp/autosync-go-cache go test ./...
```

## Variáveis de Ambiente

```text
PORT=8080
INTEGRATION_CONTRACT_VERSION=2026-07-09
INTEGRATION_HUB_TOKEN=
CALLBACK_TIMEOUT_SECONDS=5
```

`INTEGRATION_HUB_TOKEN` é opcional em ambiente local. Quando configurado, o endpoint `POST /sync-requests` exige:

```text
Authorization: Bearer <token>
```
