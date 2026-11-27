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

    /**
     * A key the row does not carry stays null.
     *
     * @return array<int, array{field: string|null, value: string|null}>
     */
    public static function addFieldsAsSent(mixed $rows): array
    {
        return array_map(
            static function ($r): array {
                $row = (array) $r;

                return [
                    'field' => isset($row['field']) ? (string) $row['field'] : null,
                    'value' => isset($row['value']) ? (string) $row['value'] : null,
                ];
            },
            array_values((array) ($rows ?? []))
        );
    }
}
