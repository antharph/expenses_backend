<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'item' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }
}
