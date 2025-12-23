<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_wallet_id' => [
                'required',
                'integer',
                'exists:wallets,id',
            ],
            'to_wallet_id' => [
                'required',
                'integer',
                'exists:wallets,id',
                'different:from_wallet_id', // Prevent self-transfer
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
            ]
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function messages(): array
    {
        return [
            'from_wallet_id.required' => 'Source wallet is required',
            'from_wallet_id.exists' => 'Source wallet not found',
            'to_wallet_id.required' => 'Destination wallet is required',
            'to_wallet_id.exists' => 'Destination wallet not found',
            'to_wallet_id.different' => 'Cannot transfer to the same wallet',
            'amount.required' => 'Amount is required',
            'amount.integer' => 'Amount must be a whole number',
            'amount.min' => 'Amount must be at least 1',
        ];
    }
}
