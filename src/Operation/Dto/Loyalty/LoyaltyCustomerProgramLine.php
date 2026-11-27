<?php

declare(strict_types=1);

/**
 * Operation DTO: program enrolment line for saveLoyaltyCustomer (10.0).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Contracts\ArrayPayload;

final class LoyaltyCustomerProgramLine implements ArrayPayload
{
    /**
     * @param  int  $id  Enrolment record ID (0 to enrol the card in a new program).
     * @param  int  $programId  Loyalty program ID.
     * @param  bool  $status  Whether the enrolment is active.
     */
    public function __construct(
        public readonly int $id,
        public readonly int $programId,
        public readonly bool $status,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'program_id' => $this->programId,
            'status' => $this->status,
        ];
    }
}
