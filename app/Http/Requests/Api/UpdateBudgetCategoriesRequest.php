<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBudgetCategoriesRequest extends FormRequest
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
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $categoryIds = $this->input('category_ids', []);
                if (! is_array($categoryIds) || $categoryIds === []) {
                    return;
                }

                $budget = $this->route('budget');
                $budgetId = $budget instanceof Budget ? $budget->id : null;

                $overlappingBudget = Budget::query()
                    ->where('user_id', $this->user()?->id)
                    ->when($budgetId !== null, static function ($query) use ($budgetId): void {
                        $query->whereKeyNot($budgetId);
                    })
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
