<?php

namespace App\Services\IntegrationHub;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\Vehicle;
use Illuminate\Support\Str;

class IntegrationHubClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self((string) config('services.integration_hub.url'));
    }

    public function syncVehicle(Vehicle $vehicle, array $providers): array
    {
        return [
            'hub_url' => $this->baseUrl,
            'vehicle_id' => $vehicle->id,
            'vehicle_external_code' => $vehicle->external_code,
            'results' => collect($providers)
                ->map(fn (string $provider): array => $this->simulateProviderResult($vehicle, $provider))
                ->values()
                ->all(),
        ];
    }

    private function simulateProviderResult(Vehicle $vehicle, string $provider): array
    {
        if ($provider === IntegrationProvider::Icarros->value && blank($vehicle->version)) {
            return [
                'provider' => $provider,
                'status' => IntegrationStatus::Failed->value,
                'external_reference' => null,
                'error_message' => 'Version field is required by provider',
            ];
        }

        $status = match ($provider) {
            IntegrationProvider::MercadoLivre->value => IntegrationStatus::Processing->value,
            default => IntegrationStatus::Published->value,
        };

        return [
            'provider' => $provider,
            'status' => $status,
            'external_reference' => strtoupper(Str::slug($provider, '')).'-'.$vehicle->id.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'error_message' => null,
        ];
    }
}
