<?php

declare(strict_types=1);

/**
 * Operation DTO: loyalty (Cloud) program from getLoyaltyCustomerPrograms (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * `program_type`: 1 discount, 2 accumulation, 3 promotion, 4 price type.
 * `usage_type`: 0 chosen cards, 1 every card, 2 without a card.
 * A field the answer lacks is null, never 0 (a real usage type) or false.
 *
 * @see LoyaltyApi::customerPrograms()
 */
final readonly class LoyaltyProgramDto
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $startDate,
        public ?string $endDate,
        public ?string $comment,
        public ?bool $status,
        public ?int $programType,
        public ?int $usageType,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: isset($data['name']) ? (string) $data['name'] : null,
            startDate: isset($data['start_date']) ? (string) $data['start_date'] : null,
            endDate: isset($data['end_date']) ? (string) $data['end_date'] : null,
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            status: isset($data['status']) ? (bool) $data['status'] : null,
            programType: isset($data['program_type']) ? (int) $data['program_type'] : null,
            usageType: isset($data['usage_type']) ? (int) $data['usage_type'] : null,
            raw: $data,
        );
    }
}
