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
            'budget_type_id' => ['required', 'integer', Rule::exists('budget_types', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reset_type' => [
                'required',
                Rule::in([
                    BudgetResetType::DateFixed->value,
                    BudgetResetType::Manual->value,
                ]),
            ],
            'reset_days' => ['nullable', 'array'],
            'reset_days.*' => ['integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'rollover' => ['sometimes', 'boolean'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $budgetTypeId = $this->input('budget_type_id');
                if ($budgetTypeId === null) {
                    return;
                }

                $budgetType = \App\Models\BudgetType::find($budgetTypeId);
                if ($budgetType) {
                    if ($budgetType->code === 'budget') {
                        if (! $this->filled('amount')) {
                            $validator->errors()->add('amount', 'The amount field is required for Budget type.');
                        }
                    } elseif ($budgetType->code === 'tracking') {
                        if ($this->filled('amount')) {
                            $validator->errors()->add('amount', 'The amount must be empty for Tracking type.');
                        }
                        $this->merge(['amount' => null, 'rollover' => false]);
                    }
                }

                $type = $this->input('reset_type');
                $days = $this->input('reset_days', []);

                if ($type === BudgetResetType::DateFixed->value) {
                    if (! is_array($days) || $days === []) {
                        $validator->errors()->add(
                            'reset_days',
                            'Reset days are required for this reset type.',
                        );

                        return;
                    }

                    if ($this->filled('start_date')) {
                        $validator->errors()->add(
                            'start_date',
                            'Start date is only used for manual budgets.',
                        );
                    }
                }

                if ($type === BudgetResetType::Manual->value && ! $this->filled('start_date')) {
                    $validator->errors()->add(
                        'start_date',
                        'A start date is required for manual budgets.',
                    );
                }

                $categoryIds = $this->input('category_ids', []);
                if (! is_array($categoryIds) || $categoryIds === []) {
                    return;
                }

                $overlappingBudget = Budget::query()
                    ->where('user_id', $this->user()?->id)
                    ->where('budget_type_id', $budgetTypeId)
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
