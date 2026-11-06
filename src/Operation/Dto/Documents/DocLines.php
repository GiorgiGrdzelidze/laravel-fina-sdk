<?php

declare(strict_types=1);

/**
 * Shared mapping helpers for typed document DTOs.
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

final class DocLines
{
    /**
     * @return DocProductLineDto[]
     */
    public static function products(mixed $rows): array
    {
        return array_map(
            static fn ($r) => DocProductLineDto::fromArray((array) $r),
            array_values((array) ($rows ?? []))
        );
    }

    /**
     * @return DocServiceLineDto[]
     */
    public static function services(mixed $rows): array
    {
        return array_map(
            static fn ($r) => DocServiceLineDto::fromArray((array) $r),
            array_values((array) ($rows ?? []))
        );
    }

    /**
     * @return array<int, array{field: string, value: string}>
     */
    public static function addFields(mixed $rows): array
    {
        return array_map(
            static fn ($r) => [
                'field' => (string) (((array) $r)['field'] ?? ''),
                'value' => (string) (((array) $r)['value'] ?? ''),
            ],
            array_values((array) ($rows ?? []))
        );
    }
}
