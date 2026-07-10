<?php

namespace App\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use stdClass;
use UnexpectedValueException;

class AppleIdTokenVerifier
{
    private const JWKS_URL = 'https://appleid.apple.com/auth/keys';

    private const CACHE_KEY = 'apple_sign_in_jwks';

    private const ISSUER = 'https://appleid.apple.com';

    /**
     * Verify an Apple Sign In identity token and return decoded claims.
     *
     * @throws InvalidArgumentException
     */
    public function verify(string $idToken): stdClass
    {
        $allowedAudiences = $this->allowedAudiences();
        if ($allowedAudiences === []) {
            throw new InvalidArgumentException('Apple Sign In is not configured.');
        }

        $keys = $this->signingKeys();

        try {
            $payload = JWT::decode($idToken, $keys);
        } catch (SignatureInvalidException|ExpiredException|UnexpectedValueException $e) {
            throw new InvalidArgumentException('Invalid Apple identity token.', previous: $e);
        }

        if (($payload->iss ?? null) !== self::ISSUER) {
            throw new InvalidArgumentException('Invalid Apple token issuer.');
        }

        $audience = (string) ($payload->aud ?? '');
        if ($audience === '' || ! in_array($audience, $allowedAudiences, true)) {
            throw new InvalidArgumentException('Invalid Apple token audience.');
        }

        if (empty($payload->sub)) {
            throw new InvalidArgumentException('Apple token is missing subject.');
        }

        return $payload;
    }

    /**
     * @return list<string>
     */
    public function allowedAudiences(): array
    {
        $audiences = array_filter([
            (string) config('services.apple.bundle_id'),
            (string) config('services.apple.services_id'),
        ]);

        return array_values(array_unique($audiences));
    }

    /**
     * @return array<string, \Firebase\JWT\Key>
     */
    private function signingKeys(): array
    {
        $jwks = Cache::remember(self::CACHE_KEY, now()->addHours(12), function (): array {
            $response = Http::timeout(15)->get(self::JWKS_URL);
            if (! $response->successful()) {
                throw new UnexpectedValueException('Unable to fetch Apple public keys.');
            }

            $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new UnexpectedValueException('Invalid Apple JWKS payload.');
            }

            return $decoded;
        });

        $keys = JWK::parseKeySet($jwks);
        if ($keys === []) {
            throw new UnexpectedValueException('No Apple signing keys available.');
        }

        return $keys;
    }
}
