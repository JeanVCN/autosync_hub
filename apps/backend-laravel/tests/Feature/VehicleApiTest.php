<?php

namespace Tests\Feature;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\IntegrationLog;
use App\Models\Vehicle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_vehicle(): void
    {
        $response = $this->postJson('/api/vehicles', [
            'external_code' => 'CAR-900',
            'brand' => 'Honda',
            'model' => 'Civic',
            'version' => 'EXL 2.0',
            'year' => 2020,
            'model_year' => 2021,
            'price' => 118900.00,
            'mileage' => 42000,
            'fuel_type' => 'flex',
            'transmission' => 'automatic',
            'color' => 'gray',
            'status' => 'active',
        ]);

        $response->assertCreated()->assertJsonPath('data.external_code', 'CAR-900');
        $this->assertDatabaseHas('vehicles', ['external_code' => 'CAR-900']);
    }

    public function test_it_lists_vehicles(): void
    {
        Vehicle::factory()->count(2)->create();

        $this->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_it_syncs_a_vehicle_and_registers_logs(): void
    {
        $this->configureIntegrationHubForTests();

        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-901']);

        Http::fake([
            'http://localhost:8080/sync-requests' => Http::response([
                'request_id' => 'hub-request-1',
                'status' => 'accepted',
                'message' => 'Sync request accepted for processing',
                'accepted_providers' => [IntegrationProvider::Olx->value, IntegrationProvider::MercadoLivre->value],
                'results' => [
                    [
                        'provider' => IntegrationProvider::Olx->value,
                        'operation' => 'publish',
                        'status' => IntegrationStatus::Published->value,
                        'external_reference' => 'OLX-CAR-901',
                        'response_payload' => ['message' => 'ok'],
                    ],
                    [
                        'provider' => IntegrationProvider::MercadoLivre->value,
                        'operation' => 'publish',
                        'status' => IntegrationStatus::Processing->value,
                        'external_reference' => 'MERCADOLIVRE-CAR-901',
                        'response_payload' => ['message' => 'processing'],
                    ],
                ],
            ], 202),
        ]);

        $this->postJson("/api/vehicles/{$vehicle->id}/sync", [
            'providers' => [IntegrationProvider::Olx->value, IntegrationProvider::MercadoLivre->value],
        ])->assertOk()
            ->assertJsonPath('data.contract_version', '2026-07-09')
            ->assertJsonPath('data.vehicle_external_code', 'CAR-901')
            ->assertJsonPath('data.callback_url', 'http://localhost:8000/api/integration-callbacks')
            ->assertJsonCount(2, 'data.results');

        $this->assertDatabaseCount('integration_logs', 2);
        $this->assertDatabaseMissing('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'status' => IntegrationStatus::Pending->value,
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://localhost:8080/sync-requests'
                && $request->hasHeader('X-Contract-Version', '2026-07-09')
                && $request->hasHeader('X-Request-Id')
                && $request->hasHeader('Idempotency-Key')
                && $request['operation'] === 'publish'
                && $request['vehicle']['external_code'] === 'CAR-901';
        });
    }

    public function test_it_records_failed_logs_when_the_integration_hub_rejects_the_request(): void
    {
        $this->configureIntegrationHubForTests();

        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-903']);

        Http::fake([
            'http://localhost:8080/sync-requests' => Http::response([
                'request_id' => 'hub-request-2',
                'status' => 'rejected',
                'message' => 'Request contains invalid fields',
                'errors' => [
                    'providers' => ['olx is temporarily unavailable'],
                ],
            ], 422),
        ]);

        $this->postJson("/api/vehicles/{$vehicle->id}/sync", [
            'providers' => [IntegrationProvider::Olx->value],
        ])->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.results.0.status', 'failed')
            ->assertJsonPath('data.results.0.error_message', 'Request contains invalid fields');

        $this->assertDatabaseHas('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Failed->value,
            'error_message' => 'Request contains invalid fields',
        ]);
        $this->assertDatabaseMissing('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Pending->value,
        ]);
    }

    public function test_it_does_not_leave_pending_logs_when_the_integration_hub_is_unavailable(): void
    {
        $this->configureIntegrationHubForTests();

        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-905']);

        Http::fake(function (): never {
            throw new ConnectionException('Connection refused');
        });

        $this->postJson("/api/vehicles/{$vehicle->id}/sync", [
            'providers' => [IntegrationProvider::Olx->value],
        ])->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.results.0.status', IntegrationStatus::Failed->value)
            ->assertJsonPath('data.results.0.error_message', 'Integration Hub is unavailable.');

        $this->assertDatabaseCount('integration_logs', 1);
        $this->assertDatabaseHas('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Failed->value,
            'error_message' => 'Integration Hub is unavailable.',
        ]);
        $this->assertDatabaseMissing('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'status' => IntegrationStatus::Pending->value,
        ]);
    }

    private function configureIntegrationHubForTests(): void
    {
        Config::set('services.integration_hub.url', 'http://localhost:8080');
        Config::set('services.integration_hub.callback_url', 'http://localhost:8000/api/integration-callbacks');
    }

    public function test_it_rejects_invalid_sync_providers(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->postJson("/api/vehicles/{$vehicle->id}/sync", [
            'providers' => ['unknown_provider'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('providers.0');
    }

    public function test_it_receives_an_integration_callback(): void
    {
        Vehicle::factory()->create(['external_code' => 'CAR-902']);

        $this->postJson('/api/integration-callbacks', [
            'vehicle_external_code' => 'CAR-902',
            'provider' => 'olx',
            'operation' => 'publish',
            'status' => 'published',
            'external_reference' => 'OLX-123456',
            'response_payload' => [
                'message' => 'Vehicle published successfully',
            ],
        ])->assertOk()
            ->assertJsonPath('data.provider', 'olx')
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('integration_logs', [
            'provider' => 'olx',
            'status' => IntegrationStatus::Published->value,
            'external_reference' => 'OLX-123456',
        ]);
    }

    public function test_integration_callback_updates_existing_pending_log(): void
    {
        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-904']);

        IntegrationLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Pending->value,
            'external_reference' => null,
        ]);

        $this->postJson('/api/integration-callbacks', [
            'vehicle_external_code' => 'CAR-904',
            'provider' => 'olx',
            'operation' => 'publish',
            'status' => 'published',
            'external_reference' => 'OLX-904',
            'response_payload' => [
                'message' => 'Vehicle published successfully',
            ],
        ])->assertOk();

        $this->assertDatabaseCount('integration_logs', 1);
        $this->assertDatabaseHas('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'provider' => 'olx',
            'status' => IntegrationStatus::Published->value,
            'external_reference' => 'OLX-904',
        ]);
    }

    public function test_integration_callback_preserves_attempts_for_existing_result_log(): void
    {
        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-906']);

        IntegrationLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::MercadoLivre->value,
            'status' => IntegrationStatus::Processing->value,
            'external_reference' => 'MERCADOLIVRE-CAR-906',
            'attempts' => 1,
        ]);

        $this->postJson('/api/integration-callbacks', [
            'vehicle_external_code' => 'CAR-906',
            'provider' => 'mercado_livre',
            'operation' => 'publish',
            'status' => 'processing',
            'external_reference' => 'MERCADOLIVRE-CAR-906',
            'response_payload' => [
                'attempts' => 1,
                'message' => 'Provider accepted the vehicle for processing',
            ],
        ])->assertOk();

        $this->assertDatabaseCount('integration_logs', 1);
        $this->assertDatabaseHas('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'provider' => 'mercado_livre',
            'status' => IntegrationStatus::Processing->value,
            'external_reference' => 'MERCADOLIVRE-CAR-906',
            'attempts' => 1,
        ]);
    }

    public function test_it_rejects_integration_callback_when_token_is_configured_and_missing(): void
    {
        Config::set('services.integration_hub.token', 'secret-token');
        Vehicle::factory()->create(['external_code' => 'CAR-902']);

        $this->postJson('/api/integration-callbacks', [
            'vehicle_external_code' => 'CAR-902',
            'provider' => 'olx',
            'operation' => 'publish',
            'status' => 'published',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Missing or invalid integration callback token.');
    }

    public function test_it_accepts_integration_callback_with_valid_token(): void
    {
        Config::set('services.integration_hub.token', 'secret-token');
        Vehicle::factory()->create(['external_code' => 'CAR-902']);

        $this->withHeader('Authorization', 'Bearer secret-token')
            ->postJson('/api/integration-callbacks', [
                'vehicle_external_code' => 'CAR-902',
                'provider' => 'olx',
                'operation' => 'publish',
                'status' => 'published',
            ])->assertOk()
            ->assertJsonPath('data.provider', 'olx');
    }

    public function test_it_rejects_invalid_integration_callback_payloads(): void
    {
        $this->postJson('/api/integration-callbacks', [
            'vehicle_external_code' => 'CAR-902',
            'provider' => 'unknown_provider',
            'operation' => 'publish',
            'status' => 'published',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('provider');
    }

    public function test_it_returns_not_found_for_unknown_callback_vehicle(): void
    {
        $this->postJson('/api/integration-callbacks', [
            'vehicle_external_code' => 'CAR-404',
            'provider' => 'olx',
            'operation' => 'publish',
            'status' => 'failed',
            'error_message' => 'Vehicle was not found in Laravel',
        ])->assertNotFound();
    }

    public function test_it_lists_vehicle_integration_history(): void
    {
        $vehicle = Vehicle::factory()->create();
        IntegrationLog::factory()->count(3)->create(['vehicle_id' => $vehicle->id]);

        $this->getJson("/api/vehicles/{$vehicle->id}/integration-logs")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_it_returns_current_integration_summary_by_provider(): void
    {
        $vehicle = Vehicle::factory()->create();

        IntegrationLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Processing->value,
            'last_attempt_at' => now()->subMinutes(10),
        ]);

        IntegrationLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Published->value,
            'external_reference' => 'OLX-CURRENT',
            'attempts' => 2,
            'last_attempt_at' => now(),
        ]);

        $this->getJson("/api/vehicles/{$vehicle->id}/integration-summary")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.provider', 'olx')
            ->assertJsonPath('data.0.status', 'published')
            ->assertJsonPath('data.0.external_reference', 'OLX-CURRENT')
            ->assertJsonPath('data.0.attempts', 2)
            ->assertJsonPath('data.1.provider', 'mercado_livre')
            ->assertJsonPath('data.1.status', 'not_synced');
    }

    public function test_vehicle_detail_page_displays_current_integration_summary(): void
    {
        $vehicle = Vehicle::factory()->create([
            'external_code' => 'CAR-WEB',
            'brand' => 'Honda',
            'model' => 'Civic',
        ]);

        IntegrationLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Published->value,
            'external_reference' => 'OLX-WEB',
            'last_attempt_at' => now(),
        ]);

        $this->get("/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertSee('Current Integration Status')
            ->assertSee('olx')
            ->assertSee('published')
            ->assertSee('OLX-WEB');
    }

    public function test_vehicle_detail_page_displays_sync_times_in_configured_display_timezone(): void
    {
        Config::set('app.display_timezone', 'America/Sao_Paulo');

        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-TZ']);

        IntegrationLog::factory()->create([
            'vehicle_id' => $vehicle->id,
            'provider' => IntegrationProvider::Olx->value,
            'status' => IntegrationStatus::Published->value,
            'external_reference' => 'OLX-TZ',
            'last_attempt_at' => Carbon::parse('2026-07-10 12:00:00', 'UTC'),
        ]);

        $this->get("/vehicles/{$vehicle->id}")
            ->assertOk()
            ->assertSee('Last attempt (America/Sao_Paulo)')
            ->assertSee('2026-07-10 09:00');
    }

    public function test_vehicle_create_page_displays_form(): void
    {
        $this->get('/vehicles/create')
            ->assertOk()
            ->assertSee('New Vehicle')
            ->assertSee('External code');
    }

    public function test_it_creates_a_vehicle_from_web_form(): void
    {
        $this->post('/vehicles', $this->vehicleFormPayload([
            'external_code' => 'WEB-001',
            'brand' => 'Jeep',
            'model' => 'Compass',
        ]))->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'external_code' => 'WEB-001',
            'brand' => 'Jeep',
            'model' => 'Compass',
        ]);
    }

    public function test_it_updates_a_vehicle_from_web_form(): void
    {
        $vehicle = Vehicle::factory()->create(['external_code' => 'WEB-002']);

        $this->put("/vehicles/{$vehicle->id}", $this->vehicleFormPayload([
            'external_code' => 'WEB-002',
            'brand' => 'Chevrolet',
            'model' => 'Tracker',
            'status' => 'inactive',
        ]))->assertRedirect("/vehicles/{$vehicle->id}");

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'brand' => 'Chevrolet',
            'model' => 'Tracker',
            'status' => 'inactive',
        ]);
    }

    public function test_it_requests_sync_from_web_detail_page(): void
    {
        $this->configureIntegrationHubForTests();
        $vehicle = Vehicle::factory()->create(['external_code' => 'WEB-003']);

        Http::fake([
            'http://localhost:8080/sync-requests' => Http::response([
                'request_id' => 'web-hub-request',
                'status' => 'accepted',
                'message' => 'Sync request accepted for processing',
                'accepted_providers' => IntegrationProvider::values(),
                'results' => collect(IntegrationProvider::values())
                    ->map(fn (string $provider): array => [
                        'provider' => $provider,
                        'operation' => 'publish',
                        'status' => IntegrationStatus::Processing->value,
                        'external_reference' => strtoupper($provider).'-WEB-003',
                        'response_payload' => ['message' => 'queued'],
                    ])
                    ->all(),
            ], 202),
        ]);

        $this->post("/vehicles/{$vehicle->id}/sync")
            ->assertRedirect("/vehicles/{$vehicle->id}");

        $this->assertDatabaseCount('integration_logs', 3);
        $this->assertDatabaseMissing('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'status' => IntegrationStatus::Pending->value,
        ]);
    }

    public function test_web_sync_shows_error_when_the_integration_hub_is_unavailable(): void
    {
        $this->configureIntegrationHubForTests();
        $vehicle = Vehicle::factory()->create(['external_code' => 'WEB-004']);

        Http::fake(function (): never {
            throw new ConnectionException('Connection refused');
        });

        $this->post("/vehicles/{$vehicle->id}/sync")
            ->assertRedirect("/vehicles/{$vehicle->id}")
            ->assertSessionHas('error', 'Vehicle synchronization failed: Integration Hub is unavailable.');

        $this->assertDatabaseCount('integration_logs', 3);
        $this->assertDatabaseMissing('integration_logs', [
            'vehicle_id' => $vehicle->id,
            'status' => IntegrationStatus::Pending->value,
        ]);
    }

    public function test_it_deletes_a_vehicle_from_web_detail_page(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->delete("/vehicles/{$vehicle->id}")
            ->assertRedirect('/vehicles');

        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
    }

    private function vehicleFormPayload(array $overrides = []): array
    {
        return array_merge([
            'external_code' => 'WEB-CAR',
            'brand' => 'Honda',
            'model' => 'Civic',
            'version' => 'EXL 2.0',
            'year' => 2020,
            'model_year' => 2021,
            'price' => 118900,
            'mileage' => 42000,
            'fuel_type' => 'flex',
            'transmission' => 'automatic',
            'color' => 'gray',
            'description' => 'Demo vehicle',
            'status' => 'active',
        ], $overrides);
    }
}
