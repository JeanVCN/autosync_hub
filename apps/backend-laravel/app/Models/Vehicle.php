<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_code',
        'brand',
        'model',
        'version',
        'year',
        'model_year',
        'price',
        'mileage',
        'fuel_type',
        'transmission',
        'color',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => VehicleStatus::class,
        ];
    }

    public function integrationLogs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    public function latestIntegrationLogs(): HasMany
    {
        return $this->integrationLogs()->latest('last_attempt_at')->latest();
    }
}
