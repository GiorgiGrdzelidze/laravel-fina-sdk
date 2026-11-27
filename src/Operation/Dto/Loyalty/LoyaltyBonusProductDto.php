<?php

declare(strict_types=1);

/**
 * Operation DTO: product of a {@see LoyaltyBonusHistoryDto}.
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

final readonly class LoyaltyBonusProductDto
{
    public function __construct(
        public int $id,
        public ?float $quantity,
        public ?float $price,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            price: isset($data['price']) ? (float) $data['price'] : null,
            raw: $data,
        );
    }
}
