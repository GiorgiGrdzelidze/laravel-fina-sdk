<?php

declare(strict_types=1);

/**
 * Exception for FINA API-level errors (non-null `ex` field in response).
 */

namespace Fina\Sdk\Laravel\Exceptions;

/**
 * Thrown when the FINA API returns a non-null `ex` field in its JSON response.
 *
 * The `$ex` property contains the original error value from the API; `$response` holds the
 * whole decoded body, so a caller can see an `id` FINA returned alongside the error.
 */
final class FinaRemoteException extends FinaException
{
    /**
     * @param  mixed  $ex  The raw `ex` value from the FINA response.
     * @param  string  $message  Human-readable context message.
     * @param  array<string, mixed>  $response  The whole decoded response body.
     */
    public function __construct(
        public readonly mixed $ex,
        string $message = 'FINA remote exception (ex returned)',
        public readonly array $response = [],
    ) {
        parent::__construct($message);
    }
}
