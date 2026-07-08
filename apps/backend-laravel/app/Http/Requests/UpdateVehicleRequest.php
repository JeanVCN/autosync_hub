<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')?->id;

        return [
            'external_code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('vehicles', 'external_code')->ignore($vehicleId)],
            'brand' => ['sometimes', 'required', 'string', 'max:80'],
            'model' => ['sometimes', 'required', 'string', 'max:80'],
            'version' => ['nullable', 'string', 'max:120'],
            'year' => ['sometimes', 'required', 'integer', 'between:1900,2100'],
            'model_year' => ['sometimes', 'required', 'integer', 'between:1900,2100'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'mileage' => ['sometimes', 'required', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(VehicleStatus::values())],
        ];
    }
}
