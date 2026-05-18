<?php

namespace App\Http\Requests\Api;

use App\Support\ExpenseWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class WeeklyExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'year' => $this->route('year'),
            'week' => $this->route('week'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'week' => ['required', 'integer', 'min:1', 'max:52'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $year = (int) $this->input('year');
            $week = (int) $this->input('week');

            $maxWeek = ExpenseWeek::weeksInYear($year);
            if ($week > $maxWeek) {
                $validator->errors()->add(
                    'week',
                    "Week must be between 1 and {$maxWeek} for year {$year}.",
                );
            }

            if (! ExpenseWeek::weekOverlapsYear($year, $week)) {
                $validator->errors()->add('week', 'The selected week does not fall within the given year.');
            }
        });
    }
}
