<?php

declare(strict_types=1);

/**
 * Operation DTO: loyalty (Cloud) card from getLoyaltyCustomers / getLoyaltyCustomer (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use Fina\Sdk\Laravel\Operation\LoyaltyApi;

/**
 * `code` is the holder's personal number, `qr_code` the card's unique number. `sex`: true male,
 * false female. `cerate_date` is read under that name, as the 10.0 document spells it in every
 * example and field list; `raw` keeps the answer as FINA sent it. A field the answer lacks is null:
 * `loyalty_programs` / `loyalty_groups` come with getLoyaltyCustomer only, so a card from the list
 * has them null, and null passed on to {@see LoyaltyCustomerPayload} leaves the card's lists as they are.
 *
 * @see LoyaltyApi::customers()
 * @see LoyaltyApi::customer()
 */
final readonly class LoyaltyCustomerDto
{
    /**
     * @param  LoyaltyCustomerProgramDto[]|null  $loyaltyPrograms
     * @param  LoyaltyCustomerGroupDto[]|null  $loyaltyGroups
     */
    public function __construct(
        public ?int $id,
        public ?string $code,
        public ?string $name,
        public ?string $qrCode,
        public ?string $tel,
        public ?string $birthDay,
        public ?string $email,
        public ?bool $sex,
        public ?string $comment,
        public ?bool $allowPromotions,
        public ?string $cerateDate,
        public ?array $loyaltyPrograms = null,
        public ?array $loyaltyGroups = null,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            qrCode: isset($data['qr_code']) ? (string) $data['qr_code'] : null,
            tel: isset($data['tel']) ? (string) $data['tel'] : null,
            birthDay: isset($data['birth_day']) ? (string) $data['birth_day'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            sex: isset($data['sex']) ? (bool) $data['sex'] : null,
            comment: isset($data['comment']) ? (string) $data['comment'] : null,
            allowPromotions: isset($data['allow_promotions']) ? (bool) $data['allow_promotions'] : null,
            cerateDate: isset($data['cerate_date']) ? (string) $data['cerate_date'] : null,
            loyaltyPrograms: isset($data['loyalty_programs'])
                ? array_map(
                    static fn ($p) => LoyaltyCustomerProgramDto::fromArray((array) $p),
                    array_values((array) $data['loyalty_programs'])
                )
                : null,
            loyaltyGroups: isset($data['loyalty_groups'])
                ? array_map(
                    static fn ($g) => LoyaltyCustomerGroupDto::fromArray((array) $g),
                    array_values((array) $data['loyalty_groups'])
                )
                : null,
            raw: $data,
        );
    }
}
