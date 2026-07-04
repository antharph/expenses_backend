<?php

namespace App\Enums;

use InvalidArgumentException;

enum AuthProvider: string
{
    case Email = 'email';
    case Google = 'google';
    case Apple = 'apple';
    case Facebook = 'facebook';

    public static function fromFirebaseSignInProvider(string $provider): self
    {
        return match ($provider) {
            'google.com' => self::Google,
            'apple.com' => self::Apple,
            'facebook.com' => self::Facebook,
            default => throw new InvalidArgumentException("Unsupported Firebase sign-in provider: {$provider}"),
        };
    }
}
