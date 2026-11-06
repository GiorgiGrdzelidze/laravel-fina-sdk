<?php

declare(strict_types=1);

/**
 * Reporting DTO: typed response wrapper for the Realizes Journal.
 */

namespace Fina\Sdk\Laravel\Reporting\Dto;

final class RealizesJournalResponseDto
{
    /**
     * @param  RealizesJournalRowDto[]  $journals
     */
    public function __construct(
        public readonly array $journals,
        public readonly ?string $ex,
    ) {}

    public static function fromArray(array $data): self
    {
        $rows = array_map(
            static fn ($r) => RealizesJournalRowDto::fromArray((array) $r),
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
            'journals' => array_map(static fn (RealizesJournalRowDto $r) => $r->toArray(), $this->journals),
            'ex' => $this->ex,
        ];
    }
}
