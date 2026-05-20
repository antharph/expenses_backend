<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\BudgetResetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBudgetRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reset_type' => ['required', Rule::enum(BudgetResetType::class)],
            'reset_days' => ['nullable', 'array'],
            'reset_days.*' => ['integer', 'min:1', 'max:366'],
            'rollover' => ['sometimes', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('reset_type');
                $days = $this->input('reset_days', []);

                if ($type === BudgetResetType::Manual->value) {
                    return;
                }

                if (! is_array($days) || $days === []) {
                    $validator->errors()->add(
                        'reset_days',
                        'Reset days are required for this reset type.',
                    );

                    return;
                }

                if ($type === BudgetResetType::DateFixed->value) {
                    foreach ($days as $day) {
                        if ((int) $day > 31) {
                            $validator->errors()->add(
                                'reset_days',
                                'Fixed-date reset days must be between 1 and 31.',
                            );
                            break;
                        }
                    }
                }

                if ($type === BudgetResetType::Interval->value && count($days) !== 1) {
                    $validator->errors()->add(
                        'reset_days',
                        'Interval budgets require exactly one reset interval.',
                    );
                }
            },
        ];
    }
}
