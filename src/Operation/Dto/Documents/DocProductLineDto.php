<?php

declare(strict_types=1);

/**
 * Operation DTO: product line of a getDoc* response.
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * `price` is absent on moves (cost-only documents), `self_cost` is present on
 * moves and returns, `out_id` only on customer returns (0 = not linked to a
 * specific sale).
 */
final readonly class DocProductLineDto
{
    public function __construct(
        public int $id,
        public int $subId,
        public float $quantity,
        public ?float $price,
        public ?float $selfCost,
        public ?int $outId,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            subId: (int) ($data['sub_id'] ?? 0),
            quantity: (float) ($data['quantity'] ?? 0.0),
            price: isset($data['price']) ? (float) $data['price'] : null,
            selfCost: isset($data['self_cost']) ? (float) $data['self_cost'] : null,
            outId: isset($data['out_id']) ? (int) $data['out_id'] : null,
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'sub_id' => $this->subId,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'self_cost' => $this->selfCost,
            'out_id' => $this->outId,
        ], static fn ($v) => $v !== null);
    }
}
