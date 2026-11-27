<?php

declare(strict_types=1);

/**
 * Operation DTO: materials a provided service consumes, from getServiceConsumptionMaterials (10.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Operation\ServicesApi;

/**
 * `id` is the provided service; `consumption` the products (goods / raw materials) one unit of it uses,
 * null when the answer lacks it.
 *
 * @see ServicesApi::consumptionMaterials()
 */
final readonly class ServiceConsumptionDto
{
    /**
     * @param  ServiceConsumptionLineDto[]|null  $consumption
     */
    public function __construct(
        public int $id,
        public ?array $consumption,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            consumption: isset($data['consumption'])
                ? array_map(
                    static fn ($c) => ServiceConsumptionLineDto::fromArray((array) $c),
                    array_values((array) $data['consumption'])
                )
                : null,
            raw: $data,
        );
    }
}
