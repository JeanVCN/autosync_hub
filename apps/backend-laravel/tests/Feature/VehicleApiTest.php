<?php

namespace Tests\Feature;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\IntegrationLog;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $vehicle = Vehicle::factory()->create(['external_code' => 'CAR-901']);

        $this->postJson("/api/vehicles/{$vehicle->id}/sync", [
            'providers' => [IntegrationProvider::Olx->value, IntegrationProvider::MercadoLivre->value],
        ])->assertOk()
            ->assertJsonPath('data.contract_version', '2026-07-09')
            ->assertJsonPath('data.vehicle_external_code', 'CAR-901')
            ->assertJsonPath('data.callback_url', 'http://localhost:8000/api/integration-callbacks')
            ->assertJsonCount(2, 'data.results');

        $this->assertDatabaseCount('integration_logs', 4);
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
}
