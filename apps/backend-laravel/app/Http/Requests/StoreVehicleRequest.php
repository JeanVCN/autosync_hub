<?php

namespace App\Http\Requests;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'external_code' => ['required', 'string', 'max:50', 'unique:vehicles,external_code'],
            'brand' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'version' => ['nullable', 'string', 'max:120'],
            'year' => ['required', 'integer', 'between:1900,2100'],
            'model_year' => ['required', 'integer', 'between:1900,2100'],
            'price' => ['required', 'numeric', 'min:0'],
            'mileage' => ['required', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:50'],
            'transmission' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(VehicleStatus::values())],
        ];
    }
}
