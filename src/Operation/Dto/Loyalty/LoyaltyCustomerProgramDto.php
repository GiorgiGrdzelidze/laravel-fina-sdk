<?php

declare(strict_types=1);

/**
 * Operation DTO: a program a loyalty card is enrolled in (getLoyaltyCustomer -> loyalty_programs[]).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

/**
 * `id` is the enrolment record, `program_id` the program (see getLoyaltyCustomerPrograms).
 * `end_date` is null for an open-ended enrolment. Any other field the answer lacks is null,
 * `status` included: an absent status is not "inactive".
 */
final readonly class LoyaltyCustomerProgramDto
{
    public function __construct(
        public int $id,
        public ?int $programId,
        public ?string $startDate,
        public ?string $endDate,
        public ?bool $status,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            programId: isset($data['program_id']) ? (int) $data['program_id'] : null,
            startDate: isset($data['start_date']) ? (string) $data['start_date'] : null,
            endDate: isset($data['end_date']) ? (string) $data['end_date'] : null,
            status: isset($data['status']) ? (bool) $data['status'] : null,
            raw: $data,
        );
    }
}
