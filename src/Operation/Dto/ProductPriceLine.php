<?php

declare(strict_types=1);

/**
 * Operation DTO: price line for saveProduct.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * `currency`, `discount_value`, `discount_start` and `discount_end` are in the 10.0 document
 * (10.0.20261009); left null they are not sent and FINA keeps its defaults (currency GEL).
 */
final class ProductPriceLine implements ArrayPayload
{
    /**
     * @param  int  $id  Price type ID (see getPriceTypes).
     * @param  float  $value  Price.
     * @param  float|null  $discountValue  Discounted price.
     */
    public function __construct(
        public readonly int $id,
        public readonly float $value,
        public readonly ?string $currency = null,
        public readonly ?float $discountValue = null,
        public readonly ?DateTimeInterface $discountStart = null,
        public readonly ?DateTimeInterface $discountEnd = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'value' => $this->value,
            'currency' => $this->currency,
            'discount_value' => $this->discountValue,
            'discount_start' => $this->discountStart ? FinaDate::toFina($this->discountStart) : null,
            'discount_end' => $this->discountEnd ? FinaDate::toFina($this->discountEnd) : null,
        ], static fn ($v) => $v !== null);
    }
}
