<?php

declare(strict_types=1);

/**
 * Operation DTO: typed getDocProductOut response (doc type 21).
 */

namespace Fina\Sdk\Laravel\Operation\Dto\Documents;

/**
 * `check_status` is FINA's "შემოწმების სტატუსი" flag (added in 7.0.20230210).
 * `pay_type`: 0 cash, 1 non-cash, 2 consignment, 3 credit, 4 cash/non-cash,
 * 5 free of charge, 6 other. `w_type`: 2 with transport, 3 without.
 */
final readonly class ProductOutDocDto
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
        public int $priceType,
        public int $wType,
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
        public string $sender,
        public string $reciever,
        public string $comment,
        public int $overlapType,
        public float $overlapAmount,
        public bool $checkStatus,
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
            priceType: (int) ($data['price_type'] ?? 0),
            wType: (int) ($data['w_type'] ?? 0),
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
            sender: (string) ($data['sender'] ?? ''),
            reciever: (string) ($data['reciever'] ?? ''),
            comment: (string) ($data['comment'] ?? ''),
            overlapType: (int) ($data['overlap_type'] ?? 0),
            overlapAmount: (float) ($data['overlap_amount'] ?? 0.0),
            checkStatus: (bool) ($data['check_status'] ?? false),
            addFields: DocLines::addFields($data['add_fields'] ?? []),
            products: DocLines::products($data['products'] ?? []),
            services: DocLines::services($data['services'] ?? []),
            raw: $data,
        );
    }
}
