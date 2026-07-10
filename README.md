# AutoSync Hub

AutoSync Hub é uma aplicação para gerenciar estoque automotivo e orquestrar sincronização com canais externos. A base atual combina um backend Laravel, responsável pelo domínio principal, com um serviço Go separado para execução das integrações.

O projeto ainda usa providers simulados para OLX, Mercado Livre e iCarros, porque integrações reais normalmente exigem credenciais, aprovação comercial, OAuth e contratos específicos. Mesmo assim, o fluxo foi montado com fronteiras parecidas com as de uma integração real: contrato HTTP, logs por provider, callbacks, retries e separação entre aplicação principal e hub de integração.

## O Que Existe Hoje

- Cadastro de veículos via API Laravel.
- Telas Blade para listar, criar, editar, excluir e visualizar veículos.
- Ação visual para solicitar sincronização de um veículo.
- Serviço Go recebendo solicitações de sync em `POST /sync-requests`.
- Adapters simulados por provider no Go.
- Callback do Go para o Laravel em `POST /api/integration-callbacks`.
- Histórico de logs de integração por veículo, provider e operação.
- Resumo atual de integração por provider.
- PostgreSQL no ambiente de desenvolvimento.
- SQLite em memória nos testes automatizados do Laravel.
- Datas técnicas mantidas em UTC e exibidas na interface em `America/Sao_Paulo`.
- Testes de feature no Laravel e testes unitários/HTTP no Go.
- Documentação de arquitetura, contratos e evolução em `docs/`.

## Arquitetura

```text
autosync_hub/
  apps/
    backend-laravel/      aplicação principal, API, telas, banco e logs
    integration-hub-go/   serviço de integração, providers, retries e callbacks
  docs/                   arquitetura, contratos e histórico de evolução
```

Fluxo principal:

```text
Tela/API Laravel
  -> cria ou consulta veículos no PostgreSQL
  -> solicita sincronização
  -> chama o Integration Hub em Go
  -> Go processa providers simulados
  -> Go envia callbacks para o Laravel
  -> Laravel grava logs e exibe resumo por provider
```

## Como Rodar Com Docker

Este é o caminho recomendado para avaliar o projeto, porque já sobe PostgreSQL, Laravel e Go com as variáveis corretas.

Na raiz do repositório:

```bash
cp apps/backend-laravel/.env.example apps/backend-laravel/.env
docker compose build backend-laravel
docker compose run --rm --no-deps backend-laravel composer install
docker compose up -d postgres
docker compose run --rm --no-deps backend-laravel php artisan key:generate
docker compose run --rm --no-deps backend-laravel php artisan migrate:fresh --seed
docker compose up backend-laravel integration-hub-go postgres
```

Depois abra:

- Interface web: `http://127.0.0.1:8000/vehicles`
- API Laravel: `http://127.0.0.1:8000/api`
- Health check do Go: `http://127.0.0.1:8080/healthz`

O PostgreSQL do Docker fica exposto no host em `127.0.0.1:5433`, evitando conflito com instalações locais que usam `5432`.

Por padrão, o Laravel mantém timestamps técnicos em UTC (`APP_TIMEZONE=UTC`) e exibe horários de sincronização no fuso local configurado em `APP_DISPLAY_TIMEZONE=America/Sao_Paulo`.

## Fluxo Para Testar

1. Abra `http://127.0.0.1:8000/vehicles`.
2. Clique em `New Vehicle`.
3. Cadastre um veículo.
4. Entre no detalhe do veículo.
5. Use `Edit Vehicle` para alterar algum campo.
6. Use `Request Sync` para enviar o veículo ao hub Go.
7. Confira os logs e o resumo de integração no detalhe.
8. Compare com os endpoints JSON da API, se quiser inspecionar o contrato.

Se o resultado ficar `failed` com a mensagem `Integration Hub is unavailable.`, o Laravel não conseguiu alcançar o serviço Go. Nesse caso, confirme que o Compose foi iniciado com `integration-hub-go` junto do backend:

```bash
docker compose up backend-laravel integration-hub-go postgres
```

## Rodando Localmente Sem Docker

Use este caminho apenas se você já tiver PHP, Composer, extensões de PostgreSQL e Go configurados na máquina.

Suba pelo menos o PostgreSQL:

```bash
docker compose up -d postgres
```

Em um terminal, rode o Laravel:

```bash
cd apps/backend-laravel
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Em outro terminal, rode o hub Go:

```bash
cd apps/integration-hub-go
GOCACHE=/tmp/autosync-go-cache go run ./cmd/api
```

No modo local, o `.env.example` do Laravel já aponta para o PostgreSQL em `127.0.0.1:5433` e para o hub Go em `http://localhost:8080`.

## Testes

Laravel:

```bash
docker run --rm --user "$(id -u):$(id -g)" \
  -v "$(pwd)/apps/backend-laravel:/app" \
  -w /app composer:2 php artisan test
```

Go:

```bash
cd apps/integration-hub-go
GOCACHE=/tmp/autosync-go-cache go test ./...
```

Validações úteis antes de entregar alterações:

```bash
docker compose config
git diff --check
```

## Endpoints Laravel

```text
GET    /api/vehicles
POST   /api/vehicles
GET    /api/vehicles/{vehicle}
PUT    /api/vehicles/{vehicle}
DELETE /api/vehicles/{vehicle}
POST   /api/vehicles/{vehicle}/sync
GET    /api/vehicles/{vehicle}/integration-summary
GET    /api/vehicles/{vehicle}/integration-logs
POST   /api/integration-callbacks
```

## Endpoints Go

```text
GET  /healthz
POST /sync-requests
```

## Documentação

Para entender o projeto em mais profundidade:

1. `docs/ARCHITECTURE.md`: divisão de responsabilidades entre Laravel e Go.
2. `docs/API_CONTRACTS.md`: exemplos de payloads da API.
3. `docs/LARAVEL_GO_CONTRACT.md`: contrato entre os serviços.
4. `docs/DEVELOPMENT_ROADMAP.md`: fases concluídas e próximos passos.
5. `docs/PROJECT_MEMORY.md`: memória viva do projeto.

## Próximos Caminhos Naturais

- Adicionar autenticação e perfis de usuário.
- Colocar o processamento de sync em fila assíncrona.
- Trocar os adapters simulados por integrações reais quando houver credenciais.
- Melhorar observabilidade com métricas, tracing e dashboards.
- Preparar uma configuração de produção com secrets, health checks e pipeline.

## Limites Atuais

- Providers externos ainda são simulados.
- Não há autenticação de usuários.
- Não há fila real para jobs longos.
- Não há configuração completa de produção.
