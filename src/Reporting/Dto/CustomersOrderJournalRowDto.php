<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Customers Order Journal response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * getCustomersOrderJournal -> journals[] row (doc_type 8).
 *
 * `order_status`: 1 active, 2 received, 3 cancelled.
 */
final class CustomersOrderJournalRowDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $version,
        public readonly string $date,
        public readonly string $docNum,
        public readonly int $docType,
        public readonly string $purpose,
        public readonly float $amount,
        public readonly int $staffId,
        public readonly string $currency,
        public readonly int $customerId,
        public readonly int $storeId,
        public readonly int $orderStatus,
        public readonly int $payType,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            version: (string) ($row['version'] ?? ''),
            date: (string) ($row['date'] ?? ''),
            docNum: (string) ($row['doc_num'] ?? ''),
            docType: (int) ($row['doc_type'] ?? 0),
            purpose: (string) ($row['purpose'] ?? ''),
            amount: (float) ($row['amount'] ?? 0.0),
            staffId: (int) ($row['staff_id'] ?? 0),
            currency: (string) ($row['currency'] ?? ''),
            customerId: (int) ($row['customer_id'] ?? 0),
            storeId: (int) ($row['store_id'] ?? 0),
            orderStatus: (int) ($row['order_status'] ?? 0),
            payType: (int) ($row['pay_type'] ?? 0),
            raw: $row,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'date' => $this->date,
            'doc_num' => $this->docNum,
            'doc_type' => $this->docType,
            'purpose' => $this->purpose,
            'amount' => $this->amount,
            'staff_id' => $this->staffId,
            'currency' => $this->currency,
            'customer_id' => $this->customerId,
            'store_id' => $this->storeId,
            'order_status' => $this->orderStatus,
            'pay_type' => $this->payType,
            'raw' => $this->raw,
        ];
    }

    public function dedupeKey(): string
    {
        return 'idv:'.$this->id.':'.$this->version;
    }
}
