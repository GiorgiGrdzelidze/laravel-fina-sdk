<?php

declare(strict_types=1);

/**
 * Operation DTO: loyalty (Cloud) group from getLoyaltyCustomerGroups (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * A field the answer lacks is null.
 *
 * @see LoyaltyApi::customerGroups()
 */
final readonly class LoyaltyGroupDto
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $comment,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: isset($data['name']) ? (string) $data['name'] : null,
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            raw: $data,
        );
    }
}
