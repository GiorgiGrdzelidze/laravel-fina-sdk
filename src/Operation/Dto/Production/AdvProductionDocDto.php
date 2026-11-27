<?php

declare(strict_types=1);

/**
 * Operation DTO: typed getDocAdvProduction response (რთული წარმოება/დაშლა, 10.0.20260625).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Production;

use Fina\Sdk\Laravel\Operation\DocumentsApi;

/**
 * `materials` are the goods consumed or taken apart, `products` the goods made; each product
 * line carries `cost_percent`, its share of the materials' cost. `portion` is the quantity.
 * A field the answer lacks is null; an answer without an `adv_production` object gives a DTO
 * whose fields are all null.
 *
 * @see DocumentsApi::getAdvProductionTyped()
 */
final readonly class AdvProductionDocDto
{
    /**
     * @param  AdvProductionLineDto[]|null  $materials
     * @param  AdvProductionLineDto[]|null  $products
     */
    public function __construct(
        public ?int $id,
        public ?string $date,
        public ?string $numPrefix,
        public ?int $num,
        public ?string $purpose,
        public ?float $amount,
        public ?float $portion,
        public ?int $store,
        public ?int $user,
        public ?bool $makeEntry,
        public ?array $materials,
        public ?array $products,
        public array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            date: isset($data['date']) ? (string) $data['date'] : null,
            numPrefix: isset($data['num_pfx']) ? (string) $data['num_pfx'] : null,
            num: isset($data['num']) ? (int) $data['num'] : null,
            purpose: isset($data['purpose']) ? (string) $data['purpose'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            portion: isset($data['portion']) ? (float) $data['portion'] : null,
            store: isset($data['store']) ? (int) $data['store'] : null,
            user: isset($data['user']) ? (int) $data['user'] : null,
            makeEntry: isset($data['make_entry']) ? (bool) $data['make_entry'] : null,
            materials: self::lines($data['materials'] ?? null),
            products: self::lines($data['products'] ?? null),
            raw: $data,
        );
    }

    /**
     * @return AdvProductionLineDto[]|null
     */
    private static function lines(mixed $rows): ?array
    {
        if ($rows === null) {
            return null;
        }

        return array_map(
            static fn ($r) => AdvProductionLineDto::fromArray((array) $r),
            array_values((array) $rows)
        );
    }
}
