<?php

namespace App\Models;

use App\Enums\IntegrationOperation;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'provider',
        'operation',
        'status',
        'external_reference',
        'request_payload',
        'response_payload',
        'error_message',
        'attempts',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'operation' => IntegrationOperation::class,
            'status' => IntegrationStatus::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
