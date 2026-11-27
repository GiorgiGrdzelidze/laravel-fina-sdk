<?php

declare(strict_types=1);

/**
 * Operation DTO: material a provided service used up (saveDocProvidedService, 10.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Contracts\ArrayPayload;

/**
 * Used in {@see ServiceLine::$consumedMaterials}.
 */
final class ConsumedMaterialLine implements ArrayPayload
{
    /**
     * @param  int  $id  Product (goods / raw material) ID.
     * @param  float  $quantity  Quantity used.
     */
    public function __construct(
        public readonly int $id,
        public readonly float $quantity,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
        ];
    }
}
