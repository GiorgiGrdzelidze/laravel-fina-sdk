<?php

declare(strict_types=1);

/**
 * Reporting DTO: row from the Entries Journal response.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

/**
 * Accounting entries journal row (getEntriesJournal -> journals[]).
 *
 * FINA returns one row per entry LINE, so several rows share the same `id`
 * and `version` (the operation they belong to). `dedupeKey()` is therefore an
 * operation-level key, never a line-level one.
 */
final class EntriesJournalRowDto
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
        public readonly float $amountCurrency,
        public readonly string $currency,
        public readonly int $staffId,
        public readonly string $debitAcc,
        public readonly string $debitComment,
        public readonly float $debitQuantity,
        public readonly string $creditAcc,
        public readonly string $creditComment,
        public readonly float $creditQuantity,
        public readonly int $projectId,
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
            amountCurrency: (float) ($row['amount_currency'] ?? ($row['amount'] ?? 0.0)),
            currency: (string) ($row['currency'] ?? ''),
            staffId: (int) ($row['staff_id'] ?? 0),
            debitAcc: (string) ($row['debit_acc'] ?? ''),
            debitComment: (string) ($row['debit_comment'] ?? ''),
            debitQuantity: (float) ($row['debit_quantity'] ?? 0.0),
            creditAcc: (string) ($row['credit_acc'] ?? ''),
            creditComment: (string) ($row['credit_comment'] ?? ''),
            creditQuantity: (float) ($row['credit_quantity'] ?? 0.0),
            projectId: (int) ($row['project_id'] ?? 0),
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
            'amount_currency' => $this->amountCurrency,
            'currency' => $this->currency,
            'staff_id' => $this->staffId,
            'debit_acc' => $this->debitAcc,
            'debit_comment' => $this->debitComment,
            'debit_quantity' => $this->debitQuantity,
            'credit_acc' => $this->creditAcc,
            'credit_comment' => $this->creditComment,
            'credit_quantity' => $this->creditQuantity,
            'project_id' => $this->projectId,
            'raw' => $this->raw,
        ];
    }

    /**
     * Operation-level key (all lines of one operation share it).
     */
    public function dedupeKey(): string
    {
        return 'idv:'.$this->id.':'.$this->version;
    }
}
