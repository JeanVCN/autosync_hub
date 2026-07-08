<?php

namespace Database\Factories;

use App\Enums\IntegrationOperation;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationLogFactory extends Factory
{
    public function definition(): array
    {
        $provider = $this->faker->randomElement(IntegrationProvider::values());
        $status = $this->faker->randomElement(IntegrationStatus::values());

        return [
            'vehicle_id' => Vehicle::factory(),
            'provider' => $provider,
            'operation' => IntegrationOperation::Publish->value,
            'status' => $status,
            'external_reference' => strtoupper(str_replace('_', '', $provider)).'-'.$this->faker->numberBetween(100000, 999999),
            'request_payload' => ['provider' => $provider],
            'response_payload' => ['message' => 'Simulated provider response'],
            'error_message' => $status === IntegrationStatus::Failed->value ? 'Provider rejected a required field.' : null,
            'attempts' => 1,
            'last_attempt_at' => now()->subMinutes($this->faker->numberBetween(1, 300)),
        ];
    }
}
