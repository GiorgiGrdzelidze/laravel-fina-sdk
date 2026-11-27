<?php

declare(strict_types=1);

/**
 * Operation DTO: store (warehouse) from getStores.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Operation\Dto\Documents\DocLines;
use Fina\Sdk\Laravel\Operation\ReferenceApi;

/**
 * `add_fields` (10.0.20261027) are described by getStoreAdditionalFields. A field the answer lacks
 * is null; `addFields` too, so a FINA build that does not send them is not read as "no fields".
 *
 * @see ReferenceApi::storesTyped()
 */
final readonly class StoreDto
{
    /**
     * @param  array<int, array{field: string|null, value: string|null}>|null  $addFields
     */
    public function __construct(
        public int $id,
        public ?int $groupId,
        public ?string $name,
        public ?string $address,
        public ?int $projectId,
        public ?array $addFields = null,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            addFields: isset($data['add_fields']) ? DocLines::addFieldsAsSent($data['add_fields']) : null,
            raw: $data,
        );
    }
}
