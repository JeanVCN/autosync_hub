<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntegrationSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'provider' => $this['provider'],
            'status' => $this['status'],
            'operation' => $this['operation'],
            'external_reference' => $this['external_reference'],
            'error_message' => $this['error_message'],
            'attempts' => $this['attempts'],
            'last_attempt_at' => $this['last_attempt_at'],
        ];
    }
}
