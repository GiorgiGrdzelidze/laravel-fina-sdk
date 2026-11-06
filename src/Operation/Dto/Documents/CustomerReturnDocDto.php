<?php

declare(strict_types=1);

/**
 * Operation DTO: typed getDocCustomerReturn response (doc type 9).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * Each product line carries `out_id`, the id of the sale it reverses
 * (0 when the return is not tied to one sale).
 */
final readonly class CustomerReturnDocDto
{
    /**
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
        public string $currency,
        public float $rate,
        public int $store,
        public int $customer,
        public int $user,
        public int $staff,
        public int $project,
        public bool $isVat,
        public bool $makeEntry,
        public int $payType,
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
            currency: (string) ($data['currency'] ?? ''),
            rate: (float) ($data['rate'] ?? 0.0),
            store: (int) ($data['store'] ?? 0),
            customer: (int) ($data['customer'] ?? 0),
            user: (int) ($data['user'] ?? 0),
            staff: (int) ($data['staff'] ?? 0),
            project: (int) ($data['project'] ?? 0),
            isVat: (bool) ($data['is_vat'] ?? false),
            makeEntry: (bool) ($data['make_entry'] ?? false),
            payType: (int) ($data['pay_type'] ?? 0),
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
            products: DocLines::products($data['products'] ?? []),
            raw: $data,
        );
    }
}
