<?php

use App\Integrations\Support\AppStoreConnectTokenFactory;
use App\Models\Integration;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function makeAscIntegration(array $credentials): Integration
{
    return Integration::factory()->create([
        'provider' => 'app_store',
        'name' => 'ASC test',
        'credentials' => $credentials,
    ]);
}

beforeEach(function () {
    [$this->privatePem, $this->publicPem] = makeAscTestKeyPair();
    $this->factory = new AppStoreConnectTokenFactory;
});

it('mints a valid ES256 token with Apple-required header and claims', function () {
    $integration = makeAscIntegration([
        'issuer_id' => 'issuer-abc',
        'key_id' => 'KEY123',
        'private_key' => $this->privatePem,
    ]);

    $token = $this->factory->tokenFor($integration);

    [$header64] = explode('.', $token);
    $header = json_decode(JWT::urlsafeB64Decode($header64), true);
    expect($header['alg'])->toBe('ES256')
        ->and($header['kid'])->toBe('KEY123');

    // Signature must verify against the matching public key.
    $claims = (array) JWT::decode($token, new Key($this->publicPem, 'ES256'));
    expect($claims['iss'])->toBe('issuer-abc')
        ->and($claims['aud'])->toBe('appstoreconnect-v1')
        ->and($claims['exp'] - $claims['iat'])->toBeLessThanOrEqual(20 * 60);
});

it('reuses the cached token until expiry', function () {
    $integration = makeAscIntegration([
        'issuer_id' => 'issuer-abc',
        'key_id' => 'KEY123',
        'private_key' => $this->privatePem,
    ]);

    expect($this->factory->tokenFor($integration))
        ->toBe($this->factory->tokenFor($integration));
});

it('accepts a base64-encoded PEM private key', function () {
    $integration = makeAscIntegration([
        'issuer_id' => 'issuer-abc',
        'key_id' => 'KEY123',
        'private_key' => base64_encode($this->privatePem),
    ]);

    $claims = (array) JWT::decode($this->factory->tokenFor($integration), new Key($this->publicPem, 'ES256'));

    expect($claims['iss'])->toBe('issuer-abc');
});

it('throws a clear error when a credential is missing', function () {
    $integration = makeAscIntegration([
        'issuer_id' => 'issuer-abc',
        'key_id' => 'KEY123',
        // private_key missing
    ]);

    $this->factory->tokenFor($integration);
})->throws(RuntimeException::class, 'missing the [private_key] credential');

it('rejects a key that is neither PEM nor base64 PEM', function () {
    $integration = makeAscIntegration([
        'issuer_id' => 'issuer-abc',
        'key_id' => 'KEY123',
        'private_key' => 'not-a-key',
    ]);

    $this->factory->tokenFor($integration);
})->throws(RuntimeException::class, 'PEM');
