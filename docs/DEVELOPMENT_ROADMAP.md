# Development Roadmap — AutoSync Hub

Este documento existe para responder rapidamente:

- em qual fase o projeto esta;
- o que ja foi concluido;
- o que falta fazer;
- qual e o proximo passo quando retomarmos o trabalho.

Ele deve ser atualizado sempre que uma fase avancar, uma decisao importante mudar ou uma validacao relevante for concluida.

## Current Phase

```text
Phase 5 — Go Integration Hub Foundation
```

Status atual:

```text
Pending
```

Objetivo da fase atual:

Criar o servico Go que assumira a responsabilidade de simular e depois executar providers.

## Phase 1 — Laravel Foundation

Status:

```text
Completed
```

Entregas:

- Monorepo criado.
- Aplicacao Laravel adicionada em `apps/backend-laravel`.
- Placeholder do futuro hub Go criado em `apps/integration-hub-go`.
- Entidade `Vehicle` criada.
- Entidade `IntegrationLog` criada.
- Enums de status, providers e operations criados.
- API REST de veiculos criada.
- Endpoint de sync simulado criado.
- Endpoint de callback criado.
- Telas Blade simples criadas.
- Seeders e factories criados.
- Testes de feature criados.
- Documentacao inicial criada.
- Guia Laravel para Go developers criado.

Commits relacionados:

```text
0c31af4 feat: bootstrap AutoSync Hub
c4e73cd docs: add Laravel guide for Go developers
```

Observacao importante:

A estrutura foi criada manualmente porque o ambiente inicial nao tinha `php` nem `composer` disponiveis. Por isso a Fase 2 existe antes de evoluir o dominio ou iniciar o servico Go.

## Phase 2 — Laravel Runtime Validation

Status:

```text
Completed
```

Objetivo:

Transformar a base Laravel de "estrutura implementada" para "aplicacao executavel e demonstravel".

Checklist:

- Confirmar disponibilidade de PHP.
- Confirmar disponibilidade de Composer.
- Rodar `composer install`.
- Criar `.env` local.
- Gerar `APP_KEY`.
- Criar banco SQLite local.
- Rodar migrations.
- Rodar seeders.
- Rodar testes automatizados.
- Subir servidor local.
- Testar rotas web.
- Testar endpoints principais da API.
- Corrigir erros encontrados.
- Atualizar documentacao com o resultado real.

Resultado da validacao em 2026-07-09:

- Host local nao tinha `php` nem `composer`.
- Docker estava disponivel, mas exigiu acesso ao daemon.
- Imagem `composer:2` foi baixada e usada como runtime de validacao.
- `laravel/framework ^11.0` foi bloqueado por advisories de seguranca do Packagist.
- Projeto foi atualizado para `laravel/framework ^12.0`.
- `mockery/mockery` foi adicionado em `require-dev`, pois a suite de testes Laravel dependia dele.
- `composer install` concluiu com sucesso.
- `php artisan key:generate` concluiu com sucesso.
- `php artisan migrate --seed` concluiu com sucesso.
- `php artisan test` concluiu com sucesso: 5 testes, 15 assertions.
- `/vehicles` respondeu HTTP 200.
- `/vehicles/1` respondeu HTTP 200.
- `GET /api/vehicles` respondeu HTTP 200.
- `POST /api/vehicles/1/sync` respondeu HTTP 200 e criou logs por provider.
- `POST /api/integration-callbacks` respondeu HTTP 200 e persistiu um log de callback.
- `GET /api/vehicles/1/integration-logs` respondeu HTTP 200.

Comandos esperados:

```bash
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan test
php artisan serve
```

Critérios de conclusao:

- `php artisan migrate --seed` executa sem erro.
- `php artisan test` executa sem erro.
- `/vehicles` abre com dados seedados.
- `GET /api/vehicles` retorna veiculos.
- `POST /api/vehicles/{vehicle}/sync` cria logs.
- `POST /api/integration-callbacks` registra atualizacao de provider.
- `GET /api/vehicles/{vehicle}/integration-logs` retorna historico.
- `docs/PROJECT_MEMORY.md` registra o resultado da validacao.

Se a fase for interrompida:

1. Verifique `git status --short`.
2. Leia esta secao.
3. Veja o ultimo item concluido no checklist.
4. Continue a partir do primeiro item pendente.
5. Se houver erro tecnico, documente o erro e a correcao aplicada.

## Phase 3 — Laravel Integration Hardening

Status:

```text
Completed
```

Objetivo:

Melhorar a robustez do fluxo Laravel antes de mover a simulacao para Go.

Possiveis entregas:

- Endpoint de resumo por provider, `GET /api/vehicles/{vehicle}/integration-summary`.
- Melhor exibicao do status atual por provider na tela de detalhe.
- Callback armazenando o payload recebido em `request_payload`.
- Callback incrementando `attempts` com base em eventos anteriores do mesmo provider/operation.
- Testes para payloads invalidos.
- Testes para providers invalidos.
- Teste para callback com veiculo inexistente.
- Exemplos de `curl` mais completos em `docs/API_CONTRACTS.md`.

Resultado da validacao em 2026-07-09:

- `php artisan test` concluiu com sucesso: 10 testes, 37 assertions.
- O endpoint `GET /api/vehicles/{vehicle}/integration-summary` foi coberto por teste.
- Providers invalidos no sync retornam HTTP 422.
- Callbacks com provider invalido retornam HTTP 422.
- Callbacks com veiculo inexistente retornam HTTP 404.

Critério de entrada:

Fase 2 concluida.

## Phase 4 — Laravel-Go Contract Design

Status:

```text
Completed
```

Objetivo:

Definir o contrato exato entre Laravel e o futuro Integration Hub em Go.

Possiveis entregas:

- Payload `Laravel -> Go` para sync.
- Payload `Go -> Laravel` para callback.
- Campos obrigatorios e opcionais.
- Codigos de erro.
- Estrategia de idempotencia.
- Estrategia de autenticacao entre servicos.
- Documentacao de timeout e retry.

Resultado da validacao em 2026-07-09:

- Criado `docs/LARAVEL_GO_CONTRACT.md`.
- Definido endpoint futuro `POST /sync-requests` no Go.
- Definidos headers de contrato: `X-Contract-Version`, `X-Request-Id`, `Idempotency-Key` e `Authorization`.
- Definidos payloads de sync, resposta imediata, callback de sucesso e callback de falha.
- Definidas estrategias de idempotencia, timeout, retry, seguranca e versionamento.
- Laravel passou a expor configuracoes futuras em `config/services.php` e `.env.example`.
- `IntegrationHubClient` passou a incluir metadados de contrato no fluxo simulado.

Critério de entrada:

Laravel validado e fluxo atual demonstravel.

## Phase 5 — Go Integration Hub Foundation

Status:

```text
Pending
```

Objetivo:

Criar o servico Go que assumira a responsabilidade de simular e depois executar providers.

Possivel estrutura:

```text
apps/integration-hub-go/
  cmd/api/
  internal/http/
  internal/sync/
  internal/providers/
  internal/callback/
```

Possiveis entregas:

- API HTTP em Go.
- Endpoint para receber sync request do Laravel.
- Provider adapters simulados.
- Callback HTTP para Laravel.
- Testes unitarios dos providers.
- Documentacao de execucao local.

Critério de entrada:

Contrato Laravel-Go definido.

## Phase 6 — Replace Laravel Simulation With Go Calls

Status:

```text
Pending
```

Objetivo:

Trocar a simulacao interna do Laravel por chamada HTTP real para o Go hub.

Possiveis entregas:

- `IntegrationHubClient` chamando `INTEGRATION_HUB_URL`.
- Tratamento de timeout.
- Tratamento de falha de rede.
- Testes com client fake.
- Docker Compose incluindo Laravel e Go.

Critério de entrada:

Go hub minimo funcionando localmente.

## Quick Resume Checklist

Quando voltar ao projeto, execute:

```bash
git status --short
git log --oneline -5
```

Depois leia:

```text
docs/DEVELOPMENT_ROADMAP.md
docs/PROJECT_MEMORY.md
```

Se o projeto estiver na Fase 2, o proximo comando provavelmente sera algum destes:

```bash
cd apps/backend-laravel
composer install
php artisan migrate --seed
php artisan test
php artisan serve
```

## Current Next Step

Iniciar a Fase 5:

```text
criar a fundacao do servico Go de Integration Hub
```
