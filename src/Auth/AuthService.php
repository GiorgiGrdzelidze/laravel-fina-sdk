<?php

declare(strict_types=1);

/**
 * Authentication service for the FINA Web API.
 */

namespace Fina\Sdk\Laravel\Auth;

use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Exceptions\FinaHttpException;
use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;
use Illuminate\Http\Client\RequestException;

/**
 * Handles FINA API authentication: fetching, caching, and refreshing bearer tokens.
 */
final readonly class AuthService
{
    /**
     * @param  FinaClient  $client  The parent FINA client instance.
     */
    public function __construct(
        private FinaClient $client
    ) {}

    /**
     * Fetch a new token from the FINA API and cache it.
     *
     * @return string The new bearer token.
     *
     * @throws FinaHttpException If the HTTP request fails.
     * @throws FinaRemoteException If the API returns an error or no token.
     */
    public function authenticate(): string
    {
        $url = $this->client->prefix('authentication').'/authenticate';

        try {
            $response = $this->client->http()
                ->post($url, [
                    'login' => $this->client->login(),
                    'password' => $this->client->password(),
                ])
                ->throw();
        } catch (RequestException $e) {
            $status = $e->response?->status() ?? 0;
            $body = $e->response?->body();
            throw new FinaHttpException($status, $body, $e);
        }

        $data = $response->json();
        $dto = AuthTokenResponse::fromArray(is_array($data) ? $data : []);

        if ($dto->ex !== null) {
            throw new FinaRemoteException($dto->ex, 'FINA authenticate returned ex');
        }

        if (! $dto->token) {
            throw new FinaRemoteException($data, 'FINA authenticate did not return token');
        }

        $this->client->tokenStore()->put($dto->token, $dto->expiresAt());

        return $dto->token;
    }

    /**
     * Get the cached token, or fetch a new one if none is cached.
     *
     * @return string The bearer token.
     *
     * @throws FinaHttpException If the HTTP request fails.
     * @throws FinaRemoteException If the API returns an error.
     */
    public function token(): string
    {
        $cached = $this->client->tokenStore()->get();

        return $cached ?: $this->authenticate();
    }

    /**
     * Clear the cached authentication token.
     */
    public function forgetToken(): void
    {
        $this->client->tokenStore()->forget();
    }
}
