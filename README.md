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
- Endpoint de sincronização com resultados simulados por provider.
- Histórico de logs de integração por provider e operação.
- Endpoint de callback preparado para atualizações futuras vindas do hub Go.
- Endpoint de resumo atual de integração por provider.
- Telas Blade simples para listar e inspecionar veículos.
- Dados seedados para demonstração de veículos e logs.
- Testes de feature para os principais fluxos da API.
- Contrato Laravel-Go documentado para a próxima etapa.

## Requisitos

- PHP 8.2 ou superior.
- Composer.
- SQLite, MySQL ou PostgreSQL. SQLite é suficiente para a demo.
- Docker, se preferir usar o fluxo validado em container.

## Como Rodar

```bash
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Fluxo Docker usado na validação:

```bash
docker pull composer:2
docker run --rm --user "$(id -u):$(id -g)" \
  -v "$(pwd)/apps/backend-laravel:/app" \
  -w /app composer:2 composer install
docker run --rm --user "$(id -u):$(id -g)" -p 8000:8000 \
  -v "$(pwd)/apps/backend-laravel:/app" \
  -w /app composer:2 php artisan serve --host=0.0.0.0 --port=8000
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

- `GET /api/vehicles`
- `POST /api/vehicles`
- `GET /api/vehicles/{vehicle}`
- `PUT /api/vehicles/{vehicle}`
- `DELETE /api/vehicles/{vehicle}`
- `POST /api/vehicles/{vehicle}/sync`
- `GET /api/vehicles/{vehicle}/integration-summary`
- `GET /api/vehicles/{vehicle}/integration-logs`
- `POST /api/integration-callbacks`

## Status

A base Laravel foi validada com dependências Composer, migrations SQLite, seeders, testes automatizados e checagens manuais web/API. O fluxo de integração Laravel foi endurecido com resumo por provider e validações negativas, e o contrato Laravel-Go já está documentado.

A próxima fase é implementar a fundação do hub Go em `apps/integration-hub-go`.

Consulte `docs/` para arquitetura, contratos de API, contrato Laravel-Go, fluxo de integração, guia de apresentação e memória viva do projeto.
