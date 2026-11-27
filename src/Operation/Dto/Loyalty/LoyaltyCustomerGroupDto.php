<?php

declare(strict_types=1);

/**
 * Operation DTO: a group a loyalty card belongs to (getLoyaltyCustomer -> loyalty_groups[]).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

/**
 * `id` is the membership record, `group_id` the group (see getLoyaltyCustomerGroups); null when
 * the answer lacks it.
 */
final readonly class LoyaltyCustomerGroupDto
{
    public function __construct(
        public int $id,
        public ?int $groupId,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            raw: $data,
        );
    }
}
