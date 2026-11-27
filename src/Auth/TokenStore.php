<?php

declare(strict_types=1);

/**
 * Cache-based storage for the FINA API authentication token.
 */

namespace Fina\Sdk\Laravel\Auth;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Stores, retrieves, and invalidates the FINA API bearer token using Laravel's cache.
 */
final class TokenStore
{
    /** A token is dropped this long before FINA's `valid_to`. */
    private const EXPIRY_MARGIN_SECONDS = 3600;

    /** How long a token is kept when its `valid_to` is already past or closer than the margin. */
    private const SHORT_TTL_SECONDS = 60;

    /**
     * @param  string  $cacheKey  Unique cache key (includes base URL + login hash).
     * @param  int  $ttlSeconds  Time-to-live for the cached token in seconds.
     */
    public function __construct(
        private readonly string $cacheKey,
        private readonly int $ttlSeconds
    ) {}

    /**
     * Retrieve the cached authentication token.
     *
     * @return string|null The token, or null if not cached / empty.
     */
    public function get(): ?string
    {
        $token = Cache::get($this->cacheKey);

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * Store an authentication token in the cache.
     *
     * @param  string  $token  The bearer token to cache.
     * @param  DateTimeInterface|null  $expiresAt  FINA's `valid_to`; it can shorten the lifetime, never extend it.
     */
    public function put(string $token, ?DateTimeInterface $expiresAt = null): void
    {
        Cache::put($this->cacheKey, $token, $this->ttlFor($expiresAt));
    }

    /**
     * Seconds a token is kept: until an hour before `$expiresAt`, at most the configured TTL.
     * When `$expiresAt` is already past or less than an hour away, 60 seconds (at most the configured
     * TTL), so the first call after that minute logs in again. Without `$expiresAt`, the configured TTL.
     */
    public function ttlFor(?DateTimeInterface $expiresAt): int
    {
        if ($expiresAt === null) {
            return $this->ttlSeconds;
        }

        $left = $expiresAt->getTimestamp() - Carbon::now()->getTimestamp() - self::EXPIRY_MARGIN_SECONDS;

        return min($left > 0 ? $left : self::SHORT_TTL_SECONDS, $this->ttlSeconds);
    }

    /**
     * Remove the cached authentication token.
     */
    public function forget(): void
    {
        Cache::forget($this->cacheKey);
    }
}
