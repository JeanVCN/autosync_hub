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

        $values = [
            'status' => $payload['status'],
            'request_payload' => $payload,
            'response_payload' => $payload['response_payload'] ?? $payload,
            'error_message' => $payload['error_message'] ?? null,
            'last_attempt_at' => Carbon::now(),
        ];

        $pendingLog = IntegrationLog::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('provider', $payload['provider'])
            ->where('operation', $payload['operation'])
            ->where('status', 'pending')
            ->latest('last_attempt_at')
            ->latest()
            ->first();

        if ($pendingLog) {
            $pendingLog->update($values + [
                'external_reference' => $payload['external_reference'] ?? null,
                'attempts' => max((int) $pendingLog->attempts, 1),
            ]);

            return $pendingLog->refresh();
        }

        $log = IntegrationLog::firstOrNew([
            'vehicle_id' => $vehicle->id,
            'provider' => $payload['provider'],
            'operation' => $payload['operation'],
            'external_reference' => $payload['external_reference'] ?? null,
        ]);

        $attempts = max(
            (int) $log->attempts,
            (int) data_get($payload, 'response_payload.attempts', 1),
            1,
        );

        if (! $log->exists) {
            $attempts = max(
                $attempts,
                IntegrationLog::query()
                    ->where('vehicle_id', $vehicle->id)
                    ->where('provider', $payload['provider'])
                    ->where('operation', $payload['operation'])
                    ->count() + 1,
            );
        }

        $log->fill($values + ['attempts' => $attempts])->save();

        return $log->refresh();
    }
}
