<?php

declare(strict_types=1);

/**
 * Operation DTO: one accumulation / spending of a loyalty (Cloud) card, from getLoyaltyBonusHistory (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * `id` is the FINA operation the points came from (`int?` in the document, null when no operation
 * stands behind the entry), `amount` that operation's value, `bonus` the points, `coeff` 1 accumulated
 * or -1 spent. A field the answer lacks is null.
 *
 * @see LoyaltyApi::bonusHistory()
 */
final readonly class LoyaltyBonusHistoryDto
{
    /**
     * @param  LoyaltyBonusProductDto[]|null  $products
     */
    public function __construct(
        public ?int $id,
        public ?string $tdate,
        public ?float $amount,
        public ?float $bonus,
        public ?int $coeff,
        public ?string $comment,
        public ?array $products,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            tdate: isset($data['tdate']) ? (string) $data['tdate'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            bonus: isset($data['bonus']) ? (float) $data['bonus'] : null,
            coeff: isset($data['coeff']) ? (int) $data['coeff'] : null,
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            products: isset($data['products'])
                ? array_map(
                    static fn ($p) => LoyaltyBonusProductDto::fromArray((array) $p),
                    array_values((array) $data['products'])
                )
                : null,
            raw: $data,
        );
    }
}
