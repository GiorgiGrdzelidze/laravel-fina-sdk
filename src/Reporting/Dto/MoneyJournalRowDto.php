<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Money Journal response (customers/vendors).
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * `ref_id` (getCustomersMoneyJournal, 10.0.20261027) is the FINA operation the money was
 * received or returned for, 0 when none; null when not sent (getVendorsMoneyJournal never sends it).
 */
final class MoneyJournalRowDto
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
        public readonly ?int $customerId,
        public readonly ?int $vendorId,
        public readonly int $payType,
        public readonly int $payTypeId,
        public readonly ?int $refId = null,
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
            customerId: isset($row['customer_id']) ? (int) $row['customer_id'] : null,
            vendorId: isset($row['vendor_id']) ? (int) $row['vendor_id'] : null,
            payType: (int) ($row['pay_type'] ?? 0),
            payTypeId: (int) ($row['pay_type_id'] ?? 0),
            refId: isset($row['ref_id']) ? (int) $row['ref_id'] : null,
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
            'vendor_id' => $this->vendorId,
            'pay_type' => $this->payType,
            'pay_type_id' => $this->payTypeId,
            'ref_id' => $this->refId,
        ];
    }
}
