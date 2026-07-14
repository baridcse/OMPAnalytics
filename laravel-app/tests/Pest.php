<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Throwaway EC P-256 key pair for App Store Connect JWT tests — no real
 * Apple credentials are ever used in the suite.
 *
 * @return array{0: string, 1: string} [privatePem, publicPem]
 */
function makeAscTestKeyPair(): array
{
    $resource = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
    ]);

    openssl_pkey_export($resource, $privatePem);
    $publicPem = openssl_pkey_get_details($resource)['key'];

    return [$privatePem, $publicPem];
}

/**
 * Load a JSON/TSV fixture, substituting {{PLACEHOLDER}} tokens so date
 * fields stay relative to the test run instead of going stale.
 *
 * @param  array<string, string>  $replacements
 */
function appStoreFixture(string $name, array $replacements = []): string
{
    $contents = file_get_contents(base_path("tests/Fixtures/AppStore/{$name}"));

    return strtr($contents, $replacements);
}
