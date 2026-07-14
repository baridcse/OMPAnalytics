<?php

namespace App\Integrations\Support;

use App\Models\Integration;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Mints ES256 JWTs for the App Store Connect API from user-provisioned team
 * keys (Issuer ID + Key ID + .p8 private key). Apple caps token lifetime at
 * 20 minutes and recommends reuse until expiry, so tokens are cached for
 * 15 minutes (4-minute safety buffer before the 19-minute exp claim).
 */
class AppStoreConnectTokenFactory
{
    public function tokenFor(Integration $integration): string
    {
        $issuerId = $integration->credential('issuer_id');
        $keyId = $integration->credential('key_id');
        $privateKey = $integration->credential('private_key');

        foreach (['issuer_id' => $issuerId, 'key_id' => $keyId, 'private_key' => $privateKey] as $name => $value) {
            if ($value === null || $value === '') {
                throw new RuntimeException(
                    "App Store Connect integration [{$integration->name}] is missing the [{$name}] credential."
                );
            }
        }

        $cacheKey = sprintf('asc:jwt:%d:%s', $integration->id, sha1($issuerId.$keyId.$privateKey));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($issuerId, $keyId, $privateKey) {
            $now = time();

            return JWT::encode(
                [
                    'iss' => $issuerId,
                    'iat' => $now,
                    'exp' => $now + 19 * 60,
                    'aud' => 'appstoreconnect-v1',
                ],
                $this->normalizePem($privateKey),
                'ES256',
                $keyId,
            );
        });
    }

    /**
     * Accept the .p8 contents either as raw PEM or base64-encoded PEM (the
     * latter makes storing a multiline key in an env var practical).
     */
    private function normalizePem(string $key): string
    {
        if (str_contains($key, '-----BEGIN')) {
            return $key;
        }

        $decoded = base64_decode($key, strict: true);

        if ($decoded === false || ! str_contains($decoded, '-----BEGIN')) {
            throw new RuntimeException(
                'App Store Connect private key must be PEM text or base64-encoded PEM.'
            );
        }

        return $decoded;
    }
}
