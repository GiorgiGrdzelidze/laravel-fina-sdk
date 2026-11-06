<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Discount Cards Journal response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * getDiscountCardsJournal -> journals[] row (doc_type 118).
 *
 * `amount` is the customer's accumulated purchase total through the card;
 * the holder id comes under `customer` (not customer_id) in this journal.
 */
final class DiscountCardsJournalRowDto
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
        public readonly int $customerId,
        public readonly int $storeId,
        public readonly string $cardCode,
        public readonly int $discountId,
        public readonly bool $status,
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
            customerId: (int) ($row['customer'] ?? ($row['customer_id'] ?? 0)),
            storeId: (int) ($row['store_id'] ?? 0),
            cardCode: (string) ($row['card_code'] ?? ''),
            discountId: (int) ($row['discount_id'] ?? 0),
            status: (bool) ($row['status'] ?? false),
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
            'customer_id' => $this->customerId,
            'store_id' => $this->storeId,
            'card_code' => $this->cardCode,
            'discount_id' => $this->discountId,
            'status' => $this->status,
            'raw' => $this->raw,
        ];
    }

    public function dedupeKey(): string
    {
        return 'idv:'.$this->id.':'.$this->version;
    }
}
