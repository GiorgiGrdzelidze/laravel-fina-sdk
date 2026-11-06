<?php

declare(strict_types=1);

/**
 * Operation DTO: service line of a getDoc* response.
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

final readonly class DocServiceLineDto
{
    public function __construct(
        public int $id,
        public float $quantity,
        public float $price,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            quantity: (float) ($data['quantity'] ?? 0.0),
            price: (float) ($data['price'] ?? 0.0),
            raw: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];
    }
}
