<?php

declare(strict_types=1);

/**
 * Reporting DTO: typed response wrapper for the Moves Journal.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

final class MovesJournalResponseDto
{
    /**
     * @param  MovesJournalRowDto[]  $journals
     */
    public function __construct(
        public readonly array $journals,
        public readonly ?string $ex,
    ) {}

    public static function fromArray(array $data): self
    {
        $rows = array_map(
            static fn ($r) => MovesJournalRowDto::fromArray((array) $r),
            (array) ($data['journals'] ?? [])
        );

        return new self(
            journals: $rows,
            ex: isset($data['ex']) ? (string) $data['ex'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'journals' => array_map(static fn (MovesJournalRowDto $r) => $r->toArray(), $this->journals),
            'ex' => $this->ex,
        ];
    }
}
