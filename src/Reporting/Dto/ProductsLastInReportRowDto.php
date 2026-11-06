<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Products Last-In Report response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * getProductsLastInReport -> reports[] row: the last purchase of each product
 * inside the requested period. `id` is the product id.
 */
final class ProductsLastInReportRowDto
{
    public function __construct(
        public readonly int $id,
        public readonly float $quantity,
        public readonly float $price,
        public readonly int $vendorId,
        public readonly string $currency,
        public readonly float $rate,
        public readonly string $waybillDate,
        public readonly string $waybillNum,
        public readonly string $purpose,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            quantity: (float) ($row['quantity'] ?? 0.0),
            price: (float) ($row['price'] ?? 0.0),
            vendorId: (int) ($row['vendor_id'] ?? 0),
            currency: (string) ($row['currency'] ?? ''),
            rate: (float) ($row['rate'] ?? 0.0),
            waybillDate: (string) ($row['waybill_date'] ?? ''),
            waybillNum: (string) ($row['waybill_num'] ?? ''),
            purpose: (string) ($row['purpose'] ?? ''),
            raw: $row,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'vendor_id' => $this->vendorId,
            'currency' => $this->currency,
            'rate' => $this->rate,
            'waybill_date' => $this->waybillDate,
            'waybill_num' => $this->waybillNum,
            'purpose' => $this->purpose,
            'raw' => $this->raw,
        ];
    }
}
