<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocDiscountCard.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\DocumentsApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Issues or updates a discount card. The holder fields `person_*` are in the 10.0 document only;
 * left null they are not sent, and the body is the one the 8.0 document shows.
 *
 * @see DocumentsApi::saveDiscountCard()
 */
final class DiscountCardPayload implements ValidatesPayload
{
    /**
     * @param  int  $id  Document ID (0 for new).
     * @param  int  $discountId  Discount type ID (see getDiscountTypes).
     * @param  bool  $status  Whether the card is active.
     */
    public function __construct(
        public readonly int $id,
        public readonly DateTimeInterface $date,
        public readonly string $numPrefix,
        public readonly int $num,
        public readonly string $purpose,
        public readonly int $customer,
        public readonly int $store,
        public readonly int $user,
        public readonly string $cardCode,
        public readonly int $discountId,
        public readonly bool $status,
        public readonly ?string $personCode = null,
        public readonly ?string $personName = null,
        public readonly ?string $personAddress = null,
        public readonly ?string $personTel = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'date' => FinaDate::toFina($this->date),
            'num_pfx' => $this->numPrefix,
            'num' => $this->num,
            'purpose' => $this->purpose,
            'customer' => $this->customer,
            'store' => $this->store,
            'user' => $this->user,
            'card_code' => $this->cardCode,
            'person_code' => $this->personCode,
            'person_name' => $this->personName,
            'person_address' => $this->personAddress,
            'person_tel' => $this->personTel,
            'discount_id' => $this->discountId,
            'status' => $this->status,
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'string'],
            'num_pfx' => ['nullable', 'string', 'max:20'],
            'num' => ['required', 'integer', 'min:0'],
            'purpose' => ['required', 'string', 'max:750'],
            'customer' => ['required', 'integer', 'min:1'],
            'store' => ['required', 'integer', 'min:1'],
            'user' => ['required', 'integer', 'min:1'],
            'card_code' => ['required', 'string'],
            'person_code' => ['nullable', 'string'],
            'person_name' => ['nullable', 'string'],
            'person_address' => ['nullable', 'string'],
            'person_tel' => ['nullable', 'string'],
            'discount_id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }
}
