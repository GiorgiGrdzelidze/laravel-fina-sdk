<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Moves Journal response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * Internal transfer journal row (getMovesJournal -> journals[]).
 *
 * Covers product moves (doc_type 20) and fixed-asset moves (12). The row has
 * no currency: `amount` is the cost value of the moved goods.
 */
final class MovesJournalRowDto
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
        public readonly int $storeFromId,
        public readonly int $storeToId,
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
            storeFromId: (int) ($row['store_from_id'] ?? 0),
            storeToId: (int) ($row['store_to_id'] ?? 0),
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
            'store_from_id' => $this->storeFromId,
            'store_to_id' => $this->storeToId,
            'raw' => $this->raw,
        ];
    }

    public function dedupeKey(): string
    {
        return 'idv:'.$this->id.':'.$this->version;
    }
}
