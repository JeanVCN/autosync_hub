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
        $pendingLogs = collect($providers)
            ->mapWithKeys(fn (string $provider): array => [
                $provider => $this->createPendingLog($vehicle, $provider, $requestedAt),
            ]);

        $hubResponse = $this->client->syncVehicle($vehicle, $providers);

        foreach ($hubResponse['results'] as $result) {
            $this->persistProviderResult(
                $vehicle,
                $result,
                $hubResponse,
                $requestedAt,
                $pendingLogs->get($result['provider']),
            );
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

    private function createPendingLog(Vehicle $vehicle, string $provider, Carbon $requestedAt): IntegrationLog
    {
        return IntegrationLog::create([
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

    private function persistProviderResult(
        Vehicle $vehicle,
        array $result,
        array $hubResponse,
        Carbon $requestedAt,
        ?IntegrationLog $pendingLog,
    ): void {
        $log = $this->resolveResultLog($vehicle, $result, $pendingLog);

        $log->update([
            'status' => $result['status'],
            'external_reference' => $result['external_reference'] ?? null,
            'request_payload' => [
                'vehicle_external_code' => $vehicle->external_code,
                'provider' => $result['provider'],
            ],
            'response_payload' => $hubResponse,
            'error_message' => $result['error_message'] ?? null,
            'attempts' => max((int) $log->attempts, 1),
            'last_attempt_at' => $requestedAt,
        ]);
    }

    private function resolveResultLog(Vehicle $vehicle, array $result, ?IntegrationLog $pendingLog): IntegrationLog
    {
        if ($pendingLog) {
            return $pendingLog;
        }

        if (! empty($result['external_reference'])) {
            $existingResult = IntegrationLog::query()
                ->where('vehicle_id', $vehicle->id)
                ->where('provider', $result['provider'])
                ->where('operation', IntegrationOperation::Publish->value)
                ->where('external_reference', $result['external_reference'])
                ->latest('last_attempt_at')
                ->latest()
                ->first();

            if ($existingResult) {
                return $existingResult;
            }
        }

        return IntegrationLog::create([
            'vehicle_id' => $vehicle->id,
            'provider' => $result['provider'],
            'operation' => IntegrationOperation::Publish->value,
            'status' => IntegrationStatus::Pending->value,
        ]);
    }
}
