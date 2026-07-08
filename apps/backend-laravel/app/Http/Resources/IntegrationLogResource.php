<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'provider' => $this->provider->value,
            'operation' => $this->operation->value,
            'status' => $this->status->value,
            'external_reference' => $this->external_reference,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'error_message' => $this->error_message,
            'attempts' => $this->attempts,
            'last_attempt_at' => $this->last_attempt_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
