<?php

declare(strict_types=1);

/**
 * Operation DTO: payload for saveCustomer / saveVendor.
 */

namespace Fina\Sdk\Laravel\Operation\Dto;

use DateTimeInterface;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Operation\CustomersApi;
use Fina\Sdk\Laravel\Operation\VendorsApi;
use Fina\Sdk\Laravel\Support\FinaDate;

/**
 * Insert (`id` 0) or update a customer or vendor; both endpoints take the same body. Optional
 * fields left null are not sent (FINA's defaults: group 5 for customers, 3 for vendors).
 * `marketing_promotions` (8.0.20260511) is in the 10.0 document.
 *
 * @see CustomersApi::save()
 * @see VendorsApi::save()
 */
final class ContragentPayload implements ValidatesPayload
{
    /**
     * @param  int  $id  Customer / vendor ID (0 for new).
     * @param  string  $code  Identification code or personal number.
     * @param  int|null  $vatType  0 not a VAT payer, 1 VAT payer, 2 exempt with deduction, 3 exempt without.
     * @param  bool|null  $isResident  true local, false foreign.
     * @param  bool|null  $isCompany  true legal entity, false natural person.
     * @param  int|null  $consPeriod  Consignment period, days.
     * @param  AddField[]|null  $addFields
     */
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?int $groupId = null,
        public readonly ?string $address = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?int $vatType = null,
        public readonly ?bool $isResident = null,
        public readonly ?bool $isCompany = null,
        public readonly ?int $consPeriod = null,
        public readonly ?DateTimeInterface $birthDate = null,
        public readonly ?bool $marketingPromotions = null,
        public readonly ?array $addFields = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'group_id' => $this->groupId,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'vat_type' => $this->vatType,
            'is_resident' => $this->isResident,
            'is_company' => $this->isCompany,
            'cons_period' => $this->consPeriod,
            'birth_date' => $this->birthDate ? FinaDate::toFina($this->birthDate) : null,
            'marketing_promotions' => $this->marketingPromotions,
            'add_fields' => $this->addFields === null
                ? null
                : array_map(fn (AddField $f) => $f->toArray(), $this->addFields),
        ], static fn ($v) => $v !== null);
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:0'],
            'code' => ['present', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'group_id' => ['nullable', 'integer', 'min:1'],
            'address' => ['nullable', 'string', 'max:200'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:50'],
            'vat_type' => ['nullable', 'integer', 'in:0,1,2,3'],
            'is_resident' => ['nullable', 'boolean'],
            'is_company' => ['nullable', 'boolean'],
            'cons_period' => ['nullable', 'integer', 'min:0'],
            'birth_date' => ['nullable', 'string'],
            'marketing_promotions' => ['nullable', 'boolean'],
            'add_fields' => ['nullable', 'array'],
            'add_fields.*.field' => ['required', 'string'],
            'add_fields.*.value' => ['present', 'string'],
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
