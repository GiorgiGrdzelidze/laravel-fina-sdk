<?php

declare(strict_types=1);

/**
 * Operation DTO: group membership line for saveLoyaltyCustomer (10.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Contracts\ArrayPayload;

final class LoyaltyCustomerGroupLine implements ArrayPayload
{
    /**
     * @param  int  $id  Membership record ID (0 to add the card to a new group).
     * @param  int  $groupId  Loyalty group ID.
     */
    public function __construct(
        public readonly int $id,
        public readonly int $groupId,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'group_id' => $this->groupId,
        ];
    }
}
