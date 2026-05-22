<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\BudgetResetType;
use App\Models\Budget;
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
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('reset_type');
                $days = $this->input('reset_days', []);

                if ($type !== BudgetResetType::Manual->value) {
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
                }

                $categoryIds = $this->input('category_ids', []);
                if (! is_array($categoryIds) || $categoryIds === []) {
                    return;
                }

                $overlappingBudget = Budget::query()
                    ->where('user_id', $this->user()?->id)
                    ->whereHas('categories', static function ($query) use ($categoryIds): void {
                        $query->whereIn('categories.id', $categoryIds);
                    })
                    ->exists();

                if ($overlappingBudget) {
                    $validator->errors()->add(
                        'category_ids',
                        'One or more selected categories are already assigned to another active budget.',
                    );
                }
            },
        ];
    }
}
