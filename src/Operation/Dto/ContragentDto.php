<?php

declare(strict_types=1);

/**
 * Operation DTO: customer or vendor from getCustomers / getCustomersByCode / getVendors / getVendorsByCode.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use Fina\Sdk\Laravel\Operation\CustomersApi;
use Fina\Sdk\Laravel\Operation\Dto\Documents\DocLines;
use Fina\Sdk\Laravel\Operation\VendorsApi;

/**
 * `vat_type`: 0 not a VAT payer, 1 VAT payer, 2 exempt with the right of deduction,
 * 3 exempt without it. A field the answer lacks is null, never 0, false, '' or []: an older
 * build leaves out `marketing_promotions` (8.0.20260511) and `birth_date`, and null passed on
 * to {@see ContragentPayload} is not sent.
 *
 * @see CustomersApi::allTyped()
 * @see VendorsApi::allTyped()
 */
final readonly class ContragentDto
{
    /**
     * @param  array<int, array{field: string|null, value: string|null}>|null  $addFields
     */
    public function __construct(
        public int $id,
        public ?int $groupId,
        public ?string $code,
        public ?string $name,
        public ?string $address,
        public ?string $phone,
        public ?string $email,
        public ?bool $isCompany,
        public ?bool $isResident,
        public ?int $vatType,
        public ?int $consPeriod,
        public ?string $birthDate,
        public ?bool $marketingPromotions,
        public ?array $addFields = null,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            isCompany: isset($data['is_company']) ? (bool) $data['is_company'] : null,
            isResident: isset($data['is_resident']) ? (bool) $data['is_resident'] : null,
            vatType: isset($data['vat_type']) ? (int) $data['vat_type'] : null,
            consPeriod: isset($data['cons_period']) ? (int) $data['cons_period'] : null,
            birthDate: isset($data['birth_date']) ? (string) $data['birth_date'] : null,
            marketingPromotions: isset($data['marketing_promotions']) ? (bool) $data['marketing_promotions'] : null,
            addFields: isset($data['add_fields']) ? DocLines::addFieldsAsSent($data['add_fields']) : null,
            raw: $data,
        );
    }
}
