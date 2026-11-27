<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveLoyaltyBonusPoint (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\LoyaltyApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Gives (`coeff` 1) or takes (`coeff` -1) points on a loyalty (Cloud) card; insert only.
 * The card goes out as `id`, as the document has it.
 *
 * @see LoyaltyApi::saveBonusPoint()
 */
final class LoyaltyBonusPointPayload implements ValidatesPayload
{
    /**
     * @param  int  $cardId  Loyalty card ID (sent as `id`).
     * @param  float  $amount  Points given or taken.
     * @param  int  $coeff  1 = give, -1 = take.
     */
    public function __construct(
        public readonly int $cardId,
        public readonly DateTimeInterface $date,
        public readonly float $amount,
        public readonly int $coeff,
        public readonly ?string $comment = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->cardId,
            'date' => FinaDate::toFina($this->date),
            'amount' => $this->amount,
            'coeff' => $this->coeff,
            'comment' => $this->comment,
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'coeff' => ['required', 'integer', 'in:1,-1'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'id' => 'loyalty card id',
        ];
    }
}
