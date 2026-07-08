<?php

namespace App\Services\IntegrationHub;

use App\Models\IntegrationLog;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class IntegrationCallbackService
{
    public function handle(array $payload): IntegrationLog
    {
        $vehicle = Vehicle::where('external_code', $payload['vehicle_external_code'])->first();

        if (! $vehicle) {
            throw (new ModelNotFoundException)->setModel(Vehicle::class, [$payload['vehicle_external_code']]);
        }

        return IntegrationLog::updateOrCreate(
            [
                'vehicle_id' => $vehicle->id,
                'provider' => $payload['provider'],
                'operation' => $payload['operation'],
                'external_reference' => $payload['external_reference'] ?? null,
            ],
            [
                'status' => $payload['status'],
                'response_payload' => $payload['response_payload'] ?? $payload,
                'error_message' => $payload['error_message'] ?? null,
                'attempts' => 1,
                'last_attempt_at' => Carbon::now(),
            ],
        );
    }
}
