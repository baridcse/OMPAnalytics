<?php

namespace App\Integrations\Support;

use App\Models\Integration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin authenticated HTTP wrapper for the App Store Connect API: bearer JWT,
 * bounded retries honoring Retry-After on 429s (Apple allows ~3,500 req/hr
 * per key), and pass-through of absolute pagination URLs (links.next).
 */
class AppStoreConnectClient
{
    public function __construct(private readonly AppStoreConnectTokenFactory $tokens) {}

    /**
     * Pass $query = null when the URL already carries its own query string
     * (e.g. an ASC links.next pagination URL) — an array here would replace it.
     * Report endpoints require Accept: application/a-gzip or Apple returns 406.
     *
     * @param  array<string, mixed>|null  $query
     * @param  array<string, string>  $headers
     */
    public function get(Integration $integration, string $pathOrUrl, ?array $query = null, array $headers = []): Response
    {
        return $this->pending($integration, $headers)->get($pathOrUrl, $query);
    }

    /**
     * @param  array<string, mixed>  $payload  JSON:API document
     */
    public function post(Integration $integration, string $path, array $payload): Response
    {
        return $this->pending($integration)->post($path, $payload);
    }

    public function delete(Integration $integration, string $path): Response
    {
        return $this->pending($integration)->delete($path);
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function pending(Integration $integration, array $headers = []): PendingRequest
    {
        return Http::withToken($this->tokens->tokenFor($integration))
            ->withHeaders($headers)
            ->baseUrl(config('integrations.providers.app_store.base_url'))
            ->retry(
                3,
                fn (int $attempt, $exception) => $this->retryDelayMs($exception),
                fn ($exception) => $this->shouldRetry($exception),
                throw: false,
            );
    }

    private function shouldRetry($exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response->status();

            return $status === 429 || $status >= 500;
        }

        return false;
    }

    private function retryDelayMs($exception): int
    {
        if ($exception instanceof RequestException) {
            $retryAfter = $exception->response->header('Retry-After');

            if ($retryAfter !== '' && is_numeric($retryAfter)) {
                return (int) $retryAfter * 1000;
            }
        }

        return 1000;
    }
}
