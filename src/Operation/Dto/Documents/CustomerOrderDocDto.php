<?php

declare(strict_types=1);

/**
 * Operation DTO: typed getDocCustomerOrder response (doc type 8).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * `order_status`: 1 active, 2 received, 3 cancelled.
 */
final readonly class CustomerOrderDocDto
{
    /**
     * @param  array<int, array{field: string, value: string}>  $addFields
     * @param  DocProductLineDto[]  $products
     * @param  DocServiceLineDto[]  $services
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
        public int $customer,
        public int $user,
        public int $staff,
        public int $project,
        public bool $isVat,
        public int $payType,
        public string $trStart,
        public string $trEnd,
        public bool $reserved,
        public int $orderStatus,
        public array $addFields,
        public array $products,
        public array $services,
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
            customer: (int) ($data['customer'] ?? 0),
            user: (int) ($data['user'] ?? 0),
            staff: (int) ($data['staff'] ?? 0),
            project: (int) ($data['project'] ?? 0),
            isVat: (bool) ($data['is_vat'] ?? false),
            payType: (int) ($data['pay_type'] ?? 0),
            trStart: (string) ($data['tr_start'] ?? ''),
            trEnd: (string) ($data['tr_end'] ?? ''),
            reserved: (bool) ($data['reserved'] ?? false),
            orderStatus: (int) ($data['order_status'] ?? 0),
            addFields: DocLines::addFields($data['add_fields'] ?? []),
            products: DocLines::products($data['products'] ?? []),
            services: DocLines::services($data['services'] ?? []),
            raw: $data,
        );
    }
}
