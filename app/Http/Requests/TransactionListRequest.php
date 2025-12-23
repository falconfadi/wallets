<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['deposit', 'withdrawal'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'sort_by' => ['nullable', 'string', Rule::in(['transaction_date', 'amount', 'created_at'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Type must be either deposit or withdrawal',
            'start_date.date_format' => 'Start date must be in format YYYY-MM-DD',
            'end_date.date_format' => 'End date must be in format YYYY-MM-DD',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'per_page.max' => 'Per page cannot exceed 100 records',
        ];
    }
}
