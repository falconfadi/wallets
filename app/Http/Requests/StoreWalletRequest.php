<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'owner_name' => ['required', 'string','max:50','unique:wallets,owner_name'],
            'currency_id' => [
                'required',
                'integer',
                'exists:currencies,id'
            ],
            'description' => ['nullable', 'string', 'max:1000']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Wallet name is required',
            'owner_name.required' => 'Owner name is required',
            'currency_id.required' => 'Currency is required',
            'currency_id.exists' => 'Selected currency does not exist',
        ];
    }
}
