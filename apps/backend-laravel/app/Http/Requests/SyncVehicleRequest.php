<?php

namespace App\Http\Requests;

use App\Enums\IntegrationProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'providers' => ['sometimes', 'array', 'min:1'],
            'providers.*' => ['required', 'string', Rule::in(IntegrationProvider::values())],
        ];
    }
}
