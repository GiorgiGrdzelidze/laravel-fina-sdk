<?php

declare(strict_types=1);

/**
 * Operation DTO: material or product line of a getDocAdvProduction response.
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Production;

/**
 * `cost_percent` is sent on product lines only; null on materials. `quantity` is null when the
 * answer lacks it.
 */
final readonly class AdvProductionLineDto
{
    public function __construct(
        public int $id,
        public ?float $quantity,
        public ?float $costPercent,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            quantity: isset($data['quantity']) ? (float) $data['quantity'] : null,
            costPercent: isset($data['cost_percent']) ? (float) $data['cost_percent'] : null,
            raw: $data,
        );
    }
}
