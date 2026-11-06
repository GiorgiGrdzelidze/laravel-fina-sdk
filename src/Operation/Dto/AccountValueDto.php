<?php

declare(strict_types=1);

/**
 * Operation DTO: aggregated debit/credit turnover of one accounting account.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

final readonly class AccountValueDto
{
    public function __construct(
        public float $debitVal,
        public float $creditVal,
    ) {}

    public static function fromArray(array $data): self
    {
        $values = (array) ($data['values'] ?? $data);

        return new self(
            (float) ($values['debit_val'] ?? 0.0),
            (float) ($values['credit_val'] ?? 0.0),
        );
    }
}
