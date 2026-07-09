<?php

namespace App\Services\IntegrationHub;

use App\Enums\IntegrationStatus;
use App\Models\Vehicle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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
        $idempotencyKey = $this->buildIdempotencyKey($vehicle, $providers);
        $payload = $this->buildPayload($vehicle, $providers, $requestId, $idempotencyKey);

        $metadata = [
            'contract_version' => $this->contractVersion,
            'request_id' => $requestId,
            'idempotency_key' => $idempotencyKey,
            'hub_url' => $this->baseUrl,
            'timeout_seconds' => $this->timeoutSeconds,
            'auth_configured' => filled($this->token),
            'callback_url' => $this->callbackUrl,
            'vehicle_id' => $vehicle->id,
            'vehicle_external_code' => $vehicle->external_code,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout($this->timeoutSeconds)
                ->withHeaders($this->headers($requestId, $idempotencyKey))
                ->post($this->syncEndpoint(), $payload);
        } catch (ConnectionException $exception) {
            return $metadata + [
                'status' => 'failed',
                'message' => 'Integration Hub is unavailable.',
                'errors' => ['hub' => [$exception->getMessage()]],
                'results' => $this->failedResults($providers, 'Integration Hub is unavailable.'),
            ];
        }

        $body = $response->json() ?? [];

        if ($response->failed()) {
            return $metadata + [
                'status' => $body['status'] ?? 'failed',
                'message' => $body['message'] ?? 'Integration Hub rejected the sync request.',
                'errors' => $body['errors'] ?? ['hub' => ['Unexpected hub error.']],
                'results' => $this->failedResults($providers, $body['message'] ?? 'Integration Hub rejected the sync request.'),
            ];
        }

        return $metadata + [
            'status' => $body['status'] ?? 'accepted',
            'message' => $body['message'] ?? 'Sync request accepted for processing',
            'accepted_providers' => $body['accepted_providers'] ?? $providers,
            'results' => $body['results'] ?? [],
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

    private function buildPayload(Vehicle $vehicle, array $providers, string $requestId, string $idempotencyKey): array
    {
        return [
            'contract_version' => $this->contractVersion,
            'request_id' => $requestId,
            'idempotency_key' => $idempotencyKey,
            'callback_url' => $this->callbackUrl,
            'operation' => 'publish',
            'providers' => $providers,
            'vehicle' => [
                'id' => $vehicle->id,
                'external_code' => $vehicle->external_code,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'version' => $vehicle->version,
                'year' => $vehicle->year,
                'model_year' => $vehicle->model_year,
                'price' => (float) $vehicle->price,
                'mileage' => $vehicle->mileage,
                'fuel_type' => $vehicle->fuel_type,
                'transmission' => $vehicle->transmission,
                'color' => $vehicle->color,
                'description' => $vehicle->description,
                'status' => $vehicle->status->value,
                'updated_at' => $vehicle->updated_at?->toISOString(),
            ],
        ];
    }

    private function headers(string $requestId, string $idempotencyKey): array
    {
        $headers = [
            'X-Contract-Version' => $this->contractVersion,
            'X-Request-Id' => $requestId,
            'Idempotency-Key' => $idempotencyKey,
        ];

        if (filled($this->token)) {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }

        return $headers;
    }

    private function syncEndpoint(): string
    {
        return Str::finish($this->baseUrl, '/').'sync-requests';
    }

    private function failedResults(array $providers, string $message): array
    {
        return collect($providers)
            ->map(fn (string $provider): array => [
                'provider' => $provider,
                'operation' => 'publish',
                'status' => IntegrationStatus::Failed->value,
                'external_reference' => null,
                'error_message' => $message,
                'response_payload' => [
                    'message' => $message,
                ],
            ])
            ->values()
            ->all();
    }
}
