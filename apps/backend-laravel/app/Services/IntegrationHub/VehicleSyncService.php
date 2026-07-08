<?php

namespace App\Services\IntegrationHub;

use App\Enums\IntegrationOperation;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\IntegrationLog;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;

class VehicleSyncService
{
    public function __construct(
        private readonly IntegrationHubClient $client,
    ) {
    }

    public function sync(Vehicle $vehicle, array $providers): array
    {
        $providers = $this->normalizeProviders($providers);
        $requestedAt = Carbon::now();

        foreach ($providers as $provider) {
            $this->createPendingLog($vehicle, $provider, $requestedAt);
        }

        $hubResponse = $this->client->syncVehicle($vehicle, $providers);

        foreach ($hubResponse['results'] as $result) {
            $this->persistProviderResult($vehicle, $result, $hubResponse, $requestedAt);
        }

        return $hubResponse;
    }

    private function normalizeProviders(array $providers): array
    {
        $providers = $providers ?: IntegrationProvider::values();

        return collect($providers)
            ->intersect(IntegrationProvider::values())
            ->values()
            ->all();
    }

    private function createPendingLog(Vehicle $vehicle, string $provider, Carbon $requestedAt): void
    {
        IntegrationLog::create([
            'vehicle_id' => $vehicle->id,
            'provider' => $provider,
            'operation' => IntegrationOperation::Publish->value,
            'status' => IntegrationStatus::Pending->value,
            'request_payload' => [
                'vehicle_external_code' => $vehicle->external_code,
                'provider' => $provider,
            ],
            'attempts' => 1,
            'last_attempt_at' => $requestedAt,
        ]);
    }

    private function persistProviderResult(Vehicle $vehicle, array $result, array $hubResponse, Carbon $requestedAt): void
    {
        IntegrationLog::create([
            'vehicle_id' => $vehicle->id,
            'provider' => $result['provider'],
            'operation' => IntegrationOperation::Publish->value,
            'status' => $result['status'],
            'external_reference' => $result['external_reference'] ?? null,
            'request_payload' => [
                'vehicle_external_code' => $vehicle->external_code,
                'provider' => $result['provider'],
            ],
            'response_payload' => $hubResponse,
            'error_message' => $result['error_message'] ?? null,
            'attempts' => 1,
            'last_attempt_at' => $requestedAt,
        ]);
    }
}
