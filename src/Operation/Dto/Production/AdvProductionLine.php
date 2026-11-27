<?php

declare(strict_types=1);

/**
 * Operation DTO: material or product line for saveDocAdvProduction (10.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Production;

use Fina\Sdk\Laravel\Contracts\ArrayPayload;

/**
 * Used in {@see AdvProductionPayload::$materials} (without `costPercent`) and
 * {@see AdvProductionPayload::$products} (with it).
 */
final class AdvProductionLine implements ArrayPayload
{
    /**
     * @param  int  $id  Product ID.
     * @param  float  $quantity  Quantity consumed (material) or made (product).
     * @param  float|null  $costPercent  Product lines: share of the materials' cost, in percent.
     */
    public function __construct(
        public readonly int $id,
        public readonly float $quantity,
        public readonly ?float $costPercent = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'quantity' => $this->quantity,
            'cost_percent' => $this->costPercent,
        ], static fn ($v) => $v !== null);
    }
}
