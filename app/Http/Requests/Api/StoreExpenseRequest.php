<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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
        $hasReceipt = $this->hasFile('receipt');

        return [
            'receipt' => ['nullable', 'file', 'image', 'max:5120'],
            'item' => [Rule::requiredIf(! $hasReceipt), 'nullable', 'string', 'max:255'],
            'price' => [Rule::requiredIf(! $hasReceipt), 'nullable', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }
}
