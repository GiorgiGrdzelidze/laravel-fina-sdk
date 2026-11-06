<?php

declare(strict_types=1);

/**
 * Operation DTO: typed getDocProductOutSingle response (retail sale, doc type 23).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * Retail receipts have no customer, waybill or transport block and only
 * `pay_type` 0 (cash), 1 (non-cash) or 6 (other). The same product may appear
 * on several lines with different prices.
 */
final readonly class ProductOutSingleDocDto
{
    /**
     * @param  array<int, array{field: string, value: string}>  $addFields
     * @param  DocProductLineDto[]  $products
     */
    public function __construct(
        public int $id,
        public string $date,
        public string $numPfx,
        public int $num,
        public string $purpose,
        public float $amount,
        public string $currency,
        public float $rate,
        public int $store,
        public int $user,
        public int $staff,
        public int $project,
        public bool $isVat,
        public bool $makeEntry,
        public int $payType,
        public array $addFields,
        public array $products,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            date: (string) ($data['date'] ?? ''),
            numPfx: (string) ($data['num_pfx'] ?? ''),
            num: (int) ($data['num'] ?? 0),
            purpose: (string) ($data['purpose'] ?? ''),
            amount: (float) ($data['amount'] ?? 0.0),
            currency: (string) ($data['currency'] ?? ''),
            rate: (float) ($data['rate'] ?? 0.0),
            store: (int) ($data['store'] ?? 0),
            user: (int) ($data['user'] ?? 0),
            staff: (int) ($data['staff'] ?? 0),
            project: (int) ($data['project'] ?? 0),
            isVat: (bool) ($data['is_vat'] ?? false),
            makeEntry: (bool) ($data['make_entry'] ?? false),
            payType: (int) ($data['pay_type'] ?? 0),
            addFields: DocLines::addFields($data['add_fields'] ?? []),
            products: DocLines::products($data['products'] ?? []),
            raw: $data,
        );
    }
}
