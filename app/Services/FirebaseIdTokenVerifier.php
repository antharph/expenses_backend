<?php

namespace App\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use stdClass;
use UnexpectedValueException;

class FirebaseIdTokenVerifier
{
    private const CERTS_URL = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';

    private const CACHE_KEY = 'firebase_securetoken_x509';

    /**
     * Verify a Firebase Auth ID token and return decoded claims.
     *
     * @throws InvalidArgumentException
     */
    public function verify(string $idToken): stdClass
    {
        $projectId = (string) config('services.firebase.project_id');
        if ($projectId === '') {
            throw new InvalidArgumentException('FIREBASE_PROJECT_ID is not configured.');
        }

        $certs = Cache::remember(self::CACHE_KEY, now()->addHours(12), function (): array {
            $response = Http::timeout(15)->get(self::CERTS_URL);
            if (! $response->successful()) {
                throw new UnexpectedValueException('Unable to fetch Firebase public certificates.');
            }

            $decoded = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($decoded)) {
                throw new UnexpectedValueException('Invalid Firebase certificate payload.');
            }

            return $decoded;
        });

        $keys = [];
        foreach ($certs as $kid => $pem) {
            if (! is_string($kid) || ! is_string($pem)) {
                continue;
            }
            $keys[$kid] = new Key($pem, 'RS256');
        }

        if ($keys === []) {
            throw new UnexpectedValueException('No Firebase signing keys available.');
        }

        try {
            $payload = JWT::decode($idToken, $keys);
        } catch (SignatureInvalidException|ExpiredException|UnexpectedValueException $e) {
            throw new InvalidArgumentException('Invalid Firebase ID token.', previous: $e);
        }

        $expectedIss = 'https://securetoken.google.com/'.$projectId;
        if (($payload->iss ?? null) !== $expectedIss) {
            throw new InvalidArgumentException('Invalid token issuer.');
        }

        if (($payload->aud ?? null) !== $projectId) {
            throw new InvalidArgumentException('Invalid token audience.');
        }

        if (empty($payload->sub)) {
            throw new InvalidArgumentException('Token is missing subject.');
        }

        return $payload;
    }
}
