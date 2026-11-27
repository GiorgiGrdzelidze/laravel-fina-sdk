<?php

declare(strict_types=1);

/**
 * Operation DTO: response from saveDocDiscountOperation (10.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

/**
 * The 10.0 document answers `{res, ex}` although the method is a save. `res` is null when the
 * answer has none: that is not "not applied", so do not send the operation again on it. `raw` keeps
 * the whole answer, so an `id` FINA might send as well is not lost.
 */
final readonly class DiscountOperationResponse
{
    /**
     * @param  bool|null  $res  Whether the operation succeeded; null when the answer has no `res`.
     * @param  mixed  $ex  Error field (null on success).
     * @param  array<string, mixed>  $raw  The whole decoded response.
     */
    public function __construct(
        public ?bool $res,
        public mixed $ex,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data  Raw API response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['res']) ? (bool) $data['res'] : null,
            $data['ex'] ?? null,
            $data,
        );
    }
}
