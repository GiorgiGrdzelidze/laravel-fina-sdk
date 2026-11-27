<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveDocDiscountOperation (10.0.20261009).
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * Puts a discount card on a sale and adds the sale's amount to the card.
 *
 * @see LoyaltyApi::saveDiscountOperation()
 */
final class DiscountOperationPayload implements ValidatesPayload
{
    /**
     * @param  int  $cardId  Discount card ID.
     * @param  int  $refId  ID of the operation the card is used on.
     */
    public function __construct(
        public readonly int $cardId,
        public readonly int $refId,
    ) {}

    public function toArray(): array
    {
        return [
            'card_id' => $this->cardId,
            'ref_id' => $this->refId,
        ];
    }

    public function rules(): array
    {
        return [
            'card_id' => ['required', 'integer', 'min:1'],
            'ref_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'card_id' => 'discount card id',
            'ref_id' => 'reference operation id',
        ];
    }
}
