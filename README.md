# AutoSync Hub

AutoSync Hub é um projeto demonstrativo de backend e integrações para sincronização de estoque automotivo. Ele mostra como um backend Laravel pode gerenciar veículos, solicitar sincronização com marketplaces, receber atualizações de status por provider e ficar preparado para um hub de integração em Go.

A primeira etapa implementada concentra-se em `apps/backend-laravel`. O serviço Go ainda está representado apenas como base planejada, porque integrações reais com providers como OLX, Mercado Livre e iCarros normalmente exigem credenciais, aprovação, OAuth e contratos específicos por plataforma.

## Por Que Laravel e Go

Laravel é o backend principal da aplicação: ele cuida do cadastro de veículos, API pública, telas de apresentação e armazenamento dos status de integração.

Go será o hub de integração: um serviço focado em adapters de providers, retries, chamadas externas, normalização de respostas e envio de callbacks de volta para o Laravel.

Essa divisão mantém o Laravel limpo e, ao mesmo tempo, mostra um caminho realista para escalar cargas de trabalho de integração.

## Estrutura do Repositório

```text
autosync_hub/
  apps/
    backend-laravel/
    integration-hub-go/
  docs/
```

## Funcionalidades Atuais

- API CRUD de veículos.
- Endpoint de sincronização integrado ao hub Go.
- Histórico de logs de integração por provider e operação.
- Endpoint de callback preparado para atualizações futuras vindas do hub Go.
- Endpoint de resumo atual de integração por provider.
- Telas Blade simples para listar e inspecionar veículos.
- Dados seedados para demonstração de veículos e logs.
- Testes de feature para os principais fluxos da API.
- Contrato Laravel-Go documentado para a próxima etapa.
- Fundação do hub Go com endpoint `/sync-requests` e providers simulados.

## Requisitos

- PHP 8.2 ou superior.
- Composer.
- PostgreSQL para o ambiente de desenvolvimento principal.
- O PostgreSQL do Docker fica disponível no host em `127.0.0.1:5433` para não brigar com um PostgreSQL local em `5432`.
- SQLite ainda é usado nos testes automatizados em memória, por ser rápido e isolado.
- Docker, se preferir usar o fluxo validado em container.

## Como Rodar

```bash
docker compose up -d postgres
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Fluxo Docker recomendado:

```bash
docker compose build backend-laravel
docker compose run --rm --no-deps backend-laravel composer install
docker compose run --rm --no-deps backend-laravel php artisan key:generate
docker compose run --rm backend-laravel php artisan migrate --seed
docker compose up backend-laravel integration-hub-go postgres
```

Abra:

- Painel web: `http://127.0.0.1:8000/vehicles`
- Base da API: `http://127.0.0.1:8000/api`

## Testes

```bash
cd apps/backend-laravel
php artisan test
```

Comando de teste validado em container:

```bash
docker run --rm --user "$(id -u):$(id -g)" \
  -v "$(pwd)/apps/backend-laravel:/app" \
  -w /app composer:2 php artisan test
```

## Endpoints Principais

Laravel:

- `GET /api/vehicles`
- `POST /api/vehicles`
- `GET /api/vehicles/{vehicle}`
- `PUT /api/vehicles/{vehicle}`
- `DELETE /api/vehicles/{vehicle}`
- `POST /api/vehicles/{vehicle}/sync`
- `GET /api/vehicles/{vehicle}/integration-summary`
- `GET /api/vehicles/{vehicle}/integration-logs`
- `POST /api/integration-callbacks`

Go Integration Hub:

- `GET /healthz`
- `POST /sync-requests`

## Status

A base Laravel foi migrada para PostgreSQL no ambiente de desenvolvimento, mantendo SQLite apenas para testes automatizados em memória. O fluxo de integração Laravel foi endurecido com resumo por provider e validações negativas, e o contrato Laravel-Go já está documentado.

A fundação do hub Go já foi implementada em `apps/integration-hub-go`, e o Laravel já chama esse hub por HTTP no fluxo de sincronização. Os providers ainda são simulados dentro do Go, o que preserva a demo sem depender de APIs reais.

Consulte `docs/` para arquitetura, contratos de API, contrato Laravel-Go, fluxo de integração, guia de apresentação e memória viva do projeto.
