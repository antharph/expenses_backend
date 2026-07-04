<?php

namespace App\Models;

use App\Enums\AuthProvider;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'firebase_uid', 'password_auth_enabled', 'auth_provider', 'timezone'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'password_auth_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * IANA timezone for expense calendar days and API date formatting.
     */
    public function displayTimezone(): string
    {
        return self::normalizeTimezone($this->timezone);
    }

    public static function normalizeTimezone(?string $timezone): string
    {
        if ($timezone === null || $timezone === '') {
            return 'UTC';
        }

        try {
            new \DateTimeZone($timezone);
        } catch (\Exception) {
            return 'UTC';
        }

        return $timezone;
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<Budget, $this>
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * @return array{id: int, name: string, email: string, password_auth_enabled: bool, auth_provider: string}
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password_auth_enabled' => (bool) $this->password_auth_enabled,
            'auth_provider' => $this->authProviderValue(),
        ];
    }

    public function authProviderValue(): string
    {
        $provider = $this->auth_provider;

        if (is_string($provider) && $provider !== '') {
            return $provider;
        }

        return AuthProvider::Email->value;
    }
}
