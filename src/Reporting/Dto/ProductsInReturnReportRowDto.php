<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Products In/Return Report response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * getProductsInReturnReport -> reports[] row: one line per product per
 * purchase (doc_type 16) or return-to-vendor (doc_type 32) document.
 */
final class ProductsInReturnReportRowDto
{
    public function __construct(
        public readonly string $date,
        public readonly string $docNum,
        public readonly int $docType,
        public readonly int $vendorId,
        public readonly int $storeId,
        public readonly int $productId,
        public readonly int $unitId,
        public readonly float $quantity,
        public readonly float $amount,
        public readonly float $vat,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            date: (string) ($row['date'] ?? ''),
            docNum: (string) ($row['doc_num'] ?? ''),
            docType: (int) ($row['doc_type'] ?? 0),
            vendorId: (int) ($row['vendor_id'] ?? 0),
            storeId: (int) ($row['store_id'] ?? 0),
            productId: (int) ($row['product_id'] ?? 0),
            unitId: (int) ($row['unit_id'] ?? 0),
            quantity: (float) ($row['quantity'] ?? 0.0),
            amount: (float) ($row['amount'] ?? 0.0),
            vat: (float) ($row['vat'] ?? 0.0),
            raw: $row,
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'doc_num' => $this->docNum,
            'doc_type' => $this->docType,
            'vendor_id' => $this->vendorId,
            'store_id' => $this->storeId,
            'product_id' => $this->productId,
            'unit_id' => $this->unitId,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'vat' => $this->vat,
            'raw' => $this->raw,
        ];
    }
}
