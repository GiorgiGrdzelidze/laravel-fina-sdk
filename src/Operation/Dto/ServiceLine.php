<?php

declare(strict_types=1);

/**
 * Operation DTO: service line item used in document payloads.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Contracts\ArrayPayload;

/**
 * `consumedMaterials` (sent as `consumed_materials`) is documented for saveDocProvidedService only
 * (10.0); left null it is not sent.
 */
final class ServiceLine implements ArrayPayload
{
    /**
     * @param  ConsumedMaterialLine[]|null  $consumedMaterials
     */
    public function __construct(
        public readonly int $id,
        public readonly float $quantity = 0.0,
        public readonly float $price = 0.0,
        public readonly ?array $consumedMaterials = null,
    ) {}

    public function toArray(): array
    {
        $line = [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price' => $this->price,
        ];

        if ($this->consumedMaterials !== null) {
            $line['consumed_materials'] = array_map(fn (ConsumedMaterialLine $m) => $m->toArray(), $this->consumedMaterials);
        }

        return $line;
    }
}
