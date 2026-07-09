<?php

namespace App\Services\IntegrationHub;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class IntegrationHubClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds,
        private readonly ?string $token,
        private readonly string $callbackUrl,
        private readonly string $contractVersion,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            (string) Config::get('services.integration_hub.url'),
            (int) Config::get('services.integration_hub.timeout_seconds'),
            Config::get('services.integration_hub.token'),
            (string) Config::get('services.integration_hub.callback_url'),
            (string) Config::get('services.integration_hub.contract_version'),
        );
    }

    public function syncVehicle(Vehicle $vehicle, array $providers): array
    {
        $requestId = (string) Str::uuid();

        return [
            'contract_version' => $this->contractVersion,
            'request_id' => $requestId,
            'idempotency_key' => $this->buildIdempotencyKey($vehicle, $providers),
            'hub_url' => $this->baseUrl,
            'timeout_seconds' => $this->timeoutSeconds,
            'auth_configured' => filled($this->token),
            'callback_url' => $this->callbackUrl,
            'vehicle_id' => $vehicle->id,
            'vehicle_external_code' => $vehicle->external_code,
            'results' => collect($providers)
                ->map(fn (string $provider): array => $this->simulateProviderResult($vehicle, $provider))
                ->values()
                ->all(),
        ];
    }

    private function buildIdempotencyKey(Vehicle $vehicle, array $providers): string
    {
        return hash('sha256', implode('|', [
            $vehicle->external_code,
            $vehicle->updated_at?->timestamp ?? 'new',
            implode(',', $providers),
        ]));
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
