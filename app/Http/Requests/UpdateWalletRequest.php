<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'owner_name' => ['sometimes', 'string', 'max:255'],
            'currency_id' => [
                'sometimes',
                'integer',
                'exists:currencies,id'
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            // Balance cannot be updated through this endpoint
        ];
    }

    public function messages(): array
    {
        return [
            'currency_id.exists' => 'Selected currency does not exist',
        ];
    }
}
