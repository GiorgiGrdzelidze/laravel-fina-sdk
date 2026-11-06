<?php

declare(strict_types=1);

/**
 * Operation DTO: response of updateRsStatus.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

final readonly class RsStatusResponse
{
    public function __construct(
        public bool $status,
        public mixed $ex,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (bool) ($data['status'] ?? false),
            $data['ex'] ?? null,
        );
    }
}
