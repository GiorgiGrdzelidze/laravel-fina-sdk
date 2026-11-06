<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Realizes Journal response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * Sales journal row (getRealizesJournal -> journals[]).
 *
 * Covers product sales (doc_type 21), retail sales (23) and fixed-asset sales.
 * `pay_type`: 0 cash, 1 non-cash, 2 consignment, 3 credit, 4 cash/non-cash,
 * 5 free of charge, 6 other.
 */
final class RealizesJournalRowDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $version,
        public readonly string $date,
        public readonly string $docNum,
        public readonly ?string $waybillNum,
        public readonly int $docType,
        public readonly string $purpose,
        public readonly float $amount,
        public readonly int $staffId,
        public readonly string $currency,
        public readonly int $customerId,
        public readonly int $storeId,
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
            waybillNum: isset($row['waybill_num']) ? (string) $row['waybill_num'] : null,
            docType: (int) ($row['doc_type'] ?? 0),
            purpose: (string) ($row['purpose'] ?? ''),
            amount: (float) ($row['amount'] ?? 0.0),
            staffId: (int) ($row['staff_id'] ?? 0),
            currency: (string) ($row['currency'] ?? ''),
            customerId: (int) ($row['customer_id'] ?? 0),
            storeId: (int) ($row['store_id'] ?? 0),
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
            'waybill_num' => $this->waybillNum,
            'doc_type' => $this->docType,
            'purpose' => $this->purpose,
            'amount' => $this->amount,
            'staff_id' => $this->staffId,
            'currency' => $this->currency,
            'customer_id' => $this->customerId,
            'store_id' => $this->storeId,
            'pay_type' => $this->payType,
            'raw' => $this->raw,
        ];
    }

    public function dedupeKey(): string
    {
        return 'idv:'.$this->id.':'.$this->version;
    }
}
