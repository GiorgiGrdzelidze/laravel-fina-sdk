<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Productions Journal response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * getProductionsJournal -> journals[] row.
 *
 * `production_type`: 0 production, 1 disassembly, 2 part added / repair,
 * 3 part separated.
 */
final class ProductionsJournalRowDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $version,
        public readonly string $date,
        public readonly string $docNum,
        public readonly int $docType,
        public readonly string $purpose,
        public readonly float $amount,
        public readonly int $storeId,
        public readonly int $productionType,
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
            storeId: (int) ($row['store_id'] ?? 0),
            productionType: (int) ($row['production_type'] ?? 0),
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
            'store_id' => $this->storeId,
            'production_type' => $this->productionType,
            'raw' => $this->raw,
        ];
    }

    public function dedupeKey(): string
    {
        return 'idv:'.$this->id.':'.$this->version;
    }
}
