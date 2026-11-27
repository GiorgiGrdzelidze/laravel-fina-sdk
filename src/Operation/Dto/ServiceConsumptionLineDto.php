<?php

declare(strict_types=1);

/**
 * Operation DTO: one material of a {@see ServiceConsumptionDto}.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

final readonly class ServiceConsumptionLineDto
{
    public function __construct(
        public int $productId,
        public ?float $quantity,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) ($data['product_id'] ?? 0),
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            raw: $data,
        );
    }
}
