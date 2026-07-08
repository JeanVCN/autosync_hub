<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_code' => $this->external_code,
            'brand' => $this->brand,
            'model' => $this->model,
            'version' => $this->version,
            'year' => $this->year,
            'model_year' => $this->model_year,
            'price' => (float) $this->price,
            'mileage' => $this->mileage,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'color' => $this->color,
            'description' => $this->description,
            'status' => $this->status->value,
            'integration_logs' => IntegrationLogResource::collection($this->whenLoaded('integrationLogs')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
