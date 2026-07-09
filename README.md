# AutoSync Hub

AutoSync Hub é um projeto demonstrativo de backend e integrações para sincronização de estoque automotivo. Ele mostra como um backend Laravel pode gerenciar veículos, solicitar sincronização com marketplaces, receber atualizações de status por provider e delegar execução de integrações para um hub em Go.

O projeto não tenta fingir integração real com OLX, Mercado Livre ou iCarros. Essas plataformas normalmente exigem credenciais, aprovação, OAuth e contratos específicos. Por isso, o hub Go usa adapters simulados, mas com o mesmo desenho de fronteira que permitiria plugar adapters reais depois.

## Por Que Laravel e Go

Laravel é o backend principal da aplicação: ele cuida do cadastro de veículos, API pública, telas de apresentação e armazenamento dos status de integração.

Go é o hub de integração: um serviço focado em adapters de providers, retries, chamadas externas, normalização de respostas e envio de callbacks de volta para o Laravel.

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
- Contrato Laravel-Go documentado.
- Hub Go com endpoint `/sync-requests`, adapters simulados, callbacks, retries/backoff e validação opcional de token.

## Como Avaliar Rapido

Para entender o projeto sem apresentação, olhe nesta ordem:

1. `README.md`: visão geral, como rodar e endpoints.
2. `docs/ARCHITECTURE.md`: divisão de responsabilidades entre Laravel e Go.
3. `docs/API_CONTRACTS.md`: exemplos de payloads HTTP.
4. `docs/LARAVEL_GO_CONTRACT.md`: contrato entre os serviços.
5. `docs/DEVELOPMENT_ROADMAP.md`: histórico das fases e próximos passos.

Fluxo principal para testar:

1. Abrir `http://127.0.0.1:8000/vehicles`.
2. Entrar no detalhe de um veículo.
3. Disparar `POST /api/vehicles/{vehicle}/sync`.
4. Conferir os logs em `GET /api/vehicles/{vehicle}/integration-logs`.
5. Conferir o resumo atual em `GET /api/vehicles/{vehicle}/integration-summary`.

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
docker compose up -d postgres
docker compose run --rm --no-deps backend-laravel composer install
docker compose run --rm --no-deps backend-laravel php artisan key:generate
docker compose run --rm --no-deps backend-laravel php artisan migrate --seed
docker compose up backend-laravel integration-hub-go postgres
```

No Docker, o Laravel é servido com `php -S 0.0.0.0:8000 -t public public/index.php` para preservar corretamente as variáveis de ambiente do Compose mesmo quando existe um `.env` local montado no volume.

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

A base Laravel roda com PostgreSQL no ambiente de desenvolvimento, mantendo SQLite apenas para testes automatizados em memória. O fluxo de integração Laravel foi endurecido com resumo por provider, validações negativas, tratamento de falha do hub e callback protegido por token opcional.

O hub Go foi implementado em `apps/integration-hub-go`, e o Laravel chama esse hub por HTTP no fluxo de sincronização. Os providers ainda são simulados, mas ficam atrás de uma interface de adapter com retries/backoff, preservando a demo sem depender de APIs reais.

## Limites Honestamente Assumidos

- Não há integração real com marketplaces.
- Não há autenticação de usuários.
- Não há fila assíncrona real para processamento longo.
- Não há setup de produção completo.
- O objetivo é demonstrar arquitetura, contrato entre serviços, persistência de logs, tratamento de falhas e caminho evolutivo.

Consulte `docs/` para arquitetura, contratos de API, contrato Laravel-Go, fluxo de integração e memória viva do projeto.
