<?php

namespace App\Http\Requests\Api;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteUserAccountRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        if ($user !== null && $user->password_auth_enabled) {
            return [
                'password' => $this->currentPasswordRules(),
            ];
        }

        return [];
    }
}
