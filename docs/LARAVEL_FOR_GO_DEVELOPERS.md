# Laravel for Go Developers — AutoSync Hub

Este guia explica o AutoSync Hub pensando em quem vem de Golang e ainda nao domina PHP/Laravel.

A ideia nao e ensinar todo o Laravel, mas te dar o mapa mental necessario para entender este projeto por completo: onde cada coisa fica, qual papel cada arquivo cumpre, como o fluxo HTTP funciona, como o banco e modelado, como a regra de negocio foi separada e como isso se compara com um backend Go bem organizado.

## 1. Visao geral do projeto

AutoSync Hub e um projeto demonstrativo de backend e integracoes para estoque automotivo.

O fluxo principal e:

```text
Laravel cadastra veiculos
↓
Usuario/API solicita sincronizacao
↓
Laravel cria logs de integracao
↓
Laravel chama um cliente do futuro hub Go
↓
O cliente simula respostas de providers
↓
Laravel salva status por marketplace
↓
Callbacks futuros podem atualizar esses status
```

Nesta etapa, o Laravel e o backend principal. O Go ainda nao foi implementado, mas ja existe uma pasta reservada em `apps/integration-hub-go`.

## 2. Como pensar Laravel comparando com Go

Em Go, voce normalmente monta explicitamente:

- `main.go`;
- router;
- handlers;
- services;
- repositories;
- structs;
- migrations externas ou SQL;
- middlewares;
- configuracao;
- injeção manual de dependencias.

No Laravel, muita coisa ja vem convencionada pelo framework:

- `routes/api.php` e `routes/web.php` fazem o papel do roteador;
- Controllers fazem o papel dos handlers;
- Form Requests fazem validacao de entrada;
- Models Eloquent representam entidades e acesso ao banco;
- Migrations definem schema do banco;
- Resources formatam JSON de saida;
- Services carregam regra de negocio;
- Providers registram dependencias no container;
- Artisan e o CLI do Laravel, parecido com um conjunto de comandos de projeto.

Uma comparacao direta:

| Conceito em Go | Equivalente neste Laravel | Exemplo no projeto |
|---|---|---|
| `main.go` | `public/index.php` + `bootstrap/app.php` | Entrada HTTP e boot do app |
| Router `chi`, `gin`, `net/http` | `routes/api.php`, `routes/web.php` | Rotas da API e telas |
| Handler | Controller | `VehicleController` |
| Request DTO + validator | Form Request | `StoreVehicleRequest` |
| Response DTO/Presenter | Resource | `VehicleResource` |
| Struct de dominio | Model Eloquent | `Vehicle`, `IntegrationLog` |
| SQL migration | Laravel migration | `create_vehicles_table` |
| Service | Service | `VehicleSyncService` |
| Interface/client externo | Client service | `IntegrationHubClient` |
| DI manual | Service Container | `AppServiceProvider` |
| Seed script | Seeder | `DatabaseSeeder` |
| Teste HTTP | Feature Test | `VehicleApiTest` |

## 3. Estrutura do monorepo

```text
autosync_hub/
  README.md
  docker-compose.yml
  apps/
    backend-laravel/
    integration-hub-go/
  docs/
```

### `apps/backend-laravel`

E a aplicacao Laravel atual. Ela contem:

- API;
- telas Blade;
- banco;
- validacoes;
- services;
- testes;
- configuracoes.

### `apps/integration-hub-go`

Ainda e um placeholder. A ideia futura e colocar ali o servico Go responsavel por providers como OLX, Mercado Livre e iCarros.

### `docs`

Contem a documentacao de arquitetura, contratos, fluxo, apresentacao e memoria viva.

## 4. Entrada da aplicacao Laravel

### `public/index.php`

Esse arquivo e parecido com o ponto de entrada HTTP de um servidor Go, mas no Laravel voce nao costuma mexer nele.

Em Go, voce poderia ter algo como:

```go
func main() {
    r := chi.NewRouter()
    http.ListenAndServe(":8080", r)
}
```

No Laravel, o servidor web aponta para `public/index.php`. Esse arquivo carrega o autoload do Composer, cria a aplicacao e entrega a request para o framework.

### `bootstrap/app.php`

Esse arquivo configura a aplicacao:

- rotas web;
- rotas API;
- comandos de console;
- middlewares;
- tratamento de excecoes.

Ele ocupa um papel parecido com a montagem inicial de dependencias e rotas que voce faria em `main.go`.

## 5. Rotas

### `routes/api.php`

Aqui ficam as rotas JSON:

```php
Route::apiResource('vehicles', VehicleController::class);
Route::post('vehicles/{vehicle}/sync', [VehicleController::class, 'sync']);
Route::get('vehicles/{vehicle}/integration-logs', [VehicleController::class, 'integrationLogs']);
Route::post('integration-callbacks', IntegrationCallbackController::class);
```

Comparando com Go:

```go
r.Get("/api/vehicles", vehicleHandler.List)
r.Post("/api/vehicles", vehicleHandler.Create)
r.Post("/api/vehicles/{id}/sync", vehicleHandler.Sync)
```

`Route::apiResource` e um atalho do Laravel para criar rotas REST padrao:

- `GET /vehicles`;
- `POST /vehicles`;
- `GET /vehicles/{vehicle}`;
- `PUT /vehicles/{vehicle}`;
- `DELETE /vehicles/{vehicle}`.

### `routes/web.php`

Aqui ficam as rotas que retornam HTML:

- `/vehicles`;
- `/vehicles/{vehicle}`.

No Go, seria como handlers que renderizam templates HTML.

## 6. Controllers sao handlers

### `VehicleController`

Arquivo:

```text
apps/backend-laravel/app/Http/Controllers/Api/VehicleController.php
```

Ele recebe requests HTTP e chama as camadas certas.

Exemplo:

```php
public function store(StoreVehicleRequest $request): JsonResponse
{
    $vehicle = Vehicle::create($request->validated());

    return (new VehicleResource($vehicle))
        ->response()
        ->setStatusCode(Response::HTTP_CREATED);
}
```

Traduzindo para Go:

```go
func (h *VehicleHandler) Create(w http.ResponseWriter, r *http.Request) {
    input := validateVehicleRequest(r)
    vehicle, err := h.vehicleService.Create(r.Context(), input)
    writeJSON(w, http.StatusCreated, vehicle)
}
```

Diferença importante:

- Em Go, voce normalmente decodifica JSON e valida manualmente ou com biblioteca.
- No Laravel, `StoreVehicleRequest` ja chega validado no controller.

## 7. Form Requests sao validadores de entrada

Arquivos:

```text
app/Http/Requests/StoreVehicleRequest.php
app/Http/Requests/UpdateVehicleRequest.php
app/Http/Requests/SyncVehicleRequest.php
app/Http/Requests/IntegrationCallbackRequest.php
```

Eles definem as regras de validacao.

Exemplo:

```php
'external_code' => ['required', 'string', 'max:50', 'unique:vehicles,external_code'],
'status' => ['required', Rule::in(VehicleStatus::values())],
```

Em Go, isso seria parecido com:

```go
type CreateVehicleRequest struct {
    ExternalCode string `json:"external_code" validate:"required,max=50"`
    Status       string `json:"status" validate:"required,oneof=draft active inactive"`
}
```

No Laravel, se a validacao falhar, o framework retorna erro HTTP automaticamente. Por isso o controller fica menor.

## 8. Resources sao formatadores de resposta

Arquivos:

```text
app/Http/Resources/VehicleResource.php
app/Http/Resources/IntegrationLogResource.php
```

Eles controlam o JSON que sai da API.

Exemplo:

```php
return [
    'id' => $this->id,
    'external_code' => $this->external_code,
    'brand' => $this->brand,
    'status' => $this->status->value,
];
```

Em Go, seria parecido com criar structs de response:

```go
type VehicleResponse struct {
    ID           int64  `json:"id"`
    ExternalCode string `json:"external_code"`
    Brand        string `json:"brand"`
    Status       string `json:"status"`
}
```

A vantagem e separar o formato publico da API do model interno.

## 9. Models Eloquent: entidade + acesso ao banco

Arquivos:

```text
app/Models/Vehicle.php
app/Models/IntegrationLog.php
```

No Laravel, o model Eloquent representa a tabela e tambem fornece metodos para consultar e persistir dados.

Exemplo:

```php
Vehicle::create($request->validated());
Vehicle::query()->latest()->paginate(15);
```

Em Go, voce talvez separasse:

- struct de dominio;
- repository;
- queries SQL;
- scanner de linhas.

Algo como:

```go
vehicle, err := repo.Create(ctx, input)
vehicles, err := repo.List(ctx, pagination)
```

Neste projeto, nao criamos repositories separados porque seria overengineering para a fase atual. O Eloquent ja resolve bem o CRUD demonstrativo.

## 10. Relacionamentos

No model `Vehicle`:

```php
public function integrationLogs(): HasMany
{
    return $this->hasMany(IntegrationLog::class);
}
```

Isso diz:

```text
Vehicle hasMany IntegrationLog
```

No model `IntegrationLog`:

```php
public function vehicle(): BelongsTo
{
    return $this->belongsTo(Vehicle::class);
}
```

Isso diz:

```text
IntegrationLog belongsTo Vehicle
```

Em Go, voce provavelmente nao teria isso automatico. Voce faria query explicita:

```sql
SELECT * FROM integration_logs WHERE vehicle_id = ?
```

Ou teria um metodo:

```go
repo.ListLogsByVehicleID(ctx, vehicleID)
```

## 11. Migrations: schema do banco

Arquivos:

```text
database/migrations/0001_01_01_000000_create_vehicles_table.php
database/migrations/0001_01_01_000001_create_integration_logs_table.php
```

Elas criam as tabelas.

Exemplo:

```php
$table->string('external_code')->unique();
$table->decimal('price', 12, 2);
$table->string('status')->default('draft')->index();
```

Em Go, isso poderia estar em arquivos `.sql` usados por ferramentas como Goose, Atlas ou golang-migrate:

```sql
CREATE TABLE vehicles (
    id BIGSERIAL PRIMARY KEY,
    external_code VARCHAR(50) UNIQUE NOT NULL,
    price NUMERIC(12,2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft'
);
```

No Laravel, migrations sao PHP, entao o schema fica escrito com a API do framework.

## 12. Enums: valores permitidos

Arquivos:

```text
app/Enums/VehicleStatus.php
app/Enums/IntegrationProvider.php
app/Enums/IntegrationOperation.php
app/Enums/IntegrationStatus.php
```

Eles centralizam valores como:

```text
draft
active
inactive
olx
mercado_livre
icarros
pending
processing
published
failed
```

Em Go, isso seria parecido com:

```go
type VehicleStatus string

const (
    VehicleStatusDraft    VehicleStatus = "draft"
    VehicleStatusActive   VehicleStatus = "active"
    VehicleStatusInactive VehicleStatus = "inactive"
)
```

No Laravel/PHP moderno, `enum` da uma forma tipada de representar esses valores.

## 13. Services: regra de negocio fora do controller

Arquivos:

```text
app/Services/IntegrationHub/VehicleSyncService.php
app/Services/IntegrationHub/IntegrationHubClient.php
app/Services/IntegrationHub/IntegrationCallbackService.php
```

### `VehicleSyncService`

Responsavel por orquestrar a sincronizacao:

1. normaliza providers;
2. cria logs `pending`;
3. chama o client do hub;
4. persiste resultados por provider.

Em Go, seria algo como:

```go
type VehicleSyncService struct {
    hubClient HubClient
    logsRepo  IntegrationLogRepository
}

func (s *VehicleSyncService) Sync(ctx context.Context, vehicle Vehicle, providers []Provider) (*SyncResponse, error) {
    // cria logs pending
    // chama hub
    // salva resultados
}
```

### `IntegrationHubClient`

Hoje ele simula a resposta do futuro hub Go.

Ele ja usa a ideia de uma URL configuravel:

```text
INTEGRATION_HUB_URL=http://localhost:8080
```

Quando o Go existir, esse arquivo deve trocar a simulacao por chamada HTTP real.

Em Go, esse client seria equivalente a uma interface:

```go
type HubClient interface {
    SyncVehicle(ctx context.Context, vehicle Vehicle, providers []Provider) (*SyncResponse, error)
}
```

### `IntegrationCallbackService`

Recebe payload do callback, encontra o veiculo por `external_code` e cria ou atualiza log.

Ele representa a regra de negocio por tras de:

```text
POST /api/integration-callbacks
```

## 14. Service Provider: registro de dependencias

Arquivo:

```text
app/Providers/AppServiceProvider.php
```

Ele registra o `IntegrationHubClient` no container:

```php
$this->app->singleton(IntegrationHubClient::class, fn (): IntegrationHubClient => IntegrationHubClient::fromConfig());
```

Em Go, voce faria isso manualmente no bootstrap:

```go
hubClient := NewIntegrationHubClient(cfg.IntegrationHubURL)
syncService := NewVehicleSyncService(hubClient, logsRepo)
handler := NewVehicleHandler(syncService)
```

No Laravel, o container injeta automaticamente no construtor ou nos metodos quando reconhece o tipo.

## 15. Seeders e factories

### `DatabaseSeeder`

Arquivo ativo no seu editor:

```text
database/seeders/DatabaseSeeder.php
```

Ele cria dados reais de demonstracao:

- Honda Civic EXL 2.0 2020/2021;
- Toyota Corolla XEI 2.0 2021/2022;
- Jeep Compass Longitude 2022/2022;
- Volkswagen T-Cross Highline 2021/2021;
- Chevrolet Onix Premier 2020/2021.

Tambem cria logs simulados com providers e statuses variados.

Em Go, voce talvez tivesse:

- script SQL de seed;
- comando CLI proprio;
- funcao `SeedDatabase(ctx, db)`.

### Factories

Arquivos:

```text
database/factories/VehicleFactory.php
database/factories/IntegrationLogFactory.php
```

Factories criam dados falsos para testes.

Em Go, isso seria parecido com helpers de teste:

```go
func NewVehicleFixture() Vehicle {
    return Vehicle{ExternalCode: "CAR-001"}
}
```

## 16. Blade: telas HTML simples

Arquivos:

```text
resources/views/layout.blade.php
resources/views/vehicles/index.blade.php
resources/views/vehicles/show.blade.php
```

Blade e o template engine do Laravel.

Comparando com Go:

```go
html/template
```

No projeto:

- `/vehicles` lista veiculos;
- `/vehicles/{vehicle}` mostra detalhe e historico de integracao.

Essas telas nao sao o centro do projeto. Elas existem para demonstracao visual rapida.

## 17. Testes

Arquivo:

```text
tests/Feature/VehicleApiTest.php
```

Ele testa os fluxos principais:

- criar veiculo;
- listar veiculos;
- sincronizar veiculo;
- receber callback;
- listar historico de logs.

Em Go, seria parecido com testes usando `httptest`:

```go
req := httptest.NewRequest(http.MethodPost, "/api/vehicles", body)
rec := httptest.NewRecorder()
router.ServeHTTP(rec, req)
assert.Equal(t, http.StatusCreated, rec.Code)
```

No Laravel, os testes usam helpers:

```php
$this->postJson('/api/vehicles', [...])
    ->assertCreated()
    ->assertJsonPath('data.external_code', 'CAR-900');
```

O trait `RefreshDatabase` recria o banco entre testes, parecido com usar banco isolado por teste em Go.

## 18. Configuracao

Arquivos importantes:

```text
.env.example
apps/backend-laravel/.env.example
config/app.php
config/database.php
config/services.php
config/logging.php
```

No Laravel, `.env` alimenta os arquivos de `config/`.

Exemplo:

```text
INTEGRATION_HUB_URL=http://localhost:8080
```

Esse valor e lido em:

```php
config('services.integration_hub.url')
```

Em Go, voce provavelmente teria:

```go
cfg.IntegrationHubURL = os.Getenv("INTEGRATION_HUB_URL")
```

## 19. Fluxo completo de `POST /api/vehicles/{vehicle}/sync`

Este e o melhor fluxo para entender o projeto.

### 1. Rota

```text
routes/api.php
```

```php
Route::post('vehicles/{vehicle}/sync', [VehicleController::class, 'sync']);
```

### 2. Controller

```text
VehicleController::sync
```

Recebe:

- request validada;
- vehicle resolvido automaticamente pelo Laravel;
- service injetado automaticamente.

### 3. Validacao

```text
SyncVehicleRequest
```

Garante que providers sejam validos:

- `olx`;
- `mercado_livre`;
- `icarros`.

### 4. Service

```text
VehicleSyncService
```

Cria logs pending, chama o client e salva resultados.

### 5. Client

```text
IntegrationHubClient
```

Simula resposta do hub Go.

### 6. Banco

```text
integration_logs
```

Recebe registros por provider e status.

### 7. Resposta JSON

Retorna algo como:

```json
{
  "message": "Vehicle synchronization requested.",
  "data": {
    "vehicle_external_code": "CAR-001",
    "results": [
      {
        "provider": "olx",
        "status": "published"
      }
    ]
  }
}
```

## 20. Fluxo completo de callback

Endpoint:

```text
POST /api/integration-callbacks
```

Ele representa o futuro Go chamando Laravel.

Payload:

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

Fluxo interno:

```text
IntegrationCallbackRequest valida entrada
↓
IntegrationCallbackController chama service
↓
IntegrationCallbackService busca Vehicle por external_code
↓
IntegrationLog e criado ou atualizado
↓
IntegrationLogResource formata resposta
```

Em Go, isso seria:

```text
handler decode JSON
↓
validator valida campos
↓
service aplica regra
↓
repository salva log
↓
response DTO vira JSON
```

## 21. O que e "magico" no Laravel

Laravel tem algumas convenções que parecem magica para quem vem de Go.

### Route model binding

Quando a rota tem:

```php
vehicles/{vehicle}
```

E o controller recebe:

```php
public function show(Vehicle $vehicle)
```

O Laravel busca o `Vehicle` pelo ID automaticamente.

Em Go, voce faria:

```go
id := chi.URLParam(r, "vehicleID")
vehicle, err := repo.FindByID(ctx, id)
```

### Injeção de dependencia

Quando o controller recebe:

```php
VehicleSyncService $syncService
```

O Laravel cria/injeta essa dependencia pelo container.

Em Go, voce normalmente passaria isso no struct do handler.

### Resposta automatica de validacao

Se um `FormRequest` falha, o Laravel retorna erro 422 automaticamente.

Em Go, voce geralmente escreve esse fluxo.

## 22. O que voce deve saber para apresentar bem

Pontos fortes para explicar:

- O projeto nao finge integrar marketplaces reais.
- A arquitetura esta preparada para integrar depois.
- Laravel esta como backend principal e fonte canonica dos veiculos.
- Go entra depois como hub de providers.
- Logs de integracao tornam o sistema auditavel.
- Enums deixam status e providers explicitos.
- Services evitam controller gordo.
- Resources deixam o contrato JSON claro.
- Form Requests deixam validacao fora dos handlers.

Uma frase boa para entrevista:

```text
Eu usei Laravel para concentrar o dominio principal, API, persistencia e visibilidade operacional. O servico Go ficara responsavel pela parte que tende a crescer em complexidade tecnica: adapters de providers, chamadas externas, retries e normalizacao de respostas. Nesta primeira fase eu simulei o hub, mas deixei o ponto de substituicao claro no IntegrationHubClient.
```

## 23. Onde mexer quando quiser evoluir

### Adicionar novo provider

Mexer em:

```text
app/Enums/IntegrationProvider.php
app/Services/IntegrationHub/IntegrationHubClient.php
docs/API_CONTRACTS.md
docs/PROJECT_MEMORY.md
```

### Adicionar novo campo em Vehicle

Mexer em:

```text
database/migrations/
app/Models/Vehicle.php
app/Http/Requests/StoreVehicleRequest.php
app/Http/Requests/UpdateVehicleRequest.php
app/Http/Resources/VehicleResource.php
database/seeders/DatabaseSeeder.php
resources/views/vehicles/
```

### Trocar simulacao por HTTP real para Go

Mexer principalmente em:

```text
app/Services/IntegrationHub/IntegrationHubClient.php
config/services.php
.env.example
```

### Adicionar autenticacao

Provavelmente mexer em:

```text
routes/api.php
routes/web.php
bootstrap/app.php
app/Http/Middleware/
```

## 24. Comandos importantes

```bash
cd apps/backend-laravel
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
php artisan test
```

Comparando com Go:

| Laravel | Go |
|---|---|
| `composer install` | `go mod download` |
| `php artisan serve` | `go run ./cmd/api` |
| `php artisan migrate` | `goose up` / `migrate up` |
| `php artisan test` | `go test ./...` |
| `php artisan make:*` | criar arquivos/pacotes manualmente |

## 25. Como ler o projeto do zero

Siga esta ordem:

1. Leia `README.md`.
2. Leia `docs/ARCHITECTURE.md`.
3. Abra `routes/api.php`.
4. Abra `VehicleController`.
5. Abra os Form Requests.
6. Abra `VehicleSyncService`.
7. Abra `IntegrationHubClient`.
8. Abra os models `Vehicle` e `IntegrationLog`.
9. Abra as migrations.
10. Abra `DatabaseSeeder`.
11. Abra `VehicleApiTest`.
12. Abra as telas Blade.

Essa ordem mostra o projeto de fora para dentro: contrato HTTP, entrada, validacao, regra de negocio, persistencia, dados de demo e testes.

## 26. Resumo mental

Se voce tivesse que traduzir este Laravel para Go, a arquitetura seria algo assim:

```text
cmd/api/main.go
internal/vehicle/handler.go
internal/vehicle/service.go
internal/vehicle/repository.go
internal/vehicle/dto.go
internal/integration/service.go
internal/integration/hub_client.go
internal/integration/repository.go
migrations/*.sql
templates/vehicles/*.html
tests/feature/*
```

No Laravel, esses papeis ficam distribuidos assim:

```text
routes/
app/Http/Controllers/
app/Http/Requests/
app/Http/Resources/
app/Services/
app/Models/
database/migrations/
database/seeders/
resources/views/
tests/Feature/
```

O desenho geral e o mesmo que voce ja conhece em Go: separar entrada HTTP, validacao, regra de negocio, persistencia e apresentacao. O Laravel apenas oferece mais convenções prontas e mais automacao ao redor dessas camadas.
