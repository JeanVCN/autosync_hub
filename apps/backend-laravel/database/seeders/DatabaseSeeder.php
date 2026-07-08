<?php

namespace Database\Seeders;

use App\Enums\IntegrationOperation;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use App\Enums\VehicleStatus;
use App\Models\IntegrationLog;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = collect([
            ['CAR-001', 'Honda', 'Civic', 'EXL 2.0', 2020, 2021, 118900.00, 42000, 'flex', 'automatic', 'gray'],
            ['CAR-002', 'Toyota', 'Corolla', 'XEI 2.0', 2021, 2022, 132500.00, 31000, 'flex', 'automatic', 'silver'],
            ['CAR-003', 'Jeep', 'Compass', 'Longitude', 2022, 2022, 154900.00, 28000, 'diesel', 'automatic', 'white'],
            ['CAR-004', 'Volkswagen', 'T-Cross', 'Highline', 2021, 2021, 126900.00, 39000, 'flex', 'automatic', 'blue'],
            ['CAR-005', 'Chevrolet', 'Onix', 'Premier', 2020, 2021, 82900.00, 51000, 'flex', 'automatic', 'black'],
        ])->map(fn (array $data): Vehicle => Vehicle::updateOrCreate(
            ['external_code' => $data[0]],
            [
                'brand' => $data[1],
                'model' => $data[2],
                'version' => $data[3],
                'year' => $data[4],
                'model_year' => $data[5],
                'price' => $data[6],
                'mileage' => $data[7],
                'fuel_type' => $data[8],
                'transmission' => $data[9],
                'color' => $data[10],
                'description' => "{$data[1]} {$data[2]} {$data[3]} prepared for marketplace synchronization demo.",
                'status' => VehicleStatus::Active->value,
            ],
        ));

        $statuses = [
            IntegrationStatus::Published->value,
            IntegrationStatus::Processing->value,
            IntegrationStatus::Failed->value,
            IntegrationStatus::RequiresAction->value,
        ];

        foreach ($vehicles as $index => $vehicle) {
            foreach (IntegrationProvider::values() as $providerIndex => $provider) {
                $status = $statuses[($index + $providerIndex) % count($statuses)];

                IntegrationLog::updateOrCreate(
                    [
                        'vehicle_id' => $vehicle->id,
                        'provider' => $provider,
                        'operation' => IntegrationOperation::Publish->value,
                    ],
                    [
                        'status' => $status,
                        'external_reference' => strtoupper(str_replace('_', '', $provider)).'-'.$vehicle->id.$providerIndex.'00',
                        'request_payload' => [
                            'vehicle_external_code' => $vehicle->external_code,
                            'provider' => $provider,
                        ],
                        'response_payload' => [
                            'message' => 'Seeded provider status for presentation.',
                        ],
                        'error_message' => $status === IntegrationStatus::Failed->value ? 'Provider requires a richer version description.' : null,
                        'attempts' => 1,
                        'last_attempt_at' => now()->subMinutes(($index + 1) * ($providerIndex + 5)),
                    ],
                );
            }
        }
    }
}
