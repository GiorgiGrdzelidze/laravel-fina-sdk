<?php

declare(strict_types=1);

/**
 * DTO for the FINA authentication endpoint response.
 */

namespace Fina\Sdk\Laravel\Auth;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Represents the JSON response from `POST /api/authentication/authenticate`.
 */
final readonly class AuthTokenResponse
{
    /**
     * @param  string|null  $token  The bearer token (null if authentication failed).
     * @param  mixed  $ex  The FINA error field (null on success).
     * @param  string|null  $validTo  When the token expires, as FINA sent it (10.0; null if absent).
     */
    public function __construct(
        public ?string $token,
        public mixed $ex,
        public ?string $validTo = null,
    ) {}

    /**
     * Build from raw FINA API response array.
     *
     * @param  array{token?: string, ex?: mixed, valid_to?: mixed}  $data
     */
    public static function fromArray(array $data): self
    {
        $token = isset($data['token']) && is_string($data['token']) && $data['token'] !== ''
            ? $data['token']
            : null;

        $validTo = isset($data['valid_to']) && is_string($data['valid_to']) && $data['valid_to'] !== ''
            ? $data['valid_to']
            : null;

        return new self($token, $data['ex'] ?? null, $validTo);
    }

    /**
     * `valid_to` as a date, or null when it is missing or not a plain date-time.
     * FINA sends it in UTC without naming a zone, so a value without one is read as UTC,
     * whatever the application's time zone; a value with its own offset or `Z` keeps it.
     */
    public function expiresAt(): ?DateTimeImmutable
    {
        if ($this->validTo === null
            || ! preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?$/', $this->validTo)) {
            return null;
        }

        try {
            return new DateTimeImmutable($this->validTo, new DateTimeZone('UTC'));
        } catch (Exception) {
            return null;
        }
    }
}
