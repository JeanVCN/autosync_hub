<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'external_code' => 'CAR-'.$this->faker->unique()->numberBetween(100, 999),
            'brand' => $this->faker->randomElement(['Honda', 'Toyota', 'Jeep', 'Volkswagen', 'Chevrolet']),
            'model' => $this->faker->word(),
            'version' => $this->faker->words(2, true),
            'year' => $this->faker->numberBetween(2018, 2024),
            'model_year' => $this->faker->numberBetween(2019, 2025),
            'price' => $this->faker->randomFloat(2, 50000, 180000),
            'mileage' => $this->faker->numberBetween(0, 90000),
            'fuel_type' => $this->faker->randomElement(['flex', 'gasoline', 'diesel', 'hybrid']),
            'transmission' => $this->faker->randomElement(['automatic', 'manual']),
            'color' => $this->faker->safeColorName(),
            'description' => $this->faker->sentence(),
            'status' => VehicleStatus::Active->value,
        ];
    }
}
