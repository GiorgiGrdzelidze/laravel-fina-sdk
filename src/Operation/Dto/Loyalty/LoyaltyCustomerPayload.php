<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveLoyaltyCustomer (10.0.20260713).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Loyalty;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\LoyaltyApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Insert (`id` 0) or update a loyalty (Cloud) card. A non-null `loyaltyPrograms` / `loyaltyGroups`
 * becomes the card's whole list: FINA inserts, updates and deletes to match it, so an empty array
 * removes them all. Leave them null to keep what the card has. Optional fields left null are not sent.
 *
 * @see LoyaltyApi::saveCustomer()
 */
final class LoyaltyCustomerPayload implements ValidatesPayload
{
    /**
     * @param  int  $id  Card ID (0 for new).
     * @param  string|null  $code  Holder's personal number.
     * @param  bool|null  $sex  true male, false female.
     * @param  string|null  $qrCode  Card's unique number.
     * @param  LoyaltyCustomerProgramLine[]|null  $loyaltyPrograms
     * @param  LoyaltyCustomerGroupLine[]|null  $loyaltyGroups
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $code = null,
        public readonly ?string $tel = null,
        public readonly ?string $email = null,
        public readonly ?bool $sex = null,
        public readonly ?DateTimeInterface $birthDay = null,
        public readonly ?string $qrCode = null,
        public readonly ?string $comment = null,
        public readonly ?bool $allowPromotions = null,
        public readonly ?array $loyaltyPrograms = null,
        public readonly ?array $loyaltyGroups = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'tel' => $this->tel,
            'email' => $this->email,
            'sex' => $this->sex,
            'birth_day' => $this->birthDay ? FinaDate::toFina($this->birthDay) : null,
            'qr_code' => $this->qrCode,
            'comment' => $this->comment,
            'allow_promotions' => $this->allowPromotions,
            'loyalty_programs' => $this->loyaltyPrograms === null
                ? null
                : array_map(fn (LoyaltyCustomerProgramLine $p) => $p->toArray(), $this->loyaltyPrograms),
            'loyalty_groups' => $this->loyaltyGroups === null
                ? null
                : array_map(fn (LoyaltyCustomerGroupLine $g) => $g->toArray(), $this->loyaltyGroups),
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'name' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'tel' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'sex' => ['nullable', 'boolean'],
            'birth_day' => ['nullable', 'string'],
            'qr_code' => ['nullable', 'string'],
            'comment' => ['nullable', 'string'],
            'allow_promotions' => ['nullable', 'boolean'],
            'loyalty_programs' => ['nullable', 'array'],
            'loyalty_programs.*.id' => ['required', 'integer', 'min:0'],
            'loyalty_programs.*.program_id' => ['required', 'integer', 'min:1'],
            'loyalty_programs.*.status' => ['required', 'boolean'],
            'loyalty_groups' => ['nullable', 'array'],
            'loyalty_groups.*.id' => ['required', 'integer', 'min:0'],
            'loyalty_groups.*.group_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [
            'loyalty_programs.*.program_id' => 'loyalty program id',
            'loyalty_groups.*.group_id' => 'loyalty group id',
        ];
    }
}
