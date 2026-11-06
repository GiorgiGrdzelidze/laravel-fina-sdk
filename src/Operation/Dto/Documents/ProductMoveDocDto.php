<?php

declare(strict_types=1);

/**
 * Operation DTO: typed getDocProductMove response (doc type 20).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * Moves carry no currency: `amount` and every line's `self_cost` are cost
 * values; lines have no price.
 */
final readonly class ProductMoveDocDto
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
        public ?string $waybillNum,
        public string $purpose,
        public float $amount,
        public int $storeFrom,
        public int $storeTo,
        public int $user,
        public int $staff,
        public bool $makeEntry,
        public int $tType,
        public int $tPayer,
        public float $wCost,
        public bool $foreign,
        public string $drvName,
        public string $trStart,
        public string $trEnd,
        public string $driverId,
        public string $carNum,
        public string $trText,
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
            waybillNum: isset($data['waybill_num']) ? (string) $data['waybill_num'] : null,
            purpose: (string) ($data['purpose'] ?? ''),
            amount: (float) ($data['amount'] ?? 0.0),
            storeFrom: (int) ($data['store_from'] ?? 0),
            storeTo: (int) ($data['store_to'] ?? 0),
            user: (int) ($data['user'] ?? 0),
            staff: (int) ($data['staff'] ?? 0),
            makeEntry: (bool) ($data['make_entry'] ?? false),
            tType: (int) ($data['t_type'] ?? 0),
            tPayer: (int) ($data['t_payer'] ?? 0),
            wCost: (float) ($data['w_cost'] ?? 0.0),
            foreign: (bool) ($data['foreign'] ?? false),
            drvName: (string) ($data['drv_name'] ?? ''),
            trStart: (string) ($data['tr_start'] ?? ''),
            trEnd: (string) ($data['tr_end'] ?? ''),
            driverId: (string) ($data['driver_id'] ?? ''),
            carNum: (string) ($data['car_num'] ?? ''),
            trText: (string) ($data['tr_text'] ?? ''),
            addFields: DocLines::addFields($data['add_fields'] ?? []),
            products: DocLines::products($data['products'] ?? []),
            raw: $data,
        );
    }
}
