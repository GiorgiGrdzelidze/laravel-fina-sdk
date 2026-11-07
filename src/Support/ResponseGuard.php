<?php

declare(strict_types=1);

/**
 * Guard that inspects FINA API responses for the `ex` error field.
 */

namespace Fina\Sdk\Laravel\Support;

use Fina\Sdk\Laravel\Exceptions\FinaRemoteException;

/**
 * Inspects raw FINA API response arrays and throws if an error is present.
 *
 * FINA signals errors by including a non-null `ex` key in the JSON response.
 */
final class ResponseGuard
{
    /**
     * Throw a {@see FinaRemoteException} if the response contains a non-null `ex` field.
     *
     * @param  array<string, mixed>  $data  Decoded JSON response from the FINA API.
     * @param  string  $context  Human-readable context for the error message.
     *
     * @throws FinaRemoteException If `$data['ex']` is present and not null.
     */
    public static function throwIfEx(array $data, string $context = 'FINA response returned ex'): void
    {
        if (array_key_exists('ex', $data) && $data['ex'] !== null) {
            throw new FinaRemoteException($data['ex'], $context, $data);
        }
    }
}
