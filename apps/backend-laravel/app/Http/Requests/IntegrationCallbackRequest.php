<?php

namespace App\Http\Requests;

use App\Enums\IntegrationOperation;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntegrationCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_external_code' => ['required', 'string', 'max:50'],
            'provider' => ['required', 'string', Rule::in(IntegrationProvider::values())],
            'operation' => ['required', 'string', Rule::in(IntegrationOperation::values())],
            'status' => ['required', 'string', Rule::in(IntegrationStatus::values())],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'response_payload' => ['nullable', 'array'],
            'error_message' => ['nullable', 'string'],
        ];
    }
}
