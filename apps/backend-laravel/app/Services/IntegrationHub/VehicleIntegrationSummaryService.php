<?php

namespace App\Services\IntegrationHub;

use App\Enums\IntegrationProvider;
use App\Models\IntegrationLog;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class VehicleIntegrationSummaryService
{
    public function summarize(Vehicle $vehicle): array
    {
        $latestLogs = $vehicle->integrationLogs()
            ->latest('last_attempt_at')
            ->latest()
            ->get()
            ->unique(fn (IntegrationLog $log): string => $log->provider->value);

        return collect(IntegrationProvider::values())
            ->map(fn (string $provider): array => $this->summarizeProvider($provider, $latestLogs))
            ->values()
            ->all();
    }

    private function summarizeProvider(string $provider, Collection $latestLogs): array
    {
        /** @var IntegrationLog|null $log */
        $log = $latestLogs->first(fn (IntegrationLog $item): bool => $item->provider->value === $provider);

        if (! $log) {
            return [
                'provider' => $provider,
                'status' => 'not_synced',
                'operation' => null,
                'external_reference' => null,
                'error_message' => null,
                'attempts' => 0,
                'last_attempt_at' => null,
            ];
        }

        return [
            'provider' => $provider,
            'status' => $log->status->value,
            'operation' => $log->operation->value,
            'external_reference' => $log->external_reference,
            'error_message' => $log->error_message,
            'attempts' => $log->attempts,
            'last_attempt_at' => $log->last_attempt_at?->toISOString(),
        ];
    }
}
